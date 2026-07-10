<?php
// pages/user_admin_plan.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../conn.php';

// Interceptor para verificar la existencia del correo antes de enviar invitación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_email_exists') {
    header('Content-Type: application/json');
    
    // Verificación de sesión
    if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['soc_id_socio'])) {
        echo json_encode(['success' => false, 'message' => 'Acceso denegado. Sesión no iniciada.']);
        exit;
    }
    
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, ingrese un correo válido.']);
        exit;
    }
    
    try {
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM san_socios WHERE soc_correo = ?");
        $stmtCheck->execute([$email]);
        $exists = $stmtCheck->fetchColumn() > 0;
        
        if (!$exists) {
            echo json_encode([
                'success' => false,
                'message' => 'El correo ingresado no pertenece a un miembro registrado del gimnasio.'
            ]);
            exit;
        }
        
        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $e->getMessage()]);
        exit;
    }
}

if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['soc_id_socio'])) {
    echo "<script>window.location.href = 'index.php?page=login';</script>";
    exit;
}
$idUsuarioLogueado = $_SESSION['admin']['soc_id_socio'];

// =======================================================
// ⚙️ DEFINIR SOLO LOS PLANES TITULARES 
// (Los hijos 125, 126, 127 no van aquí)
// =======================================================
$planesTitulares = [
    123 => 3,  // Plan Grupal 3 (Titular)
    124 => 4,  // Plan Grupal 4 (Titular)
    167 => 2   // Plan Parejas (Titular)
];
// =======================================================

try {
    $esTitular = false;
    $idTitularReal = 0;

    // 1. Verificamos si soy el Titular de un plan Padre
    $stmtPago = $conn->prepare("SELECT pag_id_servicio FROM san_pagos WHERE pag_id_socio = ? AND pag_status = 'A' AND pag_fecha_fin >= CURDATE() ORDER BY pag_id_pago DESC LIMIT 1");
    $stmtPago->execute([$idUsuarioLogueado]);
    $miPago = $stmtPago->fetch(PDO::FETCH_ASSOC);

    if ($miPago && array_key_exists($miPago['pag_id_servicio'], $planesTitulares)) {
        $esTitular = true;
        $idTitularReal = $idUsuarioLogueado;
    } else {
        // 2. Si no soy titular, busco a mi Padrino (Titular Real)
        $stmtRef = $conn->prepare("SELECT soc_id_titular_grupo FROM san_socios WHERE soc_id_socio = ?");
        $stmtRef->execute([$idUsuarioLogueado]);
        $resRef = $stmtRef->fetch(PDO::FETCH_ASSOC);

        if ($resRef && $resRef['soc_id_titular_grupo'] > 0) {
            $esTitular = false;
            $idTitularReal = $resRef['soc_id_titular_grupo'];
        } else {
            mostrarError("No tienes un Plan Familiar activo ni estás vinculado a uno.");
            return;
        }
    }

    // 3. Obtenemos los datos del Titular Real y su Plan
    $stmtPlan = $conn->prepare("
        SELECT s.soc_nombres, s.soc_imagen, sv.ser_descripcion, pg.pag_id_servicio, pg.pag_fecha_fin 
        FROM san_socios s
        JOIN san_pagos pg ON pg.pag_id_socio = s.soc_id_socio
        JOIN san_servicios sv ON sv.ser_id_servicio = pg.pag_id_servicio
        WHERE s.soc_id_socio = ? AND pg.pag_status = 'A' AND pg.pag_fecha_fin >= CURDATE()
        ORDER BY pg.pag_fecha_fin DESC LIMIT 1
    ");
    $stmtPlan->execute([$idTitularReal]);
    $planActual = $stmtPlan->fetch(PDO::FETCH_ASSOC);

    $idServicio = $planActual['pag_id_servicio'] ?? 0;
    
    if (!$planActual || !array_key_exists($idServicio, $planesTitulares)) {
        // Si el plan caducó o cambió, desvinculamos a todos sus referidos (Spotify model)
        $stmtDesv = $conn->prepare("UPDATE san_socios SET soc_id_titular_grupo = 0 WHERE soc_id_titular_grupo = ?");
        $stmtDesv->execute([$idTitularReal]);
        mostrarError("El plan grupal ha expirado o el titular ya no está activo.");
        return;
    }

    $nombrePlan = $planActual['ser_descripcion'];
    $totalSlots = $planesTitulares[$idServicio] ?? 3; 
    $fotoTitular = !empty($planActual['soc_imagen']) ? $planActual['soc_imagen'] : 'assets/img/avatar_default.png';
    $nombreTitular = explode(' ', trim($planActual['soc_nombres']))[0];
    $fechaFinTitular = $planActual['pag_fecha_fin'];

    // 4. Obtenemos a los hermanos (beneficiarios) y validamos su ciclo
    $stmtMiembros = $conn->prepare("SELECT soc_id_socio, soc_nombres, soc_imagen FROM san_socios WHERE soc_id_titular_grupo = ?");
    $stmtMiembros->execute([$idTitularReal]);
    $posiblesBeneficiarios = $stmtMiembros->fetchAll(PDO::FETCH_ASSOC);

    $beneficiarios = [];
    foreach ($posiblesBeneficiarios as $b) {
        $qCheck = $conn->prepare("
            SELECT pag_fecha_fin 
            FROM san_pagos 
            WHERE pag_id_socio = ? AND pag_status = 'A' AND pag_id_servicio IN (125, 126, 127)
            ORDER BY pag_fecha_fin DESC LIMIT 1
        ");
        $qCheck->execute([$b['soc_id_socio']]);
        $pagoBen = $qCheck->fetch(PDO::FETCH_ASSOC);

        // Si el beneficiario es de un ciclo anterior (diferente fecha de fin) o su plan expiró, se desvincula.
        if (!$pagoBen || $pagoBen['pag_fecha_fin'] < date('Y-m-d') || $pagoBen['pag_fecha_fin'] != $fechaFinTitular) {
            $stmtDesv = $conn->prepare("UPDATE san_socios SET soc_id_titular_grupo = 0 WHERE soc_id_socio = ?");
            $stmtDesv->execute([$b['soc_id_socio']]);
            
            if ($b['soc_id_socio'] == $idUsuarioLogueado) {
                mostrarError("El ciclo de tu membresía grupal ha finalizado. El Titular debe enviarte una nueva invitación.");
                return;
            }
        } else {
            $beneficiarios[] = $b;
        }
    }

    // 5. Armamos la lista (El Titular siempre es el primero)
    $miembros = [];
    $miembros[] = [
        'id' => $idTitularReal, 
        'nombre' => $nombreTitular, 
        'rol' => 'Titular', 
        'foto' => $fotoTitular, 
        'es_yo' => ($idTitularReal == $idUsuarioLogueado)
    ];
    
    // Agregamos a los hermanos/invitados que siguen vigentes
    foreach($beneficiarios as $b) {
        if (count($miembros) < $totalSlots) {
            $miembros[] = [
                'id' => $b['soc_id_socio'], 
                'nombre' => explode(' ', trim($b['soc_nombres']))[0], 
                'rol' => 'Beneficiario', 
                'foto' => !empty($b['soc_imagen']) ? $b['soc_imagen'] : 'assets/img/avatar_default.png', 
                'es_yo' => ($b['soc_id_socio'] == $idUsuarioLogueado)
            ];
        }
    }

} catch (PDOException $e) {
    die("Error SQL: " . $e->getMessage());
}

function mostrarError($msg) {
    echo '<div style="background:#0f0f0f; min-height:80vh; padding-top:100px; display:flex; align-items:center; justify-content:center; color:white;"><div class="text-center p-4 border border-secondary rounded"><h3 class="text-danger">Aviso</h3><p>'.$msg.'</p><a href="index.php?page=user_home" class="btn btn-outline-light mt-3">Volver al Home</a></div></div>';
}

// El enlace se generará dinámicamente vía AJAX usando tokens
$linkInvitacion = "";
?>

<style>
    /* Estilos del Administrador de Plan */
    .admin-plan-wrapper { background-color: #050505; color: #e0e0e0; font-family: 'Muli', sans-serif; padding-top: 120px; padding-bottom: 80px; min-height: 100vh; }
    
    .plan-header-card { 
        background: linear-gradient(135deg, #121212 0%, #0a0a0a 100%); 
        border-radius: 16px; padding: 35px 30px; margin-bottom: 40px; 
        border: 1px solid #222; border-left: 6px solid #ef4444; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.8); 
    }
    
    /* Tarjeta Base */
    .slot-card { 
        background-color: #121212; border: 1px solid #2a2a2a; border-radius: 16px; 
        padding: 30px 20px; text-align: center; height: 100%; transition: all 0.3s ease; 
        display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; 
    }
    
    /* Tarjeta Ocupada */
    .slot-card.occupied:hover { 
        border-color: #ef4444; transform: translateY(-5px); 
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.15); 
    }
    
    /* Tarjeta Vacía (Dashed) */
    .slot-card.empty-slot {
        background-color: transparent; border: 2px dashed #333; cursor: pointer;
    }
    .slot-card.empty-slot:hover {
        border-color: #ef4444; background-color: rgba(239, 68, 68, 0.05); transform: translateY(-3px);
    }

    /* Avatares */
    .avatar-circle { 
        width: 80px; height: 80px; border-radius: 50%; background: #1a1a1a; 
        border: 2px solid #333; display: flex; align-items: center; justify-content: center; 
        margin-bottom: 15px; overflow: hidden; font-size: 35px; color: #555;
    }
    .slot-card.occupied .avatar-circle { border-color: #ef4444; }
    .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
    
    /* Avatares Vacíos */
    .avatar-circle.empty { border: none; background: #222; color: #ef4444; font-size: 28px; transition: 0.3s; }
    .slot-card.empty-slot:hover .avatar-circle.empty { background: #ef4444; color: #fff; transform: scale(1.1); }

    /* Botón Eliminar Moderno */
    .btn-delete { 
        position: absolute; top: 15px; right: 15px; background: transparent; 
        border: none; color: #555; width: 30px; height: 30px; border-radius: 50%; 
        transition: 0.3s; cursor: pointer; display: flex; align-items: center; justify-content: center; 
    }
    .btn-delete:hover { background: #ef4444; color: white; }
    /* Botón de Regresar Estilo Frontend */
    .btn-back {
        display: inline-flex;
        align-items: center;
        background: #1a1a1a;
        color: #fff;
        border: 1px solid #333;
        padding: 8px 20px;
        border-radius: 50px; /* Estilo píldora según manual */
        text-decoration: none;
        transition: all 0.3s ease;
        margin-bottom: 20px;
        font-size: 14px;
        cursor: pointer;
    }

    .btn-back:hover {
        background: #ef4444; /* Acento rojo */
        color: #fff;
        border-color: #ef4444;
        transform: translateX(-5px);
        text-decoration: none;
    }

    .btn-back i {
        margin-right: 8px;
    }

    /* Corrección estética: Input de correo oscuro al escribir */
    #inviteEmail {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
    }
    #inviteEmail:focus {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
        border-color: #555;
        box-shadow: none;
    }
</style>

<div class="admin-plan-wrapper">
    <div class="container">
        <a href="javascript:void(0);" onclick="history.back();" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
        <div class="plan-header-card">
            <h2 class="text-white text-uppercase" style="font-family: 'Oswald', sans-serif; letter-spacing: 1px;">
                <?php echo $esTitular ? 'Administrar ' : 'Mi '; ?> 
                <span class="text-danger"><?php echo htmlspecialchars($nombrePlan); ?></span>
            </h2>
            <p class="text-muted mb-0 mt-2" style="font-size: 1.1rem;">
                Espacios ocupados: <strong><?php echo (count($miembros)); ?> / <?php echo $totalSlots; ?></strong>
            </p>
            
            <?php if(!$esTitular): ?>
                <div class="alert alert-dark mt-3 mb-0" style="border: 1px solid #444; background: #1a1a1a; color: #aaa; font-size: 14px;">
                    <i class="fas fa-info-circle mr-2"></i> Eres un beneficiario de este plan. Solo el Titular puede invitar o remover personas.
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <?php for ($i = 0; $i < $totalSlots; $i++): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    
                    <?php if (isset($miembros[$i])): $m = $miembros[$i]; ?>
                        <div class="slot-card occupied" id="slot_<?php echo $m['id']; ?>" <?php echo $m['es_yo'] ? 'style="border-color: #ef4444;"' : ''; ?>>
                            
                            <?php if ($esTitular && $m['rol'] != 'Titular'): ?>
                                <button class="btn-delete" onclick="eliminarMiembro(<?php echo $m['id']; ?>, '<?php echo addslashes($m['nombre']); ?>')" title="Desvincular usuario">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>

                            <div class="avatar-circle">
                                <?php if (strpos($m['foto'], 'avatar_default') !== false || empty($m['foto'])): ?>
                                    <i class="fas fa-user"></i>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($m['foto']); ?>" alt="Foto" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <i class="fas fa-user" style="display:none;"></i>
                                <?php endif; ?>
                            </div>
                            
                            <h5 class="text-white mb-1" style="font-family: 'Oswald', sans-serif; letter-spacing: 0.5px;">
                                <?php echo htmlspecialchars($m['nombre']); ?>
                            </h5>
                            
                            <span class="badge <?php echo $m['rol'] == 'Titular' ? 'badge-danger' : 'badge-dark border border-secondary'; ?> px-3 py-1 mt-1 rounded-pill" style="font-weight: 400;">
                                <?php echo $m['rol']; ?>
                            </span>
                            
                            <?php if ($m['es_yo']): ?>
                                <small class="d-block mt-2 text-success" style="font-weight: bold;">(Tú)</small>
                            <?php endif; ?>
                        </div>
                    
                    <?php else: ?>
                        <div class="slot-card empty-slot" id="slot_libre_<?php echo $i; ?>" <?php echo $esTitular ? 'onclick="abrirModalInvitacion()"' : ''; ?> style="<?php echo !$esTitular ? 'cursor: not-allowed; opacity: 0.5;' : ''; ?>">
                            <div class="avatar-circle empty">
                                <i class="fas fa-plus"></i>
                            </div>
                            <h5 class="text-muted mb-1" style="font-family: 'Oswald', sans-serif;">ESPACIO LIBRE</h5>
                            
                            <?php if ($esTitular): ?>
                                <span class="text-danger small font-weight-bold mt-2"><i class="fas fa-link mr-1"></i> Invitar / Agregar</span>
                            <?php else: ?>
                                <span class="text-secondary small mt-2">Disponible</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endfor; ?>
        </div>

    </div>
</div>

<?php if($esTitular): ?>
<div class="modal fade" id="modalAddMember" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #121212; border: 1px solid #333; color: white; border-radius: 16px;">
            <div class="modal-header" style="border-bottom: 1px solid #222; padding: 20px 25px;">
                <h5 class="modal-title text-uppercase" style="font-family: 'Oswald', sans-serif; font-size: 20px;">
                    <i class="fas fa-user-plus text-danger mr-2"></i>Invitar Integrante
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 1;">&times;</button>
            </div>
            <div class="modal-body text-center p-5">
                
                <div class="mb-4">
                    <i class="fas fa-link text-danger" style="font-size: 45px; opacity: 0.8;"></i>
                </div>
                
                <h4 class="text-white mb-3" style="font-family: 'Oswald', sans-serif;">Envía una Invitación Mágica</h4>
                
                <p class="text-muted small mb-4" style="font-size: 14px; line-height: 1.6;">
                    Comparte este enlace con la persona que deseas agregar a tu plan. 
                    <br><strong class="text-light">El enlace expirará en 48 horas.</strong>
                </p>

                <div class="p-3 mb-4" style="background: #0a0a0a; border: 1px dashed #444; border-radius: 10px; word-break: break-all; min-height: 50px;" id="linkContainer">
                    <div class="spinner-border text-danger spinner-border-sm" role="status" id="linkSpinner"></div>
                    <code class="text-success" style="font-size: 13px; display: none;" id="linkText"></code>
                </div>

                <div class="row mt-2 mb-4">
                    <div class="col-6 pr-2">
                        <button class="btn btn-success btn-block rounded-pill py-2 font-weight-bold" onclick="compartirWhatsApp()" id="btnWp" disabled>
                            <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                        </button>
                    </div>
                    <div class="col-6 pl-2">
                        <button class="btn btn-outline-light btn-block rounded-pill py-2 font-weight-bold" onclick="copiarLink()" style="border-color: #444;" id="btnCopy" disabled>
                            <i class="fas fa-copy mr-1"></i> Copiar
                        </button>
                    </div>
                </div>

                <hr style="border-color: #333;">
<div class="mt-4 text-left">
    <label class="text-muted small font-weight-bold">O enviar por correo electrónico:</label>
    <div class="input-group">
        <input type="email" class="form-control text-white border-secondary" id="inviteEmail" placeholder="correo@ejemplo.com" style="background-color: #1a1a1a !important; color: #fff !important; border-top-left-radius: 50px; border-bottom-left-radius: 50px;">
        <div class="input-group-append">
            <button class="btn btn-danger px-4 font-weight-bold" type="button" onclick="enviarPorEmail()" id="btnEmail" style="border-top-right-radius: 50px; border-bottom-right-radius: 50px;" disabled>Enviar</button>
        </div>
    </div>
</div>

            </div>
        </div>
    </div>
</div>

<script>
    let currentInviteLink = "";

    function abrirModalInvitacion() {
        $('#modalAddMember').modal('show');
        $('#linkText').hide();
        $('#linkSpinner').show();
        $('#btnWp, #btnCopy, #btnEmail').prop('disabled', true);
        
        // Fetch new token
        $.ajax({
            url: 'api/generate_invite_token.php',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    currentInviteLink = res.link;
                    $('#linkText').text(currentInviteLink).fadeIn();
                    $('#btnWp, #btnCopy, #btnEmail').prop('disabled', false);
                } else {
                    $('#linkText').text('Error al generar enlace').addClass('text-danger').removeClass('text-success').show();
                }
                $('#linkSpinner').hide();
            },
            error: function() {
                $('#linkText').text('Error de conexión').addClass('text-danger').removeClass('text-success').show();
                $('#linkSpinner').hide();
            }
        });
    }

    function copiarLink() {
        if (!currentInviteLink) return;
        navigator.clipboard.writeText(currentInviteLink).then(() => {
            Swal.fire({
                icon: 'success', 
                title: '¡Enlace Copiado!', 
                text: 'Ya puedes pegarlo y enviarlo.', 
                timer: 2000, 
                showConfirmButton: false, 
                background: '#1a1a1a', 
                color: '#fff'
            });
        });
    }

    function compartirWhatsApp() {
        if (!currentInviteLink) return;
        const mensaje = encodeURIComponent("¡Hola! Te he invitado a unirte a mi Plan Familiar en Sandy's Gym. Haz clic en este enlace mágico para aceptar la invitación y crear tu cuenta: " + currentInviteLink);
        window.open("https://wa.me/?text=" + mensaje, "_blank");
    }

    function enviarPorEmail() {
        const email = $('#inviteEmail').val().trim();
        if(!email) {
            Swal.fire({icon:'warning', title:'Atención', text:'Ingresa un correo electrónico', background: '#1a1a1a', color: '#fff'});
            return;
        }
        
        $('#btnEmail').html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        // Primero verificamos si el correo pertenece a un miembro registrado del gimnasio
        $.ajax({
            url: 'pages/user_admin_plan.php',
            type: 'POST',
            data: {
                action: 'check_email_exists',
                email: email
            },
            dataType: 'json',
            success: function(checkRes) {
                if (!checkRes.success) {
                    $('#btnEmail').text('Enviar').prop('disabled', false);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Validación',
                        text: checkRes.message,
                        background: '#1a1a1a',
                        color: '#fff'
                    });
                    return;
                }
                
                // Si el correo sí existe, procedemos con el envío del correo de invitación original
                $.ajax({
                    url: 'api/send_invitation_email.php',
                    type: 'POST',
                    data: {
                        email: email,
                        link: currentInviteLink,
                        nombre: '<?php echo addslashes($nombreTitular); ?>',
                        allow_existing: '1'
                    },
                    dataType: 'json',
                    success: function(res) {
                        $('#btnEmail').text('Enviar').prop('disabled', false);
                        if(res.success) {
                            Swal.fire({icon: 'success', title: 'Enviado', text: 'Invitación enviada por correo', background: '#1a1a1a', color: '#fff'});
                            $('#inviteEmail').val('');
                        } else {
                            Swal.fire({icon: 'error', title: 'Error', text: res.message, background: '#1a1a1a', color: '#fff'});
                        }
                    },
                    error: function() {
                        $('#btnEmail').text('Enviar').prop('disabled', false);
                        Swal.fire({icon: 'error', title: 'Error', text: 'Fallo de conexión al enviar la invitación', background: '#1a1a1a', color: '#fff'});
                    }
                });
            },
            error: function() {
                $('#btnEmail').text('Enviar').prop('disabled', false);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Fallo de conexión al verificar el correo electrónico',
                    background: '#1a1a1a',
                    color: '#fff'
                });
            }
        });
    }

    function eliminarMiembro(idSocio, nombreSocio) {
        Swal.fire({
            title: '¿Desvincular a ' + nombreSocio + '?',
            text: "Esta persona perderá el acceso a tu plan familiar inmediatamente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#333',
            confirmButtonText: 'Sí, desvincular',
            cancelButtonText: 'Cancelar',
            background: '#1a1a1a', color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({title: 'Procesando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }, background: '#1a1a1a', color: '#fff'});
                $.ajax({
                    url: 'api/delete_group_member.php',
                    type: 'POST',
                    data: { id_beneficiario: idSocio },
                    dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            Swal.close();
                            // Manipulación del DOM para reactividad
                            const slot = $('#slot_' + idSocio);
                            const parentCol = slot.parent();
                            
                            // Transformar la tarjeta en una vacía
                            const emptyCardHtml = `
                                <div class="slot-card empty-slot" onclick="abrirModalInvitacion()">
                                    <div class="avatar-circle empty">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <h5 class="text-muted mb-1" style="font-family: 'Oswald', sans-serif;">ESPACIO LIBRE</h5>
                                    <span class="text-danger small font-weight-bold mt-2"><i class="fas fa-link mr-1"></i> Invitar / Agregar</span>
                                </div>
                            `;
                            
                            // Animación suave de desaparición y aparición
                            slot.fadeOut(300, function() {
                                parentCol.html(emptyCardHtml);
                                parentCol.find('.empty-slot').hide().fadeIn(300);
                            });
                            
                            // Actualizar contador
                            let countText = $('.plan-header-card p strong').text();
                            let parts = countText.split(' / ');
                            if(parts.length == 2) {
                                let newCount = parseInt(parts[0]) - 1;
                                $('.plan-header-card p strong').text(newCount + ' / ' + parts[1]);
                            }
                            
                            Swal.fire({icon: 'success', title: 'Desvinculado', text: nombreSocio + ' ha sido removido del plan.', background: '#1a1a1a', color: '#fff', timer: 2000, showConfirmButton: false});
                        } else { 
                            Swal.fire({icon: 'error', title: 'Error', text: res.message, background: '#1a1a1a', color: '#fff'}); 
                        }
                    },
                    error: function() {
                        Swal.fire({icon: 'error', title: 'Error', text: 'Fallo de red', background: '#1a1a1a', color: '#fff'});
                    }
                });
            }
        });
    }
</script>
<?php endif; ?>
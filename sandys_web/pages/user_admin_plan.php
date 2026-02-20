<?php
// pages/user_admin_plan.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../conn.php';

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
        $stmtRef = $conn->prepare("SELECT soc_id_referido_por FROM san_socios WHERE soc_id_socio = ?");
        $stmtRef->execute([$idUsuarioLogueado]);
        $resRef = $stmtRef->fetch(PDO::FETCH_ASSOC);

        if ($resRef && $resRef['soc_id_referido_por'] > 0) {
            $esTitular = false;
            $idTitularReal = $resRef['soc_id_referido_por'];
        } else {
            mostrarError("No tienes un Plan Familiar activo ni estás vinculado a uno.");
            return;
        }
    }

    // 3. Obtenemos los datos del Titular Real y su Plan
    $stmtPlan = $conn->prepare("
        SELECT s.soc_nombres, s.soc_imagen, sv.ser_descripcion, pg.pag_id_servicio 
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
        mostrarError("El plan grupal ha expirado o el titular ya no está activo.");
        return;
    }

    $nombrePlan = $planActual['ser_descripcion'];
    $totalSlots = $planesTitulares[$idServicio] ?? 3; 
    $fotoTitular = !empty($planActual['soc_imagen']) ? $planActual['soc_imagen'] : 'assets/img/avatar_default.png';
    $nombreTitular = explode(' ', trim($planActual['soc_nombres']))[0];

    // 4. Obtenemos a los hermanos (beneficiarios)
    $stmtMiembros = $conn->prepare("SELECT soc_id_socio, soc_nombres, soc_imagen FROM san_socios WHERE soc_id_referido_por = ?");
    $stmtMiembros->execute([$idTitularReal]);
    $beneficiarios = $stmtMiembros->fetchAll(PDO::FETCH_ASSOC);

    // 5. Armamos la lista (El Titular siempre es el primero)
    $miembros = [];
    $miembros[] = [
        'id' => $idTitularReal, 
        'nombre' => $nombreTitular, 
        'rol' => 'Titular', 
        'foto' => $fotoTitular, 
        'es_yo' => ($idTitularReal == $idUsuarioLogueado)
    ];
    
    // Agregamos a los hermanos/invitados
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

$linkInvitacion = "http://" . $_SERVER['HTTP_HOST'] . "/SandysGym2/sandys_web/index.php?page=accept_invite&ref=" . base64_encode($idTitularReal);
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
</style>

<div class="admin-plan-wrapper">
    <div class="container">
        
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
                        <div class="slot-card occupied" <?php echo $m['es_yo'] ? 'style="border-color: #ef4444;"' : ''; ?>>
                            
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
                        <div class="slot-card empty-slot" <?php echo $esTitular ? 'data-toggle="modal" data-target="#modalAddMember"' : ''; ?> style="<?php echo !$esTitular ? 'cursor: not-allowed; opacity: 0.5;' : ''; ?>">
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
                    <br><strong class="text-light">Cuando se registren o inicien sesión desde el link, se vincularán automáticamente a tu grupo.</strong>
                </p>

                <div class="p-3 mb-5" style="background: #0a0a0a; border: 1px dashed #444; border-radius: 10px; word-break: break-all;">
                    <code class="text-success" style="font-size: 13px;" id="linkText">
                        <?php echo htmlspecialchars($linkInvitacion); ?>
                    </code>
                </div>

                <div class="row mt-2">
                    <div class="col-6 pr-2">
                        <button class="btn btn-success btn-block rounded-pill py-2 font-weight-bold" onclick="compartirWhatsApp()">
                            <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                        </button>
                    </div>
                    <div class="col-6 pl-2">
                        <button class="btn btn-outline-light btn-block rounded-pill py-2 font-weight-bold" onclick="copiarLink()" style="border-color: #444;">
                            <i class="fas fa-copy mr-1"></i> Copiar
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    const inviteLink = "<?php echo $linkInvitacion; ?>";

    function copiarLink() {
        navigator.clipboard.writeText(inviteLink).then(() => {
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
        const mensaje = encodeURIComponent("¡Hola! Te he invitado a unirte a mi Plan Familiar en Sandy's Gym. Haz clic en este enlace mágico para aceptar la invitación y crear tu cuenta: " + inviteLink);
        window.open("https://wa.me/?text=" + mensaje, "_blank");
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
                $.ajax({
                    url: 'api/delete_group_member.php',
                    type: 'POST',
                    data: { id_beneficiario: idSocio },
                    dataType: 'json',
                    success: function(res) {
                        if(res.success) { location.reload(); } 
                        else { Swal.fire({icon: 'error', title: 'Error', text: res.message, background: '#1a1a1a', color: '#fff'}); }
                    }
                });
            }
        });
    }
</script>
<?php endif; ?>
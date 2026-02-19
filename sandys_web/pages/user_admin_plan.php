<?php
// pages/user_admin_plan.php

// 1. CONFIGURACIÓN Y CONEXIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../conn.php';

// 2. SEGURIDAD
if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['soc_id_socio'])) {
    echo "<script>window.location.href = 'index.php?page=login';</script>";
    exit;
}
$idUsuarioLogueado = $_SESSION['admin']['soc_id_socio'];

// =======================================================
// ⚙️ CONFIGURACIÓN DE PLANES (IDs vs CAPACIDAD)
// =======================================================
$configuracionPlanes = [
    123 => 3,  // Plan Grupal 3
    167 => 2,  // Plan Parejas
    125 => 3,  // (Ejemplo)
    126 => 4   // Plan Grupal 4
];
// =======================================================

try {
    // 3. DETERMINAR ROL (¿Es titular o beneficiario?)
    $esTitular = false;
    $idTitularReal = 0;

    $stmtPago = $conn->prepare("SELECT pag_id_socio FROM san_pagos WHERE pag_id_socio = ? AND pag_status = 'A' AND pag_fecha_fin >= CURDATE() LIMIT 1");
    $stmtPago->execute([$idUsuarioLogueado]);
    
    if ($stmtPago->fetch()) {
        $esTitular = true;
        $idTitularReal = $idUsuarioLogueado;
    } else {
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

    // 4. DATOS DEL PLAN DEL TITULAR
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

    if (!$planActual || !array_key_exists($idServicio, $configuracionPlanes)) {
        mostrarError("El plan actual no está configurado como Plan Grupal o ha expirado.");
        return;
    }

    $nombrePlan = $planActual['ser_descripcion'];
    $totalSlots = $configuracionPlanes[$idServicio];
    $fotoTitular = !empty($planActual['soc_imagen']) ? $planActual['soc_imagen'] : 'assets/img/avatar_default.png';
    $nombreTitular = explode(' ', $planActual['soc_nombres'])[0];

    // 5. OBTENER MIEMBROS VINCULADOS
    $stmtMiembros = $conn->prepare("SELECT soc_id_socio, soc_nombres, soc_imagen FROM san_socios WHERE soc_id_referido_por = ? AND is_active = 1");
    $stmtMiembros->execute([$idTitularReal]);
    $beneficiarios = $stmtMiembros->fetchAll(PDO::FETCH_ASSOC);

    // Armar el arreglo de miembros (Titular siempre va primero)
    $miembros = [];
    $miembros[] = [
        'id' => $idTitularReal, 
        'nombre' => $nombreTitular, 
        'rol' => 'Titular', 
        'foto' => $fotoTitular, 
        'es_yo' => ($idTitularReal == $idUsuarioLogueado)
    ];
    
    foreach($beneficiarios as $b) {
        if (count($miembros) < $totalSlots) {
            $miembros[] = [
                'id' => $b['soc_id_socio'], 
                'nombre' => explode(' ', $b['soc_nombres'])[0], 
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
?>

<style>
    /* Estilos del Administrador de Plan */
    .admin-plan-wrapper { background-color: #0f0f0f; color: #e0e0e0; font-family: 'Muli', sans-serif; padding-top: 100px; padding-bottom: 80px; min-height: 100vh; }
    .plan-header-card { background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%); border-radius: 15px; padding: 40px 30px; margin-bottom: 40px; border: 1px solid #333; border-left: 5px solid #ef4444; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .slot-card { background-color: #161616; border: 1px solid #2a2a2a; border-radius: 16px; padding: 30px 20px; text-align: center; height: 100%; transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; }
    .slot-card:hover { border-color: #ef4444; transform: translateY(-5px); box-shadow: 0 8px 25px rgba(239, 68, 68, 0.2); background-color: #1a1a1a; }
    .avatar-circle { width: 90px; height: 90px; border-radius: 50%; background: #0f0f0f; border: 2px solid #333; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; overflow: hidden; }
    .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-circle.empty { border: 2px dashed #444; color: #444; font-size: 30px; background: transparent; }
    .slot-card:hover .avatar-circle.empty { color: #ef4444; border-color: #ef4444; }
    .btn-delete { position: absolute; top: 15px; right: 15px; background: rgba(239,68,68,0.1); border: none; color: #ef4444; width: 35px; height: 35px; border-radius: 50%; transition: 0.3s; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .btn-delete:hover { background: #ef4444; color: white; }
    .form-control-dark { background-color: #0f0f0f; border: 1px solid #333; color: white; border-radius: 8px; }
    .form-control-dark:focus { background-color: #000; border-color: #ef4444; color: white; box-shadow: none; }
    .btn-copy { background: #252525; color: #fff; border: 1px solid #444; }
    .btn-copy:hover { background: #333; color: #fff; }
</style>

<div class="admin-plan-wrapper">
    <div class="container">
        
        <div class="plan-header-card">
            <h2 class="text-white text-uppercase" style="font-family: 'Oswald', sans-serif; letter-spacing: 1px;">
                Administrar <span class="text-danger"><?php echo htmlspecialchars($nombrePlan); ?></span>
            </h2>
            <p class="text-muted mb-0 mt-2" style="font-size: 1.1rem;">
                Espacios disponibles: <strong><?php echo (count($miembros)); ?> / <?php echo $totalSlots; ?></strong>
            </p>
        </div>

        <div class="row">
            <?php for ($i = 0; $i < $totalSlots; $i++): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    
                    <?php if (isset($miembros[$i])): $m = $miembros[$i]; ?>
                        <div class="slot-card">
                            <?php if ($esTitular && $m['rol'] != 'Titular'): ?>
                                <button class="btn-delete" onclick="eliminarMiembro(<?php echo $m['id']; ?>, '<?php echo addslashes($m['nombre']); ?>')" title="Eliminar del plan">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php endif; ?>

                            <div class="avatar-circle">
                                <img src="<?php echo htmlspecialchars($m['foto']); ?>" alt="Foto">
                            </div>
                            <h5 class="text-white mb-1"><?php echo htmlspecialchars($m['nombre']); ?></h5>
                            <span class="badge <?php echo $m['rol'] == 'Titular' ? 'badge-danger' : 'badge-secondary'; ?> px-3 py-1 rounded-pill">
                                <?php echo $m['rol']; ?>
                            </span>
                            
                            <?php if ($m['es_yo']): ?>
                                <small class="d-block mt-2 text-success">(Tú)</small>
                            <?php endif; ?>
                        </div>
                    
                    <?php else: ?>
                        <div class="slot-card" style="cursor: pointer;" <?php echo $esTitular ? 'data-toggle="modal" data-target="#modalAddMember"' : 'onclick="Swal.fire(\'Aviso\', \'Solo el titular puede agregar miembros.\', \'info\')"'; ?>>
                            <div class="avatar-circle empty">
                                <i class="fas fa-plus"></i>
                            </div>
                            <h5 class="text-muted mb-1">Espacio Libre</h5>
                            <?php if ($esTitular): ?>
                                <span class="text-danger small font-weight-bold mt-2">Invitar / Agregar</span>
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
        <div class="modal-content" style="background-color: #121212; border: 1px solid #333; color: white;">
            <div class="modal-header" style="border-bottom: 1px solid #222;">
                <h5 class="modal-title text-uppercase" style="font-family: 'Oswald', sans-serif;"><i class="fas fa-user-plus text-danger mr-2"></i>Agregar Miembro</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center p-4">
                
                <p class="text-muted small mb-4 text-left">
                    Escribe el teléfono. <strong>Si ya es socio</strong>, lo vincularemos. <strong>Si es nuevo</strong>, pon también su nombre.
                </p>

                <form id="formAddMember">
                    <div class="form-group text-left mb-3">
                        <label class="small text-muted font-weight-bold">TELÉFONO CELULAR (OBLIGATORIO)</label>
                        <input type="tel" class="form-control form-control-dark" name="telefono_beneficiario" maxlength="10" placeholder="10 Dígitos" required>
                    </div>

                    <div class="form-group text-left mb-4">
                        <label class="small text-muted font-weight-bold">NOMBRE COMPLETO</label>
                        <input type="text" class="form-control form-control-dark" name="nombre_beneficiario" placeholder="Llenar solo si es usuario nuevo">
                    </div>
                </form>

                <button class="btn btn-danger btn-block rounded-pill font-weight-bold py-2 mb-4" id="btnGuardarMiembro">
                    Vincular / Crear Usuario
                </button>

                <div class="position-relative mb-4">
                    <hr style="border-color: #333;">
                    <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #121212; padding: 0 10px; color: #666; font-size: 0.8rem;">O ENVÍA UN LINK</span>
                </div>

                <div class="row">
                    <div class="col-7 pr-1">
                        <button class="btn btn-success btn-block rounded-pill btn-sm" onclick="compartirWhatsApp()">
                            <i class="fab fa-whatsapp mr-1"></i> Enviar a WhatsApp
                        </button>
                    </div>
                    <div class="col-5 pl-1">
                        <button class="btn btn-copy btn-block rounded-pill btn-sm" onclick="copiarLink()">
                            <i class="fas fa-copy mr-1"></i> Copiar
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php 
$linkInvitacion = "http://" . $_SERVER['HTTP_HOST'] . "/gym/sandys_web/index.php?page=accept_invite&ref=" . base64_encode($idUsuarioLogueado);
?>

<script>
    const inviteLink = "<?php echo $linkInvitacion; ?>";

    function copiarLink() {
        navigator.clipboard.writeText(inviteLink).then(() => {
            Swal.fire({icon: 'success', title: '¡Copiado!', text: 'Link copiado al portapapeles', timer: 1500, showConfirmButton: false, background: '#1a1a1a', color: '#fff'});
        });
    }

    function compartirWhatsApp() {
        const mensaje = encodeURIComponent("¡Hola! Te he invitado a unirte a mi Plan Familiar en Sandy's Gym. Haz clic aquí para aceptar y crear tu cuenta: " + inviteLink);
        window.open("https://wa.me/?text=" + mensaje, "_blank");
    }

    // Funcionalidad para agregar/vincular usuario
    $(document).ready(function() {
        $('#btnGuardarMiembro').click(function() {
            const btn = $(this);
            const form = $('#formAddMember');
            const tel = $('input[name="telefono_beneficiario"]').val();
            
            if(!tel || tel.length < 10) {
                Swal.fire({icon:'warning', title:'Atención', text:'Debes ingresar un número de 10 dígitos.', background:'#1a1a1a', color:'#fff'});
                return;
            }

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

            $.ajax({
                url: 'api/add_group_member.php', 
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        $('#modalAddMember').modal('hide');
                        Swal.fire({icon: 'success', title: '¡Listo!', text: res.message, background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444'})
                        .then(() => location.reload());
                    } else {
                        Swal.fire({icon: 'error', title: 'Error', text: res.message, background: '#1a1a1a', color: '#fff'});
                    }
                },
                complete: function() { btn.prop('disabled', false).html('Vincular / Crear Usuario'); }
            });
        });
    });

    // Funcionalidad para eliminar miembro
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
                        if(res.success) {
                            location.reload();
                        } else {
                            Swal.fire({icon: 'error', title: 'Error', text: res.message, background: '#1a1a1a', color: '#fff'});
                        }
                    }
                });
            }
        });
    }
</script>
<?php endif; ?>
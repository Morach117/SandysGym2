<?php
// --- INCLUDES Y LÓGICA ---
include_once('conn.php');
include_once('./query/select_data.php');

if (!$selSocioData) {
    echo "<div style='color:white; text-align:center; padding:50px;'>Error al cargar datos del socio.</div>";
    exit;
}

// LÓGICA PARA EL MES DE NACIMIENTO
$fecha_bd = $selSocioData['soc_fecha_nacimiento'];
$mes_guardado = '';

// Si hay una fecha válida en la BD, extraemos solo el mes (MM)
if (!empty($fecha_bd) && $fecha_bd != '0000-00-00') {
    $porciones = explode('-', $fecha_bd);
    if (count($porciones) == 3) {
        $mes_guardado = $porciones[1]; 
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Muli:wght@300;400;700&family=Oswald:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
    /* --- Base --- */
    body {
        background-color: #0f0f0f;
        color: #e0e0e0;
        font-family: 'Muli', sans-serif;
    }

    /* --- Layout ajustado para respetar el Navbar fijo --- */
    .profile-section {
        padding: 120px 0 80px; /* 120px empuja el contenido hacia abajo */
    }

    /* --- Encabezado Limpio --- */
    .page-header-custom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        border-bottom: 1px solid #333;
        padding-bottom: 15px;
    }
    .page-title {
        font-family: 'Oswald', sans-serif;
        font-size: 28px;
        color: #fff;
        text-transform: uppercase;
        margin: 0;
    }
    .btn-back {
        color: #ef4444;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        transition: 0.3s;
    }
    .btn-back:hover { color: #ff6b6b; text-decoration: none; }

    /* --- Tarjetas de Perfil --- */
    .profile-card {
        background-color: #1a1a1a;
        border: 1px solid #333;
        border-radius: 16px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        overflow: hidden;
    }

    .card-header-custom {
        background-color: #222;
        padding: 20px 25px;
        border-bottom: 1px solid #333;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .header-icon { color: #ef4444; font-size: 20px; }
    .header-title {
        font-family: 'Oswald', sans-serif;
        font-size: 18px;
        color: #fff;
        text-transform: uppercase;
        margin: 0;
    }

    .card-body-custom { padding: 30px 25px; }

    /* --- Inputs y Labels --- */
    .form-group label {
        color: #ccc;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .form-control {
        background-color: #0f0f0f !important;
        border: 1px solid #444 !important;
        color: #fff !important;
        border-radius: 8px !important;
        height: 48px !important;
        padding: 10px 15px !important;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
    }

    /* Inputs Bloqueados */
    .form-control[readonly], select.form-control:disabled {
        background-color: #252525 !important;
        color: #888 !important;
        border-color: #333 !important;
        cursor: not-allowed;
        opacity: 1;
    }

    .form-text { color: #666; font-size: 12px; margin-top: 6px; display: block; }

    /* Botón Guardar */
    .save-btn {
        background-color: #ef4444;
        color: #fff;
        border: none;
        padding: 15px 50px;
        border-radius: 50px;
        font-family: 'Oswald', sans-serif;
        font-size: 16px;
        text-transform: uppercase;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 5px 20px rgba(239, 68, 68, 0.3);
    }
    .save-btn:hover {
        background-color: #dc2626;
        transform: translateY(-3px);
    }
</style>

<section class="profile-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9"> 
                
                <div class="page-header-custom">
                    <h2 class="page-title"><i class="fas fa-user-edit mr-2"></i> Mi Perfil</h2>
                    <a href="index.php?page=user_home" class="btn-back">
                        <i class="fas fa-arrow-left mr-1"></i> Volver al Panel
                    </a>
                </div>

                <form id="editarPerfilForm" method="POST">
                    <input type="hidden" name="id_socio" value="<?= htmlspecialchars($selSocioData['soc_id_socio']) ?>">

                    <div class="profile-card">
                        <div class="card-header-custom">
                            <i class="fas fa-user-circle header-icon"></i>
                            <h3 class="header-title">Datos Personales</h3>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-6 form-group mb-4">
                                    <label>Nombres *</label>
                                    <input type="text" name="nombres" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_nombres']) ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Apellido Paterno *</label>
                                    <input type="text" name="ap_paterno" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_apepat']) ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Apellido Materno</label>
                                    <input type="text" name="ap_materno" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_apemat']) ?>">
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Género</label>
                                    <select name="genero" class="form-control">
                                        <option value="Masculino" <?= ($selSocioData['soc_genero'] == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                                        <option value="Femenino" <?= ($selSocioData['soc_genero'] == 'Femenino') ? 'selected' : '' ?>>Femenino</option>
                                    </select>
                                </div>
                                <div class="col-md-12 form-group mb-0">
                                    <label>Mes de Nacimiento *</label>
                                    <select name="mes_nacimiento" class="form-control" <?= ($mes_guardado != '') ? 'disabled' : 'required' ?>>
                                        <option value="" <?= ($mes_guardado == '') ? 'selected' : '' ?> disabled>Selecciona tu Mes</option>
                                        <option value="01" <?= ($mes_guardado == '01') ? 'selected' : '' ?>>Enero</option>
                                        <option value="02" <?= ($mes_guardado == '02') ? 'selected' : '' ?>>Febrero</option>
                                        <option value="03" <?= ($mes_guardado == '03') ? 'selected' : '' ?>>Marzo</option>
                                        <option value="04" <?= ($mes_guardado == '04') ? 'selected' : '' ?>>Abril</option>
                                        <option value="05" <?= ($mes_guardado == '05') ? 'selected' : '' ?>>Mayo</option>
                                        <option value="06" <?= ($mes_guardado == '06') ? 'selected' : '' ?>>Junio</option>
                                        <option value="07" <?= ($mes_guardado == '07') ? 'selected' : '' ?>>Julio</option>
                                        <option value="08" <?= ($mes_guardado == '08') ? 'selected' : '' ?>>Agosto</option>
                                        <option value="09" <?= ($mes_guardado == '09') ? 'selected' : '' ?>>Septiembre</option>
                                        <option value="10" <?= ($mes_guardado == '10') ? 'selected' : '' ?>>Octubre</option>
                                        <option value="11" <?= ($mes_guardado == '11') ? 'selected' : '' ?>>Noviembre</option>
                                        <option value="12" <?= ($mes_guardado == '12') ? 'selected' : '' ?>>Diciembre</option>
                                    </select>
                                    
                                    <?php if($mes_guardado != ''): ?>
                                        <input type="hidden" name="mes_nacimiento" value="<?= htmlspecialchars($mes_guardado) ?>">
                                    <?php endif; ?>
                                    
                                    <small class="form-text"><i class="fas fa-info-circle"></i> Por seguridad, este campo no se puede modificar una vez establecido.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-card">
                        <div class="card-header-custom">
                            <i class="fas fa-address-card header-icon"></i>
                            <h3 class="header-title">Información de Contacto</h3>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-12 form-group mb-4">
                                    <label>Dirección Completa</label>
                                    <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_direccion']) ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Teléfono Celular</label>
                                    <input type="text" name="tel_cel" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_tel_cel']) ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Correo Electrónico</label>
                                    <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_correo']) ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-card">
                        <div class="card-header-custom">
                            <i class="fas fa-heartbeat header-icon"></i>
                            <h3 class="header-title">Contacto de Emergencia</h3>
                        </div>
                         <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-12 form-group mb-4">
                                    <label>Nombre del Contacto</label>
                                    <input type="text" name="emer_nombres" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_emer_nombres']) ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Teléfono</label>
                                    <input type="text" name="emer_tel" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_emer_tel']) ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Parentesco</label>
                                    <input type="text" name="emer_parentesco" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_emer_parentesco']) ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <button id="guardarCambiosBtn" type="submit" class="save-btn">
                            <i class="fas fa-save mr-2"></i> Guardar Cambios
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {
        $('#editarPerfilForm').on('submit', function(e) {
            e.preventDefault();
            
            let btn = $('#guardarCambiosBtn');
            let originalHTML = btn.html();
            let form = $(this);

            btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...');
            btn.prop('disabled', true).css('opacity', '0.7');

            $.ajax({
                url: 'api/update_profile_reward.php', 
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    btn.html(originalHTML).prop('disabled', false).css('opacity', '1');

                    if (response.success) {
                        if (response.rewardGiven) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Misión Cumplida!',
                                text: 'Has completado tu perfil al 100%. ¡Te hemos abonado $35 MXN a tu monedero!',
                                confirmButtonColor: '#ef4444'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Actualizado!',
                                text: 'Tu información se guardó correctamente.',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        }
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    btn.html(originalHTML).prop('disabled', false).css('opacity', '1');
                    Swal.fire('Error', 'Hubo un problema al conectar con el servidor.', 'error');
                }
            });
        });
    });
</script>
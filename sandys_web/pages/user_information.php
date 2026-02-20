<?php
// --- INCLUDES Y LÓGICA ---
include_once('conn.php');
include_once('./api/select_data.php');

if (!$selSocioData) {
    echo "<div style='color:white; text-align:center; padding:150px 20px 50px;'>Error al cargar datos del socio.</div>";
    exit;
}

// LÓGICA PARA EL MES DE NACIMIENTO
$fecha_bd = $selSocioData['soc_fecha_nacimiento'];
$mes_guardado = '';

if (!empty($fecha_bd) && $fecha_bd != '0000-00-00') {
    $porciones = explode('-', $fecha_bd);
    if (count($porciones) == 3) {
        $mes_guardado = $porciones[1]; 
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Muli:wght@300;400;700&family=Oswald:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* --- Base --- */
    body { background-color: #050505; color: #e0e0e0; font-family: 'Muli', sans-serif; }

    /* --- Layout para respetar el Navbar fijo --- */
    .profile-section { padding: 130px 0 80px; min-height: 100vh; }

    /* --- Encabezado Optimizado (Ahorro de espacio vertical) --- */
    .page-header-custom {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 25px; border-bottom: 1px solid #222; padding-bottom: 15px;
        gap: 10px;
    }
    .page-title { font-family: 'Oswald', sans-serif; font-size: 26px; color: #fff; text-transform: uppercase; margin: 0; flex-grow: 1; }
    
    /* Botón Volver tipo Icono en Móvil */
    .btn-back {
        background-color: #1a1a1a; border: 1px solid #ef4444; color: #ef4444;
        width: 42px; height: 42px; border-radius: 10px; font-size: 16px;
        display: inline-flex; align-items: center; justify-content: center;
        transition: 0.3s; text-decoration: none;
    }
    .btn-back span { display: none; } /* Oculto por defecto, se muestra en PC */
    .btn-back:hover { background-color: #ef4444; color: #fff; }

    /* --- Tarjetas de Perfil (Consistencia Visual) --- */
    .profile-card {
        background-color: #121212; border: 1px solid #2a2a2a; border-radius: 16px;
        margin-bottom: 25px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6); overflow: hidden;
    }

    .card-header-custom {
        background-color: #0a0a0a; padding: 15px 25px; border-bottom: 2px solid #ef4444;
        display: flex; align-items: center; gap: 12px;
    }
    .header-icon { color: #ef4444; font-size: 18px; }
    .header-title { font-family: 'Oswald', sans-serif; font-size: 18px; color: #fff; text-transform: uppercase; margin: 0; }
    .card-body-custom { padding: 25px; }

    /* --- Inputs con Alto Contraste para Móvil --- */
    .form-group label {
        color: #aaa; font-size: 12px; font-weight: 700; margin-bottom: 6px;
        text-transform: uppercase; letter-spacing: 0.5px; display: block;
    }

    .form-control, .custom-select {
        background-color: #080808 !important; 
        border: 1px solid #444 !important; /* Borde más visible */
        color: #ffffff !important;
        border-radius: 8px !important; height: 50px !important; padding: 10px 15px !important;
        font-size: 15px !important; transition: all 0.3s ease;
    }
    .form-control:focus {
        border-color: #ef4444 !important; background-color: #0f0f0f !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2) !important;
    }

    /* 🔥 Estado Deshabilitado Muy Claro 🔥 */
    .form-control[readonly], select.form-control:disabled {
        background-color: #222 !important; color: #666 !important;
        border-color: #333 !important; cursor: not-allowed; opacity: 0.7;
    }

    .form-text { color: #777; font-size: 11px; margin-top: 8px; line-height: 1.4; }

    /* Botón Guardar */
    .save-btn {
        background-color: #ef4444; color: #fff; border: none; padding: 16px;
        border-radius: 12px; font-family: 'Oswald', sans-serif; font-size: 18px;
        text-transform: uppercase; font-weight: 700; cursor: pointer; transition: 0.3s;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4); width: 100%;
    }
    .save-btn:hover { background-color: #dc2626; transform: translateY(-2px); }

    /* --- MEDIA QUERIES (PC) --- */
    @media (min-width: 992px) {
        .page-title { font-size: 32px; }
        .btn-back { width: auto; height: auto; padding: 10px 25px; border-radius: 50px; }
        .btn-back span { display: inline; margin-left: 8px; }
        .save-btn { max-width: 350px; margin: 0 auto; display: block; border-radius: 50px; }
    }
</style>

<section class="profile-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9"> 
                
                <div class="page-header-custom">
                    <a href="index.php?page=user_home" class="btn-back" title="Volver al Panel">
                        <i class="fas fa-arrow-left"></i><span>Volver</span>
                    </a>
                    <h2 class="page-title text-center">Mi Perfil</h2>
                    <div style="width: 42px;" class="d-lg-none"></div> </div>

                <form id="editarPerfilForm" method="POST">
                    <input type="hidden" name="id_socio" value="<?= htmlspecialchars($selSocioData['soc_id_socio']) ?>">

                    <div class="profile-card">
                        <div class="card-header-custom">
                            <i class="fas fa-id-card header-icon"></i>
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
                                    <input type="text" name="ap_materno" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_apemat']) ?>" placeholder="Opcional">
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Género *</label>
                                    <select name="genero" class="form-control" required>
                                        <option value="Masculino" <?= ($selSocioData['soc_genero'] == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                                        <option value="Femenino" <?= ($selSocioData['soc_genero'] == 'Femenino') ? 'selected' : '' ?>>Femenino</option>
                                    </select>
                                </div>
                                <div class="col-md-12 form-group mb-0">
                                    <label>Mes de Nacimiento *</label>
                                    <select name="mes_nacimiento" class="form-control" <?= ($mes_guardado != '') ? 'disabled' : 'required' ?>>
                                        <option value="" disabled>-- Seleccionar --</option>
                                        <?php 
                                        $meses = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
                                        foreach($meses as $val => $nom):
                                            $selected = ($mes_guardado == $val) ? 'selected' : '';
                                            echo "<option value='$val' $selected>$nom</option>";
                                        endforeach;
                                        ?>
                                    </select>
                                    <?php if($mes_guardado != ''): ?><input type="hidden" name="mes_nacimiento" value="<?= $mes_guardado ?>"><?php endif; ?>
                                    <small class="form-text"><i class="fas fa-info-circle mr-1"></i> Este dato es permanente por seguridad de promociones.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-card">
                        <div class="card-header-custom">
                            <i class="fas fa-phone-alt header-icon"></i>
                            <h3 class="header-title">Contacto</h3>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-12 form-group mb-4">
                                    <label>Dirección Completa *</label>
                                    <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_direccion']) ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>WhatsApp / Celular *</label>
                                    <input type="text" name="tel_cel" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_tel_cel']) ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Correo Electrónico</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_correo']) ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-card">
                        <div class="card-header-custom">
                            <i class="fas fa-ambulance header-icon"></i>
                            <h3 class="header-title">En caso de emergencia</h3>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-12 form-group mb-4">
                                    <label>A quién llamar *</label>
                                    <input type="text" name="emer_nombres" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_emer_nombres']) ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Teléfono emergencia *</label>
                                    <input type="text" name="emer_tel" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_emer_tel']) ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-0">
                                    <label>Parentesco *</label>
                                    <input type="text" name="emer_parentesco" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_emer_parentesco']) ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button id="guardarCambiosBtn" type="submit" class="save-btn">
                            Actualizar mi Información
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
            let originalText = btn.text();

            btn.text('Procesando...').prop('disabled', true);

            $.ajax({
                url: 'api/update_profile_reward.php', 
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    btn.text(originalText).prop('disabled', false);
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: res.rewardGiven ? '¡Perfil Completo!' : '¡Guardado!',
                            text: res.rewardGiven ? 'Te abonamos $35 MXN a tu monedero.' : 'Información actualizada.',
                            background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message, background: '#1a1a1a', color: '#fff' });
                    }
                },
                error: function() {
                    btn.text(originalText).prop('disabled', false);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error de red.', background: '#1a1a1a', color: '#fff' });
                }
            });
        });
    });
</script>
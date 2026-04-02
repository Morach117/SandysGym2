<?php
// --- INCLUDES Y LÓGICA DE LA VISTA (HTML) ---
// (Se eliminaron los headers de caché para evitar el error "headers already sent")
include_once('conn.php');
include_once('./api/select_data.php');

if (!$selSocioData) {
    echo "<div style='color:white; text-align:center; padding:150px 20px 50px;'>Error al cargar datos del socio.</div>";
    exit;
}

// LÓGICA INFALIBLE PARA EL MES DE NACIMIENTO
$fecha_bd = trim($selSocioData['soc_fecha_nacimiento'] ?? '');
$mes_guardado = '';

if (!empty($fecha_bd) && $fecha_bd != '0000-00-00' && $fecha_bd != '0000-00-00 00:00:00') {
    // Usamos explode para evitar errores de strtotime con años "0000"
    $porciones = explode('-', $fecha_bd);
    if (count($porciones) >= 3) {
        $mes_int = intval($porciones[1]); // Extraemos la posición del mes
        // Validamos que sea un mes real (1 al 12)
        if ($mes_int >= 1 && $mes_int <= 12) {
            $mes_guardado = str_pad($mes_int, 2, "0", STR_PAD_LEFT);
        }
    }
}

// LÓGICA PARA LA FOTO
$foto_bd = !empty($selSocioData['soc_imagen']) ? $selSocioData['soc_imagen'] : '';
$nombres_url = urlencode(trim($selSocioData['soc_nombres'] ?? 'Usuario'));
$avatar_default = "https://ui-avatars.com/api/?name={$nombres_url}&background=ef4444&color=fff&size=150";

$foto_perfil = $avatar_default;

if ($foto_bd !== '') {
    $foto_perfil = trim($foto_bd);
    
    // CORRECCIÓN: Se ajustó la ruta quitando el "../" para que la busque correctamente desde el index
    if (strpos($foto_perfil, '/') === false) {
        $foto_perfil = '../imagenes/avatar/' . $foto_perfil;
    }
    
    // Agregamos el timestamp para evitar que el navegador muestre una foto vieja guardada en caché
    $foto_perfil .= '?v=' . time();
}
?>

<link href="https://fonts.googleapis.com/css2?family=Muli:wght@300;400;700&family=Oswald:wght@400;700&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* --- Base --- */
body {
    background-color: #050505;
    color: #e0e0e0;
    font-family: 'Muli', sans-serif;
}

.profile-section {
    padding: 130px 0 80px;
    min-height: 100vh;
}

/* --- Encabezado --- */
.page-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    border-bottom: 1px solid #222;
    padding-bottom: 15px;
    gap: 10px;
}

.page-title {
    font-family: 'Oswald', sans-serif;
    font-size: 26px;
    color: #fff;
    text-transform: uppercase;
    margin: 0;
    flex-grow: 1;
}

.btn-back {
    background-color: #1a1a1a;
    border: 1px solid #ef4444;
    color: #ef4444;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: 0.3s;
    text-decoration: none;
}

.btn-back span {
    display: none;
}

.btn-back:hover {
    background-color: #ef4444;
    color: #fff;
}

/* --- Foto de Perfil --- */
.foto-container {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto;
}

.foto-perfil-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #ef4444;
    box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
}

.btn-camara {
    position: absolute;
    bottom: 0;
    right: 0;
    background: #ef4444;
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: 2px solid #121212;
    transition: 0.3s;
}

.btn-camara:hover {
    background: #dc2626;
    transform: scale(1.1);
}

/* --- Tarjetas --- */
.profile-card {
    background-color: #121212;
    border: 1px solid #2a2a2a;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
    overflow: hidden;
}

.card-header-custom {
    background-color: #0a0a0a;
    padding: 15px 25px;
    border-bottom: 2px solid #ef4444;
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-icon {
    color: #ef4444;
    font-size: 18px;
}

.header-title {
    font-family: 'Oswald', sans-serif;
    font-size: 18px;
    color: #fff;
    text-transform: uppercase;
    margin: 0;
}

.card-body-custom {
    padding: 25px;
}

/* --- Inputs --- */
.form-group label {
    color: #aaa;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
}

.form-control,
.custom-select {
    background-color: #080808 !important;
    border: 1px solid #444 !important;
    color: #ffffff !important;
    border-radius: 8px !important;
    height: 50px !important;
    padding: 10px 15px !important;
    font-size: 15px !important;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #ef4444 !important;
    background-color: #0f0f0f !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2) !important;
}

.form-control[readonly] {
    background-color: #222 !important;
    color: #888 !important;
    border-color: #333 !important;
    cursor: not-allowed;
}

.form-text {
    color: #777;
    font-size: 11px;
    margin-top: 8px;
    line-height: 1.4;
}

/* Botón Guardar */
.save-btn {
    background-color: #ef4444;
    color: #fff;
    border: none;
    padding: 16px;
    border-radius: 12px;
    font-family: 'Oswald', sans-serif;
    font-size: 18px;
    text-transform: uppercase;
    font-weight: 700;
    cursor: pointer;
    transition: 0.3s;
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
    width: 100%;
}

.save-btn:hover {
    background-color: #dc2626;
    transform: translateY(-2px);
}

@media (min-width: 992px) {
    .page-title {
        font-size: 32px;
    }

    .btn-back {
        width: auto;
        height: auto;
        padding: 10px 25px;
        border-radius: 50px;
    }

    .btn-back span {
        display: inline;
        margin-left: 8px;
    }

    .save-btn {
        max-width: 350px;
        margin: 0 auto;
        display: block;
        border-radius: 50px;
    }
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
                    <div style="width: 42px;" class="d-lg-none"></div>
                </div>

                <form id="editarPerfilForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_socio"
                        value="<?= htmlspecialchars($selSocioData['soc_id_socio'] ?? '') ?>">

                    <div class="text-center mb-4">
                        <div class="foto-container">
                            <img id="previewFoto" src="<?= $foto_perfil ?>" class="foto-perfil-img" alt="Foto de perfil"
                                onerror="this.src='<?= $avatar_default ?>'">
                            <label for="fotoInput" class="btn-camara">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="fotoInput" name="foto_perfil" accept="image/*" capture="environment"
                                class="d-none" onchange="previewImage(event)">
                        </div>
                        <p class="text-muted mt-2 small" style="color: #aaa;">Toca la cámara para subir una foto o tomar
                            una selfie</p>
                    </div>

                    <div class="profile-card">
                        <div class="card-header-custom">
                            <i class="fas fa-id-card header-icon"></i>
                            <h3 class="header-title">Datos Personales</h3>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-6 form-group mb-4">
                                    <label>Nombres *</label>
                                    <input type="text" name="nombres" class="form-control"
                                        value="<?= htmlspecialchars($selSocioData['soc_nombres'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Apellido Paterno *</label>
                                    <input type="text" name="ap_paterno" class="form-control"
                                        value="<?= htmlspecialchars($selSocioData['soc_apepat'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Apellido Materno</label>
                                    <input type="text" name="ap_materno" class="form-control"
                                        value="<?= htmlspecialchars($selSocioData['soc_apemat'] ?? '') ?>"
                                        placeholder="Opcional">
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Género *</label>
                                    <select name="genero" class="form-control" required>
                                        <option value="Masculino"
                                            <?= (($selSocioData['soc_genero'] ?? '') == 'Masculino') ? 'selected' : '' ?>>
                                            Masculino</option>
                                        <option value="Femenino"
                                            <?= (($selSocioData['soc_genero'] ?? '') == 'Femenino') ? 'selected' : '' ?>>
                                            Femenino</option>
                                    </select>
                                </div>
                                <div class="col-md-12 form-group mb-0">
                                    <label>Mes de Nacimiento *</label>

                                    <?php 
                                    $meses = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
                                    
                                    if ($mes_guardado !== ''): 
                                        // SI YA TIENE MES: Mostramos un input bloqueado
                                        $nombre_mes = $meses[$mes_guardado] ?? '';
                                    ?>
                                    <input type="text" class="form-control" value="<?= $nombre_mes ?>" readonly>
                                    <input type="hidden" name="mes_nacimiento" value="<?= $mes_guardado ?>">
                                    <?php else: ?>
                                    <select name="mes_nacimiento" class="form-control" required>
                                        <option value="" disabled selected>-- Seleccionar --</option>
                                        <?php foreach($meses as $val => $nom): ?>
                                        <option value="<?= $val ?>"><?= $nom ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php endif; ?>

                                    <small class="form-text"><i class="fas fa-info-circle mr-1"></i> Este dato es
                                        permanente por seguridad de promociones.</small>
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
                                <input type="hidden" name="direccion"
                                    value="<?= htmlspecialchars($selSocioData['soc_direccion'] ?? '') ?>">

                                <div class="col-md-6 form-group mb-4">
                                    <label>WhatsApp / Celular *</label>
                                    <input type="text" name="tel_cel" class="form-control"
                                        value="<?= htmlspecialchars($selSocioData['soc_tel_cel'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Correo Electrónico</label>
                                    <input type="email" class="form-control"
                                        value="<?= htmlspecialchars($selSocioData['soc_correo'] ?? '') ?>" readonly>
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
                                    <label>A quién llamar</label>
                                    <input type="text" name="emer_nombres" class="form-control"
                                        value="<?= htmlspecialchars($selSocioData['soc_emer_nombres'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label>Teléfono emergencia</label>
                                    <input type="text" name="emer_tel" class="form-control"
                                        value="<?= htmlspecialchars($selSocioData['soc_emer_tel'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 form-group mb-0">
                                    <label>Parentesco</label>
                                    <input type="text" name="emer_parentesco" class="form-control"
                                        value="<?= htmlspecialchars($selSocioData['soc_emer_parentesco'] ?? '') ?>">
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Mostrar vista previa de la imagen seleccionada
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const output = document.getElementById('previewFoto');
        output.src = reader.result;
    };
    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}

$(document).ready(function() {
    // Escuchamos el evento 'submit' y bloqueamos otros repetidos
    $('#editarPerfilForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        let btn = $('#guardarCambiosBtn');
        let originalText = btn.text();
        btn.text('Procesando...').prop('disabled', true);

        // --- MAGIA ANTI-BASE64 ---
        let formData = new FormData();

        // 1. Añadimos solo los datos de texto
        let formArray = $(this).serializeArray();
        $.each(formArray, function(i, field) {
            formData.append(field.name, field.value);
        });

        // 2. Extraemos el archivo físicamente y lo añadimos al paquete
        let fotoInput = document.getElementById('fotoInput');
        if (fotoInput && fotoInput.files.length > 0) {
            formData.append('foto_perfil', fotoInput.files[0]);
        }

        // Realizamos la solicitud AJAX al PHP de recompensa
        $.ajax({
            url: 'api/update_profile_reward.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(res) {
                btn.text(originalText).prop('disabled', false);

                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: res.rewardGiven ? '¡Perfil Completo!' : '¡Guardado!',
                        text: res.message,
                        background: '#1a1a1a',
                        color: '#fff',
                        confirmButtonColor: '#ef4444'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message || 'Error desconocido al guardar.',
                        background: '#1a1a1a',
                        color: '#fff'
                    });
                }
            },
            error: function(xhr, status, error) {
                btn.text(originalText).prop('disabled', false);

                let errorMsg = 'Error de red o de servidor.';
                try {
                    let response = JSON.parse(xhr.responseText);
                    if (response.message) errorMsg = response.message;
                } catch (e) {}

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg,
                    background: '#1a1a1a',
                    color: '#fff'
                });
            }
        });
    });
});
</script>
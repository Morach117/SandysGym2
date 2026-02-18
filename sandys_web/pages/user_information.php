<?php
// --- INCLUDES Y LÓGICA ---
include_once('conn.php');
include_once('./query/select_data.php');

// Asegurarnos que existen datos para evitar errores
if (!$selSocioData) {
    echo "<div style='color:white; text-align:center; padding:50px;'>Error al cargar datos del socio.</div>";
    exit;
}
?>

<style>
    /* --- Base --- */
    body {
        background-color: #0f0f0f;
        color: #e0e0e0;
        font-family: 'Muli', sans-serif;
    }

    /* --- Hero Section --- */
    .profile-hero {
        position: relative;
        padding: 60px 0 40px;
        background: linear-gradient(180deg, rgba(17,17,17,0.8), #0f0f0f), url('./assets/img/hero/hero-user.jpg');
        background-size: cover;
        background-position: center;
        text-align: center;
        border-bottom: 1px solid #222;
    }
    .profile-title {
        font-family: 'Oswald', sans-serif;
        font-size: 38px;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5px;
    }
    .profile-subtitle {
        color: #9ca3af;
        font-size: 15px;
    }
    .profile-subtitle a { color: #ef4444; text-decoration: none; transition: 0.3s; }
    .profile-subtitle a:hover { color: #ff6b6b; }

    /* --- Tarjetas de Perfil --- */
    .profile-section {
        padding: 40px 0 80px;
    }

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
    
    .header-icon {
        color: #ef4444;
        font-size: 20px;
    }
    
    .header-title {
        font-family: 'Oswald', sans-serif;
        font-size: 18px;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
    }

    .card-body-custom {
        padding: 30px 25px;
    }

    /* --- Inputs y Labels --- */
    .form-group label {
        color: #ccc;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
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

    /* Inputs Readonly / Disabled */
    .form-control[readonly], .form-control.disabled {
        background-color: #252525 !important;
        color: #888 !important;
        border-color: #333 !important;
        cursor: not-allowed;
    }

    /* Small text helper */
    .form-text {
        color: #666;
        font-size: 12px;
        margin-top: 6px;
    }

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
        letter-spacing: 1px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 5px 20px rgba(239, 68, 68, 0.3);
    }
    .save-btn:hover {
        background-color: #dc2626;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.5);
        color: #fff;
    }

    /* Select Options (Fix para navegadores) */
    select.form-control option {
        background-color: #1a1a1a;
        color: #fff;
        padding: 10px;
    }
</style>

<section class="profile-hero">
    <div class="container">
        <h2 class="profile-title">Mi Perfil</h2>
        <p class="profile-subtitle">
            <a href="index.php?page=user_home"><i class="fas fa-arrow-left mr-1"></i> Volver al Panel</a>
            <span class="mx-2">|</span>
            <span>Actualiza tu información personal</span>
        </p>
    </div>
</section>

<section class="profile-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9"> <form id="editarPerfilForm" method="POST">
                    <input type="hidden" name="id_socio" value="<?= htmlspecialchars($selSocioData['soc_id_socio']) ?>">

                    <div class="profile-card">
                        <div class="card-header-custom">
                            <i class="fas fa-user-circle header-icon"></i>
                            <h3 class="header-title">Datos Personales</h3>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-6 form-group mb-4">
                                    <label for="nombres">Nombres *</label>
                                    <input type="text" name="nombres" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_nombres']) ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label for="ap_paterno">Apellido Paterno *</label>
                                    <input type="text" name="ap_paterno" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_apepat']) ?>" required>
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label for="ap_materno">Apellido Materno</label>
                                    <input type="text" name="ap_materno" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_apemat']) ?>">
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label for="genero">Género</label>
                                    <select name="genero" class="form-control custom-select">
                                        <option value="Masculino" <?= ($selSocioData['soc_genero'] == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                                        <option value="Femenino" <?= ($selSocioData['soc_genero'] == 'Femenino') ? 'selected' : '' ?>>Femenino</option>
                                    </select>
                                </div>
                                <div class="col-md-12 form-group mb-0">
                                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_fecha_nacimiento']) ?>" 
                                        <?= ($selSocioData['soc_fecha_nacimiento'] != '0000-00-00') ? 'readonly' : '' ?>>
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
                                    <label for="direccion">Dirección Completa</label>
                                    <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_direccion']) ?>">
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label for="tel_cel">Teléfono Celular</label>
                                    <input type="text" name="tel_cel" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_tel_cel']) ?>">
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label for="correo">Correo Electrónico</label>
                                    <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_correo']) ?>" readonly>
                                    <small class="form-text">El correo electrónico es tu identificador único.</small>
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
                                    <label for="emer_nombres">Nombre del Contacto</label>
                                    <input type="text" name="emer_nombres" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_emer_nombres']) ?>">
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label for="emer_tel">Teléfono</label>
                                    <input type="text" name="emer_tel" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_emer_tel']) ?>">
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label for="emer_parentesco">Parentesco (Ej. Madre, Hermano)</label>
                                    <input type="text" name="emer_parentesco" class="form-control" value="<?= htmlspecialchars($selSocioData['soc_emer_parentesco']) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <button id="guardarCambiosBtn" type="button" class="save-btn">
                            <i class="fas fa-save mr-2"></i> Guardar Cambios
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</section>

<script>
    document.getElementById('guardarCambiosBtn').addEventListener('click', function() {
        // Aquí iría tu lógica de AJAX (similar a lo que hemos hecho en otros formularios)
        // Por ahora, solo un efecto visual de "Cargando"
        let btn = this;
        let originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...';
        btn.disabled = true;
        btn.style.opacity = '0.7';

        // Simular envío (Remplazar con submit real)
        setTimeout(function(){
            // document.getElementById('editarPerfilForm').submit(); // Descomentar para enviar
            alert("Funcionalidad de guardado lista para conectar con backend.");
            btn.innerHTML = originalText;
            btn.disabled = false;
            btn.style.opacity = '1';
        }, 1500);
    });
</script>
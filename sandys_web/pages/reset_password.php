<?php
// Asegurarse de que se proporciona un token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    // Redirigir usando JavaScript es más amigable que un header de PHP aquí
    echo "<script>window.location.href = 'index.php?page=login';</script>";
    exit();
}
$token = htmlspecialchars($_GET['token']);
?>

<style>
    /* Forza un fondo blanco en el body para esta página específica */
    body {
        background-color: #f8f9fa !important;
    }

    /* Corrige el navbar para que sea visible */
    .header-section {
        background-color: white !important;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1) !important;
    }

    .header-section .nav-menu ul li a,
    .header-section .top-option i {
        color: #333 !important;
    }

    /* Contenedor principal y diseño de la tarjeta */
    .password_reset_area {
        padding-top: 140px;
        padding-bottom: 70px;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
    }

    /* ... (El resto de los estilos del diseño moderno van aquí, tal como los tenías) ... */
    .reset-container {
        display: flex;
        width: 100%;
        max-width: 900px;
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .reset_box_img {
        width: 50%;
        background: url('./assets/img/login.jpg') center/cover;
        position: relative;
    }

    .img-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.7));
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 40px;
    }

    .img-overlay h4 {
        font-size: 24px;
        margin-bottom: 15px;
        font-weight: bold;
    }

    .reset_form_inner {
        width: 50%;
        padding: 50px;
    }

    .reset_form_inner h3 {
        margin-bottom: 20px;
        font-size: 28px;
        font-weight: bold;
    }

    .input-group {
        position: relative;
        margin-bottom: 20px;
    }

    .form-control {
        width: 100%;
        box-sizing: border-box;
        height: 50px;
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 0 45px 0 20px;
    }

    .form-control:focus {
        outline: none;
        border-color: #f28123;
        box-shadow: 0 0 0 3px rgba(242, 129, 35, 0.25);
    }

    .toggle-password {
        position: absolute;
        right: 15px;
        top: 25px;
        transform: translateY(-50%);
        cursor: pointer;
        color: #aaa;
    }

    .primary-btn {
        background-color: #f28123;
        color: white;
        border: none;
        padding: 15px 20px;
        border-radius: 8px;
        cursor: pointer;
        width: 100%;
        font-weight: bold;
        text-transform: uppercase;
    }

    #passwordHelpBlock ul {
        list-style: none;
        padding-left: 0;
        margin-top: 10px;
    }

    #passwordHelpBlock li {
        margin-bottom: 5px;
        color: #6c757d;
        transition: color 0.3s ease;
    }

    #passwordHelpBlock li i {
        margin-right: 8px;
    }

    #passwordHelpBlock li.valid {
        color: #28a745;
        font-weight: bold;
    }

    @media (max-width: 991px) {
        .reset_box_img {
            display: none;
        }

        .reset-container {
            max-width: 450px;
        }

        .reset_form_inner {
            width: 100%;
            padding: 40px 25px;
        }
    }
</style>

<section class="password_reset_area">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="reset-container">
                    <div class="reset_box_img d-none d-lg-block">
                        <div class="img-overlay">
                            <h4>La Seguridad es Nuestra Prioridad</h4>
                            <p>Elige una contraseña fuerte para mantener tu cuenta protegida.</p>
                        </div>
                    </div>
                    <div class="reset_form_inner">
                        <h3>Crea tu Nueva Contraseña</h3>
                        <form method="post" id="passwordResetFrm">
                            <input type="hidden" name="token" id="token" value="<?php echo $token; ?>">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Nueva Contraseña" required>
                                    <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                                </div>
                                <small id="passwordHelpBlock" class="form-text text-muted">
                                    <ul>
                                        <li id="length"><i class="fa-solid fa-check"></i> Al menos 8 caracteres</li>
                                        <li id="uppercase"><i class="fa-solid fa-check"></i> Una letra mayúscula</li>
                                        <li id="lowercase"><i class="fa-solid fa-check"></i> Una letra minúscula</li>
                                        <li id="number"><i class="fa-solid fa-check"></i> Un número</li>
                                        <li id="special"><i class="fa-solid fa-check"></i> Un carácter especial</li>
                                    </ul>
                                </small>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="primary-btn">Restablecer Contraseña</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

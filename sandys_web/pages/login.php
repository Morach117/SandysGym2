<style>
    /* --- Estilos Generales --- */
    body {
        background-color: #f8f9fa;
    }

    /* --- Correcciones para el Navbar Transparente --- */
    .login_box_area {
        padding-top: 140px;
        /* Espacio para el header flotante. AJUSTA SI ES NECESARIO. */
        padding-bottom: 70px;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
    }

    /* Corrección de colores del Navbar en esta página */
    body:has(.login_box_area) .header-section .nav-menu ul li a,
    body:has(.login_box_area) .header-section .top-option .to-search i,
    body:has(.login_box_area) .header-section .top-option .to-social a i,
    body:has(.login_box_area) .header-section .top-option a[href*="login"] i,
    body:has(.login_box_area) .header-section .top-option .dropdown a i {
        color: #333;
        /* Color oscuro para los iconos del navbar */
    }

    body:has(.login_box_area) .header-section .canvas-open i {
        color: #333;
    }

    /* --- Diseño de la Tarjeta de Login --- */
    .login-container {
        display: flex;
        width: 100%;
        max-width: 900px;
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .login_box_img {
        position: relative;
        width: 50%;
        color: white;
    }

    .login_box_img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .img-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.7));
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 40px;
        box-sizing: border-box;
    }

    .img-overlay h4 {
        font-size: 24px;
        margin-bottom: 15px;
        font-weight: bold;
    }

    .img-overlay p {
        font-size: 16px;
        margin-bottom: 25px;
        line-height: 1.6;
    }

    .login_form_inner {
        width: 50%;
        padding: 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-sizing: border-box;
    }

    .login_form_inner h3 {
        margin-bottom: 10px;
        font-size: 28px;
        font-weight: bold;
    }

    .login_form_inner .welcome-text {
        margin-bottom: 30px;
        color: #666;
    }

    .input-group {
        position: relative;
        margin-bottom: 20px;
    }

    .input-group .icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
    }

    .form-control {
        width: 100%;
        box-sizing: border-box;
        height: 50px;
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 0 20px 0 45px;
    }

    .form-control:focus {
        outline: none;
        border-color: #f28123;
        box-shadow: 0 0 0 3px rgba(242, 129, 35, 0.25);
    }

    .input-group .form-control:focus~.icon {
        color: #f28123;
    }

    .toggle-password {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #aaa;
        z-index: 2;
    }

    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        font-size: 14px;
    }

    .form-options a {
        color: #f28123;
        text-decoration: none;
    }

    .primary-btn {
        background-color: #f28123;
        color: white;
        border: none;
        padding: 15px 20px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        font-weight: bold;
        text-align: center;
        text-transform: uppercase;
    }

    .primary-btn:hover {
        background-color: #e56b1f;
        transform: translateY(-2px);
    }

    @media (max-width: 991px) {
        .login_box_img {
            display: none;
        }

        .login-container {
            flex-direction: column;
            max-width: 450px;
        }

        .login_form_inner {
            width: 100%;
            padding: 40px 25px;
        }
    }
    
</style>

<!-- Inicia el contenido de la página -->
<section class="login_box_area">
    <div class="login-container">
        <div class="login_box_img d-none d-lg-block">
            <img src="./assets/img/login.jpg" alt="Mujer entrenando">
            <div class="img-overlay">
                <h4>¿Nuevo en nuestro gimnasio?</h4>
                <p>Únete y empieza a transformar tu vida hoy mismo.</p>
                <a class="primary-btn" href="index.php?page=inscribite">Crear una Cuenta</a>
            </div>
        </div>
        <div class="login_form_inner">
            <h3>Bienvenido de Nuevo</h3>
            <p class="welcome-text">Inicia sesión para continuar.</p>
            <form class="login_form" method="post" id="adminLoginFrm">
                <div class="input-group">
                    <i class="fa-solid fa-user icon"></i>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Correo Electrónico" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock icon"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                    <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                </div>
                <div class="form-options">
                    <div class="creat_account">
                        <input type="checkbox" id="f-option2" name="selector">
                        <label for="f-option2">Mantenerme conectado</label>
                    </div>
                    <a href="#" data-toggle="modal" data-target="#forgotPasswordModal">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="primary-btn">Iniciar Sesión</button>
            </form>
        </div>
    </div>
</section>

<!-- Modal para restablecer contraseña -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restablecer Contraseña</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="passwordResetRequestFrm">
                    <div class="form-group">
                        <label for="reset_email">Correo Electrónico</label>
                        <input type="email" class="form-control" id="reset_email" name="reset_email" placeholder="tu@correo.com" required>
                    </div>
                    <button type="submit" class="primary-btn mt-3">Enviar enlace</button>
                </form>
            </div>
        </div>
    </div>
</div>
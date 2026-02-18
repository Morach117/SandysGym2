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
        text-align: center; /* Centrado para este mensaje */
    }
    
    .form-group {
        margin-bottom: 20px;
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
        justify-content: center; /* Centrado para el link de reenviar */
        align-items: center;
        margin-top: 25px;
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

<section class="login_box_area">
    <div class="login-container">
        
        <div class="login_box_img d-none d-lg-block">
            <img src="./assets/img/login.jpg" alt="Mujer entrenando">
            <div class="img-overlay">
                <h4>¡Ya casi estás!</h4>
                <p>Estás a un solo paso de activar tu cuenta y unirte a la familia Sandys Gym.</p>
                </div>
        </div>
        
        <div class="login_form_inner">
            <h3>Verifica tu Cuenta</h3>
            <p class="welcome-text">
                Hemos enviado un código de 4 dígitos a:
                <br>
                <strong id="userEmail">tu@correo.com</strong>
            </p>
            
            <form class="login_form" id="validationForm" method="post" novalidate="novalidate">
                
                <div class="form-group">
                    <input type="text" class="form-control text-center" id="validation_code" name="validation_code" placeholder="----" maxlength="4" required style="font-size: 2rem; letter-spacing: 15px; padding-left: 15px; padding-right: 15px;">
                </div>
                
                <button type="submit" class="primary-btn">Validar Cuenta</button>

                <div class="form-options">
                    <a href="#" id="resendCodeLink">¿No recibiste el código? Reenviar</a>
                </div>
            </form>
        </div>
    </div>
</section>
<link href="https://fonts.googleapis.com/css2?family=Muli:wght@300;400;700&family=Oswald:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* --- 1. GENERAL --- */
    body { background-color: #050505; color: #e0e0e0; font-family: 'Muli', sans-serif; }

    /* --- 2. LAYOUT --- */
    .login_box_area {
        padding-top: 140px; padding-bottom: 80px; min-height: 100vh;
        display: flex; align-items: center; justify-content: center; box-sizing: border-box;
    }
    .login-container {
        display: flex; width: 100%; max-width: 900px;
        background-color: #121212; border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
        border: 1px solid #2a2a2a; overflow: hidden;
    }

    /* --- 3. COLUMNA IZQUIERDA --- */
    .login_box_img {
        position: relative; width: 45%;
        background: linear-gradient(135deg, rgba(34, 10, 10, 0.85) 0%, rgba(0, 0, 0, 0.95) 100%), url('./assets/img/login.jpg');
        background-size: cover; background-position: center;
        display: flex; align-items: center; justify-content: center;
    }
    .img-overlay { position: relative; z-index: 2; padding: 40px; text-align: center; width: 100%; }
    .img-overlay h4 { font-family: 'Oswald', sans-serif; font-size: 32px; color: #fff; margin-bottom: 15px; text-transform: uppercase; }
    .img-overlay p { color: #aaa; margin-bottom: 25px; font-size: 15px; }

    /* --- 4. FORMULARIO DERECHA --- */
    .login_form_inner { width: 55%; padding: 50px 40px; background-color: #121212; display: flex; flex-direction: column; justify-content: center; }
    .login_form_inner h3 { font-family: 'Oswald', sans-serif; font-size: 28px; color: #fff; text-transform: uppercase; margin-bottom: 5px; }
    .welcome-text { margin-bottom: 30px; color: #888; font-size: 14px; }

    /* --- 5. INPUTS CORREGIDOS --- */
    .form-group-login { position: relative; margin-bottom: 25px; width: 100%; }
    
    .form-group-login .form-control {
        background-color: #1a1a1a !important; 
        border: 1px solid #333 !important; 
        color: #fff !important;
        height: 55px !important; 
        border-radius: 10px !important;
        padding-left: 50px !important; 
        padding-right: 45px !important; 
        font-size: 15px; 
        transition: all 0.3s ease;
        width: 100%;
        box-shadow: none !important;
    }
    .form-group-login .form-control:focus { 
        border-color: #ef4444 !important; 
        background-color: #121212 !important;
    }

    /* 🔥 SOLUCIÓN ANTIBALAS: FONDO BLANCO DE AUTOCOMPLETADO (CHROME/EDGE) 🔥 */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active,
    input:autofill {
        -webkit-box-shadow: 0 0 0px 1000px #0a0a0a inset !important;
        -webkit-text-fill-color: #ffffff !important;
        caret-color: #ffffff !important;
        border: 1px solid #333 !important;
        transition: background-color 5000s ease-in-out 0s !important;
    }
    input::-ms-reveal, input::-ms-clear { display: none; }

    /* --- 6. ICONOS --- */
    .input-icon { 
        position: absolute; left: 18px; top: 50%; transform: translateY(-50%);
        color: #666; font-size: 18px; pointer-events: none; transition: 0.3s; z-index: 5;
    }
    .form-group-login .form-control:focus ~ .input-icon { color: #ef4444; }
    
    .toggle-password { 
        position: absolute; right: 18px; top: 50%; transform: translateY(-50%);
        cursor: pointer; color: #666; z-index: 10; transition: color 0.3s; font-size: 18px;
    }
    .toggle-password:hover { color: #fff; }

    /* --- 7. BOTONES Y OPCIONES --- */
    .form-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; font-size: 14px; color: #aaa; }
    .form-options a { color: #ef4444; text-decoration: none; font-weight: 600; transition: 0.3s; }
    .form-options a:hover { color: #ff6b6b; }
    
    .creat_account { display: flex; align-items: center; }
    .creat_account input[type="checkbox"] { accent-color: #ef4444; margin-right: 8px; cursor: pointer; width: 16px; height: 16px; }
    .creat_account label { color: #ccc; cursor: pointer; margin-bottom: 0; user-select: none; }

    .primary-btn { 
        background-color: #ef4444; color: white; border: none; padding: 16px; 
        border-radius: 10px; font-family: 'Oswald', sans-serif; font-size: 16px; 
        text-transform: uppercase; font-weight: 700; cursor: pointer; width: 100%; transition: 0.3s; 
    }
    .primary-btn:hover { background-color: #d12f2f; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3); }
    
    .btn-outline-light-custom {
        border: 2px solid #ef4444; color: #fff; background: rgba(0,0,0,0.3);
        padding: 12px 30px; border-radius: 50px; font-weight: bold; text-decoration: none; transition: 0.3s; display: inline-block;
    }
    .btn-outline-light-custom:hover { background: #ef4444; box-shadow: 0 0 20px rgba(239, 68, 68, 0.6); color: #fff; text-decoration: none; }

    /* --- 8. MODAL RECOVERY DARK MODE --- */
    .modal-content.dark-modal { background-color: #121212; color: #fff; border: 1px solid #333; border-radius: 12px; }
    .dark-modal .modal-header { border-bottom: 1px solid #222; }
    .dark-modal .modal-title { font-family: 'Oswald', sans-serif; font-size: 20px; text-transform: uppercase; }
    .dark-modal .close { color: #fff; text-shadow: none; opacity: 0.8; }
    .dark-modal .close:hover { opacity: 1; }
    .dark-modal label { color: #aaa; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 8px; }

    /* 🔥 OVERRIDE GLOBAL PARA FORZAR SWEETALERT EN MODO OSCURO 🔥 */
    div.swal2-popup {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
        border: 1px solid #333 !important;
        border-radius: 12px !important;
    }
    div.swal2-title, div.swal2-html-container { color: #ffffff !important; }
    .swal2-icon.swal2-error { border-color: #ef4444 !important; color: #ef4444 !important; }
    .swal2-icon.swal2-error [class^=swal2-x-mark-line] { background-color: #ef4444 !important; }
    .swal2-icon.swal2-warning { border-color: #facc15 !important; color: #facc15 !important; }
    .swal2-icon.swal2-success { border-color: #10b981 !important; color: #10b981 !important; }
    .swal2-icon.swal2-success [class^=swal2-success-line] { background-color: #10b981 !important; }
    .swal2-icon.swal2-success .swal2-success-ring { border-color: rgba(16, 185, 129, 0.3) !important; }
    .swal2-confirm { background-color: #ef4444 !important; color: white !important; font-weight: bold !important; border-radius: 8px !important; }

    @media (max-width: 991px) {
        .login_box_img { display: none; }
        .login-container { max-width: 450px; flex-direction: column; }
        .login_form_inner { width: 100%; padding: 40px 25px; }
    }
</style>

<section class="login_box_area">
    <div class="login-container">
        
        <div class="login_box_img d-none d-lg-flex">
            <div class="img-overlay">
                <h4>¿Nuevo en el Gimnasio?</h4>
                <p>Únete y empieza a transformar tu vida hoy mismo. Regístrate de forma rápida y sencilla.</p>
                <a class="btn-outline-light-custom" href="index.php?page=inscribite">Crear una Cuenta</a>
            </div>
        </div>

        <div class="login_form_inner">
            <h3>Bienvenido de Nuevo</h3>
            <p class="welcome-text">Inicia sesión para continuar.</p>
            
            <form class="login_form" method="post" id="adminLoginFrm">
                <div class="form-group-login">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Correo Electrónico" required>
                    <i class="fa-solid fa-envelope input-icon"></i>
                </div>
                
                <div class="form-group-login">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                    <i class="fa-solid fa-lock input-icon"></i>
                    <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                </div>
                
                <div class="form-options">
                    <div class="creat_account">
                        <input type="checkbox" id="f-option2" name="selector">
                        <label for="f-option2">Mantenerme conectado</label>
                    </div>
                    <a href="#" data-toggle="modal" data-target="#forgotPasswordModal">¿Olvidaste tu contraseña?</a>
                </div>
                
                <button type="submit" class="primary-btn" id="btnLoginSubmit">
                    INICIAR SESIÓN
                </button>
            </form>
        </div>
    </div>
</section>

<div class="modal fade" id="forgotPasswordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content dark-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key text-danger mr-2"></i> Recuperar Acceso</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-4">Ingresa tu correo electrónico registrado y te enviaremos un enlace para restablecer tu contraseña.</p>
                <form id="passwordResetRequestFrm">
                    <div class="form-group-login mb-4" style="margin-bottom: 0;">
                        <label for="reset_email">Correo Electrónico</label>
                        <input type="email" class="form-control" id="reset_email" name="reset_email" placeholder="tu@correo.com" required style="padding-left: 20px !important;">
                    </div>
                    <button type="submit" class="primary-btn mt-4" id="btnResetSubmit">Enviar Enlace</button>
                </form>
            </div>
        </div>
    </div>
</div>
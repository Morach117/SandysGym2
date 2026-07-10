<?php
// Asegurarse de que se proporciona un token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    echo "<script>window.location.href = 'index.php?page=login';</script>";
    exit();
}
$token = htmlspecialchars($_GET['token']);
?>
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
        position: absolute; left: 18px; top: 27.5px; transform: translateY(-50%);
        color: #666; font-size: 18px; pointer-events: none; transition: 0.3s; z-index: 5;
    }
    .form-group-login .form-control:focus ~ .input-icon { color: #ef4444; }
    
    .toggle-password { 
        position: absolute; right: 18px; top: 27.5px; transform: translateY(-50%);
        cursor: pointer; color: #666; z-index: 10; transition: color 0.3s; font-size: 18px;
    }
    .toggle-password:hover { color: #fff; }

    /* --- 7. BOTONES Y OPCIONES --- */
    .primary-btn { 
        background-color: #ef4444; color: white; border: none; padding: 16px; 
        border-radius: 10px; font-family: 'Oswald', sans-serif; font-size: 16px; 
        text-transform: uppercase; font-weight: 700; cursor: pointer; width: 100%; transition: 0.3s; 
    }
    .primary-btn:hover { background-color: #d12f2f; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3); }

    /* --- REQUISITOS --- */
    #passwordHelpBlock ul {
        list-style: none;
        padding-left: 0;
        margin-top: 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 12px;
    }

    #passwordHelpBlock li {
        color: #6c757d;
        transition: color 0.3s ease;
        display: flex;
        align-items: center;
    }

    #passwordHelpBlock li i {
        margin-right: 5px;
        font-size: 10px;
    }

    #passwordHelpBlock li.valid {
        color: #4ade80;
    }

    /* 🔥 OVERRIDE GLOBAL PARA FORZAR SWEETALERT EN MODO OSCURO 🔥 */
    div.swal2-popup {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
        border: 1px solid #333 !important;
        border-radius: 12px !important;
    }
    div.swal2-title, div.swal2-html-container { color: #ffffff !important; }

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
                <h4>La Seguridad es Nuestra Prioridad</h4>
                <p>Elige una contraseña fuerte para mantener tu cuenta protegida.</p>
            </div>
        </div>

        <div class="login_form_inner">
            <h3>Crea tu Nueva Contraseña</h3>
            <p class="welcome-text">Ingresa tu nueva contraseña a continuación.</p>
            
            <form method="post" id="passwordResetFrm">
                <input type="hidden" name="token" id="token" value="<?php echo $token; ?>">
                
                <div class="form-group-login">
                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Nueva Contraseña" required>
                    <i class="fa-solid fa-lock input-icon"></i>
                    <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                </div>
                
                <small id="passwordHelpBlock" class="form-text text-muted mb-4">
                    <ul>
                        <li id="length"><i class="fa-solid fa-circle"></i> Al menos 8 caracteres</li>
                        <li id="uppercase"><i class="fa-solid fa-circle"></i> Una mayúscula</li>
                        <li id="lowercase"><i class="fa-solid fa-circle"></i> Una minúscula</li>
                        <li id="number"><i class="fa-solid fa-circle"></i> Un número</li>
                        <li id="special"><i class="fa-solid fa-circle"></i> Un carácter especial</li>
                    </ul>
                </small>
                
                <button type="submit" class="primary-btn">Restablecer Contraseña</button>
            </form>
        </div>
    </div>
</section>

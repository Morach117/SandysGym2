<style>
    /* --- INICIO: Estilos de Requisitos de Contraseña (de tu Pág. de Registro) --- */
    .password-requirements {
        padding-left: 20px;
        margin-top: 10px;
        list-style: none;
        color: #333;
        /* Color de texto normal */
    }

    .password-requirements li {
        margin-bottom: 5px;
        color: #dc3545;
        /* Rojo (Inválido) por defecto */
    }

    .password-requirements li i {
        margin-right: 8px;
        font-size: 0.8em;
    }

    .password-requirements li.valid {
        color: #28a745;
        /* Verde (Válido) */
    }

    .password-requirements li.valid i::before {
        content: "\f00c";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
    }
    /* --- FIN: Estilos de Requisitos de Contraseña --- */


    /* --- INICIO: Estilos de Tarjeta de Login (de tu Pág. de Login) --- */
    body {
        background-color: #f8f9fa;
    }

    .login_box_area {
        padding-top: 140px;
        padding-bottom: 70px;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
    }

    body:has(.login_box_area) .header-section .nav-menu ul li a,
    body:has(.login_box_area) .header-section .top-option .to-search i,
    body:has(.login_box_area) .header-section .top-option .to-social a i,
    body:has(.login_box_area) .header-section .top-option a[href*="login"] i,
    body:has(.login_box_area) .header-section .top-option .dropdown a i {
        color: #333;
    }

    body:has(.login_box_area) .header-section .canvas-open i {
        color: #333;
    }

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
    
    /* Regla del form-group para dar espacio extra a los campos de contraseña */
    .form-group {
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
        /* Padding: Izq para icono, Der para "ojo" */
        padding: 0 45px 0 45px; 
    }
    
    /* El email no tiene "ojo", así que reducimos el padding derecho */
    #email.form-control {
        padding-right: 20px;
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
        z-index: 5; /* Asegura que esté sobre el input */
    }
    
    .toggle-password:hover {
        color: #333;
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
    
    /* Ajuste para el botón de verificar correo */
    .btn-normal {
        text-transform: none;
        font-weight: normal;
        padding: 12px 20px;
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
    /* --- FIN: Estilos de Tarjeta de Login --- */
</style>

<section class="login_box_area">
    <div class="login-container">
        
        <div class="login_box_img d-none d-lg-block">
            <img src="./assets/img/login.jpg" alt="Persona entrenando">
            <div class="img-overlay">
                <h4>¡Ya eres miembro!</h4>
                <p>Inicia sesión para acceder a tu perfil, ver tus rutinas y administrar tus pagos.</p>
                <a class="primary-btn" href="index.php?page=login">Iniciar Sesión</a>
            </div>
        </div>
        
        <div class="login_form_inner">
            <h3>Crea tu Cuenta</h3>
            <p class="welcome-text">Regístrate para unirte a nuestro gimnasio.</p>
            
            <form class="registration-form" id="registrationForm" novalidate="novalidate">
                
                <div class="input-group" id="emailSection">
                    <i class="fa-solid fa-envelope icon"></i>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Correo Electrónico" required>
                </div>
                
                <button type="button" class="primary-btn btn-normal mb-3" id="verifyEmailBtn">Verificar Correo</button>

                <div id="additionalFields" style="display:none;">
                    
                    <div class="input-group">
                        <i class="fa-solid fa-user icon"></i>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Nombre(s)" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fa-solid fa-user-tag icon"></i>
                        <input type="text" class="form-control" id="paternal_surname" name="paternal_surname" placeholder="Apellido Paterno" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fa-solid fa-user-tag icon"></i>
                        <input type="text" class="form-control" id="maternal_surname" name="maternal_surname" placeholder="Apellido Materno">
                    </div>

                    <div class="input-group">
                         <i class="fa-solid fa-phone icon"></i>
                        <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="Teléfono Celular" required>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <i class="fa-solid fa-lock icon"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                            <i class="fa-solid fa-eye toggle-password"></i>
                        </div>
                        <small id="passwordHelpBlock" class="form-text text-muted">
                            <ul class="password-requirements">
                                <li id="reg_length"><i class="fa-solid fa-times"></i> Al menos 8 caracteres</li>
                                <li id="reg_uppercase"><i class="fa-solid fa-times"></i> Una letra mayúscula</li>
                                <li id="reg_lowercase"><i class="fa-solid fa-times"></i> Una letra minúscula</li>
                                <li id="reg_number"><i class="fa-solid fa-times"></i> Un número</li>
                                <li id="reg_special"><i class="fa-solid fa-times"></i> Un carácter especial (@$!%*?&)</li>
                            </ul>
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <i class="fa-solid fa-lock icon"></i>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmar Contraseña" required>
                            <i class="fa-solid fa-eye toggle-password"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="primary-btn">Registrarse</Gimnasio</button>
                </div>
            </form>
        </div>
    </div>
</section>
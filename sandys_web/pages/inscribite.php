<link href="https://fonts.googleapis.com/css2?family=Muli:wght@300;400;700&family=Oswald:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* --- 0. FIX PARA EL MENÚ SUPERIOR --- */
    /* Esto ocultará el botón "Inscríbete Ya" del menú superior en esta página. 
       Si tu botón tiene una clase específica, puedes agregarla aquí. */
    header a[href*="inscribite"], 
    .navbar a[href*="inscribite"], 
    .header-btn, 
    .inscribete-btn { 
        display: none !important; 
    }

    /* --- 1. GENERAL --- */
    body { background-color: #050505; color: #e0e0e0; font-family: 'Muli', sans-serif; }

    /* --- 2. LAYOUT --- */
    .login_box_area {
        /* Aumentamos el padding superior en PC para que el menú no estorbe */
        padding-top: 150px; padding-bottom: 80px; min-height: 100vh;
        display: flex; align-items: center; justify-content: center;
    }
    .login-container {
        display: flex; width: 100%; max-width: 1000px;
        background-color: #121212; border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
        border: 1px solid #2a2a2a; overflow: hidden;
        position: relative; z-index: 1; 
    }

    /* --- 3. COLUMNA IZQUIERDA --- */
    .login_box_img {
        position: relative; width: 45%;
        background: linear-gradient(135deg, #220a0a 0%, #000000 100%);
        display: flex; align-items: center; justify-content: center;
    }
    .login_box_img img { width: 100%; height: 100%; object-fit: cover; opacity: 0.5; position: absolute; top: 0; left: 0; }
    .img-overlay { position: relative; z-index: 2; padding: 40px; text-align: center; width: 100%; }
    .img-overlay h4 { font-family: 'Oswald', sans-serif; font-size: 36px; color: #fff; margin-bottom: 15px; text-transform: uppercase; }
    .btn-outline-light-custom {
        border: 2px solid #ef4444; color: #fff; background: rgba(0,0,0,0.3);
        padding: 10px 30px; border-radius: 50px; font-weight: bold; text-decoration: none; transition: 0.3s;
    }
    .btn-outline-light-custom:hover { background: #ef4444; box-shadow: 0 0 20px rgba(239, 68, 68, 0.6); color: #fff; }

    /* --- 4. FORMULARIO DERECHA --- */
    .login_form_inner { width: 55%; padding: 50px 40px; background-color: #121212; }
    .login_form_inner h3 { font-family: 'Oswald', sans-serif; font-size: 28px; color: #fff; text-transform: uppercase; margin-bottom: 5px; }
    .welcome-text { margin-bottom: 25px; color: #cccccc; font-size: 15px; }

    /* --- 5. INPUTS Y LABELS --- */
    .form-group, .input-group { margin-bottom: 20px; }
    .form-group label { color: #ffffff; font-size: 13px; font-weight: 700; margin-bottom: 8px; text-transform: uppercase; display: block; letter-spacing: 0.5px; }

    /* CONTENEDOR MÁGICO PARA ALINEAR ICONOS PERFECTAMENTE */
    .input-wrapper {
        position: relative;
        width: 100%;
    }

    #registrationForm .form-control {
        background-color: #121212 !important; 
        border: 1px solid #555 !important; 
        color: #ffffff !important;
        height: 50px !important; 
        border-radius: 8px !important;
        padding-left: 45px !important; 
        padding-right: 45px !important; 
        font-size: 15px !important; 
        width: 100%;
        -webkit-appearance: none; appearance: none;
        transition: all 0.3s ease;
    }
    #registrationForm .form-control::placeholder { color: #9ca3af !important; opacity: 1; }
    #registrationForm .form-control:focus { border-color: #ef4444 !important; outline: none; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important; }

    /* Fix Autocomplete Chrome */
    #registrationForm input:-webkit-autofill,
    #registrationForm input:-webkit-autofill:hover, 
    #registrationForm input:-webkit-autofill:focus, 
    #registrationForm input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 1000px #121212 inset !important;
        -webkit-text-fill-color: #ffffff !important; caret-color: #ffffff !important;
        border: 1px solid #555 !important;
        transition: background-color 9999s ease-in-out 0s;
    }
    input::-ms-reveal, input::-ms-clear { display: none; }

    /* Select Flecha */
    select.form-control {
        cursor: pointer;
        background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%239ca3af%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E');
        background-repeat: no-repeat; background-position: right 15px center; background-size: 10px auto;
    }

    /* --- 6. ICONOS CENTRADOS --- */
    .input-wrapper .input-icon { 
        position: absolute; 
        left: 15px; 
        top: 50%; /* Centrado vertical perfecto dentro del wrapper */
        transform: translateY(-50%);
        color: #9ca3af; 
        font-size: 18px; 
        pointer-events: none; 
        transition: 0.3s;
        z-index: 5;
    }
    .form-control:focus ~ .input-icon { color: #ef4444; }

    /* Ojo Contraseña */
    .input-wrapper .toggle-password { 
        position: absolute; 
        right: 15px; 
        top: 50%; /* Centrado vertical perfecto */
        transform: translateY(-50%);
        cursor: pointer; 
        color: #9ca3af; 
        z-index: 10; 
        transition: color 0.3s; 
        font-size: 18px;
    }
    .input-wrapper .toggle-password:hover { color: #ffffff; }

    /* Botón Cambiar Correo */
    .input-wrapper .btn-change-email { 
        position: absolute; right: 15px; top: 50%; transform: translateY(-50%); 
        background: transparent; border: none; color: #9ca3af; font-size: 13px; cursor: pointer; text-decoration: underline; z-index: 10; font-weight: bold; 
    }
    .input-wrapper .btn-change-email:hover { color: #ffffff; }

    /* --- 7. BOTONES --- */
    .primary-btn { background-color: #ef4444; color: white; border: none; padding: 14px; border-radius: 8px; font-family: 'Oswald', sans-serif; font-size: 16px; text-transform: uppercase; font-weight: 700; cursor: pointer; width: 100%; transition: 0.3s; }
    .primary-btn:hover { background-color: #d12f2f; transform: translateY(-2px); }
    .btn-normal { width: auto; padding: 10px 30px; border-radius: 50px; margin: 0 auto; }

    /* --- 8. EXTRAS --- */
    small { color: #aaaaaa !important; font-size: 12px !important; }
    .password-requirements { padding-left: 0; margin-bottom: 15px; list-style: none; font-size: 13px; color: #aaaaaa; display: flex; gap: 15px; }
    .password-requirements li i { font-size: 8px; margin-right: 5px; transform: translateY(-2px); display: inline-block; }
    .password-requirements li.valid { color: #4ade80; }

    /* --- 9. RESPONSIVE MÓVIL --- */
    @media (max-width: 991px) {
        .login_box_img { display: none; }
        .login_form_inner { width: 100%; padding: 30px 20px; }
        .login-container { max-width: 450px; border: none; background: transparent; box-shadow: none;}
        /* Ajuste de espacio para móviles para que no choque con el logo */
        .login_box_area { padding-top: 130px; }
    }
</style>

<section class="login_box_area">
    <div class="login-container">

        <div class="login_box_img d-none d-lg-flex">
            <div class="img-overlay">
                <h4>¡Ya eres miembro!</h4>
                <p>Inicia sesión para acceder a tu perfil.</p>
                <a class="btn-outline-light-custom" href="index.php?page=login">Iniciar Sesión</a>
            </div>
        </div>

        <div class="login_form_inner">
            <h3>Crea tu Cuenta</h3>
            <p class="welcome-text">Completa tus datos para unirte al gimnasio.</p>

            <form class="registration-form" id="registrationForm" novalidate>

                <div class="input-group" id="emailSection">
                    <div class="input-wrapper">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Correo Electrónico" required>
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <button type="button" id="changeEmailBtn" class="btn-change-email" style="display: none;">Cambiar</button>
                    </div>
                </div>

                <div class="text-center mb-4" id="verifyContainer">
                    <button type="button" class="primary-btn btn-normal" id="verifyEmailBtn">Continuar</button>
                    <p id="emailFeedback" style="color: #ef4444; font-size: 13px; margin-top: 8px; display: none; font-weight: bold;"></p>
                </div>

                <div id="additionalFields" style="display:none; opacity: 0;">

                    <div class="form-group">
                        <label for="referral_code" style="color: #ef4444;">¿Tienes un código de referido?</label>
                        <div class="input-wrapper">
                            <input type="text" class="form-control" id="referral_code" name="referral_code" placeholder="Teléfono de quien te invitó (Opcional)" maxlength="10">
                            <i class="fa-solid fa-gift input-icon"></i>
                        </div>
                        <small style="display: block; margin-top: 5px;">Si un amigo te invitó, ingresa su teléfono aquí.</small>
                    </div>

                    <div class="form-group">
                        <label>Nombre(s)</label>
                        <div class="input-wrapper">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Tu nombre" required>
                            <i class="fa-solid fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Apellido Paterno</label>
                            <div class="input-wrapper">
                                <input type="text" class="form-control" id="paternal_surname" name="paternal_surname" placeholder="Paterno" required>
                                <i class="fa-solid fa-user-tag input-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Apellido Materno</label>
                            <div class="input-wrapper">
                                <input type="text" class="form-control" id="maternal_surname" name="maternal_surname" placeholder="Materno">
                                <i class="fa-solid fa-user-tag input-icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Género</label>
                        <div class="input-wrapper">
                            <select class="form-control" id="genero" name="genero" required>
                                <option value="" selected disabled>Selecciona</option>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino">Femenino</option>
                            </select>
                            <i class="fa-solid fa-venus-mars input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mes de Nacimiento</label>
                        <div class="input-wrapper">
                            <select class="form-control" name="dob_month" id="dob_month" required>
                                <option value="" disabled selected>Selecciona tu Mes</option>
                                <option value="01">Enero</option>
                                <option value="02">Febrero</option>
                                <option value="03">Marzo</option>
                                <option value="04">Abril</option>
                                <option value="05">Mayo</option>
                                <option value="06">Junio</option>
                                <option value="07">Julio</option>
                                <option value="08">Agosto</option>
                                <option value="09">Septiembre</option>
                                <option value="10">Octubre</option>
                                <option value="11">Noviembre</option>
                                <option value="12">Diciembre</option>
                            </select>
                            <i class="fa-solid fa-calendar-alt input-icon"></i>
                        </div>
                        <input type="hidden" name="dob_day" value="01">
                        <input type="hidden" name="dob_year" value="2000">
                    </div>

                    <div class="form-group">
                        <label>Teléfono Celular</label>
                        <div class="input-wrapper">
                            <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="10 dígitos" required>
                            <i class="fa-solid fa-phone input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Contraseña</label>
                        <div class="input-wrapper">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Crea tu contraseña" required>
                            <i class="fa-solid fa-lock input-icon"></i>
                            <i class="fa-solid fa-eye toggle-password"></i>
                        </div>
                    </div>

                    <ul class="password-requirements">
                        <li id="reg_length"><i class="fas fa-circle"></i> 8+</li>
                        <li id="reg_uppercase"><i class="fas fa-circle"></i> Mayúscula</li>
                        <li id="reg_number"><i class="fas fa-circle"></i> Número</li>
                    </ul>

                    <div class="form-group">
                        <label>Confirmar Contraseña</label>
                        <div class="input-wrapper">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repite tu contraseña" required>
                            <i class="fa-solid fa-lock input-icon"></i>
                            <i class="fa-solid fa-eye toggle-password"></i>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="primary-btn">REGISTRARME</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
<head>
    <!-- Other head content -->
    <style>
        .banner-area {
            padding: 20px 0;
            background-color: #FFA500;
        }

        .breadcrumb-banner .col-first h1 {
            font-size: 36px;
            color: #ffffff;
            margin-bottom: 10px;
        }

        .breadcrumb-banner .col-first nav a {
            font-size: 18px;
            color: #ffffff;
        }

        .breadcrumb-banner .col-first nav a .lnr-arrow-right {
            font-size: 18px;
            margin-left: 5px;
            color: #ffffff;
        }
    </style>
</head>

<!-- Área de Banner -->
<section class="banner-area organic-breadcrumb" style="padding: 20px 0; background-color: #FFA500;">
    <div class="container">
        <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
            <div class="col-first">
                <h1 style="font-size: 36px; color: #ffffff; margin-bottom: 10px;">Registrarse</h1>
                <nav class="d-flex align-items-center">
                    <a href="index.php" style="font-size: 18px; color: #ffffff;">Inicio<span class="lnr lnr-arrow-right" style="font-size: 18px; margin-left: 5px;"></span></a>
                </nav>
            </div>
        </div>
    </div>
</section>
<!-- Fin del Área de Banner -->


<!-- Fin del Área de Banner -->

<!--================Área de Caja de Registro =================-->
<section class="login_box_area section_gap">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <div class="login_box_img">
                    <img class="img-fluid" src="./assets/img/login.jpg" alt="">
                    <div class="hover">
                        <h4>¿Ya eres miembro de nuestro gimnasio?</h4>
                        <p>Si ya tienes una cuenta, inicia sesión para acceder a todas nuestras funciones y servicios exclusivos.</p>
                        <a class="primary-btn" href="login.html">Iniciar Sesión</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="login_form_inner">
                    <h3>Crea una cuenta nueva</h3>
                    <form class="row login_form" id="registrationForm" novalidate="novalidate">
                        <div class="col-md-12 form-group">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Correo electrónico" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Correo electrónico'">
                            <button type="button" class="primary-btn" id="verifyEmail">Verificar Correo</button>
                        </div>
                        <div id="additionalFields" style="display:none;">
                            <div class="col-md-12 form-group">
                                <input type="text" class="form-control" id="name" name="name" placeholder="Nombre" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Nombre'">
                            </div>
                            <div class="col-md-12 form-group">
                                <input type="text" class="form-control" id="paternal_surname" name="paternal_surname" placeholder="Apellido Paterno" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Apellido Paterno'">
                            </div>
                            <div class="col-md-12 form-group">
                                <input type="text" class="form-control" id="maternal_surname" name="maternal_surname" placeholder="Apellido Materno" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Apellido Materno'">
                            </div>
                            <div class="col-md-12 form-group" style="position: relative;">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Contraseña'">
                                <span class="toggle-password" toggle="#password-field" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"><i class="fa fa-eye"></i></span>
                            </div>
                            <div class="col-md-12 form-group" style="position: relative;">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmar contraseña" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Confirmar contraseña'">
                                <span class="toggle-password" toggle="#password-field" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"><i class="fa fa-eye"></i></span>
                            </div>
                            <div class="col-md-12 form-group">
                                <button type="submit" class="primary-btn">Registrarse</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<!--================ Fin del Área de Caja de Registro =================-->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script>
$(document).ready(function() {
    $('#verifyEmail').click(function() {
        var email = $('#email').val();

        if (!email) {
            Swal.fire('Error', 'Por favor, introduce un correo electrónico.', 'error');
            return;
        }

        $.ajax({
            type: 'POST',
            url: './query/check_email.php',
            data: { email: email },
            dataType: 'json',
            encode: true
        }).done(function(data) {
            if (data.exists) {
                $('#name').val(data.name);
                $('#paternal_surname').val(data.paternal_surname);
                $('#maternal_surname').val(data.maternal_surname);
                $('#additionalFields').show();
                $('#verifyEmail').prop('disabled', true);
                $('#email').prop('readonly', true);
                localStorage.setItem('email', email); // Store the email in localStorage
            } else {
                if (data.message) {
                    Swal.fire('Error', data.message, 'error');
                } else {
                    $('#name').val('');
                    $('#paternal_surname').val('');
                    $('#maternal_surname').val('');
                    $('#additionalFields').show();
                    $('#verifyEmail').prop('disabled', true);
                    $('#email').prop('readonly', true);
                }
            }
        }).fail(function() {
            Swal.fire('Error', 'Hubo un problema al verificar el correo electrónico. Por favor, inténtalo de nuevo más tarde.', 'error');
        });
    });

    $('#registrationForm').submit(function(event) {
        event.preventDefault();

        var name = $('#name').val();
        var paternal_surname = $('#paternal_surname').val();
        var maternal_surname = $('#maternal_surname').val();
        var email = $('#email').val();
        var password = $('#password').val();
        var confirm_password = $('#confirm_password').val();

        if (!name || !paternal_surname || !maternal_surname || !email || !password || !confirm_password) {
            Swal.fire('Error', 'Por favor, rellena todos los campos.', 'error');
            return;
        }

        if (password !== confirm_password) {
            Swal.fire('Error', 'Las contraseñas no coinciden.', 'error');
            return;
        }

        var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+[\]{};:'",.<>/?`~])[A-Za-z\d!@#$%^&*()\-_=+[\]{};:'",.<>/?`~]{8,}$/ ;
        if (!passwordRegex.test(password)) {
            Swal.fire('Error', 'La contraseña debe tener al menos 8 caracteres, incluyendo una letra mayúscula, una letra minúscula, un número y un carácter especial.', 'error');
            return;
        }

        var formData = {
            name: name,
            paternal_surname: paternal_surname,
            maternal_surname: maternal_surname,
            email: email,
            password: password,
            confirm_password: confirm_password
        };

        $.ajax({
            type: 'POST',
            url: './query/registration_process.php',
            data: formData,
            dataType: 'json',
            encode: true
        }).done(function(data) {
            if (data.success) {
                Swal.fire('Éxito', 'Registrado correctamente. Verifica tu correo para activar tu cuenta.', 'success')
                    .then(() => {
                        window.location.href = 'index.php?page=validate';
                    });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        }).fail(function() {
            Swal.fire('Error', 'Hubo un problema al intentar registrarte. Por favor, inténtalo de nuevo más tarde.', 'error');
        });
    });

    // Show/hide password
    $('.toggle-password').click(function() {
        $(this).toggleClass("fa-eye fa-eye-slash");
        var input = $(this).parent().find('input');
        if (input.attr("type") == "password") {
            input.attr("type", "text");
        } else {
            input.attr("type", "password");
        }
    });
});
</script>

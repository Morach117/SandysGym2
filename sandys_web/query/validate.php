<!DOCTYPE html>
<html lang="es">

<head>
    <title>Validar Cuenta</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <section class="breadcrumb-section set-bg" data-setbg="./assets/img/breadcrumb-bg.jpg">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb-text">
                        <h2>Validar Cuenta</h2>
                        <div class="bt-option">
                            <a href="index.php?page=home">Inicio</a>
                            <span>Validación</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="login_box_area section_gap">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="login_form_inner text-center">
                        <h3>Verifica tu Cuenta</h3>
                        <p class="mb-4">
                            Hemos enviado un código de 4 dígitos a tu correo: 
                            <br>
                            <strong id="userEmail"></strong>
                        </p>
                        <form class="row login_form" id="validationForm" method="post" novalidate="novalidate">
                            <div class="col-md-12 form-group">
                                <input type="text" class="form-control text-center" id="validation_code" name="validation_code" placeholder="----" maxlength="4" required style="font-size: 2rem; letter-spacing: 15px;">
                            </div>
                            <div class="col-md-12 form-group">
                                <button type="submit" class="primary-btn">Validar Cuenta</button>
                            </div>
                        </form>
                        <a href="#" id="resendCodeLink">¿No recibiste el código? Reenviar</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
    $(document).ready(function() {
        // Obtenemos el email guardado en el LocalStorage durante el registro
        const email = localStorage.getItem('email');
        if (email) {
            $('#userEmail').text(email);
        } else {
            $('#userEmail').text('tu dirección de correo electrónico.');
        }

        $('#validationForm').submit(function(event) {
            event.preventDefault();
            
            var validationCode = $('#validation_code').val();
            var submitButton = $(this).find('button[type="submit"]');

            if (!validationCode || validationCode.length < 4) {
                Swal.fire('Atención', 'Por favor, introduce el código de 4 dígitos.', 'warning');
                return;
            }

            submitButton.prop('disabled', true).text('Validando...');

            $.ajax({
                type: 'POST',
                url: './query/validate_code.php',
                data: { validation_code: validationCode },
                dataType: 'json'
            }).done(function(response) {
                if (response.success) {
                    // Si la validación es exitosa, limpiamos el email del storage
                    localStorage.removeItem('email');
                    Swal.fire({
                        icon: 'success',
                        title: '¡Validación Exitosa!',
                        text: response.message,
                        timer: 2500,
                        showConfirmButton: false
                    }).then(() => {
                        // Redirigimos al usuario a la página de inicio de sesión
                        window.location.href = 'index.php?page=login';
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }).fail(function() {
                Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor. Inténtalo de nuevo.', 'error');
            }).always(function() {
                submitButton.prop('disabled', false).text('Validar Cuenta');
            });
        });

        // Opcional: Lógica para reenviar el código (necesitarás crear el script PHP correspondiente)
        $('#resendCodeLink').click(function(e){
            e.preventDefault();
            // Aquí iría una llamada AJAX a un script 'resend_code.php' que use el email guardado en localStorage
            Swal.fire('Info', 'La funcionalidad de reenviar código aún no está implementada.', 'info');
        });
    });
    </script>

</body>
</html>
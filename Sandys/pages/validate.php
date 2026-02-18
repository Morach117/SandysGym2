<!-- Área de Banner -->
<section class="banner-area organic-breadcrumb">
    <div class="container">
        <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
            <div class="col-first">
                <h1>Validar Cuenta</h1>
                <nav class="d-flex align-items-center">
                    <a href="index.php">Inicio<span class="lnr lnr-arrow-right"></span></a>
                    <a href="register.html">Registrarse<span class="lnr lnr-arrow-right"></span></a>
                    <a href="validate.html">Validar Cuenta</a>
                </nav>
            </div>
        </div>
    </div>
</section>
<!-- Fin del Área de Banner -->

<!-- Área de Caja de Validación de Cuenta -->
<section class="login_box_area section_gap">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="login_form_inner">
                    <h3>Introduce el código de validación</h3>
                    <form class="row login_form" id="validateForm" novalidate="novalidate">
                        <div class="col-md-12 form-group">
                            <input type="text" class="form-control" id="validation_code" name="validation_code" placeholder="Código de validación" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Código de validación'">
                        </div>
                        <div class="col-md-12 form-group">
                            <button type="button" id="validateButton" class="primary-btn">Validar Cuenta</button>
                        </div>
                        <div class="col-md-12 form-group">
                            <button type="button" id="resendCodeButton" class="primary-btn">Reenviar Código</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Fin del Área de Caja de Validación de Cuenta -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<!-- AJAX para validar la cuenta -->
<script>
$(document).ready(function() {
    var email = localStorage.getItem('email'); // Retrieve stored email if any

    // Escucha el clic en el botón de validar cuenta
    $('#validateButton').click(function() {
        var validationCode = $('#validation_code').val();

        if (validationCode.trim() === '') {
            Swal.fire({
                icon: 'error',
                title: 'Campo Vacío',
                text: 'Por favor, introduce un código de validación.'
            });
            return;
        }

        $.ajax({
            type: 'POST',
            url: './query/validate_process.php',
            data: { validation_code: validationCode },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Cuenta validada con éxito!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(function() {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al validar la cuenta',
                        text: response.message
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al enviar la solicitud',
                    text: 'Hubo un problema al intentar validar la cuenta. Por favor, inténtalo de nuevo más tarde.'
                });
            }
        });
    });

    // Escucha el clic en el botón de reenviar código
    $('#resendCodeButton').click(function() {
        if (!email) {
            Swal.fire({
                icon: 'error',
                title: 'Correo no encontrado',
                text: 'No se encontró un correo electrónico asociado. Por favor, vuelve a registrarte.'
            });
            return;
        }

        $.ajax({
            type: 'POST',
            url: './query/resend_code_process.php',
            data: { email: email },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Código reenviado!',
                        text: response.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al reenviar el código',
                        text: response.message
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al enviar la solicitud',
                    text: 'Hubo un problema al intentar reenviar el código. Por favor, inténtalo de nuevo más tarde.'
                });
            }
        });
    });
});
</script>


$(document).ready(function() {

    // =======================================================================
    // #1: MANEJADOR DEL FORMULARIO DE LOGIN PRINCIPAL
    // =======================================================================
    // This uses event delegation which is efficient.
    $(document).on("submit", "#adminLoginFrm", function(event) {
        event.preventDefault(); // Prevents the default form submission (page reload)

        $.post("query/login.php", $(this).serialize(), function(data) {
            if (data.res == "invalid_email") {
                Swal.fire({
                    title: 'Correo no válido',
                    text: 'Por favor, ingrese un correo electrónico válido.',
                    icon: 'error',
                    confirmButtonColor: '#F28123'
                });
            } else if (data.res == "invalid") {
                Swal.fire({
                    title: 'Credenciales incorrectas',
                    text: 'El correo o la contraseña no son correctos. Por favor, inténtelo de nuevo.',
                    icon: 'error',
                    confirmButtonColor: '#F28123'
                });
            } else if (data.res == "inactive_email") {
                Swal.fire({
                    title: 'Cuenta inactiva',
                    text: 'Esta cuenta no está activa. Por favor, contacte al administrador.',
                    icon: 'warning',
                    confirmButtonColor: '#F28123'
                });
            } else if (data.res == "locked") {
                Swal.fire({
                    title: 'Cuenta bloqueada',
                    text: 'Demasiados intentos fallidos. Su cuenta ha sido bloqueada temporalmente.',
                    icon: 'error',
                    confirmButtonColor: '#F28123'
                });
            } else if (data.res == "success") {
                // Optional: Show a success message before redirecting
                Swal.fire({
                    title: '¡Bienvenido!',
                    icon: 'success',
                    timer: 1500, // Auto-close after 1.5 seconds
                    showConfirmButton: false
                }).then(() => {
                    $('body').fadeOut(400, function() {
                        window.location.href = 'index.php?page=user_home';
                    });
                });
            }
        }, 'json');
    });

    // =======================================================================
    // #2: FUNCIONALIDAD DEL "OJO" PARA MOSTRAR/OCULTAR CONTRASEÑA
    // =======================================================================
    $('#togglePassword').on('click', function() {
        const passwordField = $('#password');
        const passwordFieldType = passwordField.attr('type');

        if (passwordFieldType === 'password') {
            passwordField.attr('type', 'text');
            $(this).removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            $(this).removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // =======================================================================
    // #3: MANEJADOR DEL FORMULARIO PARA RECUPERAR CONTRASEÑA (MODAL)
    // =======================================================================
    $('#passwordResetRequestFrm').on('submit', function(event) {
        event.preventDefault();
        var email = $('#reset_email').val();

        $.ajax({
            url: './query/password_reset_request.php',
            method: 'POST',
            data: { email: email },
            success: function(response) {
                // 1. Close the Bootstrap modal.
                $('#forgotPasswordModal').modal('hide');

                // 2. Show the SweetAlert confirmation.
                Swal.fire({
                    title: '¡Revisa tu correo!',
                    text: 'Si tu correo electrónico está registrado, recibirás un enlace para restablecer tu contraseña.',
                    icon: 'success',
                    confirmButtonColor: '#F28123'
                }).then((result) => {
                    // 3. Force-clean the screen to prevent it from freezing.
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                });
            },
            error: function() {
                // If there's an error, also make sure to clean up the screen.
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');

                Swal.fire({
                    title: 'Error de Conexión',
                    text: 'No se pudo enviar la solicitud. Por favor, inténtalo de nuevo más tarde.',
                    icon: 'error',
                    confirmButtonColor: '#F28123'
                });
            }
        });
    });

});
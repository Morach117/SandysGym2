$(document).ready(function () {

    $(document).on("submit", "#adminLoginFrm", function (event) {
        event.preventDefault();

        $.post("./api/login.php", $(this).serialize(), function (data) {
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
                Swal.fire({
                    title: '¡Bienvenido!',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    $('body').fadeOut(400, function () {
                        window.location.href = 'index.php?page=user_home';
                    });
                });
            }
        }, 'json');
    });

    $('.toggle-password').on('click', function () {
        const passwordField = $(this).siblings('input'); 
        const passwordFieldType = passwordField.attr('type');

        if (passwordFieldType === 'password') {
            passwordField.attr('type', 'text');
            $(this).removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            $(this).removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    $('#passwordResetRequestFrm').on('submit', function (event) {
        event.preventDefault();
        
        let btnReset = $(this).find('button[type="submit"]');
        if (btnReset.prop('disabled')) return;
        
        let originalResetText = btnReset.html();
        btnReset.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Enviando...');
        
        var email = $('#reset_email').val();

        $.ajax({
            url: './api/password_reset_request.php',
            method: 'POST',
            data: { email: email },
            success: function (response) {
                btnReset.prop('disabled', false).html(originalResetText);
                $('#forgotPasswordModal').modal('hide');

                Swal.fire({
                    title: '¡Revisa tu correo!',
                    text: 'Si tu correo electrónico está registrado, recibirás un enlace para restablecer tu contraseña.',
                    icon: 'success',
                    confirmButtonColor: '#F28123'
                }).then((result) => {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                });
            },
            error: function () {
                btnReset.prop('disabled', false).html(originalResetText);
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
$(document).ready(function() {

    // =======================================================================
    // #1: MANEJADOR DEL FORMULARIO DE LOGIN PRINCIPAL
    // =======================================================================
    $(document).on("submit", "#adminLoginFrm", function(event) {
        event.preventDefault(); 
        
        let btnSubmit = $('#btnLoginSubmit');
        let originalText = btnSubmit.html();
        
        // Estado de carga
        btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Validando...');

        $.post("query/login.php", $(this).serialize(), function(data) {
            
            // Restaurar botón
            btnSubmit.prop('disabled', false).html(originalText);

            if (data.res == "invalid_email") {
                Swal.fire({
                    title: 'Correo no válido',
                    text: 'Por favor, ingrese un correo electrónico válido.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444',
                    background: '#1a1a1a', color: '#fff'
                });
            } else if (data.res == "invalid") {
                Swal.fire({
                    title: 'Credenciales incorrectas',
                    text: 'El correo o la contraseña no son correctos. Por favor, inténtelo de nuevo.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444',
                    background: '#1a1a1a', color: '#fff'
                });
            } else if (data.res == "inactive_email") {
                Swal.fire({
                    title: 'Cuenta inactiva',
                    text: 'Esta cuenta no está activa. Por favor, contacte al administrador.',
                    icon: 'warning',
                    confirmButtonColor: '#ef4444',
                    background: '#1a1a1a', color: '#fff'
                });
            } else if (data.res == "locked") {
                Swal.fire({
                    title: 'Cuenta bloqueada',
                    text: 'Demasiados intentos fallidos. Su cuenta ha sido bloqueada temporalmente.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444',
                    background: '#1a1a1a', color: '#fff'
                });
            } else if (data.res == "success") {
                Swal.fire({
                    title: '¡Bienvenido!',
                    icon: 'success',
                    timer: 1500, 
                    showConfirmButton: false,
                    background: '#1a1a1a', color: '#fff'
                }).then(() => {
                    $('body').fadeOut(400, function() {
                        window.location.href = 'index.php?page=user_home';
                    });
                });
            }
        }, 'json').fail(function() {
            btnSubmit.prop('disabled', false).html(originalText);
            Swal.fire({ title: 'Error', text: 'Problema de conexión con el servidor.', icon: 'error', confirmButtonColor: '#ef4444', background: '#1a1a1a', color: '#fff' });
        });
    });

    // =======================================================================
    // #2: FUNCIONALIDAD DEL "OJO" PARA MOSTRAR/OCULTAR CONTRASEÑA
    // =======================================================================
    $('#togglePassword').on('click', function() {
        const passwordField = $('#password');
        const passwordFieldType = passwordField.attr('type');

        if (passwordFieldType === 'password') {
            passwordField.attr('type', 'text');
            $(this).removeClass('fa-eye').addClass('fa-eye-slash text-danger');
        } else {
            passwordField.attr('type', 'password');
            $(this).removeClass('fa-eye-slash text-danger').addClass('fa-eye');
        }
    });

    // =======================================================================
    // #3: MANEJADOR DEL FORMULARIO PARA RECUPERAR CONTRASEÑA (MODAL)
    // =======================================================================
    $('#passwordResetRequestFrm').on('submit', function(event) {
        event.preventDefault();
        
        var email = $('#reset_email').val();
        let btnReset = $('#btnResetSubmit');
        let originalResetText = btnReset.html();

        btnReset.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Enviando...');

        $.ajax({
            url: './query/password_reset_request.php',
            method: 'POST',
            data: { email: email },
            success: function(response) {
                btnReset.prop('disabled', false).html(originalResetText);
                $('#forgotPasswordModal').modal('hide');

                Swal.fire({
                    title: '¡Revisa tu correo!',
                    text: 'Si tu correo electrónico está registrado, recibirás un enlace para restablecer tu contraseña.',
                    icon: 'success',
                    confirmButtonColor: '#ef4444',
                    background: '#1a1a1a', color: '#fff'
                }).then((result) => {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                });
            },
            error: function() {
                btnReset.prop('disabled', false).html(originalResetText);
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');

                Swal.fire({
                    title: 'Error de Conexión',
                    text: 'No se pudo enviar la solicitud. Por favor, inténtalo de nuevo más tarde.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444',
                    background: '#1a1a1a', color: '#fff'
                });
            }
        });
    });

});
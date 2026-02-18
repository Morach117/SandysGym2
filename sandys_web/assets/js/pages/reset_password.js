$(document).ready(function() {
    // Solo ejecuta este código si el formulario de reseteo existe en la página actual
    if ($('#passwordResetFrm').length) {
        
        const passwordInput = $('#new_password');
        const requirements = {
            length: $('#length'),
            uppercase: $('#uppercase'),
            lowercase: $('#lowercase'),
            number: $('#number'),
            special: $('#special')
        };

        // --- VALIDACIÓN DE CONTRASEÑA EN TIEMPO REAL ---
        passwordInput.on('keyup', function() {
            const password = $(this).val();
            
            if (password.length >= 8) requirements.length.addClass('valid');
            else requirements.length.removeClass('valid');
            
            if (/[A-Z]/.test(password)) requirements.uppercase.addClass('valid');
            else requirements.uppercase.removeClass('valid');
            
            if (/[a-z]/.test(password)) requirements.lowercase.addClass('valid');
            else requirements.lowercase.removeClass('valid');
            
            if (/\d/.test(password)) requirements.number.addClass('valid');
            else requirements.number.removeClass('valid');
            
            if (/[@$!%*?&]/.test(password)) requirements.special.addClass('valid');
            else requirements.special.removeClass('valid');
        });

        // --- MOSTRAR/OCULTAR CONTRASEÑA (OJO) ---
        $('#togglePassword').on('click', function() {
            const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
            passwordInput.attr('type', type);
            $(this).toggleClass('fa-eye fa-eye-slash');
        });

        // --- ENVÍO DEL FORMULARIO CON AJAX ---
        $('#passwordResetFrm').on('submit', function(event) {
            event.preventDefault(); 
            
            const token = $('#token').val();
            const newPassword = passwordInput.val();
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

            // Validación del lado del cliente antes de enviar
            if (!passwordRegex.test(newPassword)) {
                Swal.fire('Error', 'La contraseña no cumple con todos los requisitos de seguridad.', 'error');
                return;
            }

            $.ajax({
                url: './api/reset_password.php',
                method: 'POST',
                data: {
                    token: token,
                    new_password: newPassword
                },
                success: function(response) {
                    // Ya no se necesita JSON.parse(), 'response' es el objeto.
                    if (response.status === 'success') {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.message, 
                            icon: 'success'
                        }).then(() => {
                            // Redirige al login
                            window.location.href = 'index.php?page=login';
                        });
                    } else {
                        // Si el servidor SÍ envió un error (ej. token expirado)
                        Swal.fire('Error', response.message || 'Ocurrió un error.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Hubo un problema de conexión. Inténtalo de nuevo más tarde.', 'error');
                }
            });
        });
    }
});
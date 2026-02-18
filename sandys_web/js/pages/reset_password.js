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
                // ... (toda tu lógica de validación) ...
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
                event.preventDefault(); // Ahora sí funcionará
                const token = $('#token').val();
                const newPassword = passwordInput.val();
                const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

                if (!passwordRegex.test(newPassword)) {
                    Swal.fire('Error', 'La contraseña no cumple con todos los requisitos de seguridad.', 'error');
                    return;
                }

                $.ajax({
                    url: './query/reset_password.php',
                    method: 'POST',
                    data: {
                        token: token,
                        new_password: newPassword
                    },
                    success: function(response) {
                        try {
                            var data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire({
                                    title: '¡Éxito!',
                                    text: 'Tu contraseña ha sido restablecida correctamente.',
                                    icon: 'success'
                                }).then(() => {
                                    window.location.href = 'index.php?page=login';
                                });
                            } else {
                                Swal.fire('Error', data.message || 'El enlace de restablecimiento es inválido o ha expirado.', 'error');
                            }
                        } catch (e) {
                            Swal.fire('Error', 'Hubo una respuesta inesperada del servidor.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Hubo un problema de conexión. Inténtalo de nuevo más tarde.', 'error');
                    }
                });
            });
        }
    });
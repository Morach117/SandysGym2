$(document).ready(function () {
    // Obtenemos el email guardado en el LocalStorage durante el registro
    const email = localStorage.getItem('email');
    if (email) {
        $('#userEmail').text(email);
    } else {
        $('#userEmail').text('tu dirección de correo electrónico.');
    }

    $('#validationForm').submit(function (event) {
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
            url: './api/validate_process.php',
            data: { validation_code: validationCode },
            dataType: 'json'
        }).done(function (response) {
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
        }).fail(function () {
            Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor. Inténtalo de nuevo.', 'error');
        }).always(function () {
            submitButton.prop('disabled', false).text('Validar Cuenta');
        });
    });

    // --- CAMBIO AQUÍ: FUNCIONALIDAD DE REENVIAR CÓDIGO AÑADIDA ---
    $('#resendCodeLink').click(function (e) {
        e.preventDefault();
        
        // 1. Verificamos que tengamos el email del localStorage
        if (!email) {
            Swal.fire('Error', 'No se pudo encontrar tu correo. Por favor, regresa y vuelve a registrarte.', 'error');
            return;
        }

        var link = $(this);
        // Deshabilitamos el link temporalmente
        link.css('pointer-events', 'none').text('Reenviando...');

        // 2. Llamada AJAX a tu nuevo script resend_code.php
        $.ajax({
            type: 'POST',
            url: './api/resend_code_process.php', // Apunta a tu nuevo archivo PHP
            data: { email: email },      // Envía el correo
            dataType: 'json'
        }).done(function (response) {
            if (response.success) {
                Swal.fire('¡Enviado!', response.message, 'success');
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }).fail(function () {
            Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor. Inténtalo de nuevo.', 'error');
        }).always(function () {
            // Volvemos a habilitar el link
            link.css('pointer-events', 'auto').text('¿No recibiste el código? Reenviar');
        });
    });
});
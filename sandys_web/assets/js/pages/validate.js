$(document).ready(function () {
    const urlParams = new URLSearchParams(window.location.search);
    const email = urlParams.get('email') || localStorage.getItem('email');
    if (email) {
        $('#userEmail').text(email);
    } else {
        $('#userEmail').text('tu dirección de correo electrónico.');
    }

    const inputs = document.querySelectorAll('.code-input');
    const hiddenInput = document.getElementById('validation_code');

    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            if (e.target.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            updateHiddenInput();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && e.target.value.length === 0 && index > 0) {
                inputs[index - 1].focus();
            }
        });
        
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            let pasteData = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
            for(let i = 0; i < pasteData.length; i++) {
                if (index + i < inputs.length) {
                    inputs[index + i].value = pasteData[i];
                }
            }
            if (index + pasteData.length < inputs.length) {
                inputs[index + pasteData.length].focus();
            } else {
                inputs[inputs.length - 1].focus();
            }
            updateHiddenInput();
        });
    });

    function updateHiddenInput() {
        hiddenInput.value = Array.from(inputs).map(input => input.value).join('');
    }

    $('#validationForm').submit(function (event) {
        event.preventDefault();

        var validationCode = $('#validation_code').val();
        var submitButton = $(this).find('button[type="submit"]');

        if (!validationCode || validationCode.length < 6 || !/^\d+$/.test(validationCode)) {
            Swal.fire('Atención', 'Por favor, introduce el código de 6 dígitos completo y numérico.', 'warning');
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
                localStorage.removeItem('email');
                Swal.fire({
                    icon: 'success',
                    title: '¡Validación Exitosa!',
                    text: response.message,
                    timer: 2500,
                    showConfirmButton: false
                }).then(() => {
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

    $('#resendCodeLink').click(function (e) {
        e.preventDefault();
        
        var fallbackEmail = $('#resend_email_fallback').val();
        var currentEmail = fallbackEmail || email;

        if (!currentEmail) {
            console.error("Error: Correo no encontrado.");
            Swal.fire('Error', 'No se pudo encontrar tu correo. Por favor, regresa y vuelve a registrarte.', 'error');
            return;
        }

        var link = $(this);
        link.css('pointer-events', 'none').text('Reenviando...');

        $.ajax({
            type: 'POST',
            url: './api/resend_code_process.php',
            data: { email: currentEmail },
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
            link.css('pointer-events', 'auto').text('¿No recibiste el código? Reenviar');
        });
    });
});
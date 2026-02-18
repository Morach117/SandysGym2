$(document).ready(function() {

    // --- FUNCIÓN PARA MOSTRAR/OCULTAR CAMPOS ---
    function showAdditionalFields(isLocked, data = {}) {
        if (isLocked) {
            $('#additionalFields').slideDown(); // Muestra los campos con una animación suave
            $('#email').prop('readonly', true);
            // Cambia el botón a "Cambiar correo"
            $('#verifyEmailBtn').text('Cambiar Correo').removeClass('verify-action').addClass('change-action');

            // Si el correo existe, llena los campos
            if (data.exists) {
                $('#name').val(data.name);
                $('#paternal_surname').val(data.paternal_surname);
                $('#maternal_surname').val(data.maternal_surname);
            } else {
                // Si es un correo nuevo, asegúrate de que los campos estén vacíos
                $('#name').val('').focus(); // Coloca el cursor en el campo de nombre
                $('#paternal_surname').val('');
                $('#maternal_surname').val('');
                $('#telefono').val(''); // Limpia el teléfono también
            }
        } else {
            // Oculta los campos y restaura el estado inicial
            $('#additionalFields').slideUp();
            $('#email').prop('readonly', false).focus();
            $('#verifyEmailBtn').text('Verificar Correo').removeClass('change-action').addClass('verify-action');
        }
    }

    // --- MANEJADOR DEL BOTÓN DE VERIFICAR/CAMBIAR CORREO ---
    $('#verifyEmailBtn').click(function() {
        // Si el botón es para "Cambiar", ejecuta la lógica de desbloqueo
        if ($(this).hasClass('change-action')) {
            showAdditionalFields(false);
            return;
        }

        var email = $('#email').val();
        if (!email) {
            Swal.fire('Atención', 'Por favor, introduce un correo electrónico.', 'warning');
            return;
        }

        var btn = $(this);
        // Feedback de carga para el usuario
        btn.prop('disabled', true).text('Verificando...');

        $.ajax({
            type: 'POST',
            url: './api/check_email.php',
            data: {
                email: email
            },
            dataType: 'json'
        }).done(function(data) {
            if (data.message) { // Si el servidor devuelve un error específico
                Swal.fire('Error', data.message, 'error');
            } else {
                localStorage.setItem('email', email);
                showAdditionalFields(true, data); // Llama a la función para mostrar los campos
            }
        }).fail(function() {
            Swal.fire('Error', 'Hubo un problema al verificar el correo. Por favor, inténtalo de nuevo.', 'error');
        }).always(function() {
            // Siempre vuelve a habilitar el botón, sin importar si falló o no
            if (!btn.hasClass('change-action')) {
                btn.prop('disabled', false).text('Verificar Correo');
            }
        });
    });

    // --- VALIDACIÓN DE CONTRASEÑA EN TIEMPO REAL ---
    const passwordInput = $('#password'); // Apunta al input de contraseña de registro
    const requirements = {
        length: $('#reg_length'), // Apunta al <li> con el ID de registro
        uppercase: $('#reg_uppercase'), // Apunta al <li> con el ID de registro
        lowercase: $('#reg_lowercase'), // Apunta al <li> con el ID de registro
        number: $('#reg_number'), // Apunta al <li> con el ID de registro
        special: $('#reg_special') // Apunta al <li> con el ID de registro
    };

    if (passwordInput.length > 0) { // Asegura que el script no falle si el elemento no existe
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
    }

    // --- CÓDIGO NUEVO PARA MOSTRAR/OCULTAR CONTRASEÑA (OJO) ---
    $('.toggle-password').on('click', function() {
        // 'this' es el ícono (<i>) en el que se hizo clic
        
        // 1. Encuentra el campo de contraseña (input) que es "hermano" del ícono
        const passwordField = $(this).siblings('input');

        // 2. Revisa el tipo actual y cámbialo
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);

        // 3. Cambia el ícono de 'ojo' a 'ojo tachado' y viceversa
        $(this).toggleClass('fa-eye fa-eye-slash');
    });
    // --- FIN DEL CÓDIGO NUEVO ---

    // --- MANEJADOR DEL ENVÍO DEL FORMULARIO DE REGISTRO ---
    $('#registrationForm').submit(function(event) {
        event.preventDefault();

        var password = $('#password').val();
        var confirm_password = $('#confirm_password').val();

        // --- VALIDACIÓN DE CAMPOS (TELÉFONO AÑADIDO) ---
        if (!$('#name').val() || !$('#paternal_surname').val() || !$('#telefono').val() || !$('#email').val() || !password || !confirm_password) {
            Swal.fire('Error', 'Por favor, rellena todos los campos requeridos.', 'error');
            return;
        }

        if (password !== confirm_password) {
            Swal.fire('Error', 'Las contraseñas no coinciden.', 'error');
            return;
        }

        var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        if (!passwordRegex.test(password)) {
            Swal.fire('Error', 'La contraseña no cumple con los requisitos de seguridad.', 'error');
            return;
        }

        // --- DATOS DEL FORMULARIO (TELÉFONO AÑADIDO) ---
        var formData = {
            name: $('#name').val(),
            paternal_surname: $('#paternal_surname').val(),
            maternal_surname: $('#maternal_surname').val(),
            telefono: $('#telefono').val(), // Campo añadido
            email: $('#email').val(),
            password: password
        };

        var submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Registrando...');

        $.ajax({
            type: 'POST',
            url: './api/registration_process.php',
            data: formData,
            dataType: 'json',
        }).done(function(data) {
            if (data.success) {
                Swal.fire('¡Éxito!', 'Te has registrado correctamente. Revisa tu correo para el código de validación.', 'success')
                    .then(() => {
                        // Redirige a la página de validación
                        window.location.href = 'index.php?page=validate';
                    });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        }).fail(function() {
            Swal.fire('Error', 'Hubo un problema con el registro. Por favor, inténtalo de nuevo.', 'error');
        }).always(function() {
            submitButton.prop('disabled', false).text('Registrarse');
        });
    });
});
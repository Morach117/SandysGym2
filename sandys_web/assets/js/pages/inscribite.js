$(document).ready(function() {

    // ==========================================
    // 0. AUTO-LLENADO DE REFERIDO POR URL
    // ==========================================
    // Si la URL es ...?ref=9191234567, llenamos el input automáticamente
    const urlParams = new URLSearchParams(window.location.search);
    const refCode = urlParams.get('ref');

    if (refCode) {
        $('#referral_code').val(refCode).css('border-color', '#22c55e');
    }

    // ==========================================
    // 1. CONFIGURACIÓN Y SELECTORES
    // ==========================================
    const UI = {
        form: $('#registrationForm'),
        inputs: {
            email: $('#email'),
            password: $('#password'),
            confirmPass: $('#confirm_password'),
            name: $('#name'),
            paternal: $('#paternal_surname'),
            maternal: $('#maternal_surname'), 
            telefono: $('#telefono'),
            dobMonth: $('#dob_month'),
            genero: $('#genero'),
            referral: $('#referral_code') // <--- AGREGADO AQUÍ
        },
        buttons: {
            verify: $('#verifyEmailBtn'),
            changeEmail: $('#changeEmailBtn'),
            submit: $('button[type="submit"]')
        },
        containers: {
            verify: $('#verifyContainer'),
            additional: $('#additionalFields'),
            feedback: $('#emailFeedback')
        },
        requirements: {
            length: $('#reg_length'),
            upper: $('#reg_uppercase'),
            number: $('#reg_number')
        },
        toggles: $('.toggle-password') 
    };

    const REGEX_EMAIL = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    // ==========================================
    // 2. LÓGICA DE CORREO
    // ==========================================
    UI.buttons.verify.on('click', function() {
        const emailVal = UI.inputs.email.val().trim();
        
        if (!REGEX_EMAIL.test(emailVal)) {
            Swal.fire({
                icon: 'warning',
                title: 'Correo Inválido',
                text: 'Por favor ingresa un correo válido.',
                background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444'
            });
            UI.inputs.email.css('border-color', '#ef4444');
            return;
        }
        
        resetInputStyle(UI.inputs.email); 
        setLoading(UI.buttons.verify, true, '<i class="fas fa-spinner fa-spin"></i> Verificando...');

        $.ajax({
            type: 'POST',
            url: './api/check_email.php',
            data: { email: emailVal },
            dataType: 'json'
        })
        .done(function(response) {
            if (response.exists) {
                showInlineFeedback(response.message || "El correo ya está en uso.", true);
            } else {
                showInlineFeedback("", false); 
                lockEmailState(true);
            }
        })
        .fail(function() {
             // Si falla la conexión (ej. localhost sin internet), permitimos avanzar para pruebas
             lockEmailState(true); 
        })
        .always(function() {
            setLoading(UI.buttons.verify, false, 'Continuar <i class="fas fa-arrow-right ml-2"></i>');
        });
    });

    UI.buttons.changeEmail.on('click', function() {
        UI.containers.additional.find('input:not([type=hidden]), select').val('').trigger('change');
        UI.containers.additional.find('.form-control').css('border-color', '#333');
        $('.password-requirements li').removeClass('valid').find('i').removeClass('fa-check').addClass('fa-circle').css('color', '');
        $('.password-requirements li').css('color', '');
        lockEmailState(false);
    });

    function lockEmailState(isLocked) {
        if (isLocked) {
            UI.inputs.email.prop('readonly', true);
            UI.containers.verify.slideUp();
            UI.containers.additional.css({opacity: 0, display: 'block'}).animate({ opacity: 1 }, 400);
            UI.buttons.changeEmail.fadeIn();
        } else {
            UI.inputs.email.prop('readonly', false).focus().select();
            UI.containers.additional.slideUp();
            UI.containers.verify.slideDown();
            UI.buttons.changeEmail.fadeOut();
            showInlineFeedback("", false);
        }
    }

    function showInlineFeedback(msg, isError) {
        const color = isError ? '#ef4444' : '#22c55e';
        if(msg) {
            UI.containers.feedback.text(msg).css('color', color).slideDown();
            if(isError) UI.inputs.email.css('border-color', color);
        } else {
            UI.containers.feedback.slideUp();
            UI.inputs.email.css('border-color', '#333');
        }
    }

    // ==========================================
    // 3. LÓGICA DE PASSWORD
    // ==========================================
    
    UI.toggles.on('click', function() {
        const icon = $(this);
        const input = icon.siblings('input');
        if (input.length > 0) {
            const type = input.attr('type') === 'password' ? 'text' : 'password';
            input.attr('type', type);
            icon.toggleClass('fa-eye fa-eye-slash');
            icon.css('color', type === 'text' ? '#ef4444' : '#555');
        }
    });

    UI.inputs.password.on('input', function() {
        const val = $(this).val();
        updateRequirement(UI.requirements.length, val.length >= 8);
        updateRequirement(UI.requirements.upper, /[A-Z]/.test(val));
        updateRequirement(UI.requirements.number, /[0-9]/.test(val));
        if (UI.inputs.confirmPass.val().length > 0) validateMatch();
    });

    UI.inputs.confirmPass.on('input', validateMatch);

    function validateMatch() {
        const pass = UI.inputs.password.val();
        const confirm = UI.inputs.confirmPass.val();
        if (confirm.length === 0) { UI.inputs.confirmPass.css('border-color', '#333'); return; }
        UI.inputs.confirmPass.css('border-color', pass !== confirm ? '#ef4444' : '#22c55e');
    }

    function updateRequirement(element, isValid) {
        const icon = element.find('i');
        if (isValid) {
            element.addClass('valid').css('color', '#4ade80');
            icon.removeClass('fa-circle').addClass('fa-check');
        } else {
            element.removeClass('valid').css('color', '#666');
            icon.removeClass('fa-check').addClass('fa-circle');
        }
    }

    // ==========================================
    // 4. ENVÍO DEL FORMULARIO
    // ==========================================
    UI.form.on('submit', function(event) {
        event.preventDefault();

        // 1. Validar vacíos
        let hasError = false;
        const requiredFields = [
            UI.inputs.name, UI.inputs.paternal, UI.inputs.telefono, 
            UI.inputs.genero, UI.inputs.dobMonth, UI.inputs.password
        ];
        
        requiredFields.forEach(field => {
            if (!field.val()) {
                field.css('border-color', '#ef4444');
                hasError = true;
            } else {
                field.css('border-color', '#333');
            }
        });

        if (hasError) {
            Swal.fire({ icon: 'warning', title: 'Faltan Datos', text: 'Completa los campos marcados en rojo.', background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444' });
            return;
        }

        if (UI.inputs.password.val() !== UI.inputs.confirmPass.val()) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Las contraseñas no coinciden.', background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444' });
            return;
        }

        // 2. PREPARAR DATOS (AQUÍ ESTABA LA MAGIA FALTANTE)
        const formData = {
            name: UI.inputs.name.val(),
            paternal_surname: UI.inputs.paternal.val(),
            maternal_surname: UI.inputs.maternal.val(),
            telefono: UI.inputs.telefono.val(),
            email: UI.inputs.email.val(),
            password: UI.inputs.password.val(),
            genero: UI.inputs.genero.val(),
            mes_nacimiento: UI.inputs.dobMonth.val(),
            referral_code: UI.inputs.referral.val() // <--- AGREGADO: Envia el código al PHP
        };

        // 3. Enviar
        const btn = UI.buttons.submit;
        const originalText = btn.html();
        setLoading(btn, true, '<i class="fas fa-spinner fa-spin"></i> Registrando...');

        $.ajax({
            type: 'POST',
            url: './api/registration_process.php',
            data: formData,
            dataType: 'json'
        })
        .done(function(data) {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Registro Exitoso!',
                    text: 'Te hemos enviado un correo de validación.',
                    background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444'
                }).then(() => {
                    window.location.href = 'index.php?page=validate';
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message, background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444' });
            }
        })
        .fail(function(xhr) {
            console.error(xhr.responseText); 
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión con el servidor.', background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444' });
        })
        .always(function() {
            setLoading(btn, false, originalText);
        });
    });

    // Utilidades
    function setLoading(btn, isLoading, html) {
        btn.prop('disabled', isLoading).html(html).css('opacity', isLoading ? 0.7 : 1);
    }
    function resetInputStyle(input) { input.css('border-color', '#333'); }
    $('input, select').on('input change', function() { 
        if($(this).val().length > 0) $(this).css('border-color', '#333'); 
    });

});
$(document).ready(function() {

    const urlParams = new URLSearchParams(window.location.search);
    const refCode = urlParams.get('ref');

    if (refCode) {
        $('#referral_code').val(refCode).css('border-color', '#22c55e');
    }

    let isEmailVerified = false;
    
    const UI = {
        form: $('#registrationForm'),
        inputs: {
            search_account: $('#search_account'),
            email: $('#email'),
            password: $('#password'),
            confirmPass: $('#confirm_password'),
            name: $('#name'),
            paternal: $('#paternal_surname'),
            maternal: $('#maternal_surname'), 
            telefono: $('#telefono'),
            dobMonth: $('#dob_month'),
            genero: $('#genero'),
            referral: $('#referral_code')
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

    UI.inputs.telefono.add(UI.inputs.referral).on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });

    const REGEX_EMAIL = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    let debounceTimer;
    let pendingOtpSearchTerm = null;
    let cachedOtpMessage = "";
    let cachedOtpEmail = "";

    UI.inputs.search_account.on('input', function() {
        clearTimeout(debounceTimer);
        isEmailVerified = false;
        pendingOtpSearchTerm = null;
        UI.buttons.verify.html('Continuar <i class="fas fa-arrow-right ml-2"></i>');
        debounceTimer = setTimeout(() => {
            if(UI.inputs.search_account.val().trim().length > 0) {
                checkEmailExistence(false);
            }
        }, 600);
    });

    UI.inputs.search_account.on('blur', function() {
        clearTimeout(debounceTimer);
        if(UI.inputs.search_account.val().trim().length > 0) {
            checkEmailExistence(false);
        }
    });

    function checkEmailExistence(lockAfterValid) {
        const searchVal = UI.inputs.search_account.val().trim();
        
        if (searchVal.length < 3) {
            showInlineFeedback("Por favor ingresa un dato válido (Mínimo 3 caracteres).", true);
            isEmailVerified = false;
            return;
        }

        if (lockAfterValid) {
            setLoading(UI.buttons.verify, true, '<i class="fas fa-spinner fa-spin"></i> Verificando...');
        }

        $.ajax({
            type: 'POST',
            url: './api/find_account.php',
            data: { search_term: searchVal, send_otp: lockAfterValid ? 1 : 0 },
            dataType: 'json'
        })
        .done(function(response) {
            if (response.exists) {
                if (response.needs_verification && lockAfterValid) {
                    pendingOtpSearchTerm = searchVal;
                    cachedOtpMessage = response.message;
                    cachedOtpEmail = response.email;
                    showOtpModal(response.message, response.email, searchVal);
                } else {
                    isEmailVerified = false;
                    showInlineFeedback(response.message || "El correo ya está en uso.", true);
                }
            } else {
                if (response.is_email) {
                    UI.inputs.email.val(searchVal);
                } else {
                    UI.inputs.email.val(''); // Clear it so they fill it in manually
                }
                isEmailVerified = true;
                showInlineFeedback(response.message || "", response.message ? true : false);
                if (lockAfterValid) {
                    lockEmailState(true);
                }
            }
        })
        .fail(function() {
             isEmailVerified = false;
             showInlineFeedback("Error de conexión. Intente de nuevo más tarde.", true);
        })
        .always(function() {
            if (lockAfterValid && pendingOtpSearchTerm !== searchVal) {
                setLoading(UI.buttons.verify, false, 'Continuar <i class="fas fa-arrow-right ml-2"></i>');
            } else if (lockAfterValid && pendingOtpSearchTerm === searchVal) {
                setLoading(UI.buttons.verify, false, 'Ingresar Código');
            }
        });
    }

    function showOtpModal(message, email, searchVal) {
        Swal.fire({
            title: 'Cuenta Encontrada',
            text: message,
            input: 'text',
            inputPlaceholder: 'Ingresa el código de 6 dígitos',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'Verificar',
            cancelButtonText: 'Cerrar',
            denyButtonText: 'Reenviar Código',
            background: '#1a1a1a', color: '#fff', 
            confirmButtonColor: '#22c55e', cancelButtonColor: '#666', denyButtonColor: '#ef4444',
            preConfirm: (otp) => {
                if (!otp) {
                    Swal.showValidationMessage('El código es obligatorio');
                }
                return otp;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('./api/verify_otp_registration.php', { otp: result.value, email: email }, function(res) {
                    if (res.success) {
                        isEmailVerified = true;
                        pendingOtpSearchTerm = null;
                        showInlineFeedback("Cuenta verificada con éxito.", false);
                        
                        UI.inputs.email.val(res.data.email);
                        UI.inputs.name.val(res.data.name);
                        UI.inputs.paternal.val(res.data.paternal_surname);
                        UI.inputs.maternal.val(res.data.maternal_surname);
                        UI.inputs.telefono.val(res.data.telefono);
                        UI.inputs.genero.val(res.data.genero);
                        UI.inputs.dobMonth.val(res.data.mes_nacimiento);
                        
                        lockEmailState(true);
                    } else {
                        Swal.fire('Error', res.message, 'error').then(() => {
                            showOtpModal(message, email, searchVal);
                        });
                        isEmailVerified = false;
                    }
                }, 'json');
            } else if (result.isDenied) {
                pendingOtpSearchTerm = null;
                UI.buttons.verify.html('Continuar <i class="fas fa-arrow-right ml-2"></i>');
                checkEmailExistence(true);
            } else {
                isEmailVerified = false;
                showInlineFeedback("Haz clic en 'Ingresar Código' para continuar.", true);
                UI.buttons.verify.html('Ingresar Código');
            }
        });
    }

    UI.buttons.verify.on('click', function() {
        const searchVal = UI.inputs.search_account.val().trim();
        
        if (searchVal.length < 3) {
            Swal.fire({
                icon: 'warning',
                title: 'Dato Inválido',
                text: 'Por favor ingresa un correo, teléfono o nombre válido.',
                background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444'
            });
            UI.inputs.search_account.css('border-color', '#ef4444');
            return;
        }
        
        resetInputStyle(UI.inputs.search_account); 
        
        if (isEmailVerified) {
            lockEmailState(true);
        } else if (pendingOtpSearchTerm !== null && searchVal === pendingOtpSearchTerm) {
            showOtpModal(cachedOtpMessage, cachedOtpEmail, searchVal);
        } else {
            checkEmailExistence(true);
        }
    });

    UI.buttons.changeEmail.on('click', function() {
        isEmailVerified = false;
        UI.inputs.search_account.prop('readonly', false).prop('disabled', false).focus().select();
        UI.buttons.changeEmail.fadeOut();
    });

    function lockEmailState(isLocked) {
        if (isLocked) {
            UI.inputs.search_account.prop('readonly', true);
            UI.containers.verify.slideUp(400);
            UI.containers.additional.hide().css('opacity', 1).slideDown(400);
            UI.buttons.changeEmail.fadeIn();
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

    // Filtro en tiempo real para inputs numéricos
    $('#telefono, #referral_code').on('input', function() {
        let val = $(this).val();
        val = val.replace(/[^0-9]/g, ''); // Remover letras y caracteres especiales
        if(val.length > 10) val = val.substring(0, 10);
        $(this).val(val);
    });

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

    UI.form.on('submit', function(event) {
        event.preventDefault();

        if (!isEmailVerified) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Por favor, verifica tu correo electrónico antes de continuar.', background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444' });
            return;
        }

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

        if (UI.inputs.telefono.val().length !== 10) {
            Swal.fire({ icon: 'warning', title: 'Teléfono Inválido', text: 'El teléfono celular debe tener exactamente 10 dígitos numéricos.', background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444' });
            return;
        }

        if (UI.inputs.referral.val().length > 0 && UI.inputs.referral.val().length !== 10) {
            Swal.fire({ icon: 'warning', title: 'Código Inválido', text: 'El código de referido debe tener exactamente 10 dígitos numéricos.', background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444' });
            return;
        }

        if (UI.inputs.password.val() !== UI.inputs.confirmPass.val()) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Las contraseñas no coinciden.', background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444' });
            return;
        }

        const formData = {
            name: UI.inputs.name.val(),
            paternal_surname: UI.inputs.paternal.val(),
            maternal_surname: UI.inputs.maternal.val(),
            telefono: UI.inputs.telefono.val(),
            email: UI.inputs.email.val(),
            password: UI.inputs.password.val(),
            genero: UI.inputs.genero.val(),
            mes_nacimiento: UI.inputs.dobMonth.val(),
            referral_code: UI.inputs.referral.val()
        };

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
            let errorMsg = 'Error de conexión con el servidor.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            Swal.fire({ icon: 'error', title: 'Error', text: errorMsg, background: '#1a1a1a', color: '#fff', confirmButtonColor: '#ef4444' });
        })
        .always(function() {
            setLoading(btn, false, originalText);
        });
    });

    function setLoading(btn, isLoading, html) {
        btn.prop('disabled', isLoading).html(html).css('opacity', isLoading ? 0.7 : 1);
    }
    function resetInputStyle(input) { input.css('border-color', '#333'); }
    $('input, select').on('input change', function() { 
        if($(this).val().length > 0) $(this).css('border-color', '#333'); 
    });

});

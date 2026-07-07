$(document).ready(function() {
    var debounceTimer; 
    
    // Recuperar token CSRF (Asume la existencia del input oculto en el HTML)
    var csrfToken = $('#csrf_token').val() || '';

    // Configuración global de AJAX para inyectar credenciales y header de seguridad
    $.ajaxSetup({
        xhrFields: {
            withCredentials: true // Obligatorio en producción para no perder la cookie PHPSESSID
        },
        headers: {
            'X-CSRF-Token': csrfToken
        }
    });

    // --- Función para actualizar el resumen de pago (PREVIEW) ---
    function actualizarPreviewPago() {
        var servicioSeleccionado = $('#servicio').val();
        var codigoPromocion = $('#codigo_promocion').val();
        var fechaInicio = $('#fecha_inicio').val();
        var fechaFin = $('#fecha_fin').val();
        var $resumenBox = $('#resumenPago');
        var $filaDescuento = $('#filaDescuento');

        if (!servicioSeleccionado || !fechaInicio || !fechaFin) {
            return;
        }

        $('#previewTotal').css('opacity', '0.5');

        $.ajax({
            type: "POST",
            url: "./api/procesar_pago.php", 
            data: {
                servicio: servicioSeleccionado,
                codigo_promocion: codigoPromocion,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin,
                accion: "preview",
                csrf_token: csrfToken // Redundancia directa en el payload
            },
            dataType: "json",
            success: function(response) {
                $('#previewTotal').css('opacity', '1');

                if (response.status === 'success') {
                    $('#previewSubtotal').text('$' + parseFloat(response.subtotal).toFixed(2));
                    $('#previewTotal').text('$' + parseFloat(response.total).toFixed(2));

                    if (parseFloat(response.descuento_monto) > 0) {
                        $('#previewDescuentoNombre').text(response.descuento_nombre);
                        $('#previewDescuentoMonto').text('-$' + parseFloat(response.descuento_monto).toFixed(2));
                        $filaDescuento.addClass('d-flex').show(); 
                    } else {
                        $filaDescuento.removeClass('d-flex').hide();
                    }
                    
                    $resumenBox.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de cálculo',
                        text: response.message,
                        background: '#121212',
                        color: '#ffffff',
                        confirmButtonColor: '#ef4444'
                    });
                }
            },
            error: function(xhr) {
                $('#previewTotal').css('opacity', '1');
                console.error("Error en preview: " + xhr.responseText);
            }
        });
    }

    // --- Función para validar y controlar la sección del cupón ---
    function validarCupon(immediate) {
        var servicioSeleccionado = $('#servicio').val();
        var $cuponContainer = $('#codigo_promocion').closest('.form-group');
        var $cuponInput = $('#codigo_promocion');
        var $cuponBtn = $('#aplicarCuponBtn');

        if (servicioSeleccionado === '1-1') {
            if (immediate) {
                $cuponContainer.show();
            } else {
                $cuponContainer.slideDown();
            }
            $cuponInput.prop('disabled', false);
            $cuponBtn.prop('disabled', false);
        } else {
            if (immediate) {
                $cuponContainer.hide();
            } else {
                $cuponContainer.slideUp();
            }
            $cuponInput.prop('disabled', true);
            $cuponBtn.prop('disabled', true);
        }
    }

    // --- Evento: Cambiar el servicio (Cálculo de Fechas) ---
    $('#servicio').on('change', function() {
        validarCupon(false);
        var servicioSeleccionado = $(this).val();
        var $dateFieldsContainer = $('#dateFieldsContainer');
        var $fechaInicioInput = $('#fecha_inicio');
        var $fechaFinInput = $('#fecha_fin');

        if (!servicioSeleccionado) {
            $dateFieldsContainer.hide();
            return;
        }

        $.ajax({
            type: "POST",
            url: "./api/calcular_fecha_fin.php",
            data: { 
                servicio: servicioSeleccionado,
                csrf_token: csrfToken // Inyección del token por seguridad en este endpoint
            }, 
            dataType: "json",
            success: function(response) {
                if (response.status === 'success') {
                    $fechaInicioInput.val(response.fecha_inicio);
                    $fechaFinInput.val(response.fecha_fin); 
                    $dateFieldsContainer.slideDown(); 
                    actualizarPreviewPago(); 
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de fechas',
                        text: response.message || 'Error al calcular fechas.',
                        background: '#121212',
                        color: '#ffffff',
                        confirmButtonColor: '#ef4444'
                    });
                    $dateFieldsContainer.hide();
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'Error al comunicarse con el servidor para calcular vigencia.',
                    background: '#121212',
                    color: '#ffffff',
                    confirmButtonColor: '#ef4444'
                });
            }
        });
    });

    // --- Evento: Botón Aplicar Cupón ---
    $('#aplicarCuponBtn').on('click', function() {
        actualizarPreviewPago();
    });

    // --- Evento: Escribir en Código (Debounce) ---
    $('#codigo_promocion').on('keyup', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            actualizarPreviewPago();
        }, 800); 
    });

    // --- Evento: Pagar ---
    $('#pagoMembresiaForm').on('submit', function(e) {
        e.preventDefault();

        var $btn = $('#realizarPagoBtn');
        var $loader = $('#loader');

        if (!$('#servicio').val()) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor selecciona un servicio.',
                background: '#121212',
                color: '#ffffff',
                confirmButtonColor: '#ef4444'
            });
            return;
        }

        $btn.prop('disabled', true);
        $loader.show();

        // Serializar form y anexar variables adicionales
        var formData = $(this).serialize();
        formData += '&accion=pagar'; 
        
        // Garantizar que el token viaja en el body si el form no lo captura
        if(formData.indexOf('csrf_token') === -1) {
            formData += '&csrf_token=' + encodeURIComponent(csrfToken);
        }

        $.ajax({
            type: "POST",
            url: "./api/procesar_pago.php",
            data: formData,
            dataType: "json",
            success: function(response) {
                if (response.status === 'success' && response.url) {
                    window.location.href = response.url;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de transacción',
                        text: response.message || 'Error desconocido.',
                        background: '#121212',
                        color: '#ffffff',
                        confirmButtonColor: '#ef4444'
                    });
                    $btn.prop('disabled', false);
                    $loader.hide();
                }
            },
            error: function(xhr) {
                var errorText = "Error al procesar la solicitud.";
                try {
                    var jsonError = JSON.parse(xhr.responseText);
                    if (jsonError.message) errorText = jsonError.message;
                } catch(e) {}
                
                Swal.fire({
                    icon: 'error',
                    title: 'Transacción Abortada',
                    text: errorText,
                    background: '#121212',
                    color: '#ffffff',
                    confirmButtonColor: '#ef4444'
                });
                
                $btn.prop('disabled', false);
                $loader.hide();
            }
        });
    });

    // Validar estado inicial al cargar la página
    validarCupon(true);
});
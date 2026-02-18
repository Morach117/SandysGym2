$(document).ready(function() {
    var debounceTimer; 

    // --- Función para actualizar el resumen de pago (PREVIEW) ---
    function actualizarPreviewPago() {
        var servicioSeleccionado = $('#servicio').val();
        var codigoPromocion = $('#codigo_promocion').val();
        
        // IMPORTANTE: Leer las fechas directamente de los inputs
        var fechaInicio = $('#fecha_inicio').val();
        var fechaFin = $('#fecha_fin').val();

        var $resumenBox = $('#resumenPago');
        var $filaDescuento = $('#filaDescuento');
        var $errorMsg = $('#mensajeError');

        // Validar que tengamos lo mínimo necesario antes de llamar al backend
        if (!servicioSeleccionado || !fechaInicio || !fechaFin) {
            return;
        }

        // Mostrar indicador de carga sutil en el total
        $('#previewTotal').css('opacity', '0.5');

        $.ajax({
            type: "POST",
            url: "./api/procesar_pago.php", 
            data: {
                servicio: servicioSeleccionado,
                codigo_promocion: codigoPromocion,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin,
                accion: "preview" 
            },
            dataType: "json",
            success: function(response) {
                $('#previewTotal').css('opacity', '1');

                if (response.status === 'success') {
                    // Llenar datos numéricos
                    $('#previewSubtotal').text('$' + parseFloat(response.subtotal).toFixed(2));
                    $('#previewTotal').text('$' + parseFloat(response.total).toFixed(2));

                    // Lógica de descuento
                    if (parseFloat(response.descuento_monto) > 0) {
                        $('#previewDescuentoNombre').text(response.descuento_nombre);
                        $('#previewDescuentoMonto').text('-$' + parseFloat(response.descuento_monto).toFixed(2));
                        $filaDescuento.addClass('d-flex').show(); 
                    } else {
                        $filaDescuento.removeClass('d-flex').hide();
                    }
                    
                    $resumenBox.show();
                    $errorMsg.hide();
                } else {
                    $errorMsg.text(response.message).show();
                }
            },
            error: function(xhr) {
                $('#previewTotal').css('opacity', '1');
                console.error("Error en preview: " + xhr.responseText);
            }
        });
    }

    // --- Evento: Cambiar el servicio (Cálculo de Fechas) ---
    $('#servicio').on('change', function() {
        var servicioSeleccionado = $(this).val();
        var $dateFieldsContainer = $('#dateFieldsContainer');
        var $fechaInicioInput = $('#fecha_inicio');
        var $fechaFinInput = $('#fecha_fin');
        var $errorMsg = $('#mensajeError');

        if (!servicioSeleccionado) {
            $dateFieldsContainer.hide();
            return;
        }

        // Calcular fechas
        $.ajax({
            type: "POST",
            url: "./api/calcular_fecha_fin.php",
            data: { servicio: servicioSeleccionado }, 
            dataType: "json",
            success: function(response) {
                // Verificar status
                if (response.status === 'success') {
                    
                    // --- CORRECCIÓN AQUÍ ---
                    // Asignamos las PROPIEDADES ESPECÍFICAS del objeto JSON
                    $fechaInicioInput.val(response.fecha_inicio);
                    $fechaFinInput.val(response.fecha_fin); 
                    // -----------------------
                    
                    // Mostrar contenedor con animación suave
                    $dateFieldsContainer.slideDown(); 
                    $errorMsg.hide();

                    // Ahora que los inputs tienen valor, llamamos al preview
                    actualizarPreviewPago(); 
                } else {
                    $errorMsg.text(response.message || 'Error al calcular fechas.').show();
                    $dateFieldsContainer.hide();
                }
            },
            error: function() {
                $errorMsg.text('Error de conexión al calcular vigencia.').show();
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
        var $errorMsg = $('#mensajeError');

        if (!$('#servicio').val()) {
            $errorMsg.text("Por favor selecciona un servicio.").show();
            return;
        }

        $btn.prop('disabled', true);
        $loader.show();
        $errorMsg.hide();

        var formData = $(this).serialize();
        formData += '&accion=pagar'; 

        $.ajax({
            type: "POST",
            url: "./api/procesar_pago.php",
            data: formData,
            dataType: "json",
            success: function(response) {
                if (response.status === 'success' && response.url) {
                    window.location.href = response.url;
                } else {
                    $errorMsg.text(response.message || 'Error desconocido.').show();
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
                $errorMsg.text(errorText).show();
                $btn.prop('disabled', false);
                $loader.hide();
            }
        });
    });
});
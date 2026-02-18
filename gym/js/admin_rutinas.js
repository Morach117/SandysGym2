// assets/js/admin_rutinas.js
$(document).ready(function () {
    // Inicializar DataTables
    var table = $('#tabla_rutinas_admin').DataTable({
        "language": {
            "sProcessing": "Procesando...", "sLengthMenu": "Mostrar _MENU_ registros", "sZeroRecords": "No se encontraron resultados", "sEmptyTable": "Ningún dato disponible en esta tabla", "sInfo": "Mostrando _START_ al _END_ de _TOTAL_ registros", "sInfoEmpty": "Mostrando 0 al 0 de 0 registros", "sInfoFiltered": "(filtrado de _MAX_ registros)", "sSearch": "Buscar:", "oPaginate": { "sFirst": "Primero", "sLast": "Último", "sNext": "Siguiente", "sPrevious": "Anterior" }, "oAria": { "sSortAscending": ": Ordenar ascendente", "sSortDescending": ": Ordenar descendente" }
        },
        "order": [[1, "asc"], [2, "asc"], [3, "asc"], [5, "asc"]], // Ordenar por Nivel, Género, Grupo, Orden
        "columnDefs": [{ "targets": [9], "orderable": false, "searchable": false }] // No ordenar/buscar en Acciones
    });

    var $editingRow = null;

    // --- Lógica Modal ---

    // Abrir modal para agregar
    $('#btnAgregarAsignacion').on('click', function () {
        $('#formAsignacion')[0].reset();
        $('#rutina_ejercicio_id').val(''); // Borrar ID oculto
        $('#modalAsignacionLabel').text('Agregar Asignación de Ejercicio');
        $editingRow = null; // Asegurarse que no esté en modo edición
        $('#modalAsignacion').css('display', 'block');
    });

    // Abrir modal para editar
    table.on('click', '.btn-editar-asignacion', function () {
        var data = $(this).data(); // Obtiene todos los data-*
        $editingRow = $(this).closest('tr'); // Guardar la fila que se está editando

        $('#rutina_ejercicio_id').val(data.id);
        $('#id_nivel').val(data.nivel);
        $('#genero').val(data.genero);
        $('#id_grupo_muscular').val(data.grupo);
        $('#id_ejercicio').val(data.ejercicio);
        $('#orden_ejercicio').val(data.orden);
        $('#series').val(data.series);
        $('#repeticiones').val(data.reps);
        $('#descanso_seg').val(data.descanso);

        $('#modalAsignacionLabel').text('Editar Asignación');
        $('#modalAsignacion').css('display', 'block');
    });

    // --- LÓGICA AJAX ---

    // Botón Guardar (AHORA CON AJAX)
    $('#btnGuardarAsignacion').on('click', function () {
        var $button = $(this);
        $button.text('Guardando...').prop('disabled', true); // Deshabilitar botón

        var formData = $('#formAsignacion').serialize();
        var rutinaId = $('#rutina_ejercicio_id').val();

        // Añadir la acción al FormData
        formData += "&action=" + (rutinaId ? 'guardar_asignacion' : 'guardar_asignacion');

        $.ajax({
            url: 'funciones/rutinas_handler.php', // *** ¡RUTA AL NUEVO API! ***
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#modalAsignacion').css('display', 'none');
                    Swal.fire({
                        title: '¡Éxito!',
                        text: response.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload(); // Recargar la página para ver cambios
                    });
                } else {
                    Swal.fire('Error al Guardar', 'No se pudo guardar: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("Respuesta cruda del servidor:", jqXHR.responseText); // Muestra el HTML/error
                var errorMsg = 'No se pudo comunicar con el servidor.';
                if (textStatus === 'parsererror') {
                    errorMsg = 'Error al procesar la respuesta (no es JSON). Revisa la consola para ver el error de PHP.';
                } else if (textStatus === 'error') {
                    errorMsg = `Error del servidor: ${errorThrown}`;
                }
                Swal.fire('Error de Red', errorMsg, 'error');
            },
            complete: function () {
                $button.text('Guardar Cambios').prop('disabled', false); // Rehabilitar botón
            }
        });
    });

    // Botón Eliminar (AHORA CON AJAX)
    table.on('click', '.btn-eliminar-asignacion', function () {
        var data = $(this).data();
        var row = $(this).closest('tr');
        var idParaEliminar = data.id;

        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Eliminar esta asignación?\n' + data.descripcion,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, ¡eliminar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'funciones/rutinas_handler.php', // *** ¡RUTA AL NUEVO API! ***
                    type: 'POST',
                    data: {
                        action: 'eliminar_asignacion',
                        rutina_ejercicio_id: idParaEliminar
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            table.row(row).remove().draw(false); // Eliminar la fila de la tabla
                            Swal.fire(
                                '¡Eliminado!',
                                'La asignación ha sido eliminada.',
                                'success'
                            );
                        } else {
                            Swal.fire('Error', 'No se pudo eliminar: ' + response.message, 'error');
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error("Respuesta cruda del servidor:", jqXHR.responseText);
                        var errorMsg = 'No se pudo comunicar con el servidor.';
                        if (textStatus === 'parsererror') {
                            errorMsg = 'Error al procesar la respuesta (no es JSON). Revisa la consola.';
                        }
                        Swal.fire('Error de Red', errorMsg, 'error');
                    }
                });
            }
        });
    });

    // Cerrar modal
    $('.modal-footer .btn-secondary, .close-button').on('click', function () {
        $('#modalAsignacion').css('display', 'none');
    });
    $('.modal-overlay').on('click', function (event) {
        if ($(event.target).is('.modal-overlay')) {
            $(this).css('display', 'none');
        }
    });

});
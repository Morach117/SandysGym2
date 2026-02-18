// assets/js/admin_videos.js
$(document).ready(function () {
    // Inicializar DataTables
    var table = $('#tabla_videos_admin').DataTable({
        "language": {
            "sProcessing": "Procesando...", "sLengthMenu": "Mostrar _MENU_ registros", "sZeroRecords": "No se encontraron resultados", "sEmptyTable": "Ningún dato disponible en esta tabla", "sInfo": "Mostrando _START_ al _END_ de _TOTAL_ registros", "sInfoEmpty": "Mostrando 0 al 0 de 0 registros", "sInfoFiltered": "(filtrado de _MAX_ registros)", "sSearch": "Buscar:", "oPaginate": { "sFirst": "Primero", "sLast": "Último", "sNext": "Siguiente", "sPrevious": "Anterior" }, "oAria": { "sSortAscending": ": Ordenar ascendente", "sSortDescending": ": Ordenar descendente" }
        },
        "order": [
            [1, "asc"] // Ordenar por nombre
        ],
        "columnDefs": [
            { "targets": [3], "orderable": false, "searchable": false } // No ordenar/buscar en Acciones
        ]
    });

    // --- Lógica Modal ---

    // Abrir modal para agregar
    $('#btnAgregarNuevoVideo').on('click', function () {
        $('#formVideo')[0].reset();
        $('#ejercicio_id').val('');
        $('#modalVideoLabel').text('Agregar Nuevo Video');
        $('#current_video_info, #current_poster_info, #video_preview_container').hide();
        $('#video_preview_container video source').attr('src', '');
        $('#modalVideo').css('display', 'block');
    });

    // Abrir modal para editar (delegación)
    table.on('click', '.btn-editar-video', function () {
        var data = $(this).data();
        $('#formVideo')[0].reset();

        // Llenar campos
        $('#ejercicio_id').val(data.id);
        $('#nombre_ejercicio').val(data.nombre);
        $('#descripcion').val(data.descripcion);
        $('#recomendaciones').val(data.recomendaciones);

        // --- CORRECCIÓN AQUÍ: Definir las rutas base ---
        // Esta ruta es RELATIVA a la página admin_videos.php
        // Ajusta si tu página de admin está en una ubicación diferente
        const videoBasePath = './../sandys_web/assets/videos/';
        const posterBasePath = './../sandys_web/assets/img/posters/';

        // Mostrar archivo actual (video)
        if (data.video) {
            let videoFilename = data.video; // data.video es solo el nombre (ej: video_123.mp4)
            let fullVideoPath = videoBasePath + videoFilename; // Construir la ruta completa

            $('#current_video_info span').html('<a href="' + fullVideoPath + '" target="_blank">' + videoFilename + '</a>');
            $('#current_video_info').show();

            // Cargar y mostrar vista previa usando la ruta completa
            $('#video_preview_container video source').attr('src', fullVideoPath);
            $('#video_preview_container video')[0].load(); // Recargar el video
            $('#video_preview_container').show();
        } else {
            $('#current_video_info span').text('Ninguno');
            $('#current_video_info').show();
            $('#video_preview_container').hide(); // Ocultar si no hay video
            $('#video_preview_container video source').attr('src', '');
        }

        // Mostrar archivo actual (poster)
        if (data.poster) {
            let posterFilename = data.poster;
            let fullPosterPath = posterBasePath + posterFilename; // Construir la ruta completa

            $('#current_poster_info span').html('<a href="' + fullPosterPath + '" target="_blank">' + posterFilename + '</a>');
            $('#current_poster_info').show();
        } else {
            $('#current_poster_info span').text('Ninguno');
            $('#current_poster_info').show();
        }
        // --- FIN DE LA CORRECCIÓN ---

        $('#modalVideoLabel').text('Editar Video: ' + data.nombre);
        $('#modalVideo').css('display', 'block');
    });

    // --- LÓGICA AJAX ---

    // Botón Guardar (con FormData para subida de archivos)
    $('#btnGuardarVideo').on('click', function () {
        var $button = $(this);
        $button.text('Guardando...').prop('disabled', true);

        var formData = new FormData($('#formVideo')[0]);
        var ejercicioId = $('#ejercicio_id').val();

        formData.append('action', 'guardar_video'); // Añadir la acción

        $.ajax({
            url: './funciones/videos_handler.php', // Apuntar al API PHP
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#modalVideo').css('display', 'none');
                    Swal.fire({
                        title: '¡Éxito!',
                        text: response.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload(); // Recargar página para ver cambios
                    });
                } else {
                    Swal.fire('Error al Guardar', 'No se pudo guardar: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("Respuesta cruda del servidor:", jqXHR.responseText);
                var errorMsg = 'No se pudo comunicar con el servidor.';
                if (textStatus === 'parsererror') {
                    errorMsg = 'Error al procesar la respuesta (no es JSON). Revisa la consola.';
                }
                Swal.fire('Error de Red', errorMsg, 'error');
            },
            complete: function () {
                $button.text('Guardar Cambios').prop('disabled', false); // Rehabilitar
            }
        });
    });

    // Botón Eliminar
    table.on('click', '.btn-eliminar-video', function () {
        var data = $(this).data();
        var row = $(this).closest('tr');
        var idParaEliminar = data.id;

        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Eliminar el ejercicio "' + data.nombre + '"? ¡Esto también borrará el archivo de video y poster asociados!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, ¡eliminar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'funciones/videos_handler.php', // Apuntar al API PHP
                    type: 'POST',
                    data: {
                        action: 'eliminar_video',
                        id_ejercicio: idParaEliminar
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            table.row(row).remove().draw(false); // Eliminar fila visualmente
                            Swal.fire('¡Eliminado!', response.message, 'success');
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

    // Cerrar modal (Sin cambios)
    $('.modal-footer .btn-secondary, .close-button').on('click', function () {
        $('#modalVideo').css('display', 'none');
    });
    $('.modal-overlay').on('click', function (event) {
        if ($(event.target).is('.modal-overlay')) {
            $(this).css('display', 'none');
        }
    });

});
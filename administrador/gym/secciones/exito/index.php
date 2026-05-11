<?php
global $conexion;

// 1. OBTENCIÓN DE CASOS DE ÉXITO
function obtener_casos_exito() {
    global $conexion; 
    
    $query = "SELECT id_historia, cliente_nombre, foto_antes, foto_despues, video_url, testimonio, estado, fecha_registro 
              FROM san_historias 
              ORDER BY fecha_registro DESC";
              
    $resultado = mysqli_query($conexion, $query);
    $datos = "";
    $i = 1;

    if ($resultado) {
        if (mysqli_num_rows($resultado) > 0) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $fecha = date('d/m/Y', strtotime($fila['fecha_registro']));
                
                $estado_badge = ($fila['estado'] == 1) 
                    ? "<span class='label label-success'>Activo</span>" 
                    : "<span class='label label-danger'>Oculto</span>";
                
                // Rutas relativas a los archivos
                $ruta_base = "./../../imagenes/exito/";
                $url_a = $ruta_base . htmlspecialchars($fila['foto_antes']);
                $url_d = $ruta_base . htmlspecialchars($fila['foto_despues']);
                $url_v = !empty($fila['video_url']) ? $ruta_base . htmlspecialchars($fila['video_url']) : "";

                $cliente_js = htmlspecialchars($fila['cliente_nombre'], ENT_QUOTES);
                $testimonio_js = htmlspecialchars($fila['testimonio'], ENT_QUOTES);

                $btn_ver = "<button class='btn btn-xs btn-info' onclick='abrirPreview(\"{$url_a}\", \"{$url_d}\", \"{$url_v}\")' title='Vista Previa'><span class='glyphicon glyphicon-eye-open'></span></button>";
                $btn_editar = "<button class='btn btn-xs btn-primary' onclick='abrirModalEditar({$fila['id_historia']}, \"{$cliente_js}\", \"{$testimonio_js}\")' title='Editar'><span class='glyphicon glyphicon-pencil'></span></button>";
                $btn_estado = "<button class='btn btn-xs btn-warning' id='btn_estado_{$fila['id_historia']}' onclick='cambiarEstado({$fila['id_historia']}, {$fila['estado']})' title='Cambiar Estado'><span class='glyphicon glyphicon-refresh'></span></button>";
                $btn_eliminar = "<button class='btn btn-xs btn-danger' onclick='eliminarCaso({$fila['id_historia']})' title='Eliminar'><span class='glyphicon glyphicon-trash'></span></button>";

                $video_html = !empty($fila['video_url']) ? "<span class='label label-info'><span class='glyphicon glyphicon-play-circle'></span> Video</span>" : "<span class='text-muted'>N/A</span>";

                $datos .= "<tr id='fila_exito_{$fila['id_historia']}'>
                            <td>{$i}</td>
                            <td><strong>" . htmlspecialchars($fila['cliente_nombre']) . "</strong></td>
                            <td>
                                <img src='{$url_a}' style='width:50px; height:50px; object-fit:cover;' class='img-thumbnail'>
                                <span class='glyphicon glyphicon-arrow-right text-muted'></span>
                                <img src='{$url_d}' style='width:50px; height:50px; object-fit:cover;' class='img-thumbnail'>
                            </td>
                            <td>{$video_html}</td>
                            <td>{$fecha}</td>
                            <td id='td_estado_{$fila['id_historia']}'>{$estado_badge}</td>
                            <td>
                                <div class='btn-group'>
                                    {$btn_ver}
                                    {$btn_editar}
                                    {$btn_estado}
                                    {$btn_eliminar}
                                </div>
                            </td>
                           </tr>";
                $i++;
            }
        } else {
            $datos = "<tr id='fila_vacia'><td colspan='7' class='text-center'>No hay casos de éxito registrados.</td></tr>";
        }
        mysqli_free_result($resultado);
        return $datos;
    } else {
        return "<tr><td colspan='7' class='text-center text-danger'>Error DB: " . mysqli_error($conexion) . "</td></tr>";
    }
}
$var_lista_exito = obtener_casos_exito();
?>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap.min.css">
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap.min.js"></script>

<div class="row">
    <div class="col-md-12">
        <h4 class="text-info" style="display: flex; justify-content: space-between; align-items: center;">
            <span><span class="glyphicon glyphicon-star"></span> Gestión de Casos de Éxito</span>
            <button class="btn btn-sm btn-primary" onclick="abrirModalExito()"><span
                    class="glyphicon glyphicon-plus"></span> Nuevo Caso</button>
        </h4>
        <hr />
        <div class="table-responsive">
            <table id="tabla_exito" class="table table-hover table-condensed table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Evidencia (A -> D)</th>
                        <th>Video</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $var_lista_exito; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExito" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                    style="color:#fff; opacity:1;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" style="color:#fff;"><span class="glyphicon glyphicon-upload"></span> Subir Caso
                    de Éxito</h4>
            </div>
            <form id="formExito" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Nombre del Cliente:</label>
                                <input type="text" name="cliente_nombre" id="cliente_nombre" class="form-control"
                                    required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Foto ANTES (Requerido):</label>
                                <input type="file" name="foto_antes" id="foto_antes" class="form-control"
                                    accept="image/*" required onchange="previewMedia(this, 'preview_antes')">
                                <div class="text-center" style="margin-top:10px;">
                                    <img id="preview_antes" src=""
                                        style="max-width:100%; height:150px; display:none; object-fit:cover; border:1px solid #ddd; border-radius:4px;">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Foto DESPUÉS (Requerido):</label>
                                <input type="file" name="foto_despues" id="foto_despues" class="form-control"
                                    accept="image/*" required onchange="previewMedia(this, 'preview_despues')">
                                <div class="text-center" style="margin-top:10px;">
                                    <img id="preview_despues" src=""
                                        style="max-width:100%; height:150px; display:none; object-fit:cover; border:1px solid #ddd; border-radius:4px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Video del Proceso (Opcional, Max 20MB):</label>
                                <input type="file" name="video_archivo" id="video_archivo" class="form-control"
                                    accept="video/mp4,video/webm" onchange="previewVideo(this, 'preview_video')">
                                <div class="text-center" style="margin-top:10px;">
                                    <video id="preview_video" controls
                                        style="max-width:100%; height:150px; display:none; background:#000; border-radius:4px;"></video>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Testimonio Escrito (Opcional):</label>
                                <textarea name="testimonio" id="testimonio" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarExito">Guardar Caso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarExito" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                    style="color:#fff; opacity:1;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" style="color:#fff;"><span class="glyphicon glyphicon-pencil"></span> Editar Caso
                    de Éxito</h4>
            </div>
            <form id="formEditarExito" enctype="multipart/form-data">
                <input type="hidden" name="id_historia_edit" id="id_historia_edit">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <span class="glyphicon glyphicon-info-sign"></span> <strong>Nota:</strong> Solo sube archivos si
                        deseas reemplazar los actuales.
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Nombre del Cliente:</label>
                                <input type="text" name="cliente_nombre_edit" id="cliente_nombre_edit"
                                    class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reemplazar Foto ANTES:</label>
                                <input type="file" name="foto_antes_edit" id="foto_antes_edit" class="form-control"
                                    accept="image/*" onchange="previewMedia(this, 'preview_antes_edit')">
                                <div class="text-center" style="margin-top:10px;">
                                    <img id="preview_antes_edit" src=""
                                        style="max-width:100%; height:150px; display:none; object-fit:cover; border:1px solid #ddd; border-radius:4px;">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reemplazar Foto DESPUÉS:</label>
                                <input type="file" name="foto_despues_edit" id="foto_despues_edit" class="form-control"
                                    accept="image/*" onchange="previewMedia(this, 'preview_despues_edit')">
                                <div class="text-center" style="margin-top:10px;">
                                    <img id="preview_despues_edit" src=""
                                        style="max-width:100%; height:150px; display:none; object-fit:cover; border:1px solid #ddd; border-radius:4px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Reemplazar Video (Max 20MB):</label>
                                <input type="file" name="video_archivo_edit" id="video_archivo_edit"
                                    class="form-control" accept="video/mp4,video/webm"
                                    onchange="previewVideo(this, 'preview_video_edit')">
                                <div class="text-center" style="margin-top:10px;">
                                    <video id="preview_video_edit" controls
                                        style="max-width:100%; height:150px; display:none; background:#000; border-radius:4px;"></video>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Testimonio Escrito:</label>
                                <textarea name="testimonio_edit" id="testimonio_edit" class="form-control"
                                    rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarEdicion">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPreview" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span class="glyphicon glyphicon-eye-open"></span> Vista Previa de Evidencia
                </h4>
            </div>
            <div class="modal-body">
                <div class="row text-center">
                    <div class="col-md-6">
                        <h5 class="text-info"><strong>ANTES</strong></h5>
                        <img id="vista_img_a" src="" class="img-responsive img-thumbnail"
                            style="margin: 0 auto; max-height: 300px;">
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-success"><strong>DESPUÉS</strong></h5>
                        <img id="vista_img_d" src="" class="img-responsive img-thumbnail"
                            style="margin: 0 auto; max-height: 300px;">
                    </div>
                </div>
                <hr id="vista_hr_video" style="display:none;">
                <div class="row text-center" id="vista_contenedor_video" style="display:none;">
                    <div class="col-md-12">
                        <h5><strong>VIDEO DEL PROCESO</strong></h5>
                        <video id="vista_vid" controls
                            style="max-width:100%; max-height:400px; background:#000; border-radius:4px; margin: 0 auto;"></video>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
let tablaExitoDT;

$(document).ready(function() {
    try {
        if (typeof $.fn.DataTable !== 'undefined' && !$.fn.DataTable.isDataTable('#tabla_exito')) {
            tablaExitoDT = $('#tabla_exito').DataTable({
                "order": [],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                },
                "columnDefs": [{
                    "orderable": false,
                    "targets": [2, 6]
                }]
            });
        }
    } catch (error) {
        console.error("DataTables Warning: ", error);
    }

    $('#modalPreview').on('hidden.bs.modal', function() {
        let vid = document.getElementById("vista_vid");
        if (vid) {
            vid.pause();
            vid.currentTime = 0;
        }
    });
});

function abrirPreview(url_a, url_d, url_v) {
    $('#vista_img_a').attr('src', url_a);
    $('#vista_img_d').attr('src', url_d);

    if (url_v && url_v !== "") {
        $('#vista_vid').attr('src', url_v);
        $('#vista_contenedor_video').show();
        $('#vista_hr_video').show();
    } else {
        $('#vista_vid').attr('src', '');
        $('#vista_contenedor_video').hide();
        $('#vista_hr_video').hide();
    }
    $('#modalPreview').modal('show');
}

function abrirModalExito() {
    $('#formExito')[0].reset();
    $('#preview_antes, #preview_despues, #preview_video').hide().attr('src', '');
    $('#modalExito').modal('show');
}

function abrirModalEditar(id, cliente, testimonio) {
    $('#formEditarExito')[0].reset();
    $('#preview_antes_edit, #preview_despues_edit, #preview_video_edit').hide().attr('src', '');

    $('#id_historia_edit').val(id);
    $('#cliente_nombre_edit').val(cliente);
    $('#testimonio_edit').val(testimonio);

    $('#modalEditarExito').modal('show');
}

function previewMedia(input, imgId) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            $('#' + imgId).attr('src', e.target.result).show();
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewVideo(input, videoId) {
    if (input.files && input.files[0]) {
        let file = input.files[0];
        if (file.size > 20971520) {
            alert("El video supera los 20MB permitidos.");
            input.value = "";
            $('#' + videoId).hide().attr('src', '');
            return;
        }
        let url = URL.createObjectURL(file);
        $('#' + videoId).attr('src', url).show();
    }
}

$('#formExito').on('submit', function(e) {
    e.preventDefault();
    const btn = $('#btnGuardarExito');
    btn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh"></i> Subiendo Archivos...');

    let formData = new FormData(this);
    formData.append('accion', 'nuevo_caso');

    $.ajax({
        url: 'secciones/exito/acciones_exito.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(r) {
            if (r.exito) {
                if (tablaExitoDT) {
                    let nodoHTML = $(r.html);
                    tablaExitoDT.row.add(nodoHTML).draw(false);
                } else {
                    $('#fila_vacia').remove();
                    $('#tabla_exito tbody').prepend(r.html);
                }
                $('#modalExito').modal('hide');
                alert(r.mensaje);
            } else {
                alert(r.mensaje);
            }
        },
        error: () => alert("Error de conexión al servidor."),
        complete: () => btn.prop('disabled', false).html('Guardar Caso')
    });
});

$('#formEditarExito').on('submit', function(e) {
    e.preventDefault();
    const btn = $('#btnGuardarEdicion');
    btn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh"></i> Actualizando...');

    let formData = new FormData(this);
    formData.append('accion', 'editar_caso');

    $.ajax({
        url: 'secciones/exito/acciones_exito.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(r) {
            if (r.exito) {
                if (tablaExitoDT) {
                    tablaExitoDT.row($('#fila_exito_' + r.id)).remove().draw(false);
                    let nodoHTML = $(r.html);
                    tablaExitoDT.row.add(nodoHTML).draw(false);
                } else {
                    $('#fila_exito_' + r.id).replaceWith(r.html);
                }
                $('#modalEditarExito').modal('hide');
                alert(r.mensaje);
            } else {
                alert(r.mensaje);
            }
        },
        error: () => alert("Error de conexión al servidor."),
        complete: () => btn.prop('disabled', false).html('Guardar Cambios')
    });
});

function eliminarCaso(id) {
    if (confirm('¿Eliminar permanentemente este caso y sus archivos?')) {
        $.post('secciones/exito/acciones_exito.php', {
            accion: 'eliminar',
            id_historia: id
        }, function(r) {
            if (r.exito) {
                if (tablaExitoDT) {
                    tablaExitoDT.row($('#fila_exito_' + r.id)).remove().draw(false);
                } else {
                    $('#fila_exito_' + r.id).fadeOut(400, function() {
                        $(this).remove();
                    });
                }
            } else {
                alert(r.mensaje);
            }
        }, 'json');
    }
}

function cambiarEstado(id, estadoActual) {
    $('#btn_estado_' + id).prop('disabled', true);

    $.post('secciones/exito/acciones_exito.php', {
        accion: 'cambiar_estado',
        id_historia: id,
        estado: estadoActual
    }, function(r) {
        if (r.exito) {
            if (r.nuevo_estado === 1) {
                $('#td_estado_' + r.id).html("<span class='label label-success'>Activo</span>");
                $('#btn_estado_' + r.id).attr('onclick', 'cambiarEstado(' + r.id + ', 1)');
            } else {
                $('#td_estado_' + r.id).html("<span class='label label-danger'>Oculto</span>");
                $('#btn_estado_' + r.id).attr('onclick', 'cambiarEstado(' + r.id + ', 0)');
            }
        } else {
            alert(r.mensaje);
        }
        $('#btn_estado_' + id).prop('disabled', false);
    }, 'json');
}
</script>
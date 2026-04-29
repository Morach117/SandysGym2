<?php
// Seguridad: Validar acciones y sanitizar entradas
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';
$id_empresa_esc = (int) $id_empresa;
$is_ajax = isset($_POST['is_ajax']) ? true : false;

// Función Helper para generar el HTML de la fila y evitar duplicar código
function generarFilaPlantilla($id, $nombre, $cuerpo, $is_new = false, $is_update = false) {
    $vista_previa = htmlspecialchars(mb_strimwidth($cuerpo, 0, 50, '...'));
    $nombre_safe = htmlspecialchars($nombre);
    
    // Codificación segura para los inputs ocultos (previene romper el HTML con comillas)
    $nombre_attr = htmlspecialchars($nombre, ENT_QUOTES);
    $cuerpo_attr = htmlspecialchars($cuerpo, ENT_QUOTES);
    
    $badge = $is_new ? "<span class='label label-success'>Nuevo</span>" : ($is_update ? "<span class='label label-warning'>Editado</span>" : $id);

    return "<tr id='fila_plan_{$id}'>
                <td>{$badge}</td>
                <td><strong>{$nombre_safe}</strong></td>
                <td><small>{$vista_previa}</small></td>
                <td class='text-center'>
                    <input type='hidden' id='raw_nombre_{$id}' value='{$nombre_attr}'>
                    <textarea id='raw_cuerpo_{$id}' style='display:none;'>{$cuerpo_attr}</textarea>
                    <button type='button' class='btn btn-warning btn-xs' onclick='editarPlantilla({$id})'><span class='glyphicon glyphicon-pencil'></span></button>
                    <button type='button' class='btn btn-danger btn-xs' onclick='eliminarPlantilla({$id})'><span class='glyphicon glyphicon-trash'></span></button>
                </td>
            </tr>";
}

if ($accion === 'guardar') {
    $plan_id_edit = isset($_POST['plan_id']) ? (int) $_POST['plan_id'] : 0;
    $nombre = mysqli_real_escape_string($conexion, trim($_POST['plan_nombre']));
    $cuerpo = mysqli_real_escape_string($conexion, trim($_POST['plan_cuerpo']));

    if (!empty($nombre) && !empty($cuerpo)) {
        if ($plan_id_edit > 0) {
            // Lógica de Actualización
            $sql = "UPDATE san_plantillas_correo 
                    SET plan_nombre = '$nombre', plan_cuerpo = '$cuerpo' 
                    WHERE plan_id = $plan_id_edit AND plan_id_empresa = $id_empresa_esc";
            $is_update = true;
        } else {
            // Lógica de Inserción
            $sql = "INSERT INTO san_plantillas_correo (plan_id_empresa, plan_nombre, plan_cuerpo) 
                    VALUES ($id_empresa_esc, '$nombre', '$cuerpo')";
            $is_update = false;
        }

        if (mysqli_query($conexion, $sql)) {
            if ($is_ajax) {
                $target_id = $is_update ? $plan_id_edit : mysqli_insert_id($conexion);
                $html_fila = generarFilaPlantilla($target_id, trim($_POST['plan_nombre']), trim($_POST['plan_cuerpo']), !$is_update, $is_update);

                // DESTRUIR HTML PREVIO DEL BUFFER Y FORZAR HEADER JSON
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');

                echo json_encode([
                    'exito' => true,
                    'is_update' => $is_update,
                    'id' => $target_id,
                    'html' => $html_fila
                ]);
                exit;
            }
        }
    }
    if ($is_ajax) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['exito' => false, 'mensaje' => 'Error al procesar en la base de datos.']);
        exit;
    }
}

if ($accion === 'eliminar') {
    $plan_id = (int) $_POST['plan_id'];
    $sql_delete = "DELETE FROM san_plantillas_correo WHERE plan_id = $plan_id AND plan_id_empresa = $id_empresa_esc";
    if (mysqli_query($conexion, $sql_delete)) {
        if ($is_ajax) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['exito' => true]);
            exit;
        }
    }
    if ($is_ajax) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['exito' => false, 'mensaje' => 'Error al eliminar.']);
        exit;
    }
}

// Obtener plantillas existentes
$query_plantillas = "SELECT plan_id, plan_nombre, plan_cuerpo FROM san_plantillas_correo WHERE plan_id_empresa = $id_empresa_esc";
$res_plantillas = mysqli_query($conexion, $query_plantillas);
?>

<div class="panel panel-info">
    <div class="panel-heading">
        <h3 class="panel-title"><span class="glyphicon glyphicon-list-alt"></span> Gestión de Plantillas de Correo</h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4">
                <div class="well well-sm">
                    <form id="formPlantilla">
                        <input type="hidden" id="plan_id_edit" value="0">
                        <fieldset>
                            <legend id="leyendaForm"><span class="glyphicon glyphicon-plus"></span> Nueva Plantilla</legend>
                            <div class="form-group">
                                <label>Nombre corto (ej: PromoEspecial):</label>
                                <input type="text" id="plan_nombre" class="form-control input-sm" required maxlength="50">
                            </div>
                            <div class="form-group">
                                <label>Mensaje (Usa [NOMBRE] para comodín):</label>
                                <textarea id="plan_cuerpo" class="form-control" rows="6" required></textarea>
                            </div>
                            <button type="submit" id="btnGuardar" class="btn btn-success btn-block">
                                <span class="glyphicon glyphicon-floppy-disk"></span> Guardar Plantilla
                            </button>
                            <button type="button" id="btnCancelar" class="btn btn-default btn-block" style="display:none;" onclick="cancelarEdicion()">
                                <span class="glyphicon glyphicon-remove"></span> Cancelar Edición
                            </button>
                        </fieldset>
                    </form>
                </div>
            </div>
            <div class="col-md-8 table-responsive">
                <table class="table table-hover table-condensed table-bordered" id="tablaPlantillas">
                    <thead>
                        <tr class="info">
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Vista Previa</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($res_plantillas) > 0) {
                            $i = 1;
                            while ($fila = mysqli_fetch_assoc($res_plantillas)) {
                                echo generarFilaPlantilla($fila['plan_id'], $fila['plan_nombre'], $fila['plan_cuerpo']);
                                $i++;
                            }
                        } else {
                            echo "<tr id='fila_vacia'><td colspan='4' class='text-center'>No hay plantillas registradas.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#formPlantilla').on('submit', function (e) {
            e.preventDefault();

            let id_edit = $('#plan_id_edit').val();
            let nombre = $('#plan_nombre').val().trim();
            let cuerpo = $('#plan_cuerpo').val().trim();
            let btn = $('#btnGuardar');

            if (!nombre || !cuerpo) return;

            btn.prop('disabled', true).html('<span class="glyphicon glyphicon-refresh"></span> Procesando...');

            $.ajax({
                type: 'POST',
                url: window.location.href, 
                data: {
                    accion: 'guardar',
                    is_ajax: 1,
                    plan_id: id_edit,
                    plan_nombre: nombre,
                    plan_cuerpo: cuerpo
                },
                dataType: 'json',
                success: function (res) {
                    if (res.exito) {
                        if (res.is_update) {
                            $('#fila_plan_' + res.id).replaceWith(res.html);
                        } else {
                            $('#fila_vacia').remove();
                            $('#tablaPlantillas tbody').append(res.html);
                        }
                        cancelarEdicion();
                    } else {
                        alert(res.mensaje);
                    }
                },
                error: function () {
                    alert("Error de conexión al guardar.");
                },
                complete: function () {
                    // Restauración visual en caso de error (si hay éxito, cancelarEdicion lo maneja)
                    if($('#plan_id_edit').val() == 0){
                        btn.prop('disabled', false).html('<span class="glyphicon glyphicon-floppy-disk"></span> Guardar Plantilla');
                    } else {
                        btn.prop('disabled', false).html('<span class="glyphicon glyphicon-refresh"></span> Actualizar Plantilla');
                    }
                }
            });
        });
    });

    function editarPlantilla(id) {
        // Extraer valores crudos desde la tabla de forma segura
        let nombre = $('#raw_nombre_' + id).val();
        let cuerpo = $('#raw_cuerpo_' + id).val();

        // Inyectar en el formulario
        $('#plan_id_edit').val(id);
        $('#plan_nombre').val(nombre);
        $('#plan_cuerpo').val(cuerpo);

        // Modificar UI del panel lateral
        $('#leyendaForm').html('<span class="glyphicon glyphicon-pencil"></span> Editar Plantilla');
        $('#btnGuardar').removeClass('btn-success').addClass('btn-warning').html('<span class="glyphicon glyphicon-refresh"></span> Actualizar Plantilla');
        $('#btnCancelar').show();
        
        // Hacer scroll suave hacia el formulario (útil en móviles)
        $('html, body').animate({ scrollTop: $('#formPlantilla').offset().top - 20 }, 'fast');
    }

    function cancelarEdicion() {
        $('#formPlantilla')[0].reset();
        $('#plan_id_edit').val(0);
        $('#leyendaForm').html('<span class="glyphicon glyphicon-plus"></span> Nueva Plantilla');
        $('#btnGuardar').removeClass('btn-warning').addClass('btn-success').html('<span class="glyphicon glyphicon-floppy-disk"></span> Guardar Plantilla');
        $('#btnCancelar').hide();
    }

    function eliminarPlantilla(id) {
        if (!confirm("¿Eliminar esta plantilla definitivamente?")) return;

        let fila = $('#fila_plan_' + id);
        let btn = fila.find('.btn-danger');

        btn.prop('disabled', true).html('<span class="glyphicon glyphicon-time"></span>');

        $.ajax({
            type: 'POST',
            url: window.location.href, 
            data: {
                accion: 'eliminar',
                is_ajax: 1,
                plan_id: id
            },
            dataType: 'json',
            success: function (res) {
                if (res.exito) {
                    fila.fadeOut(300, function () { $(this).remove(); });
                    // Si se está editando la fila eliminada, abortar edición
                    if ($('#plan_id_edit').val() == id) cancelarEdicion();
                } else {
                    alert(res.mensaje);
                    btn.prop('disabled', false).html('<span class="glyphicon glyphicon-trash"></span>');
                }
            },
            error: function () {
                alert("Error de conexión al eliminar.");
                btn.prop('disabled', false).html('<span class="glyphicon glyphicon-trash"></span>');
            }
        });
    }
</script>
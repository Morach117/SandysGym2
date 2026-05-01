<?php
// Forzar codificación utf8mb4 en la conexión para soportar Emojis
mysqli_set_charset($conexion, "utf8mb4");

// Seguridad: Validar acciones y sanitizar entradas
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';
$id_empresa_esc = (int) $id_empresa;
$is_ajax = isset($_POST['is_ajax']) ? true : false;

// Función Helper para generar el HTML de la fila
function generarFilaPlantilla($id, $nombre, $cuerpo, $is_new = false, $is_update = false) {
    // Strip tags para evitar renderizar imágenes gigantes en la vista previa
    $vista_previa = htmlspecialchars(mb_strimwidth(strip_tags($cuerpo), 0, 50, '...'));
    $nombre_safe = htmlspecialchars($nombre);
    
    $nombre_attr = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $cuerpo_attr = htmlspecialchars($cuerpo, ENT_QUOTES, 'UTF-8');
    
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
    $nombre_raw = trim($_POST['plan_nombre']);
    $cuerpo_raw = trim($_POST['plan_cuerpo']);

    // Procesamiento seguro de imagen adjunta
    if (isset($_FILES['plan_imagen']) && $_FILES['plan_imagen']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['plan_imagen']['tmp_name'];
        $file_name = $_FILES['plan_imagen']['name'];
        $file_size = $_FILES['plan_imagen']['size'];

        // Límite estricto: 2MB
        if ($file_size > 2097152) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['exito' => false, 'mensaje' => 'La imagen excede el límite de 2MB.']);
            exit;
        }

        // Validación por MIME type real
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);

        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowed_mimes)) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['exito' => false, 'mensaje' => 'Formato de imagen no permitido (solo JPG, PNG, GIF, WEBP).']);
            exit;
        }

        // Renombrar y mover para evitar ejecución remota
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_filename = uniqid('img_') . '.' . $ext;
        
        // CORRECCIÓN: Definir ruta absoluta física a la carpeta 'imagenes' en la raíz del proyecto
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/imagenes/';

        // Verificar y crear el directorio local si no existe, aplicando hardening
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
            // Crear .htaccess para prevenir ejecución de scripts en esta carpeta
            file_put_contents($upload_dir . '.htaccess', "removehandler .php .phtml .php3\nphp_flag engine off");
            // Index ciego por privacidad
            file_put_contents($upload_dir . 'index.php', '<?php // Silence');
        }

        if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
            // CORRECCIÓN: Generar URL pública apuntando al directorio raíz '/imagenes/'
            $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
            $img_url = $protocolo . $_SERVER['HTTP_HOST'] . "/imagenes/" . $new_filename;
            
            // Inyectar etiqueta
            $cuerpo_raw .= "<br><br><img src='{$img_url}' alt='Imagen adjunta' style='max-width:100%; border-radius: 8px;'>";
        } else {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['exito' => false, 'mensaje' => 'Error de permisos al guardar la imagen en el servidor local.']);
            exit;
        }
    }

    $nombre = mysqli_real_escape_string($conexion, $nombre_raw);
    $cuerpo = mysqli_real_escape_string($conexion, $cuerpo_raw);

    if (!empty($nombre) && !empty($cuerpo)) {
        if ($plan_id_edit > 0) {
            $sql = "UPDATE san_plantillas_correo 
                    SET plan_nombre = '$nombre', plan_cuerpo = '$cuerpo' 
                    WHERE plan_id = $plan_id_edit AND plan_id_empresa = $id_empresa_esc";
            $is_update = true;
        } else {
            $sql = "INSERT INTO san_plantillas_correo (plan_id_empresa, plan_nombre, plan_cuerpo) 
                    VALUES ($id_empresa_esc, '$nombre', '$cuerpo')";
            $is_update = false;
        }

        if (mysqli_query($conexion, $sql)) {
            if ($is_ajax) {
                $target_id = $is_update ? $plan_id_edit : mysqli_insert_id($conexion);
                $html_fila = generarFilaPlantilla($target_id, $nombre_raw, $cuerpo_raw, !$is_update, $is_update);

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
        } else {
            $db_error = mysqli_error($conexion);
            if ($is_ajax) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['exito' => false, 'mensaje' => "Error DB: " . $db_error]);
                exit;
            }
        }
    }
    
    if ($is_ajax) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['exito' => false, 'mensaje' => 'Faltan datos obligatorios.']);
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
                    <!-- enctype añadido para soportar archivos -->
                    <form id="formPlantilla" enctype="multipart/form-data">
                        <input type="hidden" id="plan_id_edit" value="0">
                        <fieldset>
                            <legend id="leyendaForm"><span class="glyphicon glyphicon-plus"></span> Nueva Plantilla</legend>
                            <div class="form-group">
                                <label>Nombre corto (ej: PromoEspecial):</label>
                                <input type="text" id="plan_nombre" class="form-control input-sm" required maxlength="50">
                            </div>
                            <div class="form-group">
                                <label>Mensaje (Soporta Emojis):</label>
                                <textarea id="plan_cuerpo" class="form-control" rows="6" required></textarea>
                            </div>
                            <div class="form-group">
                                <label><span class="glyphicon glyphicon-picture"></span> Adjuntar Imagen (Opcional):</label>
                                <input type="file" id="plan_imagen" class="form-control input-sm" accept=".jpg, .jpeg, .png, .gif, .webp">
                                <small class="text-muted">Máx 2MB. Se incrustará al final del mensaje.</small>
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
            let inputFile = $('#plan_imagen')[0].files[0];
            let btn = $('#btnGuardar');

            if (!nombre || !cuerpo) return;

            let formData = new FormData();
            formData.append('accion', 'guardar');
            formData.append('is_ajax', 1);
            formData.append('plan_id', id_edit);
            formData.append('plan_nombre', nombre);
            formData.append('plan_cuerpo', cuerpo);
            
            if (inputFile) {
                formData.append('plan_imagen', inputFile);
            }

            btn.prop('disabled', true).html('<span class="glyphicon glyphicon-refresh"></span> Procesando...');

            $.ajax({
                type: 'POST',
                url: window.location.href, 
                data: formData,
                processData: false, 
                contentType: false, 
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
        let nombre = $('#raw_nombre_' + id).val();
        let cuerpo = $('#raw_cuerpo_' + id).val();

        $('#plan_id_edit').val(id);
        $('#plan_nombre').val(nombre);
        $('#plan_cuerpo').val(cuerpo);
        $('#plan_imagen').val('');

        $('#leyendaForm').html('<span class="glyphicon glyphicon-pencil"></span> Editar Plantilla');
        $('#btnGuardar').removeClass('btn-success').addClass('btn-warning').html('<span class="glyphicon glyphicon-refresh"></span> Actualizar Plantilla');
        $('#btnCancelar').show();
        
        $('html, body').animate({ scrollTop: $('#formPlantilla').offset().top - 20 }, 'fast');
    }

    function cancelarEdicion() {
        $('#formPlantilla')[0].reset();
        $('#plan_id_edit').val(0);
        $('#plan_imagen').val('');
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
            data: { accion: 'eliminar', is_ajax: 1, plan_id: id },
            dataType: 'json',
            success: function (res) {
                if (res.exito) {
                    fila.fadeOut(300, function () { $(this).remove(); });
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
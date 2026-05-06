<?php
// 1. OBTENCIÓN DE PLANTILLAS DESDE BD
global $conexion, $id_empresa; // Asegurar alcance de variables del router legacy

mysqli_set_charset($conexion, "utf8mb4");
$id_empresa_esc = mysqli_real_escape_string($conexion, $id_empresa ?? '');

$plantillas_db = [];
$query_plan = "SELECT plan_nombre, plan_cuerpo FROM san_plantillas_correo WHERE plan_id_empresa = '$id_empresa_esc'";
$res_plan = mysqli_query($conexion, $query_plan);

if ($res_plan) {
    while ($row = mysqli_fetch_assoc($res_plan)) {
        $plantillas_db[$row['plan_nombre']] = $row['plan_cuerpo'];
    }
}
$plantillas_json = json_encode($plantillas_db, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// 2. OBTENCIÓN DE MENSAJES
function obtener_mensajes_contacto() {
    global $conexion; 
    
    $query = "SELECT id_contacto, nombre, correo, telefono, mensaje, fecha_registro, leido 
              FROM san_contactos 
              ORDER BY fecha_registro DESC";
              
    $resultado = mysqli_query($conexion, $query);
    $datos = "";
    $i = 1;

    if ($resultado) {
        if (mysqli_num_rows($resultado) > 0) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $fecha = date('d/m/Y h:i A', strtotime($fila['fecha_registro']));
                
                $estado_badge = ($fila['leido'] == 0) 
                    ? "<span class='label label-success'>Nuevo</span>" 
                    : "<span class='label label-default'>Leído</span>";
                
                $telefono = !empty($fila['telefono']) ? htmlspecialchars($fila['telefono']) : '<em class="text-muted">N/A</em>';

                $btn_responder = "<button class='btn btn-xs btn-info' onclick='abrirModalRespuesta(\"".htmlspecialchars($fila['correo'])."\", \"".htmlspecialchars($fila['nombre'])."\", {$fila['id_contacto']})' title='Responder'><span class='glyphicon glyphicon-share-alt'></span></button>";
                $btn_eliminar = "<button class='btn btn-xs btn-danger' onclick='eliminarMensaje({$fila['id_contacto']})' title='Eliminar'><span class='glyphicon glyphicon-trash'></span></button>";

                $datos .= "<tr>
                            <td>{$i}</td>
                            <td>
                                <strong>" . htmlspecialchars($fila['nombre']) . "</strong><br>
                                <small class='text-muted'>" . htmlspecialchars($fila['correo']) . "</small>
                            </td>
                            <td>{$telefono}</td>
                            <td>" . nl2br(htmlspecialchars($fila['mensaje'])) . "</td>
                            <td>{$fecha}</td>
                            <td>{$estado_badge}</td>
                            <td>
                                <div class='btn-group'>
                                    {$btn_responder}
                                    {$btn_eliminar}
                                </div>
                            </td>
                           </tr>";
                $i++;
            }
        } else {
            $datos = "<tr><td colspan='7' class='text-center'>No hay mensajes en la bandeja.</td></tr>";
        }
        mysqli_free_result($resultado);
        return $datos;
    } else {
        return "<tr><td colspan='7' class='text-center text-danger'>Error: " . mysqli_error($conexion) . "</td></tr>";
    }
}
$var_lista_contactos = obtener_mensajes_contacto();
?>

<div class="row">
    <div class="col-md-12">
        <h4 class="text-info">
            <span class="glyphicon glyphicon-envelope"></span> Bandeja de Contactos y Comentarios
        </h4>
        <hr/>
        <div class="table-responsive">
            <table id="tabla_contactos" class="table table-hover table-condensed table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente / Correo</th>
                        <th>Teléfono</th>
                        <th>Mensaje</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $var_lista_contactos; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Bootstrap 3 -->
<div class="modal fade" id="modalRespuesta" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span class="glyphicon glyphicon-send"></span> Responder Mensaje</h4>
            </div>
            <form id="formResponderContacto">
                <div class="modal-body">
                    <input type="hidden" name="id_contacto" id="res_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Para:</label>
                                <input type="text" name="email" id="res_email" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombre:</label>
                                <input type="text" name="socio" id="res_nombre" class="form-control" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Plantilla:</label>
                        <select class="form-control input-sm" id="select_plantilla_respuesta" onchange="cargarPlantillaContacto()">
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Respuesta (Soporta HTML/Emojis):</label>
                        <textarea name="mensaje" id="res_mensaje" class="form-control" rows="8" required placeholder="Seleccione una plantilla o redacte aquí..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnEnviar">Enviar Correo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const plantillas = <?= $plantillas_json ?: '{}' ?>; // Fallback a objeto vacío si falla JSON

$(document).ready(function() {
    // 1. Cargar plantillas de inmediato (No bloqueante)
    let options = '<option value="">-- Redacción Manual --</option>';
    for (const name in plantillas) {
        options += `<option value="${name}">${name}</option>`;
    }
    $('#select_plantilla_respuesta').html(options);

    // 2. Inicialización segura de DataTables
    try {
        if (typeof $.fn.DataTable !== 'undefined' && !$.fn.DataTable.isDataTable('#tabla_contactos')) {
            $('#tabla_contactos').DataTable({
                "order": [],
                "language": { "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json" }
            });
        }
    } catch (error) {
        console.error("DataTables Warning: ", error);
    }
});

function cargarPlantillaContacto() {
    const key = $('#select_plantilla_respuesta').val();
    const nombre = $('#res_nombre').val() || 'Cliente';
    if (key && plantillas[key]) {
        let texto = plantillas[key].replace(/\[NOMBRE\]/g, nombre);
        $('#res_mensaje').val(texto);
    } else {
        $('#res_mensaje').val('');
    }
}

function abrirModalRespuesta(email, nombre, id) {
    $('#res_id').val(id);
    $('#res_email').val(email);
    $('#res_nombre').val(nombre);
    $('#select_plantilla_respuesta').val('');
    $('#res_mensaje').val('');
    $('#modalRespuesta').modal('show');
}

$('#formResponderContacto').on('submit', function(e){
    e.preventDefault();
    const btn = $('#btnEnviar');
    btn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh"></i> Enviando...');

    $.ajax({
        url: 'secciones/comentarios/acciones_contacto.php',
        type: 'POST',
        data: $(this).serialize() + '&accion=responder',
        dataType: 'json',
        success: function(r) {
            alert(r.mensaje);
            if(r.exito) location.reload();
        },
        error: () => alert("Error de conexión al servidor."),
        complete: () => btn.prop('disabled', false).html('Enviar Correo')
    });
});

function eliminarMensaje(id) {
    if(confirm('¿Desea eliminar este mensaje permanentemente?')) {
        $.post('secciones/comentarios/acciones_contacto.php', { accion: 'eliminar', id_contacto: id }, function(r) {
            alert(r.mensaje);
            if(r.exito) location.reload();
        }, 'json');
    }
}
</script>
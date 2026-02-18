<?php

function lista_socios_fechas($rango_ini, $rango_fin, $pag_busqueda)
{
    global $conexion, $id_empresa, $gbl_paginado;

    $datos = "";
    $condicion = "";
    $contador = 1;
    $exito = array();
    $pagina = (request_var('pag', 1) - 1) * $gbl_paginado;
    $bloque = request_var('blq', 0);
    $pag = request_var('pag', 0);

    $parametros = "&pag_fechai=$rango_ini&pag_fechaf=$rango_fin&item=lista_vencidos";

    if ($pag_busqueda)
        $parametros .= "&pag_busqueda=$pag_busqueda";

    if ($bloque)
        $parametros .= "&blq=$bloque";

    if ($pag)
        $parametros .= "&pag=$pag";

    $rango_ini = fecha_formato_mysql($rango_ini);
    $rango_fin = fecha_formato_mysql($rango_fin);

    if ($pag_busqueda) {
        $condicion = "AND (
                            LOWER(CONCAT(soc_apepat, ' ', soc_apemat, ' ', soc_nombres)) LIKE LOWER('%$pag_busqueda%')
                        )";
    }

    // Consulta para contar el número de socios
    $query = "SELECT soc_id_socio AS id_socio
              FROM san_socios
              INNER JOIN san_pagos ON pag_id_socio = soc_id_socio
              AND DATE_FORMAT(pag_fecha_fin, '%Y-%m-%d') 
              BETWEEN DATE_FORMAT('$rango_ini', '%Y-%m-%d') 
              AND DATE_FORMAT('$rango_fin', '%Y-%m-%d') 
              AND pag_status = 'A'
              AND pag_fecha_fin = (
                  SELECT pag_fecha_fin
                  FROM san_pagos
                  WHERE pag_id_socio = soc_id_socio
                  AND pag_status = 'A'
                  ORDER BY pag_fecha_fin DESC 
                  LIMIT 0, 1
              )
              WHERE soc_id_empresa = $id_empresa
              AND soc_tel_cel IS NOT NULL AND soc_tel_cel <> ''
              AND (is_active IS NULL OR is_active = '0000-00-00') 
              $condicion
              GROUP BY soc_id_socio";

    $resultado = mysqli_query($conexion, $query);

    if ($resultado)
        $exito['num'] = mysqli_num_rows($resultado);

    // Consulta principal con los campos adicionales is_active y metodo_contacto
    $query = "SELECT soc_id_socio AS id_socio,
                     pag_id_pago AS id_pago,
                     soc_nombres AS nombres,
                     CONCAT(soc_apepat, ' ', soc_apemat) AS apellidos,
                     IF(pag_id_pago > 0, CONCAT(DATE_FORMAT(pag_fecha_ini, '%d-%m-%Y'), ' al ', DATE_FORMAT(pag_fecha_fin, '%d-%m-%Y')), 'Pago Vencido') AS status_pago,
                     IF(soc_imagen IS NULL OR soc_imagen = '', 'Sin nombre de archivo', soc_imagen) AS img,
                     is_active,
                     metodo_contacto,
                     soc_correo AS correo,
                     soc_tel_cel AS telefono
              FROM san_socios
              INNER JOIN san_pagos ON pag_id_socio = soc_id_socio
              AND DATE_FORMAT(pag_fecha_fin, '%Y-%m-%d') 
              BETWEEN DATE_FORMAT('$rango_ini', '%Y-%m-%d') 
              AND DATE_FORMAT('$rango_fin', '%Y-%m-%d') 
              AND pag_status = 'A'
              AND pag_fecha_fin = (
                  SELECT pag_fecha_fin
                  FROM san_pagos
                  WHERE pag_id_socio = soc_id_socio
                  AND pag_status = 'A'
                  ORDER BY pag_fecha_fin DESC 
                  LIMIT 0, 1
              )
              WHERE soc_id_empresa = $id_empresa
              AND soc_tel_cel IS NOT NULL AND soc_tel_cel <> ''
              AND (is_active IS NULL OR is_active = '0000-00-00') 
              $condicion
              GROUP BY soc_id_socio
              ORDER BY pag_fecha_fin DESC
              LIMIT $pagina, $gbl_paginado";

    $resultado = mysqli_query($conexion, $query);

    if ($resultado) {
        $datos .= "<table class='table table-striped'>";
        $datos .= "<thead>
                        <tr>
                            <th>Opc</th>                       
                            <th>#</th>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Correo</th>
                            <th>Teléfono</th>
                            <th>Vigencia</th>
                            <th>Foto</th>
                            <th>Asignado</th>
                            <th>Método de Contacto</th>
                        </tr>
                   </thead>
                   <tbody>";

        $current_month = date('Y-m'); // Obtener el mes actual

        while ($fila = mysqli_fetch_assoc($resultado)) {
            $fotografia = "../../imagenes/avatar/{$fila['id_socio']}.jpg";
            if (!file_exists($fotografia)) {
                $fotografia = "../../imagenes/avatar/noavatar.jpg";
            }

            // Verificar si el socio está activo en el mes actual
            $is_active_date = $fila['is_active'];
            $is_active = ($is_active_date !== '0000-00-00' && $is_active_date !== null && strpos($is_active_date, $current_month) === 0) ? 'checked' : '';

            $datos .= "<tr>
                           <td>
                                <div class='btn-group'>
                                    <a class='pointer dropdown-toggle' data-toggle='dropdown'>
                                        <span class='glyphicon glyphicon-chevron-down'></span>
                                    </a>
                                    <ul class='dropdown-menu'>
                                        <li>
                                            <a href='javascript:mostrarHistorial({$fila['id_socio']})'><span class='glyphicon glyphicon-list-alt'></span> Historial</a>
                                        </li>
                                        <li>
                                            <a href='javascript:editarDatos({$fila['id_socio']})'><span class='glyphicon glyphicon-edit'></span> Editar Datos</a>
                                        </li>
                                        <li>
                                            <a href='javascript:actualizarTelefono({$fila['id_socio']})'><span class='glyphicon glyphicon-phone'></span> Actualizar Teléfono</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>{$contador}</td>
                            <td>{$fila['id_socio']}</td>
                            <td>{$fila['apellidos']} {$fila['nombres']}</td>
                            <td>{$fila['correo']}</td>
                            <td><a href='https://wa.me/+521{$fila['telefono']}' target='_blank'>+521{$fila['telefono']}</a></td>
                            <td>{$fila['status_pago']}</td>
                            <td>
                            <a href='#' data-toggle='modal' data-target='#fotografiaModal' data-id='{$fila['id_socio']}' onclick='cargarFotografia({$fila['id_socio']})'>
                            <img src='{$fotografia}' class='img-responsive' alt='{$fila['id_socio']}' width='50px' />                            
                            </td>
                            <td>
                                <input type='checkbox' class='socio-checkbox' data-id='{$fila['id_socio']}' $is_active>
                            </td>
                            <td>
                                <select class='form-control metodo-contacto' data-id='{$fila['id_socio']}'>
                                    <option value='Llamada' " . ($fila['metodo_contacto'] == 'Llamada' ? 'selected' : '') . ">Llamada</option>
                                    <option value='Whatsapp' " . ($fila['metodo_contacto'] == 'Whatsapp' ? 'selected' : '') . ">Whatsapp</option>
                                    <option value='Correo electronico' " . ($fila['metodo_contacto'] == 'Correo electronico' ? 'selected' : '') . ">Correo electrónico</option>
                                </select>
                            </td>
                          </tr>";

            $contador++;
        }

        $datos .= "</tbody></table>";

        if (isset($exito['num']) && $exito['num'] == 0) {
            $datos = "<div class='alert alert-info'>No hay datos</div>";
        }
    } else {
        $datos .= "<div class='alert alert-danger'>Error: " . mysqli_error($conexion) . "</div>";
    }

    $exito['msj'] = $datos;

    return $exito;
}



?>
<!-- Modal Fotografía -->
<div class="modal fade" id="fotografiaModal" tabindex="-1" role="dialog" aria-labelledby="fotografiaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fotografiaModalLabel">Fotografía</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <img id="fotografiaPreview" src="" class="img-responsive" alt="Fotografía del socio" width="100%">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
        // Evento para cargar la fotografía en el modal
        $('#fotografiaModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var fotografiaUrl = '../../imagenes/avatar/' + id + '.jpg';
            $('#fotografiaPreview').attr('src', fotografiaUrl);
        });

        // Evento para actualizar el método de contacto
        $('.metodo-contacto').change(function() {
            var id_socio = $(this).data('id');
            var metodo_contacto = $(this).val();

            $.ajax({
                url: './funciones/actualizar_metodo_contacto.php',
                method: 'POST',
                data: {
                    id_socio: id_socio,
                    metodo_contacto: metodo_contacto
                },
                dataType: 'json', // Asegura que el formato de la respuesta sea JSON
                success: function(response) {
                    if (response.success) {
                        console.log('Método de contacto actualizado exitosamente');
                    } else {
                        console.error('Error al actualizar el método de contacto: ' + response.error);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error en la petición AJAX: ' + textStatus + ' - ' + errorThrown);
                }
            });
        });
    });

    function mostrarHistorial(id_socio) {
        $.ajax({
            url: './funciones/funciones_reportes_vencidos.php',
            method: 'POST',
            data: {
                action: 'mostrarHistorial',
                id_socio: id_socio
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    alert(response.error);
                } else {
                    var historialHtml = '<table class="table table-striped">';
                    historialHtml += '<thead><tr><th>Descripción</th><th>Fecha de Pago</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Importe</th><th>Vigencia</th></tr></thead><tbody>';
                    response.forEach(function(pago) {
                        historialHtml += '<tr class="' + pago.class + '">';
                        historialHtml += '<td>' + pago.descripcion + '</td>';
                        historialHtml += '<td>' + pago.fecha_pago + '</td>';
                        historialHtml += '<td>' + pago.fecha_ini + '</td>';
                        historialHtml += '<td>' + pago.fecha_fin + '</td>';
                        historialHtml += '<td>$' + pago.importe + '</td>';
                        historialHtml += '<td>' + pago.vigencia + '</td>';
                        historialHtml += '</tr>';
                    });
                    historialHtml += '</tbody></table>';
                    $('#modalHistorial .modal-body').html(historialHtml);
                    $('#modalHistorial').modal('show');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al mostrar historial:', error);
                alert('Ocurrió un error al obtener el historial.');
            }
        });
    }


    function editarDatos(id_socio) {
        $.ajax({
            url: './funciones/funciones_reportes_vencidos.php',
            method: 'POST',
            data: {
                action: 'editarDatos',
                id_socio: id_socio
            },
            dataType: 'json', // Indica que se espera una respuesta JSON
            success: function(response) {
                if (response.error) {
                    alert(response.error);
                } else {
                    var formulario = '<form id="formEditar">' +
                        '<input type="hidden" name="action" value="guardarCambios">' +
                        '<input type="hidden" name="id_socio" value="' + response.id_socio + '">' +
                        '<div class="form-group">' +
                        '<label for="nombres">Nombres</label>' +
                        '<input type="text" class="form-control" name="nombres" value="' + response.nombres + '">' +
                        '</div>' +
                        '<div class="form-group">' +
                        '<label for="apepat">Apellido Paterno</label>' +
                        '<input type="text" class="form-control" name="apepat" value="' + response.apepat + '">' +
                        '</div>' +
                        '<div class="form-group">' +
                        '<label for="apemat">Apellido Materno</label>' +
                        '<input type="text" class="form-control" name="apemat" value="' + response.apemat + '">' +
                        '</div>' +
                        '<div class="form-group">' +
                        '<label for="correo">Correo</label>' +
                        '<input type="email" class="form-control" name="correo" value="' + response.correo + '">' +
                        '</div>' +
                        '<div class="form-group">' +
                        '<label for="telefono">Teléfono</label>' +
                        '<input type="text" class="form-control" name="telefono" value="' + response.telefono + '">' +
                        '</div>' +
                        '<div class="form-group">' +
                        '<label for="fecha_nacimiento">Fecha de Nacimiento</label>' +
                        '<input type="date" class="form-control" name="fecha_nacimiento" value="' + response.fecha_nacimiento + '">' +
                        '</div>' +
                        '<button type="button" class="btn btn-primary" onclick="guardarCambios()">Guardar Cambios</button>' +
                        '</form>';
                    $('#modalEditar .modal-body').html(formulario);
                    $('#modalEditar').modal('show');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al obtener datos:', error);
                alert('Ocurrió un error al obtener los datos.');
            }
        });
    }

    function actualizarTelefono(id_socio) {
        $.ajax({
            url: './funciones/funciones_reportes_vencidos.php',
            method: 'POST',
            data: {
                action: 'actualizarTelefono',
                id_socio: id_socio
            },
            dataType: 'json', // Indica que se espera una respuesta JSON
            success: function(response) {
                if (response.error) {
                    alert(response.error);
                } else {
                    var formulario = '<form id="formTelefono">' +
                        '<input type="hidden" name="action" value="guardarTelefono">' +
                        '<input type="hidden" name="id_socio" value="' + response.id_socio + '">' +
                        '<div class="form-group">' +
                        '<label for="telefono">Teléfono</label>' +
                        '<input type="text" class="form-control" name="telefono" value="' + response.telefono + '">' +
                        '</div>' +
                        '<button type="button" class="btn btn-primary" onclick="guardarTelefono()">Guardar Teléfono</button>' +
                        '</form>';
                    $('#modalTelefono .modal-body').html(formulario);
                    $('#modalTelefono').modal('show');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al obtener teléfono:', error);
                alert('Ocurrió un error al obtener el teléfono.');
            }
        });
    }

    function guardarCambios() {
        var formData = $('#formEditar').serialize();
        $.ajax({
            url: './funciones/funciones_reportes_vencidos.php',
            method: 'POST',
            data: formData,
            dataType: 'json', // Indica que se espera una respuesta JSON
            success: function(response) {
                if (response.success) {
                    alert(response.success);
                    $('#modalEditar').modal('hide');
                    location.reload();
                } else if (response.error) {
                    alert(response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al guardar cambios:', error);
                alert('Ocurrió un error al guardar los cambios.');
            }
        });
    }

    function guardarTelefono() {
        var formData = $('#formTelefono').serialize();
        $.ajax({
            url: './funciones/funciones_reportes_vencidos.php',
            method: 'POST',
            data: formData,
            dataType: 'json', // Indica que se espera una respuesta JSON
            success: function(response) {
                if (response.success) {
                    alert(response.success);
                    $('#modalTelefono').modal('hide');
                    location.reload();
                } else if (response.error) {
                    alert(response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al guardar teléfono:', error);
                alert('Ocurrió un error al actualizar el teléfono.');
            }
        });
    }

    $(document).ready(function() {
        // Manejar el evento de cambio en los checkboxes de los socios
        $('.socio-checkbox').change(function() {
            var id_socio = $(this).data('id');
            var is_active = $(this).is(':checked') ? 1 : 0;

            // Enviar solicitud AJAX para actualizar el estado del socio
            $.ajax({
                url: window.location.href, // URL actual de la página
                type: 'POST',
                data: {
                    ajax: 'actualizar_socio',
                    id_socio: id_socio,
                    is_active: is_active
                },
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.status === 'success') {
                        alert('Estado del socio actualizado con éxito.');
                    } else {
                        alert('Error al actualizar el estado del socio.');
                    }
                },
                error: function() {
                    alert('Ocurrió un error en la solicitud.');
                }
            });
        });
    });
</script>

<?php
// Procesar la actualización del estado del socio vía AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax']) && $_POST['ajax'] == 'actualizar_socio') {
    $id_socio = intval($_POST['id_socio']);
    $is_active = $_POST['is_active'] ? date('Y-m-d') : '0000-00-00';

    // Actualizar el estado del socio con la fecha actual o restablecer la fecha
    $query = "UPDATE san_socios SET is_active = '$is_active' WHERE soc_id_socio = $id_socio";

    $resultado = mysqli_query($conexion, $query);

    if ($resultado) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}
?>

<!-- Modal Historial -->
<div class="modal fade" id="modalHistorial" tabindex="-1" role="dialog" aria-labelledby="modalHistorialLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHistorialLabel">Historial</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- El contenido se actualizará con AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarLabel">Editar Datos</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- El contenido se actualizará con AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Teléfono -->
<div class="modal fade" id="modalTelefono" tabindex="-1" role="dialog" aria-labelledby="modalTelefonoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTelefonoLabel">Actualizar Teléfono</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- El contenido se actualizará con AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php
// Asumo que la conexión $conexion ya está disponible
// include('conn.php');

function obtener_mensajes_no_leidos()
{
    global $conexion, $id_empresa;
    
    // Filtramos estrictamente por mensajes donde 'leido' sea 0
    $query = "SELECT id_contacto, nombre, correo, telefono, mensaje, fecha_registro 
              FROM san_contactos 
              WHERE leido = 0 
              ORDER BY fecha_registro DESC";
              
    $resultado = mysqli_query($conexion, $query);
    
    $datos = "";
    $i = 1;

    if ($resultado) {
        if (mysqli_num_rows($resultado) > 0) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                
                $fecha = date('d/m/Y h:i A', strtotime($fila['fecha_registro']));
                $telefono = !empty($fila['telefono']) ? htmlspecialchars($fila['telefono']) : '<em class="text-muted">N/A</em>';

                $datos .= "<tr>
                            <td>{$i}</td>
                            <td>
                                <strong>" . htmlspecialchars($fila['nombre']) . "</strong><br>
                                <small class='text-muted'>" . htmlspecialchars($fila['correo']) . "</small>
                            </td>
                            <td>{$telefono}</td>
                            <td>" . nl2br(htmlspecialchars($fila['mensaje'])) . "</td>
                            <td>{$fecha}</td>
                            <td class='text-center'>
                                <span class='label label-success' style='background-color: #10b981;'>Pendiente</span>
                            </td>
                           </tr>";
                $i++;
            }
        } else {
            // Mensaje amigable si todo está al día
            $datos = "<tr><td colspan='6' class='text-center' style='padding: 20px;'>
                        <span class='glyphicon glyphicon-ok-circle' style='font-size: 20px; color: #10b981;'></span><br>
                        ¡Excelente! No tienes mensajes pendientes por leer.
                      </td></tr>";
        }
        
        mysqli_free_result($resultado);
        return $datos;

    } else {
        return "<tr><td colspan='6' class='text-center text-danger'>Error: " . mysqli_error($conexion) . "</td></tr>";
    }
}

$var_pendientes = obtener_mensajes_no_leidos();
?>

<div class="row">
    <div class="col-md-12">
        <h4 class="text-info">
            <span class="glyphicon glyphicon-bell"></span> Mensajes Nuevos (Sin leer)
        </h4>
    </div>
</div>

<hr/>

<div class="row">
    <div class="col-md-12">
        <table id="tabla_no_leidos" class="table table-hover table-condensed table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente / Correo</th>
                    <th>Teléfono</th>
                    <th>Mensaje</th>
                    <th>Fecha</th>
                    <th class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $var_pendientes; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <br>
        <a href="javascript:history.back()" class="btn btn-primary">Regresar</a>
    </div>
</div>

<script>
$(document).ready( function () {
    $('#tabla_no_leidos').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        }
    });
});
</script>
<?php
// Asumo que tu archivo de conexión ya fue incluido en el enrutador del panel
// include('conn.php');

function obtener_mensajes_contacto()
{
    global $conexion; // Usamos tu variable de conexión original
    
    // Consulta ordenando por los más recientes primero
    $query = "SELECT id_contacto, nombre, correo, telefono, mensaje, fecha_registro, leido 
              FROM san_contactos 
              ORDER BY fecha_registro DESC";
              
    $resultado = mysqli_query($conexion, $query);
    
    $datos = "";
    $i = 1;

    if ($resultado) {
        if (mysqli_num_rows($resultado) > 0) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                
                // Formateamos la fecha a un formato más amigable (Día/Mes/Año Hora)
                $fecha = date('d/m/Y h:i A', strtotime($fila['fecha_registro']));
                
                // Usamos las etiquetas (labels) nativas de Bootstrap 3/4 para el panel
                $estado_badge = ($fila['leido'] == 0) 
                    ? "<span class='label label-success' style='background-color: #5cb85c; color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px;'>Nuevo</span>" 
                    : "<span class='label label-default' style='background-color: #777777; color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px;'>Leído</span>";
                
                // Validar si dejó teléfono
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
                            <td>{$estado_badge}</td>
                           </tr>";
                $i++;
            }
        } else {
            $datos = "<tr><td colspan='6' class='text-center'>No hay mensajes de contacto en la bandeja.</td></tr>";
        }
        
        // Liberamos la memoria del resultado
        mysqli_free_result($resultado);
        
        return $datos;

    } else {
        return "<tr><td colspan='6' class='text-center text-danger'>Error al obtener los datos: " . mysqli_error($conexion) . "</td></tr>";
    }
}

$var_lista_contactos = obtener_mensajes_contacto();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bandeja de Contacto</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
</head>
<body>

<div class="row">
    <div class="col-md-12">
        <h4 class="text-info">
            <span class="glyphicon glyphicon-envelope"></span> Bandeja de Contacto Web
        </h4>
    </div>
</div>

<hr/>

<div class="row">
    <div class="col-md-12">
        <table id="tabla_contactos" class="table table-hover table-condensed table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente / Correo</th>
                    <th>Teléfono</th>
                    <th>Mensaje</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="lista_contactos">
                <?php echo $var_lista_contactos; ?>
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
    $('#tabla_contactos').DataTable({
        "order": [], // Evita que ordene por defecto y respete el orden DESC de PHP
        "language": {
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "No se encontraron resultados",
            "info": "Mostrando página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        }
    });
});
</script>

</body>
</html>
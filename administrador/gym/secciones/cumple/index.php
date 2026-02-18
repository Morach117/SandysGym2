<?php
function obtener_socios_cumpleaños()
{
    global $conexion, $id_empresa, $gbl_paginado;
    
    // Obtener la fecha de inicio y fin del mes actual
    $fecha_inicio_mes = date('Y-m-01');
    $fecha_fin_mes = date('Y-m-t');
    $fecha_mov = date('Y-m-d'); // Fecha actual

    // Consulta para obtener los socios que cumplen años este mes junto con la vigencia de su último pago
    $query = "SELECT 
                s.soc_id_socio AS id_socio,
                CONCAT(s.soc_apepat, ' ', s.soc_apemat, ' ', s.soc_nombres) AS nombres,
                DATE_FORMAT(s.soc_fecha_nacimiento, '%d-%m-%Y') AS fecha_nacimiento,
                s.soc_tel_cel,
                IF(p.pag_id_pago IS NOT NULL, 
                    CONCAT(DATE_FORMAT(p.pag_fecha_ini, '%d-%m-%Y'), ' al ', DATE_FORMAT(p.pag_fecha_fin, '%d-%m-%Y')), 
                    'Pago Vencido') AS estado_pago
            FROM 
                san_socios s
            LEFT JOIN 
                (SELECT 
                    p.pag_id_socio, 
                    MAX(p.pag_id_pago) AS pag_id_pago,
                    p.pag_fecha_ini,
                    p.pag_fecha_fin,
                    p.pag_status 
                 FROM 
                    san_pagos p
                 WHERE 
                    p.pag_id_empresa = $id_empresa 
                 AND 
                    '$fecha_mov' <= p.pag_fecha_fin 
                 AND 
                    p.pag_status = 'A'
                 GROUP BY 
                    p.pag_id_socio
                 ) p 
            ON 
                s.soc_id_socio = p.pag_id_socio
            WHERE 
                s.soc_id_empresa = $id_empresa 
            AND 
                MONTH(s.soc_fecha_nacimiento) = MONTH(CURRENT_DATE()) 
            AND 
                DAY(s.soc_fecha_nacimiento) >= DAY('$fecha_inicio_mes') 
            AND 
                DAY(s.soc_fecha_nacimiento) <= DAY('$fecha_fin_mes')";
    
    $resultado = mysqli_query($conexion, $query);

    if ($resultado) {
        $datos = "";
        $i = 1;
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $datos .= "<tr>
                        <td>$i</td>
                        <td>$fila[nombres]</td>
                        <td>$fila[fecha_nacimiento]</td>
                        <td>$fila[soc_tel_cel]</td>
                        <td>$fila[estado_pago]</td>
                      </tr>";
            $i++;
        }
        // Liberar el resultado
        mysqli_free_result($resultado);

        // Si no hay datos
        if ($i == 1) {
            $datos = "<tr><td colspan='5'>No hay socios que cumplan años este mes.</td></tr>";
        }

        return $datos;
    } else {
        // Si hay un error en la consulta
        return "<tr><td colspan='5'>Ocurrió un problema al obtener los datos de los socios: " . mysqli_error($conexion) . "</td></tr>";
    }
}

// Incluye este código en la parte donde deseas mostrar la lista de cumpleaños
$gbl_paginado = 10; // Establece aquí la cantidad de registros por página
$var_exito_cumpleaños = obtener_socios_cumpleaños();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Cumpleaños</title>
    <!-- Incluye los estilos de DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- Incluye jQuery -->
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <!-- Incluye el script de DataTables -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
</head>
<body>
<div class="row">
    <div class="col-md-12">
        <h4 class="text-info">
            <span class="glyphicon glyphicon-gift"></span> Lista de Cumpleaños
        </h4>
    </div>
</div>

<hr/>

<div class="row">
    <div class="col-md-12">
        <table id="tabla_cumpleaños" class="table table-hover table-condensed">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Fecha de Nacimiento</th>
                    <th>Teléfono</th>
                    <th>Estado del Pago</th>
                </tr>
            </thead>

            <tbody id="lista_cumpleaños">
            <?php echo $var_exito_cumpleaños; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Botón de regresar -->
<div class="row">
    <div class="col-md-12">
        <a href="javascript:history.back()" class="btn btn-primary">Regresar</a>
    </div>
</div>

<script>
$(document).ready( function () {
    $('#tabla_cumpleaños').DataTable({
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

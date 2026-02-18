<?php
function obtener_promociones_usadas($mes = null, $anio = null)
{
    global $conexion, $id_empresa;

    // Si no se pasa un mes, se utiliza el mes actual
    if (is_null($mes)) {
        $mes = date('m');
    }

    // Si no se pasa un año, se utiliza el año actual
    if (is_null($anio)) {
        $anio = date('Y');
    }

    // Consulta para obtener las promociones usadas junto con el nombre del socio y la fecha de uso
    $query = "SELECT 
                s.soc_id_socio AS id_socio,
                CONCAT(s.soc_apepat, ' ', s.soc_apemat, ' ', s.soc_nombres) AS nombres,
                cu.codigo_generado AS codigo_usado,
                DATE_FORMAT(cu.fecha_usado, '%d-%m-%Y') AS fecha_usado
            FROM 
                san_codigos_usados cu
            INNER JOIN 
                san_socios s ON cu.id_socio = s.soc_id_socio
            WHERE 
                cu.id_empresa = $id_empresa
            AND 
                MONTH(cu.fecha_usado) = $mes
            AND 
                YEAR(cu.fecha_usado) = $anio
            ORDER BY 
                cu.fecha_usado DESC";
    
    $resultado = mysqli_query($conexion, $query);

    if ($resultado) {
        $datos = "";
        $i = 1;
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $datos .= "<tr>
                        <td>$i</td>
                        <td>$fila[nombres]</td>
                        <td>$fila[codigo_usado]</td>
                        <td>$fila[fecha_usado]</td>
                      </tr>";
            $i++;
        }
        // Liberar el resultado
        mysqli_free_result($resultado);

        // Si no hay datos
        if ($i == 1) {
            $datos = "<tr><td colspan='4'>No hay promociones usadas para el mes y año seleccionados.</td></tr>";
        }

        return $datos;
    } else {
        // Si hay un error en la consulta
        return "<tr><td colspan='4'>Ocurrió un problema al obtener los datos: " . mysqli_error($conexion) . "</td></tr>";
    }
}

// Obtener el mes y año seleccionados del formulario, por defecto son el mes y año actuales
$mes_seleccionado = isset($_POST['mes']) ? $_POST['mes'] : date('m');
$anio_seleccionado = isset($_POST['anio']) ? $_POST['anio'] : date('Y');

// Incluye este código en la parte donde deseas mostrar la lista de promociones usadas
$var_exito_promociones = obtener_promociones_usadas($mes_seleccionado, $anio_seleccionado);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Promociones Usadas</title>
    <!-- Incluye los archivos CSS de DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- Incluye jQuery -->
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <!-- Incluye los archivos JS de DataTables -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
</head>
<body>

<div class="row">
    <div class="col-md-12">
        <h4 class="text-info">
            <span class="glyphicon glyphicon-tags"></span> Lista de Promociones Usadas
        </h4>
    </div>
</div>

<hr/>

<!-- Formulario para seleccionar el mes y año -->
<form method="post" action="">
    <div class="row">
        <div class="col-md-4">
            <label for="mes">Seleccionar Mes:</label>
            <select name="mes" id="mes" class="form-control" onchange="this.form.submit()">
                <?php
                $meses = [
                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                ];
                foreach ($meses as $num => $nombre) {
                    $selected = ($num == $mes_seleccionado) ? 'selected' : '';
                    echo "<option value='$num' $selected>$nombre</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="anio">Seleccionar Año:</label>
            <select name="anio" id="anio" class="form-control" onchange="this.form.submit()">
                <?php
                $anio_actual = date('Y');
                for ($anio = $anio_actual; $anio >= $anio_actual - 10; $anio--) {
                    $selected = ($anio == $anio_seleccionado) ? 'selected' : '';
                    echo "<option value='$anio' $selected>$anio</option>";
                }
                ?>
            </select>
        </div>
    </div>
</form>

<hr/>

<div class="row">
    <div class="col-md-12">
        <table id="tabla_promociones_usadas" class="table table-hover table-condensed">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Código Usado</th>
                    <th>Fecha</th>
                </tr>
            </thead>

            <tbody id="lista_promociones_usadas">
            <?php echo $var_exito_promociones; ?>
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

<!-- Inicializa DataTables -->
<script>
$(document).ready( function () {
    $('#tabla_promociones_usadas').DataTable({
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    });
} );
</script>

</body>
</html>

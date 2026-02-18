<?php
function obtener_referidos($mes = null, $anio = null)
{
    global $conexion;

    if (is_null($mes)) {
        $mes = date('m');
    }
    if (is_null($anio)) {
        $anio = date('Y');
    }

    $query = "SELECT 
                id, 
                CONCAT(apellido_paterno, ' ', apellido_materno, ' ', nombre) AS nombres,
                telefono, 
                correo_electronico, 
                codigo_promocion, 
                fecha_registro,
                id_socio,
                estado, 
                origen, 
                verificado, 
                fecha_verificacion, 
                codigo_invitacion, 
                fecha_envio, 
                estado_invitacion 
            FROM san_referidos
            WHERE MONTH(fecha_registro) = $mes AND YEAR(fecha_registro) = $anio 
            ORDER BY fecha_registro DESC";

    $resultado = mysqli_query($conexion, $query);

    if ($resultado) {
        $datos = "";
        $i = 1;
        while ($fila = mysqli_fetch_assoc($resultado)) {
            // Obtener el nombre completo del socio que refirió al referido
            $socio_query = "SELECT CONCAT(soc_nombres, ' ', soc_apepat, ' ', soc_apemat) AS socio_nombre
                            FROM san_socios WHERE soc_id_socio = " . $fila['id_socio'];
            $socio_resultado = mysqli_query($conexion, $socio_query);
            $socio = mysqli_fetch_assoc($socio_resultado);

            // Agregar a la tabla, ahora mostrando el nombre del socio
            $datos .= "<tr onclick=\"mostrarDetalles('" . htmlspecialchars(json_encode(array_merge($fila, ['socio_nombre' => $socio['socio_nombre']])), ENT_QUOTES, 'UTF-8') . "')\">
                <td>$i</td>
                <td>$fila[nombres]</td>
                <td>$fila[telefono]</td>
                <td>$fila[correo_electronico]</td>
                <td>" . htmlspecialchars($socio['socio_nombre'], ENT_QUOTES, 'UTF-8') . "</td>
            </tr>";
            $i++;
        }
        mysqli_free_result($resultado);
        // Si no hay resultados, asegúrate de tener la fila con 5 columnas
        return $datos ?: "<tr><td colspan='5'>No hay referidos registrados en este período.</td><td class='dummy-column'></td><td class='dummy-column'></td><td class='dummy-column'></td><td class='dummy-column'></td></tr>";
    } else {
        return "<tr><td colspan='5'>Error al obtener datos: " . mysqli_error($conexion) . "</td><td class='dummy-column'></td><td class='dummy-column'></td><td class='dummy-column'></td><td class='dummy-column'></td></tr>";
    }
}

$mes_seleccionado = isset($_POST['mes']) ? $_POST['mes'] : date('m');
$anio_seleccionado = isset($_POST['anio']) ? $_POST['anio'] : date('Y');
$lista_referidos = obtener_referidos($mes_seleccionado, $anio_seleccionado);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Referidos</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.min.css">
    <style>
        .dummy-column {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h4 class="text-info">Lista de Referidos</h4>
        <form method="post">
            <div class="form-row">
                <div class="col-md-4">
                    <label for="mes">Seleccionar Mes:</label>
                    <select name="mes" id="mes" class="form-control" onchange="this.form.submit()">
                        <?php
                        $meses = [
                            1 => 'Enero',
                            2 => 'Febrero',
                            3 => 'Marzo',
                            4 => 'Abril',
                            5 => 'Mayo',
                            6 => 'Junio',
                            7 => 'Julio',
                            8 => 'Agosto',
                            9 => 'Septiembre',
                            10 => 'Octubre',
                            11 => 'Noviembre',
                            12 => 'Diciembre'
                        ];
                        foreach ($meses as $num => $nombre) {
                            echo "<option value='$num' " . ($num == $mes_seleccionado ? 'selected' : '') . ">$nombre</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="anio">Seleccionar Año:</label>
                    <select name="anio" id="anio" class="form-control" onchange="this.form.submit()">
                        <?php
                        for ($anio = date('Y'); $anio >= date('Y') - 10; $anio--) {
                            echo "<option value='$anio' " . ($anio == $anio_seleccionado ? 'selected' : '') . ">$anio</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </form>
        <hr />
        <table id="tabla_referidos" class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Correo Electrónico</th>
                    <th>Socio que Refirió</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $lista_referidos; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal de Detalles -->
    <div class="modal fade" id="modalDetalles" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Referido</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="contenidoModal"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.3/js/jquery.dataTables.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tabla_referidos').DataTable({
                "language": {
                    "sSearch": "Buscar:"
                },
                "columns": [
                    null,
                    null,
                    null,
                    null,
                    null
                ]
            });
        });

        function mostrarDetalles(data) {
            var referido = JSON.parse(data);
            var contenido = `<strong>Nombre:</strong> ${referido.nombres}<br>
                     <strong>Teléfono:</strong> ${referido.telefono}<br>
                     <strong>Correo:</strong> ${referido.correo_electronico}<br>
                     <strong>Código Promoción:</strong> ${referido.codigo_promocion}<br>
                     <strong>Fecha Registro:</strong> ${referido.fecha_registro}<br>
                     <strong>Estado:</strong> ${referido.estado}<br>
                     <strong>Origen:</strong> ${referido.origen}<br>
                     <strong>Nombre Socio que Refirió:</strong> ${referido.socio_nombre}<br>
                     <strong>Verificado:</strong> ${referido.verificado}<br>
                     <strong>Fecha Verificación:</strong> ${referido.fecha_verificacion}<br>
                     <strong>Código Invitación:</strong> ${referido.codigo_invitacion}<br>
                     <strong>Fecha Envío:</strong> ${referido.fecha_envio}<br>
                     <strong>Estado Invitación:</strong> ${referido.estado_invitacion}`;
            $('#contenidoModal').html(contenido);
            $('#modalDetalles').modal('show');
        }
    </script>
</body>

</html>
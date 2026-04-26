<?php
// Validaciones de seguridad para los filtros (Casteo a entero para evitar SQLi)
$mes_filtro = isset($_GET['mes_filtro']) ? (int) $_GET['mes_filtro'] : (int) date('n');
$anio_filtro = isset($_GET['anio_filtro']) ? (int) $_GET['anio_filtro'] : (int) date('Y');

$meses_nombres = [
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

function obtener_socios_cumpleaños($mes_filtro, $meses_nombres)
{
    global $conexion, $id_empresa;

    // Consulta optimizada solo con los campos solicitados
    $query = "SELECT 
                soc_id_socio AS id_socio,
                CONCAT(soc_apepat, ' ', soc_apemat, ' ', soc_nombres) AS nombres,
                MONTH(soc_fecha_nacimiento) AS mes_nacimiento,
                soc_tel_cel,
                soc_correo
            FROM 
                san_socios
            WHERE 
                soc_id_empresa = $id_empresa 
            AND 
                MONTH(soc_fecha_nacimiento) = $mes_filtro";

    $resultado = mysqli_query($conexion, $query);

    if ($resultado) {
        $datos = "";
        $i = 1;

        while ($fila = mysqli_fetch_assoc($resultado)) {
            $num_mes = (int) $fila['mes_nacimiento'];
            $mes_texto = isset($meses_nombres[$num_mes]) ? $meses_nombres[$num_mes] : 'Desconocido';

            // Si no tiene correo, mostramos una advertencia con label
            $correo_badge = !empty($fila['soc_correo']) ? $fila['soc_correo'] : '<span class="label label-default">Sin correo</span>';
            $btn_correo = !empty($fila['soc_correo'])
                ? "<button class='btn btn-primary btn-xs' onclick=\"abrirModalCorreo('{$fila['soc_correo']}', '{$fila['nombres']}')\"><span class='glyphicon glyphicon-envelope'></span> Enviar Felicitación</button>"
                : "<button class='btn btn-default btn-xs' disabled><span class='glyphicon glyphicon-ban-circle'></span> No disponible</button>";

            $datos .= "<tr>
                        <td>$i</td>
                        <td>{$fila['nombres']}</td>
                        <td>$mes_texto</td>
                        <td>{$fila['soc_tel_cel']}</td>
                        <td>$correo_badge</td>
                        <td>$btn_correo</td>
                      </tr>";
            $i++;
        }
        mysqli_free_result($resultado);

        if ($i == 1) {
            $datos = "<tr><td colspan='6' class='text-center'>No hay socios que cumplan años en este mes.</td></tr>";
        }
        return $datos;
    } else {
        return "<tr><td colspan='6' class='text-danger'>Error SQL: " . mysqli_error($conexion) . "</td></tr>";
    }
}

function obtener_reporte_concentrado($anio_filtro, $meses_nombres)
{
    global $conexion, $id_empresa;

    // Consulta para el concentrado cruzando pagos del mes de cumpleaños en el año seleccionado
    $query = "SELECT 
                MONTH(s.soc_fecha_nacimiento) AS mes,
                COUNT(DISTINCT s.soc_id_socio) AS total_cumpleaneros,
                COUNT(DISTINCT p.pag_id_socio) AS total_pagaron
              FROM san_socios s
              LEFT JOIN san_pagos p 
                ON s.soc_id_socio = p.pag_id_socio 
                AND p.pag_status = 'A' 
                AND YEAR(p.pag_fecha_ini) = $anio_filtro 
                AND MONTH(p.pag_fecha_ini) = MONTH(s.soc_fecha_nacimiento)
              WHERE s.soc_id_empresa = $id_empresa
              GROUP BY MONTH(s.soc_fecha_nacimiento)
              ORDER BY mes ASC";

    $resultado = mysqli_query($conexion, $query);

    if ($resultado) {
        $datos = "";
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $mes_texto = isset($meses_nombres[$fila['mes']]) ? $meses_nombres[$fila['mes']] : 'Desconocido';
            $datos .= "<tr>
                        <td><strong>$mes_texto</strong></td>
                        <td><span class='badge'>{$fila['total_cumpleaneros']}</span></td>
                        <td><span class='label label-success'>{$fila['total_pagaron']}</span></td>
                      </tr>";
        }
        mysqli_free_result($resultado);
        return $datos ?: "<tr><td colspan='3' class='text-center'>Sin datos para este año.</td></tr>";
    } else {
        return "<tr><td colspan='3' class='text-danger'>Error SQL: " . mysqli_error($conexion) . "</td></tr>";
    }
}

$var_exito_cumpleaños = obtener_socios_cumpleaños($mes_filtro, $meses_nombres);
$var_exito_reporte = obtener_reporte_concentrado($anio_filtro, $meses_nombres);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Módulo de Cumpleaños</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h3 class="text-info">
                    <span class="glyphicon glyphicon-gift"></span> Gestión y Reporte de Cumpleaños
                </h3>
                <hr />
            </div>
        </div>

        <div class="row well well-sm">
            <form method="GET" action="">
                <div class="col-md-3">
                    <label>Filtrar Lista por Mes:</label>
                    <select name="mes_filtro" class="form-control">
                        <?php foreach ($meses_nombres as $num => $nombre): ?>
                        <option value="<?= $num ?>" <?= ($num == $mes_filtro) ? 'selected' : '' ?>><?= $nombre ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Filtrar Reporte por Año:</label>
                    <select name="anio_filtro" class="form-control">
                        <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= ($y == $anio_filtro) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-info btn-block">
                        <span class="glyphicon glyphicon-search"></span> Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <span class="glyphicon glyphicon-stats"></span> Concentrado del Año <?= $anio_filtro ?>
                        </h3>
                    </div>
                    <div class="panel-body table-responsive">
                        <table class="table table-hover table-condensed table-striped table-bordered text-center"
                            style="margin-bottom: 0;">
                            <thead>
                                <tr class="success">
                                    <th class="text-center">Mes</th>
                                    <th class="text-center">N° Cumpleañeros</th>
                                    <th class="text-center">N° Pagaron su Mes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo $var_exito_reporte; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <span class="glyphicon glyphicon-list-alt"></span> Cumpleañeros de
                            <?= $meses_nombres[$mes_filtro] ?>
                        </h3>
                    </div>
                    <div class="panel-body table-responsive">
                        <table id="tabla_cumpleaños"
                            class="table table-hover table-condensed table-bordered text-center" style="width: 100%;">
                            <thead>
                                <tr class="info">
                                    <th class="text-center">#</th>
                                    <th class="text-center">Nombre Completo</th>
                                    <th class="text-center">Mes</th>
                                    <th class="text-center">Teléfono</th>
                                    <th class="text-center">Correo Electrónico</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo $var_exito_cumpleaños; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" style="margin-top: 20px;">
            <div class="col-md-12">
                <a href="javascript:history.back()" class="btn btn-default"><span
                        class="glyphicon glyphicon-arrow-left"></span> Regresar</a>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCorreo" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><span class="glyphicon glyphicon-envelope"></span> Enviar Felicitación</h4>
                </div>
                <div class="modal-body">
                    <form id="formCorreoFelicitacion">
                        <div class="form-group">
                            <label>Para:</label>
                            <input type="email" id="correoDestino" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Socio:</label>
                            <input type="text" id="nombreSocio" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Mensaje Personalizado:</label>
                            <textarea id="mensajeCorreo" class="form-control"
                                rows="5">¡Hola! De parte de todo el equipo de Sandys Gym queremos desearte un muy feliz cumpleaños. ¡Que pases un excelente día lleno de energía!</textarea>
                            <p class="help-block"><small>Este mensaje se incrustará en la plantilla HTML
                                    correspondiente.</small></p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="enviarCorreoAjax()">
                        <span class="glyphicon glyphicon-send"></span> Enviar Correo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js">
    </script>
    <script>
    $(document).ready(function() {
        $('#tabla_cumpleaños').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json" // Más limpio cargar la traducción oficial
            }
        });
    });

    function abrirModalCorreo(correo, nombre) {
        $('#correoDestino').val(correo);
        $('#nombreSocio').val(nombre);
        $('#modalCorreo').modal('show');
    }

    function enviarCorreoAjax() {
        var correo = $('#correoDestino').val();
        var nombre = $('#nombreSocio').val();
        var mensaje = $('#mensajeCorreo').val();

        // Bloquear botón para evitar doble envío
        var btn = $('#modalCorreo .btn-primary');
        var btnTextoOriginal = btn.html();
        btn.html('<span class="glyphicon glyphicon-refresh form-control-feedback"></span> Enviando...').prop('disabled',
            true);

        $.ajax({
            url: 'secciones/cumple/correo.php', // Ruta relativa desde la vista
            type: 'POST',
            data: {
                email: correo,
                socio: nombre,
                mensaje: mensaje
            },
            dataType: 'json',
            success: function(response) {
                if (response.exito) {
                    alert("¡Correo enviado exitosamente a " + nombre + "!");
                    $('#modalCorreo').modal('hide');
                } else {
                    alert("Error al enviar el correo: " + response.mensaje);
                }
            },
            error: function() {
                alert("Ocurrió un error de red o de servidor al intentar enviar el correo.");
            },
            complete: function() {
                // Restaurar botón
                btn.html(btnTextoOriginal).prop('disabled', false);
            }
        });
    }
    </script>
</body>

</html>
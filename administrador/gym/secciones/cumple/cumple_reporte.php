<?php
$anio_filtro = isset($_GET['anio_filtro']) ? (int) $_GET['anio_filtro'] : (int) date('Y');

// Definición requerida para el mapeo de meses en este módulo
$meses_nombres = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

function obtener_reporte_concentrado($anio_filtro, $meses_nombres) {
    global $conexion, $id_empresa;
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
    $datos = "";
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $mes_texto = $meses_nombres[$fila['mes']] ?? 'Desconocido';
            $datos .= "<tr>
                        <td><strong>$mes_texto</strong></td>
                        <td><span class='badge'>{$fila['total_cumpleaneros']}</span></td>
                        <td><span class='label label-success'>{$fila['total_pagaron']}</span></td>
                      </tr>";
        }
        return $datos ?: "<tr><td colspan='3' class='text-center'>Sin datos registrados para este año.</td></tr>";
    }
    return "<tr><td colspan='3' class='text-danger'>Error SQL: " . mysqli_error($conexion) . "</td></tr>";
}
?>

<div class="well well-sm">
    <form method="GET" action="">
        <input type="hidden" name="s" value="<?= htmlspecialchars($_GET['s'] ?? '') ?>">
        <input type="hidden" name="i" value="<?= htmlspecialchars($_GET['i'] ?? '') ?>">
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="input-group">
                    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span> Año</span>
                    <select name="anio_filtro" class="form-control input-sm">
                        <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= ($y == $anio_filtro) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-success btn-sm">
                            <span class="glyphicon glyphicon-search"></span> Filtrar
                        </button>
                    </span>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="panel panel-success">
    <div class="panel-heading">
        <h3 class="panel-title"><span class="glyphicon glyphicon-stats"></span> Concentrado Anual: <?= $anio_filtro ?></h3>
    </div>
    <div class="panel-body table-responsive">
        <table class="table table-hover table-condensed table-striped table-bordered text-center">
            <thead>
                <tr class="success">
                    <th class="text-center">Mes</th>
                    <th class="text-center">N° Cumpleañeros</th>
                    <th class="text-center">N° Pagaron Mes</th>
                </tr>
            </thead>
            <tbody>
                <?= obtener_reporte_concentrado($anio_filtro, $meses_nombres) ?>
            </tbody>
        </table>
    </div>
</div>
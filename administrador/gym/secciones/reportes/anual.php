<?php
    // --- INICIALIZACIÓN Y OBTENCIÓN DE DATOS ---
    $año = request_var('año_calcular', date('Y'));
    $opciones_año = combo_años($año);

    // Array central para almacenar los resúmenes de ingresos
    $resumen_ingresos = [];
    $errores = [];

    // Obtener datos de cada categoría
    $importe_mens = obtener_importe_mensualidades($año, 'A');
    if ($importe_mens['num'] == 1) {
        $resumen_ingresos['mensualidades'] = $importe_mens;
    } else {
        $errores[] = $importe_mens['msj'];
    }

    $importe_hors = obtener_importe_por_horas('HORA', $año, 'A');
    if ($importe_hors['num'] == 1) {
        $resumen_ingresos['horas'] = $importe_hors;
    } else {
        $errores[] = $importe_hors['msj'];
    }

    $importe_hdia = obtener_importe_por_horas('VISITA', $año, 'A');
    if ($importe_hdia['num'] == 1) {
        // Las visitas no tienen desglose de tarjeta/comisión
        $importe_hdia['tar_com'] = 0;
        $resumen_ingresos['visitas'] = $importe_hdia;
    } else {
        $errores[] = $importe_hdia['msj'];
    }

    $importe_prep = obtener_importe_monedero($año, 'A'); 
    if ($importe_prep['num'] == 1) {
        $importe_prep['tar_com'] = 0;
        $resumen_ingresos['prepagos'] = $importe_prep;
    } else {
        $errores[] = $importe_prep['msj'];
    }
    
    $importe_vent = obtener_importe_venta_efectivo($año, 'A');
    if ($importe_vent['num'] == 1) {
        $resumen_ingresos['articulos'] = $importe_vent;
    } else {
        $errores[] = $importe_vent['msj'];
    }

    // Mostrar todos los errores juntos
    if (!empty($errores)) {
        foreach ($errores as $error) {
            mostrar_mensaje_div($error, 'danger');
        }
    }
    
    // --- CÁLCULO DE TOTALES (MÉTODO REFACTORIZADO) ---
    $totales = [
        'total' => 0,
        'efectivo' => 0,
        'tar_com' => 0,
    ];

    foreach ($resumen_ingresos as $ingreso) {
        $totales['total']    += $ingreso['total'] ?? 0;
        $totales['efectivo'] += $ingreso['efectivo'] ?? 0;
        $totales['tar_com']  += $ingreso['tar_com'] ?? 0;
    }

    // --- CÁLCULO DE GASTOS Y UTILIDAD ---
    $gastos_display = ['importe' => "-/-", 'iva' => "-/-", 'descuento' => "-/-", 'total' => "-/-"];
    $v_utilidad = 0;
    
    $importe_gastos = obtener_gastos($año, 'A');

    if ($importe_gastos['num'] == 1 && isset($importe_gastos['msj']['total'])) {
        $gastos_reales = $importe_gastos['msj'];
        $gastos_display['importe']   = number_format($gastos_reales['importe'], 2);
        $gastos_display['iva']       = number_format($gastos_reales['iva'], 2);
        $gastos_display['descuento'] = number_format($gastos_reales['descuento'], 2);
        $gastos_display['total']     = number_format($gastos_reales['total'], 2);
        
        // CORRECCIÓN: Se usa la variable normal $v_utilidad, no $$v_utilidad
        $v_utilidad = $totales['total'] - $gastos_reales['total'];
    } else {
        // Si no hay gastos, la utilidad es igual al total de ingresos
        $v_utilidad = $totales['total'];
    }
    
    // Formatear la utilidad para mostrarla
    $v_utilidad_formato = number_format($v_utilidad, 2);

    // --- OBTENER LA TABLA DETALLADA POR MES ---
    // Esta función ya fue corregida en el paso anterior
    $lista_ventas = lista_ventas_del_mes($año);
?>

<div class="row">
    <div class="col-md-12">
        <h4 class="text-info">
            <span class="glyphicon glyphicon-tasks"></span> Informe de ingresos y gastos
        </h4>
    </div>
</div>

<hr/>

<form role="form" method="post" action=".?s=<?= $seccion ?>&i=<?= $item ?>">
    <div class="row">
        <label class="col-md-3">Selecciona el Año</label>
        <div class="col-md-3">
            <select name="año_calcular" class="form-control">
                <?= $opciones_año ?>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-offset-3 col-md-3">
            <input type="submit" class="btn btn-primary btn-sm" value="Buscar" name="enviar"/>
        </div>
    </div>
</form>

<hr/>

<div class="row">
    <div class="col-md-6">
        <table class="table table-hover h6">
            <thead>
                <tr class="active">
                    <th>Descripción</th>
                    <th class="text-right">Efectivo</th>
                    <th class="text-right">Tarjeta</th>
                    <th class="text-right">Importe</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total en mensualidades</td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['mensualidades']['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['mensualidades']['tar_com'] ?? 0, 2) ?></td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['mensualidades']['total'] ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <td>Total en entradas por horas</td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['horas']['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['horas']['tar_com'] ?? 0, 2) ?></td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['horas']['total'] ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <td>Total en entradas por visitas</td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['visitas']['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right">-/-</td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['visitas']['total'] ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <td>Total en venta de articulos</td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['articulos']['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['articulos']['tar_com'] ?? 0, 2) ?></td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['articulos']['total'] ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <td>Total en prepagos</td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['prepagos']['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right">-/-</td>
                    <td class="text-right">$<?= number_format($resumen_ingresos['prepagos']['total'] ?? 0, 2) ?></td>
                </tr>
                <tr class="success text-bold">
                    <td class="text-right">Total de ingresos</td>
                    <td class="text-right">$<?= number_format($totales['efectivo'], 2) ?></td>
                    <td class="text-right">$<?= number_format($totales['tar_com'], 2) ?></td>
                    <td class="text-right">$<?= number_format($totales['total'], 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="col-md-6">
        <table class="table table-hover h6">
            <thead>
                <tr class="active">
                    <th>Descripción</th>
                    <th class="text-right">Importe</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Importe</td>
                    <td class="text-right">$<?= $gastos_display['importe'] ?></td>
                </tr>
                <tr>
                    <td>IVA</td>
                    <td class="text-right">$<?= $gastos_display['iva'] ?></td>
                </tr>
                <tr>
                    <td>Descuento</td>
                    <td class="text-right">$<?= $gastos_display['descuento'] ?></td>
                </tr>
                <tr>
                    <td class="text-right"><strong>Total en Gastos</strong></td>
                    <td class="text-right"><strong>$<?= $gastos_display['total'] ?></strong></td>
                </tr>
            </tbody>
        </table>
        <br/>
        <h3 class="text-info"><strong>Utilidad: $<?= $v_utilidad_formato ?></strong></h3>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h5 class="text-primary"><strong>Movimiento en ventas de servicios por días del mes.</strong></h5>
        <table class="table table-hover h6">
            <thead>
                <tr class="active">
                    <th>#</th>
                    <th>Fecha</th>
                    <th class="text-right">Mensualidades</th>
                    <th class="text-right">Horas</th>
                    <th class="text-right">Visitas</th>
                    <th class="text-right">Artículos</th>
                    <th class="text-right">Prepagos</th>
                    <th class="text-right">Cortes</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?= $lista_ventas ?>
            </tbody>
        </table>
    </div>
</div>
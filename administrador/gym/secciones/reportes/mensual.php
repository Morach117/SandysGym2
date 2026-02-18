<?php
// ARCHIVO: reporte_mensual.php

// =========================================================================================
// === LÓGICA DE LA PÁGINA ===
// =========================================================================================

// Configuración inicial de la fecha
$año = request_var('año_calcular', date('Y'));
$mes = request_var('mes_calcular', date('m'));
$opciones_año = combo_años($año);
$opciones_mes = combo_meses($mes);
$mes_ganancia = "$año-$mes";

// --- OBTENCIÓN DE DATOS PARA EL RESUMEN ---
$res_mens = obtener_resumen_mensualidades($mes_ganancia);
$res_hors = obtener_resumen_horas_visitas($mes_ganancia, 'HORA');
$res_hdia = obtener_resumen_horas_visitas($mes_ganancia, 'VISITA');
$res_vent = obtener_resumen_venta_articulos($mes_ganancia);
$res_prep = obtener_resumen_abonos_monedero($mes_ganancia);
$res_gast = obtener_resumen_gastos($mes_ganancia);

// --- CÁLCULO DE TOTALES GENERALES ---
$total_ingresos = ($res_mens['total'] ?? 0) + ($res_hors['total'] ?? 0) + ($res_hdia['total'] ?? 0) + ($res_vent['total'] ?? 0) + ($res_prep['total'] ?? 0);
$total_gastos_num = $res_gast['total'] ?? 0;
$utilidad_num = $total_ingresos - $total_gastos_num;

// --- GENERACIÓN DEL HTML PARA LA TABLA DE DETALLES ---
$lista_ventas_html = lista_ventas_del_mes($mes_ganancia);

?>
<div class="row">
    <div class="col-md-12">
        <h4 class="text-info"><span class="glyphicon glyphicon-tasks"></span> Informe de Ingresos y Gastos</h4>
    </div>
</div>

<hr />

<form role="form" method="post" action=".?s=<?= htmlspecialchars($seccion) ?>&i=<?= htmlspecialchars($item) ?>">
    <div class="row" style="margin-bottom: 10px;">
        <label class="col-md-2">Selecciona el Año</label>
        <div class="col-md-3">
            <select name="año_calcular" class="form-control"><?= $opciones_año ?></select>
        </div>
    </div>
    <div class="row" style="margin-bottom: 10px;">
        <label class="col-md-2">Selecciona el Mes</label>
        <div class="col-md-3">
            <select name="mes_calcular" class="form-control"><?= $opciones_mes ?></select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-offset-2 col-md-3">
            <input type="submit" class="btn btn-primary btn-sm" value="Buscar" name="enviar" />
        </div>
    </div>
</form>

<hr />

<div class="row">
    <div class="col-md-7">
        <table class="table table-hover h6">
            <thead>
                <tr class="active">
                    <th>Descripción de Ingresos</th>
                    <th class="text-right">Efectivo</th>
                    <th class="text-right">Tarjeta</th>
                    <th class="text-right">Importe Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total en mensualidades</td>
                    <td class="text-right">$<?= number_format($res_mens['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right">$<?= number_format(($res_mens['tarjeta'] ?? 0) + ($res_mens['comision'] ?? 0), 2) ?></td>
                    <td class="text-right"><strong>$<?= number_format($res_mens['total'] ?? 0, 2) ?></strong></td>
                </tr>
                <tr>
                    <td>Total en entradas por horas</td>
                    <td class="text-right">$<?= number_format($res_hors['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right">$<?= number_format(($res_hors['tarjeta'] ?? 0) + ($res_hors['comision'] ?? 0), 2) ?></td>
                    <td class="text-right"><strong>$<?= number_format($res_hors['total'] ?? 0, 2) ?></strong></td>
                </tr>
                <tr>
                    <td>Total en entradas por visitas</td>
                    <td class="text-right">$<?= number_format($res_hdia['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right">$<?= number_format(($res_hdia['tarjeta'] ?? 0) + ($res_hdia['comision'] ?? 0), 2) ?></td>
                    <td class="text-right"><strong>$<?= number_format($res_hdia['total'] ?? 0, 2) ?></strong></td>
                </tr>
                <tr>
                    <td>Total en venta de articulos</td>
                    <td class="text-right">$<?= number_format($res_vent['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right">$<?= number_format(($res_vent['tarjeta'] ?? 0) + ($res_vent['comision'] ?? 0), 2) ?></td>
                    <td class="text-right"><strong>$<?= number_format($res_vent['total'] ?? 0, 2) ?></strong></td>
                </tr>
                <tr>
                    <td>Total en Abono a Monedero (Prepagos)</td>
                    <td class="text-right">$<?= number_format($res_prep['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right">$0.00</td>
                    <td class="text-right"><strong>$<?= number_format($res_prep['total'] ?? 0, 2) ?></strong></td>
                </tr>
                <tr class="success text-bold">
                    <td class="text-right" colspan="3">Total de Ingresos</td>
                    <td class="text-right">$<?= number_format($total_ingresos, 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="col-md-5">
        <table class="table table-hover h6">
            <thead>
                <tr class="active">
                    <th>Descripción de Gastos</th>
                    <th class="text-right">Importe</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Importe Base</td>
                    <td class="text-right">$<?= number_format($res_gast['importe_base'] ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <td>IVA</td>
                    <td class="text-right">$<?= number_format($res_gast['iva'] ?? 0, 2) ?></td>
                </tr>
                <tr class="active text-bold">
                    <td class="text-right">Total en Gastos</td>
                    <td class="text-right text-danger">$<?= number_format($total_gastos_num, 2) ?></td>
                </tr>
            </tbody>
        </table>
        <br />
        <h3 class="text-info"><strong>Utilidad del Mes: $<?= number_format($utilidad_num, 2) ?></strong></h3>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h5 class="text-primary"><strong>Movimiento detallado por día del mes</strong></h5>
        <table class="table table-hover h6">
            <thead>
                <tr class="active">
                    <th>#</th>
                    <th>Fecha</th>
                    <th class="text-right">Mensualidades</th>
                    <th class="text-right">Horas</th>
                    <th class="text-right">Visitas</th>
                    <th class="text-right">Artículos</th>
                    <th class="text-right">Monedero</th>
                    <th class="text-right">Total del Día</th>
                    <th class="text-right text-danger">Gastos</th>
                    <th class="text-right">Cortes</th>
                    <th class="text-right">Total Neto</th>
                </tr>
            </thead>
            <tbody>
                <?= $lista_ventas_html ?>
            </tbody>
        </table>
    </div>
</div>
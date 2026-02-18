<?php
// --- INICIALIZACIÓN DE VARIABLES ---
$fecha_mov   = request_var('fecha_mov', date('d-m-Y'));
$v_id_cajero = request_var('cajero', 0);
$accion      = request_var('accion', '');
$enviar      = request_var('enviar', '');

// --- PROCESAMIENTO DE ACCIONES (POST, GET) ---
if ($accion == 'e') {
    eliminar_corte(); // Función segura del archivo de funciones
}

if ($enviar) {
    // Se pasa la fecha como cadena, la función se encarga de formatearla
    $guardar = realizar_corte($id_empresa, $fecha_mov); // Función segura

    if ($guardar['num'] == 1) {
        // Redirige para evitar reenvío del formulario
        header("Location: .?s=$seccion&i=$item&fecha_mov=$fecha_mov&cajero=$v_id_cajero");
        exit;
    } else {
        mostrar_mensaje_div($guardar['num'] . ". " . $guardar['msj'], 'danger');
    }
}

// --- OBTENCIÓN DE DATOS DEL DÍA ---
// Se agrupan todos los tipos de ingresos para facilitar los cálculos.
$ingresos_del_dia = [
    'mensualidades' => obtener_importe_mensualidades($fecha_mov, 'D', $v_id_cajero),
    'horas'         => obtener_importe_por_horas('HORA', $fecha_mov, 'D', $v_id_cajero),
    'visitas'       => obtener_importe_por_horas('VISITA', $fecha_mov, 'D', $v_id_cajero),
    'articulos'     => obtener_importe_venta_efectivo($fecha_mov, 'D', $v_id_cajero),
    'abonos'        => obtener_importe_monedero($fecha_mov, 'D', $v_id_cajero) // Abonos a monedero
];

// --- CÁLCULOS DE TOTALES ---
$totales = [
    'efectivo'          => 0.0,
    'tarjeta_neto'      => 0.0,
    'comision'          => 0.0,
    'tarjeta_total'     => 0.0, // Tarjeta + Comisión
    'ingreso_real'      => 0.0, // Suma de todo el dinero nuevo (efectivo + tarjeta)
    'monedero_gastado'  => 0.0  // Total de valor pagado CON monedero
];

foreach ($ingresos_del_dia as $ingreso) {
    // Suma de dinero real que entró a caja
    $totales['efectivo']         += $ingreso['efectivo'] ?? 0;
    $totales['tarjeta_neto']     += $ingreso['tarjeta'] ?? 0;
    $totales['comision']         += $ingreso['comision'] ?? 0;

    // Suma del valor de servicios pagados con saldo existente
    $totales['monedero_gastado'] += $ingreso['monedero'] ?? 0;
}

// Se calculan los totales finales
$totales['tarjeta_total'] = $totales['tarjeta_neto'] + $totales['comision'];
$totales['ingreso_real']  = $totales['efectivo'] + $totales['tarjeta_total'];

// --- DATOS PARA LA VISTA ---
$lista_cortes   = lista_cortes_del_dia($fecha_mov);
$importe_cortes = total_importe_corte_del_dia($fecha_mov, $v_id_cajero);
$cmb_cajeros    = combo_cajeros($v_id_cajero);

// === AJUSTE LÓGICO SOLICITADO ===
// El pendiente de retirar se calcula sobre el INGRESO TOTAL del día (efectivo + tarjeta)
$por_retirar = $totales['ingreso_real'] - $importe_cortes;

?>
<div class="row">
    <div class="col-md-12">
        <h4 class="text-info">
            <span class="glyphicon glyphicon-adjust"></span> Corte diario
        </h4>
    </div>
</div>

<hr />

<form action=".?s=<?= $seccion ?>&i=<?= $item ?>" method="post">
    <div class="row">
        <div class="col-md-4 form-group">
            <label>Fecha</label>
            <input type="text" name="fecha_mov" value="<?= htmlspecialchars($fecha_mov) ?>" class="form-control" id="pag_fecha_pago" />
        </div>

        <div class="col-md-4 form-group">
            <label>Cajero</label>
            <select name="cajero" class="form-control">
                <option value="0">Todos...</option>
                <?= $cmb_cajeros ?>
            </select>
        </div>
        <div class="col-md-4 form-group" style="padding-top: 25px;">
            <input type="submit" name="buscar" value="Buscar" class="btn btn-primary" />
        </div>
    </div>
</form>

<div class="row">
    <div class="col-md-12">
        <h5 class="text-primary"><strong>Información para el corte de caja del día: <?= fecha_generica($fecha_mov); ?></strong></h5>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <table class="table table-hover h6">
            <thead>
                <tr class="active">
                    <th>Descripción de Ingresos</th>
                    <th class="text-right">Monedero</th>
                    <th class="text-right">Efectivo</th>
                    <th class="text-right">Tarjeta</th>
                    <th class="text-right">Importe</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Mensualidades</td>
                    <td class="text-right text-primary"><?= '$' . number_format($ingresos_del_dia['mensualidades']['monedero'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['mensualidades']['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['mensualidades']['tar_com'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['mensualidades']['total'] ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <td>Horas</td>
                    <td class="text-right text-primary"><?= '$' . number_format($ingresos_del_dia['horas']['monedero'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['horas']['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['horas']['tar_com'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['horas']['total'] ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <td>Visitas</td>
                    <td class="text-right text-primary"><?= '$' . number_format($ingresos_del_dia['visitas']['monedero'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['visitas']['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['visitas']['tar_com'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['visitas']['total'] ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <td>Artículos</td>
                    <td class="text-right text-primary"><?= '$' . number_format($ingresos_del_dia['articulos']['monedero'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['articulos']['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['articulos']['tar_com'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['articulos']['total'] ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <td>Abono a Monedero</td>
                    <td class="text-right">-/-</td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['abonos']['efectivo'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['abonos']['tar_com'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($ingresos_del_dia['abonos']['total'] ?? 0, 2) ?></td>
                </tr>
                <tr class="success text-bold">
                    <td class="text-right">Total Ingresos</td>
                    <td class="text-right text-primary"><?= '$' . number_format($totales['monedero_gastado'], 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($totales['efectivo'], 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($totales['tarjeta_total'], 2) ?></td>
                    <td class="text-right"><?= '$' . number_format($totales['ingreso_real'], 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="col-md-5">
        <form action=".?s=<?= $seccion ?>&i=<?= $item ?>" method="post">
            <div class="form-group">
                <label>Importe a Retirar</label>
                <input type="text" name="cor_importe" class="form-control" required="required" maxlength="10" placeholder="<?= number_format($por_retirar, 2) ?>" />
            </div>
            <div class="form-group">
                <label>Notas</label>
                <input type="text" name="cor_observaciones" class="form-control" maxlength="100" />
            </div>
            <div class="form-group">
                <input type="hidden" name="enviar" value="true" />
                <input type="hidden" name="fecha_mov" value="<?= htmlspecialchars($fecha_mov) ?>" />
                <input type="hidden" name="cajero" value="<?= htmlspecialchars($v_id_cajero) ?>" />
                <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Procesar Retiro</button>
            </div>
        </form>
        <hr />
        
        <div class="row h5">
            <div class="col-md-7"><strong>Total de Ingresos del Día:</strong></div>
            <div class="col-md-5 text-right"><strong>$<?= number_format($totales['ingreso_real'], 2) ?></strong></div>
        </div>
        <div class="row h5">
            <div class="col-md-7">(-) Retiros Realizados:</div>
            <div class="col-md-5 text-right">$<?= number_format($importe_cortes, 2) ?></div>
        </div>
        <div class="row h4 text-bold text-danger">
            <div class="col-md-7">Efectivo en caja (a retirar):</div>
            <div class="col-md-5 text-right">$<?= number_format($por_retirar, 2) ?></div>
        </div>
        </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h5 class="text-primary"><strong>Cortes de caja realizados</strong></h5>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <table class="table table-hover h6">
            <thead>
                <tr class="active">
                    <th>#</th>
                    <th>Opc.</th>
                    <th>Movimiento</th>
                    <th>Venta</th>
                    <th>Usuario</th>
                    <th>Cajero</th>
                    <th>Tipo</th>
                    <th class="text-right">Caja</th>
                    <th class="text-right">Importe</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?= $lista_cortes ?>
            </tbody>
        </table>
    </div>
</div>
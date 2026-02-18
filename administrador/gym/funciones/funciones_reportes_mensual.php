<?php
// ARCHIVO: reporte_funciones.php
// --> CORRECCIÓN: Optimizado para mayor eficiencia y validada la lógica financiera.

// =========================================================================================
// === SECCIÓN 1: FUNCIONES DE RESUMEN (YA SON CORRECTAS Y SEGURAS) ===
// =========================================================================================

function obtener_resumen_mensualidades($fecha_mov) {
    global $conexion, $id_empresa;
    $query = "SELECT 
                SUM(pag_efectivo + pag_tarjeta) as total,
                SUM(pag_efectivo) as efectivo,
                SUM(pag_tarjeta) as tarjeta,
                SUM(pag_comision) as comision
              FROM san_pagos
              WHERE DATE_FORMAT(pag_fecha_pago, '%Y-%m') = ? AND pag_id_empresa = ? AND pag_status = 'A'";
    
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $fecha_mov, $id_empresa);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: ['total'=>0,'efectivo'=>0,'tarjeta'=>0,'comision'=>0];
    }
    return ['total'=>0,'efectivo'=>0,'tarjeta'=>0,'comision'=>0];
}

function obtener_resumen_horas_visitas($fecha_mov, $tipo) {
    global $conexion, $id_empresa;
    $query = "SELECT 
                SUM(hor_efectivo + hor_tarjeta) as total,
                SUM(hor_efectivo) as efectivo,
                SUM(hor_tarjeta) as tarjeta,
                SUM(hor_comision) as comision
              FROM san_horas
              INNER JOIN san_servicios ON ser_id_servicio = hor_id_servicio
              WHERE DATE_FORMAT(hor_fecha, '%Y-%m') = ? AND hor_id_empresa = ? AND hor_status = 'A' AND ser_clave = ?";
    
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "sis", $fecha_mov, $id_empresa, $tipo);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: ['total'=>0,'efectivo'=>0,'tarjeta'=>0,'comision'=>0];
    }
    return ['total'=>0,'efectivo'=>0,'tarjeta'=>0,'comision'=>0];
}

function obtener_resumen_venta_articulos($fecha_mov) {
    global $conexion, $id_empresa;
    $query = "SELECT
                SUM(ven_total_efectivo + ven_total_tarjeta) as total,
                SUM(ven_total_efectivo) as efectivo,
                SUM(ven_total_tarjeta) as tarjeta,
                SUM(ven_comision) as comision
              FROM san_venta
              WHERE DATE_FORMAT(ven_fecha, '%Y-%m') = ? AND ven_id_empresa = ? AND ven_status = 'V'";

    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $fecha_mov, $id_empresa);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: ['total'=>0,'efectivo'=>0,'tarjeta'=>0,'comision'=>0];
    }
    return ['total'=>0,'efectivo'=>0,'tarjeta'=>0,'comision'=>0];
}

function obtener_resumen_abonos_monedero($fecha_mov) {
    global $conexion, $id_empresa;
    $query = "SELECT SUM(d.pred_importe) as total
              FROM san_prepago_detalle AS d
              INNER JOIN san_socios AS s ON d.pred_id_socio = s.soc_id_socio
              WHERE DATE_FORMAT(d.pred_fecha, '%Y-%m') = ? AND s.soc_id_empresa = ? AND d.pred_movimiento = 'S'";
    
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $fecha_mov, $id_empresa);
        mysqli_stmt_execute($stmt);
        $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return ['total' => $result['total'] ?? 0, 'efectivo' => $result['total'] ?? 0, 'tarjeta' => 0, 'comision' => 0];
    }
    return ['total'=>0,'efectivo'=>0,'tarjeta'=>0,'comision'=>0];
}

function obtener_resumen_gastos($fecha_mov) {
    global $conexion, $id_empresa;
    $query = "SELECT 
                SUM(gas_importe) as total,
                SUM(gas_total - gas_iva) as importe_base,
                SUM(gas_iva) as iva
              FROM san_gastos
              WHERE DATE_FORMAT(gas_fecha_captura, '%Y-%m') = ? AND gas_id_empresa = ?";
    
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $fecha_mov, $id_empresa);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: ['total' => 0, 'importe_base' => 0, 'iva' => 0];
    }
    return ['total' => 0, 'importe_base' => 0, 'iva' => 0];
}

// =========================================================================================
// === SECCIÓN 2: FUNCIÓN PRINCIPAL OPTIMIZADA Y AUXILIARES ===
// =========================================================================================

/**
 * Genera el HTML de la tabla con el detalle de ventas diarias del mes.
 * --> MODIFICADO: Se agrega la columna "Total del Día" con su respectiva lógica.
 */
function lista_ventas_del_mes($mes_movimiento)
{
    global $conexion;
    list($año, $mes) = explode('-', $mes_movimiento);
    $datos = ""; $colspan = 11; $contador = 1;

    // 1. Obtención de todos los datos del mes
    $d_mensual = lista_detalle_mensualidades($mes_movimiento);
    $d_horas = lista_detalle_horas_visitas($mes_movimiento, 'HORA');
    $d_visitas = lista_detalle_horas_visitas($mes_movimiento, 'VISITA');
    $d_ventas = lista_detalle_venta_articulos($mes_movimiento);
    $d_prepagos = lista_detalle_prepagos($mes_movimiento);
    $d_gastos = lista_detalle_gastos($mes_movimiento);
    $d_cortes = lista_cortes($mes_movimiento);

    // 2. Mapeo de datos por día para un acceso instantáneo
    $datos_por_dia = [];
    foreach ($d_mensual as $fila)  { $datos_por_dia[$fila['dia']]['mensual'] = $fila['importe']; }
    foreach ($d_horas as $fila)    { $datos_por_dia[$fila['dia']]['horas'] = $fila['importe']; }
    foreach ($d_visitas as $fila)  { $datos_por_dia[$fila['dia']]['visitas'] = $fila['importe']; }
    foreach ($d_ventas as $fila)   { $datos_por_dia[$fila['dia']]['articulos'] = $fila['importe']; }
    foreach ($d_prepagos as $fila) { $datos_por_dia[$fila['dia']]['prepagos'] = $fila['importe']; }
    foreach ($d_gastos as $fila)   { $datos_por_dia[$fila['dia']]['gastos'] = $fila['importe']; }
    foreach ($d_cortes as $fila)   { $datos_por_dia[$fila['dia']]['cortes'] = $fila['importe']; }
    
    // 3. Inicialización de totales
    $tot_mensual = 0; $tot_horas = 0; $tot_visitas = 0; $tot_articulos = 0;
    $tot_prepagos = 0; $tot_gastos = 0; $tot_cortes = 0; $tot_total = 0;
    $tot_total_dia_ingresos = 0; // Nuevo total
    
    $dias_en_el_mes = cal_days_in_month(CAL_GREGORIAN, (int)$mes, (int)$año);

    // 4. Bucle principal para construir las filas de la tabla
    for ($i = 1; $i <= $dias_en_el_mes; $i++) {
        // Acceso directo y rápido a los datos del día
        $fila_mensual   = $datos_por_dia[$i]['mensual'] ?? 0;
        $fila_horas     = $datos_por_dia[$i]['horas'] ?? 0;
        $fila_visitas   = $datos_por_dia[$i]['visitas'] ?? 0;
        $fila_articulos = $datos_por_dia[$i]['articulos'] ?? 0;
        $fila_prepagos  = $datos_por_dia[$i]['prepagos'] ?? 0;
        $fila_gastos    = $datos_por_dia[$i]['gastos'] ?? 0;
        $fila_cortes    = $datos_por_dia[$i]['cortes'] ?? 0;
        
        // CÁLCULO PARA LAS NUEVAS COLUMNAS
        $total_dia_ingresos = ($fila_mensual + $fila_horas + $fila_visitas + $fila_articulos + $fila_prepagos);
        $fila_total_neto = $total_dia_ingresos - $fila_gastos;
        
        if ($total_dia_ingresos > 0 || $fila_gastos > 0 || $fila_cortes > 0) {
            $fecha = "$i-$mes-$año";
            $datos .= "<tr>
                         <td>$contador</td>
                         <td>".fecha_generica($fecha)."</td>
                         <td class='text-right'>$".number_format($fila_mensual, 2)."</td>
                         <td class='text-right'>$".number_format($fila_horas, 2)."</td>
                         <td class='text-right'>$".number_format($fila_visitas, 2)."</td>
                         <td class='text-right'>$".number_format($fila_articulos, 2)."</td>
                         <td class='text-right'>$".number_format($fila_prepagos, 2)."</td>
                         <td class='text-right text-primary text-bold'>$".number_format($total_dia_ingresos, 2)."</td>
                         <td class='text-right text-danger'>$".number_format($fila_gastos, 2)."</td>
                         <td class='text-right info'>$".number_format($fila_cortes, 2)."</td>
                         <td class='text-right success text-bold'>$".number_format($fila_total_neto, 2)."</td>
                        </tr>";
            $contador++;
            $tot_mensual += $fila_mensual; $tot_horas += $fila_horas; $tot_visitas += $fila_visitas;
            $tot_articulos += $fila_articulos; $tot_prepagos += $fila_prepagos; $tot_gastos += $fila_gastos;
            $tot_cortes += $fila_cortes; $tot_total += $fila_total_neto;
            $tot_total_dia_ingresos += $total_dia_ingresos; // Acumular el nuevo total
        }
    }

    if ($contador > 1) { // Si se agregó al menos una fila
        $datos .= "<tr class='success text-bold'>
                     <td class='text-right' colspan='2'>Totales</td>
                     <td class='text-right'>$".number_format($tot_mensual, 2)."</td>
                     <td class='text-right'>$".number_format($tot_horas, 2)."</td>
                     <td class='text-right'>$".number_format($tot_visitas, 2)."</td>
                     <td class='text-right'>$".number_format($tot_articulos, 2)."</td>
                     <td class='text-right'>$".number_format($tot_prepagos, 2)."</td>
                     <td class='text-right text-primary'>$".number_format($tot_total_dia_ingresos, 2)."</td>
                     <td class='text-right text-danger'>$".number_format($tot_gastos, 2)."</td>
                     <td class='text-right'>$".number_format($tot_cortes, 2)."</td>
                     <td class='text-right'>$".number_format($tot_total, 2)."</td>
                    </tr>";
    } else {
        $datos = "<tr><td colspan='$colspan'>No hay datos para el mes seleccionado.</td></tr>";
    }
    return $datos;
}

// --- FUNCIONES DE DETALLE (YA SON CORRECTAS Y SEGURAS) ---

function lista_detalle_mensualidades($fecha_mov) {
    global $conexion, $id_empresa;
    $datos = [];
    $query = "SELECT DAYOFMONTH(pag_fecha_pago) AS dia, SUM(pag_efectivo + pag_tarjeta) AS importe FROM san_pagos WHERE DATE_FORMAT(pag_fecha_pago, '%Y-%m') = ? AND pag_status = 'A' AND pag_id_empresa = ? GROUP BY DAYOFMONTH(pag_fecha_pago)";
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $fecha_mov, $id_empresa);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        while ($fila = mysqli_fetch_assoc($resultado)) $datos[] = $fila;
        mysqli_stmt_close($stmt);
    }
    return $datos;
}

function lista_detalle_horas_visitas($fecha_mov, $tipo) {
    global $conexion, $id_empresa;
    $datos = [];
    $query = "SELECT DAYOFMONTH(hor_fecha) AS dia, SUM(hor_efectivo + hor_tarjeta) AS importe FROM san_horas INNER JOIN san_servicios ON ser_id_servicio = hor_id_servicio WHERE DATE_FORMAT(hor_fecha, '%Y-%m') = ? AND hor_status = 'A' AND hor_id_empresa = ? AND ser_clave = ? GROUP BY DAYOFMONTH(hor_fecha)";
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "sis", $fecha_mov, $id_empresa, $tipo);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        while ($fila = mysqli_fetch_assoc($resultado)) $datos[] = $fila;
        mysqli_stmt_close($stmt);
    }
    return $datos;
}

function lista_detalle_venta_articulos($fecha_mov) {
    global $conexion, $id_empresa;
    $datos = [];
    $query = "SELECT DAYOFMONTH(ven_fecha) AS dia, SUM(ven_total_efectivo + ven_total_tarjeta) AS importe FROM san_venta WHERE DATE_FORMAT(ven_fecha, '%Y-%m') = ? AND ven_id_empresa = ? AND ven_status = 'V' GROUP BY DAYOFMONTH(ven_fecha)";
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $fecha_mov, $id_empresa);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        while ($fila = mysqli_fetch_assoc($resultado)) $datos[] = $fila;
        mysqli_stmt_close($stmt);
    }
    return $datos;
}

function lista_detalle_prepagos($fecha_mov) {
    global $conexion, $id_empresa;
    $datos = [];
    $query = "SELECT DAYOFMONTH(d.pred_fecha) AS dia, SUM(d.pred_importe) AS importe FROM san_prepago_detalle AS d INNER JOIN san_socios AS s ON d.pred_id_socio = s.soc_id_socio WHERE DATE_FORMAT(d.pred_fecha, '%Y-%m') = ? AND s.soc_id_empresa = ? AND d.pred_movimiento = 'S' GROUP BY DAYOFMONTH(d.pred_fecha)";
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $fecha_mov, $id_empresa);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        while ($fila = mysqli_fetch_assoc($resultado)) $datos[] = $fila;
        mysqli_stmt_close($stmt);
    }
    return $datos;
}

function lista_detalle_gastos($fecha_mov) {
    global $conexion, $id_empresa;
    $datos = [];
    $query = "SELECT DAYOFMONTH(gas_fecha_captura) AS dia, SUM(gas_importe) AS importe FROM san_gastos WHERE DATE_FORMAT(gas_fecha_captura, '%Y-%m') = ? AND gas_id_empresa = ? GROUP BY DAYOFMONTH(gas_fecha_captura)";
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $fecha_mov, $id_empresa);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        while ($fila = mysqli_fetch_assoc($resultado)) $datos[] = $fila;
        mysqli_stmt_close($stmt);
    }
    return $datos;
}

function lista_cortes($fecha_mov) {
    global $conexion, $id_empresa;
    $datos = [];
    $query = "SELECT DAYOFMONTH(cor_fecha_venta) AS dia, SUM(cor_importe) AS importe FROM san_corte WHERE DATE_FORMAT(cor_fecha_venta, '%Y-%m') = ? AND cor_id_empresa = ? GROUP BY DAYOFMONTH(cor_fecha_venta)";
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $fecha_mov, $id_empresa);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        while ($fila = mysqli_fetch_assoc($resultado)) $datos[] = $fila;
        mysqli_stmt_close($stmt);
    }
    return $datos;
}
?>
<?php

/**
 * Genera las filas de una tabla HTML con el resumen de ventas anual, desglosado por mes.
 * --> ESTE CÓDIGO YA ES CORRECTO Y SEGURO. NO SE REQUIEREN CAMBIOS.
 *
 * @param string $año El año para el cual se generará el reporte.
 * @return string El HTML de las filas de la tabla (<tr>).
 */
function lista_ventas_del_mes($año)
{
    global $conexion;

    $datos = "";
    $colspan = 9;
    $contador = 1;

    // Obtención de todos los datos del año, agrupados por mes.
    $d_mensual = lista_detalle_mensualidades($año);
    $d_horas = lista_detalle_horas_visitas($año, 'HORA');
    $d_visitas = lista_detalle_horas_visitas($año, 'VISITA');
    $d_ventas = lista_detalle_venta_articulos($año);
    $d_prepagos = lista_detalle_prepagos($año);
    $d_cortes = lista_cortes($año);

    // Mapeo de datos para fácil acceso.
    $datos_por_mes = [];
    foreach ($d_mensual as $fila) { $datos_por_mes[$fila['mes']]['mensual'] = $fila['importe']; }
    foreach ($d_horas as $fila) { $datos_por_mes[$fila['mes']]['horas'] = $fila['importe']; }
    foreach ($d_visitas as $fila) { $datos_por_mes[$fila['mes']]['visitas'] = $fila['importe']; }
    foreach ($d_ventas as $fila) { $datos_por_mes[$fila['mes']]['articulos'] = $fila['importe']; }
    foreach ($d_prepagos as $fila) { $datos_por_mes[$fila['mes']]['prepagos'] = $fila['importe']; }
    foreach ($d_cortes as $fila) { $datos_por_mes[$fila['mes']]['cortes'] = $fila['importe']; }

    $tot_mensual = 0; $tot_horas = 0; $tot_visitas = 0; $tot_articulos = 0;
    $tot_prepagos = 0; $tot_cortes = 0; $tot_total = 0;

    if (!empty($datos_por_mes)) {
        for ($i = 1; $i <= 12; $i++) {
            $fila_mensual = $datos_por_mes[$i]['mensual'] ?? 0;
            $fila_horas = $datos_por_mes[$i]['horas'] ?? 0;
            $fila_visitas = $datos_por_mes[$i]['visitas'] ?? 0;
            $fila_articulos = $datos_por_mes[$i]['articulos'] ?? 0;
            $fila_prepagos = $datos_por_mes[$i]['prepagos'] ?? 0;
            $fila_cortes = $datos_por_mes[$i]['cortes'] ?? 0;

            // El total de la fila representa el ingreso real del mes.
            $fila_total = ($fila_mensual + $fila_horas + $fila_visitas + $fila_articulos + $fila_prepagos);

            if ($fila_total > 0 || $fila_cortes > 0) {
                $fecha_mes = "$i-$año";
                $datos .= "<tr>
                                <td>$contador</td>
                                <td>" . fecha_a_mes($fecha_mes) . "</td>
                                <td class='text-right'>$" . number_format($fila_mensual, 2) . "</td>
                                <td class='text-right'>$" . number_format($fila_horas, 2) . "</td>
                                <td class='text-right'>$" . number_format($fila_visitas, 2) . "</td>
                                <td class='text-right'>$" . number_format($fila_articulos, 2) . "</td>
                                <td class='text-right'>$" . number_format($fila_prepagos, 2) . "</td>
                                <td class='text-right'>$" . number_format($fila_cortes, 2) . "</td>
                                <td class='text-right'>$" . number_format($fila_total, 2) . "</td>
                            </tr>";
                $contador++;

                $tot_mensual += $fila_mensual;
                $tot_horas += $fila_horas;
                $tot_visitas += $fila_visitas;
                $tot_articulos += $fila_articulos;
                $tot_prepagos += $fila_prepagos;
                $tot_cortes += $fila_cortes;
                $tot_total += $fila_total;
            }
        }

        $colspan -= 7;
        $datos .= "<tr class='success text-bold'>
                        <td class='text-right' colspan='$colspan'>Totales</td>
                        <td class='text-right'>$" . number_format($tot_mensual, 2) . "</td>
                        <td class='text-right'>$" . number_format($tot_horas, 2) . "</td>
                        <td class='text-right'>$" . number_format($tot_visitas, 2) . "</td>
                        <td class='text-right'>$" . number_format($tot_articulos, 2) . "</td>
                        <td class='text-right'>$" . number_format($tot_prepagos, 2) . "</td>
                        <td class='text-right'>$" . number_format($tot_cortes, 2) . "</td>
                        <td class='text-right'>$" . number_format($tot_total, 2) . "</td>
                    </tr>";
    } else {
        $datos = "<tr><td colspan='$colspan'>No hay datos para el año seleccionado.</td></tr>";
    }

    return $datos;
}

// --- FUNCIONES DE DETALLE (YA SON SEGURAS Y CORRECTAS) ---

function lista_detalle_mensualidades($año)
{
    global $conexion, $id_empresa;
    $datos = [];
    $query = "SELECT 
                DATE_FORMAT(pag_fecha_pago, '%c') AS mes,
                SUM(pag_efectivo + pag_tarjeta) AS importe
              FROM san_pagos 
              WHERE DATE_FORMAT(pag_fecha_pago, '%Y') = ? 
                AND pag_status = 'A' 
                AND pag_id_empresa = ? 
              GROUP BY DATE_FORMAT(pag_fecha_pago, '%Y-%m')";
    
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $año, $id_empresa);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $datos[] = $fila;
        }
        mysqli_stmt_close($stmt);
    }
    return $datos;
}

function lista_detalle_horas_visitas($año, $tipo)
{
    global $conexion, $id_empresa;
    $datos = [];
    $query = "SELECT 
                DATE_FORMAT(hor_fecha, '%c') AS mes,
                SUM(hor_efectivo + hor_tarjeta) AS importe 
              FROM san_horas 
              INNER JOIN san_servicios ON ser_id_servicio = hor_id_servicio
              WHERE DATE_FORMAT(hor_fecha, '%Y') = ? 
                AND hor_status = 'A' 
                AND hor_id_empresa = ? 
                AND ser_clave = ? 
              GROUP BY DATE_FORMAT(hor_fecha, '%Y-%m')";
              
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "sis", $año, $id_empresa, $tipo);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $datos[] = $fila;
        }
        mysqli_stmt_close($stmt);
    }
    return $datos;
}

function lista_detalle_venta_articulos($año)
{
    global $conexion, $id_empresa;
    $datos = [];
    $query = "SELECT 
                DATE_FORMAT(ven_fecha, '%c') AS mes,
                SUM(ven_total_efectivo + ven_total_tarjeta) AS importe 
              FROM san_venta 
              WHERE DATE_FORMAT(ven_fecha, '%Y') = ? 
                AND ven_id_empresa = ? 
                AND ven_status = 'V' 
              GROUP BY DATE_FORMAT(ven_fecha, '%Y-%m')";
              
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $año, $id_empresa);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $datos[] = $fila;
        }
        mysqli_stmt_close($stmt);
    }
    return $datos;
}

function lista_detalle_prepagos($año)
{
    global $conexion, $id_empresa;
    $datos = [];
    $query = "SELECT 
                DATE_FORMAT(d.pred_fecha, '%c') AS mes,
                SUM(d.pred_importe) AS importe
              FROM san_prepago_detalle AS d
              INNER JOIN san_socios AS s ON d.pred_id_socio = s.soc_id_socio
              WHERE DATE_FORMAT(d.pred_fecha, '%Y') = ? 
                AND s.soc_id_empresa = ? 
                AND d.pred_movimiento = 'S'
              GROUP BY DATE_FORMAT(d.pred_fecha, '%Y-%m')";
    
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $año, $id_empresa);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $datos[] = $fila;
        }
        mysqli_stmt_close($stmt);
    }
    return $datos;
}

function lista_cortes($año)
{
    global $conexion, $id_empresa;
    $datos = [];
    $query = "SELECT 
                DATE_FORMAT(cor_fecha_venta, '%c') AS mes,
                SUM(cor_importe) AS importe 
              FROM san_corte 
              WHERE DATE_FORMAT(cor_fecha_venta, '%Y') = ? 
                AND cor_id_empresa = ? 
              GROUP BY DATE_FORMAT(cor_fecha_venta, '%Y-%m')";
              
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "si", $año, $id_empresa);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $datos[] = $fila;
        }
        mysqli_stmt_close($stmt);
    }
    return $datos;
}

?>
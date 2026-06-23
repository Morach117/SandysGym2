<?php

/**
 * Obtiene el resumen de ingresos por mensualidades.
 * INCLUYE: Integración Mercado Pago (SDK v3)
 */
function obtener_importe_mensualidades($mes_ganancia = '', $tipo_corte = 'D', $p_id_cajero = 0)
{
    global $conexion, $id_empresa;
    $exito = ['num' => 0, 'total' => 0, 'efectivo' => 0, 'tar_com' => 0, 'tarjeta' => 0, 'comision' => 0, 'monedero' => 0, 'mercado_pago' => 0, 'msj' => 'No hay datos.'];
    $params = [$id_empresa];
    $types = "i";

    $query = "SELECT 
                SUM(pag_efectivo + pag_tarjeta + CASE WHEN pag_referencia_mp IS NOT NULL AND pag_referencia_mp != '0' THEN pag_importe ELSE 0 END) AS total,
                SUM(pag_efectivo) AS efectivo,
                SUM(pag_tarjeta) AS tarjeta,
                SUM(pag_comision) AS comision,
                SUM(pag_monedero) AS monedero,
                SUM(CASE WHEN pag_referencia_mp IS NOT NULL AND pag_referencia_mp != '0' THEN pag_importe ELSE 0 END) AS mercado_pago
              FROM san_pagos WHERE pag_status = 'A' AND pag_id_empresa = ?";

    if ($tipo_corte == 'A') $query .= " AND DATE_FORMAT(pag_fecha_pago, '%Y') = ?";
    elseif ($tipo_corte == 'M') $query .= " AND DATE_FORMAT(pag_fecha_pago, '%m-%Y') = ?";
    else $query .= " AND DATE_FORMAT(pag_fecha_pago, '%d-%m-%Y') = ?";
    $params[] = $mes_ganancia;
    $types .= "s";
    
    if ($p_id_cajero) {
        $query .= " AND pag_id_usuario = ?";
        $params[] = $p_id_cajero;
        $types .= "i";
    }

    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $exito['total'] = $fila['total'] ?? 0;
            $exito['efectivo'] = $fila['efectivo'] ?? 0;
            $exito['tarjeta'] = $fila['tarjeta'] ?? 0;
            $exito['comision'] = $fila['comision'] ?? 0;
            $exito['tar_com'] = ($fila['tarjeta'] ?? 0) + ($fila['comision'] ?? 0);
            $exito['monedero'] = $fila['monedero'] ?? 0;
            $exito['mercado_pago'] = $fila['mercado_pago'] ?? 0;
            $exito['num'] = 1;
            $exito['msj'] = "Datos obtenidos.";
        }
        mysqli_stmt_close($stmt);
    }
    return $exito;
}

/**
 * Obtiene el resumen de ingresos por horas o visitas.
 */
function obtener_importe_por_horas($desc = 'HORA', $mes_ganancia = '', $tipo_corte = 'D', $p_id_cajero = 0)
{
    global $conexion, $id_empresa;
    $exito = ['num' => 0, 'total' => 0, 'efectivo' => 0, 'tar_com' => 0, 'tarjeta' => 0, 'comision' => 0, 'monedero' => 0, 'mercado_pago' => 0, 'msj' => 'No hay datos.'];
    $params = [$id_empresa, $desc];
    $types = "is";
    
    $query = "SELECT 
                SUM(hor_efectivo + hor_tarjeta) AS total,
                SUM(hor_efectivo) AS efectivo,
                SUM(hor_tarjeta) AS tarjeta,
                SUM(hor_comision) AS comision,
                SUM(hor_monedero) AS monedero,
                0 AS mercado_pago
              FROM san_horas
              INNER JOIN san_servicios ON ser_id_servicio = hor_id_servicio
              WHERE hor_status = 'A' AND hor_id_empresa = ? AND ser_clave = ?";

    if ($tipo_corte == 'A') $query .= " AND DATE_FORMAT(hor_fecha, '%Y') = ?";
    elseif ($tipo_corte == 'M') $query .= " AND DATE_FORMAT(hor_fecha, '%m-%Y') = ?";
    else $query .= " AND DATE_FORMAT(hor_fecha, '%d-%m-%Y') = ?";
    $params[] = $mes_ganancia;
    $types .= "s";
    
    if ($p_id_cajero) {
        $query .= " AND hor_id_usuario = ?";
        $params[] = $p_id_cajero;
        $types .= "i";
    }

    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $exito['total'] = $fila['total'] ?? 0;
            $exito['efectivo'] = $fila['efectivo'] ?? 0;
            $exito['tarjeta'] = $fila['tarjeta'] ?? 0;
            $exito['comision'] = $fila['comision'] ?? 0;
            $exito['tar_com'] = ($fila['tarjeta'] ?? 0) + ($fila['comision'] ?? 0);
            $exito['monedero'] = $fila['monedero'] ?? 0;
            $exito['mercado_pago'] = $fila['mercado_pago'] ?? 0;
            $exito['num'] = 1;
            $exito['msj'] = "Datos obtenidos.";
        }
        mysqli_stmt_close($stmt);
    }
    return $exito;
}

/**
 * Obtiene el resumen de ingresos por venta de artículos.
 */
function obtener_importe_venta_efectivo($mes_ganancia = '', $tipo_corte = 'D', $p_id_cajero = 0)
{
    global $conexion, $id_empresa;
    $exito = ['num' => 0, 'total' => 0, 'efectivo' => 0, 'tar_com' => 0, 'tarjeta' => 0, 'comision' => 0, 'monedero' => 0, 'mercado_pago' => 0, 'msj' => 'No hay datos.'];
    $params = [$id_empresa];
    $types = "i";

    $query = "SELECT 
                SUM(ven_total_efectivo + ven_total_tarjeta) AS total,
                SUM(ven_total_efectivo) AS efectivo,
                SUM(ven_total_tarjeta) AS tarjeta,
                SUM(ven_comision) AS comision,
                SUM(ven_total_prepago) AS monedero,
                0 AS mercado_pago
              FROM san_venta
              WHERE ven_status = 'V' AND ven_id_empresa = ?";
    
    if ($tipo_corte == 'A') $query .= " AND DATE_FORMAT(ven_fecha, '%Y') = ?";
    elseif ($tipo_corte == 'M') $query .= " AND DATE_FORMAT(ven_fecha, '%m-%Y') = ?";
    else $query .= " AND DATE_FORMAT(ven_fecha, '%d-%m-%Y') = ?";
    $params[] = $mes_ganancia;
    $types .= "s";

    if ($p_id_cajero) {
        $query .= " AND ven_id_usuario = ?";
        $params[] = $p_id_cajero;
        $types .= "i";
    }

    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $exito['total'] = $fila['total'] ?? 0;
            $exito['efectivo'] = $fila['efectivo'] ?? 0;
            $exito['tarjeta'] = $fila['tarjeta'] ?? 0;
            $exito['comision'] = $fila['comision'] ?? 0;
            $exito['tar_com'] = ($fila['tarjeta'] ?? 0) + ($fila['comision'] ?? 0);
            $exito['monedero'] = $fila['monedero'] ?? 0;
            $exito['mercado_pago'] = $fila['mercado_pago'] ?? 0;
            $exito['num'] = 1;
            $exito['msj'] = "Datos obtenidos.";
        }
        mysqli_stmt_close($stmt);
    }
    return $exito;
}

/**
 * Obtiene el total de ABONOS A MONEDERO.
 * INCLUYE: Identificación de pagos Mercado Pago vía metadatos en descripción.
 */
function obtener_importe_monedero($mes_ganancia = '', $tipo_corte = 'D', $p_id_cajero = 0)
{
    global $conexion, $id_empresa;
    $exito = ['num' => 0, 'total' => 0, 'efectivo' => 0, 'mercado_pago' => 0, 'msj' => 'No hay datos.'];
    $params = [$id_empresa];
    $types = "i";

    $query = "SELECT 
                SUM(d.pred_importe) as total_abonos,
                SUM(CASE WHEN d.pred_descripcion LIKE '%(MP Ref: %)' THEN d.pred_importe ELSE 0 END) as mercado_pago,
                SUM(CASE WHEN d.pred_descripcion NOT LIKE '%(MP Ref: %)' THEN d.pred_importe ELSE 0 END) as efectivo_caja
              FROM san_prepago_detalle d
              INNER JOIN san_socios s ON s.soc_id_socio = d.pred_id_socio
              WHERE d.pred_movimiento = 'S' AND s.soc_id_empresa = ?";
    
    if ($tipo_corte == 'A') $query .= " AND DATE_FORMAT(d.pred_fecha, '%Y') = ?";
    elseif ($tipo_corte == 'M') $query .= " AND DATE_FORMAT(d.pred_fecha, '%m-%Y') = ?";
    else $query .= " AND DATE_FORMAT(d.pred_fecha, '%d-%m-%Y') = ?";
    $params[] = $mes_ganancia;
    $types .= "s";

    if ($p_id_cajero) {
        $query .= " AND d.pred_id_usuario = ?";
        $params[] = $p_id_cajero;
        $types .= "i";
    }

    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $exito['total'] = $fila['total_abonos'] ?? 0;
            $exito['efectivo'] = $fila['efectivo_caja'] ?? 0;
            $exito['mercado_pago'] = $fila['mercado_pago'] ?? 0;
            $exito['num'] = 1;
            $exito['msj'] = "Abonos a monedero obtenidos.";
        }
        mysqli_stmt_close($stmt);
    }
    return $exito;
}

/**
 * Obtiene el total de Mercado Pago mediante UNION de flujos.
 */
function obtener_total_mercadopago_unificado($mes_ganancia = '', $tipo_corte = 'D', $p_id_cajero = 0)
{
    global $conexion, $id_empresa;
    $total_mp = 0;
    $params = [];
    $types = "";

    if ($tipo_corte == 'A') $format_str = '%Y';
    elseif ($tipo_corte == 'M') $format_str = '%m-%Y';
    else $format_str = '%d-%m-%Y';

    $condicion_cajero = "";
    if ($p_id_cajero) {
        $condicion_cajero = " AND id_usuario = ?";
        $params[] = $p_id_cajero;
        $types .= "i";
    }

    $query = "
        SELECT SUM(importe_mp) as gran_total_mp FROM (
            SELECT SUM(pag_importe) AS importe_mp FROM san_pagos 
            WHERE pag_status = 'A' AND pag_id_empresa = ? AND pag_referencia_mp IS NOT NULL AND pag_referencia_mp != '0' 
            AND DATE_FORMAT(pag_fecha_pago, '$format_str') = ? " . str_replace('id_usuario', 'pag_id_usuario', $condicion_cajero) . "
            
            UNION ALL
            
            SELECT SUM(d.pred_importe) AS importe_mp FROM san_prepago_detalle d
            INNER JOIN san_socios s ON s.soc_id_socio = d.pred_id_socio
            WHERE d.pred_movimiento = 'S' AND d.pred_descripcion LIKE '%(MP Ref: %)' AND s.soc_id_empresa = ? 
            AND DATE_FORMAT(d.pred_fecha, '$format_str') = ? " . str_replace('id_usuario', 'd.pred_id_usuario', $condicion_cajero) . "
        ) AS mp_unificado
    ";
    
    $final_params = array_merge([$id_empresa, $mes_ganancia], $params, [$id_empresa, $mes_ganancia], $params);
    $final_types = str_repeat("is" . $types, 2);
    
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, $final_types, ...$final_params);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $total_mp = $fila['gran_total_mp'] ?? 0;
        }
        mysqli_stmt_close($stmt);
    }
    return ['total' => $total_mp];
}

function obtener_total_pagado_con_monedero($mes_ganancia = '', $tipo_corte = 'D', $p_id_cajero = 0)
{
    global $conexion, $id_empresa;
    $total_monedero = 0;
    $params = [];
    $types = "";

    if ($tipo_corte == 'A') $format_str = '%Y';
    elseif ($tipo_corte == 'M') $format_str = '%m-%Y';
    else $format_str = '%d-%m-%Y';

    $condicion_cajero = "";
    if ($p_id_cajero) {
        $condicion_cajero = " AND id_usuario = ?";
        $params[] = $p_id_cajero;
        $types .= "i";
    }

    $query = "
        SELECT SUM(total_monedero) as gran_total FROM (
            SELECT SUM(pag_monedero) as total_monedero FROM san_pagos WHERE pag_id_empresa = ? AND DATE_FORMAT(pag_fecha_pago, '$format_str') = ? " . str_replace('id_usuario', 'pag_id_usuario', $condicion_cajero) . "
            UNION ALL
            SELECT SUM(hor_monedero) as total_monedero FROM san_horas WHERE hor_id_empresa = ? AND DATE_FORMAT(hor_fecha, '$format_str') = ? " . str_replace('id_usuario', 'hor_id_usuario', $condicion_cajero) . "
            UNION ALL
            SELECT SUM(ven_total_prepago) as total_monedero FROM san_venta WHERE ven_id_empresa = ? AND DATE_FORMAT(ven_fecha, '$format_str') = ? " . str_replace('id_usuario', 'ven_id_usuario', $condicion_cajero) . "
        ) as monedero_total
    ";
    
    $final_params = array_merge([$id_empresa, $mes_ganancia], $params, [$id_empresa, $mes_ganancia], $params, [$id_empresa, $mes_ganancia], $params);
    $final_types = str_repeat("is" . $types, 3);
    
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, $final_types, ...$final_params);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $total_monedero = $fila['gran_total'] ?? 0;
        }
        mysqli_stmt_close($stmt);
    }

    return ['total' => $total_monedero];
}

/**
 * CORRECCIÓN SEGURIDAD: Inyección SQL neutralizada.
 */
function obtener_gastos($mes_ganancia = '', $tipo_corte = 'D')
{
    global $conexion, $id_empresa;

    $exito = array();
    $query = "SELECT SUM(gas_importe) AS importe,
                     SUM(gas_iva) AS iva,
                     SUM(gas_descuento) AS descuento,
                     SUM(gas_total) AS total
              FROM san_gastos
              WHERE gas_id_empresa = ?";

    $params = [$id_empresa];
    $types = "i";

    if ($tipo_corte == 'A') {
        $query .= " AND ? = DATE_FORMAT(gas_fecha_fnota, '%Y')";
        $params[] = $mes_ganancia;
        $types .= "s";
    } elseif ($tipo_corte == 'M') {
        $query .= " AND ? = DATE_FORMAT(gas_fecha_fnota, '%m-%Y')";
        $params[] = $mes_ganancia;
        $types .= "s";
    }

    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        if ($fila = mysqli_fetch_assoc($resultado)) {
            $exito['num'] = 1;
            $exito['msj'] = $fila;
        } else {
            $exito['num'] = 2;
            $exito['msj'] = "No se pudo obtener el total de gastos.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $exito['num'] = 3;
        $exito['msj'] = "Ocurrió un problema al tratar de obtener los gastos.";
    }

    return $exito;
}

/**
 * CORRECCIÓN SEGURIDAD: Inyección SQL neutralizada.
 */
function nombre_archivo_imagen($id_socio)
{
    global $conexion, $id_empresa;

    $query = "SELECT soc_imagen FROM san_socios WHERE soc_id_socio = ? AND soc_id_empresa = ?";
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "ii", $id_socio, $id_empresa);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($fila = mysqli_fetch_assoc($resultado)) {
            if ($fila['soc_imagen']) {
                mysqli_stmt_close($stmt);
                return $fila['soc_imagen'];
            }
        }
        mysqli_stmt_close($stmt);
    }
    return 'Sin nombre de imagen...';
}

/**
 * CORRECCIÓN SEGURIDAD: Inyección SQL neutralizada.
 */
function obtener_datos_socio()
{
    global $conexion, $id_empresa;

    $id_socio = request_var('id_socio', 0);

    $query = "SELECT * FROM san_socios WHERE soc_id_socio = ? AND soc_id_empresa = ?";
    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "ii", $id_socio, $id_empresa);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($fila = mysqli_fetch_assoc($resultado)) {
            mysqli_stmt_close($stmt);
            return $fila;
        }
        mysqli_stmt_close($stmt);
    }
    return false;
}

/**
 * CORRECCIÓN SEGURIDAD: Inyección SQL neutralizada.
 */
function subir_fotografia()
{
    global $conexion, $id_empresa;

    $id_socio           = request_var('id_socio', 0);
    $dir_ponencias      = "../imagenes/avatar/";
    $extenciones        = "/^\.(jpg){1}$/i";
    $tamaño_maximo      = 2 * 1024 * 1024;
    $exito              = array();

    if (isset($_FILES['avatar']) && $_FILES['avatar']['name'] && $id_socio) {
        $extencion_archivo  = tipo_archivo($_FILES['avatar']['type']);
        $nombre_archivo     = $id_socio . $extencion_archivo;
        $valido             = is_uploaded_file($_FILES['avatar']['tmp_name']);

        if ($valido) {
            $safe_filename = preg_replace(array("/\s+/", "/[^-\.\w]+/"), array("_", ""), trim($_FILES['avatar']['name']));

            if ($extencion_archivo && $_FILES['avatar']['size'] <= $tamaño_maximo && preg_match($extenciones, strrchr($safe_filename, '.'))) {
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir_ponencias . $nombre_archivo)) {
                    
                    $query = "SELECT soc_id_socio FROM san_socios WHERE soc_id_socio = ? AND soc_id_empresa = ?";
                    if ($stmt = mysqli_prepare($conexion, $query)) {
                        mysqli_stmt_bind_param($stmt, "ii", $id_socio, $id_empresa);
                        mysqli_stmt_execute($stmt);
                        $resultado = mysqli_stmt_get_result($stmt);
                        $bandera = (mysqli_num_rows($resultado) > 0);
                        mysqli_stmt_close($stmt);

                        $imagen_nombre = $_FILES['avatar']['name'];

                        if ($bandera) {
                            $query_upd = "UPDATE san_socios SET soc_imagen = ? WHERE soc_id_socio = ? AND soc_id_empresa = ?";
                            if ($stmt_upd = mysqli_prepare($conexion, $query_upd)) {
                                mysqli_stmt_bind_param($stmt_upd, "sii", $imagen_nombre, $id_socio, $id_empresa);
                                mysqli_stmt_execute($stmt_upd);
                                mysqli_stmt_close($stmt_upd);
                            }
                        } else {
                            $query_ins = "INSERT INTO san_socios (soc_imagen) VALUES (?)";
                            if ($stmt_ins = mysqli_prepare($conexion, $query_ins)) {
                                mysqli_stmt_bind_param($stmt_ins, "s", $imagen_nombre);
                                mysqli_stmt_execute($stmt_ins);
                                mysqli_stmt_close($stmt_ins);
                            }
                        }
                    }

                    $exito['num'] = 1;
                    $exito['msj'] = 'Fotografía guardada.';
                } else {
                    $exito['num'] = 5;
                    $exito['msj'] = 'La fotografía no se ha guardado.<br/>';
                }
            } else {
                $exito['num'] = 4;
                $exito['msj'] = 'La fotografía no es del tipo solicitado o excede el tamaño permitido.';
            }
        } else {
            $exito['num'] = 3;
            $exito['msj'] = 'No es archivo válido.';
        }
    } else {
        $exito['num'] = 2;
        $exito['msj'] = 'No se seleccionó un archivo para la Fotografía.';
    }

    return $exito;
}

function eliminar_fotografia()
{
    global $id_socio;

    if (file_exists("../../imagenes/avatar/$id_socio.jpg"))
        if (unlink("../../imagenes/avatar/$id_socio.jpg"))
            return true;

    return false;
}
    
?>
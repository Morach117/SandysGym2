<?php

/**
 * Inserta un nuevo registro de corte de caja en la base de datos.
 * --> CORRECCIÓN: Se reescribió con consultas preparadas para máxima seguridad.
 */
function realizar_corte($id_empresa_corte, $fecha_venta)
{
    global $conexion, $id_usuario;

    $cor_importe = request_var('cor_importe', 0.0);
    $fecha_mov = date('Y-m-d H:i:s');
    $fecha_venta_mysql = fecha_formato_mysql($fecha_venta);
    $exito = [];

    if (!$fecha_venta_mysql) {
        return ['num' => 2, 'msj' => "No se seleccionó una fecha de venta válida."];
    }

    if ($cor_importe <= 0) {
        return ['num' => 3, 'msj' => "El importe del corte debe ser mayor que cero."];
    }

    $query = "INSERT INTO san_corte 
                (cor_fecha, cor_fecha_venta, cor_id_usuario, cor_id_empresa, cor_importe, cor_observaciones) 
              VALUES (?, ?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($conexion, $query)) {
        $observaciones = request_var('cor_observaciones', '');
        
        // s: string, i: integer, d: double
        mysqli_stmt_bind_param($stmt, "ssiids", 
            $fecha_mov, 
            $fecha_venta_mysql, 
            $id_usuario, 
            $id_empresa_corte, 
            $cor_importe, 
            $observaciones
        );

        if (mysqli_stmt_execute($stmt)) {
            $exito['num'] = 1;
            $exito['msj'] = "Corte realizado exitosamente.";
        } else {
            $exito['num'] = 4;
            $exito['msj'] = "No se pudo procesar la petición: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $exito['num'] = 4;
        $exito['msj'] = "Error al preparar la consulta: " . mysqli_error($conexion);
    }

    return $exito;
}


/**
 * Genera el HTML de la tabla con la lista de cortes para una fecha específica.
 * --> CORRECCIÓN: Se reescribió con consultas preparadas para máxima seguridad.
 */
function lista_cortes_del_dia($fecha_movimiento)
{
    global $conexion, $id_empresa;
    $datos = "";
    $colspan = 10;

    if (empty($fecha_movimiento) || !preg_match('/^\d{2}-\d{2}-\d{4}$/', $fecha_movimiento)) {
        return "<tr><td colspan='$colspan'>Formato de fecha inválido. Use dd-mm-aaaa.</td></tr>";
    }

    list($d, $m, $Y) = explode('-', $fecha_movimiento);
    if (!checkdate($m, $d, $Y)) {
        return "<tr><td colspan='$colspan'>Fecha inválida seleccionada.</td></tr>";
    }

    $fecha_mysql = "$Y-$m-$d";
    $total = 0;
    $contador = 1;
    $fecha_req = request_var('fecha', date('d-m-Y'));
    $v_id_cajero = request_var('cajero', 0);

    $query = "SELECT 
                LOWER(DATE_FORMAT(cor_fecha, '%d-%m-%Y %r')) AS movimiento,
                LOWER(DATE_FORMAT(cor_fecha_venta, '%d-%m-%Y')) as fecha_venta,
                a.usua_nombres AS usuario,
                b.usua_nombres AS cajero,
                cor_id_corte AS id_corte,
                cor_importe AS importe,
                cor_caja AS caja,
                CASE cor_tipo_corte
                    WHEN 3 THEN 'APERTURA'
                    WHEN 4 THEN 'CIERRE'
                    WHEN 5 THEN 'RETIRO'
                    ELSE 'CORTE'
                END as tipo_mov,
                cor_observaciones AS notas
              FROM san_corte
              INNER JOIN san_usuarios a ON a.usua_id_usuario = cor_id_usuario
              LEFT JOIN san_usuarios b ON b.usua_id_usuario = cor_id_cajero
              WHERE 
                (DATE_FORMAT(cor_fecha, '%Y-%m-%d') = ? OR DATE_FORMAT(cor_fecha_venta, '%Y-%m-%d') = ?)
                AND cor_id_empresa = ?
              ORDER BY cor_fecha DESC";

    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, "ssi", $fecha_mysql, $fecha_mysql, $id_empresa);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        while ($fila = mysqli_fetch_assoc($resultado)) {
            $total += $fila['importe'];
            $class = (substr($fila['movimiento'], 0, 10) != $fila['fecha_venta']) ? "warning" : "";

            $datos .= "<tr class='$class'>
                        <td>$contador</td>
                        <td>
                            <div class='btn-group'>
                                <a class='pointer' dropdown-toggle' data-toggle='dropdown'><span class='glyphicon glyphicon-chevron-down'></span></a>
                                <ul class='dropdown-menu'>
                                    <li><a href='.?s=reportes&i=diario&idc=$fila[id_corte]&accion=e&fecha=$fecha_req&cajero=$v_id_cajero'><span class='glyphicon glyphicon-remove-sign'></span> Eliminar</a></li>
                                </ul>
                            </div>
                        </td>
                        <td>" . htmlspecialchars($fila['movimiento']) . "</td>
                        <td>" . htmlspecialchars($fila['fecha_venta']) . "</td>
                        <td>" . htmlspecialchars($fila['usuario']) . "</td>
                        <td>" . htmlspecialchars($fila['cajero']) . "</td>
                        <td>" . htmlspecialchars($fila['tipo_mov']) . "</td>
                        <td class='text-right'>$" . number_format($fila['caja'], 2) . "</td>
                        <td class='text-right'>$" . number_format($fila['importe'], 2) . "</td>
                        <td>" . htmlspecialchars($fila['notas']) . "</td>
                    </tr>";
            $contador++;
        }
        mysqli_stmt_close($stmt);

        if (empty($datos)) {
            $datos = "<tr><td colspan='$colspan'>No hay cortes para la fecha seleccionada.</td></tr>";
        }
    } else {
        $datos = "<tr><td colspan='$colspan'>Error al preparar la consulta.</td></tr>";
    }

    $colspan -= 2;
    $datos .= "<tr class='success text-bold'>
                    <td colspan='$colspan' class='text-right'>Total en retiros del día</td>
                    <td class='text-right'>$" . number_format($total, 2) . "</td>
                    <td>&nbsp;</td>
                </tr>";

    return $datos;
}

/**
 * Calcula el importe total de los cortes para una fecha y cajero específicos.
 * --> CORRECCIÓN: Se reescribió con consultas preparadas para máxima seguridad.
 */
function total_importe_corte_del_dia($fecha_movimiento, $p_id_cajero = 0)
{
    global $conexion, $id_empresa;

    if (empty($fecha_movimiento) || !preg_match('/^\d{2}-\d{2}-\d{4}$/', $fecha_movimiento)) return 0;
    
    list($d, $m, $Y) = explode('-', $fecha_movimiento);
    if (!checkdate($m, $d, $Y)) return 0;
    
    $fecha_mysql = "$Y-$m-$d";
    $total_dia = 0;

    $query = "SELECT SUM(cor_importe) AS total_dia
              FROM san_corte
              WHERE DATE_FORMAT(cor_fecha_venta, '%Y-%m-%d') = ? AND cor_id_empresa = ?";
    
    $params = [$fecha_mysql, $id_empresa];
    $types = "si";

    if ($p_id_cajero) {
        $query .= " AND cor_id_cajero = ?";
        $params[] = $p_id_cajero;
        $types .= "i";
    }

    if ($stmt = mysqli_prepare($conexion, $query)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $total_dia = $fila['total_dia'] ?? 0;
        }
        mysqli_stmt_close($stmt);
    }
    
    return $total_dia;
}

/**
 * Elimina un registro de corte de la base de datos.
 * --> CORRECCIÓN: Se reescribió con consultas preparadas para máxima seguridad.
 */
function eliminar_corte()
{
    global $conexion, $id_empresa;

    $v_fecha = request_var('fecha_mov', '');
    $v_id_cajero = request_var('cajero', 0);
    $v_id_corte = request_var('idc', 0);

    if ($v_id_corte) {
        $query = "DELETE FROM san_corte WHERE cor_id_corte = ? AND cor_id_empresa = ?";
        if ($stmt = mysqli_prepare($conexion, $query)) {
            mysqli_stmt_bind_param($stmt, "ii", $v_id_corte, $id_empresa);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    $redirect_fecha = !empty($v_fecha) ? $v_fecha : date('d-m-Y');
    header("Location: .?s=reportes&i=diario&fecha_mov=$redirect_fecha&cajero=$v_id_cajero");
    exit;
}

?>
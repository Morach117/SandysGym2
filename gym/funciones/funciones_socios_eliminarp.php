<?php
function obtener_detalle_pago($id_socio, $id_pago)
{
    global $conexion, $id_empresa, $id_usuario, $id_consorcio;

    // Seguridad contra Inyección SQL
    $id_socio = intval($id_socio);
    $id_pago  = intval($id_pago);
    $id_emp   = intval($id_empresa);

    $query = " SELECT  DATE_FORMAT( pag_fecha_pago, '%d-%m-%Y %r' ) AS f_pago,
                                DATE_FORMAT( pag_fecha_ini, '%d-%m-%Y' ) AS f_ini,
                                DATE_FORMAT( pag_fecha_fin, '%d-%m-%Y' ) AS f_fin,
                                pag_importe As importe
                        FROM    san_pagos 
                        WHERE   pag_id_pago = $id_pago 
                        AND     pag_id_socio = $id_socio 
                        AND     pag_id_empresa = $id_emp";

    $resultado = mysqli_query($conexion, $query);

    if ($resultado && $fila = mysqli_fetch_assoc($resultado)) {
        return $fila;
    }

    return false;
}

function eliminar_pago_socio($id_socio, $id_pago)
{
    global $conexion, $id_empresa, $id_usuario, $id_consorcio;

    // Seguridad contra Inyección SQL
    $id_socio = intval($id_socio);
    $id_pago  = intval($id_pago);
    $id_emp   = intval($id_empresa);

    $exito = array();
    $fecha_mov = date('Y-m-d H:i:s');

    // INICIO DE TRANSACCIÓN: Protegemos la integridad del dinero y vigencias
    mysqli_autocommit($conexion, false);

    try {
        // --- 1. LÓGICA DE MONEDERO ---
        $query_pago = "SELECT pag_id_prepago_abono FROM san_pagos WHERE pag_id_pago = $id_pago AND pag_id_socio = $id_socio AND pag_id_empresa = $id_emp";
        $res_pago = mysqli_query($conexion, $query_pago);

        if ($fila_pago = mysqli_fetch_assoc($res_pago)) {
            $id_abono = intval($fila_pago['pag_id_prepago_abono']);

            if ($id_abono > 0) {
                $query_monto = "SELECT pred_importe FROM san_prepago_detalle WHERE pred_id_pdetalle = $id_abono";
                $res_monto = mysqli_query($conexion, $query_monto);

                if ($fila_monto = mysqli_fetch_assoc($res_monto)) {
                    $monto_a_restar = floatval($fila_monto['pred_importe']);

                    $query_resta = "UPDATE san_socios SET soc_mon_saldo = soc_mon_saldo - $monto_a_restar WHERE soc_id_socio = $id_socio";
                    if (!mysqli_query($conexion, $query_resta)) {
                        throw new Exception("Error al restar el saldo del monedero: " . mysqli_error($conexion));
                    }

                    $query_eliminar_abono = "DELETE FROM san_prepago_detalle WHERE pred_id_pdetalle = $id_abono";
                    if (!mysqli_query($conexion, $query_eliminar_abono)) {
                        throw new Exception("Error al eliminar el historial del abono: " . mysqli_error($conexion));
                    }
                }
            }
        } else {
            throw new Exception("El pago no se encontró o no pertenece a este socio.");
        }

        // --- 2. LÓGICA ORIGINAL: Desactivar el pago ---
        $query = " UPDATE  san_pagos
                            SET     pag_status = 'E',
                                    pag_id_usuario_e = $id_usuario,
                                    pag_fecha_e = '$fecha_mov'
                            WHERE   pag_id_pago = $id_pago
                            AND     pag_id_socio = $id_socio
                            AND     pag_id_empresa = $id_emp";

        $resultado = mysqli_query($conexion, $query);

        if ($resultado && mysqli_affected_rows($conexion) > 0) {
            
            // Confirmamos todo. La tabla san_pagos ya tiene el status 'E'. 
            // El cálculo de vigencia del frontend/backend deberá consultar los pagos con status 'A'.
            mysqli_commit($conexion);
            $exito['num'] = 1;
            $exito['msj'] = "Pago cancelado y saldos actualizados correctamente.";
        } else {
            throw new Exception("No se modificó el registro. Es posible que el pago ya estuviera eliminado.");
        }

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        $exito['num'] = 3;
        $exito['msj'] = $e->getMessage();
    }

    return $exito;
}
?>
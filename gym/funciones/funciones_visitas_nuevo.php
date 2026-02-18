<?php

require_once 'correo.php';

/**
 * Guarda el registro de una visita (pago por día).
 * Incluye el manejo de monedero electrónico para socios registrados.
 * LA BONIFICACIÓN A MONEDERO SE APLICA AHORA A TODOS LOS MÉTODOS DE PAGO.
 */
function guardar_nuevo_dia()
{
    global $conexion, $id_usuario, $id_empresa, $gbl_key, $id_consorcio;

    $cuota = obtener_servicio('VISITA');
    $exito = array();
    $importe_total = $cuota['cuota'];
    $fecha_mov = date('Y-m-d H:i:s');

    // Obtener valores del formulario
    $hor_nombre = request_var('hor_nombre', '');
    $hor_genero = request_var('hor_genero', '');
    $metodo_pago = request_var('metodo_pago', '');
    $id_socio = request_var('id_socio', 0);

    // Iniciar transacción para garantizar la integridad de los datos
    mysqli_autocommit($conexion, false);

    try {
        // 1. Obtener saldo actual del socio (si aplica)
        $saldo_actual = 0;
        if ($id_socio > 0) {
            $query_saldo = "SELECT soc_mon_saldo FROM san_socios WHERE soc_id_socio = $id_socio FOR UPDATE";
            $resultado_saldo = mysqli_query($conexion, $query_saldo);
            if ($fila = mysqli_fetch_assoc($resultado_saldo)) {
                $saldo_actual = (float)$fila['soc_mon_saldo'];
            }
        }

        // 2. Validar y preparar montos de la visita
        $hor_efectivo = 0; $hor_tarjeta = 0; $hor_monedero = 0;
        if ($metodo_pago == 'E') {
            $hor_efectivo = $importe_total;
        } elseif ($metodo_pago == 'T') {
            $hor_tarjeta = $importe_total;
        } elseif ($metodo_pago == 'M' && $id_socio > 0) {
            if ($saldo_actual < $importe_total) {
                throw new Exception("Saldo insuficiente. El socio solo tiene $" . number_format($saldo_actual, 2));
            }
            $hor_monedero = $importe_total;
        }

        // 3. Insertar el registro de la visita
        $datos_visita = ['hor_nombre' => $hor_nombre, 'hor_fecha' => $fecha_mov, 'hor_importe' => $importe_total, 'hor_genero' => $hor_genero, 'hor_id_servicio' => $cuota['id_servicio'], 'hor_id_usuario' => $id_usuario, 'hor_id_empresa' => $id_empresa, 'hor_efectivo' => $hor_efectivo, 'hor_tarjeta' => $hor_tarjeta, 'hor_monedero' => $hor_monedero, 'hor_tipo_pago' => $metodo_pago];
        $query_insert = construir_insert('san_horas', $datos_visita);
        if (!mysqli_query($conexion, $query_insert)) {
            throw new Exception("No se pudo registrar la visita: " . mysqli_error($conexion));
        }
        $id_visita = mysqli_insert_id($conexion);
        $token = hash_hmac('md5', $id_visita, $gbl_key);
        
        // 4. Procesar monedero (solo si es un socio válido)
        $nuevo_saldo = $saldo_actual;
        $deduccion_para_email = 0;
        $abono_para_email = 0;

        if ($id_socio > 0) {
            // --- 4.1 DEDUCCIÓN POR PAGO CON MONEDERO ---
            if ($metodo_pago == 'M') {
                $nuevo_saldo = $saldo_actual - $hor_monedero;
                $deduccion_para_email = $hor_monedero;

                $query_update = "UPDATE san_socios SET soc_mon_saldo = $nuevo_saldo WHERE soc_id_socio = $id_socio";
                if (!mysqli_query($conexion, $query_update)) {
                    throw new Exception("Error fatal al deducir del saldo del socio.");
                }
                
                $detalle_sql = ['pred_descripcion' => 'Pago de Visita', 'pred_importe' => $hor_monedero, 'pred_saldo' => $nuevo_saldo, 'pred_movimiento' => 'R', 'pred_fecha' => $fecha_mov, 'pred_id_socio' => $id_socio, 'pred_id_usuario' => $id_usuario];
                mysqli_query($conexion, construir_insert('san_prepago_detalle', $detalle_sql));
                
                // Actualizamos la variable local del saldo para el siguiente paso
                $saldo_actual = $nuevo_saldo;
            }

            // --- 4.2 ABONO POR BONIFICACIÓN (PARA TODOS LOS MÉTODOS DE PAGO) ---
            $query_consorcio = "SELECT con_visita FROM san_consorcios WHERE con_id_consorcio = $id_consorcio";
            $resultado_consorcio = mysqli_query($conexion, $query_consorcio);
            if ($resultado_consorcio && $fila_consorcio = mysqli_fetch_assoc($resultado_consorcio)) {
                $porcentaje = floatval($fila_consorcio['con_visita']);
                $abono_monedero = round($importe_total * ($porcentaje / 100), 2);
                
                if ($abono_monedero > 0) {
                    $nuevo_saldo = $saldo_actual + $abono_monedero;
                    $abono_para_email = $abono_monedero;

                    $query_update_bono = "UPDATE san_socios SET soc_mon_saldo = $nuevo_saldo WHERE soc_id_socio = $id_socio";
                    if (!mysqli_query($conexion, $query_update_bono)) {
                        throw new Exception("Error fatal al abonar la bonificación al socio.");
                    }

                    $detalle_sql_bono = ['pred_descripcion' => 'Abono por Visita', 'pred_importe' => $abono_monedero, 'pred_saldo' => $nuevo_saldo, 'pred_movimiento' => 'A', 'pred_fecha' => $fecha_mov, 'pred_id_socio' => $id_socio, 'pred_id_usuario' => $id_usuario];
                    mysqli_query($conexion, construir_insert('san_prepago_detalle', $detalle_sql_bono));
                }
            }
        }

        // 5. Si todo salió bien, confirmar transacción y preparar respuesta exitosa
        mysqli_commit($conexion);
        $exito = ['num' => 1, 'msj' => 'Guardado.', 'IDV' => $id_visita, 'tkn' => $token];
        
        // 6. Enviar correo de notificación (se ejecuta fuera de la transacción)
        if ($id_socio > 0 && ($correo_socio = obtener_correo_socio($id_socio))) {
            $asunto = "Confirmación de tu visita a Sandys Gym";
            
            // --- INICIO DE LA PLANTILLA DE CORREO PERSONALIZADA ---
            $mensaje = '
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>' . htmlspecialchars($asunto) . '</title>
                <style>
                    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; }
                    .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden; }
                    .header { text-align: center; padding: 30px 20px; background-color: #222222; }
                    .content { padding: 35px 30px; color: #555555; line-height: 1.7; }
                    .content h1 { color: #222222; font-size: 24px; }
                    .summary-table { width: 100%; margin-bottom: 25px; }
                    .summary-table td { padding: 12px 0; border-bottom: 1px solid #eeeeee; }
                    .summary-table .label { font-weight: bold; color: #333; }
                    .summary-table .value { text-align: right; font-weight: 500; }
                    .card { border-left: 5px solid #e74c3c; background-color: #f8f9fa; padding: 20px; margin-bottom: 25px; border-radius: 5px;}
                    .card.success { border-left-color: #28a745; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <img src="https://sergym.com/imagenes/empresa_1.png" alt="Sandy\'s Gym Logo">
                    </div>
                    <div class="content">
                        <h1>¡Gracias por tu visita!</h1>
                        <p>Hola, <strong>' . htmlspecialchars($hor_nombre) . '</strong>,<br>Te confirmamos el registro de tu acceso a nuestras instalaciones el día de hoy.</p>
                        <table class="summary-table">
                            <tr><td class="label">Fecha de Visita</td><td class="value">' . date('d/m/Y H:i:s', strtotime($fecha_mov)) . '</td></tr>
                            <tr><td class="label" style="font-size: 18px;">Total Pagado</td><td class="value" style="font-size: 18px; color: #e74c3c;"><strong>$' . number_format($importe_total, 2) . '</strong></td></tr>
                        </table>';

            if ($deduccion_para_email > 0) {
                $mensaje .= '<div class="card"><h2>Movimiento en tu Monedero</h2><table class="summary-table" style="margin-bottom:0;"><tr><td>Pago de visita con monedero</td><td class="value">-$' . number_format($deduccion_para_email, 2) . '</td></tr></table></div>';
            }
            if ($abono_para_email > 0) {
                $mensaje .= '<div class="card success"><h2>¡Saldo a tu favor!</h2><table class="summary-table" style="margin-bottom:0;"><tr><td>Abono por tu visita</td><td class="value">+$' . number_format($abono_para_email, 2) . '</td></tr><tr><td class="label">Nuevo saldo total</td><td class="value">$' . number_format($nuevo_saldo, 2) . '</td></tr></table></div>';
            }

            $mensaje .= '</div><div class="footer" style="background-color: #222222; color: #aaaaaa; text-align: center; padding: 25px 20px; font-size: 13px;"><p style="margin:5px 0;"><strong>SANDY\'S GYM</strong></p><p style="margin:5px 0;">Av. Miguel Hidalgo 308, Bienestar Social, 29077 Tuxtla Gutiérrez, Chis.</p><p style="margin:5px 0;"><a href="https://www.facebook.com/gymsandy" style="color: #e74c3c; text-decoration: none; font-weight: bold;">Facebook</a> &nbsp;&middot;&nbsp; <a href="https://www.instagram.com/sandysgym/" style="color: #e74c3c; text-decoration: none; font-weight: bold;">Instagram</a></p></div></div>
            </body>
            </html>';
            // --- FIN DE LA PLANTILLA ---
            
            enviar_correo($correo_socio, $asunto, $mensaje);
        }

    } catch (Exception $e) {
        // Si algo falla, revertir todos los cambios y notificar el error
        mysqli_rollback($conexion);
        $exito = ['num' => 0, 'msj' => $e->getMessage()];
    }

    return $exito;
}

?>
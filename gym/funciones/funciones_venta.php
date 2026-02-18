<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Se asume que los archivos de PHPMailer están disponibles en la ruta especificada
// require '../funciones_globales/phpmailer/src/PHPMailer.php';
// require '../funciones_globales/phpmailer/src/SMTP.php';
// require '../funciones_globales/phpmailer/src/Exception.php';

function preocesar_venta($array_cant_idart, $p_chk_prepago, $id_socio, $p_tot_prepago, $p_tot_efectivo, $p_sub_total, $p_metodo_pago, $p_tot_tarjeta)
{
    global $conexion, $id_usuario, $id_empresa, $id_consorcio, $gbl_key;

    $continuar  = false;
    $socio_deta = array();
    $folio      = array();
    $id_venta   = 0;
    $tipo_pago  = 'E';
    $exito      = array();
    $fecha_mov  = date('Y-m-d H:i:s');
    $incremento = 0; // Variable para el abono, inicializada en 0

    if (!isset($id_usuario) || $id_usuario <= 0) {
        $id_usuario = null;
    }

    mysqli_autocommit($conexion, false); // Inicia la transacción

    // Determinar el tipo de pago principal
    if ($p_metodo_pago == 'T') {
        $tipo_pago = 'T';
    } elseif ($p_metodo_pago == 'P' || $p_chk_prepago) {
        $tipo_pago = 'P';
    }

    $usar_monedero = ($p_chk_prepago || $p_metodo_pago == 'P') && $p_tot_prepago > 0 && $id_socio > 0;

    // --- 1. PROCESAR DEDUCCIÓN DEL MONEDERO (SI APLICA) ---
    if ($usar_monedero) {
        $saldo_actual = obtener_saldo_socio($id_socio);

        if ($saldo_actual !== false && $saldo_actual >= $p_tot_prepago) {
            $query_update_saldo = "UPDATE san_socios SET soc_mon_saldo = soc_mon_saldo - $p_tot_prepago WHERE soc_id_socio = $id_socio AND soc_id_empresa = $id_empresa";
            if (mysqli_query($conexion, $query_update_saldo)) {
                $query_socio = "SELECT soc_mon_saldo AS saldo, CONCAT(soc_apepat, ' ', soc_apemat, ' ', soc_nombres) AS nombre_s FROM san_socios WHERE soc_id_socio = $id_socio";
                $res_socio = mysqli_query($conexion, $query_socio);

                if ($res_socio && $fila = mysqli_fetch_assoc($res_socio)) {
                    $socio_deta = $fila;
                    $datos_detalle_prepago = [
                        'pred_id_socio'    => $id_socio,
                        'pred_descripcion' => 'Pago de Artículos con Monedero',
                        'pred_importe'     => $p_tot_prepago,
                        'pred_saldo'       => $socio_deta['saldo'],
                        'pred_movimiento'  => 'R',
                        'pred_fecha'       => $fecha_mov,
                        'pred_id_usuario'  => $id_usuario
                    ];
                    $query_insert_detalle = construir_insert('san_prepago_detalle', $datos_detalle_prepago);
                    
                    if (mysqli_query($conexion, $query_insert_detalle)) {
                        $continuar = true;
                    } else {
                        $exito['num'] = 4; $exito['msj'] = "No se pudo guardar el detalle del Saldo. Error: " . mysqli_error($conexion);
                    }
                } else {
                    $exito['num'] = 3; $exito['msj'] = "No se pudo re-consultar el socio. Error: " . mysqli_error($conexion);
                }
            } else {
                $exito['num'] = 2; $exito['msj'] = "No se pudo actualizar el Saldo del Socio. Error: " . mysqli_error($conexion);
            }
        } else {
            $exito['num'] = 10; $exito['msj'] = "Saldo en monedero insuficiente. Saldo actual: $" . number_format($saldo_actual, 2);
        }
    } else {
        $continuar = true; // No se usa monedero, continuar con la venta
    }

    // --- 2. PROCESAR LA VENTA Y DETALLES (SI TODO VA BIEN) ---
    if ($continuar) {
        $folio = nuevo_folio();
        if ($folio && !empty($folio['folio'])) {
            $datos_sql = [
                'ven_folio'          => $folio['folio'],
                'ven_anio'           => $folio['anio'],
                'ven_fecha'          => $fecha_mov,
                'ven_total_efectivo' => $p_tot_efectivo,
                'ven_total_tarjeta'  => $p_tot_tarjeta,
                'ven_total_prepago'  => $p_tot_prepago,
                'ven_total'          => round($p_sub_total, 2),
                'ven_tipo_pago'      => $tipo_pago,
                'ven_status'         => 'V',
                'ven_id_socio'       => $id_socio,
                'ven_id_usuario'     => $id_usuario,
                'ven_id_empresa'     => $id_empresa
            ];

            $query_venta = construir_insert('san_venta', $datos_sql);
            if (mysqli_query($conexion, $query_venta)) {
                $id_venta = mysqli_insert_id($conexion);
                $error_en_bucle = false;

                foreach ($array_cant_idart as $cant_idart) {
                    list($cantidad, $id_articulo) = explode('-', $cant_idart);

                    $query_stock = "UPDATE san_stock SET stk_existencia = stk_existencia - $cantidad WHERE stk_id_articulo = $id_articulo AND stk_id_empresa = $id_empresa";
                    if (!mysqli_query($conexion, $query_stock)) {
                        $exito['num'] = 5; $exito['msj'] = "No se pudo actualizar el Stock. Error: " . mysqli_error($conexion);
                        $error_en_bucle = true;
                        break;
                    }
                    
                    $artic_deta = obtener_detalle_articulo($id_articulo);
                    $datos_detalle_venta = [
                        'vende_id_articulo' => $artic_deta['art_id_articulo'],
                        'vende_id_venta'    => $id_venta,
                        'vende_cantidad'    => $cantidad,
                        'vende_costo'       => $artic_deta['art_costo'],
                        'vende_precio'      => $artic_deta['art_precio']
                    ];
                    $query_detalle = construir_insert('san_venta_detalle', $datos_detalle_venta);
                    if (!mysqli_query($conexion, $query_detalle)) {
                        $exito['num'] = 6; $exito['msj'] = "No se puede guardar el detalle de la Venta. Error: " . mysqli_error($conexion);
                        $error_en_bucle = true;
                        break;
                    }
                }

                // --- 3. PROCESAR BONIFICACIÓN (SI LA VENTA FUE EXITOSA Y ES UN SOCIO) ---
                if (!$error_en_bucle) {
                    // ** LÓGICA DE BONIFICACIÓN APLICADA A TODOS LOS MÉTODOS DE PAGO **
                    if ($id_socio > 0) {
                        $query_consorcio = "SELECT con_venta FROM san_consorcios WHERE con_id_consorcio = $id_consorcio";
                        $resultado_consorcio = mysqli_query($conexion, $query_consorcio);
                        if ($resultado_consorcio && $fila_consorcio = mysqli_fetch_assoc($resultado_consorcio)) {
                            $porcentaje_incremento = floatval($fila_consorcio['con_venta']);
                            $incremento = round($p_sub_total * ($porcentaje_incremento / 100), 2);

                            if ($incremento > 0) {
                                $query_actualizar_saldo = "UPDATE san_socios SET soc_mon_saldo = soc_mon_saldo + $incremento WHERE soc_id_socio = $id_socio";
                                if(mysqli_query($conexion, $query_actualizar_saldo)) {
                                    $nuevo_saldo = obtener_saldo_socio($id_socio);
                                    $datos_detalle_abono = [
                                        'pred_id_socio'    => $id_socio,
                                        'pred_descripcion' => 'Abono por compra',
                                        'pred_importe'     => $incremento,
                                        'pred_saldo'       => $nuevo_saldo,
                                        'pred_movimiento'  => 'A',
                                        'pred_fecha'       => $fecha_mov,
                                        'pred_id_usuario'  => $id_usuario
                                    ];
                                    $query_insert_abono = construir_insert('san_prepago_detalle', $datos_detalle_abono);
                                    mysqli_query($conexion, $query_insert_abono);
                                }
                            }
                        }
                    }

                    // Marcar la transacción como exitosa para el commit
                    $exito['num'] = 1;
                    $exito['msj'] = "Venta terminada. Transacción finalizada.";
                    $exito = array_merge($exito, $folio, ['IDV' => $id_venta]);
                }
            } else {
                $exito['num'] = 7; $exito['msj'] = "No se pudo guardar la Venta. Error: " . mysqli_error($conexion);
            }
        } else {
            $exito['num'] = 8; $exito['msj'] = "No se pudo obtener el folio de la venta.";
        }
    }

    // --- 4. FINALIZAR TRANSACCIÓN (COMMIT O ROLLBACK) ---
    if (isset($exito['num']) && $exito['num'] == 1) {
        mysqli_commit($conexion);
        
        // --- 5. ENVIAR CORREO (FUERA DE LA TRANSACCIÓN) ---
        if ($id_socio > 0 && ($correo_socio = obtener_correo_socio($id_socio))) {
            
            if ($correo_socio) {
                if (empty($socio_deta['nombre_s'])) {
                    $query_nombre = "SELECT CONCAT(soc_nombres, ' ', soc_apepat) AS nombre_s FROM san_socios WHERE soc_id_socio = $id_socio";
                    $res_nombre = mysqli_query($conexion, $query_nombre);
                    if ($fila_nombre = mysqli_fetch_assoc($res_nombre)) {
                        $socio_deta['nombre_s'] = $fila_nombre['nombre_s'];
                    }
                }
                $nombre_socio = $socio_deta['nombre_s'] ?? 'Socio';
                
                $asunto = "Confirmación de tu compra en Sandys Gym - Folio: " . ($folio['folio'] ?? 'N/A');
                
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
                        .service-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        .service-table th, .service-table td { border: 1px solid #dddddd; padding: 12px; text-align: left; }
                        .service-table th { background-color: #f2f2f2; font-weight: bold; }
                        .footer { background-color: #222222; color: #aaaaaa; text-align: center; padding: 25px 20px; font-size: 13px; }
                        .footer a { color: #e74c3c; text-decoration: none; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <img src="https://sergym.com/imagenes/empresa_1.png" alt="Sandy\'s Gym Logo">
                        </div>
                        <div class="content">
                            <h1>¡Gracias por tu compra!</h1>
                            <p>Hola, <strong>' . htmlspecialchars($nombre_socio) . '</strong>,<br>Tu compra ha sido registrada exitosamente. Aquí tienes los detalles:</p>
                            <table class="summary-table">
                                <tr><td class="label">Folio No.</td><td class="value">' . ($folio['folio'] ?? 'N/A') . '</td></tr>
                                <tr><td class="label">Fecha de Compra</td><td class="value">' . date('d/m/Y H:i:s', strtotime($fecha_mov)) . '</td></tr>
                                <tr><td class="label" style="font-size: 18px;">Total Pagado</td><td class="value" style="font-size: 18px; color: #e74c3c;"><strong>$' . number_format($p_sub_total, 2) . '</strong></td></tr>
                            </table>';

                if ($usar_monedero) {
                    $mensaje .= '
                            <div class="card">
                                <h2>Movimiento en tu Monedero</h2>
                                <table class="summary-table" style="margin-bottom: 0;">
                                    <tr><td>Monto pagado con monedero</td><td class="value">-$' . number_format($p_tot_prepago, 2) . '</td></tr>
                                    <tr><td class="label">Saldo restante</td><td class="value">$' . number_format($socio_deta['saldo'], 2) . '</td></tr>
                                </table>
                            </div>';
                }

                if ($incremento > 0) {
                    $saldo_final_con_abono = obtener_saldo_socio($id_socio); 
                    $mensaje .= '
                            <div class="card success">
                                <h2>¡Recibiste saldo por esta compra!</h2>
                                <table class="summary-table" style="margin-bottom: 0;">
                                    <tr><td>Abono a tu favor</td><td class="value">+$' . number_format($incremento, 2) . '</td></tr>
                                    <tr><td class="label">Nuevo saldo total</td><td class="value">$' . number_format($saldo_final_con_abono, 2) . '</td></tr>
                                </table>
                            </div>';
                }

                $mensaje .= '
                            <h2>Artículos Comprados</h2>
                            <table class="service-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th style="text-align: center;">Cantidad</th>
                                        <th style="text-align: right;">Precio Unit.</th>
                                        <th style="text-align: right;">Importe</th>
                                    </tr>
                                </thead>
                                <tbody>';

                foreach ($array_cant_idart as $cant_idart) {
                    list($cantidad, $id_articulo) = explode('-', $cant_idart);
                    $detalle_articulo = obtener_detalle_articulo($id_articulo);
                    if ($detalle_articulo) {
                        $importe_linea = $cantidad * $detalle_articulo['art_precio'];
                        $mensaje .= "<tr>
                                        <td>" . htmlspecialchars($detalle_articulo['art_descripcion']) . "</td>
                                        <td style='text-align: center;'>" . $cantidad . "</td>
                                        <td style='text-align: right;'>$" . number_format($detalle_articulo['art_precio'], 2) . "</td>
                                        <td style='text-align: right;'>$" . number_format($importe_linea, 2) . "</td>
                                    </tr>";
                    }
                }
                
                $mensaje .= '
                                </tbody>
                            </table>
                        </div>
                        <div class="footer" style="background-color: #222222; color: #aaaaaa; text-align: center; padding: 25px 20px; font-size: 13px;">
                            <p style="margin:5px 0;"><strong>SANDY\'S GYM</strong></p>
                            <p style="margin:5px 0;">Av. Miguel Hidalgo 308, Bienestar Social, 29077 Tuxtla Gutiérrez, Chis.</p>
                            <p style="margin:5px 0;"><a href="https://www.facebook.com/gymsandy" style="color: #e74c3c; text-decoration: none; font-weight: bold;">Facebook</a> &nbsp;&middot;&nbsp; <a href="https://www.instagram.com/sandysgym/" style="color: #e74c3c; text-decoration: none; font-weight: bold;">Instagram</a></p>
                        </div>
                    </div>
                </body>
                </html>';
                
                enviar_correo($correo_socio, $asunto, $mensaje);
            }
        }
    } else {
        mysqli_rollback($conexion);
    }

    return $exito;
}



// --- FUNCIONES AUXILIARES ---

define('MAIL_LOG_FILE', __DIR__ . '/mail.log');

function enviar_correo($destinatario, $asunto, $mensaje)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.ionos.mx';
        $mail->SMTPAuth = true;
        $mail->Username = 'prueba@sandysgym.com';
        $mail->Password = 'Mor@ch117@';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('prueba@sandysgym.com', 'Sandys Gym');
        $mail->addAddress($destinatario);
        
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje;

        $mail->send();

        $log_message = "[" . date("Y-m-d H:i:s") . "] ÉXITO: Correo enviado a '$destinatario' con asunto '$asunto'.\n";
        file_put_contents(MAIL_LOG_FILE, $log_message, FILE_APPEND);
        
        return true;
    } catch (Exception $e) {
        $error_message = "[" . date("Y-m-d H:i:s") . "] ERROR: No se pudo enviar el correo a '$destinatario'. Asunto: '$asunto'. Detalles: " . $mail->ErrorInfo . "\n";
        file_put_contents(MAIL_LOG_FILE, $error_message, FILE_APPEND);
        
        return false;
    }
}

function obtener_correo_socio($id_socio)
{
    global $conexion;
    $query = "SELECT soc_correo FROM san_socios WHERE soc_id_socio = $id_socio";
    $resultado = mysqli_query($conexion, $query);
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $fila = mysqli_fetch_assoc($resultado);
        return $fila['soc_correo'];
    }
    return false;
}

function obtener_detalle_articulo($id_articulo)
{
    global $conexion, $id_consorcio;
    $query = "SELECT * FROM san_articulos WHERE art_id_articulo = $id_articulo AND art_id_consorcio = $id_consorcio";
    $resultado = mysqli_query($conexion, $query);
    if ($resultado && $fila = mysqli_fetch_assoc($resultado)) {
        return $fila;
    }
    return false;
}

function obtener_saldo_socio($id_socio)
{
    global $conexion, $id_empresa;
    $query = "SELECT soc_mon_saldo AS saldo FROM san_socios WHERE soc_id_socio = $id_socio AND soc_id_empresa = $id_empresa";
    $resultado = mysqli_query($conexion, $query);
    if ($resultado && $fila = mysqli_fetch_assoc($resultado)) {
        return (float)$fila['saldo'];
    }
    return false;
}

function lista_articulos()
{
    global $conexion, $id_empresa, $id_consorcio;
    $datos = "";
    $colspan = 3;
    $query = "SELECT art_id_articulo AS id_articulo, art_codigo AS codigo, art_descripcion AS descripcion, stk_existencia AS existencia, ROUND(art_precio, 2) AS precio
              FROM san_articulos
              INNER JOIN san_stock ON stk_id_articulo = art_id_articulo
              AND stk_id_empresa = $id_empresa
              AND art_id_consorcio = $id_consorcio
              AND art_status = 'A'
              ORDER BY existencia DESC, descripcion";
    $resultado = mysqli_query($conexion, $query);
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $class = $fila['existencia'] <= 0 ? "danger" : '';
            $datos .= "<tr onclick='agregar_articulo_venta($fila[id_articulo])' class='$class'>
                         <td>" . $fila['descripcion'] . "</td>
                         <td class='text-right'>" . $fila['existencia'] . "</td>
                         <td class='text-right'>$" . $fila['precio'] . "</td>
                       </tr>";
        }
    } else {
        $datos = "<tr><td colspan='$colspan'>" . mysqli_error($conexion) . "</td></tr>";
    }
    if (!$datos) {
        $datos = "<tr><td colspan='$colspan'>No hay datos.</td></tr>";
    }
    return $datos;
}
?>
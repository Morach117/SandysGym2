<?php

// Se asume que este archivo se incluye para tener la función global de envío de correo
require_once 'correo.php'; 

function obtener_prepago()
{
    global $conexion, $id_empresa;
    
    $id_socio = request_var('id_socio', 0);
    
    $query = "SELECT CONCAT(soc_apepat, ' ', soc_apemat, ' ', soc_nombres) AS nombre,
                     soc_mon_saldo AS saldo,
                     soc_id_socio AS id_socio
              FROM san_socios
              WHERE soc_id_socio = $id_socio
              AND soc_id_empresa = $id_empresa";
    
    $resultado = mysqli_query($conexion, $query);
    
    if ($resultado) {
        if ($fila = mysqli_fetch_assoc($resultado)) {
            return $fila;
        } else {
            // No se encontraron resultados, es mejor retornar un array vacío o false
            return false;
        }
    } else {
        // Hubo un error en la consulta
        return false;
    }
}

function obtener_prepago_detalle()
{
    // Esta función se mantiene igual, ya que es para la vista original no-datatables.
    global $conexion;
    $id_socio = request_var('id_socio', 0);
    $datos = "";
    $colspan = 7;
    $query = "SELECT pred_id_pdetalle AS id_pdetalle,
                     pred_descripcion AS p_descripcion,
                     ROUND(pred_importe, 2) AS importe,
                     ROUND(pred_saldo, 2) AS saldo,
                     CASE pred_movimiento WHEN 'R' THEN 'Resta' WHEN 'S' THEN 'Suma' END AS movimiento,
                     DATE_FORMAT(pred_fecha, '%d-%m-%Y') AS fecha,
                     LOWER(DATE_FORMAT(pred_fecha, '%r')) AS hora
              FROM san_prepago_detalle
              WHERE pred_id_socio = $id_socio
              ORDER BY id_pdetalle DESC";
    
    $resultado = mysqli_query($conexion, $query);
    if ($resultado) {
        $i = 1;
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $datos .= "<tr>
                         <td>$i</td>
                         <td>$fila[p_descripcion]</td>
                         <td class='text-right'>$$fila[importe]</td>
                         <td class='text-right'>$$fila[saldo]</td>
                         <td>$fila[movimiento]</td>
                         <td>$fila[fecha]</td>
                         <td>$fila[hora]</td>
                       </tr>";
            $i++;
        }
    } else {
        $datos = "<tr><td colspan='$colspan'>" . mysqli_error($conexion) . "</td></tr>";
    }
    if (!$datos) {
        $datos = "<tr><td colspan='$colspan'>No hay datos.</td></tr>";
    }
    return $datos;
}


function actualizar_prepago()
{
    global $conexion, $id_usuario, $id_empresa, $gbl_key, $id_consorcio;

    $prep_importe = request_var('prep_importe', 0.0);
    $prep_id_socio = request_var('id_socio', 0);
    $fecha_mov = date('Y-m-d H:i:s');
    $mensaje = [];

    mysqli_autocommit($conexion, false);

    try {
        // 1. Primer abono al saldo del socio
        $query_abono = "UPDATE san_socios SET soc_mon_saldo = soc_mon_saldo + $prep_importe WHERE soc_id_socio = $prep_id_socio AND soc_id_empresa = $id_empresa";
        if (!mysqli_query($conexion, $query_abono) || mysqli_affected_rows($conexion) == 0) {
            throw new Exception("No se pudo aplicar el abono inicial al socio.");
        }

        // 2. Obtener datos actualizados del socio
        $query_socio = "SELECT soc_mon_saldo AS saldo, soc_correo AS email, soc_nombres AS nombre FROM san_socios WHERE soc_id_socio = $prep_id_socio AND soc_id_empresa = $id_empresa FOR UPDATE";
        $resultado_socio = mysqli_query($conexion, $query_socio);
        if (!$resultado_socio || mysqli_num_rows($resultado_socio) == 0) {
            throw new Exception("No se pudieron obtener los detalles del socio tras el abono.");
        }
        $fila_saldo = mysqli_fetch_assoc($resultado_socio);
        $saldo_parcial = $fila_saldo['saldo'];
        $email = $fila_saldo['email'];
        $name = $fila_saldo['nombre'];

        // 3. Calcular y aplicar incremento promocional
        $query_consorcio = "SELECT con_abono FROM san_consorcios WHERE con_id_consorcio = $id_consorcio";
        $resultado_consorcio = mysqli_query($conexion, $query_consorcio);
        $porcentaje_incremento = ($resultado_consorcio && mysqli_num_rows($resultado_consorcio) > 0) ? floatval(mysqli_fetch_assoc($resultado_consorcio)['con_abono']) : 10;
        
        $incremento = round($prep_importe * ($porcentaje_incremento / 100), 2);
        $nuevo_saldo_final = $saldo_parcial + $incremento;

        $query_update_final = "UPDATE san_socios SET soc_mon_saldo = $nuevo_saldo_final WHERE soc_id_socio = $prep_id_socio AND soc_id_empresa = $id_empresa";
        if (!mysqli_query($conexion, $query_update_final)) {
            throw new Exception("No se pudo actualizar el saldo final con el incremento.");
        }

        // 4. Insertar los dos movimientos en el detalle
        $datos_abono = ['pred_descripcion' => 'ABONO A CUENTA PREPAGO', 'pred_importe' => $prep_importe, 'pred_saldo' => $nuevo_saldo_final, 'pred_movimiento' => 'S', 'pred_fecha' => $fecha_mov, 'pred_id_socio' => $prep_id_socio, 'pred_id_usuario' => $id_usuario];
        if (!mysqli_query($conexion, construir_insert('san_prepago_detalle', $datos_abono))) {
            throw new Exception("No se pudo registrar el detalle del abono.");
        }

        if ($incremento > 0) {
            $datos_incremento = ['pred_descripcion' => "INCREMENTO PROMOCIONAL ($porcentaje_incremento%)", 'pred_importe' => $incremento, 'pred_saldo' => $nuevo_saldo_final, 'pred_movimiento' => 'A', 'pred_fecha' => $fecha_mov, 'pred_id_socio' => $prep_id_socio, 'pred_id_usuario' => $id_usuario];
            if (!mysqli_query($conexion, construir_insert('san_prepago_detalle', $datos_incremento))) {
                throw new Exception("No se pudo registrar el detalle del incremento.");
            }
        }

        // 5. Si todo fue exitoso, confirmar la transacción
        mysqli_commit($conexion);
        
        // ==================================================================
        // INICIO DE LA NUEVA LÓGICA DE ENVÍO DE CORREO
        // ==================================================================
        if (isset($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $asunto = 'Confirmación de Abono a tu Monedero - Sandy\'s Gym';
            
            $mensaje_html = '
            <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>' . htmlspecialchars($asunto) . '</title>
            <style>
                body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; }
                .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden; }
                .header { text-align: center; padding: 30px 20px; background-color: #222222; }
                .content { padding: 35px 30px; color: #555555; line-height: 1.7; }
                .content h1 { color: #222222; font-size: 24px; margin-top:0; }
                .summary-table { width: 100%; margin-bottom: 25px; } .summary-table td { padding: 12px 0; border-bottom: 1px solid #eeeeee; }
                .summary-table .label { font-weight: bold; color: #333; } .summary-table .value { text-align: right; font-weight: 500; }
                .card { border-left: 5px solid #28a745; background-color: #f8f9fa; padding: 20px; margin-bottom: 25px; border-radius: 5px;}
                .footer { background-color: #222222; color: #aaaaaa; text-align: center; padding: 25px 20px; font-size: 13px; }
                .footer a { color: #e74c3c; text-decoration: none; font-weight: bold; }
            </style></head><body>
            <div class="container">
                <div class="header"><img src="https://sergym.com/imagenes/empresa_1.png" alt="Sandy\'s Gym Logo" style="max-width:150px;"></div>
                <div class="content">
                    <h1>Abono a tu Monedero Electrónico</h1>
                    <p>Hola, <strong>' . htmlspecialchars($name) . '</strong>,<br>Te confirmamos que se ha realizado un abono a tu monedero en nuestras instalaciones.</p>
                    <div class="card">
                        <h2>¡Saldo a tu favor!</h2>
                        <table class="summary-table" style="margin-bottom:0;">
                            <tr><td>Abono realizado</td><td class="value">+$' . number_format($prep_importe, 2) . '</td></tr>
                            <tr><td>Incremento de promoción (' . $porcentaje_incremento . '%)</td><td class="value">+$' . number_format($incremento, 2) . '</td></tr>
                            <tr><td class="label" style="font-size: 18px;">Nuevo Saldo Total</td><td class="value" style="font-size: 18px; color: #28a745;"><strong>$' . number_format($nuevo_saldo_final, 2) . '</strong></td></tr>
                        </table>
                    </div>
                    <p>Fecha del movimiento: ' . date('d/m/Y H:i:s', strtotime($fecha_mov)) . '</p>
                </div>
                <div class="footer">
                    <p style="margin:5px 0;"><strong>SANDY\'S GYM</strong></p><p style="margin:5px 0;">Av. Miguel Hidalgo 308, Bienestar Social, 29077 Tuxtla Gutiérrez, Chis.</p><p style="margin:5px 0;"><a href="https://www.facebook.com/gymsandy">Facebook</a> &nbsp;&middot;&nbsp; <a href="https://www.instagram.com/sandysgym/">Instagram</a></p>
                </div>
            </div></body></html>';

            // Llamada a la función global de envío de correo
            enviar_correo($email, $asunto, $mensaje_html, $prep_id_socio);
        }
        // ==================================================================
        // FIN DE LA NUEVA LÓGICA DE ENVÍO DE CORREO
        // ==================================================================

        $token = hash_hmac('md5', $prep_id_socio, $gbl_key);
        $mensaje = ['num' => 1, 'msj' => "El Prepago se ha agregado de manera correcta.", 'IDS' => $prep_id_socio, 'tkn' => $token];

    } catch (Exception $e) {
        // Si algo falla, revertir todos los cambios y notificar el error
        mysqli_rollback($conexion);
        $mensaje = ['num' => 0, 'msj' => $e->getMessage()];
    }

    return $mensaje;
}

// La función original de enviar_correo que estaba aquí ha sido eliminada.
?>
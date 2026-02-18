<?php

require_once 'correo.php';

// =================================================================================
// INICIO DE FUNCIONES DE LA APLICACIÓN
// =================================================================================

function obtener_servicios($default = '')
{
    global $conexion, $id_consorcio, $id_giro;

    $datos = "<option value=''>Selecciona...</option>";

    $query = "SELECT ser_id_servicio AS id_servicio, 
                     ser_clave AS clave,
                     ser_descripcion AS descripcion,
                     ROUND( ser_cuota, 2 ) AS cuota,
                     ser_meses AS meses
               FROM  san_servicios 
               WHERE ser_tipo = 'PERIODO'
                     AND ser_id_consorcio = $id_consorcio
                     AND ser_id_giro = $id_giro
                     AND ser_status != 'D'";

    $resultado = mysqli_query($conexion, $query);

    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $servicio = $fila['id_servicio'] . '-' . $fila['meses'];

            if ($default == $servicio)
                $datos .= "<option selected value='$servicio'>$fila[descripcion] - $$fila[cuota]</option>";
            else
                $datos .= "<option value='$servicio'>$fila[descripcion] - $$fila[cuota]</option>";
        }
    } else {
        echo "Error: " . mysqli_error($conexion);
    }

    return $datos;
}


function obtener_servicio($id_servicio)
{
    global $conexion, $id_consorcio, $id_giro;
    
    $query   = "  SELECT   ser_id_servicio AS id_servicio, 
                           ser_clave AS clave,
                           ser_descripcion AS descripcion,
                           ROUND( ser_cuota, 2 ) AS cuota,
                           ser_meses AS meses
                   FROM    san_servicios 
                   WHERE   ser_id_servicio = $id_servicio
                   AND     ser_id_consorcio = $id_consorcio
                   AND     ser_id_giro = $id_giro";
    
    $resultado   = mysqli_query( $conexion, $query );
    
    if( $resultado )
        if( $fila = mysqli_fetch_assoc( $resultado ) )
            return $fila;
    
    return false;
}

function lista_pagos_socio()
{
    Global $conexion, $id_empresa, $id_consorcio, $id_giro;
    
    $datos     = "";
    $colspan   = 6;
    $fecha_mov   = date( 'Y-m-d' );
    $id_socio  = request_var( 'id_socio', 0 );
    
    $query       = "   SELECT      pag_id_pago,
                                   pag_id_socio,
                                   pag_status AS status,
                                   ser_descripcion,
                                   LOWER( DATE_FORMAT( pag_fecha_pago, '%d-%m-%Y %r' ) ) AS fecha_pago,
                                   DATE_FORMAT( pag_fecha_ini, '%d-%m-%Y' ) AS fecha_ini,
                                   DATE_FORMAT( pag_fecha_fin, '%d-%m-%Y' ) AS fecha_fin,
                                   ROUND( pag_importe, 2 ) AS importe,
                                   IF( '$fecha_mov' > pag_fecha_fin, 'VENCIDO', 'VIGENTE' ) AS vigencia
                         FROM      san_pagos 
                         INNER JOIN   san_servicios ON ser_id_servicio = pag_id_servicio
                         WHERE      pag_id_socio = $id_socio
                         AND          pag_id_empresa = $id_empresa
                         AND          ser_id_consorcio = $id_consorcio
                         AND          ser_id_giro = $id_giro
                         ORDER BY   pag_id_pago DESC";
    
    $resultado   = mysqli_query( $conexion, $query );
    
    if( $resultado )
    {
        while( $fila = mysqli_fetch_assoc( $resultado ) )
        {
            if( $fila['vigencia'] == 'VIGENTE' && $fila['status'] == 'A' )
                $opciones = "<a href='.?s=socios&i=eliminarp&id_pago=$fila[pag_id_pago]&id_socio=$fila[pag_id_socio]'><span class='text-danger glyphicon glyphicon-remove-sign'></span></a>";
            else
                $opciones = "";
            
            $class   = ( $fila['status'] == 'E' ) ? 'danger':'';
            
            $datos   .= "<tr class='$class'>
                              <td>$opciones</td>
                              <td>$fila[ser_descripcion]</td>
                              <td>$fila[fecha_pago]</td>
                              <td>$fila[fecha_ini]</td>
                              <td>$fila[fecha_fin]</td>
                              <td class='text-right'>$$fila[importe]</td>
                         </tr>";
        }
    }
    else
        $datos = "<tr><td colspan='$colspan'>Ocurrió un error al obtener los datos. ".mysqli_error( $conexion )."</td></tr>";
    
    if( !$datos )
        $datos = "<tr><td colspan='$colspan'>No hay datos.</td></tr>";
    
    return $datos;
}



/**
 * Procesa el guardado de un nuevo pago de membresía para un socio principal y sus integrantes.
 * Incluye validaciones, manejo de códigos promocionales, descuento de socio, monedero, y envío de email.
 */
function guardar_pago_socio()
{
    global $conexion, $id_usuario, $id_empresa, $gbl_key, $id_consorcio;

    // --- Obtención de datos del formulario ---
    // Socio Principal
    $id_socio = request_var('id_socio', 0);
    $pag_fecha_pago = fecha_formato_mysql(request_var('pag_fecha_pago', date('d-m-Y')));
    list($id_servicio_orig, $meses) = explode('-', request_var('servicio', ''));
    $pag_fecha_ini_orig = fecha_formato_mysql(request_var('pag_fecha_ini', ''));
    $pag_fecha_fin_orig = fecha_formato_mysql(request_var('pag_fecha_fin', ''));
    
    // Datos de pago
    $importe_base = request_var('pag_importe', 0.0);
    $fecha_mov = $pag_fecha_pago . " " . date('H:i:s');
    $v_metodo_pago = request_var('m_pago', '');
    $codigo_promocion = request_var('codigo_promocion', '');
    
    // Socios Adicionales (Integrantes)
    $id_integrante1 = request_var('integrante1', 0);
    $id_integrante2 = request_var('integrante2', 0);
    $id_pareja = request_var('pareja', 0);
    
    // Fechas para Socios Adicionales
    $pag_fecha_ini1 = fecha_formato_mysql(request_var('pag_fecha_ini1', ''));
    $pag_fecha_fin1 = fecha_formato_mysql(request_var('pag_fecha_fin1', ''));
    $pag_fecha_ini2 = fecha_formato_mysql(request_var('pag_fecha_ini2', ''));
    $pag_fecha_fin2 = fecha_formato_mysql(request_var('pag_fecha_fin2', ''));
    $pag_fecha_ini_pareja = fecha_formato_mysql(request_var('pag_fecha_ini_pareja', ''));
    $pag_fecha_fin_pareja = fecha_formato_mysql(request_var('pag_fecha_fin_pareja', ''));

    mysqli_autocommit($conexion, false); // Iniciar transacción

    try {
        // 1. Validaciones iniciales
        if (!$pag_fecha_ini_orig || !$pag_fecha_fin_orig || !$id_servicio_orig || !$id_socio) {
            throw new Exception("Faltan datos esenciales (socio, servicio o fechas) para procesar el pago.");
        }

        $servicio = obtener_servicio($id_servicio_orig);
        if (!$servicio) {
            throw new Exception("El servicio seleccionado no es válido.");
        }

        // 2. Cálculo de importe y descuentos
        $importe = ($servicio['clave'] != 'MEN PARCIAL') ? $servicio['cuota'] : $importe_base;
        if ($importe < 0) {
            throw new Exception("El importe no puede ser negativo.");
        }

        $descuento_total_porcentaje = 0.0;

        // --- INICIO DE LÓGICA DE CÓDIGO PROMOCIONAL ---
        if (!empty($codigo_promocion)) {
            $current_date = date("Y-m-d");
            $query_validar_codigo = "SELECT p.porcentaje_descuento, p.tipo_promocion, c.status
                                     FROM san_codigos c
                                     INNER JOIN san_promociones p ON c.id_promocion = p.id_promocion
                                     WHERE c.codigo_generado = ? 
                                     AND c.status = '1' 
                                     AND p.vigencia_inicial <= ? 
                                     AND p.vigencia_final >= ?";
            
            $stmt = mysqli_prepare($conexion, $query_validar_codigo);
            mysqli_stmt_bind_param($stmt, "sss", $codigo_promocion, $current_date, $current_date);
            mysqli_stmt_execute($stmt);
            $resultado_validar_codigo = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($resultado_validar_codigo) > 0) {
                $fila_promocion = mysqli_fetch_assoc($resultado_validar_codigo);
                $descuento_total_porcentaje += floatval($fila_promocion['porcentaje_descuento']);

                if ($fila_promocion['tipo_promocion'] == 'Individual') {
                    $query_actualizar_codigo = "UPDATE san_codigos SET status = '0' WHERE codigo_generado = ?";
                    $stmt_update = mysqli_prepare($conexion, $query_actualizar_codigo);
                    mysqli_stmt_bind_param($stmt_update, "s", $codigo_promocion);
                    mysqli_stmt_execute($stmt_update);
                }
                
                $query_insertar_usado = "INSERT INTO san_codigos_usados (id_socio, codigo_generado, fecha_usado, id_empresa) VALUES (?, ?, ?, ?)";
                $stmt_insert = mysqli_prepare($conexion, $query_insertar_usado);
                mysqli_stmt_bind_param($stmt_insert, "issi", $id_socio, $codigo_promocion, $fecha_mov, $id_empresa);
                mysqli_stmt_execute($stmt_insert);
            } else {
                throw new Exception("El código de promoción no es válido, ya fue utilizado o ha expirado.");
            }
        }
        // --- FIN DE LÓGICA DE CÓDIGO PROMOCIONAL ---

        // --- INICIO DE LÓGICA DE DESCUENTO DE SOCIO ---
        $query_descuento_socio = "SELECT soc_descuento FROM san_socios WHERE soc_id_socio = ?";
        $stmt_descuento = mysqli_prepare($conexion, $query_descuento_socio);
        mysqli_stmt_bind_param($stmt_descuento, "i", $id_socio);
        mysqli_stmt_execute($stmt_descuento);
        $resultado_descuento = mysqli_stmt_get_result($stmt_descuento);
        if ($fila_descuento = mysqli_fetch_assoc($resultado_descuento)) {
            $descuento_total_porcentaje += floatval($fila_descuento['soc_descuento']);
        }
        // --- FIN DE LÓGICA DE DESCUENTO DE SOCIO ---

        // Aplicar descuento total al importe
        $importe_final = $importe * (1 - ($descuento_total_porcentaje / 100));

        // 3. Procesamiento del monedero
        $pag_monedero = 0;
        $incremento_monedero = 0;
        $saldo_actual = obtener_saldo_monedero($id_socio);
        $nuevo_saldo = $saldo_actual;

        if ($v_metodo_pago == 'M') { // Pago con monedero
            if ($saldo_actual < $importe_final) {
                throw new Exception("Saldo en monedero insuficiente. Saldo actual: $" . number_format($saldo_actual, 2));
            }
            $pag_monedero = $importe_final;
            $nuevo_saldo = $saldo_actual - $pag_monedero;
            
            $query_update_saldo = "UPDATE san_socios SET soc_mon_saldo = ? WHERE soc_id_socio = ?";
            $stmt_saldo = mysqli_prepare($conexion, $query_update_saldo);
            mysqli_stmt_bind_param($stmt_saldo, "di", $nuevo_saldo, $id_socio);
            if (!mysqli_stmt_execute($stmt_saldo)) {
                throw new Exception("Error al actualizar el saldo del monedero.");
            }

            $detalle_retiro = ['pred_descripcion' => 'Pago de Membresía', 'pred_importe' => $pag_monedero, 'pred_saldo' => $nuevo_saldo, 'pred_movimiento' => 'R', 'pred_fecha' => $fecha_mov, 'pred_id_socio' => $id_socio, 'pred_id_usuario' => $id_usuario];
            mysqli_query($conexion, construir_insert('san_prepago_detalle', $detalle_retiro));

        }
        $query_consorcio = "SELECT con_mensualidad FROM san_consorcios WHERE con_id_consorcio = ?";
        $stmt_consorcio = mysqli_prepare($conexion, $query_consorcio);
        mysqli_stmt_bind_param($stmt_consorcio, "i", $id_consorcio);
        mysqli_stmt_execute($stmt_consorcio);
        $res_consorcio = mysqli_stmt_get_result($stmt_consorcio);
        $porcentaje = ($fila = mysqli_fetch_assoc($res_consorcio)) ? floatval($fila['con_mensualidad']) : 0;

        if ($porcentaje > 0) {
            $incremento_monedero = round($importe_final * ($porcentaje / 100), 2);
            if ($incremento_monedero > 0) {
                // Se suma el incremento al saldo ya actualizado (que puede haber sido deducido)
                $nuevo_saldo += $incremento_monedero; 

                // Actualiza el saldo en la BD por el ABONO
                $query_update_bono = "UPDATE san_socios SET soc_mon_saldo = ? WHERE soc_id_socio = ?";
                $stmt_saldo_inc = mysqli_prepare($conexion, $query_update_bono);
                mysqli_stmt_bind_param($stmt_saldo_inc, "di", $nuevo_saldo, $id_socio);
                if (!mysqli_stmt_execute($stmt_saldo_inc)) {
                    throw new Exception("Error al abonar bonificación al monedero.");
                }
                $detalle_abono = ['pred_descripcion' => 'Abono por pago de Membresía', 'pred_importe' => $incremento_monedero, 'pred_saldo' => $nuevo_saldo, 'pred_movimiento' => 'A', 'pred_fecha' => $fecha_mov, 'pred_id_socio' => $id_socio, 'pred_id_usuario' => $id_usuario];
                mysqli_query($conexion, construir_insert('san_prepago_detalle', $detalle_abono));
            }
        }

        // 4. Inserción de registros de pago para todos los socios involucrados
        $socios_a_procesar = [$id_socio, $id_integrante1, $id_integrante2, $id_pareja];
        $id_pago_principal = 0;
        $token = '';

        foreach ($socios_a_procesar as $socio_actual_id) {
            if ($socio_actual_id > 0) {
                // Determinar el servicio y fechas para el socio actual
                $id_servicio_actual = $id_servicio_orig;
                $pag_fecha_ini_actual = $pag_fecha_ini_orig;
                $pag_fecha_fin_actual = $pag_fecha_fin_orig;
                
                // Lógica de servicios para integrantes
                if ($socio_actual_id == $id_integrante1) {
                    $id_servicio_actual = 125;
                    $pag_fecha_ini_actual = $pag_fecha_ini1;
                    $pag_fecha_fin_actual = $pag_fecha_fin1;
                } elseif ($socio_actual_id == $id_integrante2) {
                    $id_servicio_actual = 126;
                    $pag_fecha_ini_actual = $pag_fecha_ini2;
                    $pag_fecha_fin_actual = $pag_fecha_fin2;
                } elseif ($socio_actual_id == $id_pareja) {
                    $id_servicio_actual = 125;
                    $pag_fecha_ini_actual = $pag_fecha_ini_pareja;
                    $pag_fecha_fin_actual = $pag_fecha_fin_pareja;
                }

                // Configurar importes. Solo el socio principal tiene montos de pago.
                if ($socio_actual_id == $id_socio) {
                    $pago_efectivo = ($v_metodo_pago == 'E') ? $importe_final : 0;
                    $pago_tarjeta = ($v_metodo_pago == 'T') ? $importe_final : 0;
                    $pago_monedero_actual = $pag_monedero;
                    $importe_pago = round($importe_final, 2);
                } else {
                    $pago_efectivo = 0;
                    $pago_tarjeta = 0;
                    $pago_monedero_actual = 0;
                    $importe_pago = 0;
                }

                $datos_sql = [
                    'pag_id_socio' => $socio_actual_id,
                    'pag_fecha_pago' => $fecha_mov,
                    'pag_id_servicio' => $id_servicio_actual,
                    'pag_fecha_ini' => $pag_fecha_ini_actual,
                    'pag_fecha_fin' => $pag_fecha_fin_actual,
                    'pag_efectivo' => $pago_efectivo,
                    'pag_tarjeta' => $pago_tarjeta,
                    'pag_monedero' => $pago_monedero_actual,
                    'pag_importe' => $importe_pago,
                    'pag_tipo_pago' => $v_metodo_pago,
                    'pag_id_usuario' => $id_usuario,
                    'pag_id_empresa' => $id_empresa
                ];

                $query_pago = construir_insert('san_pagos', $datos_sql);
                if (!mysqli_query($conexion, $query_pago)) {
                    throw new Exception("No se pudo guardar el pago para el socio ID $socio_actual_id. Error: " . mysqli_error($conexion));
                }

                // Guardar el ID del pago principal para el ticket y el correo
                if ($socio_actual_id == $id_socio) {
                    $id_pago_principal = mysqli_insert_id($conexion);
                    $token = hash_hmac('md5', $id_pago_principal, $gbl_key);
                }
            }
        }
        
        // 5. Confirmar transacción
        mysqli_commit($conexion);
        $exito = ['num' => 1, 'msj' => 'Pago registrado correctamente.', 'IDS' => $id_socio, 'IDP' => $id_pago_principal, 'tkn' => $token];
        
        // 6. Enviar correo (fuera de la transacción)
        if ($id_socio > 0 && ($correo_socio = obtener_correo_socio($id_socio))) {
            $nombre_socio = obtener_nombre_socio($id_socio);
            $asunto = "Confirmación de tu pago en Sandys Gym - Recibo No. " . $id_pago_principal;
            
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
                    .header img { max-width: 180px; }
                    .content { padding: 35px 30px; color: #555555; line-height: 1.7; }
                    .content h1 { color: #222222; font-size: 24px; margin-top: 0; }
                    .summary-table { width: 100%; margin-bottom: 25px; }
                    .summary-table td { padding: 12px 0; border-bottom: 1px solid #eeeeee; }
                    .summary-table .label { font-weight: bold; color: #333; }
                    .summary-table .value { text-align: right; font-weight: 500; }
                    .summary-table .discount-value { text-align: right; color: #28a745; }
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
                        <h1>Confirmación de Pago</h1>
                        <p>Hola, <strong>' . htmlspecialchars($nombre_socio) . '</strong>,<br>¡Gracias por renovar tu compromiso con nosotros! Tu pago se ha procesado correctamente.</p>
                        <table class="summary-table">
                            <tr><td class="label">Recibo No.</td><td class="value">' . $id_pago_principal . '</td></tr>
                            <tr><td class="label">Fecha de Pago</td><td class="value">' . date('d/m/Y H:i:s', strtotime($fecha_mov)) . '</td></tr>';
            
            if ($descuento_total_porcentaje > 0) {
                $monto_descuento = $importe - $importe_final;
                $mensaje .= '<tr><td class="label">Precio Original</td><td class="value">$' . number_format($importe, 2) . '</td></tr>
                             <tr><td class="label">Descuento (' . $descuento_total_porcentaje . '%)</td><td class="discount-value">-$' . number_format($monto_descuento, 2) . '</td></tr>';
            }

            $mensaje .= '<tr><td class="label" style="font-size: 18px; border-top: 2px solid #333;">Total Pagado</td><td class="value" style="font-size: 18px; color: #e74c3c; border-top: 2px solid #333;"><strong>$' . number_format($importe_final, 2) . '</strong></td></tr></table>';
            
            if ($pag_monedero > 0) {
                $mensaje .= '<div class="card"><h2>Movimiento en tu Monedero</h2><table class="summary-table" style="margin-bottom:0;"><tr><td>Monto pagado con monedero</td><td class="value">-$' . number_format($pag_monedero, 2) . '</td></tr><tr><td class="label">Saldo restante</td><td class="value">$' . number_format($nuevo_saldo, 2) . '</td></tr></table></div>';
            }

            if ($incremento_monedero > 0) {
                $mensaje .= '<div class="card success"><h2>¡Saldo a tu favor!</h2><table class="summary-table" style="margin-bottom:0;"><tr><td>Abono por tu pago</td><td class="value">+$' . number_format($incremento_monedero, 2) . '</td></tr><tr><td class="label">Nuevo saldo total</td><td class="value">$' . number_format($nuevo_saldo, 2) . '</td></tr></table></div>';
            }

            $mensaje .= '<h2>Detalle de tu Servicio</h2><table class="service-table"><thead><tr><th>Servicio</th><th>Periodo de Vigencia</th></tr></thead><tbody><tr><td>' . htmlspecialchars($servicio['descripcion']) . '</td><td>' . date('d/m/Y', strtotime($pag_fecha_ini_orig)) . ' al ' . date('d/m/Y', strtotime($pag_fecha_fin_orig)) . '</td></tr></tbody></table>';
            $mensaje .= '</div><div class="footer"><p><strong>SANDY\'S GYM</strong></p><p>29077, Av Miguel Hidalgo 308, Bienestar Soc, 29077 Tuxtla Gutiérrez, Chis.</p><p><a href="https://www.facebook.com/gymsandy">Facebook</a> &nbsp;&middot;&nbsp; <a href="https://www.instagram.com/sandysgym/">Instagram</a></p></div></div></body></html>';
            // --- FIN DE LA PLANTILLA ---
            enviar_correo($correo_socio, $asunto, $mensaje);
        }
    } catch (Exception $e) {
        mysqli_rollback($conexion); // Revertir cambios si algo falla
        $exito = ['num' => 0, 'msj' => $e->getMessage()];
    }

    return $exito;
}
// =================================================================================
// FUNCIONES AUXILIARES (Monedero, Referidos, etc.)
// =================================================================================

function aplicar_bonificacion_monedero($id_socio, $monto, $fecha, $concepto)
{
    global $conexion, $id_usuario;
    
    // Esta función debe ejecutarse dentro de una transacción o tener la suya propia.
    $query_update = "UPDATE san_socios SET soc_mon_saldo = soc_mon_saldo + $monto WHERE soc_id_socio = $id_socio";
    mysqli_query($conexion, $query_update);

    $nuevo_saldo = obtener_saldo_monedero($id_socio);

    $detalle = [
        'pred_descripcion' => $concepto,
        'pred_importe' => $monto,
        'pred_saldo' => $nuevo_saldo,
        'pred_movimiento' => 'A', // Abono
        'pred_fecha' => $fecha,
        'pred_id_socio' => $id_socio,
        'pred_id_usuario' => $id_usuario
    ];
    mysqli_query($conexion, construir_insert('san_prepago_detalle', $detalle));
}

function obtener_saldo_monedero($id_socio)
{
    global $conexion;
    // Bloquea la fila para la transacción si se llama dentro de una.
    $query = "SELECT soc_mon_saldo FROM san_socios WHERE soc_id_socio = $id_socio FOR UPDATE"; 
    $result = mysqli_query($conexion, $query);
    if ($fila = mysqli_fetch_assoc($result)) {
        return (float)$fila['soc_mon_saldo'];
    }
    return 0.0;
}


/**
 * Obtiene el nombre completo de un socio para personalizar el correo.
 */
function obtener_nombre_socio($id_socio) {
    global $conexion;
    $nombre_completo = 'Socio'; // Valor por defecto
    $query = "SELECT CONCAT(soc_nombres, ' ', soc_apepat) AS nombre FROM san_socios WHERE soc_id_socio = $id_socio";
    $resultado = mysqli_query($conexion, $query);
    if ($resultado && $fila = mysqli_fetch_assoc($resultado)) {
        $nombre_completo = $fila['nombre'];
    }
    return $nombre_completo;
}

?>
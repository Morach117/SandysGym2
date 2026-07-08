<?php
function obtener_servicios($default = '')
{
    global $conexion, $id_consorcio, $id_giro;

    $datos = "<option value=''>Selecciona...</option>";

    $query = "SELECT ser_id_servicio AS id_servicio, 
                     ser_clave AS clave,
                     ser_descripcion AS descripcion,
                     ROUND( ser_cuota, 2 ) AS cuota,
                     ser_meses AS meses
              FROM   san_servicios 
              WHERE  ser_tipo = 'PERIODO'
                     AND ser_id_consorcio = $id_consorcio
                     AND ser_id_giro = $id_giro
                     AND ser_status != 'D'"; // Excluir servicios con estatus 0 y 'D'

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


	
	function obtener_servicio( $id_servicio )
	{
		global $conexion, $id_consorcio, $id_giro;
		
		$query		= "	SELECT 	ser_id_servicio AS id_servicio, 
								ser_clave AS clave,
								ser_descripcion AS descripcion,
								ROUND( ser_cuota, 2 ) AS cuota,
								ser_meses AS meses
						FROM 	san_servicios 
						WHERE 	ser_id_servicio = $id_servicio
						AND		ser_id_consorcio = $id_consorcio
						AND		ser_id_giro = $id_giro";
		
		$resultado	= mysqli_query( $conexion, $query );
		
		if( $resultado )
			if( $fila = mysqli_fetch_assoc( $resultado ) )
				return $fila;
		
		return false;
	}
	
	function lista_pagos_socio()
	{
		Global $conexion, $id_empresa, $id_consorcio, $id_giro;
		
		$datos		= "";
		$colspan	= 6;
		$fecha_mov	= date( 'Y-m-d' );
		$id_socio	= request_var( 'id_socio', 0 );
		
		$query		= "	SELECT 		pag_id_pago,
									pag_id_socio,
									pag_status AS status,
									ser_descripcion,
									LOWER( DATE_FORMAT( pag_fecha_pago, '%d-%m-%Y %r' ) ) AS fecha_pago,
									DATE_FORMAT( pag_fecha_ini, '%d-%m-%Y' ) AS fecha_ini,
									DATE_FORMAT( pag_fecha_fin, '%d-%m-%Y' ) AS fecha_fin,
									ROUND( pag_importe, 2 ) AS importe,
									IF( '$fecha_mov' > pag_fecha_fin, 'VENCIDO', 'VIGENTE' ) AS vigencia
						FROM 		san_pagos 
						INNER JOIN	san_servicios ON ser_id_servicio = pag_id_servicio
						WHERE 		pag_id_socio = $id_socio
						AND			pag_id_empresa = $id_empresa
						AND			ser_id_consorcio = $id_consorcio
						AND			ser_id_giro = $id_giro
						ORDER BY 	pag_id_pago DESC";
		
		$resultado	= mysqli_query( $conexion, $query );
		
		if( $resultado )
		{
			while( $fila = mysqli_fetch_assoc( $resultado ) )
			{
				if( $fila['vigencia'] == 'VIGENTE' && $fila['status'] == 'A' )
					$opciones = "<a href='.?s=socios&i=eliminarp&id_pago=$fila[pag_id_pago]&id_socio=$fila[pag_id_socio]'><span class='text-danger glyphicon glyphicon-remove-sign'></span></a>";
				else
					$opciones = "";
				
				$class	= ( $fila['status'] == 'E' ) ? 'danger':'';
				
				$datos	.= "<tr class='$class'>
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
	
	function guardar_pago_socio()
	{
		global $conexion, $id_usuario, $id_empresa, $gbl_key;
	
		$exito = array();
		$pag_fecha_pago = fecha_formato_mysql(request_var('pag_fecha_pago', date('d-m-Y')));
		$pag_fecha_ini = fecha_formato_mysql(request_var('pag_fecha_ini', ''));
		$pag_fecha_fin = fecha_formato_mysql(request_var('pag_fecha_fin', ''));
		list($id_servicio, $meses) = explode('-', request_var('servicio', ''));
		$importe = request_var('pag_importe', 0.0); // cuando el admin escribe el monto preferente
		$fecha_mov = $pag_fecha_pago . " " . date('H:i:s');
		$id_socio = request_var('id_socio', 0);
		$codigo_promocion = isset($_POST['codigo_promocion']) ? $_POST['codigo_promocion'] : ''; // Obtener el código de promoción proporcionado por el usuario
	
		// Realizar la consulta para verificar si hay descuentos promocionales permitidos para el servicio
		$query = "SELECT * FROM san_descuentos_promociones WHERE id_servicio = $id_servicio";
		
		// Ejecutar la consulta
		$result = mysqli_query($conexion, $query);
		
		// Verificar si hay algún resultado
		if ($result && mysqli_num_rows($result) > 0) {
			// Si hay resultados, significa que el servicio tiene descuentos promocionales permitidos
	
			// Obtener el descuento del socio desde la tabla de socios
			$query_descuento = "SELECT soc_descuento FROM san_socios WHERE soc_id_socio = $id_socio";
			$resultado_descuento = mysqli_query($conexion, $query_descuento);
			$fila_descuento = mysqli_fetch_assoc($resultado_descuento);
			$descuento = isset($fila_descuento['soc_descuento']) ? floatval($fila_descuento['soc_descuento']) : 0.0;
	
			$v_porcentaje_comision = request_var('comision', 0.0);
			$v_metodo_pago = request_var('m_pago', ''); // E-T
	
			$v_pag_efectivo = 0;
			$v_pag_tarjeta = 0;
			$v_monto_comision = 0;
	
			if ($pag_fecha_ini && $pag_fecha_fin) {
				if ($id_servicio && $meses && $id_socio) {
					$servicio = obtener_servicio($id_servicio);
	
					if ($servicio) {
						if (($servicio['clave'] == 'MEN PARCIAL' && $importe) || $servicio['clave'] != 'MEN PARCIAL') {
							if ($servicio['clave'] != 'MEN PARCIAL') {
								$importe = $servicio['cuota'];
							}
	
							if ($importe > 0) {
								// Verificar si se proporcionó un código de promoción y si está activo
								if (!empty($codigo_promocion)) {
									$current_date = date("Y-m-d");
									$query_validar_codigo = "SELECT p.porcentaje_descuento, p.tipo_promocion, c.status
																FROM san_codigos c
																INNER JOIN san_promociones p ON c.id_promocion = p.id_promocion
																WHERE c.codigo_generado = '$codigo_promocion' 
																AND c.status = '1' 
																AND p.vigencia_inicial <= '$current_date' 
																AND p.vigencia_final >= '$current_date'";
									$resultado_validar_codigo = mysqli_query($conexion, $query_validar_codigo);
	
									if (mysqli_num_rows($resultado_validar_codigo) > 0) {
										$fila_promocion = mysqli_fetch_assoc($resultado_validar_codigo);
										$porcentaje_descuento = $fila_promocion['porcentaje_descuento'];
										$tipo_promocion = $fila_promocion['tipo_promocion'];
										$status = $fila_promocion['status'];
	
										// Verificar si el tipo de promoción es "individual" y el estado es activo
										if ($tipo_promocion == 'Individual' && $status == '1') {
											// Cambiar el estado del código de promoción a utilizado
											$query_actualizar_codigo = "UPDATE san_codigos SET status = '0' WHERE codigo_generado = '$codigo_promocion'";
											mysqli_query($conexion, $query_actualizar_codigo);
										}
	
										// Aplicar descuento si está presente
										$descuento += $porcentaje_descuento; // Sumar el descuento de la promoción al descuento del socio
									} else {
										// Código de promoción no válido
										$exito['num'] = 9;
										$exito['msj'] = "El código de promoción proporcionado no es válido o ya ha sido utilizado.";
										return $exito;
									}
								}
	
								// Aplicar descuento si está presente
								$importe_con_descuento = $importe * (1 - $descuento / 100);
	
								if ($v_metodo_pago == 'E') {
									$v_pag_efectivo = $importe_con_descuento;
								} else {
									$v_pag_tarjeta = $importe_con_descuento;
	
									if ($v_porcentaje_comision > 0) {
										$v_monto_comision = $importe_con_descuento * ($v_porcentaje_comision / 100);
									}
								}
							}
	
							$datos_sql = array(
								'pag_id_socio' => $id_socio,
								'pag_fecha_pago' => $fecha_mov,
								'pag_id_servicio' => $id_servicio,
								'pag_fecha_ini' => $pag_fecha_ini,
								'pag_fecha_fin' => $pag_fecha_fin,
								'pag_efectivo' => $v_pag_efectivo,
								'pag_tarjeta' => $v_pag_tarjeta,
								'pag_comision' => round($v_monto_comision, 2),
								'pag_importe' => round($importe_con_descuento + $v_monto_comision, 2), // Utilizamos el importe con descuento
								'pag_tipo_pago' => $v_metodo_pago,
								'pag_id_usuario' => $id_usuario,
								'pag_id_empresa' => $id_empresa
							);
	
							$query = construir_insert('san_pagos', $datos_sql);
							$resultado = mysqli_query($conexion, $query);
							$id_pago = mysqli_insert_id($conexion);
							$token = hash_hmac('md5', $id_pago, $gbl_key);
	
							if ($resultado && $id_pago && $token) {
								$foto = subir_fotografia();
								
								// Lógica de referidos en Caja (Si es el primer pago del socio)
								procesar_recompensa_referido_caja($id_socio, $fecha_mov);
	
								//operacion exitosa
								$exito['num'] = 1;
								$exito['msj'] = "Pago y fechas guardados correctamente. ";
								$exito['IDS'] = $id_socio;
								$exito['IDP'] = $id_pago;
								$exito['tkn'] = $token;
							} else {
								$exito['num'] = 8;
								$exito['msj'] = "No se ha podido guardar la información de este socio. " . mysqli_error($conexion);
							}
						} else {
							$exito['num'] = 6;
							$exito['msj'] = "No se puede obtener el importe del servicio seleccionado.";
						}
					} else {
						$exito['num'] = 5;
						$exito['msj'] = "Se ha detectado Servicio Parcial pero no se ha indicado el importe a pagar.";
					}
				} else {
					$exito['num'] = 4;
					$exito['msj'] = "No se puede identificar el tipo de servicio seleccionado.";
				}
			} else {
				$exito['num'] = 3;
				$exito['msj'] = "Faltan datos importantes para guardar el pago.";
			}
		} else {
			// Si no hay resultados, el servicio no tiene descuentos promocionales permitidos
			$exito['num'] = 10;
			$exito['msj'] = "El servicio seleccionado no tiene descuentos promocionales permitidos.";
		}
	
		return $exito;
	}

/**
 * Aplica la bonificación en el monedero del socio y registra la transacción en el historial.
 */
function aplicar_bonificacion_monedero($id_socio, $monto, $fecha, $concepto)
{
    global $conexion, $id_usuario;
    
    $query_update = "UPDATE san_socios SET soc_mon_saldo = soc_mon_saldo + $monto WHERE soc_id_socio = $id_socio";
    mysqli_query($conexion, $query_update);

    // Obtener nuevo saldo para registrar
    $query_saldo = "SELECT soc_mon_saldo FROM san_socios WHERE soc_id_socio = $id_socio LIMIT 1";
    $result_saldo = mysqli_query($conexion, $query_saldo);
    $nuevo_saldo = 0.00;
    if ($fila_saldo = mysqli_fetch_assoc($result_saldo)) {
        $nuevo_saldo = (float)$fila_saldo['soc_mon_saldo'];
    }

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

/**
 * Procesa la recompensa para el Padrino de un socio cuando este realiza su primer pago en Caja (Panel legacy).
 */
function procesar_recompensa_referido_caja($id_socio, $fecha_mov) {
    global $conexion, $id_consorcio;
    
    try {
        // 1. Verificar si es su primer pago en la BD
        $queryPagos = "SELECT COUNT(pag_id_pago) as total FROM san_pagos WHERE pag_id_socio = " . (int)$id_socio;
        $resPagos = mysqli_query($conexion, $queryPagos);
        if ($resPagos) {
            $rowPagos = mysqli_fetch_assoc($resPagos);
            if ($rowPagos && (int)$rowPagos['total'] === 1) {
                // 2. Obtener el padrino
                $querySocio = "SELECT soc_id_referido_por, soc_nombres, soc_apepat FROM san_socios WHERE soc_id_socio = " . (int)$id_socio . " LIMIT 1";
                $resSocio = mysqli_query($conexion, $querySocio);
                if ($resSocio && $rowSocio = mysqli_fetch_assoc($resSocio)) {
                    $idPadrino = (int)$rowSocio['soc_id_referido_por'];
                    $nombreNuevoSocio = trim($rowSocio['soc_nombres'] . ' ' . $rowSocio['soc_apepat']);
                    
                    if ($idPadrino > 0) {
                        // 3. Obtener el monto de recompensa desde san_consorcios
                        $idCons = isset($id_consorcio) ? (int)$id_consorcio : 1;
                        $queryConf = "SELECT con_referidos FROM san_consorcios WHERE con_id_consorcio = $idCons LIMIT 1";
                        $resConf = mysqli_query($conexion, $queryConf);
                        $monto = 35.00; // default
                        if ($resConf && $rowConf = mysqli_fetch_assoc($resConf)) {
                            if (!empty($rowConf['con_referidos'])) {
                                $monto = (float)$rowConf['con_referidos'];
                            }
                        }
                        
                        if ($monto > 0) {
                            // 4. Aplicar bonificación al Padrino
                            aplicar_bonificacion_monedero($idPadrino, $monto, $fecha_mov, "Referido: $nombreNuevoSocio");
                            
                            // 5. Intentar enviar correo de notificación al Padrino
                            try {
                                $queryPad = "SELECT soc_nombres, soc_correo FROM san_socios WHERE soc_id_socio = $idPadrino LIMIT 1";
                                $resPad = mysqli_query($conexion, $queryPad);
                                if ($resPad && $rowPad = mysqli_fetch_assoc($resPad)) {
                                    $correoPadrino = $rowPad['soc_correo'];
                                    $nombrePadrino = $rowPad['soc_nombres'];
                                    if (!empty($correoPadrino)) {
                                        $rutaAutoload = __DIR__ . '/../../../vendor/autoload.php';
                                        $rutaConfig = __DIR__ . '/../../../sandys_web/api/config.php';
                                        $rutaEmailService = __DIR__ . '/../../../sandys_web/api/lib/EmailService.php';
                                        
                                        if (file_exists($rutaAutoload) && file_exists($rutaConfig) && file_exists($rutaEmailService)) {
                                            require_once $rutaAutoload;
                                            require_once $rutaConfig;
                                            require_once $rutaEmailService;
                                            
                                            if (class_exists('EmailService')) {
                                                $asunto = "¡Ganaste $" . number_format($monto, 0) . " MXN! 💰";
                                                
                                                ob_start();
                                                $rutaPlantilla = __DIR__ . '/../../../sandys_web/api/templates/referral_notification.php';
                                                if (file_exists($rutaPlantilla)) {
                                                    include $rutaPlantilla;
                                                } else {
                                                    echo "<h1>¡Felicidades $nombrePadrino!</h1><p>Tu referido $nombreNuevoSocio pagó su inscripción. Ganaste $$monto.</p>";
                                                }
                                                $mensajeHTML = ob_get_clean();
                                                
                                                @EmailService::send($correoPadrino, $nombrePadrino, $asunto, $mensajeHTML);
                                            }
                                        }
                                    }
                                }
                            } catch (Exception $emailEx) {
                                // Ignorar errores de envío de correo
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $ex) {
        // Silenciar excepciones para evitar romper el flujo principal de Caja
    }
}
?>
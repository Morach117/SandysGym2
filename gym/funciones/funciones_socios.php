<?php
    function checar_impresion_pagos()
    {
        global $conexion, $id_empresa;
        
        $query      = "SELECT foc_tickets FROM san_folios_conf WHERE foc_id_empresa = $id_empresa";
        $resultado  = mysqli_query( $conexion, $query );
        
        if( $resultado )
            if( $fila = mysqli_fetch_assoc( $resultado ) )
                return $fila['foc_tickets'];
        
        return 'N';
    }
    
    function nombre_archivo_imagen( $id_socio )
    {
        global $conexion, $id_empresa;
        
        $query      = "SELECT soc_imagen FROM san_socios WHERE soc_id_socio = $id_socio AND soc_id_empresa = $id_empresa";
        $resultado  = mysqli_query( $conexion, $query );
        
        if( $resultado )
            if( $fila = mysqli_fetch_assoc( $resultado ) )
                if( $fila['soc_imagen'] )
                    return $fila['soc_imagen'];
            
        return 'Sin nombre de imagen...';
    }
    
    function lista_socios_duplicados()
    {
        global $conexion, $id_empresa;
        
        $datos      = "";
        $colspan    = 3; // Corregido el colspan a 3 columnas (antes decia 37)
        $contador   = 1;
        
        $query      = " SELECT  nombre, 
                                COUNT(*) AS cantidad
                        FROM
                        (
                            SELECT  UPPER( CONCAT( soc_apepat, ' ', soc_apemat, ' ', soc_nombres ) ) AS nombre 
                            FROM    san_socios WHERE soc_id_empresa = $id_empresa
                        ) a
                        GROUP BY    nombre
                        HAVING      COUNT(*) > 1";
                        
        $resultado  = mysqli_query( $conexion, $query );
        
        if( $resultado )
        {
            while( $fila = mysqli_fetch_assoc( $resultado ) )
            {
                $datos  .= "<tr>
                                <td>$contador</td>
                                <td>$fila[nombre]</td>
                                <td class='text-right'>$fila[cantidad]</td>
                            </tr>";
                $contador++;
            }
        }
        else
            $datos  = " <tr><td colspan='$colspan'>Ocurrió un problema al obtener los datos. ".mysqli_error( $conexion )."</td></tr>";
        
        if( !$datos )
            $datos  = " <tr><td colspan='$colspan'>No hay datos.</td></tr>";
        
        return $datos;
    }
    
    function opciones_busqueda( $default = 0 )
    {
        // Se agregó la opción 4 al array
        $busqueda       = array( 1 => 'Socios agregados hoy', 2 => 'Socios que pagaron hoy', 3 => 'Los que se vencen hoy', 4 => 'Socios vencidos' );
        $opc_busqueda   = "";
        
        foreach( $busqueda as $ind => $opcion )
        {
            if( $default == $ind )
                $opc_busqueda .= "<option selected value='$ind'>$opcion</option>";
            else
                $opc_busqueda .= "<option value='$ind'>$opcion</option>";
        }
        
        return $opc_busqueda;
    }
    
function lista_socios()
    {
        global $conexion, $id_empresa, $gbl_paginado;
        
        $pag_busqueda = request_var('pag_busqueda', '');
        $pag_opciones = request_var('pag_opciones', 0);
        
        $datos      = "";
        $pagina     = (request_var('pag', 1) - 1) * $gbl_paginado;
        $fecha_mov  = date('Y-m-d');
        
        $colspan    = 7; 
        
        $var_total  = 0;
        $var_exito  = array();
        
        $pag_bloque = request_var('blq', 0);
        $pag_pag    = request_var('pag', 0);
        
        $parametros = "";
        
        if ($pag_opciones) $parametros .= "&pag_opciones=$pag_opciones";
        if ($pag_busqueda) $parametros .= "&pag_busqueda=$pag_busqueda";
        if ($pag_bloque) $parametros .= "&blq=$pag_bloque";
        if ($pag_pag) $parametros .= "&pag=$pag_pag";
        
        if ($pag_busqueda) {
            $limite     = 'LIMIT 0, 50';
            $condicion  = "AND (LOWER(CONCAT(soc_apepat, ' ', soc_apemat, ' ', soc_nombres)) LIKE LOWER('%$pag_busqueda%'))";
        } else {
            $limite     = "LIMIT $pagina, $gbl_paginado";
            $condicion  = "";
        }
        
        if ($pag_opciones == 1) $condicion .= " AND DATE_FORMAT(soc_fecha_captura, '%Y-%m-%d') = '$fecha_mov' ";
        if ($pag_opciones == 2) $condicion .= " AND DATE_FORMAT(pag_fecha_pago, '%Y-%m-%d') = '$fecha_mov' ";
        if ($pag_opciones == 3) $condicion .= " AND DATE_FORMAT(pag_fecha_fin, '%Y-%m-%d') = '$fecha_mov' ";
        if ($pag_opciones == 4) $condicion .= " AND pag_id_pago IS NULL ";
        
        $query      = "SELECT COUNT(*) AS total
                       FROM san_socios
                       LEFT JOIN san_pagos ON pag_id_socio = soc_id_socio
                       AND pag_fecha_fin = (SELECT pag_fecha_fin
                                            FROM san_pagos
                                            WHERE pag_id_socio = soc_id_socio
                                            AND '$fecha_mov' <= pag_fecha_fin 
                                            AND pag_status = 'A'
                                            ORDER BY pag_fecha_fin DESC 
                                            LIMIT 0, 1)
                       AND pag_status = 'A'
                       WHERE soc_id_empresa = $id_empresa
                       $condicion
                       GROUP BY soc_id_socio";
        
        $resultado = mysqli_query($conexion, $query);
        if ($resultado) $var_total = mysqli_num_rows($resultado);
        mysqli_free_result($resultado);
        
        $query      = "SELECT soc_id_socio AS id_socio,
                              pag_id_pago AS id_pago,
                              CONCAT(soc_apepat, ' ', soc_apemat, ' ', soc_nombres) AS nombres,
                              soc_correo,
                              soc_correo_status,
                              DATE_FORMAT(soc_fecha_nacimiento, '%d-%m-%Y') AS fecha_nacimiento,
                              soc_tel_cel,
                              IF(pag_id_pago > 0, CONCAT(DATE_FORMAT(pag_fecha_ini, '%d-%m-%Y'), ' al ', DATE_FORMAT(pag_fecha_fin, '%d-%m-%Y')), 'Pago Vencido') AS status_pago
                       FROM san_socios
                       LEFT JOIN san_pagos ON pag_id_socio = soc_id_socio
                       AND pag_fecha_fin = (SELECT pag_fecha_fin
                                            FROM san_pagos
                                            WHERE pag_id_socio = soc_id_socio
                                            AND '$fecha_mov' <= pag_fecha_fin 
                                            AND pag_status = 'A'
                                            ORDER BY pag_fecha_fin DESC 
                                            LIMIT 0, 1)
                       AND pag_status = 'A'
                       WHERE soc_id_empresa = $id_empresa
                       $condicion
                       GROUP BY soc_id_socio
                       ORDER BY pag_fecha_fin DESC, nombres
                       $limite";
        
        $resultado = mysqli_query($conexion, $query);
        
        if ($resultado) {
            $i = 1;
            while ($fila = mysqli_fetch_assoc($resultado)) {
                
                // --- LÓGICA DE FOTOS OPTIMIZADA ---
                if (file_exists("../imagenes/avatar/$fila[id_socio].jpg")) {
                    $fotografia = "<a href='../imagenes/avatar/$fila[id_socio].jpg' target='_blank' class='btn btn-xs btn-info' style='color:#fff; font-weight:bold; text-decoration:none;'>Ver Foto</a>";
                } else {
                    $fotografia = "<span class='label label-danger' style='font-size: 11px; font-weight: bold;'>SIN FOTO</span>";
                }
                
                // --- LÓGICA DE FONDOS CON !important ---
                $estilo_fila = "";
                
                // Prioridad 1: Sombreado rojo si faltan datos de contacto
                if (empty($fila['soc_correo']) || empty($fila['soc_tel_cel'])) {
                    // El !important obliga a la tabla a pintar la celda
                    $estilo_fila = "style='background-color: #ffe6e6 !important;'"; 
                } 
                // Prioridad 2: Distintivo VERDE si la cuenta web está activada
                else if ($fila['soc_correo_status'] == 1) {
                    // Un verde claro que no lastima la vista
                    $estilo_fila = "style='background-color: #e6ffe6 !important;'"; 
                }

                $datos .= "<tr $estilo_fila>
                               <td $estilo_fila>" . ($pagina + $i) . "</td>
                               <td $estilo_fila>
                                   <div class='btn-group'>
                                       <a class='pointer' dropdown-toggle' data-toggle='dropdown'>
                                           <span class='glyphicon glyphicon-chevron-down'></span>
                                       </a>
                                       <ul class='dropdown-menu'>
                                           <li><a href='.?s=socios&i=datosg&id_socio=$fila[id_socio]'><span class='glyphicon glyphicon-edit'></span> Actualizar información</a></li>
                                           <li><a href='.?s=socios&i=pagos&id_socio=$fila[id_socio]$parametros'><span class='glyphicon glyphicon-usd'></span> Pago de Cuotas</a></li>
                                           <li><a href='?s=prepagos&i=editar&id_socio=$fila[id_socio]&$parametros'><span class='glyphicon glyphicon-usd'></span> Monedero</a></li>
                                           <li><a href='.?s=socios&i=fotografia&id_socio=$fila[id_socio]'><span class='glyphicon glyphicon-picture'></span> Fotografía</a></li>
                                           <li><a href='.?s=socios&i=fechas&id_socio=$fila[id_socio]&id_pago=$fila[id_pago]'><span class='glyphicon glyphicon-calendar'></span> Cambio de Fechas</a></li>
                                           <li><a href='.?s=socios&i=eliminar&id_socio=$fila[id_socio]'><span class='glyphicon glyphicon-remove'></span> Eliminar</a></li>
                                       </ul>
                                   </div>
                               </td>
                               <td $estilo_fila>$fila[nombres]</td>
                               <td $estilo_fila>$fila[soc_correo]</td>
                               <td $estilo_fila>$fila[soc_tel_cel]</td>
                               <td $estilo_fila>$fila[status_pago]</td>
                               <td $estilo_fila style='text-align: center; vertical-align: middle;'>$fotografia</td>
                           </tr>";
                $i++;
            }
        } else {
            $datos = "<tr><td colspan='$colspan'>Ocurrió un problema al obtener los datos. " . mysqli_error($conexion) . "</td></tr>";
        }
        
        if (!$datos) $datos = "<tr><td colspan='$colspan'>No hay datos.</td></tr>";
        
        $var_exito['num'] = $var_total;
        $var_exito['msj'] = $datos;
        
        return $var_exito;
    }
    
    function obtener_socios_vigencia_rango( $rango_ini, $rango_fin, $pag_busqueda )
    {
        Global $conexion, $id_empresa;
        
        $datos      = "";
        $condicion  = "";
        $colspan    = 6;
        
        $rango_ini  = fecha_formato_mysql( $rango_ini );
        $rango_fin  = fecha_formato_mysql( $rango_fin );
        
        if( $pag_busqueda )
        {
            $condicion  = "AND  (
                                    LOWER( soc_apepat ) LIKE LOWER( '%$pag_busqueda%' )
                                    OR
                                    LOWER( soc_apemat ) LIKE LOWER( '%$pag_busqueda%' )
                                    OR
                                    LOWER( soc_nombres ) LIKE LOWER( '%$pag_busqueda%' )
                                )";
        }
        
        if( $rango_ini && $rango_fin )
        {
            $query      = " SELECT      soc_id_socio AS id_socio,
                                        pag_id_pago AS id_pago,
                                        CONCAT( soc_apepat, ' ', soc_apemat, ' ', soc_nombres ) AS nombres,
                                        IF( pag_id_pago > 0, CONCAT( DATE_FORMAT( pag_fecha_ini, '%d-%m-%Y' ), ' al ', DATE_FORMAT( pag_fecha_fin, '%d-%m-%Y' ) ), 'Pago Vencido' ) AS status_pago
                            FROM        san_socios
                            INNER JOIN  san_pagos ON pag_id_socio = soc_id_socio
                            AND         DATE_FORMAT( pag_fecha_fin, '%Y-%m-%d' )
                            BETWEEN     DATE_FORMAT( '$rango_ini', '%Y-%m-%d' )
                            AND         DATE_FORMAT( '$rango_fin', '%Y-%m-%d' )
                            AND         pag_fecha_fin = (   SELECT      pag_fecha_fin
                                                            FROM        san_pagos
                                                            WHERE       pag_id_socio = soc_id_socio
                                                            AND         pag_status = 'A'
                                                            ORDER BY    pag_fecha_fin DESC 
                                                            LIMIT       0, 1 )
                            WHERE       soc_id_empresa = $id_empresa
                            AND         pag_status = 'A'
                                        $condicion
                            GROUP BY    soc_id_socio
                            ORDER BY    pag_fecha_fin DESC";
            
            $resultado  = mysqli_query( $conexion, $query );
            
            if( $resultado )
            {
                $i =1;
                while( $fila = mysqli_fetch_assoc( $resultado ) )
                {
                    if( file_exists( "../imagenes/avatar/$fila[id_socio].jpg" ) )
                        $fotografia = "<img src='../imagenes/avatar/$fila[id_socio].jpg' class='img-responsive' width='40px' />";
                    else
                        $fotografia = "<img src='../imagenes/avatar/noavatar.jpg' class='img-responsive' width='40px' />";
                    
                    $datos  .= "<tr>
                                    <td>$i</td>
                                    <td>
                                        <div class='btn-group'>
                                            <a class='pointer' dropdown-toggle' data-toggle='dropdown'>
                                                <span class='glyphicon glyphicon-chevron-down'></span>
                                            </a>
                                            <ul class='dropdown-menu'>
                                                <li>
                                                    <a href='.?s=socios&i=datosg&id_socio=$fila[id_socio]'><span class='glyphicon glyphicon-edit'></span> Actualizar información</a>
                                                </li>
                                                
                                                <li>
                                                    <a href='.?s=socios&i=pagos&id_socio=$fila[id_socio]'><span class='glyphicon glyphicon-usd'></span> Pago de Cuotas</a>
                                                </li>
                                                
                                                <li>
                                                    <a href='.?s=socios&i=fotografia&id_socio=$fila[id_socio]'><span class='glyphicon glyphicon-picture'></span> Fotografía</a>
                                                </li>
                                                
                                                <li>
                                                    <a href='.?s=socios&i=fechas&id_socio=$fila[id_socio]&id_pago=$fila[id_pago]'><span class='glyphicon glyphicon-calendar'></span> Cambio de Fechas</a>
                                                </li>

                                                <li>
                                                    <a href='.?s=socios&i=eliminar&id_socio=$fila[id_socio]'><span class='glyphicon glyphicon-remove'></span> Eliminar</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>$fila[id_socio]</td>
                                    <td>$fila[nombres]</td>
                                    <td>$fila[status_pago]</td>
                                    <td><a href='.?s=socios&i=fotografia&id_socio=$fila[id_socio]'>$fotografia</span></a></td>
                                </tr>";
                    $i++;
                }
            }
            else
                $datos  = " <tr><td colspan='$colspan'>Ocurrió un problema al obtener los datos. ".mysqli_error( $conexion )."</td></tr>";
            
            if( !$datos )
                $datos  = " <tr><td colspan='$colspan'>No hay datos.</td></tr>";
        }
        
        $exito['num'] = 1;
        $exito['msj'] = $datos;
        
        return $exito;
    }
    
    function obtener_datos_socio()
    {
        Global $conexion, $id_empresa;
        
        $id_socio   = request_var( 'id_socio', 0 );
        
        $query      = "SELECT * FROM san_socios WHERE soc_id_socio = $id_socio AND soc_id_empresa = $id_empresa";
        $resultado  = mysqli_query( $conexion, $query );
        
        if( $resultado )
            if( $fila = mysqli_fetch_assoc( $resultado ) )
                return $fila;
        
        return false;
    }
    
    /*
    tipo    -> T=texto, N=numerico, C=correo, F=fecha
    max     -> longitud maxima del campo
    txt     -> texto o descripcion para mostrar un mensaje acerca de este campo
    req     -> obligatoriedad(S,N)
    */
    
    function validar_registro_socios()
    {
        $validar    = array
        (
            'soc_nombres'           => array( 'tipo' => 'T',    'max' => 50,    'req' => 'S', 'for' => '',  'txt' => 'Nombre'),
            'soc_apepat'            => array( 'tipo' => 'T',    'max' => 50,    'req' => 'S', 'for' => '',  'txt' => 'Apellido paterno'),
            'soc_apemat'            => array( 'tipo' => 'T',    'max' => 50,    'req' => 'N', 'for' => '',  'txt' => 'Apellido materno'),
            'soc_genero'            => array( 'tipo' => 'T',    'max' => 1,     'req' => 'N', 'for' => '',  'txt' => 'Genero'),
            'soc_turno'             => array( 'tipo' => 'T',    'max' => 1,     'req' => 'N', 'for' => '',  'txt' => 'Turno'),
            'soc_direccion'         => array( 'tipo' => 'T',    'max' => 100,   'req' => 'N', 'for' => '',  'txt' => 'Dirección'),
            'soc_colonia'           => array( 'tipo' => 'T',    'max' => 100,   'req' => 'N', 'for' => '',  'txt' => 'Colonia'),
            'soc_tel_fijo'          => array( 'tipo' => 'T',    'max' => 15,    'req' => 'N', 'for' => '',  'txt' => 'Teléfono fijo'),
            'soc_tel_cel'           => array( 'tipo' => 'T',    'max' => 15,    'req' => 'N', 'for' => '',  'txt' => 'Teléfono celular'),
            'soc_correo'            => array( 'tipo' => 'C',    'max' => 50,    'req' => 'N', 'for' => '',  'txt' => 'Correo electronico'),
            'soc_emer_tel'          => array( 'tipo' => 'T',    'max' => 15,    'req' => 'N', 'for' => '',  'txt' => 'Teléfono del contacto de emergencia'),
            'soc_observaciones'     => array( 'tipo' => 'T',    'max' => 200,   'req' => 'N', 'for' => '',  'txt' => 'Observaciones')
        );
        
        $exito      = validar_php( $validar );
        
        return $exito;
    }
    
    function validar_pago_socio()
    {
        $validar    = array
        (
            'servicio'          => array( 'tipo' => 'T',    'max' => 5,     'req' => 'S',   'for' => '',            'txt' => 'Servicio'),
            'pag_fecha_ini'     => array( 'tipo' => 'F',    'max' => 10,    'req' => 'S',   'for' => 'DD-MM-YYYY',  'txt' => 'Fecha inicial')
        );
        
        $exito      = validar_php( $validar );
        
        return $exito;
    }
    
    function subir_fotografia()
{
    global $conexion, $id_empresa;
    
    $id_socio          = request_var('id_socio', 0);
    $id_socio_seguro   = (int)$id_socio;
    $id_empresa_seguro = (int)$id_empresa;
    
    $dir_ponencias     = "../imagenes/avatar/";
    $extenciones       = "/^\.(jpg){1}$/i";
    $tamaño_maximo     = 2 * 1024 * 1024;
    $exito             = array();
    
    if (isset($_FILES['avatar']) && $_FILES['avatar']['name'] && $id_socio_seguro > 0) {
        $extencion_archivo = tipo_archivo($_FILES['avatar']['type']);
        
        // El archivo físico y el de la base de datos deben usar EXACTAMENTE esta variable
        $nombre_archivo    = $id_socio_seguro . $extencion_archivo;
        
        $valido            = is_uploaded_file($_FILES['avatar']['tmp_name']); 
        
        if ($valido) {
            $safe_filename = preg_replace(array("/\s+/", "/[^-\.\w]+/"), array("_", ""), trim($_FILES['avatar']['name']));
            
            if ($extencion_archivo && $_FILES['avatar']['size'] <= $tamaño_maximo && preg_match($extenciones, strrchr($safe_filename, '.'))) {
                
                // Mueve el archivo físico con el nombre estandarizado (Ej: 15.jpg)
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir_ponencias . $nombre_archivo)) {
                    
                    // Verificar que el socio realmente existe en esta empresa
                    $query = "SELECT soc_id_socio FROM san_socios WHERE soc_id_socio = $id_socio_seguro AND soc_id_empresa = $id_empresa_seguro LIMIT 1";
                    $resultado = mysqli_query($conexion, $query);
                    
                    if ($resultado && mysqli_num_rows($resultado) > 0) {
                        // ACTUALIZACIÓN CORREGIDA: Se guarda $nombre_archivo en BD, NO el nombre original de subida.
                        $query_update = "UPDATE san_socios SET soc_imagen = '$nombre_archivo' WHERE soc_id_socio = $id_socio_seguro AND soc_id_empresa = $id_empresa_seguro";
                        $resultado_update = mysqli_query($conexion, $query_update);
                        
                        if ($resultado_update) {
                            $exito['num'] = 1;
                            $exito['msj'] = 'Fotografía guardada y enlazada correctamente.';
                        } else {
                            $exito['num'] = 6;
                            $exito['msj'] = 'Error al actualizar el registro en la base de datos.';
                        }
                    } else {
                        $exito['num'] = 7;
                        $exito['msj'] = 'El socio no existe o no pertenece a esta sucursal.';
                    }
                } else {
                    $exito['num'] = 5;
                    $exito['msj'] = 'La fotografía no se ha guardado físicamente en el servidor.<br/>';
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
        $exito['msj'] = 'No se seleccionó un archivo para la Fotografía o ID de socio inválido.';
    }
    
    return $exito;
}
    
    function eliminar_fotografia()
    {
        global $id_socio;
        
        if( file_exists( "../imagenes/avatar/$id_socio.jpg" ) )
            if( unlink( "../imagenes/avatar/$id_socio.jpg" ) )
                return true;
        
        return false;
    }
    
?>
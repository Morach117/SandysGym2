<?php
    function guardar_nuevo_socio()
    {
        global $conexion, $id_usuario, $id_empresa, $id_consorcio;

        $mensaje = array();
        
        // 1. Capturamos variables generales
        $correo = request_var('soc_correo', '');
        
        // Validación del descuento
        $descuento = request_var('soc_descuento', '');
        if($descuento === '') {
            $descuento = 0;
        }

        // --- CORRECCIÓN DE FECHA (AQUÍ ESTABA EL PROBLEMA) ---
        // 1. Intentamos capturar el MES seleccionado (del select nuevo)
        $mes_nacimiento = request_var('soc_mes_nacimiento', '');
        
        // 2. Intentamos capturar la FECHA completa (por si acaso)
        $fecha_nac = request_var('soc_fecha_nacimiento', '');

        // Lógica de conversión:
        if (!empty($mes_nacimiento)) {
            // Si tenemos mes, construimos la fecha: Año 2000, Mes X, Día 01
            $fecha_nac = "2000-" . str_pad($mes_nacimiento, 2, "0", STR_PAD_LEFT) . "-01";
        } elseif (empty($fecha_nac)) {
            // Si no hay mes Y tampoco hay fecha completa, ponemos la default para evitar error SQL
            $fecha_nac = '1900-01-01';
        }
        // -----------------------------------------------------

        $datos_sql = array(
            'soc_nombres'           => strtoupper(request_var('soc_nombres', '')),
            'soc_apepat'            => strtoupper(request_var('soc_apepat', '')),
            'soc_apemat'            => strtoupper(request_var('soc_apemat', '')),
            'soc_genero'            => request_var('soc_genero', ''),
            'soc_turno'             => request_var('soc_turno', ''),
            'soc_direccion'         => strtoupper(request_var('soc_direccion', '')),
            'soc_tel_fijo'          => request_var('soc_tel_fijo', ''),
            'soc_tel_cel'           => request_var('soc_tel_cel', ''),
            'soc_correo'            => $correo,
            'soc_emer_tel'          => request_var('soc_emer_tel', ''),
            'soc_observaciones'     => strtoupper(request_var('soc_observaciones', '')),
            'soc_descuento'         => $descuento,
            'soc_fecha_nacimiento'  => $fecha_nac,
            
            // Campos obligatorios por defecto
            'san_password'          => '12345', 
            'is_active'             => 0,       
            
            'soc_id_usuario'        => $id_usuario,
            'soc_id_empresa'        => $id_empresa,
            'soc_id_consorcio'      => $id_consorcio
        );

        // --- VALIDACIÓN DE DUPLICADOS (CORREO Y TELÉFONO) ---
        $tel_cel = request_var('soc_tel_cel', '');
        $where_clauses = array();
        
        if (!empty($correo)) {
            $where_clauses[] = "soc_correo = '" . mysqli_real_escape_string($conexion, $correo) . "'";
        }
        if (!empty($tel_cel)) {
            $where_clauses[] = "soc_tel_cel = '" . mysqli_real_escape_string($conexion, $tel_cel) . "'";
        }

        if (count($where_clauses) > 0) {
            $where_sql = implode(" OR ", $where_clauses);
            $dup_query = "SELECT soc_nombres, soc_apepat, soc_apemat, soc_correo, soc_tel_cel FROM san_socios WHERE ($where_sql) LIMIT 1";
            $dup_resultado = mysqli_query($conexion, $dup_query);
            
            if ($dup_resultado) {
                if (mysqli_num_rows($dup_resultado) > 0) {
                    $dup_fila = mysqli_fetch_assoc($dup_resultado);
                    $nombre_completo = trim($dup_fila['soc_nombres'] . ' ' . $dup_fila['soc_apepat'] . ' ' . $dup_fila['soc_apemat']);
                    
                    $motivo = array();
                    if (!empty($correo) && strtolower($dup_fila['soc_correo']) == strtolower($correo)) {
                        $motivo[] = "el correo <strong>$correo</strong>";
                    }
                    if (!empty($tel_cel) && $dup_fila['soc_tel_cel'] == $tel_cel) {
                        $motivo[] = "el teléfono <strong>$tel_cel</strong>";
                    }
                    
                    $motivo_str = implode(" y/o ", $motivo);
                    if (empty($motivo_str)) $motivo_str = "este correo o teléfono";
                    
                    $mensaje['num'] = 2;
                    $mensaje['msj'] = "Ya existe un socio registrado con $motivo_str.<br>El nombre del socio es: <strong>$nombre_completo</strong>.";
                    return $mensaje;
                }
            } else {
                $mensaje['num'] = 3;
                $mensaje['msj'] = "Error en la consulta de validación de duplicados. " . mysqli_error($conexion);
                return $mensaje;
            }
        }

        // --- INSERCIÓN EN BASE DE DATOS ---
        $query = construir_insert('san_socios', $datos_sql);
        
        $resultado = mysqli_query($conexion, $query);

        if ($resultado) {
            $mensaje['num'] = 1;
            $mensaje['msj'] = "Registro guardado correctamente.";
        } else {
            $mensaje['num'] = 3;
            $mensaje['msj'] = "Error SQL: " . mysqli_error($conexion);
        }

        return $mensaje;
    }
?>
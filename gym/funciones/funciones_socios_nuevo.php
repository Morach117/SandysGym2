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

        // --- VALIDACIÓN DE CORREO EXISTENTE ---
        if (!empty($correo)) {
            $correo_escaped = mysqli_real_escape_string($conexion, $correo);
            $correo_query = "SELECT count(*) as total FROM san_socios WHERE soc_correo = '$correo_escaped' LIMIT 1";
            $correo_resultado = mysqli_query($conexion, $correo_query);

            if ($correo_resultado) {
                $correo_fila = mysqli_fetch_assoc($correo_resultado);
                if ($correo_fila['total'] > 0) {
                    $mensaje['num'] = 2;
                    $mensaje['msj'] = "El correo ingresado ya ha sido capturado para otro socio, es necesario cambiarlo.";
                    return $mensaje;
                }
            } else {
                $mensaje['num'] = 3;
                $mensaje['msj'] = "Error en la consulta del correo. " . mysqli_error($conexion);
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
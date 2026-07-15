<?php
// Función para actualizar los descuentos y los servicios permitidos
function actualizar_descuentos() {
    global $conexion; // Usamos la conexión global del sistema
    $errores = array();

    if (isset($_POST['enviar'])) {
        // 1. Actualizar descuento de cumpleaños (ID 104)
        $id_cumple = intval($_POST['id_cumple']);
        // CORRECCIÓN: Tu BD usa INT para el porcentaje
        $porcentaje_cumple = intval($_POST['porcentaje_cumple']); 
        
        $query_cumple = "UPDATE san_promociones SET porcentaje_descuento = '$porcentaje_cumple' WHERE id_promocion = $id_cumple";
        if (!mysqli_query($conexion, $query_cumple)) {
            $errores[] = 'Error al actualizar descuento de cumpleaños: ' . mysqli_error($conexion);
        }

        // 2. LÓGICA DE SERVICIOS
        // CORRECCIÓN DE ERROR: Nombre exacto de la tabla (san_descuentos_promociones)
        $query_borrar_servicios = "DELETE FROM san_descuentos_promociones WHERE id_promocion = $id_cumple";
        mysqli_query($conexion, $query_borrar_servicios);

        // Si marcaron checkboxes, los insertamos con permitir_descuento = 1
        if (!empty($_POST['servicios_permitidos']) && is_array($_POST['servicios_permitidos'])) {
            foreach ($_POST['servicios_permitidos'] as $id_servicio) {
                $id_servicio_seguro = intval($id_servicio);
                
                // CORRECCIÓN DE ERROR: Nombre exacto de la tabla
                $query_insertar_servicio = "INSERT INTO san_descuentos_promociones (id_promocion, id_servicio, permitir_descuento) 
                                            VALUES ($id_cumple, $id_servicio_seguro, 1)";
                if (!mysqli_query($conexion, $query_insertar_servicio)) {
                    $errores[] = 'Error al guardar un servicio permitido: ' . mysqli_error($conexion);
                }
            }
        }

        // 3. Actualizar descuento referido por Título
        if (isset($_POST['porcentaje_referido'])) {
            $porcentaje_referido = intval($_POST['porcentaje_referido']);
            $query_referido = "UPDATE san_promociones SET porcentaje_descuento = '$porcentaje_referido' WHERE titulo = 'PROMOCION FIJA DE REFERIDOS'";
            if (!mysqli_query($conexion, $query_referido)) {
                $errores[] = 'Error al actualizar descuento de referido: ' . mysqli_error($conexion);
            }
        }

        // 4. Actualizar descuento reactivación por Título
        if (isset($_POST['porcentaje_reactivacion'])) {
            $porcentaje_reactivacion = intval($_POST['porcentaje_reactivacion']);
            $query_reactivacion = "UPDATE san_promociones SET porcentaje_descuento = '$porcentaje_reactivacion' WHERE titulo = 'PROMOCION FIJA DE REACTIVACION'";
            if (!mysqli_query($conexion, $query_reactivacion)) {
                $errores[] = 'Error al actualizar descuento de reactivación: ' . mysqli_error($conexion);
            }
        }

        if (empty($errores)) {
            return array('num' => 1, 'msj' => 'Descuentos y servicios actualizados correctamente.');
        } else {
            return array('num' => 2, 'msj' => implode('<br>', $errores));
        }
    } else {
        return array('num' => 3, 'msj' => 'No se envió el formulario.');
    }
}

// Verificar si se envió el formulario
if (isset($_POST['enviar'])) {
    $exito = actualizar_descuentos();
    
    if ($exito['num'] == 1) {
        header("Location: ?s=cumple");
        exit;
    } else {
        mostrar_mensaje_div($exito['num'] . ". " . $exito['msj'], 'danger');
    }
}

// Función para obtener el porcentaje de descuento por ID
function obtener_porcentaje_descuento($id_promocion) {
    global $conexion;
    
    $id_promocion = intval($id_promocion);
    $query = "SELECT porcentaje_descuento FROM san_promociones WHERE id_promocion = $id_promocion";
    $resultado = mysqli_query($conexion, $query);
    
    if ($resultado && $fila = mysqli_fetch_assoc($resultado)) {
        return intval($fila['porcentaje_descuento']);
    }
    
    return 0; 
}

// Función para obtener el porcentaje de descuento por Título
function obtener_porcentaje_descuento_por_titulo($titulo) {
    global $conexion;
    
    $titulo_seguro = mysqli_real_escape_string($conexion, $titulo);
    $query = "SELECT porcentaje_descuento FROM san_promociones WHERE titulo = '$titulo_seguro' LIMIT 1";
    $resultado = mysqli_query($conexion, $query);
    
    if ($resultado && $fila = mysqli_fetch_assoc($resultado)) {
        return intval($fila['porcentaje_descuento']);
    }
    
    return 35; // Valor por defecto si no existe en BD aún
}

// NUEVA FUNCIÓN: Obtener servicios permitidos de tu tabla
function obtener_servicios_promocion($id_promocion) {
    global $conexion;
    $servicios_marcados = array();
    
    $id_promocion = intval($id_promocion);
    // CORRECCIÓN DE ERROR: Nombre exacto de la tabla (san_descuentos_promociones)
    $query = "SELECT id_servicio FROM san_descuentos_promociones WHERE id_promocion = $id_promocion AND permitir_descuento = 1";
    $resultado = mysqli_query($conexion, $query);
    
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $servicios_marcados[] = $fila['id_servicio'];
        }
    }
    
    return $servicios_marcados;
}

// Obtenemos los servicios guardados para el ID 104
$servicios_guardados_cumple = obtener_servicios_promocion(104);

?>

<div class="row">
    <div class="col-md-12">
        <h4 class="text-info">
            <span class="glyphicon glyphicon-pencil"></span> Modificar Descuentos
        </h4>
    </div>
</div>
<hr/>

<form method="post" action="?s=cumple&i=modificar_descuento">
    
    <div class="row">
        <label class="col-md-2">Descuento Cumpleaños (%)</label>
        <div class="col-md-4">
            <input type="hidden" name="id_cumple" value="104" />
            <input type="number" name="porcentaje_cumple" class="form-control" value="<?= obtener_porcentaje_descuento(104) ?>" min="0" max="100" required />
        </div>
    </div>

    <div class="row" style="margin-top: 15px;">
        <label class="col-md-2">Servicios Permitidos (Cumpleaños)</label>
        <div class="col-md-4">
            <div class="well well-sm" style="max-height: 200px; overflow-y: auto; background-color: #fff;">
                <?php
                    // Obtener todos los servicios activos
                    $query_servicios = "SELECT ser_id_servicio, ser_descripcion FROM san_servicios WHERE ser_id_giro = 1 AND ser_status = 'A'";
                    $result_servicios = mysqli_query($conexion, $query_servicios);

                    if ($result_servicios && mysqli_num_rows($result_servicios) > 0) {
                        while ($row = mysqli_fetch_assoc($result_servicios)) {
                            $id_servicio = $row['ser_id_servicio'];
                            $nombre_servicio = htmlspecialchars($row['ser_descripcion']);
                            
                            // Verificamos si este servicio está permitido
                            $checked = in_array($id_servicio, $servicios_guardados_cumple) ? 'checked' : '';
                            
                            echo "<div class='checkbox' style='margin-top: 5px; margin-bottom: 5px;'>
                                    <label>
                                        <input type='checkbox' name='servicios_permitidos[]' value='$id_servicio' $checked> $nombre_servicio
                                    </label>
                                  </div>";
                        }
                    } else {
                        echo "<p class='text-muted'>No hay servicios disponibles.</p>";
                    }
                ?>
            </div>
        </div>
    </div>

    <div class="row" style="margin-top: 15px;">
        <label class="col-md-2">Descuento Referido (%)</label>
        <div class="col-md-4">
            <input type="number" name="porcentaje_referido" class="form-control" value="<?= obtener_porcentaje_descuento_por_titulo('PROMOCION FIJA DE REFERIDOS') ?>" min="0" max="100" required />
        </div>
    </div>

    <div class="row" style="margin-top: 15px;">
        <label class="col-md-2">Descuento Reactivación (%)</label>
        <div class="col-md-4">
            <input type="number" name="porcentaje_reactivacion" class="form-control" value="<?= obtener_porcentaje_descuento_por_titulo('PROMOCION FIJA DE REACTIVACION') ?>" min="0" max="100" required />
        </div>
    </div>

    <div class="row" style="margin-top: 30px; margin-bottom: 30px;">
        <div class="col-md-offset-2 col-md-4">
            <button type="submit" name="enviar" class="btn btn-primary">
                <span class="glyphicon glyphicon-floppy-disk"></span> Guardar Cambios
            </button>
        </div>
    </div>
</form>
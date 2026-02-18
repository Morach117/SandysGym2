<?php
// Función para actualizar el descuento de cumpleaños
function actualizar_descuentos() {
    $conexion = obtener_conexion();
    $errores = array();

    if (isset($_POST['enviar'])) {
        // Actualizar descuento de cumpleaños
        $id_cumple = mysqli_real_escape_string($conexion, $_POST['id_cumple']);
        $porcentaje_cumple = mysqli_real_escape_string($conexion, $_POST['porcentaje_cumple']);
        $query_cumple = "UPDATE san_promociones SET porcentaje_descuento = '$porcentaje_cumple' WHERE id_promocion = '$id_cumple'";
        $resultado_cumple = mysqli_query($conexion, $query_cumple);
        if (!$resultado_cumple) {
            $errores[] = 'Error al actualizar cumpleaños: ' . mysqli_error($conexion);
        }

        // Actualizar descuento referido
        $id_referido = mysqli_real_escape_string($conexion, $_POST['id_referido']);
        $porcentaje_referido = mysqli_real_escape_string($conexion, $_POST['porcentaje_referido']);
        $query_referido = "UPDATE san_promociones SET porcentaje_descuento = '$porcentaje_referido' WHERE id_promocion = '$id_referido'";
        $resultado_referido = mysqli_query($conexion, $query_referido);
        if (!$resultado_referido) {
            $errores[] = 'Error al actualizar referido: ' . mysqli_error($conexion);
        }

        if (empty($errores)) {
            return array('num' => 1, 'msj' => 'Descuentos actualizados correctamente.');
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

// Función para obtener el porcentaje de descuento
function obtener_porcentaje_descuento($id_promocion)
{
    $conexion = obtener_conexion();
    
    if ($conexion) {
        // Escapar el ID de promoción para prevenir inyecciones SQL
        $id_promocion = mysqli_real_escape_string($conexion, $id_promocion);

        // Consulta SQL para obtener el porcentaje de descuento
        $query = "SELECT porcentaje_descuento FROM san_promociones WHERE id_promocion = '$id_promocion'";
        
        // Ejecutar la consulta
        $resultado = mysqli_query($conexion, $query);
        
        if ($resultado) {
            // Extraer el resultado
            $fila = mysqli_fetch_assoc($resultado);
            
            // Liberar el resultado
            mysqli_free_result($resultado);
            
            // Cerrar la conexión
            mysqli_close($conexion);
            
            // Retornar el porcentaje de descuento
            return $fila['porcentaje_descuento'];
        } else {
            // Si la consulta falla, mostrar error
            echo "Error al ejecutar la consulta: " . mysqli_error($conexion);
        }
    } else {
        // Si no se puede conectar a la base de datos, mostrar error
        echo "Error al conectar a la base de datos.";
    }
    
    // Si ocurre un error, retorna falso
    return false;
}

// Ejemplo de uso
$porcentaje_descuento = obtener_porcentaje_descuento($id_promocion);

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
    <!-- Descuento Cumpleaños -->
    <div class="row">
        <label class="col-md-2">Descuento Cumpleaños</label>
        <div class="col-md-4">
            <input type="hidden" name="id_cumple" value="104" />
            <input type="number" name="porcentaje_cumple" class="form-control" value="<?= obtener_porcentaje_descuento(104) ?>" min="0" max="100" required />
        </div>
    </div>

    <!-- Descuento Referido -->
    <div class="row" style="margin-top: 10px;">
        <label class="col-md-2">Descuento Referido</label>
        <div class="col-md-4">
            <input type="hidden" name="id_referido" value="167" />
            <input type="number" name="porcentaje_referido" class="form-control" value="<?= obtener_porcentaje_descuento(167) ?>" min="0" max="100" required />
        </div>
    </div>

    <div class="row" style="margin-top: 20px;">
        <div class="col-md-offset-2 col-md-4">
            <input type="submit" name="enviar" class="btn btn-primary" value="Guardar" />
        </div>
    </div>
</form>

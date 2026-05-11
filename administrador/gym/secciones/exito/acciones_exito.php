<?php
// Configuración estricta para reporte de errores en AJAX
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

require_once("../../../../funciones_globales/funciones_conexion.php");
$conexion = obtener_conexion();

if (!$conexion) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['exito' => false, 'mensaje' => 'Error crítico: No se pudo conectar a la BD.']);
    exit;
}

mysqli_set_charset($conexion, "utf8mb4");
$accion = $_POST['accion'] ?? '';
$subcarpeta_relativa = 'exito/';
$base_dir = $_SERVER['DOCUMENT_ROOT'] . '/imagenes/' . $subcarpeta_relativa; 

if (!is_dir($base_dir)) {
    mkdir($base_dir, 0755, true);
}

// Helper para generar el HTML de la fila (DOM dinámico)
function generarFilaExito($id, $cliente, $foto_a, $foto_d, $video, $testimonio = '') {
    $fecha = date('d/m/Y');
    $video_html = !empty($video) ? "<span class='label label-info'><span class='glyphicon glyphicon-play-circle'></span> Video</span>" : "<span class='text-muted'>N/A</span>";
    
    $cliente_js = htmlspecialchars($cliente, ENT_QUOTES);
    $testimonio_js = htmlspecialchars($testimonio, ENT_QUOTES);
    
    return "<tr id='fila_exito_{$id}'>
                <td><span class='label label-success'>Nuevo/Editado</span></td>
                <td><strong>" . htmlspecialchars($cliente) . "</strong></td>
                <td>
                    <img src='../imagenes/exito/" . htmlspecialchars($foto_a) . "' style='width:50px; height:50px; object-fit:cover;' class='img-thumbnail'>
                    <span class='glyphicon glyphicon-arrow-right text-muted'></span>
                    <img src='../imagenes/exito/" . htmlspecialchars($foto_d) . "' style='width:50px; height:50px; object-fit:cover;' class='img-thumbnail'>
                </td>
                <td>{$video_html}</td>
                <td>{$fecha}</td>
                <td id='td_estado_{$id}'><span class='label label-success'>Activo</span></td>
                <td>
                    <div class='btn-group'>
                        <button class='btn btn-xs btn-info' onclick='abrirPreview(\"../imagenes/exito/{$foto_a}\", \"../imagenes/exito/{$foto_d}\", \"../imagenes/exito/{$video}\")' title='Vista Previa'><span class='glyphicon glyphicon-eye-open'></span></button>
                        <button class='btn btn-xs btn-primary' onclick='abrirModalEditar({$id}, \"{$cliente_js}\", \"{$testimonio_js}\")' title='Editar'><span class='glyphicon glyphicon-pencil'></span></button>
                        <button class='btn btn-xs btn-warning' id='btn_estado_{$id}' onclick='cambiarEstado({$id}, 1)' title='Cambiar Estado'><span class='glyphicon glyphicon-refresh'></span></button>
                        <button class='btn btn-xs btn-danger' onclick='eliminarCaso({$id})' title='Eliminar'><span class='glyphicon glyphicon-trash'></span></button>
                    </div>
                </td>
            </tr>";
}

if ($accion === 'nuevo_caso') {
    $cliente = mysqli_real_escape_string($conexion, trim($_POST['cliente_nombre']));
    $testimonio = mysqli_real_escape_string($conexion, trim($_POST['testimonio']));
    $time_stamp = time();
    
    $foto_antes = ""; $foto_despues = ""; $video_nom = "";
    $allowed_image_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_video_mimes = ['video/mp4', 'video/webm'];

    if (isset($_FILES['foto_antes']) && $_FILES['foto_antes']['error'] === UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['foto_antes']['tmp_name']);
        finfo_close($finfo);
        if (in_array($mime, $allowed_image_mimes)) {
            $ext = strtolower(pathinfo($_FILES['foto_antes']['name'], PATHINFO_EXTENSION));
            $foto_antes = uniqid($time_stamp . "_a_") . "." . $ext;
            move_uploaded_file($_FILES['foto_antes']['tmp_name'], $base_dir . $foto_antes);
        }
    }

    if (isset($_FILES['foto_despues']) && $_FILES['foto_despues']['error'] === UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['foto_despues']['tmp_name']);
        finfo_close($finfo);
        if (in_array($mime, $allowed_image_mimes)) {
            $ext = strtolower(pathinfo($_FILES['foto_despues']['name'], PATHINFO_EXTENSION));
            $foto_despues = uniqid($time_stamp . "_d_") . "." . $ext;
            move_uploaded_file($_FILES['foto_despues']['tmp_name'], $base_dir . $foto_despues);
        }
    }

    if (isset($_FILES['video_archivo']) && $_FILES['video_archivo']['error'] === UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['video_archivo']['tmp_name']);
        finfo_close($finfo);
        if (in_array($mime, $allowed_video_mimes)) {
            $ext = strtolower(pathinfo($_FILES['video_archivo']['name'], PATHINFO_EXTENSION));
            $video_nom = uniqid($time_stamp . "_v_") . "." . $ext;
            move_uploaded_file($_FILES['video_archivo']['tmp_name'], $base_dir . $video_nom);
        }
    }

    if (!empty($foto_antes) && !empty($foto_despues)) {
        $sql = "INSERT INTO san_historias (cliente_nombre, foto_antes, foto_despues, video_url, testimonio, estado) 
                VALUES ('$cliente', '$foto_antes', '$foto_despues', '$video_nom', '$testimonio', 1)";
                
        if(mysqli_query($conexion, $sql)){
            $id_insertado = mysqli_insert_id($conexion);
            $html_nueva_fila = generarFilaExito($id_insertado, $cliente, $foto_antes, $foto_despues, $video_nom, $testimonio);
            
            if (ob_get_length()) ob_clean();
            echo json_encode(['exito' => true, 'mensaje' => 'Caso registrado exitosamente.', 'html' => $html_nueva_fila]);
        } else {
            if (ob_get_length()) ob_clean();
            echo json_encode(['exito' => false, 'mensaje' => 'Error DB: ' . mysqli_error($conexion)]);
        }
    } else {
        if (ob_get_length()) ob_clean();
        echo json_encode(['exito' => false, 'mensaje' => 'Las fotos (Antes y Después) son obligatorias y formato válido.']);
    }
    exit;
}

if ($accion === 'editar_caso') {
    $id = (int)$_POST['id_historia_edit'];
    $cliente = mysqli_real_escape_string($conexion, trim($_POST['cliente_nombre_edit']));
    $testimonio = mysqli_real_escape_string($conexion, trim($_POST['testimonio_edit']));
    $time_stamp = time();
    
    $allowed_image_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_video_mimes = ['video/mp4', 'video/webm'];

    $res = mysqli_query($conexion, "SELECT foto_antes, foto_despues, video_url FROM san_historias WHERE id_historia = $id");
    $row = mysqli_fetch_assoc($res);
    
    $upd_antes = ""; $upd_despues = ""; $upd_video = "";
    $foto_a_final = $row['foto_antes'];
    $foto_d_final = $row['foto_despues'];
    $video_final = $row['video_url'];

    if (isset($_FILES['foto_antes_edit']) && $_FILES['foto_antes_edit']['error'] === UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['foto_antes_edit']['tmp_name']);
        finfo_close($finfo);
        if (in_array($mime, $allowed_image_mimes)) {
            $ext = strtolower(pathinfo($_FILES['foto_antes_edit']['name'], PATHINFO_EXTENSION));
            $foto_antes_nueva = uniqid($time_stamp . "_a_") . "." . $ext;
            if (move_uploaded_file($_FILES['foto_antes_edit']['tmp_name'], $base_dir . $foto_antes_nueva)) {
                if(!empty($row['foto_antes']) && file_exists($base_dir . $row['foto_antes'])) unlink($base_dir . $row['foto_antes']);
                $upd_antes = ", foto_antes = '$foto_antes_nueva'";
                $foto_a_final = $foto_antes_nueva;
            }
        }
    }

    if (isset($_FILES['foto_despues_edit']) && $_FILES['foto_despues_edit']['error'] === UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['foto_despues_edit']['tmp_name']);
        finfo_close($finfo);
        if (in_array($mime, $allowed_image_mimes)) {
            $ext = strtolower(pathinfo($_FILES['foto_despues_edit']['name'], PATHINFO_EXTENSION));
            $foto_despues_nueva = uniqid($time_stamp . "_d_") . "." . $ext;
            if (move_uploaded_file($_FILES['foto_despues_edit']['tmp_name'], $base_dir . $foto_despues_nueva)) {
                if(!empty($row['foto_despues']) && file_exists($base_dir . $row['foto_despues'])) unlink($base_dir . $row['foto_despues']);
                $upd_despues = ", foto_despues = '$foto_despues_nueva'";
                $foto_d_final = $foto_despues_nueva;
            }
        }
    }

    if (isset($_FILES['video_archivo_edit']) && $_FILES['video_archivo_edit']['error'] === UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['video_archivo_edit']['tmp_name']);
        finfo_close($finfo);
        if (in_array($mime, $allowed_video_mimes)) {
            $ext = strtolower(pathinfo($_FILES['video_archivo_edit']['name'], PATHINFO_EXTENSION));
            $video_nuevo = uniqid($time_stamp . "_v_") . "." . $ext;
            if (move_uploaded_file($_FILES['video_archivo_edit']['tmp_name'], $base_dir . $video_nuevo)) {
                if(!empty($row['video_url']) && file_exists($base_dir . $row['video_url'])) unlink($base_dir . $row['video_url']);
                $upd_video = ", video_url = '$video_nuevo'";
                $video_final = $video_nuevo;
            }
        }
    }

    $sql = "UPDATE san_historias 
            SET cliente_nombre = '$cliente', testimonio = '$testimonio' 
            $upd_antes $upd_despues $upd_video 
            WHERE id_historia = $id";
            
    if (mysqli_query($conexion, $sql)) {
        $html_actualizada = generarFilaExito($id, $cliente, $foto_a_final, $foto_d_final, $video_final, $testimonio);
        if (ob_get_length()) ob_clean();
        echo json_encode(['exito' => true, 'mensaje' => 'Caso actualizado correctamente.', 'html' => $html_actualizada, 'id' => $id]);
    } else {
        if (ob_get_length()) ob_clean();
        echo json_encode(['exito' => false, 'mensaje' => 'Error DB al actualizar: ' . mysqli_error($conexion)]);
    }
    exit;
}

if ($accion === 'eliminar') {
    $id = (int)$_POST['id_historia'];
    $res = mysqli_query($conexion, "SELECT foto_antes, foto_despues, video_url FROM san_historias WHERE id_historia = $id");
    if($row = mysqli_fetch_assoc($res)){
        if(!empty($row['foto_antes']) && file_exists($base_dir . $row['foto_antes'])) unlink($base_dir . $row['foto_antes']);
        if(!empty($row['foto_despues']) && file_exists($base_dir . $row['foto_despues'])) unlink($base_dir . $row['foto_despues']);
        if(!empty($row['video_url']) && file_exists($base_dir . $row['video_url'])) unlink($base_dir . $row['video_url']);
    }

    if(mysqli_query($conexion, "DELETE FROM san_historias WHERE id_historia = $id")){
        if (ob_get_length()) ob_clean();
        echo json_encode(['exito' => true, 'id' => $id]);
    } else {
        if (ob_get_length()) ob_clean();
        echo json_encode(['exito' => false, 'mensaje' => 'Error DB al eliminar.']);
    }
    exit;
}

if ($accion === 'cambiar_estado') {
    $id = (int)$_POST['id_historia'];
    $estado = (int)$_POST['estado'];
    $nuevo_estado = $estado == 1 ? 0 : 1; 
    
    if(mysqli_query($conexion, "UPDATE san_historias SET estado = $nuevo_estado WHERE id_historia = $id")){
        if (ob_get_length()) ob_clean();
        echo json_encode(['exito' => true, 'id' => $id, 'nuevo_estado' => $nuevo_estado]);
    } else {
        if (ob_get_length()) ob_clean();
        echo json_encode(['exito' => false, 'mensaje' => 'Error DB al actualizar.']);
    }
    exit;
}
?>
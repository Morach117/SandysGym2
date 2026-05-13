<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

require_once("../../../../funciones_globales/funciones_conexion.php");
$conexion = obtener_conexion();
mysqli_set_charset($conexion, "utf8mb4");

$accion = $_POST['accion'] ?? '';
$root_dir = $_SERVER['DOCUMENT_ROOT']; 
$dir_hero = $root_dir . '/sandys_web/assets/img/hero/';
$dir_galeria = $root_dir . '/sandys_web/assets/img/gallery/';

if (!is_dir($dir_hero)) mkdir($dir_hero, 0755, true);
if (!is_dir($dir_galeria)) mkdir($dir_galeria, 0755, true);

// ================= HERO =================
if ($accion === 'nuevo_hero' || $accion === 'editar_hero') {
    $subtitulo = mysqli_real_escape_string($conexion, trim($_POST['subtitulo']));
    $titulo_html = mysqli_real_escape_string($conexion, trim(strip_tags($_POST['titulo_html'], '<strong>')));
    $img_query_part = "";

    if (isset($_FILES['imagen_bg']) && $_FILES['imagen_bg']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['imagen_bg']['name'], PATHINFO_EXTENSION));
        $img_name = uniqid(time() . "_") . "." . $ext;
        move_uploaded_file($_FILES['imagen_bg']['tmp_name'], $dir_hero . $img_name);
        
        if ($accion === 'editar_hero') {
            $id = (int)$_POST['id'];
            $res = mysqli_query($conexion, "SELECT imagen_bg FROM san_landing_hero WHERE id_hero = $id");
            if($row = mysqli_fetch_assoc($res)){ if(file_exists($dir_hero . $row['imagen_bg'])) unlink($dir_hero . $row['imagen_bg']); }
            $img_query_part = ", imagen_bg = '$img_name'";
        } else {
            $img_query_part = $img_name;
        }
    }

    if ($accion === 'nuevo_hero') {
        $sql = "INSERT INTO san_landing_hero (subtitulo, titulo_html, imagen_bg) VALUES ('$subtitulo', '$titulo_html', '$img_query_part')";
    } else {
        $id = (int)$_POST['id'];
        $sql = "UPDATE san_landing_hero SET subtitulo = '$subtitulo', titulo_html = '$titulo_html' $img_query_part WHERE id_hero = $id";
    }

    if(mysqli_query($conexion, $sql)) echo json_encode(['exito' => true]);
    else echo json_encode(['exito' => false, 'mensaje' => mysqli_error($conexion)]);
    exit;
}

if ($accion === 'estado_hero') {
    $id = (int)$_POST['id'];
    $nuevo = $_POST['estado'] == 1 ? 0 : 1;
    mysqli_query($conexion, "UPDATE san_landing_hero SET estado = $nuevo WHERE id_hero = $id");
    echo json_encode(['exito' => true]); exit;
}

if ($accion === 'eliminar_hero') {
    $id = (int)$_POST['id'];
    $res = mysqli_query($conexion, "SELECT imagen_bg FROM san_landing_hero WHERE id_hero = $id");
    if($row = mysqli_fetch_assoc($res)){ if(file_exists($dir_hero . $row['imagen_bg'])) unlink($dir_hero . $row['imagen_bg']); }
    mysqli_query($conexion, "DELETE FROM san_landing_hero WHERE id_hero = $id");
    echo json_encode(['exito' => true]); exit;
}

// ================= PLANES =================
if ($accion === 'nuevo_plan' || $accion === 'editar_plan') {
    $nombre = mysqli_real_escape_string($conexion, trim($_POST['nombre']));
    $precio = (float)$_POST['precio'];
    $frecuencia = mysqli_real_escape_string($conexion, trim($_POST['frecuencia']));
    $url_boton = mysqli_real_escape_string($conexion, trim($_POST['url_boton']));
    
    $beneficios = array_filter($_POST['beneficios'], fn($val) => trim($val) !== '');
    $json = mysqli_real_escape_string($conexion, json_encode(array_values($beneficios), JSON_UNESCAPED_UNICODE));

    if ($accion === 'nuevo_plan') {
        $sql = "INSERT INTO san_landing_planes (nombre, precio, frecuencia, beneficios_json, url_boton) VALUES ('$nombre', $precio, '$frecuencia', '$json', '$url_boton')";
    } else {
        $id = (int)$_POST['id'];
        $sql = "UPDATE san_landing_planes SET nombre = '$nombre', precio = $precio, frecuencia = '$frecuencia', beneficios_json = '$json', url_boton = '$url_boton' WHERE id_plan = $id";
    }

    if(mysqli_query($conexion, $sql)) echo json_encode(['exito' => true]);
    else echo json_encode(['exito' => false, 'mensaje' => mysqli_error($conexion)]);
    exit;
}

if ($accion === 'estado_plan') {
    $id = (int)$_POST['id'];
    $nuevo = $_POST['estado'] == 1 ? 0 : 1;
    mysqli_query($conexion, "UPDATE san_landing_planes SET estado = $nuevo WHERE id_plan = $id");
    echo json_encode(['exito' => true]); exit;
}

if ($accion === 'eliminar_plan') {
    $id = (int)$_POST['id'];
    mysqli_query($conexion, "DELETE FROM san_landing_planes WHERE id_plan = $id");
    echo json_encode(['exito' => true]); exit;
}

// ================= GALERÍA =================
if ($accion === 'nueva_galeria' || $accion === 'editar_galeria') {
    $es_wide = isset($_POST['es_wide']) ? 1 : 0;
    $img_query_part = "";

    if (isset($_FILES['imagen_url']) && $_FILES['imagen_url']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['imagen_url']['name'], PATHINFO_EXTENSION));
        $img_name = uniqid(time() . "_g_") . "." . $ext;
        move_uploaded_file($_FILES['imagen_url']['tmp_name'], $dir_galeria . $img_name);
        
        if ($accion === 'editar_galeria') {
            $id = (int)$_POST['id'];
            $res = mysqli_query($conexion, "SELECT imagen_url FROM san_landing_galeria WHERE id_galeria = $id");
            if($row = mysqli_fetch_assoc($res)){ if(file_exists($dir_galeria . $row['imagen_url'])) unlink($dir_galeria . $row['imagen_url']); }
            $img_query_part = ", imagen_url = '$img_name'";
        } else {
            $img_query_part = $img_name;
        }
    }

    if ($accion === 'nueva_galeria') {
        $sql = "INSERT INTO san_landing_galeria (imagen_url, es_wide) VALUES ('$img_query_part', $es_wide)";
    } else {
        $id = (int)$_POST['id'];
        $sql = "UPDATE san_landing_galeria SET es_wide = $es_wide $img_query_part WHERE id_galeria = $id";
    }

    if(mysqli_query($conexion, $sql)) echo json_encode(['exito' => true]);
    else echo json_encode(['exito' => false, 'mensaje' => mysqli_error($conexion)]);
    exit;
}

if ($accion === 'estado_galeria') {
    $id = (int)$_POST['id'];
    $nuevo = $_POST['estado'] == 1 ? 0 : 1;
    mysqli_query($conexion, "UPDATE san_landing_galeria SET estado = $nuevo WHERE id_galeria = $id");
    echo json_encode(['exito' => true]); exit;
}

if ($accion === 'eliminar_galeria') {
    $id = (int)$_POST['id'];
    $res = mysqli_query($conexion, "SELECT imagen_url FROM san_landing_galeria WHERE id_galeria = $id");
    if($row = mysqli_fetch_assoc($res)){ if(file_exists($dir_galeria . $row['imagen_url'])) unlink($dir_galeria . $row['imagen_url']); }
    mysqli_query($conexion, "DELETE FROM san_landing_galeria WHERE id_galeria = $id");
    echo json_encode(['exito' => true]); exit;
}

echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida.']);
?>
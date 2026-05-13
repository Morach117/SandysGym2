<?php
error_reporting(0);

require_once("../../../../funciones_globales/funciones_conexion.php");
$conexion = obtener_conexion();
mysqli_set_charset($conexion, "utf8mb4");

$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

// ================= LISTAR FAQ (HTML PARA TABLA) =================
if ($accion === 'listar') {
    $query = "SELECT id_faq, pregunta, respuesta, orden, estado FROM san_faq ORDER BY orden ASC, id_faq DESC";
    $resultado = mysqli_query($conexion, $query);
    
    $filas = "";
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        while ($row = mysqli_fetch_assoc($resultado)) {
            $estado_label = $row['estado'] == 1 ? '<span class="label label-success">Activo</span>' : '<span class="label label-default">Inactivo</span>';
            
            // Atributo data-orden presente para vinculación con jQuery
            $filas .= "<tr>
                        <td>{$row['id_faq']}</td>
                        <td><strong class='text-info'>" . htmlspecialchars($row['pregunta'], ENT_QUOTES) . "</strong></td>
                        <td>" . nl2br(htmlspecialchars($row['respuesta'], ENT_QUOTES)) . "</td>
                        <td><span class='label label-info'>{$row['orden']}</span></td>
                        <td>{$estado_label}</td>
                        <td class='text-right'>
                            <button class='btn btn-xs btn-default btn-editar' 
                                data-id='{$row['id_faq']}' 
                                data-pregunta='" . htmlspecialchars($row['pregunta'], ENT_QUOTES) . "' 
                                data-respuesta='" . htmlspecialchars($row['respuesta'], ENT_QUOTES) . "' 
                                data-orden='{$row['orden']}' 
                                data-estado='{$row['estado']}'>
                                <span class='glyphicon glyphicon-pencil'></span>
                            </button>
                            <button class='btn btn-xs btn-danger btn-eliminar' data-id='{$row['id_faq']}'><span class='glyphicon glyphicon-trash'></span></button>
                        </td>
                       </tr>";
        }
    } else {
        $filas = "<tr><td colspan='6' class='text-center text-muted'>No hay preguntas frecuentes registradas.</td></tr>";
    }
    echo $filas;
    exit;
}

// ================= ACCIONES CRUD (RESPUESTAS JSON) =================
header('Content-Type: application/json; charset=utf-8');

if ($accion === 'guardar') {
    $id_faq = (int)($_POST['id_faq'] ?? 0);
    $pregunta = mysqli_real_escape_string($conexion, trim($_POST['pregunta']));
    $respuesta = mysqli_real_escape_string($conexion, trim($_POST['respuesta']));
    $orden = (int)($_POST['orden'] ?? 0);
    $estado = (int)($_POST['estado'] ?? 0);

    if ($id_faq > 0) {
        $sql = "UPDATE san_faq SET pregunta = '$pregunta', respuesta = '$respuesta', orden = $orden, estado = $estado WHERE id_faq = $id_faq";
    } else {
        $sql = "INSERT INTO san_faq (pregunta, respuesta, orden, estado) VALUES ('$pregunta', '$respuesta', $orden, $estado)";
    }
    
    if(mysqli_query($conexion, $sql)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($accion === 'eliminar') {
    $id_faq = (int)($_POST['id_faq'] ?? 0);
    $sql = "DELETE FROM san_faq WHERE id_faq = $id_faq";
    
    if(mysqli_query($conexion, $sql)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'msg' => 'Acción no válida.']);
?>
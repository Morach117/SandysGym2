<?php
include "../../../funciones_globales/funciones_conexion.php";
$conexion = obtener_conexion();

$id_socio = $_POST['id_socio'];
$metodo_contacto = $_POST['metodo_contacto'];

$query = "UPDATE san_socios SET metodo_contacto = ? WHERE soc_id_socio = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param('si', $metodo_contacto, $id_socio);

$response = array();

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['success'] = false;
    $response['error'] = $stmt->error;
}

echo json_encode($response);

$stmt->close();
$conexion->close();
?>

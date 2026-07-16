<?php
require '../conn.php'; 

header('Content-Type: application/json');

if (!isset($_POST['email'])) {
    echo json_encode(['exists' => false, 'message' => 'Correo no proporcionado']);
    exit;
}

$email = trim($_POST['email']);

// Preparar la consulta
$query = "SELECT soc_correo_status FROM san_socios WHERE soc_correo = :email LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();

$response = array();

if ($stmt->rowCount() > 0) {
    // El correo ya existe
    $response['exists'] = true;
    $response['message'] = "El correo electrónico ya está registrado.";
} else {
    // El correo no existe
    $response['exists'] = false;
}

$stmt->closeCursor();
$conn = null;

// Devolver la respuesta en formato JSON
echo json_encode($response);
?>

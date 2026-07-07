<?php
require '../conn.php'; // Archivo de conexión a la base de datos

header('Content-Type: application/json');

// Obtener el correo electrónico del cliente
$email = $_POST['email'];

// Preparar la consulta
$query = "SELECT soc_nombres AS name, soc_apepat AS paternal_surname, soc_apemat AS maternal_surname, soc_correo_status FROM san_socios WHERE soc_correo = ?";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $email);
$stmt->execute();

$response = array();

if ($stmt->rowCount() > 0) {
    // El correo existe, obtener los datos del usuario
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user['soc_correo_status'] == 1) {
        // El correo está en uso, mostrar mensaje de error con SweetAlert
        $response['exists'] = false;
        $response['message'] = "El correo electrónico ya está en uso.";
    } else {
        // El correo está disponible, pero no devolvemos el nombre para evitar fuga de información de registros incompletos
        $response['exists'] = true;
    }
} else {
    // El correo no existe
    $response['exists'] = false;
}

$stmt->closeCursor();
$conn = null;

// Devolver la respuesta en formato JSON
echo json_encode($response);
?>

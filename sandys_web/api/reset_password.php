<?php
session_start();
header('Content-Type: application/json');
require '../conn.php';

date_default_timezone_set('America/Mexico_City');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Acceso denegado.']));
}

$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

try {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (empty($token) || empty($new_password)) {
        throw new Exception('El token y la nueva contraseña son obligatorios.');
    }
    if (strlen($new_password) < 8) {
        throw new Exception('La contraseña debe tener al menos 8 caracteres.');
    }

    $currentDateTime = date('Y-m-d H:i:s'); 
    $hashedToken = hash('sha256', $token);
    
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = :token AND expDate > :now");
    $stmt->bindParam(':token', $hashedToken);
    $stmt->bindParam(':now', $currentDateTime);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('El token es inválido o ha caducado.');
    }
    $email = $row['email'];

    $conn->beginTransaction();

    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    $updateStmt = $conn->prepare("UPDATE san_socios SET san_password = :password WHERE soc_correo = :email");
    $updateStmt->bindParam(':password', $hashed_password);
    $updateStmt->bindParam(':email', $email);
    $updateStmt->execute();

    $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE token = :token");
    $deleteStmt->bindParam(':token', $hashedToken);
    $deleteStmt->execute();

    $conn->commit();

    $response = ['status' => 'success', 'message' => 'Tu contraseña ha sido restablecida correctamente.'];

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Error en reset_password.php: ' . $e->getMessage());
    $response['message'] = 'Ocurrió un error en el servidor. Por favor, inténtalo de nuevo.';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
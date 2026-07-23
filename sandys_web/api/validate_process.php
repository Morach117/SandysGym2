<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Acceso denegado: Método no permitido.']));
}

$maxValidationAttempts = 5;
$lockoutTime = 300;

if (!isset($_SESSION['validation_limiter'])) {
    $_SESSION['validation_limiter'] = ['attempts' => 0, 'last_attempt' => 0];
}
$limiter = &$_SESSION['validation_limiter'];

if ($limiter['attempts'] >= $maxValidationAttempts) {
    if (time() - $limiter['last_attempt'] < $lockoutTime) {
        die(json_encode(['success' => false, 'message' => 'Demasiados intentos fallidos. Por favor espera 5 minutos.']));
    } else {
        $limiter = ['attempts' => 0, 'last_attempt' => 0];
    }
}

require '../conn.php';
header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

try {
    if (empty(trim($_POST['validation_code']))) {
        throw new Exception('¡No se recibió ningún código de validación!');
    }
    $userCode = trim($_POST['validation_code']);

    if (strlen($userCode) !== 6 || !ctype_digit($userCode)) {
        throw new Exception('¡El código de validación debe ser numérico y de exactamente 6 dígitos!');
    }

    $stmt = $conn->prepare("SELECT soc_id_socio, soc_correo_status, validation_expires FROM san_socios WHERE validation_code = :code");
    $stmt->bindParam(':code', $userCode);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $limiter['attempts']++;
        $limiter['last_attempt'] = time();
        throw new Exception('¡Código de validación inválido!');
    }

    if ($user['soc_correo_status'] == 1) {
        throw new Exception('Esta cuenta ya ha sido validada anteriormente.');
    }

    if ($user['validation_expires'] !== null && strtotime($user['validation_expires']) < time()) {
        throw new Exception('El código de validación ha caducado. Por favor solicita uno nuevo.');
    }

    $conn->beginTransaction();

    $updateStatusStmt = $conn->prepare("UPDATE san_socios SET soc_correo_status = 1 WHERE soc_id_socio = :id");
    $updateStatusStmt->bindParam(':id', $user['soc_id_socio']);
    $updateStatusStmt->execute();

    $nullifyCodeStmt = $conn->prepare("UPDATE san_socios SET validation_code = NULL, validation_expires = NULL WHERE soc_id_socio = :id");
    $nullifyCodeStmt->bindParam(':id', $user['soc_id_socio']);
    $nullifyCodeStmt->execute();

    $conn->commit();

    $limiter = ['attempts' => 0, 'last_attempt' => 0];

    $response = ['success' => true, 'message' => '¡Cuenta validada correctamente! Serás redirigido.'];

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error de BD en validate_process: " . $e->getMessage());
    $response['message'] = 'Error en el servidor. Por favor, intenta de nuevo.';
    $response['success'] = false;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['success'] = false;
}

echo json_encode($response);
?>
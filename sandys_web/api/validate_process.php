<?php
// 1. INICIAR SESIÓN DE FORMA SEGURA
session_start();

// 2. PREVENIR ACCESO DIRECTO (Whitelist de método)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // 405 Method Not Allowed
    die(json_encode(['status' => 'error', 'message' => 'Acceso denegado: Método no permitido.']));
}

// 3. PROTECCIÓN CONTRA FUERZA BRUTA (Rate Limiting)
$maxValidationAttempts = 5;
$lockoutTime = 300; // 5 minutos en segundos

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

// 4. INCLUIR CONEXIÓN Y ESTABLECER RESPUESTA
require '../conn.php';
header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

try {
    // 5. VALIDAR Y SANITIZAR LA ENTRADA
    if (empty(trim($_POST['validation_code']))) {
        throw new Exception('¡No se recibió ningún código de validación!');
    }
    $userCode = trim($_POST['validation_code']);

    if (strlen($userCode) !== 6 || !ctype_digit($userCode)) {
        throw new Exception('¡El código de validación debe ser numérico y de exactamente 6 dígitos!');
    }

    // 6. CONSULTA PREPARADA SEGURA
    $stmt = $conn->prepare("SELECT soc_id_socio, soc_correo_status, validation_expires FROM san_socios WHERE validation_code = :code");
    $stmt->bindParam(':code', $userCode);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $limiter['attempts']++;
        $limiter['last_attempt'] = time();
        // Si no se encuentra el código, es inválido.
        throw new Exception('¡Código de validación inválido!');
    }

    if ($user['soc_correo_status'] == 1) {
        // Si la cuenta ya fue validada, informamos.
        // unset($_SESSION['awaiting_validation']); // <-- YA NO ES NECESARIO
        // NOTA: Tu JS ya redirige, así que este 'throw' puede que no sea lo ideal,
        // pero lo dejamos por si el usuario llega aquí de alguna manera.
        throw new Exception('Esta cuenta ya ha sido validada anteriormente.');
    }

    if ($user['validation_expires'] !== null && strtotime($user['validation_expires']) < time()) {
        throw new Exception('El código de validación ha caducado. Por favor solicita uno nuevo.');
    }

    // 7. TRANSACCIÓN ATÓMICA PARA ACTUALIZAR
    $conn->beginTransaction();

    // Paso 1: Activar la cuenta del usuario.
    $updateStatusStmt = $conn->prepare("UPDATE san_socios SET soc_correo_status = 1 WHERE soc_id_socio = :id");
    $updateStatusStmt->bindParam(':id', $user['soc_id_socio']);
    $updateStatusStmt->execute();

    // Paso 2: Anular el código de validación y la expiración para que no pueda ser reutilizado.
    $nullifyCodeStmt = $conn->prepare("UPDATE san_socios SET validation_code = NULL, validation_expires = NULL WHERE soc_id_socio = :id");
    $nullifyCodeStmt->bindParam(':id', $user['soc_id_socio']);
    $nullifyCodeStmt->execute();

    // Si todo salió bien, confirmamos los cambios.
    $conn->commit();

    // Reiniciar intentos de fuerza bruta en caso de éxito
    $limiter = ['attempts' => 0, 'last_attempt' => 0];

    // 8. LIMPIAR SESIÓN Y PREPARAR RESPUESTA DE ÉXITO
    // unset($_SESSION['awaiting_validation']); // <-- YA NO ES NECESARIO
    
    // CAMBIAMOS 'success' a true/false, ya que tu JS busca 'response.success'
    $response = ['success' => true, 'message' => '¡Cuenta validada correctamente! Serás redirigido.'];

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $response['message'] = 'Error en el servidor. Por favor, intenta de nuevo.';
    // error_log($e->getMessage()); // <-- Es buena idea registrar esto
    $response['success'] = false; // <-- Añadido

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['success'] = false; // <-- Añadido
}

// 9. ENVIAR RESPUESTA FINAL
echo json_encode($response);
?>
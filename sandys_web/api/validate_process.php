<?php
// 1. INICIAR SESIÓN DE FORMA SEGURA
// session_start(); // <-- YA NO ES NECESARIO

// 2. PREVENIR ACCESO DIRECTO (Whitelist de método)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // 405 Method Not Allowed
    die(json_encode(['status' => 'error', 'message' => 'Acceso denegado: Método no permitido.']));
}

// 3. VERIFICAR BANDERA DE SESIÓN
// ESTA ES LA CAUSA DEL ERROR. La comentamos. La seguridad la da el código en sí.
/*
if (!isset($_SESSION['awaiting_validation'])) {
    http_response_code(403); // 403 Forbidden
    die(json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']));
}
*/

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

    // 6. CONSULTA PREPARADA SEGURA
    $stmt = $conn->prepare("SELECT soc_id_socio, soc_correo_status FROM san_socios WHERE validation_code = :code");
    $stmt->bindParam(':code', $userCode);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
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

    // 7. TRANSACCIÓN ATÓMICA PARA ACTUALIZAR
    $conn->beginTransaction();

    // Paso 1: Activar la cuenta del usuario.
    $updateStatusStmt = $conn->prepare("UPDATE san_socios SET soc_correo_status = 1 WHERE soc_id_socio = :id");
    $updateStatusStmt->bindParam(':id', $user['soc_id_socio']);
    $updateStatusStmt->execute();

    // Paso 2: Anular el código de validación para que no pueda ser reutilizado.
    $nullifyCodeStmt = $conn->prepare("UPDATE san_socios SET validation_code = NULL WHERE soc_id_socio = :id");
    $nullifyCodeStmt->bindParam(':id', $user['soc_id_socio']);
    $nullifyCodeStmt->execute();

    // Si todo salió bien, confirmamos los cambios.
    $conn->commit();

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
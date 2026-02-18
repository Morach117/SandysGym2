<?php
// 1. CONFIGURACIÓN INICIAL Y DE SEGURIDAD
session_start();
header('Content-Type: application/json');
require '../conn.php';

// ==========================================================
// ======> ¡AQUÍ ESTÁ LA CORRECCIÓN! <======
// ==========================================================
// Asegura que este script use la misma zona horaria que el script
// que creó el token de expiración.
date_default_timezone_set('America/Mexico_City');
// ==========================================================

// Bloquear cualquier método que no sea POST para prevenir acceso directo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    die(json_encode(['status' => 'error', 'message' => 'Acceso denegado.']));
}

$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

try {
    // 2. VALIDAR Y SANITIZAR LA ENTRADA
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (empty($token) || empty($new_password)) {
        throw new Exception('El token y la nueva contraseña son obligatorios.');
    }
    // Añadir una regla de seguridad básica para la contraseña
    if (strlen($new_password) < 8) {
        throw new Exception('La contraseña debe tener al menos 8 caracteres.');
    }

    // 3. VERIFICAR EL TOKEN DE FORMA SEGURA
    // Ahora $currentDateTime usará la zona horaria de Mexico City
    $currentDateTime = date('Y-m-d H:i:s'); 
    
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = :token AND expDate > :now");
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':now', $currentDateTime);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('El token es inválido o ha caducado.');
    }
    $email = $row['email'];

    // 4. USAR UNA TRANSACCIÓN PARA GARANTIZAR LA INTEGRIDAD DE LOS DATOS
    $conn->beginTransaction();

    // [CRÍTICO] HASHEAR LA CONTRASEÑA DE FORMA SEGURA CON BCRYPT
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Actualizar la contraseña del usuario en la tabla principal
    $updateStmt = $conn->prepare("UPDATE san_socios SET san_password = :password WHERE soc_correo = :email");
    $updateStmt->bindParam(':password', $hashed_password);
    $updateStmt->bindParam(':email', $email);
    $updateStmt->execute();

    // Eliminar el token para que no pueda ser reutilizado
    $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE token = :token");
    $deleteStmt->bindParam(':token', $token);
    $deleteStmt->execute();

    // Si ambas operaciones fueron exitosas, confirmar los cambios
    $conn->commit();

    // 5. PREPARAR LA RESPUESTA DE ÉXITO
    $response = ['status' => 'success', 'message' => 'Tu contraseña ha sido restablecida correctamente.'];

} catch (PDOException $e) {
    // Si hay un error de base de datos, revertir la transacción
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // Enviar un mensaje genérico al usuario y registrar el error real para el desarrollador
    // error_log('Error en reset_password.php: ' . $e->getMessage());
    $response['message'] = 'Ocurrió un error en el servidor. Por favor, inténtalo de nuevo.';

} catch (Exception $e) {
    // Capturar cualquier otra excepción (ej. token inválido, contraseña corta)
    $response['message'] = $e->getMessage();
}

// 6. ENVIAR LA RESPUESTA FINAL
echo json_encode($response);
?>
<?php
// 1. INICIAR SESIÓN DE FORMA SEGURA
// Es lo primero que hacemos para poder acceder a las variables de sesión.
session_start();

// 2. PREVENIR ACCESO DIRECTO (Whitelist de método)
// Si alguien intenta abrir este archivo en el navegador (método GET) o usar otro método,
// se le negará el acceso. Solo permitimos peticiones POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // 405 Method Not Allowed
    // Detenemos el script para que no se vea nada del código.
    die(json_encode(['status' => 'error', 'message' => 'Acceso denegado: Método no permitido.']));
}

// 3. VERIFICAR BANDERA DE SESIÓN
// Comprobamos si el usuario realmente acaba de registrarse.
// Si no existe la bandera 'awaiting_validation', significa que está intentando
// acceder a este script sin haber pasado por el proceso de registro.
if (!isset($_SESSION['awaiting_validation'])) {
    http_response_code(403); // 403 Forbidden
    die(json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']));
}

// 4. INCLUIR CONEXIÓN Y ESTABLECER RESPUESTA
require '../conn.php';
header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

try {
    // 5. VALIDAR Y SANITIZAR LA ENTRADA
    // Verificamos que el código no esté vacío y lo limpiamos.
    if (empty(trim($_POST['validation_code']))) {
        throw new Exception('¡No se recibió ningún código de validación!');
    }
    $userCode = trim($_POST['validation_code']);

    // 6. CONSULTA PREPARADA SEGURA
    // Buscamos un usuario que tenga ese código y que AÚN NO esté verificado.
    $stmt = $conn->prepare("SELECT soc_id_socio, soc_correo_status FROM san_socios WHERE validation_code = :code");
    $stmt->bindParam(':code', $userCode);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Si no se encuentra el código, es inválido.
        throw new Exception('¡Código de validación inválido!');
    }

    if ($user['soc_correo_status'] == 1) {
        // Si la cuenta ya fue validada, informamos y limpiamos la sesión.
        unset($_SESSION['awaiting_validation']);
        throw new Exception('Esta cuenta ya ha sido validada anteriormente.');
    }

    // 7. TRANSACCIÓN ATÓMICA PARA ACTUALIZAR
    // Usamos una transacción para asegurar que ambas actualizaciones se completen
    // o ninguna lo haga. Esto previene inconsistencias en la base de datos.
    $conn->beginTransaction();

    // Paso 1: Activar la cuenta del usuario.
    $updateStatusStmt = $conn->prepare("UPDATE san_socios SET soc_correo_status = 1 WHERE soc_id_socio = :id");
    $updateStatusStmt->bindParam(':id', $user['soc_id_socio']);
    $updateStatusStmt->execute();

    // Paso 2: Anular el código de validación para que no pueda ser reutilizado (¡Muy importante!).
    $nullifyCodeStmt = $conn->prepare("UPDATE san_socios SET validation_code = NULL WHERE soc_id_socio = :id");
    $nullifyCodeStmt->bindParam(':id', $user['soc_id_socio']);
    $nullifyCodeStmt->execute();

    // Si todo salió bien, confirmamos los cambios.
    $conn->commit();

    // 8. LIMPIAR SESIÓN Y PREPARAR RESPUESTA DE ÉXITO
    unset($_SESSION['awaiting_validation']);
    $response = ['status' => 'success', 'message' => '¡Cuenta validada correctamente! Serás redirigido.'];

} catch (PDOException $e) {
    // Si hay un error con la base de datos, revertimos cualquier cambio.
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // Preparamos un mensaje de error genérico para el usuario.
    $response['message'] = 'Error en el servidor. Por favor, intenta de nuevo.';
    // Opcional: Registrar el error real para el desarrollador.
    // error_log($e->getMessage());

} catch (Exception $e) {
    // Capturamos cualquier otra excepción (ej. código inválido).
    $response['message'] = $e->getMessage();
}

// 9. ENVIAR RESPUESTA FINAL
// Solo hay un punto de salida, lo que hace el código más fácil de depurar.
echo json_encode($response);
?>
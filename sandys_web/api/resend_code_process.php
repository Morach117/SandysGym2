<?php
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $ruta_base_phpmailer = '../phpmailer/src/';
    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error crítico del servidor.']);
        exit;
    }
}

require_once '../conn.php';
require_once 'config.php';
require_once 'lib/EmailService.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

/**
 * Retorna una respuesta JSON y termina la ejecución
 */
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
}

$email = $_POST['email'] ?? null;

if (empty($email)) {
    $email = $_SESSION['user_email'] ?? $_SESSION['email'] ?? null;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
     json_response(['success' => false, 'message' => 'Por favor, ingrese un correo válido.'], 400);
}

try {
    $checkQuery = "SELECT soc_nombres, validation_code FROM san_socios WHERE soc_correo = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(1, $email);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $name = $row['soc_nombres'];
        
        $validation_code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $updateStmt = $conn->prepare("UPDATE san_socios SET validation_code = ?, validation_expires = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE soc_correo = ?");
        $updateStmt->execute([$validation_code, $email]);

        $asunto = 'Tu Código de Validación de Sandys Gym';

        ob_start();
        include 'templates/resend_validation_email.php';
        $mensaje = ob_get_clean();

        $emailSent = EmailService::send($email, $name, $asunto, $mensaje);

        if ($emailSent) {
            json_response(['success' => true, 'message' => 'Código de validación reenviado. Revisa tu correo.']);
        } else {
            json_response(['success' => false, 'message' => 'Error al enviar el correo. Inténtalo de nuevo.']);
        }

    } else {
        json_response(['success' => false, 'message' => 'El correo electrónico no está registrado.']);
    }

} catch (PDOException $e) {
    error_log("PDOException [resend_code_process]: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Error de conexión con el sistema.'], 500);
} catch (Exception $e) {
    error_log("Exception [resend_code_process]: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 500);
} finally {
    if (isset($checkStmt)) {
        $checkStmt->closeCursor();
    }
    $conn = null;
}
?>
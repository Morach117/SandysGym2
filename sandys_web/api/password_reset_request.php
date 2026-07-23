<?php
date_default_timezone_set('America/Mexico_City');

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
}

if (!isset($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Por favor, proporciona una dirección de correo válida.'], 400);
}

$email = $_POST['email'];

try {
    $query = $conn->prepare("SELECT soc_id_socio, soc_nombres FROM san_socios WHERE soc_correo = ?");
    $query->execute([$email]);
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $checkQuery = $conn->prepare("SELECT expDate FROM password_resets WHERE email = ? ORDER BY expDate DESC LIMIT 1");
        $checkQuery->execute([$email]);
        $lastRequest = $checkQuery->fetch(PDO::FETCH_ASSOC);

        if ($lastRequest) {
            $creationTime = strtotime($lastRequest['expDate']) - 1800;
            $timeElapsed = time() - $creationTime;

            if ($timeElapsed < 120) {
                json_response(['success' => true, 'message' => 'Ya se ha enviado un correo recientemente. Por favor, revisa tu bandeja de entrada o espera un par de minutos.']);
            }
        }

        $token = bin2hex(random_bytes(32));
        $expDate = date("Y-m-d H:i:s", strtotime('+30 minutes'));

        $deleteQuery = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $deleteQuery->execute([$email]);

        $hashedToken = hash('sha256', $token);

        $insertQuery = $conn->prepare("INSERT INTO password_resets (email, token, expDate) VALUES (?, ?, ?)");
        $insertQuery->execute([$email, $hashedToken, $expDate]);

        $asunto = "Restablece tu contraseña de Sandys Gym";
        $nombre_usuario = $user['soc_nombres'];
        $resetLink = BASE_URL_APP . "/index.php?page=reset_password&token=" . $token;

        ob_start();
        include 'templates/password_reset_email.php';
        $mensaje = ob_get_clean();

        EmailService::send($email, $nombre_usuario, $asunto, $mensaje);
    }

    json_response(['success' => true, 'message' => 'Si tu correo electrónico está en nuestros registros, recibirás un enlace para restablecer tu contraseña.']);

} catch (PDOException $e) {
    error_log('Error de BD en password_reset_request: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Ocurrió un error de base de datos.'], 500);

} catch (Exception $e) {
    error_log('Error en password_reset_request: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Ocurrió un error inesperado al procesar tu solicitud.'], 500);
} finally {
    $conn = null;
}
?>
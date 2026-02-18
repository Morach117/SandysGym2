<?php
// /api/resend_code.php
// Script seguro para reenviar el código de validación.

// 1. CARGAR DEPENDENCIAS (MANUALMENTE) Y CONFIGURACIÓN
// (Usa el bloque de carga manual, ya que NO usas Composer)
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $ruta_base_phpmailer = '../phpmailer/src/';
    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error crítico del servidor (Fallo al cargar Mailer).']);
        exit;
    }
}
// --- Fin de la carga manual ---

require_once '../conn.php';           // Carga la variable $conn
require_once 'config.php';            // Carga las constantes (SMTP_HOST, etc.)
require_once 'lib/EmailService.php';  // Carga nuestra clase de Email

// (No necesitamos UserService.php aquí, ya que la lógica es simple)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 2. PREPARAR RESPUESTA JSON
header('Content-Type: application/json');

// Función helper para enviar respuestas
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// 3. VALIDAR MÉTODO Y DATOS DE ENTRADA
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
}

$email = $_POST['email'] ?? null;
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
     json_response(['success' => false, 'message' => 'Por favor, ingrese un correo válido.'], 400);
}

// 4. LÓGICA DE NEGOCIO
try {
    // 4.1. Lógica de Base de Datos
    $checkQuery = "SELECT soc_nombres, validation_code FROM san_socios WHERE soc_correo = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(1, $email);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        // Variables para la plantilla y el envío
        $name = $row['soc_nombres'];
        $validation_code = $row['validation_code'];
        $asunto = 'Tu Código de Validación de Sandys Gym';

        // 4.2. Preparar y enviar correo
        // Usamos "output buffering" para "capturar" el HTML de la plantilla
        ob_start();
        include 'templates/resend_validation_email.php';
        $mensaje = ob_get_clean();

        // 4.3. Llamar a nuestro EmailService
        // (Ya no hay que configurar SMTP, PHPMailer, etc. aquí)
        $emailSent = EmailService::send($email, $name, $asunto, $mensaje);

        if ($emailSent) {
            json_response(['success' => true, 'message' => 'Código de validación reenviado. Revisa tu correo.']);
        } else {
            json_response(['success' => false, 'message' => 'Error al enviar el correo. Inténtalo de nuevo.']);
        }

    } else {
        // El correo no fue encontrado en la base de datos
        json_response(['success' => false, 'message' => 'El correo electrónico no está registrado.']);
    }

} catch (PDOException $e) {
    // Error en la base de datos
    // (Aquí deberías loguear $e->getMessage() en un archivo de log)
    json_response(['success' => false, 'message' => 'Error de conexión con el sistema.'], 500);
} catch (Exception $e) {
    // Otro error inesperado
    // (Aquí deberías loguear $e->getMessage() en un archivo de log)
    json_response(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 500);
} finally {
    if (isset($checkStmt)) {
        $checkStmt->closeCursor();
    }
    $conn = null;
}
?>
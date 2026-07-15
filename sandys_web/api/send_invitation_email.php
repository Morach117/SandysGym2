<?php
// /api/send_invitation_email.php

// 1. CARGAR DEPENDENCIAS
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $ruta_base_phpmailer = '../phpmailer/src/';
    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error crítico del servidor (Fallo al cargar Mailer).']);
        exit;
    }
}

require_once '../conn.php';           // Carga la variable $conn si llegaras a necesitar DB
require_once 'config.php';            // Carga las constantes
require_once 'lib/EmailService.php';  // Carga nuestra clase de Email

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Método no permitido.'], 405);
}

// 2. RECIBIR DATOS DEL FRONTEND
$emailDestino = $_POST['email'] ?? null;
$linkRegistro = $_POST['link'] ?? null;
$nombreSocio  = $_POST['nombre'] ?? 'Un amigo';

if (empty($emailDestino) || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
     json_response(['status' => 'error', 'message' => 'Por favor, ingrese un correo válido.'], 400);
}

if (empty($linkRegistro)) {
     json_response(['status' => 'error', 'message' => 'Error al generar el enlace de invitación.'], 400);
}

// 3. VALIDACIÓN DE SOCIO EXISTENTE
$allowExisting = isset($_POST['allow_existing']) && $_POST['allow_existing'] === '1';
if (!$allowExisting) {
    $stmtCheck = $conn->prepare("SELECT soc_id_socio FROM san_socios WHERE soc_correo = :email LIMIT 1");
    $stmtCheck->bindParam(':email', $emailDestino, PDO::PARAM_STR);
    $stmtCheck->execute();
    if ($stmtCheck->fetch()) {
        json_response(['status' => 'error', 'message' => 'Correo ya existe, favor de introducir uno diferente'], 400);
    }
}

// 4. LÓGICA DE ENVÍO
try {
    $asunto = '¡' . mb_strtoupper($nombreSocio, 'UTF-8') . ' te ha invitado a Sandy\'s Gym!';

    // Cargamos la plantilla en memoria (Output Buffering)
    ob_start();
    include 'templates/invitation_email.php';
    $mensaje = ob_get_clean();

    // 4. USAR EMAIL SERVICE
    $emailSent = EmailService::send($emailDestino, 'Futuro Socio', $asunto, $mensaje);

    if ($emailSent) {
        json_response(['status' => 'success', 'message' => 'Invitación enviada con éxito.']);
    } else {
        json_response(['status' => 'error', 'message' => 'Error al enviar el correo. Inténtalo de nuevo.']);
    }

} catch (Exception $e) {
    json_response(['status' => 'error', 'message' => 'Ocurrió un error inesperado al procesar el correo.'], 500);
}
?>
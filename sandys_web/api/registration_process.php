<?php
// /api/registration_process.php

// 1. CARGAR DEPENDENCIAS (MANUALMENTE) Y CONFIGURACIÓN

// --- Carga manual de PHPMailer ---
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $ruta_base_phpmailer = '../phpmailer/src/';
    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    } else {
        // Detener si no se encuentra la librería
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error crítico del servidor (Fallo al cargar Mailer).']);
        exit;
    }
}
// --- Fin de la carga manual ---

require_once '../conn.php';           // Carga la variable $conn
require_once 'config.php';            // Carga las constantes (SMTP_HOST, etc.)
require_once 'lib/UserService.php';   // Carga nuestra clase de Usuario
require_once 'lib/EmailService.php';  // Carga nuestra clase de Email

// 2. PREPARAR RESPUESTA JSON
header('Content-Type: application/json');

/**
 * Función helper para enviar respuestas JSON y terminar el script.
 */
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// 3. VALIDAR MÉTODO Y DATOS DE ENTRADA
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
}

// <-- ACTUALIZADO: Se usa trim() para limpiar espacios en blanco -->
$name               = trim($_POST['name'] ?? null);
$paternal_surname   = trim($_POST['paternal_surname'] ?? null);
$maternal_surname   = trim($_POST['maternal_surname'] ?? null);
$email              = trim($_POST['email'] ?? null);
$password           = $_POST['password'] ?? null; // La contraseña no se limpia
$telefono           = trim($_POST['telefono'] ?? null); 

// Validación simple (añadimos teléfono)
if (empty($name) || empty($email) || empty($password) || empty($telefono)) {
    json_response(['success' => false, 'message' => 'Faltan datos obligatorios (nombre, email, teléfono, contraseña).'], 400);
}

// 4. LÓGICA DE NEGOCIO
try {
    $conn->beginTransaction(); // Iniciar transacción

    // 4.1. Lógica de Base de Datos
    $userService = new UserService($conn);
    
    $validation_code = $userService->registerOrUpdate($name, $paternal_surname, $maternal_surname, $email, $password, $telefono);

    if ($validation_code === false) {
        $conn->rollBack();
        log_error("UserService->registerOrUpdate() falló. Posible error de BD. Email: $email, Tel: $telefono");
        json_response(['success' => false, 'message' => 'Error al registrar tus datos en el sistema.'], 500);
    }

    // 4.2. Preparar y enviar correo
    $asunto = "Tu Código de Validación para Sandys Gym";

    ob_start();
    if(file_exists('templates/validation_email.php')) {
        include 'templates/validation_email.php';
    } else {
        log_error("No se encontró la plantilla de email: templates/validation_email.php");
        throw new Exception("Error interno: No se encontró la plantilla de email.");
    }
    $mensaje = ob_get_clean();

    $emailSent = EmailService::send($email, $name, $asunto, $mensaje);

    if ($emailSent) {
        $conn->commit();
        json_response(['success' => true, 'message' => 'Registro exitoso. Se ha enviado un código a tu correo.']);
    } else {
        $conn->rollBack();
        log_error("EmailService::send() falló. No se pudo enviar correo de validación a: $email");
        json_response(['success' => false, 'message' => 'No pudimos enviar el correo de validación. Inténtalo de nuevo.']);
    }

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $error_message = "Excepción capturada: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine();
    log_error($error_message);
    
    json_response(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 500);
} finally {
    $conn = null;
}
?>
<?php
// api/registration_process.php

// 1. CONFIGURACIÓN
ini_set('display_errors', 1); // Solo para desarrollo
error_reporting(E_ALL);
header('Content-Type: application/json');

// Carga de PHPMailer
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $ruta_base_phpmailer = '../phpmailer/src/';
    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    }
}

require_once '../conn.php';
require_once 'config.php';
require_once 'lib/UserService.php';
require_once 'lib/EmailService.php';

function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
}

// 2. RECIBIR DATOS
$name               = trim($_POST['name'] ?? '');
$paternal_surname   = trim($_POST['paternal_surname'] ?? '');
$maternal_surname   = trim($_POST['maternal_surname'] ?? '');
$email              = trim($_POST['email'] ?? '');
$password           = $_POST['password'] ?? '';
$telefono           = trim($_POST['telefono'] ?? '');
$genero             = trim($_POST['genero'] ?? '');
// Fecha (Soporte doble nombre)
$mes_input          = trim($_POST['mes_nacimiento'] ?? $_POST['dob_month'] ?? '');
// Código de Referido (NUEVO)
$referral_code      = trim($_POST['referral_code'] ?? ''); 

// Construir Fecha
$fecha_nacimiento_sql = '';
if (!empty($mes_input)) {
    $fecha_nacimiento_sql = "2000-" . str_pad($mes_input, 2, "0", STR_PAD_LEFT) . "-01";
}

// Validar Obligatorios
if (empty($name) || empty($email) || empty($password) || empty($telefono) || empty($genero) || empty($fecha_nacimiento_sql)) {
    json_response([
        'success' => false, 
        'message' => 'Faltan datos obligatorios. Verifica el formulario.'
    ], 400);
}

// 3. PROCESAR
try {
    $conn->beginTransaction();

    $userService = new UserService($conn);
    
    // Llamada con el nuevo parámetro $referral_code al final
    $validation_code = $userService->registerOrUpdate(
        $name, 
        $paternal_surname, 
        $maternal_surname, 
        $email, 
        $password, 
        $telefono,
        $genero,
        $fecha_nacimiento_sql,
        $referral_code // <--- AQUÍ SE PASA EL CÓDIGO
    );

    if ($validation_code === false) {
        $conn->rollBack();
        json_response(['success' => false, 'message' => 'Error al registrar. Verifica tus datos.'], 500);
    }

    // 4. CORREO
    $asunto = "Bienvenido a Sandys Gym - Valida tu cuenta";
    ob_start();
    if(file_exists('templates/validation_email.php')) {
        include 'templates/validation_email.php';
    } else {
        echo "<h1>¡Bienvenido $name!</h1><p>Tu código: <strong>$validation_code</strong></p>";
    }
    $mensaje = ob_get_clean();

    $emailSent = EmailService::send($email, $name, $asunto, $mensaje);

    if ($emailSent) {
        $conn->commit();
        json_response(['success' => true, 'message' => 'Registro exitoso. Revisa tu correo.']);
    } else {
        $conn->rollBack();
        json_response(['success' => false, 'message' => 'Error al enviar correo. Verifica tu email.'], 500);
    }

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log("Error Registro: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()], 500);
}
?>
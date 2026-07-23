<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

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

$name               = trim($_POST['name'] ?? '');
$paternal_surname   = trim($_POST['paternal_surname'] ?? '');
$maternal_surname   = trim($_POST['maternal_surname'] ?? '');
$email              = trim($_POST['email'] ?? '');
$password           = trim($_POST['password'] ?? '');
$telefono           = trim($_POST['telefono'] ?? '');
$genero             = trim($_POST['genero'] ?? '');
$mes_input          = trim($_POST['mes_nacimiento'] ?? $_POST['dob_month'] ?? '');
$referral_code      = trim($_POST['referral_code'] ?? ''); 

$fecha_nacimiento_sql = '';
if (!empty($mes_input)) {
    $fecha_nacimiento_sql = "2000-" . str_pad($mes_input, 2, "0", STR_PAD_LEFT) . "-01";
}

if (empty($name) || empty($email) || empty($password) || empty($telefono) || empty($genero) || empty($fecha_nacimiento_sql)) {
    json_response([
        'success' => false, 
        'message' => 'Faltan datos obligatorios. Verifica el formulario.'
    ], 400);
}

if (!empty($referral_code) && !preg_match('/^[0-9]{10}$/', $referral_code)) {
    json_response([
        'success' => false, 
        'message' => 'El código de referido debe tener exactamente 10 dígitos numéricos.'
    ], 400);
}

try {
    $stmtCheck = $conn->prepare("SELECT soc_id_socio, soc_correo_status FROM san_socios WHERE soc_correo = :email LIMIT 1");
    $stmtCheck->bindParam(':email', $email, PDO::PARAM_STR);
    $stmtCheck->execute();
    $existingUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        if ($existingUser['soc_correo_status'] == 1) {
            json_response(['success' => false, 'message' => 'Este correo ya está registrado'], 400);
        } else {
            $conn->beginTransaction();
            $validation_code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+1 day'));
            
            $stmtUpdate = $conn->prepare("UPDATE san_socios SET validation_code = :code, validation_expires = :expires WHERE soc_id_socio = :id");
            $stmtUpdate->bindParam(':code', $validation_code);
            $stmtUpdate->bindParam(':expires', $expires);
            $stmtUpdate->bindParam(':id', $existingUser['soc_id_socio']);
            
            if (!$stmtUpdate->execute()) {
                $conn->rollBack();
                json_response(['success' => false, 'message' => 'Error al actualizar el código de validación.'], 500);
            }
            
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
                $_SESSION['user_email'] = $email;
                json_response(['success' => true, 'message' => 'Revisa tu correo con el nuevo código de validación.']);
            } else {
                $conn->rollBack();
                json_response(['success' => false, 'message' => 'Error al enviar correo. Verifica tu email.'], 500);
            }
        }
    }

    $conn->beginTransaction();

    $userService = new UserService($conn);
    
    $validation_code = $userService->registerOrUpdate(
        $name, 
        $paternal_surname, 
        $maternal_surname, 
        $email, 
        $password, 
        $telefono,
        $genero,
        $fecha_nacimiento_sql,
        $referral_code
    );

    if ($validation_code === false) {
        $conn->rollBack();
        json_response(['success' => false, 'message' => 'Error al registrar. Verifica tus datos.'], 500);
    }

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
        $_SESSION['user_email'] = $email;
        json_response(['success' => true, 'message' => 'Registro exitoso. Revisa tu correo.']);
    } else {
        $conn->rollBack();
        json_response(['success' => false, 'message' => 'Error al enviar correo. Verifica tu email.'], 500);
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error Registro: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Error interno al procesar el registro.'], 500);
}
?>
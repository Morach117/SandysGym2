<?php
require '../conn.php'; 

header('Content-Type: application/json');

if (!isset($_POST['email'])) {
    echo json_encode(['exists' => false, 'message' => 'Correo no proporcionado']);
    exit;
}

$email = trim($_POST['email']);

$query = "SELECT soc_correo_status FROM san_socios WHERE soc_correo = :email LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();

$response = array();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row['soc_correo_status'] == 1) {
        $response['exists'] = true;
        $response['message'] = "El correo electrónico ya está registrado.";
    } else {
        // Generar OTP y enviar correo
        require_once 'lib/EmailService.php';
        require_once 'config.php';
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $ruta_base_phpmailer = '../../phpmailer/src/';
            if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
                require_once $ruta_base_phpmailer . 'PHPMailer.php';
                require_once $ruta_base_phpmailer . 'SMTP.php';
                require_once $ruta_base_phpmailer . 'Exception.php';
            }
        }
        
        $otp = sprintf("%06d", mt_rand(1, 999999));
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['registro_otp'] = $otp;
        $_SESSION['registro_email'] = $email;

        $asunto = "Código de Verificación - Sandy's Gym";
        $mensaje = "<h3>Verifica tu cuenta</h3>
                    <p>Hemos encontrado tus datos en nuestro sistema. Usa el siguiente código para enlazar tu cuenta:</p>
                    <h2 style='color:#ef4444; font-size: 24px;'>{$otp}</h2>
                    <p>Si no solicitaste este código, ignora este correo.</p>";

        EmailService::send($email, "Socio", $asunto, $mensaje);

        $response['exists'] = true;
        $response['needs_verification'] = true;
        $response['message'] = "Hemos enviado un código a tu correo para verificar tu cuenta existente.";
    }
} else {
    $response['exists'] = false;
}

$stmt->closeCursor();
$conn = null;

echo json_encode($response);
?>

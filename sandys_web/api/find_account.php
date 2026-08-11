<?php
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $ruta_base_phpmailer = '../phpmailer/src/';
    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    } else {
        http_response_code(500);
        echo json_encode(['exists' => false, 'message' => 'Error crítico del servidor.']);
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

if (!isset($_POST['search_term'])) {
    echo json_encode(['exists' => false, 'message' => 'Término de búsqueda no proporcionado']);
    exit;
}

$search_term = trim($_POST['search_term']);

if (empty($search_term)) {
    echo json_encode(['exists' => false, 'message' => 'Por favor, ingresa un dato válido.']);
    exit;
}

$query = "";
$param = "";

// Determinar el tipo de búsqueda
if (strpos($search_term, '@') !== false) {
    // Es un correo
    $query = "SELECT soc_correo, soc_correo_status, soc_nombres, soc_apepat FROM san_socios WHERE soc_correo = :term LIMIT 1";
    $param = $search_term;
} elseif (preg_match('/^[0-9]{10}$/', $search_term)) {
    // Es un teléfono de 10 dígitos
    $query = "SELECT soc_correo, soc_correo_status, soc_nombres, soc_apepat FROM san_socios WHERE REPLACE(REPLACE(REPLACE(REPLACE(soc_tel_cel, ' ', ''), '-', ''), '(', ''), ')', '') = :term LIMIT 1";
    $param = $search_term;
} else {
    // Es un nombre
    $query = "SELECT soc_correo, soc_correo_status, soc_nombres, soc_apepat FROM san_socios WHERE CONCAT_WS(' ', soc_nombres, soc_apepat, soc_apemat) LIKE :term OR CONCAT_WS(' ', soc_nombres, soc_apepat) LIKE :term LIMIT 1";
    $param = "%" . str_replace(' ', '%', $search_term) . "%";
}

$stmt = $conn->prepare($query);
$stmt->bindParam(':term', $param, PDO::PARAM_STR);
$stmt->execute();

$response = array();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $email = $row['soc_correo'];

    if (empty($email)) {
        $response['exists'] = true;
        $response['needs_verification'] = false;
        $response['message'] = "Hemos encontrado tu cuenta, pero no tienes un correo registrado. Por favor, acude a recepción para agregar tu correo y poder usar la app.";
    } elseif ($row['soc_correo_status'] == 1) {
        $response['exists'] = true;
        $response['needs_verification'] = false;
        $response['message'] = "Esta cuenta ya está registrada y activada en la app. Por favor, inicia sesión.";
    } else {
        // Generar OTP y enviar correo
        $parts = explode("@", $email);
        $maskedEmail = substr($parts[0], 0, 1) . str_repeat("*", strlen($parts[0]) - 1) . "@" . $parts[1];
        
        $send_otp = isset($_POST['send_otp']) ? (int)$_POST['send_otp'] : 0;
        $response['exists'] = true;
        $response['needs_verification'] = true;
        $response['masked_email'] = $maskedEmail;
        $response['email'] = $email;

        if ($send_otp === 1) {
            $otp = sprintf("%06d", mt_rand(1, 999999));
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['registro_otp'] = $otp;
            $_SESSION['registro_email'] = $email;

            $asunto = "Código de Verificación - Sandy's Gym";
            
            ob_start();
            $rutaPlantilla = __DIR__ . '/templates/email_verificacion.php';
            if (file_exists($rutaPlantilla)) {
                include $rutaPlantilla;
            } else {
                echo "<h3>Verifica tu cuenta</h3><p>Usa el siguiente código: <strong>{$otp}</strong></p>";
            }
            $mensaje = ob_get_clean();

            EmailService::send($email, $row['soc_nombres'], $asunto, $mensaje);
            $response['message'] = "Hemos enviado un código de 6 dígitos a tu correo ({$maskedEmail}). Por seguridad, ingrésalo para continuar.";
        } else {
            $response['message'] = "Presiona Continuar para que enviemos un código de verificación a tu correo ({$maskedEmail}).";
        }
    }
} else {
    // Si la búsqueda no arroja resultados, y fue por correo, le decimos que el correo está disponible.
    // Si fue por teléfono o nombre, le decimos que no se encontró pero que puede registrarse llenando los datos.
    $response['exists'] = false;
    if (strpos($search_term, '@') !== false) {
        // Si buscó por correo, simplemente devuelve exists: false para que continúe llenando
        $response['is_email'] = true;
    } else {
        $response['is_email'] = false;
        $response['message'] = "No encontramos tu cuenta en sucursal. Por favor, asegúrate de escribir tu información correctamente o ingresa tu Correo Electrónico para registrarte desde cero.";
    }
}

$stmt->closeCursor();
$conn = null;

echo json_encode($response);
?>

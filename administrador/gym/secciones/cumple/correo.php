<?php
// Evitar mostrar errores de PHP en la respuesta AJAX para no romper el JSON
error_reporting(0);
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 1. Corrección de Rutas (Retrocediendo 4 niveles hasta la raíz)
$ruta_base_phpmailer = '../../../../funciones_globales/phpmailer/src/';

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    } else {
        echo json_encode(["exito" => false, "mensaje" => "Error crítico: No se pudo encontrar la librería PHPMailer en: $ruta_base_phpmailer"]);
        exit;
    }
}

// 2. Recepción y validación de datos POST
$email_destino = isset($_POST['email']) ? trim($_POST['email']) : '';
$nombre_socio = isset($_POST['socio']) ? trim($_POST['socio']) : '';
$mensaje_personalizado = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';

if(empty($email_destino) || empty($nombre_socio)) {
    echo json_encode(["exito" => false, "mensaje" => "Faltan datos obligatorios (correo o nombre)."]);
    exit;
}

// 3. Carga de Plantilla HTML usando ob_start()
// Definimos variables que usará la plantilla
$data_nombre = $nombre_socio;
$data_mensaje = nl2br(htmlspecialchars($mensaje_personalizado));

ob_start();
include 'plantilla_cumple.php';
$cuerpo_correo_html = ob_get_clean(); // Extrae el HTML procesado y limpia el buffer

// 4. Configuración y envío con PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.ionos.mx';
    $mail->SMTPAuth = true;
    $mail->Username = 'prueba@sandysgym.com';
    $mail->Password = 'Mor@ch117@'; // [!] Precaución: Contraseña en código fuente. Idealmente usa variables de entorno en producción.
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    $mail->CharSet = 'UTF-8';
    $mail->setFrom('prueba@sandysgym.com', 'Sandys Gym');
    $mail->addAddress($email_destino);
    $mail->isHTML(true);
    
    $mail->Subject = '¡Feliz Cumpleaños de parte de Sandys Gym!';
    $mail->Body    = $cuerpo_correo_html;

    $mail->send();
    
    // Log de éxito (Opcional, guardado en la misma carpeta)
    file_put_contents(__DIR__ . '/mail.log', "[" . date("Y-m-d H:i:s") . "] ÉXITO: Correo a '$email_destino'\n", FILE_APPEND);

    echo json_encode(["exito" => true, "mensaje" => "Correo enviado correctamente."]);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/mail.log', "[" . date("Y-m-d H:i:s") . "] ERROR: '$email_destino' - Detalles: " . $mail->ErrorInfo . "\n", FILE_APPEND);
    echo json_encode(["exito" => false, "mensaje" => "Error de PHPMailer: " . $mail->ErrorInfo]);
}
?>
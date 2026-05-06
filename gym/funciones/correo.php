<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// --- INICIO DE LA LÓGICA DE RUTAS ---

// Define la ruta base para los archivos de PHPMailer
$ruta_base_phpmailer = '../funciones_globales/phpmailer/src/';
$ruta_alternativa_phpmailer = '../../funciones_globales/phpmailer/src/';

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    // Define la ruta base para los archivos de PHPMailer
    $ruta_base_phpmailer = '../funciones_globales/phpmailer/src/';
    $ruta_alternativa_phpmailer = '../../funciones_globales/phpmailer/src/';

    // Comprueba si la primera ruta existe, si no, usa la alternativa
    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    } elseif (file_exists($ruta_alternativa_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_alternativa_phpmailer . 'PHPMailer.php';
        require_once $ruta_alternativa_phpmailer . 'SMTP.php';
        require_once $ruta_alternativa_phpmailer . 'Exception.php';
    } else {
        // Si no se encuentra la librería en ninguna ruta, detiene la ejecución.
        die("Error crítico: No se pudo encontrar la librería PHPMailer.");
    }
}

// --- FIN DE LA LÓGICA DE RUTAS ---


// Define la ruta del archivo de log para que se cree en el directorio actual
define('MAIL_LOG_FILE', __DIR__ . '/mail.log');

/**
 * Envía un correo electrónico utilizando PHPMailer y registra el resultado en un log.
 */
function enviar_correo($destinatario, $asunto, $mensaje)
{
    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = 'smtp.ionos.mx';
        $mail->SMTPAuth = true;
        $mail->Username = 'prueba@sandysgym.com';
        $mail->Password = 'Mor@ch117@';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Contenido del correo
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('prueba@sandysgym.com', 'Sandys Gym');
        $mail->addAddress($destinatario);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje;

        $mail->send();

        // Registrar éxito en el log
        $log_message = "[" . date("Y-m-d H:i:s") . "] ÉXITO: Correo enviado a '$destinatario' con asunto '$asunto'.\n";
        file_put_contents(MAIL_LOG_FILE, $log_message, FILE_APPEND);
        
        return true;
    } catch (Exception $e) {
        // Registrar error en el log
        $error_message = "[" . date("Y-m-d H:i:s") . "] ERROR: No se pudo enviar el correo a '$destinatario'. Asunto: '$asunto'. Detalles: " . $mail->ErrorInfo . "\n";
        file_put_contents(MAIL_LOG_FILE, $error_message, FILE_APPEND);
        
        return false;
    }
}

/**
 * Obtiene la dirección de correo electrónico de un socio a partir de su ID.
 */
function obtener_correo_socio($id_socio)
{
    global $conexion;
    $query = "SELECT soc_correo FROM san_socios WHERE soc_id_socio = $id_socio";
    $resultado = mysqli_query($conexion, $query);
    if ($resultado && $fila = mysqli_fetch_assoc($resultado)) {
        return $fila['soc_correo'];
    }
    return false;
}
?>
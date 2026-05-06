<?php
error_reporting(0);
header('Content-Type: application/json');

require_once '../../../../funciones_globales/funciones_conexion.php';
$conexion = obtener_conexion();

if (!$conexion) {
    echo json_encode(["exito" => false, "mensaje" => "Error de conexión a la BD."]);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$accion = $_POST['accion'] ?? '';

if ($accion === 'eliminar') {
    $id = (int)($_POST['id_contacto'] ?? 0);
    $sql = "DELETE FROM san_contactos WHERE id_contacto = $id";
    if (mysqli_query($conexion, $sql)) {
        echo json_encode(["exito" => true, "mensaje" => "Mensaje eliminado."]);
    } else {
        echo json_encode(["exito" => false, "mensaje" => mysqli_error($conexion)]);
    }
    exit;
}

if ($accion === 'responder') {
    $id_contacto = (int)($_POST['id_contacto'] ?? 0);
    $email_destino = mysqli_real_escape_string($conexion, $_POST['email'] ?? '');
    $nombre_socio = mysqli_real_escape_string($conexion, $_POST['socio'] ?? '');
    
    // Extracción cruda para preservar HTML de la BD
    $mensaje_usuario = trim($_POST['mensaje'] ?? ''); 

    if (empty($email_destino) || empty($mensaje_usuario)) {
        echo json_encode(["exito" => false, "mensaje" => "Faltan datos para el envío."]);
        exit;
    }

    $ruta_lib = '../../../../funciones_globales/phpmailer/src/';
    require_once $ruta_lib . 'PHPMailer.php';
    require_once $ruta_lib . 'SMTP.php';
    require_once $ruta_lib . 'Exception.php';

    // Preparación de variables para la plantilla HTML
    $data_nombre = $nombre_socio;
    $data_mensaje = nl2br($mensaje_usuario);

    ob_start();
    include 'plantilla_comentario.php';
    $cuerpo_correo = ob_get_clean();

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.ionos.mx';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'prueba@sandysgym.com';
        $mail->Password   = 'Mor@ch117@';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';
        
        $mail->setFrom('prueba@sandysgym.com', 'Soporte - Sandys Gym');
        $mail->addAddress($email_destino);
        $mail->isHTML(true);
        $mail->Subject = 'Respuesta a tu mensaje 📩';
        
        $mail->Body = $cuerpo_correo;

        $mail->send();

        mysqli_query($conexion, "UPDATE san_contactos SET leido = 1 WHERE id_contacto = $id_contacto");

        echo json_encode(["exito" => true, "mensaje" => "Respuesta enviada."]);
    } catch (Exception $e) {
        echo json_encode(["exito" => false, "mensaje" => "Error de PHPMailer: " . $mail->ErrorInfo]);
    }
    exit;
}
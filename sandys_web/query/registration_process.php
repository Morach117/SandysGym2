<?php

// =================================================================================
// ARCHIVOS REQUERIDOS Y CONFIGURACIÓN INICIAL
// =================================================================================

require '../conn.php'; // Archivo de conexión a la base de datos (Asegúrate que usa PDO)

// --- Lógica de rutas para encontrar PHPMailer ---
// Esto hace el código más flexible si lo mueves de directorio.
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $ruta_base_phpmailer = '../phpmailer/src/';
    $ruta_alternativa_phpmailer = '../../phpmailer/src/'; // Por si se incluye desde otro nivel

    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    } elseif (file_exists($ruta_alternativa_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_alternativa_phpmailer . 'PHPMailer.php';
        require_once $ruta_alternativa_phpmailer . 'SMTP.php';
        require_once $ruta_alternativa_phpmailer . 'Exception.php';
    } else {
        die("Error crítico: No se pudo encontrar la librería PHPMailer.");
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Define la ruta del archivo de log en el mismo directorio que este script.
define('MAIL_LOG_FILE', __DIR__ . '/mail_errors.log');

// =================================================================================
// FUNCIÓN REUTILIZABLE PARA ENVIAR CORREOS
// =================================================================================

/**
 * Envía un correo electrónico utilizando PHPMailer y registra cualquier error.
 *
 * @param string $destinatario Correo del destinatario.
 * @param string $nombre_destinatario Nombre del destinatario.
 * @param string $asunto Asunto del correo.
 * @param string $mensaje Cuerpo del correo en HTML.
 * @return bool Devuelve true si el correo se envió con éxito, false en caso contrario.
 */
function enviar_correo_validacion($destinatario, $nombre_destinatario, $asunto, $mensaje)
{
    $mail = new PHPMailer(true);
    try {
        // --- Configuración del servidor SMTP (IONOS) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.ionos.mx';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'prueba@sandysgym.com'; // Tu correo de Ionos
        $mail->Password   = 'Mor@ch117@';                   // Tu contraseña
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // --- Contenido del correo ---
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('prueba@sandysgym.com', 'Sandys Gym');
        $mail->addAddress($destinatario, $nombre_destinatario);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje;

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Registrar el error detallado en el archivo de log para depuración.
        $error_message = "[" . date("Y-m-d H:i:s") . "] ERROR al enviar a '$destinatario': " . $mail->ErrorInfo . "\n";
        file_put_contents(MAIL_LOG_FILE, $error_message, FILE_APPEND);
        return false;
    }
}

// =================================================================================
// LÓGICA PRINCIPAL DEL SCRIPT (REGISTRO DE USUARIO)
// =================================================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name             = $_POST['name'];
    $paternal_surname = $_POST['paternal_surname'];
    $maternal_surname = $_POST['maternal_surname'];
    $email            = $_POST['email'];
    $password         = hash('sha256', $_POST['password']);
    $validation_code  = substr(str_shuffle("0123456789"), 0, 4); // Código numérico de 4 dígitos
    $fecha_captura    = date("Y-m-d H:i:s");
    $fecha_nacimiento = '0000-00-00'; // Fecha de nacimiento por defecto
    $db_success       = false;

    // 1. VERIFICAR SI EL CORREO YA EXISTE
    $checkQuery = "SELECT soc_id_socio FROM san_socios WHERE soc_correo = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(1, $email);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        // 2.A. El correo ya existe -> ACTUALIZAR DATOS
        $updateQuery = "UPDATE san_socios SET san_password = ?, validation_code = ?, soc_nombres = ?, soc_apepat = ?, soc_apemat = ?, soc_fecha_captura = ? WHERE soc_correo = ?";
        $stmt = $conn->prepare($updateQuery);
        $db_success = $stmt->execute([$password, $validation_code, $name, $paternal_surname, $maternal_surname, $fecha_captura, $email]);
    } else {
        // 2.B. El correo no existe -> INSERTAR NUEVO REGISTRO
        $insertQuery = "INSERT INTO san_socios (soc_nombres, soc_apepat, soc_apemat, soc_correo, san_password, soc_fecha_captura, soc_fecha_nacimiento, validation_code, soc_id_usuario, soc_id_empresa, soc_id_consorcio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 17, 1, 1)";
        $stmt = $conn->prepare($insertQuery);
        $db_success = $stmt->execute([$name, $paternal_surname, $maternal_surname, $email, $password, $fecha_captura, $fecha_nacimiento, $validation_code]);
    }

    $checkStmt->closeCursor();
    if (isset($stmt)) {
        $stmt->closeCursor();
    }
    
    // 3. SI LA OPERACIÓN EN LA BASE DE DATOS FUE EXITOSA, ENVIAR CORREO
    if ($db_success) {
        $asunto = "Tu Código de Validación para Sandys Gym";
        
        // --- PLANTILLA DE CORREO PERSONALIZADA PARA EL CÓDIGO ---
        $mensaje = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>' . htmlspecialchars($asunto) . '</title>
            <style>
                body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; }
                .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden; }
                .header { text-align: center; padding: 30px 20px; background-color: #222222; }
                .content { padding: 35px 30px; color: #555555; line-height: 1.7; text-align: center; }
                .content h1 { color: #222222; font-size: 24px; }
                .code-box { background-color: #f8f9fa; border: 1px solid #eeeeee; border-radius: 8px; padding: 20px; margin: 25px auto; max-width: 200px; }
                .code-box .code { font-size: 36px; font-weight: bold; color: #e74c3c; letter-spacing: 5px; }
                .footer { background-color: #222222; color: #aaaaaa; text-align: center; padding: 25px 20px; font-size: 13px; }
                .footer a { color: #e74c3c; text-decoration: none; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <img src="https://sergym.com/imagenes/empresa_1.png" alt="Sandy\'s Gym Logo" style="max-width: 150px;">
                </div>
                <div class="content">
                    <h1>¡Casi listo, ' . htmlspecialchars($name) . '!</h1>
                    <p>Gracias por registrarte. Usa el siguiente código para validar tu cuenta en nuestra aplicación:</p>
                    <div class="code-box">
                        <div class="code">' . htmlspecialchars($validation_code) . '</div>
                    </div>
                    <p style="font-size: 14px; color: #888;">Si no solicitaste este código, puedes ignorar este mensaje.</p>
                </div>
                <div class="footer">
                    <p style="margin:5px 0;"><strong>SANDY\'S GYM</strong></p>
                    <p style="margin:5px 0;">Av. Miguel Hidalgo 308, Bienestar Social, 29077 Tuxtla Gutiérrez, Chis.</p>
                    <p style="margin:5px 0;"><a href="https://www.facebook.com/gymsandy">Facebook</a> &nbsp;&middot;&nbsp; <a href="https://www.instagram.com/sandysgym/">Instagram</a></p>
                </div>
            </div>
        </body>
        </html>';
        
        // --- Llamada a la función de envío ---
        if (enviar_correo_validacion($email, $name, $asunto, $mensaje)) {
            // Éxito en BD y en envío de correo
            echo json_encode(['success' => true, 'message' => 'Registro exitoso. Se ha enviado un código a tu correo.']);
        } else {
            // Éxito en BD pero fallo en envío de correo
            echo json_encode(['success' => false, 'message' => 'Se completó tu registro, pero no pudimos enviar el correo de validación. Inténtalo de nuevo más tarde.']);
        }

    } else {
        // 4. SI LA OPERACIÓN EN LA BASE DE DATOS FALLÓ
        echo json_encode(['success' => false, 'message' => 'Error al registrar tus datos en el sistema. Inténtalo de nuevo.']);
    }

    $conn = null; // Cerrar la conexión
}
?>
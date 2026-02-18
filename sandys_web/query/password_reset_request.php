<?php
header('Content-Type: application/json'); // MEJORA: Es una buena práctica declarar el tipo de contenido.

// --- ARCHIVOS REQUERIDOS ---
require '../conn.php';
// --- Lógica de rutas para encontrar PHPMailer ---
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $ruta_base_phpmailer = '../phpmailer/src/';
    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    } else {
        // Detener si no se encuentra la librería
        echo json_encode(['success' => false, 'message' => 'Error crítico del servidor.']);
        exit;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Define la ruta del archivo de log para errores
define('MAIL_LOG_FILE', __DIR__ . '/mail_errors.log');

// --- LÓGICA PRINCIPAL ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!isset($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, proporciona una dirección de correo válida.']);
        exit;
    }

    $email = $_POST['email'];
    date_default_timezone_set('America/Mexico_City');

    // MEJORA DE SEGURIDAD (CRÍTICA): Prevenir la enumeración de usuarios.
    // Siempre mostraremos un mensaje de éxito, sin importar si el correo existe o no.
    // El trabajo real (generar token y enviar correo) solo se hará si el correo existe.
    // Esto evita que un atacante pueda usar este formulario para adivinar qué correos están registrados.

    try {
        $query = $conn->prepare("SELECT soc_id_socio, soc_nombres FROM san_socios WHERE soc_correo = ?");
        $query->execute([$email]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

        // Solo si el usuario existe, procedemos a enviar el correo.
        if ($user) {
            // Generar un token de restablecimiento seguro
            $token = bin2hex(random_bytes(32)); // 32 bytes = 64 caracteres hexadecimales
            $expDate = date("Y-m-d H:i:s", strtotime('+1 hour'));

            // MEJORA: Invalidar tokens antiguos antes de insertar el nuevo.
            // Esto asegura que solo el último enlace de restablecimiento sea válido.
            $deleteQuery = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $deleteQuery->execute([$email]);

            // Insertar el nuevo token en la base de datos
            $insertQuery = $conn->prepare("INSERT INTO password_resets (email, token, expDate) VALUES (?, ?, ?)");
            $insertQuery->execute([$email, $token, $expDate]);

            // Enviar correo electrónico con una plantilla mejorada
            $resetLink = "https://sandysgym.com/index.php?page=reset_password&token=" . $token;
            $subject = "Restablece tu contraseña de Sandys Gym";

            // --- PLANTILLA DE CORREO PROFESIONAL ---
            $message = '
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>' . htmlspecialchars($subject) . '</title>
                <style>
                    /* Estilos del correo (similares a los que ya usas) */
                    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; }
                    .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden; }
                    .header { text-align: center; padding: 30px 20px; background-color: #222222; }
                    .content { padding: 35px 30px; color: #555555; line-height: 1.7; text-align: center; }
                    .content h1 { color: #222222; font-size: 24px; }
                    .button { display: inline-block; background-color: #e74c3c; color: #ffffff; padding: 12px 25px; border-radius: 5px; text-decoration: none; font-weight: bold; margin-top: 20px; }
                    .footer { background-color: #222222; color: #aaaaaa; text-align: center; padding: 25px 20px; font-size: 13px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <img src="https://sergym.com/imagenes/empresa_1.png" alt="Sandy\'s Gym Logo" style="max-width: 150px;">
                    </div>
                    <div class="content">
                        <h1>¿Necesitas una nueva contraseña?</h1>
                        <p>Hola, ' . htmlspecialchars($user['soc_nombres']) . '. Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el botón de abajo para continuar.</p>
                        <a href="' . $resetLink . '" class="button">Restablecer Contraseña</a>
                        <p style="font-size: 14px; color: #888; margin-top: 30px;">Si no solicitaste esto, puedes ignorar este correo de forma segura.</p>
                    </div>
                    <div class="footer">
                        <p style="margin:5px 0;"><strong>SANDY\'S GYM</strong></p>
                    </div>
                </div>
            </body>
            </html>';

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.ionos.mx';
            $mail->SMTPAuth = true;
            $mail->Username   = 'prueba@sandysgym.com'; // Tu correo de Ionos
            $mail->Password   = 'Mor@ch117@';                   // Tu contraseña
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom('prueba@sandysgym.com', 'Sandys Gym');
            $mail->addAddress($email, $user['soc_nombres']);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $message;

            $mail->send();
        }

        // Se envía la respuesta de éxito genérica aquí, fuera del bloque if ($user)
        echo json_encode(['success' => true, 'message' => 'Si tu correo electrónico está en nuestros registros, recibirás un enlace para restablecer tu contraseña.']);
    } catch (Exception $e) {
        // MEJORA: No exponer errores detallados al cliente.
        // Se registra el error en el servidor para que el desarrollador lo revise.
        $error_message = "[" . date("Y-m-d H:i:s") . "] ERROR en password_reset_request.php: " . $e->getMessage() . "\n";
        file_put_contents(MAIL_LOG_FILE, $error_message, FILE_APPEND);

        // Se envía un mensaje genérico al usuario.
        echo json_encode(['success' => false, 'message' => 'Ocurrió un error al procesar tu solicitud. Por favor, intenta de nuevo más tarde.']);
    }

    $conn = null;
}

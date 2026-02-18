<?php
// /api/lib/EmailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {

    public static function send($destinatario, $nombre, $asunto, $mensaje)
    {
        $mail = new PHPMailer(true);
        
        try {
            // --- Configuración del servidor ---
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            
            // --- Contenido del correo ---
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($destinatario, $nombre);
            
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $mensaje;

            $mail->send();

            // 2. Si tiene éxito, registrarlo en el log de ÉXITO
            $success_message = "[" . date("Y-m-d H:i:s") . "] ÉXITO al enviar a '$destinatario'. Asunto: '$asunto'\n";
            
            // ¡CAMBIO AQUÍ! Usamos la nueva constante
            file_put_contents(MAIL_SUCCESS_LOG_FILE, $success_message, FILE_APPEND);
            
            return true;

        } catch (Exception $e) {
            
            // 3. Si falla, registrar el error en el log de ERROR
            $error_message = "[" . date("Y-m-d H:i:s") . "] ERROR al enviar a '$destinatario'. Asunto: '$asunto'. Error: " . $mail->ErrorInfo . "\n";
            
            // ¡CAMBIO AQUÍ! Usamos la nueva constante
            file_put_contents(MAIL_ERROR_LOG_FILE, $error_message, FILE_APPEND);
            
            return false;
        }
    }
}
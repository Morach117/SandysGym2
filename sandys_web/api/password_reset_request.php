<?php
// /api/password_reset_request.php
date_default_timezone_set('America/Mexico_City');

// 1. CARGAR DEPENDENCIAS (MANUALMENTE) Y CONFIGURACIÓN
// --- Carga manual de PHPMailer ---
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $ruta_base_phpmailer = '../phpmailer/src/';
    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error crítico del servidor (Fallo al cargar Mailer).']);
        exit;
    }
}
// --- Fin de la carga manual ---

require_once '../conn.php';           // Carga la variable $conn
require_once 'config.php';            // Carga las constantes (SMTP_HOST, etc.)
require_once 'lib/EmailService.php';  // Carga nuestra clase de Email

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 2. PREPARAR RESPUESTA JSON
header('Content-Type: application/json');

// Función helper para enviar respuestas
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// 3. VALIDAR MÉTODO Y DATOS DE ENTRADA
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
}

if (!isset($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Por favor, proporciona una dirección de correo válida.'], 400);
}

$email = $_POST['email'];

// 4. LÓGICA DE NEGOCIO (CON PREVENCIÓN DE ENUMERACIÓN DE USUARIOS)
try {
    $query = $conn->prepare("SELECT soc_id_socio, soc_nombres FROM san_socios WHERE soc_correo = ?");
    $query->execute([$email]);
    $user = $query->fetch(PDO::FETCH_ASSOC);

    // Solo si el usuario existe, generamos el token y enviamos el correo.
    if ($user) {
        
        // 1. Evitar doble envío (Rate Limiting de 2 minutos)
        $checkQuery = $conn->prepare("SELECT expDate FROM password_resets WHERE email = ? ORDER BY expDate DESC LIMIT 1");
        $checkQuery->execute([$email]);
        $lastRequest = $checkQuery->fetch(PDO::FETCH_ASSOC);

        if ($lastRequest) {
            // Calculamos cuándo se creó el token restando los 30 minutos que le dimos de vigencia
            $creationTime = strtotime($lastRequest['expDate']) - 1800; // 1800 segundos = 30 minutos
            $timeElapsed = time() - $creationTime;

            if ($timeElapsed < 120) { // 120 segundos = 2 minutos
                // Devolvemos success true para no revelar si el correo existe o no, pero con un mensaje de advertencia
                json_response(['success' => true, 'message' => 'Ya se ha enviado un correo recientemente. Por favor, revisa tu bandeja de entrada o espera un par de minutos.']);
            }
        }

        // 2. Generar token y expiración (30 minutos)
        $token = bin2hex(random_bytes(32));
        $expDate = date("Y-m-d H:i:s", strtotime('+30 minutes'));

        // 3. Invalidar tokens antiguos
        $deleteQuery = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $deleteQuery->execute([$email]);

        // Hashear el token antes de guardarlo
        $hashedToken = hash('sha256', $token);

        // Insertar el nuevo token hasheado en la base de datos
        $insertQuery = $conn->prepare("INSERT INTO password_resets (email, token, expDate) VALUES (?, ?, ?)");
        $insertQuery->execute([$email, $hashedToken, $expDate]);

        // =================================================================
        // --- INICIO DE LA MEJORA (USANDO EmailService) ---
        // =================================================================

        // 1. Definir variables para la plantilla
        $asunto = "Restablece tu contraseña de Sandys Gym";
        $nombre_usuario = $user['soc_nombres'];
        $resetLink = "https://sandysgym.com/index.php?page=reset_password&token=" . $token;

        // 2. Cargar la plantilla HTML en una variable
        ob_start();
        include 'templates/password_reset_email.php';
        $mensaje = ob_get_clean();

        // 3. Enviar el correo usando el EmailService
        // (Las credenciales y la configuración de SMTP están seguras en config.php)
        // (El log de éxito/error se manejará automáticamente dentro de EmailService)
        EmailService::send($email, $nombre_usuario, $asunto, $mensaje);
        
        // (YA NO NECESITAMOS LA CONFIGURACIÓN DE PHPMailer AQUÍ)
        // (YA NO NECESITAMOS LAS CREDENCIALES AQUÍ)

        // =================================================================
        // --- FIN DE LA MEJORA ---
        // =================================================================
    }

    // Respuesta de éxito genérica (Seguridad: previene enumeración de usuarios)
    json_response(['success' => true, 'message' => 'Si tu correo electrónico está en nuestros registros, recibirás un enlace para restablecer tu contraseña.']);

} catch (PDOException $e) {
    // Error de Base de Datos
    error_log('Error de BD en password_reset_request: ' . $e->getMessage()); // Loguea el error real en el servidor
    json_response(['success' => false, 'message' => 'Ocurrió un error de base de datos.'], 500);

} catch (Exception $e) {
    // Otro error (ej. random_bytes falló)
    error_log('Error en password_reset_request: ' . $e->getMessage()); // Loguea el error real en el servidor
    json_response(['success' => false, 'message' => 'Ocurrió un error inesperado al procesar tu solicitud.'], 500);
} finally {
    $conn = null;
}
?>
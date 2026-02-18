<?php
// 1. INICIAR SESIÓN Y CONFIGURAR RESPUESTA
session_start();
header('Content-Type: application/json'); // Siempre define el tipo de contenido

// 2. ARCHIVOS REQUERIDOS Y FUNCIÓN DE RESPUESTA
include '../conn.php';
/** @var PDO $conn */ // (Para ayudar a tu editor VS Code)

// Función helper para respuestas JSON limpias
function json_response($res) {
    echo json_encode(['res' => $res]);
    exit;
}

// 3. CONFIGURACIÓN DEL LÍMITE DE INTENTOS
$maxLoginAttempts = 3;
$lockoutTime = 240; // 4 minutos en segundos

// Usamos un array en la sesión para mantenerlo limpio
if (!isset($_SESSION['login_limiter'])) {
    $_SESSION['login_limiter'] = ['attempts' => 0, 'last_attempt' => 0];
}
// Usamos una referencia (&) para modificar el array original fácilmente
$limiter = &$_SESSION['login_limiter'];

// 4. MEJORA DE SEGURIDAD: NO USAR extract()
// Obtenemos las variables de forma segura
$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;

if (empty($email) || empty($password)) {
    json_response('invalid'); // Mensaje genérico
    exit;
}

// 5. VERIFICAR BLOQUEO DE INTENTOS (Tu lógica actual, que es correcta)
if ($limiter['attempts'] >= $maxLoginAttempts) {
    if (time() - $limiter['last_attempt'] < $lockoutTime) {
        json_response('locked');
    } else {
        // Reiniciar los intentos después del tiempo de bloqueo
        $limiter = ['attempts' => 0, 'last_attempt' => 0];
    }
}

// 6. VALIDAR EMAIL
$email = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($email === false) {
    json_response('invalid_email');
}

// 7. LÓGICA DE INICIO DE SESIÓN
try {
    // Tu consulta preparada (¡esto es seguro contra SQLi!)
    $selAcc = $conn->prepare("SELECT soc_id_socio, soc_correo, san_password, soc_correo_status FROM san_socios WHERE soc_correo = :email");
    $selAcc->bindParam(':email', $email);
    $selAcc->execute();

    if ($selAcc->rowCount() > 0) {
        $selAccRow = $selAcc->fetch(PDO::FETCH_ASSOC);

        if ($selAccRow['soc_correo_status'] != 1) {
            json_response('inactive_email');
        }

        // ==========================================================
        // MEJORA DE SEGURIDAD CRÍTICA (PASSWORD_VERIFY)
        // Compara de forma segura la contraseña proporcionada con el hash de la BD
        // ==========================================================
        if (password_verify($password, $selAccRow['san_password'])) {
            
            // ¡ÉXITO!
            
            // MEJORA DE SEGURIDAD (FIJACIÓN DE SESIÓN)
            // Regeneramos el ID de la sesión para prevenir secuestros de sesión
            session_regenerate_id(true);

            $_SESSION['admin'] = [
                'soc_id_socio' => $selAccRow['soc_id_socio'], // Guarda el ID, es más útil
                'soc_correo' => $selAccRow['soc_correo'],
                'adminnakalogin' => true,
            ];

            // Restablecer los intentos después de un inicio de sesión exitoso
            $limiter = ['attempts' => 0, 'last_attempt' => 0];

            json_response('success');

        } else {
            // Contraseña incorrecta
            $limiter['attempts']++;
            $limiter['last_attempt'] = time();
            json_response('invalid');
        }
    } else {
        // Usuario no encontrado
        $limiter['attempts']++;
        $limiter['last_attempt'] = time();
        json_response('invalid');
    }

} catch (PDOException $e) {
    // Error de base de datos
    error_log('Error en login: ' . $e->getMessage()); // Registra el error real en el log del servidor
    json_response('error_db'); // Envía un mensaje genérico
}
?>
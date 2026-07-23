<?php
$domain = isset($_SERVER['HTTP_HOST']) ? preg_replace('/^www\./', '', explode(':', $_SERVER['HTTP_HOST'])[0]) : '';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $domain,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
header('Content-Type: application/json');

include '../conn.php';

/**
 * Retorna una respuesta en formato JSON y termina la ejecución
 */
function json_response($res) {
    echo json_encode(['res' => $res]);
    exit;
}

$maxLoginAttempts = 3;
$lockoutTime = 240;

if (!isset($_SESSION['login_limiter'])) {
    $_SESSION['login_limiter'] = ['attempts' => 0, 'last_attempt' => 0];
}
$limiter = &$_SESSION['login_limiter'];

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    json_response('invalid');
    exit;
}

if ($limiter['attempts'] >= $maxLoginAttempts) {
    if (time() - $limiter['last_attempt'] < $lockoutTime) {
        json_response('locked');
    } else {
        $limiter = ['attempts' => 0, 'last_attempt' => 0];
    }
}

$email = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($email === false) {
    json_response('invalid_email');
}

try {
    $selAcc = $conn->prepare("SELECT soc_id_socio, soc_correo, san_password, soc_correo_status FROM san_socios WHERE soc_correo = :email");
    $selAcc->bindParam(':email', $email);
    $selAcc->execute();

    if ($selAcc->rowCount() > 0) {
        $selAccRow = $selAcc->fetch(PDO::FETCH_ASSOC);

        if ($selAccRow['soc_correo_status'] != 1) {
            json_response('inactive_email');
        }

        if (password_verify($password, $selAccRow['san_password'])) {
            session_regenerate_id(true);

            $_SESSION['admin'] = [
                'soc_id_socio' => $selAccRow['soc_id_socio'],
                'soc_correo' => $selAccRow['soc_correo'],
                'adminnakalogin' => true,
            ];

            $limiter = ['attempts' => 0, 'last_attempt' => 0];

            json_response('success');

        } else {
            $limiter['attempts']++;
            $limiter['last_attempt'] = time();
            json_response('invalid');
        }
    } else {
        $limiter['attempts']++;
        $limiter['last_attempt'] = time();
        json_response('invalid');
    }

} catch (PDOException $e) {
    error_log('Error en login: ' . $e->getMessage());
    json_response('error_db');
}
?>
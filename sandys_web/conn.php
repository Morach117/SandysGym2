<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Carga variables de entorno desde un archivo .env
 */
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
loadEnv(__DIR__ . '/.env');

$local_hosts = [
    'localhost',
    '127.0.0.1',
    'gym.test',
    '192.168.0.181'
];

$current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if (in_array($current_host, $local_hosts)) {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "dbs1756575";
} else {
    $host = getenv('DB_HOST') ?: "localhost";
    $user = getenv('DB_USER') ?: "root";
    $pass = getenv('DB_PASS') ?: "";
    $db   = getenv('DB_NAME') ?: "dbs1756575";
}

$conn = null;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('Error de conexión a la BD: ' . $e->getMessage());
    die("Error de conexión con el sistema. Por favor, intente más tarde.");
}
?>
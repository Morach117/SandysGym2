<?php
/*
|--------------------------------------------------------------------------
| Archivo de Conexión Universal (Local y Producción)
|--------------------------------------------------------------------------
|
| Este script detecta automáticamente el entorno (local o producción)
| y establece la conexión PDO correspondiente.
|
*/

// Iniciar la sesión si no está activa (buena práctica para un archivo central)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. --- DEFINIR CREDENCIALES ---

// Lista de hosts que consideramos "locales"
$local_hosts = [
    'localhost',
    '127.0.0.1'
];

// Revisa en qué host (dominio) se está ejecutando el script
$current_host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // '??' maneja si se ejecuta desde consola

if (in_array($current_host, $local_hosts)) {
    // Estamos en LOCAL
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "dbs1756575";
} else {
    // Estamos en PRODUCCIÓN (el servidor de hosting)
    $host = "db5002171142.hosting-data.io";
    $user = "dbu577361";
    $pass = "Sandys_empresas_2";
    $db   = "dbs1756575";
}

// 2. --- CONEXIÓN PDO ÚNICA ---

$conn = null;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      // Manejo de errores
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,            // Modo de "fetch" por defecto
    PDO::ATTR_EMULATE_PREPARES   => false,                       // Usar preparaciones nativas
];

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4"; // charset es importante

try {
    // Intenta conectar usando las variables dinámicas ($host, $user, $pass)
    $conn = new PDO($dsn, $user, $pass, $options);
     
} catch (PDOException $e) {
    // Si algo sale mal, registra el error y detén la ejecución
    error_log('Error de conexión a la BD: ' . $e->getMessage()); // Registra el error real en el log del servidor
    die("Error de conexión con el sistema. Por favor, intente más tarde."); // Mensaje genérico para el usuario
}

/** @var PDO $conn */ // Esto es para que VS Code no te marque $conn en rojo
?>
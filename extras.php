<?php
/*
|--------------------------------------------------------------------------
| Instalador Independiente de Base de Datos - Sandys Gym
|--------------------------------------------------------------------------
|
| Este script detecta el entorno, se conecta mediante PDO y crea la
| tabla necesaria para el módulo de contactos.
|
*/

// 1. --- DEFINIR CREDENCIALES Y ENTORNO ---
$local_hosts = ['localhost', '127.0.0.1', 'gym.test', '192.168.0.181'];
$current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if (in_array($current_host, $local_hosts)) {
    // Estamos en LOCAL
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "dbs1756575";
} else {
    // Estamos en PRODUCCIÓN
    $host = "db5002171142.hosting-data.io";
    $user = "dbu577361";
    $pass = "Sandys_empresas_2";
    $db   = "dbs1756575";
}

// 2. --- ESTABLECER CONEXIÓN PDO ---
$conn = null;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

// Estilos básicos tipo "Dark Mode" para la visualización del instalador
echo "<body style='background-color: #050505; color: #ffffff; font-family: sans-serif; padding: 40px;'>";
echo "<h2 style='color: #ef4444;'>⚙️ Instalador de BD - Sandys Gym</h2>";
echo "<hr style='border-color: #333;'>";

try {
    $conn = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color: #10b981;'>✅ Conexión a la base de datos establecida con éxito (Entorno: <b>{$current_host}</b>).</p>";

    // 3. --- CONSULTA SQL PARA CREAR LA TABLA ---
    $sql_contactos = "
        CREATE TABLE IF NOT EXISTS `san_contactos` (
            `id_contacto` int(11) NOT NULL AUTO_INCREMENT,
            `nombre` varchar(100) NOT NULL,
            `correo` varchar(100) NOT NULL,
            `telefono` varchar(20) DEFAULT NULL,
            `mensaje` text NOT NULL,
            `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
            `leido` tinyint(1) DEFAULT 0,
            PRIMARY KEY (`id_contacto`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // 4. --- EJECUTAR LA CREACIÓN ---
    $conn->exec($sql_contactos);
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_contactos'</b> verificada/creada con éxito.</p>";

    // 5. --- AVISO DE SEGURIDAD ---
    echo "<br><div style='background-color: #1a1a1a; padding: 20px; border-left: 5px solid #F28123; border-radius: 5px;'>";
    echo "<h3 style='color: #F28123; margin-top: 0;'>⚠️ ALERTA DE SEGURIDAD CRÍTICA</h3>";
    echo "<p>La tabla está lista para usarse. <b>Por favor, elimina este archivo (instalar_bd.php) de tu servidor inmediatamente.</b> Dejarlo público expone la estructura de tu base de datos y tus credenciales de producción.</p>";
    echo "</div>";

} catch (PDOException $e) {
    // Si falla la conexión o la consulta
    echo "<p style='color: #ef4444;'>❌ <b>Error crítico:</b> " . $e->getMessage() . "</p>";
}

echo "</body>";
?>
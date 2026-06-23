<?php
/*
|--------------------------------------------------------------------------
| Instalador Independiente de Base de Datos - Sandys Gym (Progresos/Timers)
|--------------------------------------------------------------------------
*/

$local_hosts = ['localhost', '127.0.0.1', 'gym.test', '192.168.0.181'];
$current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if (in_array($current_host, $local_hosts)) {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "dbs1756575";
} else {
    $host = "db5002171142.hosting-data.io";
    $user = "dbu577361";
    $pass = "Sandys_empresas_2";
    $db   = "dbs1756575";
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

echo "<body style='background-color: #050505; color: #ffffff; font-family: sans-serif; padding: 40px;'>";
echo "<h2 style='color: #F28123;'>⚙️ Instalador de Módulos: Progresos y Timers</h2>";
echo "<hr style='border-color: #333;'>";

try {
    $conn = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color: #10b981;'>✅ Conexión establecida.</p>";

    // 1. Tabla de Progreso
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `san_rutinas_progreso` (
            `pro_id_progreso` INT AUTO_INCREMENT PRIMARY KEY,
            `pro_id_socio` INT NOT NULL,
            `pro_id_ejercicio` INT NOT NULL,
            `pro_peso_kg` DECIMAL(5,2) NOT NULL,
            `pro_repeticiones` INT NOT NULL,
            `pro_series` INT NOT NULL,
            `pro_fecha` DATETIME NOT NULL,
            FOREIGN KEY (`pro_id_socio`) REFERENCES `san_socios`(`soc_id_socio`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_rutinas_progreso'</b> verificada.</p>";

    // 2. Tabla de Timers
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `san_rutinas_timers` (
            `tim_id_timer` INT AUTO_INCREMENT PRIMARY KEY,
            `tim_id_rutina` INT NOT NULL,
            `tim_tiempo_esfuerzo_seg` INT NOT NULL,
            `tim_tiempo_descanso_seg` INT NOT NULL,
            `tim_series_totales` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_rutinas_timers'</b> verificada.</p>";

    echo "<br><div style='background-color: #1a1a1a; padding: 20px; border-left: 5px solid #ef4444; border-radius: 5px;'>";
    echo "<h3 style='color: #ef4444; margin-top: 0;'>⚠️ ALERTA DE SEGURIDAD</h3>";
    echo "<p>Elimina este archivo tras la ejecución en producción.</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<p style='color: #ef4444;'>❌ <b>Error:</b> " . $e->getMessage() . "</p>";
}
echo "</body>";
?>
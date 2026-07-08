<?php
/*
|--------------------------------------------------------------------------
| Instalador Independiente de Base de Datos - Sandys Gym (Plan Invitaciones)
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
echo "<h2 style='color: #F28123;'>⚙️ Instalador de Módulos: Plan Invitaciones</h2>";
echo "<hr style='border-color: #333;'>";

try {
    $conn = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color: #10b981;'>✅ Conexión establecida con éxito.</p>";

    // 1. Crear tabla san_plan_invitaciones
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `san_plan_invitaciones` (
          `id_invitacion` INT(11) NOT NULL AUTO_INCREMENT,
          `id_socio_titular` INT(11) NOT NULL,
          `token_unico` VARCHAR(64) NOT NULL,
          `status` ENUM('pendiente', 'aceptado', 'expirado', 'cancelado') NOT NULL DEFAULT 'pendiente',
          `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `fecha_expiracion` DATETIME NOT NULL,
          PRIMARY KEY (`id_invitacion`),
          UNIQUE KEY `token_unico` (`token_unico`),
          KEY `id_socio_titular` (`id_socio_titular`),
          KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_plan_invitaciones'</b> verificada/creada.</p>";

    // 2. Agregar columna soc_id_titular_grupo a san_socios si no existe
    $checkCol = $conn->query("SHOW COLUMNS FROM `san_socios` LIKE 'soc_id_titular_grupo'")->fetch();
    if (!$checkCol) {
        $conn->exec("ALTER TABLE san_socios ADD COLUMN soc_id_titular_grupo INT DEFAULT 0 AFTER soc_id_referido_por;");
        echo "<p style='color: #10b981;'>✅ Columna <b>'soc_id_titular_grupo'</b> agregada exitosamente en 'san_socios'.</p>";
    } else {
        echo "<p style='color: #9ca3af;'>ℹ️ La columna 'soc_id_titular_grupo' ya existía en 'san_socios'.</p>";
    }

    echo "<br><div style='background-color: #1a1a1a; padding: 20px; border-left: 5px solid #ef4444; border-radius: 5px;'>";
    echo "<h3 style='color: #ef4444; margin-top: 0;'>⚠️ ALERTA DE SEGURIDAD</h3>";
    echo "<p>Elimina este archivo de inmediato tras su ejecución exitosa en producción.</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<p style='color: #ef4444;'>❌ <b>Error:</b> " . $e->getMessage() . "</p>";
}
echo "</body>";
?>
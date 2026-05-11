<?php
/*
|--------------------------------------------------------------------------
| Instalador Independiente de Base de Datos - Sandys Gym
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

$conn = null;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

echo "<body style='background-color: #050505; color: #ffffff; font-family: sans-serif; padding: 40px;'>";
echo "<h2 style='color: #ef4444;'>⚙️ Instalador de Módulos (FAQ, Historias & Landing) - Sandys Gym</h2>";
echo "<hr style='border-color: #333;'>";

try {
    $conn = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color: #10b981;'>✅ Conexión establecida.</p>";

    // Tabla: san_faq
    $sql_faq = "
        CREATE TABLE IF NOT EXISTS `san_faq` (
            `id_faq` INT(11) NOT NULL AUTO_INCREMENT,
            `pregunta` TEXT NOT NULL,
            `respuesta` TEXT NOT NULL,
            `orden` INT(11) DEFAULT 0,
            PRIMARY KEY (`id_faq`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->exec($sql_faq);
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_faq'</b> verificada/creada.</p>";

    // Tabla: san_historias
    $sql_historias = "
        CREATE TABLE IF NOT EXISTS `san_historias` (
            `id_historia` INT(11) NOT NULL AUTO_INCREMENT,
            `cliente_nombre` VARCHAR(255) NOT NULL,
            `foto_antes` VARCHAR(255) NOT NULL,
            `foto_despues` VARCHAR(255) NOT NULL,
            `video_url` VARCHAR(255) DEFAULT NULL,
            `testimonio` TEXT,
            `estado` TINYINT(1) DEFAULT 1,
            `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_historia`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->exec($sql_historias);
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_historias'</b> verificada/creada con estructura actualizada.</p>";

    // Parche para agregar la columna 'estado' si la tabla ya existía de una ejecución anterior
    try {
        $sql_alter_historias = "ALTER TABLE san_historias ADD COLUMN estado TINYINT(1) DEFAULT 1";
        $conn->exec($sql_alter_historias);
        echo "<p style='color: #10b981;'>✅ Columna <b>'estado'</b> añadida a 'san_historias' exitosamente.</p>";
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: #F28123;'>⚠️ La columna <b>'estado'</b> ya existe en 'san_historias'.</p>";
        } else {
            throw $e;
        }
    }

    // --- MÓDULOS DE LANDING PAGE ---
    
    // Tabla: san_landing_hero
    $sql_hero = "
        CREATE TABLE IF NOT EXISTS `san_landing_hero` (
            `id_hero` INT(11) NOT NULL AUTO_INCREMENT,
            `subtitulo` VARCHAR(100) NOT NULL,
            `titulo_html` VARCHAR(255) NOT NULL,
            `imagen_bg` VARCHAR(255) NOT NULL,
            `estado` TINYINT(1) DEFAULT 1,
            PRIMARY KEY (`id_hero`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->exec($sql_hero);
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_landing_hero'</b> verificada/creada.</p>";

    // Tabla: san_landing_planes
    $sql_planes = "
        CREATE TABLE IF NOT EXISTS `san_landing_planes` (
            `id_plan` INT(11) NOT NULL AUTO_INCREMENT,
            `nombre` VARCHAR(100) NOT NULL,
            `precio` DECIMAL(10,2) NOT NULL,
            `frecuencia` VARCHAR(100) NOT NULL,
            `beneficios_json` JSON NOT NULL, 
            `url_boton` VARCHAR(255) DEFAULT 'index.php?page=inscribite',
            `estado` TINYINT(1) DEFAULT 1,
            `orden` INT(11) DEFAULT 0,
            PRIMARY KEY (`id_plan`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->exec($sql_planes);
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_landing_planes'</b> verificada/creada.</p>";

    // Tabla: san_landing_galeria
    $sql_galeria = "
        CREATE TABLE IF NOT EXISTS `san_landing_galeria` (
            `id_galeria` INT(11) NOT NULL AUTO_INCREMENT,
            `imagen_url` VARCHAR(255) NOT NULL,
            `es_wide` TINYINT(1) DEFAULT 0,
            `estado` TINYINT(1) DEFAULT 1,
            `orden` INT(11) DEFAULT 0,
            PRIMARY KEY (`id_galeria`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->exec($sql_galeria);
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_landing_galeria'</b> verificada/creada.</p>";

    echo "<br><div style='background-color: #1a1a1a; padding: 20px; border-left: 5px solid #F28123; border-radius: 5px;'>";
    echo "<h3 style='color: #F28123; margin-top: 0;'>⚠️ ALERTA DE SEGURIDAD</h3>";
    echo "<p>Elimina este archivo tras la ejecución.</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<p style='color: #ef4444;'>❌ <b>Error:</b> " . $e->getMessage() . "</p>";
}
echo "</body>";
?>
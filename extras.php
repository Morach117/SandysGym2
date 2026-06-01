<?php
/*
|--------------------------------------------------------------------------
| Instalador Independiente de Base de Datos - Sandys Gym (Landing)
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
echo "<h2 style='color: #F28123;'>⚙️ Instalador de Módulos (Landing Page) - Sandys Gym</h2>";
echo "<hr style='border-color: #333;'>";

try {
    $conn = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color: #10b981;'>✅ Conexión establecida.</p>";

    // 1. Estructura san_landing_config
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `san_landing_config` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `color_bg` VARCHAR(7) NOT NULL DEFAULT '#050505',
            `color_input` VARCHAR(7) NOT NULL DEFAULT '#1a1a1a',
            `color_accent_red` VARCHAR(7) NOT NULL DEFAULT '#ef4444',
            `color_accent_green` VARCHAR(7) NOT NULL DEFAULT '#10b981',
            `color_accent_orange` VARCHAR(7) NOT NULL DEFAULT '#F28123',
            `color_text_muted` VARCHAR(7) NOT NULL DEFAULT '#888888',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $conn->exec("INSERT IGNORE INTO san_landing_config (id, color_bg, color_input, color_accent_red, color_accent_green, color_accent_orange, color_text_muted) VALUES (1, '#050505', '#1a1a1a', '#ef4444', '#10b981', '#F28123', '#888888')");
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_landing_config'</b> creada/verificada.</p>";

    // 2. Estructura san_landing_hero
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `san_landing_hero` (
            `id_hero` INT(11) NOT NULL AUTO_INCREMENT,
            `img_desktop` VARCHAR(255) NOT NULL,
            `img_mobile` VARCHAR(255) NOT NULL,
            `estado` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_hero`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_landing_hero'</b> creada/verificada.</p>";

    // 3. Estructura san_landing_planes
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `san_landing_planes` (
            `id_plan` INT(11) NOT NULL AUTO_INCREMENT,
            `nombre` VARCHAR(100) NOT NULL,
            `precio` DECIMAL(10,2) NOT NULL,
            `frecuencia` VARCHAR(50) NOT NULL,
            `beneficios_json` TEXT NOT NULL,
            `url_boton` VARCHAR(255) NOT NULL,
            `estado` TINYINT(1) NOT NULL DEFAULT 1,
            `orden` INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id_plan`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_landing_planes'</b> creada/verificada.</p>";

    // 4. Estructura san_landing_galeria
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `san_landing_galeria` (
            `id_galeria` INT(11) NOT NULL AUTO_INCREMENT,
            `imagen_url` VARCHAR(255) NOT NULL,
            `es_wide` TINYINT(1) NOT NULL DEFAULT 0,
            `estado` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_galeria`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_landing_galeria'</b> creada/verificada.</p>";

    // 5. Inserción de Mock Data (Solo si las tablas están vacías)
    $hero_count = $conn->query("SELECT COUNT(*) FROM san_landing_hero")->fetchColumn();
    if ($hero_count == 0) {
        $conn->exec("
            INSERT INTO san_landing_hero (img_desktop, img_mobile, estado) VALUES 
            ('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1920&auto=format&fit=crop', 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=800&auto=format&fit=crop', 1),
            ('https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=1920&auto=format&fit=crop', 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=800&auto=format&fit=crop', 1);

            INSERT INTO san_landing_planes (nombre, precio, frecuencia, beneficios_json, url_boton, estado, orden) VALUES 
            ('FIT', 349.00, 'MENSUAL', '[\"Acceso a pesas libres e integrado\",\"Área de cardio equipada\",\"Uso de regaderas y lockers\",\"Seguimiento básico en app\"]', 'index.php?page=inscribite', 1, 1),
            ('BLACK', 549.00, 'MENSUAL', '[\"Todo lo del plan FIT\",\"Acceso a clases grupales\",\"Sillones de masaje\",\"Invita a 5 amigos al mes\",\"Smart Fit App Personalizada\"]', 'index.php?page=inscribite', 1, 2),
            ('VIP ANUAL', 4999.00, 'ANUAL', '[\"Todos los beneficios BLACK\",\"Mensualidad más baja garantizada\",\"Sin cobro de inscripción\",\"Evaluación corporal gratis\"]', 'index.php?page=inscribite', 1, 3);

            INSERT INTO san_landing_galeria (imagen_url, es_wide, estado) VALUES 
            ('https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=800&auto=format&fit=crop', 1, 1),
            ('https://images.unsplash.com/photo-1518611012118-696072aa579a?q=80&w=800&auto=format&fit=crop', 0, 1),
            ('https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=800&auto=format&fit=crop', 0, 1),
            ('https://images.unsplash.com/photo-1594882645126-14020914d58d?q=80&w=800&auto=format&fit=crop', 1, 1),
            ('https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=800&auto=format&fit=crop', 0, 1);
        ");
        echo "<p style='color: #10b981;'>✅ Mock data insertada con éxito.</p>";
    }

    echo "<br><div style='background-color: #1a1a1a; padding: 20px; border-left: 5px solid #ef4444; border-radius: 5px;'>";
    echo "<h3 style='color: #ef4444; margin-top: 0;'>⚠️ ALERTA DE SEGURIDAD</h3>";
    echo "<p>Elimina este archivo tras la ejecución en producción.</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<p style='color: #ef4444;'>❌ <b>Error:</b> " . $e->getMessage() . "</p>";
}
echo "</body>";
?>
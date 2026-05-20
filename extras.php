<?php
/*
|--------------------------------------------------------------------------
| Instalador Independiente de Base de Datos - Sandys Gym (Solo FAQ)
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
echo "<h2 style='color: #ef4444;'>⚙️ Instalador de Módulos (Solo FAQ) - Sandys Gym</h2>";
echo "<hr style='border-color: #333;'>";

try {
    $conn = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color: #10b981;'>✅ Conexión establecida.</p>";

    // Tabla: san_faq (Estructura actualizada)
    $sql_faq = "
        CREATE TABLE IF NOT EXISTS `san_faq` (
            `id_faq` INT(11) NOT NULL AUTO_INCREMENT,
            `pregunta` TEXT NOT NULL,
            `respuesta` TEXT NOT NULL,
            `orden` INT(11) DEFAULT 0,
            `estado` INT(11) DEFAULT 0,
            PRIMARY KEY (`id_faq`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->exec($sql_faq);
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_faq'</b> verificada/creada con estructura actualizada.</p>";

    // Parche para agregar la columna 'estado' si la tabla ya existía de antes
    try {
        $sql_alter_faq = "ALTER TABLE san_faq ADD COLUMN estado INT(11) DEFAULT 0";
        $conn->exec($sql_alter_faq);
        echo "<p style='color: #10b981;'>✅ Columna <b>'estado'</b> añadida a 'san_faq' exitosamente.</p>";
    } catch (PDOException $e) {
        // Si el código de error es que la columna ya existe, lo manejamos limpiamente
        if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: #F28123;'>⚠️ La columna <b>'estado'</b> ya existe en 'san_faq'.</p>";
        } else {
            throw $e;
        }
    }

    echo "<br><div style='background-color: #1a1a1a; padding: 20px; border-left: 5px solid #F28123; border-radius: 5px;'>";
    echo "<h3 style='color: #F28123; margin-top: 0;'>⚠️ ALERTA DE SEGURIDAD</h3>";
    echo "<p>Elimina este archivo tras la ejecución.</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<p style='color: #ef4444;'>❌ <b>Error:</b> " . $e->getMessage() . "</p>";
}
echo "</body>";
?>
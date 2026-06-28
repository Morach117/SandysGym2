<?php
/*
|--------------------------------------------------------------------------
| Instalador Independiente de Base de Datos - Sandys Gym (Landing & Amenidades)
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
echo "<h2 style='color: #F28123;'>⚙️ Instalador de Módulos: Landing Page & Amenidades</h2>";
echo "<hr style='border-color: #333;'>";

try {
    $conn = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color: #10b981;'>✅ Conexión establecida con éxito.</p>";

    // 1. Crear tabla san_landing_amenidades
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `san_landing_amenidades` (
          `id_amenidad` INT(11) NOT NULL AUTO_INCREMENT,
          `icono` VARCHAR(100) NOT NULL,
          `titulo` VARCHAR(100) NOT NULL,
          `descripcion` TEXT NOT NULL,
          `estado` TINYINT(1) DEFAULT '1',
          PRIMARY KEY (`id_amenidad`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "<p style='color: #10b981;'>✅ Tabla <b>'san_landing_amenidades'</b> verificada/creada.</p>";

    // 2. Insertar registros iniciales en san_landing_amenidades (si está vacía)
    $checkAmenidades = $conn->query("SELECT COUNT(*) FROM `san_landing_amenidades`")->fetchColumn();
    if ($checkAmenidades == 0) {
        $conn->exec("
            INSERT INTO `san_landing_amenidades` (`icono`, `titulo`, `descripcion`) VALUES 
            ('fa-solid fa-dumbbell', 'Peso Libre e Integrado', 'Equipamiento moderno para todo tipo de rutinas.'),
            ('fa-solid fa-person-running', 'Zona Cardio', 'Caminadoras, elípticas y escaladoras de última generación.'),
            ('fa-solid fa-people-group', 'Clases Grupales', 'Zumba, Cross, funcional y más actividades guiadas.'),
            ('fa-solid fa-shower', 'Regaderas y Lockers', 'Vestidores amplios, seguros y con agua caliente.');
        ");
        echo "<p style='color: #34d399;'>🔹 Datos iniciales insertados en <b>'san_landing_amenidades'</b>.</p>";
    } else {
        echo "<p style='color: #9ca3af;'>ℹ️ La tabla 'san_landing_amenidades' ya contiene registros. No se duplicaron los datos iniciales.</p>";
    }

    // 3. Alterar la tabla san_landing_config para añadir las nuevas columnas
    // Añadimos validaciones individuales por columna para evitar fallos si el script se corre más de una vez
    $columnasNuevas = [
        'app_titulo'    => "VARCHAR(255) DEFAULT 'Lleva tu entrenamiento'",
        'app_subtitulo' => "VARCHAR(255) DEFAULT 'al siguiente nivel'",
        'app_desc'      => "TEXT DEFAULT 'Al formar parte de la familia Sandys Gym, tienes acceso a un seguimiento continuo.'",
        'app_btn_url'   => "VARCHAR(255) DEFAULT 'index.php?page=login'",
        'app_imagen'    => "VARCHAR(255) DEFAULT ''",
        'cta_titulo'    => "VARCHAR(255) DEFAULT '¿Listo para transformar tu vida?'",
        'cta_desc'      => "VARCHAR(255) DEFAULT 'Visítanos en Tuxtla y comienza hoy mismo.'",
        'cta_btn_url'   => "VARCHAR(255) DEFAULT 'index.php?page=inscribite'"
    ];

    foreach ($columnasNuevas as $columna => $definicion) {
        // Verificar si la columna ya existe en san_landing_config
        $checkCol = $conn->query("SHOW COLUMNS FROM `san_landing_config` LIKE '$columna'")->fetch();
        if (!$checkCol) {
            $conn->exec("ALTER TABLE `san_landing_config` ADD COLUMN `$columna` $definicion;");
            echo "<p style='color: #10b981;'>✅ Columna <b>'$columna'</b> agregada exitosamente.</p>";
        } else {
            echo "<p style='color: #9ca3af;'>ℹ️ La columna '$columna' ya existía en 'san_landing_config'.</p>";
        }
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
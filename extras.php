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

    function addIndexIfNotExists($conn, $table, $indexName, $column) {
        $stmt = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
        if (!$stmt->fetch()) {
            $conn->exec("ALTER TABLE `$table` ADD INDEX `$indexName` ($column)");
            echo "<p style='color: #10b981;'>✅ Índice <b>'$indexName'</b> agregado en '$table'.</p>";
        } else {
            echo "<p style='color: #9ca3af;'>ℹ️ El índice '$indexName' ya existía en '$table'.</p>";
        }
    }

    echo "<h3>Optimizaciones de Base de Datos (Índices)</h3>";
    addIndexIfNotExists($conn, 'san_socios', 'idx_soc_correo', '`soc_correo`');
    addIndexIfNotExists($conn, 'san_socios', 'idx_soc_empresa', '`soc_id_empresa`');
    addIndexIfNotExists($conn, 'san_pagos', 'idx_pag_id_socio', '`pag_id_socio`');
    addIndexIfNotExists($conn, 'san_pagos', 'idx_pag_fecha_pago', '`pag_fecha_pago`');
    addIndexIfNotExists($conn, 'san_mp_pref', 'idx_external_reference', '`external_reference`');
    addIndexIfNotExists($conn, 'san_codigos', 'idx_codigo_generado', '`codigo_generado`, `status`');

    echo "<br><div style='background-color: #1a1a1a; padding: 20px; border-left: 5px solid #ef4444; border-radius: 5px;'>";
    echo "<h3 style='color: #ef4444; margin-top: 0;'>⚠️ ALERTA DE SEGURIDAD</h3>";
    echo "<p>Elimina este archivo de inmediato tras su ejecución exitosa en producción.</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<p style='color: #ef4444;'>❌ <b>Error:</b> " . $e->getMessage() . "</p>";
}
echo "</body>";
?>
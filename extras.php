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

    echo "<br><div style='background-color: #1a1a1a; padding: 20px; border-left: 5px solid #ef4444; border-radius: 5px;'>";
    echo "<h3 style='color: #ef4444; margin-top: 0;'>⚠️ ALERTA DE SEGURIDAD</h3>";
    
    echo "<h3>Creación de Promociones Fijas Masivas</h3>";
    
    $fechaActual = date('Y-m-d');
    $vigenciaFinal = date('Y-m-d', strtotime('+10 years'));
    
    // Promoción Referidos
    $stmtRef = $conn->query("SELECT id_promocion FROM san_promociones WHERE titulo = 'PROMOCION FIJA DE REFERIDOS'");
    if (!$stmtRef->fetch()) {
        $conn->exec("INSERT INTO san_promociones (titulo, fecha_generada, vigencia_inicial, vigencia_final, porcentaje_descuento, utilizado, tipo_promocion) VALUES ('PROMOCION FIJA DE REFERIDOS', '$fechaActual', '$fechaActual', '$vigenciaFinal', 35, 0, 'Masivo')");
        echo "<p style='color: #10b981;'>✅ Promoción <b>'PROMOCION FIJA DE REFERIDOS'</b> creada exitosamente.</p>";
    } else {
        echo "<p style='color: #9ca3af;'>ℹ️ La promoción 'PROMOCION FIJA DE REFERIDOS' ya existía.</p>";
    }

    // Promoción Reactivación
    $stmtReact = $conn->query("SELECT id_promocion FROM san_promociones WHERE titulo = 'PROMOCION FIJA DE REACTIVACION'");
    if (!$stmtReact->fetch()) {
        $conn->exec("INSERT INTO san_promociones (titulo, fecha_generada, vigencia_inicial, vigencia_final, porcentaje_descuento, utilizado, tipo_promocion) VALUES ('PROMOCION FIJA DE REACTIVACION', '$fechaActual', '$fechaActual', '$vigenciaFinal', 35, 0, 'Masivo')");
        echo "<p style='color: #10b981;'>✅ Promoción <b>'PROMOCION FIJA DE REACTIVACION'</b> creada exitosamente.</p>";
    } else {
        echo "<p style='color: #9ca3af;'>ℹ️ La promoción 'PROMOCION FIJA DE REACTIVACION' ya existía.</p>";
    }
    echo "<p>Elimina este archivo de inmediato tras su ejecución exitosa en producción.</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<p style='color: #ef4444;'>❌ <b>Error:</b> " . $e->getMessage() . "</p>";
}
echo "</body>";
?>
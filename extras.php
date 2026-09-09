<?php
/*
|--------------------------------------------------------------------------
| Archivo de Pruebas y Consultas Rápidas
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
echo "<h2 style='color: #F28123;'>🔍 Pruebas de Base de Datos</h2>";
echo "<hr style='border-color: #333;'>";

try {
    $conn = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color: #10b981;'>✅ Conexión establecida con éxito a la base de datos: <b>$db</b></p>";

    echo "<div style='background-color: #1a1a1a; padding: 20px; border-left: 5px solid #3b82f6; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3 style='color: #3b82f6; margin-top: 0;'>Resultados de prueba</h3>";
    
    // --- CONSULTAS DE PRUEBA ---
    
    // 1. Verificar el socio que estaba causando problemas de duplicidad (por teléfono)
    $telefono_prueba = '9611757480';
    $stmt = $conn->query("SELECT soc_id_socio, soc_nombres, soc_apepat, soc_apemat, soc_tel_cel, soc_id_empresa FROM san_socios WHERE soc_tel_cel = '$telefono_prueba'");
    $socios = $stmt->fetchAll();
    
    echo "<h4>Búsqueda por teléfono ($telefono_prueba):</h4>";
    if (count($socios) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; border-color: #555; width: 100%;'>";
        echo "<tr style='background-color: #333;'><th>ID Socio</th><th>Nombres</th><th>Ape. Paterno</th><th>Ape. Materno</th><th>Teléfono</th><th>ID Empresa (Sucursal)</th></tr>";
        foreach ($socios as $s) {
            echo "<tr>
                    <td>{$s['soc_id_socio']}</td>
                    <td>{$s['soc_nombres']}</td>
                    <td>{$s['soc_apepat']}</td>
                    <td>{$s['soc_apemat']}</td>
                    <td>{$s['soc_tel_cel']}</td>
                    <td><b>{$s['soc_id_empresa']}</b></td>
                  </tr>";
        }
        echo "</table>";
        echo "<p style='color: #9ca3af; font-size: 0.9em;'>* Nota: Aquí puedes confirmar a qué ID de empresa pertenece el registro original, lo que explica por qué antes bloqueaba el registro en otras empresas.</p>";
    } else {
        echo "<p>No se encontró ningún socio con el teléfono $telefono_prueba.</p>";
    }

    echo "</div>";

} catch (PDOException $e) {
    echo "<p style='color: #ef4444;'>❌ <b>Error:</b> " . $e->getMessage() . "</p>";
}
echo "</body>";
?>
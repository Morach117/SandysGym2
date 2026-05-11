<?php
// Deshabilitar salida de errores HTML que corrompan el JSON
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

// Ajusta la ruta a tu archivo conn.php según tu estructura real.
// Si conn.php está en la misma carpeta que este archivo:
require_once("conn.php"); 
// Si conn.php está una carpeta atrás (en /gym/): require_once("../conn.php");

try {
    if (!isset($conn)) {
        throw new Exception("Variable PDO \$conn no encontrada.");
    }

    $stmtH = $conn->query("SELECT subtitulo, titulo_html, imagen_bg FROM san_landing_hero WHERE estado = 1");
    $heroes = $stmtH->fetchAll(PDO::FETCH_ASSOC);

    $stmtP = $conn->query("SELECT nombre, precio, frecuencia, beneficios_json, url_boton FROM san_landing_planes WHERE estado = 1 ORDER BY orden ASC, precio ASC");
    $planes = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($planes as &$plan) {
        $plan['beneficios'] = json_decode($plan['beneficios_json'], true) ?: [];
        unset($plan['beneficios_json']);
    }

    $stmtG = $conn->query("SELECT imagen_url, es_wide FROM san_landing_galeria WHERE estado = 1 ORDER BY id_galeria DESC");
    $galeria = $stmtG->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'exito' => true,
        'data' => [
            'hero' => $heroes,
            'planes' => $planes,
            'galeria' => $galeria
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()]);
}
exit;
?>
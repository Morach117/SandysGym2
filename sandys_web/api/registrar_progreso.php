<?php
declare(strict_types=1);

// api/registrar_progreso.php
session_start();
date_default_timezone_set('America/Mexico_City');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Validación estricta de sesión pública (Frontend)
if (empty($_SESSION['admin']['soc_id_socio'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida o expirada.']);
    exit;
}

require_once __DIR__ . '/../conn.php'; // Conexión PDO ($conn)

// Captura de payload JSON nativo (Fetch API)
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !$input) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payload JSON malformado.']);
    exit;
}

$id_socio     = (int)$_SESSION['admin']['soc_id_socio'];
$id_ejercicio = filter_var($input['id_ejercicio'] ?? 0, FILTER_VALIDATE_INT);
$peso_kg      = filter_var($input['peso'] ?? false, FILTER_VALIDATE_FLOAT);
$reps         = filter_var($input['reps'] ?? 0, FILTER_VALIDATE_INT);

// $peso_kg permite 0 (ejercicios de peso corporal), pero requiere validación estricta de tipo
if (!$id_ejercicio || $peso_kg === false || $peso_kg < 0 || !$reps || $reps <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Métricas de rendimiento inválidas.']);
    exit;
}

try {
    $sql = "INSERT INTO san_rutinas_progreso 
            (pro_id_socio, pro_id_ejercicio, pro_peso_kg, pro_repeticiones, pro_series, pro_fecha) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    // Se registra 1 serie por cada disparo del Webhook AJAX
    $stmt->execute([
        $id_socio,
        $id_ejercicio,
        $peso_kg,
        $reps,
        1, 
        date('Y-m-d H:i:s')
    ]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    error_log("PDOException [registrar_progreso]: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Fallo en la persistencia de datos.']);
}
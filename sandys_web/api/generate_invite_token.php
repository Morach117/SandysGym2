<?php
// api/generate_invite_token.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['soc_id_socio'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

$idTitular = $_SESSION['admin']['soc_id_socio'];

try {
    // Generar un token único de 32 bytes (64 caracteres hex)
    $token = bin2hex(random_bytes(32));
    
    // Caducidad de 48 horas
    $fecha_creacion = date('Y-m-d H:i:s');
    $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+48 hours'));
    
    // Invalidar tokens previos pendientes del mismo titular (opcional, pero buena práctica si solo queremos 1 link activo por slot, aquí no lo hacemos porque puede invitar a varios)
    // $stmtDelete = $conn->prepare("UPDATE san_plan_invitaciones SET status = 'expirado' WHERE id_socio_titular = ? AND status = 'pendiente'");
    // $stmtDelete->execute([$idTitular]);

    $stmtInsert = $conn->prepare("
        INSERT INTO san_plan_invitaciones (id_socio_titular, token_unico, status, fecha_creacion, fecha_expiracion) 
        VALUES (?, ?, 'pendiente', ?, ?)
    ");
    
    $stmtInsert->execute([$idTitular, $token, $fecha_creacion, $fecha_expiracion]);
    
    $link = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?page=accept_invite&token=" . $token;
    
    echo json_encode(['success' => true, 'link' => $link, 'token' => $token]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar el token: ' . $e->getMessage()]);
}
?>

<?php
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
    $token = bin2hex(random_bytes(32));
    $fecha_creacion = date('Y-m-d H:i:s');
    $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+48 hours'));

    $stmtInsert = $conn->prepare("
        INSERT INTO san_plan_invitaciones (id_socio_titular, token_unico, status, fecha_creacion, fecha_expiracion) 
        VALUES (?, ?, 'pendiente', ?, ?)
    ");
    
    $stmtInsert->execute([$idTitular, $token, $fecha_creacion, $fecha_expiracion]);
    
    $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $link = $protocol . $_SERVER['HTTP_HOST'] . "/index.php?page=accept_invite&token=" . $token;
    
    echo json_encode(['success' => true, 'link' => $link, 'token' => $token]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar el token.']);
}
?>

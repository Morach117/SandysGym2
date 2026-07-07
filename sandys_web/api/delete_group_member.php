<?php
// api/delete_group_member.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../conn.php';
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['soc_id_socio'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

$idTitular = $_SESSION['admin']['soc_id_socio'];
$idBeneficiario = $_POST['id_beneficiario'] ?? 0;

if (!$idBeneficiario) {
    echo json_encode(['success' => false, 'message' => 'Falta el ID del beneficiario.']);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Verificar que el beneficiario pertenece a este titular
    $stmtCheck = $conn->prepare("SELECT soc_id_socio, soc_nombres FROM san_socios WHERE soc_id_socio = ? AND soc_id_titular_grupo = ? FOR UPDATE");
    $stmtCheck->execute([$idBeneficiario, $idTitular]);
    $beneficiario = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$beneficiario) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'El beneficiario no pertenece a tu plan familiar.']);
        exit;
    }

    // 2. Desvincular al beneficiario
    $stmtUnlink = $conn->prepare("UPDATE san_socios SET soc_id_titular_grupo = 0 WHERE soc_id_socio = ?");
    $stmtUnlink->execute([$idBeneficiario]);

    // 3. Cancelar su pago actual (poniendo la fecha de fin a hoy o status = 'C')
    $stmtCancelPago = $conn->prepare("
        UPDATE san_pagos 
        SET pag_status = 'C', pag_fecha_fin = CURDATE() 
        WHERE pag_id_socio = ? AND pag_status = 'A' AND pag_id_servicio IN (125, 126, 127)
    ");
    $stmtCancelPago->execute([$idBeneficiario]);

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Miembro eliminado con éxito.']);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'ERROR SQL: ' . $e->getMessage()]);
}
?>

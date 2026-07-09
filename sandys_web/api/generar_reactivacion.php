<?php
// api/generar_reactivacion.php
require_once '../conn.php';

header('Content-Type: application/json');
$idSocioPost = (int)($_POST['id_socio'] ?? 0);

if ($idSocioPost <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de socio inválido.']);
    exit;
}

try {
    $conn->beginTransaction();
    
    $tituloPromo = "REACTIVACION-" . $idSocioPost;
    
    $stmtVal = $conn->prepare("SELECT id_promocion FROM san_promociones WHERE titulo = ? LIMIT 1");
    $stmtVal->execute([$tituloPromo]);
    if ($stmtVal->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya has generado tu cupón de reactivación.']);
        $conn->rollBack();
        exit;
    }
    
    $stmtPago = $conn->prepare("SELECT pag_fecha_fin FROM san_pagos WHERE pag_id_socio = ? AND pag_status = 'A' ORDER BY pag_fecha_fin DESC LIMIT 1");
    $stmtPago->execute([$idSocioPost]);
    $pago = $stmtPago->fetch(PDO::FETCH_ASSOC);
    
    if (!$pago) {
        echo json_encode(['success' => false, 'message' => 'No se encontró historial de membresía.']);
        $conn->rollBack();
        exit;
    }
    
    $currentDate = new DateTime();
    $currentDate->setTime(0, 0, 0);
    $fechaFinDate = new DateTime($pago['pag_fecha_fin']);
    $fechaFinDate->setTime(0, 0, 0);
    
    if ($currentDate <= $fechaFinDate || $fechaFinDate->diff($currentDate)->days <= 30) {
        echo json_encode(['success' => false, 'message' => 'Aún no cumples con los 30 días de vencimiento.']);
        $conn->rollBack();
        exit;
    }
    
    $fechaActual = date('Y-m-d');
    $vigenciaFinal = date('Y-m-d', strtotime($fechaActual . ' + 15 days'));
    
    $stmtPromo = $conn->prepare("INSERT INTO san_promociones (titulo, fecha_generada, vigencia_inicial, vigencia_final, porcentaje_descuento, utilizado, tipo_promocion, fecha_creacion) VALUES (?, ?, ?, ?, 35, '0', 'Individual', ?)");
    $stmtPromo->execute([$tituloPromo, $fechaActual, $fechaActual, $vigenciaFinal, $fechaActual]);
    
    $idPromocion = $conn->lastInsertId();
    
    $stmtDesc = $conn->prepare("INSERT INTO san_descuentos_promociones (id_promocion, id_servicio) VALUES (?, '1-1')");
    $stmtDesc->execute([$idPromocion]);
    
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $codigoFinal = 'VUELVE' . $idSocioPost . '-';
    for ($i = 0; $i < 4; $i++) {
        $codigoFinal .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    $stmtCodigo = $conn->prepare("INSERT INTO san_codigos (codigo_generado, id_promocion, status, id_socio) VALUES (?, ?, 1, ?)");
    $stmtCodigo->execute([$codigoFinal, $idPromocion, $idSocioPost]);
    
    $conn->commit();
    echo json_encode(['success' => true, 'codigo' => $codigoFinal, 'message' => 'Cupón generado exitosamente.']);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error al generar cupón: ' . $e->getMessage()]);
}
exit;
?>

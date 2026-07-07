<?php
// api/join_plan_session.php

if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('America/Mexico_City'); 

require_once __DIR__ . '/../conn.php';
header('Content-Type: application/json');

// Validar sesión
if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['soc_id_socio'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
    exit;
}

$idUsuarioActual     = (int)$_SESSION['admin']['soc_id_socio'];
$id_empresa          = (int)($_SESSION['admin']['id_empresa']   ?? 1);
$id_usuario_logueado = (int)($_SESSION['admin']['id_usuario']   ?? 1);

$token = $_POST['token'] ?? '';
$action = $_POST['action'] ?? '';

if (empty($token) || !$action) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos de la invitación.']);
    exit;
}

try {
    // 0. Validar Token
    $qToken = "SELECT id_invitacion, id_socio_titular FROM san_plan_invitaciones WHERE token_unico = ? AND status = 'pendiente' AND fecha_expiracion >= NOW()";
    $stmtToken = $conn->prepare($qToken);
    $stmtToken->execute([$token]);
    $invitacion = $stmtToken->fetch(PDO::FETCH_ASSOC);

    if (!$invitacion) {
        echo json_encode(['success' => false, 'message' => 'El enlace de invitación es inválido o ha expirado.']);
        exit;
    }

    $hostId = (int)$invitacion['id_socio_titular'];
    $idInvitacion = (int)$invitacion['id_invitacion'];

    // ACCIÓN 1: Verificar el nombre del anfitrión
    if ($action === 'check') {
        $q = "SELECT soc_nombres FROM san_socios WHERE soc_id_socio = ?";
        $stmt = $conn->prepare($q);
        $stmt->execute([$hostId]);
        $host = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($host) {
            $nombreCorto = explode(' ', trim($host['soc_nombres']))[0];
            echo json_encode(['success' => true, 'host_name' => $nombreCorto]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Anfitrión no encontrado.']);
        }
        exit;
    }

    // ACCIÓN 2: Confirmar Vinculación y Asignar Servicio de Integrante
    if ($action === 'confirm') {
        $conn->beginTransaction();

        // Buscamos el plan ACTIVO del Titular con LOCK FOR UPDATE para evitar Race Conditions
        $qPlan = "SELECT pag_id_servicio, pag_fecha_ini, pag_fecha_fin, pag_id_empresa 
                  FROM san_pagos 
                  WHERE pag_id_socio = ? AND pag_status = 'A' AND pag_fecha_fin >= CURDATE() 
                  ORDER BY pag_id_pago DESC LIMIT 1 FOR UPDATE";
        $stmtPlan = $conn->prepare($qPlan);
        $stmtPlan->execute([$hostId]);
        $planTitular = $stmtPlan->fetch(PDO::FETCH_ASSOC);

        if (!$planTitular) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'El anfitrión no tiene un plan activo en este momento.']);
            exit;
        }

        $id_servicio_titular = (int)$planTitular['pag_id_servicio'];
        $fechaFinTitular = $planTitular['pag_fecha_fin'];

        // 1. Limpieza de beneficiarios de ciclos anteriores (Spotify Model)
        // Desvincular usuarios cuyo ciclo no coincida con la fecha de fin actual del Titular
        $qLimpiar = "SELECT soc_id_socio FROM san_socios WHERE soc_id_titular_grupo = ?";
        $stmtLimpiar = $conn->prepare($qLimpiar);
        $stmtLimpiar->execute([$hostId]);
        $posibles = $stmtLimpiar->fetchAll(PDO::FETCH_ASSOC);

        foreach ($posibles as $p) {
            $qCheckBen = $conn->prepare("
                SELECT pag_fecha_fin 
                FROM san_pagos 
                WHERE pag_id_socio = ? AND pag_status = 'A' AND pag_id_servicio IN (125, 126, 127)
                ORDER BY pag_fecha_fin DESC LIMIT 1
            ");
            $qCheckBen->execute([$p['soc_id_socio']]);
            $pBen = $qCheckBen->fetch(PDO::FETCH_ASSOC);
            
            if (!$pBen || $pBen['pag_fecha_fin'] < date('Y-m-d') || $pBen['pag_fecha_fin'] != $fechaFinTitular) {
                $conn->prepare("UPDATE san_socios SET soc_id_titular_grupo = 0 WHERE soc_id_socio = ?")
                     ->execute([$p['soc_id_socio']]);
            }
        }
        
        // Contamos cuántos beneficiarios tiene actualmente el titular (post-limpieza)
        $qCount = "SELECT COUNT(soc_id_socio) FROM san_socios WHERE soc_id_titular_grupo = ?";
        $stmtCount = $conn->prepare($qCount);
        $stmtCount->execute([$hostId]);
        $numBeneficiarios = (int)$stmtCount->fetchColumn();

        // 🧠 LÓGICA DE ASIGNACIÓN DE SERVICIO PARA EL INVITADO 🧠
        $id_servicio_hijo = 0;

        if ($id_servicio_titular == 123) { // Plan 3 integrantes
            if ($numBeneficiarios == 0) $id_servicio_hijo = 125;      // Integrante 2
            else if ($numBeneficiarios == 1) $id_servicio_hijo = 126; // Integrante 3
            else {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => 'El plan ya está lleno (3/3).']);
                exit;
            }
        } else if ($id_servicio_titular == 124) { // Plan 4 integrantes
            if ($numBeneficiarios == 0) $id_servicio_hijo = 125; 
            else if ($numBeneficiarios == 1) $id_servicio_hijo = 126;
            else if ($numBeneficiarios == 2) $id_servicio_hijo = 127; // Integrante 4
            else {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => 'El plan ya está lleno (4/4).']);
                exit;
            }
        } else {
            // Plan parejas u otro por defecto
            $id_servicio_hijo = 125;
        }

        // 1. Vinculamos al usuario actual
        $qLink = "UPDATE san_socios SET soc_id_titular_grupo = ? WHERE soc_id_socio = ?";
        $stmtLink = $conn->prepare($qLink);
        $stmtLink->execute([$hostId, $idUsuarioActual]);

        // 2. Insertamos el pago del integrante con el ID correcto
        $fecha_mov = date('Y-m-d H:i:s');
        $fecha_ini = $planTitular['pag_fecha_ini'];
        $fecha_fin = $planTitular['pag_fecha_fin'];
        $empresa_pago = $planTitular['pag_id_empresa'] ?: $id_empresa;

        $queryInsertPago = "
            INSERT INTO san_pagos (
                pag_id_socio, pag_fecha_pago, pag_id_servicio, 
                pag_fecha_ini, pag_fecha_fin, pag_efectivo, pag_tarjeta, 
                pag_monedero, pag_importe, pag_tipo_pago, pag_id_usuario, 
                pag_id_empresa, pag_codigo_promocion, pag_status
            ) VALUES (
                ?, ?, ?, 
                ?, ?, 0.00, 0.00, 
                0.00, 0.00, 'E', ?, 
                ?, '', 'A'
            )
        ";
        
        $stmtInsert = $conn->prepare($queryInsertPago);
        $stmtInsert->execute([
            $idUsuarioActual, $fecha_mov, $id_servicio_hijo, 
            $fecha_ini, $fecha_fin, $id_usuario_logueado, $empresa_pago
        ]);

        // 3. Marcar el token como aceptado
        $stmtUpdateToken = $conn->prepare("UPDATE san_plan_invitaciones SET status = 'aceptado' WHERE id_invitacion = ?");
        $stmtUpdateToken->execute([$idInvitacion]);

        $conn->commit();

        setcookie('gym_invite_token', '', time() - 3600, '/');
        unset($_SESSION['gym_invite_token']);

        echo json_encode(['success' => true, 'message' => 'Vinculación exitosa.']);
        exit;
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'ERROR SQL: ' . $e->getMessage()]);
}
?>
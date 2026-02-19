<?php
// api/update_profile_reward.php

// 1. CONFIGURACIÓN
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php'; 

// 2. VERIFICAR MÉTODO
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // ---------------------------------------------------------
    // A. RECIBIR Y LIMPIAR DATOS
    // ---------------------------------------------------------
    $idSocio = isset($_POST['id_socio']) ? intval($_POST['id_socio']) : 0;
    
    // Limpiamos espacios y convertimos a mayúsculas
    $nombres        = strtoupper(trim($_POST['nombres'] ?? ''));
    $apPaterno      = strtoupper(trim($_POST['ap_paterno'] ?? ''));
    $apMaterno      = strtoupper(trim($_POST['ap_materno'] ?? ''));
    $genero         = isset($_POST['genero']) ? $_POST['genero'] : '';
    $mesNacimiento  = isset($_POST['mes_nacimiento']) ? $_POST['mes_nacimiento'] : '';
    $telCel         = trim($_POST['tel_cel'] ?? '');
    
    // Datos de Emergencia
    $emerNombres    = strtoupper(trim($_POST['emer_nombres'] ?? ''));
    $emerTel        = trim($_POST['emer_tel'] ?? '');
    $emerParentesco = strtoupper(trim($_POST['emer_parentesco'] ?? ''));

    if ($idSocio <= 0) throw new Exception("ID de socio inválido.");

    // ---------------------------------------------------------
    // B. VALIDACIÓN ESTRICTA (NUEVO)
    // ---------------------------------------------------------
    // Para ganar la recompensa, TODOS estos campos deben tener valor.
    // Si falta uno solo, esta variable será FALSE.
    $estanTodosLosDatos = (
        !empty($nombres) &&
        !empty($apPaterno) &&
        !empty($genero) &&
        !empty($mesNacimiento) &&
        !empty($telCel) &&
        !empty($emerNombres) &&     // Antes no validaba esto
        !empty($emerTel) &&
        !empty($emerParentesco)     // Antes no validaba esto
    );

    // ---------------------------------------------------------
    // C. CONSULTAR ESTADO ACTUAL EN BD
    // ---------------------------------------------------------
    // Obtenemos los datos actuales para saber si YA estaba completo o si YA se pagó.
    $checkQuery = "SELECT soc_tel_cel, soc_emer_tel, soc_emer_nombres, soc_emer_parentesco, soc_fecha_nacimiento, soc_mon_saldo, perfil_completado_reward 
                   FROM san_socios WHERE soc_id_socio = :id";
    $stmtCheck = $conn->prepare($checkQuery);
    $stmtCheck->bindParam(':id', $idSocio);
    $stmtCheck->execute();
    $datosActuales = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$datosActuales) {
        throw new Exception("No se encontraron datos del socio.");
    }

    // 1. ¿Ya se le pagó antes?
    $yaRecibioRecompensa = (isset($datosActuales['perfil_completado_reward']) && (int)$datosActuales['perfil_completado_reward'] == 1);
    
    // 2. ¿Estaba incompleto en la BD? (Verificamos si faltaba algo importante)
    // Consideramos incompleto si faltaba celular, emergencia o fecha.
    $estabaIncompletoEnBD = (
        empty($datosActuales['soc_tel_cel']) || 
        empty($datosActuales['soc_emer_tel']) || 
        empty($datosActuales['soc_emer_nombres']) || 
        $datosActuales['soc_fecha_nacimiento'] == '0000-00-00'
    );
    
    // 3. DECISIÓN FINAL
    // Damos dinero SI: No ha recibido premio Y estaba incompleto Y ahora envía TODO lleno.
    $darRecompensa = false;
    if (!$yaRecibioRecompensa && $estabaIncompletoEnBD && $estanTodosLosDatos) {
        $darRecompensa = true;
    }

    // ---------------------------------------------------------
    // D. PREPARAR FECHA SQL
    // ---------------------------------------------------------
    $fechaSQL = $datosActuales['soc_fecha_nacimiento'];
    // Solo actualizamos fecha si envían mes nuevo y antes no tenían.
    if (!empty($mesNacimiento) && $fechaSQL == '0000-00-00') {
        $fechaSQL = "2000-" . str_pad($mesNacimiento, 2, "0", STR_PAD_LEFT) . "-01";
    }

    // ---------------------------------------------------------
    // E. TRANSACCIÓN (UPDATE + INSERT)
    // ---------------------------------------------------------
    $conn->beginTransaction();

    // 1. ACTUALIZAR DATOS
    $sql = "UPDATE san_socios SET 
            soc_nombres = :nom, 
            soc_apepat = :apat, 
            soc_apemat = :amat, 
            soc_genero = :gen, 
            soc_fecha_nacimiento = :fnac, 
            soc_tel_cel = :tel, 
            soc_emer_nombres = :enom, 
            soc_emer_tel = :etel, 
            soc_emer_parentesco = :epar";
    
    // Si gana recompensa, marcamos la bandera y sumamos saldo
    if ($darRecompensa) {
        $sql .= ", soc_mon_saldo = soc_mon_saldo + 35, perfil_completado_reward = 1";
    }

    $sql .= " WHERE soc_id_socio = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':nom' => $nombres, ':apat' => $apPaterno, ':amat' => $apMaterno,
        ':gen' => $genero, ':fnac' => $fechaSQL, ':tel' => $telCel,
        ':enom' => $emerNombres, ':etel' => $emerTel, ':epar' => $emerParentesco,
        ':id' => $idSocio
    ]);

    // 2. INSERTAR HISTORIAL (Solo si hay recompensa)
    if ($darRecompensa) {
        $saldoNuevo = $datosActuales['soc_mon_saldo'] + 35;
        $fechaMov = date('Y-m-d H:i:s');
        $idUsuarioSistema = 17; // Usuario sistema

        $sqlMov = "INSERT INTO san_prepago_detalle 
                   (pred_id_socio, pred_fecha, pred_movimiento, pred_importe, pred_saldo, pred_descripcion, pred_id_usuario) 
                   VALUES (:id, :fec, 'A', 35.00, :saldo, 'Bonificación Perfil Completado', :uid)";
        
        $stmtMov = $conn->prepare($sqlMov);
        $stmtMov->execute([
            ':id' => $idSocio, 
            ':fec' => $fechaMov, 
            ':saldo' => $saldoNuevo,
            ':uid' => $idUsuarioSistema
        ]);
    }

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'rewardGiven' => $darRecompensa,
        'mesGuardado' => !empty($mesNacimiento)
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
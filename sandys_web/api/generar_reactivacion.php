<?php
// api/generar_reactivacion.php

// =========================================================================
// CONFIGURACIÓN DE LA PROMOCIÓN FIJA
// =========================================================================
define('TITULO_PROMO_REACTIVACION', 'PROMOCION FIJA DE REACTIVACION');
define('DESCUENTO_REACTIVACION', 35); // Porcentaje de descuento base a otorgar

// 1. INICIAR SESIÓN CON PARÁMETROS SEGUROS
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => isset($_SERVER['HTTPS']), // True si está en HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

header('Content-Type: application/json');

// 2. VALIDAR SESIÓN DE USUARIO
if (!isset($_SESSION['admin']['soc_id_socio'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada o no válida. Por favor, inicia sesión nuevamente.']);
    exit;
}

require_once '../conn.php';

// =========================================================================
// Función para generar el formato exacto de código
// =========================================================================
function generar_codigo_promocion()
{
    $numeros = '0123456789';
    $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $codigo = '';

    for ($i = 0; $i < 2; $i++) { $codigo .= $numeros[rand(0, strlen($numeros) - 1)]; }
    $codigo .= $letras[rand(0, strlen($letras) - 1)];
    for ($i = 0; $i < 2; $i++) { $codigo .= $numeros[rand(0, strlen($numeros) - 1)]; }
    $codigo .= $letras[rand(0, strlen($letras) - 1)];
    for ($i = 0; $i < 2; $i++) { $codigo .= $numeros[rand(0, strlen($numeros) - 1)]; }

    return $codigo;
}

// =========================================================================
// Helper para construir_insert compatible con PDO
// =========================================================================
function construir_insert($tabla, $datos) {
    global $conn, $conexion; // Declaramos global $conexion por si se requiere en el core, pero usamos PDO $conn
    $columnas = implode(", ", array_keys($datos));
    $placeholders = implode(", ", array_fill(0, count($datos), "?"));
    $sql = "INSERT INTO $tabla ($columnas) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array_values($datos));
    return $conn->lastInsertId();
}

// Obtener ID del socio desde la sesión
$idSocioPost = (int)$_SESSION['admin']['soc_id_socio'];

if ($idSocioPost <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de socio inválido.']);
    exit;
}

try {
    $conn->beginTransaction();
    
    // 1. Validar si el usuario YA generó un cupón asociado a esta promoción base
    $stmtVal = $conn->prepare("
        SELECT c.codigo_generado 
        FROM san_codigos c
        INNER JOIN san_promociones p ON c.id_promocion = p.id_promocion
        WHERE c.id_socio = ? AND p.titulo = ? LIMIT 1
    ");
    $stmtVal->execute([$idSocioPost, TITULO_PROMO_REACTIVACION]);
    if ($stmtVal->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya has generado tu cupón de reactivación.']);
        $conn->rollBack();
        exit;
    }
    
    // 2. Verificar si cumple los requisitos (30 días de vencimiento)
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
    
    // 3. Buscar o crear la Promoción Fija Base (Relación 1 a Muchos)
    $stmtPromo = $conn->prepare("SELECT id_promocion FROM san_promociones WHERE titulo = ? LIMIT 1");
    $stmtPromo->execute([TITULO_PROMO_REACTIVACION]);
    $promoBaseId = $stmtPromo->fetchColumn();
    
    if (!$promoBaseId) {
        // Persistencia automatizada por primera vez
        $fechaActual = date('Y-m-d');
        $vigenciaFinal = date('Y-m-d', strtotime('+10 years'));
        
        $datosPromo = [
            'titulo' => TITULO_PROMO_REACTIVACION,
            'fecha_generada' => $fechaActual,
            'vigencia_inicial' => $fechaActual,
            'vigencia_final' => $vigenciaFinal,
            'porcentaje_descuento' => DESCUENTO_REACTIVACION,
            'utilizado' => '0',
            'tipo_promocion' => 'General',
            'fecha_creacion' => $fechaActual
        ];
        
        $promoBaseId = construir_insert('san_promociones', $datosPromo);
        
        // Agregar registro en san_descuentos_promociones asociado a la promoción
        construir_insert('san_descuentos_promociones', [
            'id_promocion' => $promoBaseId,
            'id_servicio' => '1-1'
        ]);
    }
    
    // 4. Generar y validar el código único on-demand
    $codigoFinal = '';
    $codigoUnico = false;
    
    do {
        $codigoGenerado = generar_codigo_promocion();
        
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM san_codigos WHERE codigo_generado = ?");
        $stmtCheck->execute([$codigoGenerado]);
        $existe = $stmtCheck->fetchColumn();
        
        if ($existe == 0) {
            $codigoFinal = $codigoGenerado;
            $codigoUnico = true;
        }
    } while (!$codigoUnico);
    
    // 5. Inserción Única en la tabla san_codigos enlazada a la promo fija
    $datosCodigo = [
        'codigo_generado' => $codigoFinal,
        'id_promocion' => $promoBaseId,
        'status' => 1,
        'id_socio' => $idSocioPost
    ];
    construir_insert('san_codigos', $datosCodigo);
    
    $conn->commit();
    echo json_encode(['success' => true, 'codigo' => $codigoFinal, 'message' => 'Cupón generado exitosamente.']);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error PDO en generar_reactivacion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error de base de datos al procesar la solicitud. Por favor intenta nuevamente.'
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error General en generar_reactivacion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ocurrió un error inesperado al generar el cupón.'
    ]);
}

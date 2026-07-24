<?php
define('TITULO_PROMO_REACTIVACION', 'PROMOCION FIJA DE REACTIVACION');
define('DESCUENTO_REACTIVACION', 35);

$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax'
]);
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin']['soc_id_socio'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada o no válida. Por favor, inicia sesión nuevamente.']);
    exit;
}

require_once '../conn.php';

/**
 * Genera un código de cupón aleatorio con formato alfanumérico
 */
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

/**
 * Inserta un registro en una tabla de forma dinámica usando PDO
 */
function construir_insert($tabla, $datos) {
    global $conn;
    $columnas = implode(", ", array_keys($datos));
    $placeholders = implode(", ", array_fill(0, count($datos), "?"));
    $sql = "INSERT INTO $tabla ($columnas) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array_values($datos));
    return $conn->lastInsertId();
}

$idSocioPost = (int)$_SESSION['admin']['soc_id_socio'];

if ($idSocioPost <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de socio inválido.']);
    exit;
}

try {
    $conn->beginTransaction();
    
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
    
    $stmtPromo = $conn->prepare("SELECT id_promocion FROM san_promociones WHERE titulo = ? LIMIT 1");
    $stmtPromo->execute([TITULO_PROMO_REACTIVACION]);
    $promoBaseId = $stmtPromo->fetchColumn();
    
    if (!$promoBaseId) {
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
        
        construir_insert('san_descuentos_promociones', [
            'id_promocion' => $promoBaseId,
            'id_servicio' => '1-1'
        ]);
    }
    
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
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error PDO en generar_reactivacion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error de base de datos al procesar la solicitud. Por favor intenta nuevamente.'
    ]);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error General en generar_reactivacion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ocurrió un error inesperado al generar el cupón.'
    ]);
}
?>

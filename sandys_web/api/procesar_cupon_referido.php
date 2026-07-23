<?php
define('TITULO_PROMO_REFERIDOS', 'PROMOCION FIJA DE REFERIDOS');
define('DESCUENTO_REFERIDOS', 35);

$domain = isset($_SERVER['HTTP_HOST']) ? preg_replace('/^www\./', '', explode(':', $_SERVER['HTTP_HOST'])[0]) : '';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $domain,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

if (!isset($_SESSION['admin']['soc_id_socio'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada o no válida. Por favor, inicia sesión nuevamente.']);
    exit;
}

require_once __DIR__ . '/../conn.php'; 

header('Content-Type: application/json');

/**
 * Retorna una respuesta JSON y termina la ejecución
 */
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Genera un código de cupón alfanumérico aleatorio
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
 * Inserta dinámicamente un registro usando PDO
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_cupon'])) {
    
    $idSocio = (int)$_SESSION['admin']['soc_id_socio'];

    try {
        $conn->beginTransaction();

        $stmtVal = $conn->prepare("SELECT soc_nombres FROM san_socios WHERE soc_id_socio = ?");
        $stmtVal->execute([$idSocio]);
        $validacion = $stmtVal->fetch(PDO::FETCH_ASSOC);

        if (!$validacion) {
            $conn->rollBack();
            json_response(['success' => false, 'message' => 'Socio no válido.'], 400);
        }
            
        $stmtPromo = $conn->prepare("SELECT id_promocion FROM san_promociones WHERE titulo = ? LIMIT 1");
        $stmtPromo->execute([TITULO_PROMO_REFERIDOS]);
        $promoBaseId = $stmtPromo->fetchColumn();

        if (!$promoBaseId) {
            $fechaActual = date('Y-m-d');
            $vigenciaFinal = date('Y-m-d', strtotime('+10 years'));
            
            $datosPromo = [
                'titulo' => TITULO_PROMO_REFERIDOS,
                'fecha_generada' => $fechaActual,
                'vigencia_inicial' => $fechaActual,
                'vigencia_final' => $vigenciaFinal,
                'porcentaje_descuento' => DESCUENTO_REFERIDOS,
                'utilizado' => '0',
                'tipo_promocion' => 'General',
                'fecha_creacion' => $fechaActual
            ];
            
            $promoBaseId = construir_insert('san_promociones', $datosPromo);
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
            'id_socio' => $idSocio
        ];
        construir_insert('san_codigos', $datosCodigo);

        $conn->commit();
        
        json_response([
            'success' => true,
            'codigo' => $codigoFinal,
            'message' => 'Cupón generado exitosamente.'
        ]);

    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error generando cupón de referido: " . $e->getMessage());
        json_response(['success' => false, 'message' => 'Error de sistema al generar cupón.'], 500);
    }
} else {
    json_response(['success' => false, 'message' => 'Petición inválida.'], 400);
}
?>
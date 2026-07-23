<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = md5((string)mt_rand());
    }
}
date_default_timezone_set('America/Mexico_City');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conn.php';           
require_once __DIR__ . '/config.php';            

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;

MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

if (!defined('MP_PROCESSOR_LOG_FILE')) {
    define('MP_PROCESSOR_LOG_FILE', __DIR__ . '/../logs/procesar_pago.log');
}

/**
 * Escribe un mensaje en el archivo de registro de pagos
 */
function log_processor(string $message): void {
    $ts = date("Y-m-d H:i:s"); 
    @file_put_contents(MP_PROCESSOR_LOG_FILE, "[$ts] [MEMBRESIA] $message" . PHP_EOL, FILE_APPEND);
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$origin = $_SERVER['HTTP_ORIGIN'] ?? 'https://sandysgym.com';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Consulta la información del socio en la base de datos
 */
function obtener_datos_socio_pdo(PDO $conexion_pdo, int $id_socio, int $id_empresa) {
    $q = "SELECT * FROM san_socios WHERE soc_id_socio = ? AND soc_id_empresa = ? LIMIT 1";
    $st = $conexion_pdo->prepare($q);
    $st->execute([$id_socio, $id_empresa]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obtiene los detalles de un servicio
 */
function obtener_servicio_pdo(PDO $conexion_pdo, int $id_servicio, int $id_consorcio, int $id_giro) {
    $q = "SELECT ser_id_servicio AS id_servicio, ser_clave AS clave, ser_descripcion AS descripcion, ROUND(ser_cuota, 2) AS cuota, ser_meses AS meses 
          FROM san_servicios WHERE ser_id_servicio = ? AND ser_id_consorcio = ? AND ser_id_giro = ? AND ser_status <> 'D' LIMIT 1";
    $st = $conexion_pdo->prepare($q);
    $st->execute([$id_servicio, $id_consorcio, $id_giro]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

/**
 * Valida si un servicio es elegible para descuentos promocionales
 */
function verificar_descuentos_promocionales_pdo(PDO $conexion_pdo, int $id_servicio) {
    if (in_array($id_servicio, [125, 126], true)) return true;
    $q = "SELECT 1 FROM san_descuentos_promociones WHERE id_servicio = ? LIMIT 1";
    $st = $conexion_pdo->prepare($q);
    $st->execute([$id_servicio]);
    return $st->fetch(PDO::FETCH_NUM) !== false;
}

/**
 * Valida el código de descuento en la base de datos
 */
function validar_codigo_promo_pdo(PDO $conexion_pdo, string $codigo_promocion) {
    $q = "SELECT p.porcentaje_descuento, p.tipo_promocion FROM san_codigos c 
          INNER JOIN san_promociones p ON c.id_promocion = p.id_promocion 
          WHERE c.codigo_generado = ? AND c.status = '1' AND p.vigencia_inicial <= CURDATE() AND p.vigencia_final >= CURDATE() LIMIT 1";
    $st = $conexion_pdo->prepare($q);
    $st->execute([$codigo_promocion]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $jsonPayload = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonPayload)) {
        $_POST = $jsonPayload;
    }
}

if (empty($_SESSION['admin']['soc_id_socio'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida o expirada.']);
    exit;
}

$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    log_processor("Bloqueo: CSRF inválido. Socio ID {$_SESSION['admin']['soc_id_socio']}");
    echo json_encode(['status' => 'error', 'message' => 'Token de seguridad inválido. Recarga la página.']);
    exit;
}

$id_socio_pagador = (int)$_SESSION['admin']['soc_id_socio'];
$id_empresa   = (int)($_SESSION['admin']['id_empresa']   ?? 1);
$id_consorcio = (int)($_SESSION['admin']['id_consorcio'] ?? 1);
$id_giro      = (int)($_SESSION['admin']['id_giro']      ?? 1);
$id_usuario_logueado = (int)($_SESSION['admin']['id_usuario'] ?? 99);

$servicio_id_str  = filter_input(INPUT_POST, 'servicio', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''; 
$codigo_promocion = filter_input(INPUT_POST, 'codigo_promocion', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$id_socio_benef   = filter_input(INPUT_POST, 'miembro_a_pagar', FILTER_VALIDATE_INT) ?: $id_socio_pagador;
$accion           = filter_input(INPUT_POST, 'accion', FILTER_SANITIZE_SPECIAL_CHARS) === 'preview' ? 'preview' : 'pagar';

$fecha_ini_pago = $_POST['fecha_inicio'] ?? null;
$fecha_fin_pago = $_POST['fecha_fin'] ?? null;

if ($accion === 'pagar') {
    $dateRegex = '/^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[012])-(20\d{2})$/';
    if (!preg_match($dateRegex, (string)$fecha_ini_pago) || !preg_match($dateRegex, (string)$fecha_fin_pago)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'El formato de las fechas es inválido.']);
        exit;
    }
}

if (defined('BASE_URL_APP') && BASE_URL_APP) {
    $baseUrl = rtrim(BASE_URL_APP, '/');
} else {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $root    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $baseUrl = $scheme . '://' . $host . preg_replace('#/api$#', '', $root);
}

try {
    if (!$servicio_id_str || strpos($servicio_id_str, '-') === false) {
        throw new InvalidArgumentException("Selecciona un servicio válido.");
    }
    
    $parts = explode('-', $servicio_id_str, 2);
    $id_servicio_num = (int)$parts[0];

    $servicio = obtener_servicio_pdo($conn, $id_servicio_num, $id_consorcio, $id_giro);
    if (!$servicio) throw new InvalidArgumentException("El servicio seleccionado no está disponible.");

    $subtotal = (float)$servicio['cuota'];
    $total    = $subtotal;

    $benef = obtener_datos_socio_pdo($conn, $id_socio_benef, $id_empresa);
    if (!$benef) throw new InvalidArgumentException("No fue posible cargar el socio beneficiario.");

    $porc_total = 0.0;
    $desc_texto = '';
    $promo_info = null;
    $es_cumple = false;
    
    if (!empty($benef['soc_fecha_nacimiento'])) {
        $fn = new DateTime($benef['soc_fecha_nacimiento']);
        $hoy = new DateTime();
        if ($fn->format('m') === $hoy->format('m')) $es_cumple = true;
    }

    $desc_socio = (float)($benef['soc_descuento'] ?? 0);
    if ($desc_socio > 0 && !$es_cumple) {
        $porc_total += $desc_socio;
        $desc_texto  = "{$desc_socio}% Socio";
    }

    $codigo_a_validar   = $codigo_promocion;
    $es_codigo_cumple   = false;
    if ($es_cumple) {
        $codigo_a_validar = "11d11l12";
        $es_codigo_cumple = true;
    }
    
    if ($codigo_a_validar !== '') {
        if (!verificar_descuentos_promocionales_pdo($conn, $id_servicio_num) && !$es_codigo_cumple) {
            throw new InvalidArgumentException("El servicio seleccionado no permite códigos promocionales.");
        }
        $promo_info = validar_codigo_promo_pdo($conn, $codigo_a_validar);
        if ($promo_info) {
            $porc_promo = (float)$promo_info['porcentaje_descuento'];
            if ($es_codigo_cumple) {
                $porc_total  = $porc_promo;
                $desc_texto  = "Descuento Cumpleaños";
            } else {
                $porc_total += $porc_promo;
                $desc_texto  = $desc_texto ? $desc_texto . " + {$porc_promo}% Promo" : "{$porc_promo}% Promo";
            }
        } else {
            throw new InvalidArgumentException($es_codigo_cumple ? "Código de cumpleaños expirado." : "Código de promoción inválido.");
        }
    }

    $desc_monto = round($subtotal * ($porc_total / 100), 2);
    $total      = round($subtotal - $desc_monto, 2);

    if ($accion === 'preview') {
        echo json_encode([
            'status'            => 'success',
            'subtotal'          => (float)$subtotal,
            'descuento_monto'   => (float)$desc_monto,
            'total'             => (float)$total,
            'descuento_nombre'  => $desc_texto !== '' ? $desc_texto : 'N/A'
        ]);
        exit;
    }

    $external_reference = 'SOCIO_' . $id_socio_benef . '_' . time();

    $item = [
        'title'       => $servicio['descripcion'],
        'quantity'    => 1,
        'unit_price'  => (float)$total,
        'currency_id' => 'MXN',
    ];

    $metadata = [
        'tipo_operacion'        => 'membresia',
        'id_socio_pagador'      => $id_socio_pagador,
        'id_socio_beneficiario' => $id_socio_benef,
        'id_servicio'           => $id_servicio_num,
        'fecha_ini'             => $fecha_ini_pago,
        'fecha_fin'             => $fecha_fin_pago,
        'codigo_usado'          => $promo_info ? $codigo_a_validar : null,
        'subtotal'              => (float)$subtotal,
        'monto_pagado'          => (float)$total,
        'descuento_aplicado'    => (float)$desc_monto,
        'tipo_promo'            => $promo_info['tipo_promocion'] ?? null,
        'id_empresa'            => $id_empresa,
        'id_usuario'            => $id_usuario_logueado
    ];

    $request = [
        'items'              => [$item],
        'back_urls'          => [
            'success' => $baseUrl . "/index.php?page=gracias",
            'failure' => $baseUrl . "/index.php?page=gracias",
            'pending' => $baseUrl . "/index.php?page=gracias",
        ],
        'auto_return'        => 'approved',
        'external_reference' => $external_reference,
        'notification_url'   => $baseUrl . "/api/webhook_mercadopago.php",
        'metadata'           => $metadata,
    ];

    $client     = new PreferenceClient();
    $preference = $client->create($request);

    $sql = "INSERT INTO san_mp_pref (pref_id, external_reference, metadata_json, created_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE metadata_json = VALUES(metadata_json)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $preference->id,
        $external_reference,
        json_encode($metadata, JSON_UNESCAPED_UNICODE),
        date("Y-m-d H:i:s")
    ]);

    echo json_encode(['status' => 'success', 'url' => $preference->init_point]);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (MPApiException $e) {
    $apiResponse = $e->getApiResponse();
    $statusCode = method_exists($apiResponse, 'getStatusCode') ? $apiResponse->getStatusCode() : 0;
    $content = method_exists($apiResponse, 'getContent') ? json_encode($apiResponse->getContent()) : 'N/A';
    log_processor("MPApiException: HTTP={$statusCode} | Body={$content}");
    http_response_code(502);
    
    $userMsg = 'Fallo en la comunicación con Mercado Pago.';
    if ($statusCode === 401 || $statusCode === 403) {
        $userMsg = 'Error de autorización: Verifica que el Access Token de la cuenta sea correcto y tenga permisos.';
    } elseif ($statusCode === 400) {
        $userMsg = 'Error en los datos de la preferencia enviados a Mercado Pago. Verifica la configuración de la cuenta.';
    }
    
    echo json_encode(['status' => 'error', 'message' => $userMsg]);
} catch (PDOException $e) {
    log_processor("PDOException: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error interno en la base de datos.']);
} catch (Exception $e) {
    log_processor("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error inesperado procesando la solicitud.']);
}
?>
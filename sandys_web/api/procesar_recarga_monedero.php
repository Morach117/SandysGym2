<?php
declare(strict_types=1);

// api/procesar_recarga_monedero.php
// --- 1. INICIAR SESIÓN (SOLO SI NO ESTÁ ACTIVA) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1.1 GENERAR CSRF TOKEN ---
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));
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

function log_processor(string $message): void {
    $ts = date("Y-m-d H:i:s"); 
    @file_put_contents(MP_PROCESSOR_LOG_FILE, "[$ts] [MONEDERO] $message" . PHP_EOL, FILE_APPEND);
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// 1. Validación estricta de Sesión
if (empty($_SESSION['admin']['soc_id_socio'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida o expirada.']);
    exit;
}

// 2. Validación CSRF (Requiere que el Front envíe el token en la cabecera o POST)
$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    log_processor("Bloqueo de seguridad: Intento de CSRF detectado para el socio ID {$_SESSION['admin']['soc_id_socio']}");
    echo json_encode(['status' => 'error', 'message' => 'Token de seguridad inválido. Recarga la página.']);
    exit;
}

try {
    // 3. Mitigación IDOR: El ID del socio SIEMPRE se toma de la sesión confiable, NUNCA del POST
    $id_socio = (int)$_SESSION['admin']['soc_id_socio'];
    
    // Sanitización y validación del importe
    $importe_recarga = filter_input(INPUT_POST, 'importe', FILTER_VALIDATE_FLOAT);
    
    $id_empresa = (int)($_SESSION['admin']['id_empresa'] ?? 1);
    $id_usuario = (int)($_SESSION['admin']['id_usuario'] ?? 99);
    $id_consorcio = (int)($_SESSION['admin']['id_consorcio'] ?? 1);

    // Validación de límites lógicos para prevenir overflows o recargas nulas
    if ($importe_recarga === false || $importe_recarga < 50.00 || $importe_recarga > 20000.00) {
        throw new InvalidArgumentException("El importe es inválido. Debe ser entre $50.00 y $20,000.00 MXN.");
    }

    // 4. Obtener promoción del consorcio (Uso estricto de PDO)
    $stmt = $conn->prepare("SELECT con_abono FROM san_consorcios WHERE con_id_consorcio = ? LIMIT 1");
    $stmt->execute([$id_consorcio]);
    $consorcio = $stmt->fetch(PDO::FETCH_ASSOC);
    $porcentaje_incremento = $consorcio ? (float)$consorcio['con_abono'] : 10.0;

    $incremento_monto = round($importe_recarga * ($porcentaje_incremento / 100), 2);

    $item = [
        'title'       => "Recarga de Monedero - Sandy's Gym",
        'quantity'    => 1,
        'unit_price'  => $importe_recarga,
        'currency_id' => 'MXN',
    ];

    $metadata = [
        'tipo_operacion'        => 'recarga_monedero',
        'id_socio'              => $id_socio,
        'importe_recarga'       => $importe_recarga,
        'porcentaje_incremento' => $porcentaje_incremento,
        'incremento_monto'      => $incremento_monto,
        'id_empresa'            => $id_empresa,
        'id_usuario'            => $id_usuario
    ];

    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = defined('BASE_URL_APP') ? rtrim(BASE_URL_APP, '/') : $scheme . '://' . $_SERVER['HTTP_HOST'] . preg_replace('#/api$#', '', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

    $request = [
        'items'              => [$item],
        'back_urls'          => [
            'success' => $baseUrl . "/index.php?page=gracias",
            'failure' => $baseUrl . "/index.php?page=gracias",
            'pending' => $baseUrl . "/index.php?page=gracias"
        ],
        'auto_return'        => 'approved',
        'external_reference' => 'MONEDERO_' . $id_socio . '_' . time(),
        'notification_url'   => $baseUrl . "/api/webhook_mercadopago.php",
        'metadata'           => $metadata,
    ];

    $client = new PreferenceClient();
    $preference = $client->create($request);

    // 5. Cache de Metadata
    $sql = "INSERT INTO san_mp_pref (pref_id, external_reference, metadata_json, created_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE metadata_json = VALUES(metadata_json)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $preference->id,
        $request['external_reference'],
        json_encode($metadata, JSON_UNESCAPED_UNICODE),
        date("Y-m-d H:i:s")
    ]);

    log_processor("Preferencia Monedero Creada: ID {$preference->id} para Socio: {$id_socio}");

    echo json_encode(['status' => 'success', 'url' => $preference->init_point]);

} catch (InvalidArgumentException $e) {
    // Excepciones controladas de negocio sí se muestran al cliente
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (MPApiException $e) {
    log_processor("MPApiException: " . json_encode($e->getApiResponse()));
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'Error de comunicación con la pasarela de pagos.']);
} catch (PDOException $e) {
    log_processor("PDOException: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error interno procesando la solicitud.']);
} catch (Exception $e) {
    log_processor("Exception Genérica: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error inesperado. Intente más tarde.']);
}
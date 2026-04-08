<?php
// api/procesar_recarga_monedero.php
session_start();

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

header('Content-Type: application/json');

if (empty($_SESSION['admin']['soc_id_socio'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida.']);
    exit;
}

try {
    $id_socio = (int)$_POST['id_socio'];
    $importe_recarga = (float)$_POST['importe'];
    
    $id_empresa = (int)($_SESSION['admin']['id_empresa'] ?? 1);
    $id_usuario = (int)($_SESSION['admin']['id_usuario'] ?? 99);
    $id_consorcio = (int)($_SESSION['admin']['id_consorcio'] ?? 1);

    if ($importe_recarga <= 0) {
        throw new Exception("El importe debe ser mayor a 0.");
    }

    // 1. Obtener promoción del consorcio para el monedero
    $query_consorcio = "SELECT con_abono FROM san_consorcios WHERE con_id_consorcio = ?";
    $stmt = $conn->prepare($query_consorcio);
    $stmt->execute([$id_consorcio]);
    $consorcio = $stmt->fetch(PDO::FETCH_ASSOC);
    $porcentaje_incremento = $consorcio ? (float)$consorcio['con_abono'] : 10; // 10% por defecto si no hay

    $incremento_monto = round($importe_recarga * ($porcentaje_incremento / 100), 2);

    // 2. Crear Item para Mercado Pago
    $item = [
        'title'       => "Recarga de Monedero - Sandy's Gym",
        'quantity'    => 1,
        'unit_price'  => $importe_recarga,
        'currency_id' => 'MXN',
    ];

    // 3. Metadata (¡CRÍTICA PARA EL WEBHOOK!)
    $metadata = [
        'tipo_operacion'        => 'recarga_monedero', // <-- Esto le dirá al webhook qué hacer
        'id_socio'              => $id_socio,
        'importe_recarga'       => $importe_recarga,
        'porcentaje_incremento' => $porcentaje_incremento,
        'incremento_monto'      => $incremento_monto,
        'id_empresa'            => $id_empresa,
        'id_usuario'            => $id_usuario
    ];

    // URLs de retorno
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = defined('BASE_URL_APP') ? rtrim(BASE_URL_APP, '/') : $scheme . '://' . $_SERVER['HTTP_HOST'] . preg_replace('#/api$#', '', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

    $request = [
        'items'              => [$item],
        'back_urls'          => [
            'success' => $baseUrl . "/index.php?page=user_monedero&status=success",
            'failure' => $baseUrl . "/index.php?page=user_monedero&status=error",
            'pending' => $baseUrl . "/index.php?page=user_monedero"
        ],
        'auto_return'        => 'approved',
        'external_reference' => 'MONEDERO_' . $id_socio . '_' . time(),
        'notification_url'   => $baseUrl . "/api/webhook_mercadopago.php",
        'metadata'           => $metadata,
    ];

    // 4. Generar Preferencia
    $client = new PreferenceClient();
    $preference = $client->create($request);

    // 5. Guardar Metadata en BD (Cache)
    $sql = "INSERT INTO san_mp_pref (pref_id, external_reference, metadata_json, created_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE metadata_json = VALUES(metadata_json)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $preference->id,
        $request['external_reference'],
        json_encode($metadata, JSON_UNESCAPED_UNICODE),
        date("Y-m-d H:i:s")
    ]);

    log_processor("Preferencia Monedero Creada: ID {$preference->id}");

    echo json_encode(['status' => 'success', 'url' => $preference->init_point]);

} catch (Exception $e) {
    log_processor("Error en recarga monedero: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
<?php
// api/procesar_pago.php
// PASO 1: Calcular precio y crear el link de pago a Mercado Pago (SDK PHP v3).

session_start();

// --- 1. AJUSTE DE ZONA HORARIA (MÉXICO) ---
date_default_timezone_set('America/Mexico_City');
// -------------------------------------------

require_once __DIR__ . '/../vendor/autoload.php'; // Composer
require_once __DIR__ . '/../conn.php';           // Conexión PDO
require_once __DIR__ . '/config.php';            // Debe definir MP_ACCESS_TOKEN y opcional BASE_URL_APP

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;

/* ================== CONFIG MERCADO PAGO ================== */
MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

// ===== INICIO DE NUEVA FUNCIÓN DE LOG =====
if (!defined('MP_PROCESSOR_LOG_FILE')) {
    define('MP_PROCESSOR_LOG_FILE', __DIR__ . '/../logs/procesar_pago.log'); // Log separado
}
function log_processor(string $message): void {
    // Ahora date() usará el horario de México definido arriba
    $ts = date("Y-m-d H:i:s"); 
    @file_put_contents(MP_PROCESSOR_LOG_FILE, "[$ts] $message" . PHP_EOL, FILE_APPEND);
}
log_processor("--- INICIO DE SOLICITUD api/procesar_pago.php ---");
// ===== FIN DE NUEVA FUNCIÓN DE LOG =====

/* ================== HELPERS DE BD (PDO) ================== */

function obtener_datos_socio_pdo(PDO $conexion_pdo, int $id_socio, int $id_empresa) {
    $q = "SELECT * FROM san_socios WHERE soc_id_socio = ? AND soc_id_empresa = ?";
    $st = $conexion_pdo->prepare($q);
    $st->execute([$id_socio, $id_empresa]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

function obtener_servicio_pdo(PDO $conexion_pdo, int $id_servicio, int $id_consorcio, int $id_giro) {
    $q = "SELECT ser_id_servicio AS id_servicio, 
                 ser_clave AS clave,
                 ser_descripcion AS descripcion,
                 ROUND(ser_cuota, 2) AS cuota,
                 ser_meses AS meses
          FROM san_servicios
          WHERE ser_id_servicio = ?
            AND ser_id_consorcio = ?
            AND ser_id_giro = ?
            AND ser_status <> 'D'";
    $st = $conexion_pdo->prepare($q);
    $st->execute([$id_servicio, $id_consorcio, $id_giro]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

function verificar_descuentos_promocionales_pdo(PDO $conexion_pdo, int $id_servicio) {
    if ($id_servicio === 125 || $id_servicio === 126) return true;
    $q = "SELECT 1 FROM san_descuentos_promociones WHERE id_servicio = ? LIMIT 1";
    $st = $conexion_pdo->prepare($q);
    $st->execute([$id_servicio]);
    return $st->fetch(PDO::FETCH_NUM) !== false;
}

function validar_codigo_promo_pdo(PDO $conexion_pdo, string $codigo_promocion) {
    $q = "SELECT p.porcentaje_descuento, p.tipo_promocion
          FROM san_codigos c
          INNER JOIN san_promociones p ON c.id_promocion = p.id_promocion
          WHERE c.codigo_generado = ?
            AND c.status = '1'
            AND p.vigencia_inicial <= CURDATE()
            AND p.vigencia_final   >= CURDATE()";
    $st = $conexion_pdo->prepare($q);
    $st->execute([$codigo_promocion]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

/* ================== VALIDACIÓN DE SESIÓN ================== */

if (empty($_SESSION['admin']['soc_id_socio'])) {
    log_processor("ERROR: Sesión no válida. _SESSION['admin']['soc_id_socio'] está vacía.");
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida.']);
    exit;
}

/* ================== ENTRADAS ================== */

// IDs base desde sesión
$id_socio_pagador = (int)$_SESSION['admin']['soc_id_socio'];
$id_empresa   = (int)($_SESSION['admin']['id_empresa']   ?? 1);
$id_consorcio = (int)($_SESSION['admin']['id_consorcio'] ?? 1);
$id_giro      = (int)($_SESSION['admin']['id_giro']      ?? 1);
$id_usuario_logueado = (int)($_SESSION['admin']['id_usuario'] ?? 99); // 99 como fallback

// Datos de formulario
$servicio_id_str  = $_POST['servicio']       ?? '';     // Ej: "123-1"
$codigo_promocion = trim((string)($_POST['codigo_promocion'] ?? ''));
$id_socio_benef   = (int)($_POST['miembro_a_pagar'] ?? $id_socio_pagador);
$fecha_ini_pago   = $_POST['fecha_inicio']   ?? null;   // "dd-mm-YYYY"
$fecha_fin_pago   = $_POST['fecha_fin']      ?? null;   // "dd-mm-YYYY"
$accion           = $_POST['accion']         ?? 'pagar';// 'preview' o 'pagar'

// Logs de entrada
log_processor("Datos de Sesión: id_socio_pagador={$id_socio_pagador}, id_empresa={$id_empresa}, id_usuario={$id_usuario_logueado}, id_consorcio={$id_consorcio}");
log_processor("Datos POST: servicio={$servicio_id_str}, socio_benef={$id_socio_benef}, accion={$accion}, codigo=" . ($codigo_promocion ?: 'N/A'));
log_processor("Fechas POST: inicio=" . ($fecha_ini_pago ?: 'N/A') . ", fin=" . ($fecha_fin_pago ?: 'N/A'));

/* ================== URLS / DOMINIO ================== */

if (defined('BASE_URL_APP') && BASE_URL_APP) {
    $baseUrl = rtrim(BASE_URL_APP, '/');
} else {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $root    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // /api
    $baseUrl = $scheme . '://' . $host . preg_replace('#/api$#', '', $root);
}
log_processor("BaseURL calculada: {$baseUrl}");

/* ================== LÓGICA DE CÁLCULO Y PREFERENCIA ================== */

header('Content-Type: application/json');

try {
    if (!$servicio_id_str || strpos($servicio_id_str, '-') === false) {
        throw new Exception("Selecciona un servicio válido.");
    }
    [$id_servicio_num, $meses] = explode('-', $servicio_id_str, 2);
    $id_servicio_num = (int)$id_servicio_num;

    $servicio = obtener_servicio_pdo($conn, $id_servicio_num, $id_consorcio, $id_giro);
    if (!$servicio) throw new Exception("El servicio seleccionado no existe o no está disponible.");

    $subtotal = (float)$servicio['cuota'];
    $total    = $subtotal;

    $benef = obtener_datos_socio_pdo($conn, $id_socio_benef, $id_empresa);
    if (!$benef) throw new Exception("No fue posible cargar el socio beneficiario.");

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
            throw new Exception("El servicio seleccionado no permite códigos promocionales.");
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
            if ($es_codigo_cumple) {
                throw new Exception("El código de cumpleaños ha expirado o no es válido. Contacta administración.");
            } elseif ($codigo_promocion !== '') {
                throw new Exception("El código de promoción no es válido o ha expirado.");
            }
        }
    }

    $desc_monto = round($subtotal * ($porc_total / 100), 2);
    $total      = round($subtotal - $desc_monto, 2);

    // =========== Solo PREVIEW ===========
    if ($accion === 'preview') {
        log_processor("Acción 'preview' detectada. Devolviendo cálculo.");
        echo json_encode([
            'status'            => 'success',
            'subtotal'          => (float)$subtotal,
            'descuento_monto'   => (float)$desc_monto,
            'total'             => (float)$total,
            'descuento_nombre'  => $desc_texto !== '' ? $desc_texto : 'N/A'
        ]);
        exit;
    }

    // =========== Pago real ===========
    log_processor("Acción 'pagar' detectada. Validando fechas.");
    if ($accion === 'pagar' && (!$fecha_ini_pago || !$fecha_fin_pago)) {
        throw new Exception("Las fechas de inicio y fin son requeridas para procesar el pago.");
    }

    // Item
    $item = [
        'title'       => $servicio['descripcion'],
        'quantity'    => 1,
        'unit_price'  => (float)$total,
        'currency_id' => 'MXN',
    ];

    // Metadatos (viajan a tu webhook)
    $metadata = [
        'id_socio_pagador'      => $id_socio_pagador,
        'id_socio_beneficiario' => $id_socio_benef,
        'id_servicio'           => $id_servicio_num,
        'fecha_ini'             => $fecha_ini_pago, // dd-mm-YYYY
        'fecha_fin'             => $fecha_fin_pago, // dd-mm-YYYY
        'codigo_usado'          => $promo_info ? $codigo_a_validar : null,
        'subtotal'              => (float)$subtotal,
        'monto_pagado'          => (float)$total,
        'descuento_aplicado'    => (float)$desc_monto,
        'tipo_promo'            => $promo_info['tipo_promocion'] ?? null,
        'id_empresa'            => $id_empresa,
        'id_usuario'            => $id_usuario_logueado
    ];

    // URLs
    $backUrls = [
        'success' => $baseUrl . "/index.php?page=gracias",
        'failure' => $baseUrl . "/index.php?page=pago_fallido",
        'pending' => $baseUrl . "/index.php?page=gracias",
    ];
    $notificationUrl = $baseUrl . "/api/webhook_mercadopago.php";

    // Request de preferencia
    $request = [
        'items'              => [$item],
        'back_urls'          => $backUrls,
        'auto_return'        => 'approved',
        'external_reference' => 'SOCIO_' . $id_socio_benef,
        'notification_url'   => $notificationUrl,
        'metadata'           => $metadata,
    ];

    // Log: lo que se envía a MP
    log_processor("Enviando la siguiente data a MP: " . json_encode($request, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    // Crear preferencia
    $client     = new PreferenceClient();
    $preference = $client->create($request);

    log_processor("Preferencia Creada. ID: {$preference->id}, URL: {$preference->init_point}");

    /* =========================================================
     * GUARDAR METADATA LOCALMENTE (HORARIO MX)
     * ========================================================= */
    try {
        // 2. MODIFICACIÓN: INCLUIR 'created_at' EXPLICITAMENTE
        // Esto garantiza que la fecha guardada sea la de PHP (MX) y no la del servidor MySQL (que puede ser UTC)
        $sql = "INSERT INTO san_mp_pref (pref_id, external_reference, metadata_json, created_at)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  external_reference = VALUES(external_reference),
                  metadata_json      = VALUES(metadata_json),
                  created_at         = VALUES(created_at)"; // Actualizar fecha si se reusa ID
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $preference->id,                       // pref_id
            $request['external_reference'] ?? null,// "SOCIO_5857"
            json_encode($metadata, JSON_UNESCAPED_UNICODE),
            date("Y-m-d H:i:s")                    // <-- AQUÍ SE MANDA LA HORA DE MÉXICO
        ]);
        log_processor("Cache de metadata guardada para pref_id={$preference->id} a las " . date("Y-m-d H:i:s"));
    } catch (Exception $e) {
        // No bloquear el cobro; solo registrar el problema
        log_processor("[WARN] No se pudo guardar san_mp_pref: " . $e->getMessage());
    }
    /* ================== FIN BLOQUE NUEVO ================== */

    echo json_encode([
        'status' => 'success',
        'url'    => $preference->init_point, // URL de pago
    ]);
    exit;

} catch (MPApiException $e) {
    $api = $e->getApiResponse();
    $status  = is_array($api) && isset($api['status'])  ? (int)$api['status'] : 0;
    $content = is_array($api) && isset($api['content']) ? $api['content'] : $e->getMessage();

    log_processor("MPApiException: HTTP={$status}, Content=" . json_encode($content));

    http_response_code($status && $status >= 400 ? $status : 502);
    echo json_encode([
        'status'  => 'error',
        'http'    => $status,
        'message' => $content,
    ]);
    exit;

} catch (Exception $e) {
    log_processor("Exception General: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
    exit;
}
?>
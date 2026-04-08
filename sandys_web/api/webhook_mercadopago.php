<?php
// api/webhook_mercadopago.php
// SISTEMA UNIFICADO: Procesa Membresías y Recargas de Monedero con ENVÍO DE CORREOS MEDIANTE PLANTILLAS.

// --- 1. AJUSTE DE ZONA HORARIA (MÉXICO) ---
date_default_timezone_set('America/Mexico_City');
// -------------------------------------------

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $ruta_base_phpmailer = __DIR__ . '/../phpmailer/src/';

    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    } else {
        @file_put_contents(__DIR__ . '/../logs/webhook.log', "[" . date("Y-m-d H:i:s") . "] ERROR CRÍTICO: No se encontraron los archivos de PHPMailer en: " . $ruta_base_phpmailer . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        exit;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conn.php';           // $conn (PDO)
require_once __DIR__ . '/config.php';            // MP_ACCESS_TOKEN, MP_WEBHOOK_SECRET, etc.
require_once __DIR__ . '/lib/EmailService.php';  // EmailService::send()

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;

/* ======================= LOG ======================= */
if (!defined('MP_WEBHOOK_LOG_FILE')) {
    define('MP_WEBHOOK_LOG_FILE', __DIR__ . '/../logs/webhook.log');
}
function log_webhook(string $message): void
{
    $ts = date("Y-m-d H:i:s");
    @file_put_contents(MP_WEBHOOK_LOG_FILE, "[$ts] $message" . PHP_EOL, FILE_APPEND);
}

/* =========== Lectura de headers (case-insensitive) =========== */
function read_headers_ci(): array
{
    $h = function_exists('getallheaders') ? getallheaders() : [];
    $norm = [];
    foreach ($h as $k => $v)
        $norm[strtolower($k)] = $v;
    foreach ($_SERVER as $k => $v) {
        if (strpos($k, 'HTTP_') === 0) {
            $key = strtolower(str_replace('_', '-', substr($k, 5)));
            if (!isset($norm[$key]))
                $norm[$key] = $v;
        }
    }
    return $norm;
}

/* =========== Validación HMAC =========== */
function mp_validate_signature_permissive(string $eventId, ?string $topic, string $xSignature, string $xRequestId, string $secret): bool
{
    $ts = null;
    $v1 = null;
    foreach (explode(',', $xSignature) as $part) {
        [$k, $v] = array_map('trim', explode('=', $part, 2) + [null, null]);
        if ($k === 'ts')
            $ts = $v;
        if ($k === 'v1')
            $v1 = $v;
    }
    if (!$ts || !$v1)
        return false;

    $cands = [];
    $cands[] = "id:{$eventId};request-id:{$xRequestId};ts:{$ts};";
    $cands[] = "id:{$eventId};request-id:{$xRequestId};ts:{$ts}";
    if ($topic) {
        $cands[] = "id:{$eventId};topic:{$topic};request-id:{$xRequestId};ts:{$ts};";
        $cands[] = "id:{$eventId};topic:{$topic};request-id:{$xRequestId};ts:{$ts}";
        $cands[] = "topic:{$topic};id:{$eventId};request-id:{$xRequestId};ts:{$ts};";
        $cands[] = "topic:{$topic};id:{$eventId};request-id:{$xRequestId};ts:{$ts}";
    }

    foreach ($cands as $manifest) {
        $calc = hash_hmac('sha256', $manifest, $secret);
        if (hash_equals($v1, $calc))
            return true;
    }
    return false;
}

/* =================== INICIO DE SOLICITUD =================== */
log_webhook("--- INICIO DE NOTIFICACIÓN ---");

try {
    if (!defined('MP_ACCESS_TOKEN') || !MP_ACCESS_TOKEN)
        throw new Exception("MP_ACCESS_TOKEN no definido.");
    if (!defined('MP_WEBHOOK_SECRET'))
        throw new Exception("MP_WEBHOOK_SECRET no definido.");
    $skipValidation = defined('MP_WEBHOOK_SKIP_VALIDATION') ? (bool) MP_WEBHOOK_SKIP_VALIDATION : false;

    MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

    $requestBody = file_get_contents('php://input') ?: '';
    $headers = read_headers_ci();

    $xSignature = $headers['x-signature'] ?? '';
    $xRequestId = $headers['x-request-id'] ?? '';

    $data = $requestBody !== '' ? json_decode($requestBody, true) : null;
    if ($requestBody !== '' && !is_array($data)) {
        log_webhook("WARN: Body no es JSON válido.");
        $data = null;
    }

    $eventId = $_GET['id'] ?? $_GET['data.id'] ?? ($data['data']['id'] ?? null) ?? ($data['id'] ?? null);
    $topic = $_GET['topic'] ?? ($data['type'] ?? null);

    if (!$eventId) {
        log_webhook("ERROR: eventId ausente.");
        http_response_code(200);
        exit;
    }

    if (!$skipValidation) {
        if (!$xSignature || !$xRequestId) {
            log_webhook("ERROR: faltan headers para validar. X-Signature o X-Request-Id ausentes.");
            http_response_code(401);
            exit;
        }

        $isValid = mp_validate_signature_permissive($eventId, $topic, $xSignature, $xRequestId, MP_WEBHOOK_SECRET);
        if (!$isValid) {
            log_webhook("ERROR firma inválida.");
            http_response_code(401);
            exit;
        }
        log_webhook("ÉXITO: Firma de Webhook validada correctamente.");
    }
} catch (Exception $e) {
    log_webhook("ERROR FATAL (prevalidación): " . $e->getMessage());
    http_response_code(401);
    exit;
}

/* =================== PROCESAMIENTO =================== */
$data = isset($data) && is_array($data) ? $data : json_decode($requestBody, true);
$action = $data['action'] ?? 'N/A';
$type = $data['type'] ?? ($_GET['topic'] ?? 'N/A');

if (($data['action'] ?? null) === 'payment.updated' || $type === 'payment') {
    $payment_id = $data['data']['id'] ?? ($_GET['id'] ?? null);

    if (!$payment_id) {
        log_webhook("ERROR: evento de pago sin id.");
        http_response_code(200);
        exit;
    }

    try {
        $payClient = new PaymentClient();
        $payment = $payClient->get($payment_id);
        $status = $payment->status ?? 'N/D';

        if ($status === 'approved') {
            log_webhook("Pago APROBADO ({$payment_id}). Resolviendo metadata...");

            // --- RESOLUCIÓN DE METADATA ---
            $metadata = (array) ($payment->metadata ?? []);
            $preference_id = $payment->preference_id ?? null;
            $external_reference = $payment->external_reference ?? null;

            if (empty($metadata) && $preference_id) {
                try {
                    $prefClient = new PreferenceClient();
                    $pref = $prefClient->get($preference_id);
                    $metadata = (array) ($pref->metadata ?? []);
                } catch (Exception $ePref) {
                }
            }

            if (empty($metadata) && $preference_id) {
                try {
                    $stmt = $conn->prepare("SELECT metadata_json FROM san_mp_pref WHERE pref_id = ? LIMIT 1");
                    $stmt->execute([$preference_id]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row && !empty($row['metadata_json'])) {
                        $metadata = json_decode($row['metadata_json'], true) ?: [];
                    }
                } catch (Exception $eDB) {
                }
            }

            if (empty($metadata) && $external_reference) {
                try {
                    $stmt = $conn->prepare("SELECT metadata_json FROM san_mp_pref WHERE external_reference = ? ORDER BY created_at DESC LIMIT 1");
                    $stmt->execute([$external_reference]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row && !empty($row['metadata_json'])) {
                        $metadata = json_decode($row['metadata_json'], true) ?: [];
                    }
                } catch (Exception $eDB2) {
                }
            }

            log_webhook("[Metadata] Obtenida: " . (empty($metadata) ? "VACÍA" : json_encode($metadata)));

            // --- ENRUTADOR DE OPERACIÓN (EL "CEREBRO") ---
            $tipo_operacion = $metadata['tipo_operacion'] ?? 'membresia';

            if ($tipo_operacion === 'recarga_monedero') {
                // ==========================================
                // LÓGICA DE MONEDERO
                // ==========================================
                log_webhook("Operación: RECARGA DE MONEDERO.");

                if (!recarga_ya_procesada_pdo($conn, $payment_id)) {
                    $exito = registrar_recarga_monedero_pdo($conn, $payment_id, (array) $metadata);
                    if ($exito) {
                        log_webhook("ÉXITO: Monedero actualizado. Enviando correo...");
                        enviar_correo_monedero_pdo($conn, $payment_id, (array) $metadata);
                    }
                } else {
                    log_webhook("ADVERTENCIA: Recarga de monedero duplicada. Ignorada.");
                }

            } else {
                // ==========================================
                // LÓGICA DE MEMBRESÍAS
                // ==========================================
                log_webhook("Operación: PAGO DE MEMBRESÍA.");

                if (!pago_ya_procesado_pdo($conn, $payment_id)) {
                    $id_pago_guardado = registrar_pago_completo_pdo($conn, $payment, (array) $metadata);
                    log_webhook("ÉXITO: Pago de membresía guardado en BD ID: {$id_pago_guardado}");

                    if ($id_pago_guardado) {
                        try {
                            enviar_correo_confirmacion_pdo($conn, $id_pago_guardado, (array) $metadata);
                        } catch (Exception $email_error) {
                            log_webhook("ERROR EMAIL: " . $email_error->getMessage());
                        }
                    }
                } else {
                    log_webhook("ADVERTENCIA: Pago de membresía duplicado. Ignorado.");
                }
            }

        } else {
            log_webhook("INFO: Status distinto de 'approved' ({$status}). Sin acciones.");
        }

        http_response_code(200);
        exit;
    } catch (MPApiException $e) {
        $api = $e->getApiResponse();
        $http = is_array($api) && isset($api['status']) ? (int) $api['status'] : 0;
        log_webhook("MPApiException: HTTP={$http}");
        http_response_code(in_array($http, [401, 403, 404]) ? 200 : 500);
        exit;
    } catch (Exception $e) {
        log_webhook("ERROR en procesamiento: " . $e->getMessage());
        http_response_code(500);
        exit;
    }
} else {
    http_response_code(200);
    exit;
}


/* =====================================================================
 * FUNCIONES PARA MONEDERO
 * ===================================================================== */

function recarga_ya_procesada_pdo(PDO $conn, $payment_id): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM san_prepago_detalle WHERE pred_descripcion LIKE ? LIMIT 1");
    $stmt->execute(["%(MP Ref: $payment_id)%"]);
    return $stmt->fetchColumn() !== false;
}

function registrar_recarga_monedero_pdo(PDO $conn, $payment_id, array $metadata)
{
    try {
        $conn->beginTransaction();

        $id_socio = (int) $metadata['id_socio'];
        $id_empresa = (int) $metadata['id_empresa'];
        $id_usuario = (int) $metadata['id_usuario'];
        $importe_recarga = (float) $metadata['importe_recarga'];
        $incremento_monto = (float) $metadata['incremento_monto'];
        $porc_incremento = (float) $metadata['porcentaje_incremento'];
        $fecha_mov = date('Y-m-d H:i:s');

        // 1. Bloquear y obtener saldo actual
        $stmtSocio = $conn->prepare("SELECT soc_mon_saldo FROM san_socios WHERE soc_id_socio = ? AND soc_id_empresa = ? FOR UPDATE");
        $stmtSocio->execute([$id_socio, $id_empresa]);
        $saldo_actual = (float) $stmtSocio->fetchColumn();

        // Query Genérico de detalle
        $sql_detalle = "INSERT INTO san_prepago_detalle (pred_descripcion, pred_importe, pred_saldo, pred_movimiento, pred_fecha, pred_id_socio, pred_id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?)";

        // 2. Abono Inicial (Movimiento 'S')
        $saldo_tras_abono = $saldo_actual + $importe_recarga;
        $desc_abono = "ABONO PREPAGO (MP Ref: $payment_id)";
        $conn->prepare($sql_detalle)->execute([$desc_abono, $importe_recarga, $saldo_tras_abono, 'S', $fecha_mov, $id_socio, $id_usuario]);

        // 3. Incremento Promocional (Movimiento 'A')
        $saldo_final = $saldo_tras_abono;
        if ($incremento_monto > 0) {
            $saldo_final = $saldo_tras_abono + $incremento_monto;
            $desc_promo = "INCREMENTO PROMOCIONAL ($porc_incremento%) (MP Ref: $payment_id)";
            $conn->prepare($sql_detalle)->execute([$desc_promo, $incremento_monto, $saldo_final, 'A', $fecha_mov, $id_socio, $id_usuario]);
        }

        // 4. Actualizar saldo total del socio
        $sqlUpdateSocio = "UPDATE san_socios SET soc_mon_saldo = ? WHERE soc_id_socio = ? AND soc_id_empresa = ?";
        $conn->prepare($sqlUpdateSocio)->execute([$saldo_final, $id_socio, $id_empresa]);

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

function enviar_correo_monedero_pdo(PDO $conn, $payment_id, array $metadata): void
{
    $id_socio = (int) $metadata['id_socio'];

    // Obtener datos actualizados del socio
    $stmt = $conn->prepare("SELECT soc_nombres, soc_correo, soc_mon_saldo FROM san_socios WHERE soc_id_socio = ?");
    $stmt->execute([$id_socio]);
    $socio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$socio || empty($socio['soc_correo']))
        return;

    $nombre = trim($socio['soc_nombres'] ?? 'Socio');
    $correo = $socio['soc_correo'];

    // Variables que requiere la plantilla
    $importe_recarga = (float) ($metadata['importe_recarga'] ?? 0);
    $incremento_monto = (float) ($metadata['incremento_monto'] ?? 0);
    $porcentaje_incremento = (float) ($metadata['porcentaje_incremento'] ?? 0);
    $saldo_final = (float) ($socio['soc_mon_saldo'] ?? 0);
    $fecha_hora = date('d/m/Y H:i:s');

    $asunto = "Confirmación de Recarga Monedero - Sandy's Gym";

    // ========= RENDER DE LA PLANTILLA =========
    ob_start();
    include __DIR__ . '/templates/monedero_confirmation_email.php';
    $mensaje = ob_get_clean();

    EmailService::send($correo, $nombre, $asunto, $mensaje);
}

/* =====================================================================
 * FUNCIONES PARA MEMBRESÍAS
 * ===================================================================== */

function pago_ya_procesado_pdo(PDO $conexion_pdo, $payment_id): bool
{
    $stmt = $conexion_pdo->prepare("SELECT 1 FROM san_pagos WHERE pag_referencia_mp = ? LIMIT 1");
    $stmt->execute([$payment_id]);
    return $stmt->fetchColumn() !== false;
}

function registrar_pago_completo_pdo(PDO $conn, $payment, array $metadata)
{
    $id_socio = (int) ($metadata['id_socio_beneficiario'] ?? 0);
    $fecha_ini_str = $metadata['fecha_ini'] ?? null;
    $fecha_fin_str = $metadata['fecha_fin'] ?? null;

    $fecha_ini = null;
    if (!empty($fecha_ini_str)) {
        $dt_ini = DateTime::createFromFormat('d-m-Y', $fecha_ini_str);
        if ($dt_ini)
            $fecha_ini = $dt_ini->format('Y-m-d');
    }
    $fecha_fin = null;
    if (!empty($fecha_fin_str)) {
        $dt_fin = DateTime::createFromFormat('d-m-Y', $fecha_fin_str);
        if ($dt_fin)
            $fecha_fin = $dt_fin->format('Y-m-d');
    }

    $fecha_mov = date('Y-m-d H:i:s');
    $importe_pagado = (float) ($metadata['monto_pagado'] ?? ($payment->transaction_amount ?? 0));
    $codigo_usado = $metadata['codigo_usado'] ?? null;
    $tipo_promo = $metadata['tipo_promo'] ?? null;
    $payment_id = $payment->id ?? null;
    $id_usuario_sis = (int) ($metadata['id_usuario'] ?? 0);
    $id_empresa_sis = (int) ($metadata['id_empresa'] ?? 0);
    $id_servicio_leido = (int) ($metadata['id_servicio'] ?? 0);

    // Validaciones
    $stmt = $conn->prepare("SELECT soc_id_socio, soc_id_empresa FROM san_socios WHERE soc_id_socio = ? LIMIT 1");
    $stmt->execute([$id_socio]);
    $rowSoc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$rowSoc)
        throw new Exception("Socio beneficiario no existe.");

    $soc_empresa = (int) ($rowSoc['soc_id_empresa'] ?? 0);
    if ($id_empresa_sis <= 0)
        $id_empresa_sis = $soc_empresa;

    $stmt = $conn->prepare("SELECT 1 FROM san_servicios WHERE ser_id_servicio = ? LIMIT 1");
    $stmt->execute([$id_servicio_leido]);
    if (!$stmt->fetchColumn())
        throw new Exception("Servicio {$id_servicio_leido} no existe.");

    $valid_user_id = null;
    if ($id_usuario_sis > 0) {
        $stmt = $conn->prepare("SELECT usua_id_usuario FROM san_usuarios WHERE usua_id_usuario = ? LIMIT 1");
        $stmt->execute([$id_usuario_sis]);
        if ($stmt->fetchColumn())
            $valid_user_id = $id_usuario_sis;
    }
    if (!$valid_user_id) {
        $stmt = $conn->query("SELECT usua_id_usuario FROM san_usuarios ORDER BY usua_id_usuario ASC LIMIT 1");
        $valid_user_id = (int) $stmt->fetchColumn();
    }

    $conn->beginTransaction();
    try {
        if (!empty($codigo_usado)) {
            if ($tipo_promo === 'Individual') {
                $conn->prepare("UPDATE san_codigos SET status = '0' WHERE codigo_generado = ?")->execute([$codigo_usado]);
            }
            $conn->prepare("INSERT INTO san_codigos_usados (id_socio, codigo_generado, fecha_usado, id_empresa) VALUES (?, ?, ?, ?)")
                ->execute([$id_socio, $codigo_usado, $fecha_mov, $id_empresa_sis]);
        }

        $sql_pago = "INSERT INTO san_pagos (pag_id_socio, pag_fecha_pago, pag_id_servicio, pag_fecha_ini, pag_fecha_fin, pag_tarjeta, pag_importe, pag_tipo_pago, pag_id_usuario, pag_id_empresa, pag_referencia_mp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $conn->prepare($sql_pago)->execute([
            $id_socio,
            $fecha_mov,
            $id_servicio_leido,
            $fecha_ini,
            $fecha_fin,
            $importe_pagado,
            $importe_pagado,
            'T',
            $valid_user_id,
            $id_empresa_sis,
            $payment_id
        ]);

        $id_pago_principal = $conn->lastInsertId();
        $conn->commit();
        return $id_pago_principal;
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

function enviar_correo_confirmacion_pdo(PDO $conn, int $id_pago_principal, array $metadata): void
{
    $id_socio_beneficiario = (int) ($metadata['id_socio_beneficiario'] ?? 0);
    $stmt = $conn->prepare("SELECT CONCAT(soc_nombres, ' ', soc_apepat, ' ', IFNULL(soc_apemat,'')) AS nombre, soc_correo FROM san_socios WHERE soc_id_socio = ?");
    $stmt->execute([$id_socio_beneficiario]);
    $socio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$socio || empty($socio['soc_correo']))
        return;

    $correo = $socio['soc_correo'];
    $nombre = trim($socio['nombre']);

    $stmt = $conn->prepare("SELECT p.*, s.ser_descripcion, s.ser_clave FROM san_pagos p LEFT JOIN san_servicios s ON s.ser_id_servicio = p.pag_id_servicio WHERE p.pag_id_pago = ? LIMIT 1");
    $stmt->execute([$id_pago_principal]);
    $pago = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pago)
        return;

    $fecha_pago = $pago['pag_fecha_pago'] ? date('d/m/Y H:i:s', strtotime($pago['pag_fecha_pago'])) : 'N/A';
    $total_pagado = (float) $pago['pag_importe'];
    $servicio_nombre = trim(($pago['ser_descripcion'] ?? 'Servicio') . (!empty($pago['ser_clave']) ? ' (' . $pago['ser_clave'] . ')' : ''));
    $fecha_ini = $pago['pag_fecha_ini'] ? date('d/m/Y', strtotime($pago['pag_fecha_ini'])) : 'N/A';
    $fecha_fin = $pago['pag_fecha_fin'] ? date('d/m/Y', strtotime($pago['pag_fecha_fin'])) : 'N/A';

    $asunto = "Confirmación de tu pago - Recibo No. {$id_pago_principal}";

    ob_start();
    include __DIR__ . '/templates/payment_confirmation_email.php';
    $mensaje = ob_get_clean();

    EmailService::send($correo, $nombre, $asunto, $mensaje);
}
?>
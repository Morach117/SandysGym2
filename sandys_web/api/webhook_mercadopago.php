<?php
declare(strict_types=1);

// api/webhook_mercadopago.php
// SISTEMA UNIFICADO: Procesa Membresías y Recargas de Monedero con ENVÍO DE CORREOS MEDIANTE PLANTILLAS.

date_default_timezone_set('America/Mexico_City');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conn.php';           // $conn (PDO)
require_once __DIR__ . '/config.php';            // MP_ACCESS_TOKEN, MP_WEBHOOK_SECRET
require_once __DIR__ . '/lib/EmailService.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;

if (!defined('MP_WEBHOOK_LOG_FILE')) {
    define('MP_WEBHOOK_LOG_FILE', __DIR__ . '/../logs/webhook.log');
}

function log_webhook(string $message): void
{
    $ts = date("Y-m-d H:i:s");
    @file_put_contents(MP_WEBHOOK_LOG_FILE, "[$ts] $message" . PHP_EOL, FILE_APPEND);
}

function read_headers_ci(): array
{
    $h = function_exists('getallheaders') ? getallheaders() : [];
    $norm = [];
    foreach ($h as $k => $v) {
        $norm[strtolower($k)] = $v;
    }
    foreach ($_SERVER as $k => $v) {
        if (strpos($k, 'HTTP_') === 0) {
            $key = strtolower(str_replace('_', '-', substr($k, 5)));
            if (!isset($norm[$key]))
                $norm[$key] = $v;
        }
    }
    return $norm;
}

function mp_validate_signature(string $eventId, ?string $topic, string $xSignature, string $xRequestId, string $secret): bool
{
    $ts = null;
    $v1 = null;
    foreach (explode(',', $xSignature) as $part) {
        $keyValue = explode('=', $part, 2);
        if (count($keyValue) !== 2)
            continue;

        $k = trim($keyValue[0]);
        $v = trim($keyValue[1]);

        if ($k === 'ts')
            $ts = $v;
        if ($k === 'v1')
            $v1 = $v;
    }
    if (!$ts || !$v1)
        return false;

    $manifests = [
        "id:{$eventId};request-id:{$xRequestId};ts:{$ts};",
        "id:{$eventId};request-id:{$xRequestId};ts:{$ts}",
    ];

    if ($topic) {
        $manifests[] = "id:{$eventId};topic:{$topic};request-id:{$xRequestId};ts:{$ts};";
        $manifests[] = "id:{$eventId};topic:{$topic};request-id:{$xRequestId};ts:{$ts}";
        $manifests[] = "topic:{$topic};id:{$eventId};request-id:{$xRequestId};ts:{$ts};";
        $manifests[] = "topic:{$topic};id:{$eventId};request-id:{$xRequestId};ts:{$ts}";
    }

    foreach ($manifests as $manifest) {
        $calc = hash_hmac('sha256', $manifest, $secret);
        if (hash_equals($v1, $calc))
            return true; // Prevención de Timing Attacks
    }
    return false;
}

/* =================== INICIO DE SOLICITUD =================== */
log_webhook("--- INICIO DE NOTIFICACIÓN ---");

try {
    if (!defined('MP_ACCESS_TOKEN') || empty(MP_ACCESS_TOKEN))
        throw new Exception("MP_ACCESS_TOKEN no definido.");
    if (!defined('MP_WEBHOOK_SECRET') || empty(MP_WEBHOOK_SECRET))
        throw new Exception("MP_WEBHOOK_SECRET no definido.");

    MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

    $requestBody = file_get_contents('php://input') ?: '';
    $headers = read_headers_ci();

    $xSignature = $headers['x-signature'] ?? '';
    $xRequestId = $headers['x-request-id'] ?? '';

    $data = $requestBody !== '' ? json_decode($requestBody, true) : null;
    if ($requestBody !== '' && json_last_error() !== JSON_ERROR_NONE) {
        log_webhook("WARN: Body no es JSON válido.");
        http_response_code(400);
        exit;
    }

    $eventId = $_GET['id'] ?? $data['id'] ?? null;
    $topic = $_GET['topic'] ?? $data['type'] ?? null;

    if (!$eventId) {
        log_webhook("ERROR: eventId ausente.");
        http_response_code(200); // MP requiere 200 para no reintentar infinitamente
        exit;
    }

    if (!$xSignature || !$xRequestId) {
        log_webhook("ERROR: Faltan headers para validar firma.");
        http_response_code(401);
        exit;
    }

    if (!mp_validate_signature((string) $eventId, $topic, $xSignature, $xRequestId, MP_WEBHOOK_SECRET)) {
        log_webhook("ERROR: Firma de Webhook inválida. Posible ataque de suplantación.");
        http_response_code(401);
        exit;
    }

} catch (Exception $e) {
    log_webhook("ERROR FATAL (prevalidación): " . $e->getMessage());
    http_response_code(500);
    exit;
}

/* =================== PROCESAMIENTO =================== */
$action = $data['action'] ?? 'N/A';
$type = $data['type'] ?? $topic ?? 'N/A';

if ($action === 'payment.updated' || $type === 'payment') {
    $payment_id = filter_var($data['data']['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$payment_id) {
        log_webhook("ERROR: ID de pago inválido o ausente.");
        http_response_code(200);
        exit;
    }

    try {
        // La fuente de verdad absoluta DEBE ser la API de MP, no el payload
        $payClient = new PaymentClient();
        $payment = $payClient->get((int) $payment_id);
        $status = $payment->status ?? 'unknown';

        if ($status === 'approved') {
            log_webhook("Pago APROBADO ({$payment_id}). Procesando...");

            $metadata = (array) ($payment->metadata ?? []);
            $preference_id = $payment->preference_id ?? null;
            $external_reference = $payment->external_reference ?? null;

            // Resolución de metadata de base de datos segura
            if (empty($metadata) && $preference_id) {
                $stmt = $conn->prepare("SELECT metadata_json FROM san_mp_pref WHERE pref_id = ? LIMIT 1");
                $stmt->execute([$preference_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['metadata_json'])) {
                    $metadata = json_decode($row['metadata_json'], true) ?: [];
                }
            }

            if (empty($metadata) && $external_reference) {
                $stmt = $conn->prepare("SELECT metadata_json FROM san_mp_pref WHERE external_reference = ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$external_reference]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['metadata_json'])) {
                    $metadata = json_decode($row['metadata_json'], true) ?: [];
                }
            }

            $tipo_operacion = filter_var($metadata['tipo_operacion'] ?? 'membresia', FILTER_SANITIZE_SPECIAL_CHARS);

            if ($tipo_operacion === 'recarga_monedero') {
                if (!recarga_ya_procesada_pdo($conn, $payment_id)) {
                    if (registrar_recarga_monedero_pdo($conn, $payment_id, $metadata)) {
                        enviar_correo_monedero_pdo($conn, $payment_id, $metadata);
                    }
                }
            } else {
                if (!pago_ya_procesado_pdo($conn, $payment_id)) {
                    $id_pago_guardado = registrar_pago_completo_pdo($conn, $payment, $metadata);
                    if ($id_pago_guardado) {
                        try {
                            enviar_correo_confirmacion_pdo($conn, $id_pago_guardado, $metadata);
                        } catch (Exception $e) {
                            log_webhook("ERROR EMAIL: " . $e->getMessage());
                        }

                        // Lógica estricta de Referidos
                        procesar_recompensa_referido($conn, (int) ($metadata['id_socio_beneficiario'] ?? 0));
                    }
                }
            }
        }
        http_response_code(200);
        exit;
    } catch (MPApiException $e) {
        $http = is_array($e->getApiResponse()) && isset($e->getApiResponse()['status']) ? (int) $e->getApiResponse()['status'] : 0;
        log_webhook("MPApiException: HTTP={$http}");
        http_response_code(in_array($http, [401, 403, 404]) ? 200 : 500);
        exit;
    } catch (Exception $e) {
        log_webhook("ERROR INTERNO: " . $e->getMessage());
        http_response_code(500);
        exit;
    }
}

http_response_code(200);
exit;

/* =====================================================================
 * FUNCIONES DE NEGOCIO AISLADAS
 * ===================================================================== */

function procesar_recompensa_referido(PDO $conn, int $id_socio): void
{
    if ($id_socio <= 0)
        return;
    try {
        $stmtPagos = $conn->prepare("SELECT COUNT(pag_id_pago) FROM san_pagos WHERE pag_id_socio = ?");
        $stmtPagos->execute([$id_socio]);

        // La bonificación al Padrino se dispara solo con el PRIMER pago (COUNT == 1)
        if ($stmtPagos->fetchColumn() == 1) {
            $stmtSocio = $conn->prepare("SELECT soc_id_referido_por, soc_nombres, soc_apepat FROM san_socios WHERE soc_id_socio = ? LIMIT 1");
            $stmtSocio->execute([$id_socio]);
            $datosSocio = $stmtSocio->fetch(PDO::FETCH_ASSOC);

            if ($datosSocio && $datosSocio['soc_id_referido_por'] > 0) {
                require_once __DIR__ . '/lib/UserService.php';
                $userService = new UserService($conn);

                $idPadrino = (int) $datosSocio['soc_id_referido_por'];
                $nombreNuevoSocio = trim($datosSocio['soc_nombres'] . ' ' . $datosSocio['soc_apepat']);

                $stmtPadrino = $conn->prepare("SELECT soc_nombres, soc_correo FROM san_socios WHERE soc_id_socio = ? LIMIT 1");
                $stmtPadrino->execute([$idPadrino]);
                $datosPadrino = $stmtPadrino->fetch(PDO::FETCH_ASSOC);

                if ($datosPadrino) {
                    $userService->darRecompensaPadrino($idPadrino, $datosPadrino, $nombreNuevoSocio);
                    log_webhook("ÉXITO: Recompensa de referido otorgada al padrino ID: {$idPadrino}");
                }
            }
        }
    } catch (Exception $e) {
        log_webhook("ERROR REFERIDOS: " . $e->getMessage());
    }
}

function recarga_ya_procesada_pdo(PDO $conn, int $payment_id): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM san_prepago_detalle WHERE pred_descripcion LIKE ? LIMIT 1");
    $stmt->execute(["%(MP Ref: $payment_id)%"]);
    return $stmt->fetchColumn() !== false;
}

function registrar_recarga_monedero_pdo(PDO $conn, int $payment_id, array $metadata): bool
{
    $conn->beginTransaction();
    try {
        $id_socio = (int) $metadata['id_socio'];
        $id_usuario = (int) $metadata['id_usuario'];
        $importe_recarga = (float) $metadata['importe_recarga'];
        $incremento_monto = (float) ($metadata['incremento_monto'] ?? 0);
        $porc_incremento = (float) ($metadata['porcentaje_incremento'] ?? 0);
        $fecha_mov = date('Y-m-d H:i:s');

        $stmtSocio = $conn->prepare("SELECT soc_mon_saldo FROM san_socios WHERE soc_id_socio = ? FOR UPDATE");
        $stmtSocio->execute([$id_socio]);
        $saldo_actual = (float) $stmtSocio->fetchColumn();

        $sql_detalle = "INSERT INTO san_prepago_detalle (pred_descripcion, pred_importe, pred_saldo, pred_movimiento, pred_fecha, pred_id_socio, pred_id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtDetalle = $conn->prepare($sql_detalle);

        $saldo_tras_abono = $saldo_actual + $importe_recarga;
        $stmtDetalle->execute(["ABONO PREPAGO (MP Ref: $payment_id)", $importe_recarga, $saldo_tras_abono, 'S', $fecha_mov, $id_socio, $id_usuario]);

        $saldo_final = $saldo_tras_abono;
        if ($incremento_monto > 0) {
            $saldo_final = $saldo_tras_abono + $incremento_monto;
            $stmtDetalle->execute(["INCREMENTO PROMOCIONAL ($porc_incremento%) (MP Ref: $payment_id)", $incremento_monto, $saldo_final, 'A', $fecha_mov, $id_socio, $id_usuario]);
        }

        $conn->prepare("UPDATE san_socios SET soc_mon_saldo = ? WHERE soc_id_socio = ?")->execute([$saldo_final, $id_socio]);

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

function enviar_correo_monedero_pdo(PDO $conn, int $payment_id, array $metadata): void
{
    $id_socio = (int) $metadata['id_socio'];
    $stmt = $conn->prepare("SELECT soc_nombres, soc_correo, soc_mon_saldo FROM san_socios WHERE soc_id_socio = ? LIMIT 1");
    $stmt->execute([$id_socio]);
    $socio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$socio || empty($socio['soc_correo']))
        return;

    $nombre = trim($socio['soc_nombres'] ?? 'Socio');
    $correo = filter_var($socio['soc_correo'], FILTER_SANITIZE_EMAIL);

    $importe_recarga = (float) ($metadata['importe_recarga'] ?? 0);
    $incremento_monto = (float) ($metadata['incremento_monto'] ?? 0);
    $porcentaje_incremento = (float) ($metadata['porcentaje_incremento'] ?? 0);
    $saldo_final = (float) ($socio['soc_mon_saldo'] ?? 0);
    $fecha_hora = date('d/m/Y H:i:s');
    $asunto = "Confirmación de Recarga Monedero - Sandy's Gym";

    ob_start();
    include __DIR__ . '/templates/monedero_confirmation_email.php';
    $mensaje = ob_get_clean();

    EmailService::send($correo, $nombre, $asunto, $mensaje);
}

function pago_ya_procesado_pdo(PDO $conn, int $payment_id): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM san_pagos WHERE pag_referencia_mp = ? LIMIT 1");
    $stmt->execute([$payment_id]);
    return $stmt->fetchColumn() !== false;
}

function registrar_pago_completo_pdo(PDO $conn, object $payment, array $metadata): int
{
    $id_socio = (int) ($metadata['id_socio_beneficiario'] ?? 0);
    $fecha_ini = !empty($metadata['fecha_ini']) ? DateTime::createFromFormat('d-m-Y', $metadata['fecha_ini'])->format('Y-m-d') : null;
    $fecha_fin = !empty($metadata['fecha_fin']) ? DateTime::createFromFormat('d-m-Y', $metadata['fecha_fin'])->format('Y-m-d') : null;

    $fecha_mov = date('Y-m-d H:i:s');
    $importe_pagado = (float) ($metadata['monto_pagado'] ?? ($payment->transaction_amount ?? 0));
    $codigo_usado = filter_var($metadata['codigo_usado'] ?? null, FILTER_SANITIZE_SPECIAL_CHARS);
    $tipo_promo = filter_var($metadata['tipo_promo'] ?? null, FILTER_SANITIZE_SPECIAL_CHARS);
    $payment_id = (int) $payment->id;
    $id_usuario_sis = (int) ($metadata['id_usuario'] ?? 0);
    $id_empresa_sis = (int) ($metadata['id_empresa'] ?? 0);
    $id_servicio_leido = (int) ($metadata['id_servicio'] ?? 0);

    $stmt = $conn->prepare("SELECT soc_id_empresa FROM san_socios WHERE soc_id_socio = ? LIMIT 1");
    $stmt->execute([$id_socio]);
    $rowSoc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$rowSoc)
        throw new Exception("Socio inexistente.");

    $id_empresa_sis = $id_empresa_sis > 0 ? $id_empresa_sis : (int) $rowSoc['soc_id_empresa'];

    $conn->beginTransaction();
    try {
        if (!empty($codigo_usado)) {
            if ($tipo_promo === 'Individual') {
                $conn->prepare("UPDATE san_codigos SET status = '0' WHERE codigo_generado = ?")->execute([$codigo_usado]);
            }
            $conn->prepare("INSERT INTO san_codigos_usados (id_socio, codigo_generado, fecha_usado, id_empresa) VALUES (?, ?, ?, ?)")
                ->execute([$id_socio, $codigo_usado, $fecha_mov, $id_empresa_sis]);
        }

        $sql_pago = "INSERT INTO san_pagos (pag_id_socio, pag_fecha_pago, pag_id_servicio, pag_fecha_ini, pag_fecha_fin, pag_importe, pag_tipo_pago, pag_id_usuario, pag_id_empresa, pag_referencia_mp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $conn->prepare($sql_pago)->execute([
            $id_socio,
            $fecha_mov,
            $id_servicio_leido,
            $fecha_ini,
            $fecha_fin,
            $importe_pagado,
            'T',
            $id_usuario_sis,
            $id_empresa_sis,
            $payment_id
        ]);

        $id_pago_principal = (int) $conn->lastInsertId();
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
    $stmt = $conn->prepare("SELECT CONCAT(soc_nombres, ' ', soc_apepat) AS nombre, soc_correo FROM san_socios WHERE soc_id_socio = ? LIMIT 1");
    $stmt->execute([$id_socio_beneficiario]);
    $socio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$socio || empty($socio['soc_correo']))
        return;

    $correo = filter_var($socio['soc_correo'], FILTER_SANITIZE_EMAIL);
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
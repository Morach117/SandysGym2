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

/**
 * Validación estricta de la firma (X-Signature) para Webhooks API v3
 */
function mp_validate_signature(string $dataId, string $xSignature, string $xRequestId, string $secret): bool
{
    $ts = null;
    $v1 = null;

    // Desglose tolerante a espacios
    foreach (explode(',', $xSignature) as $part) {
        $keyValue = explode('=', trim($part), 2);
        if (count($keyValue) === 2) {
            $k = trim($keyValue[0]);
            $v = trim($keyValue[1]);
            
            if ($k === 'ts') $ts = $v;
            if ($k === 'v1') $v1 = $v;
        }
    }

    if (!$ts || !$v1) {
        return false;
    }

    // Fallbacks del manifiesto para soportar API v3 y notificaciones IPN legacy/test
    $manifests = [
        "id:{$dataId};request-id:{$xRequestId};ts:{$ts};",
        "id:{$dataId};request-id:{$xRequestId};ts:{$ts}",
        "id:{$dataId};ts:{$ts};" // Respaldo por si x-request-id viene vacío
    ];

    foreach ($manifests as $manifest) {
        $calc = hash_hmac('sha256', $manifest, $secret);
        if (hash_equals($v1, $calc)) {
            return true;
        }
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

    // Extracción en cascada: URL (Webhooks) -> URL (IPN) -> JSON data.id -> JSON id
    $dataId = $_GET['data_id'] ?? $_GET['id'] ?? ($data['data']['id'] ?? ($data['id'] ?? null));
    $dataId = $dataId !== null ? (string) $dataId : null;

    if (!$dataId) {
        log_webhook("ERROR: ID ausente. GET: " . json_encode($_GET) . " | Body: " . json_encode($data));
        http_response_code(200); 
        exit;
    }

    // Comprobación del modo de pruebas
    $skip_validation = defined('MP_WEBHOOK_SKIP_VALIDATION') && MP_WEBHOOK_SKIP_VALIDATION === true;

    if (!$skip_validation) {
        if (!$xSignature) {
            log_webhook("ERROR: Falta x-signature para validar firma.");
            http_response_code(401);
            exit;
        }

        if (!mp_validate_signature($dataId, $xSignature, $xRequestId, MP_WEBHOOK_SECRET)) {
            log_webhook("ERROR: Firma de Webhook inválida. ID extraído: {$dataId}. Posible ataque o Test Ping.");
            http_response_code(401);
            exit;
        }
    } else {
        log_webhook("AVISO: Validación de firma omitida por configuración (MP_WEBHOOK_SKIP_VALIDATION).");
    }

} catch (Exception $e) {
    log_webhook("ERROR FATAL (prevalidación): " . $e->getMessage());
    http_response_code(500);
    exit;
}

/* =================== PROCESAMIENTO =================== */
$action = $data['action'] ?? 'N/A';
$type = $data['type'] ?? $_GET['topic'] ?? 'N/A';

// FIX CRÍTICO: MP envía payment.created Y payment.updated para un mismo pago.
// Antes solo se procesaba payment.updated, lo que causaba que muchos pagos se ignoraran.
$es_evento_pago = in_array($action, ['payment.created', 'payment.updated'], true) || $type === 'payment';

if ($es_evento_pago) {
    // Reutilizamos el $dataId validado y convertido a entero
    $payment_id = filter_var($dataId, FILTER_VALIDATE_INT);

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
                } else {
                    log_webhook("ADVERTENCIA: Recarga duplicada (Payment ID: {$payment_id}). Ignorado.");
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
                } else {
                    log_webhook("ADVERTENCIA: Pago duplicado (Payment ID: {$payment_id}). Ignorado.");
                }
            }
        } else {
            log_webhook("Pago {$payment_id} con status '{$status}'. No se procesa.");
        }
        http_response_code(200);
        exit;
    } catch (MPApiException $e) {
        $apiResponse = $e->getApiResponse();
        $http = 0;
        if (is_object($apiResponse) && method_exists($apiResponse, 'getStatusCode')) {
            $http = (int) $apiResponse->getStatusCode();
        } elseif (is_array($apiResponse) && isset($apiResponse['status'])) {
            $http = (int) $apiResponse['status'];
        }
        log_webhook("MPApiException: HTTP={$http} | " . $e->getMessage());
        // Si MP dice 404 (pago no existe) o 401/403, no hay nada que reintentar
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
    $stmt->execute(["%MP Ref: $payment_id%"]);
    $result = $stmt->fetchColumn();
    return $result !== false && $result !== null;
}

function registrar_recarga_monedero_pdo(PDO $conn, int $payment_id, array $metadata): bool
{
    $conn->beginTransaction();
    try {
        // Validamos variables con tolerancias (puede venir como id_socio o id_socio_beneficiario)
        $id_socio = (int) ($metadata['id_socio'] ?? $metadata['id_socio_beneficiario'] ?? 0);
        $id_usuario = (int) ($metadata['id_usuario'] ?? 1); // Fallback a 1 si no existe
        $importe_recarga = (float) ($metadata['importe_recarga'] ?? 0);
        
        if ($id_socio <= 0 || $importe_recarga <= 0) {
            throw new Exception("Datos de recarga inválidos. Socio: $id_socio | Importe: $importe_recarga");
        }

        $incremento_monto = (float) ($metadata['incremento_monto'] ?? 0);
        $porc_incremento = (float) ($metadata['porcentaje_incremento'] ?? 0);
        $fecha_mov = date('Y-m-d H:i:s');

        // Lock de fila con FOR UPDATE para evitar race conditions por notificaciones simultáneas
        $stmtSocio = $conn->prepare("SELECT soc_mon_saldo FROM san_socios WHERE soc_id_socio = ? FOR UPDATE");
        $stmtSocio->execute([$id_socio]);
        $saldo_row = $stmtSocio->fetch(PDO::FETCH_ASSOC);

        if (!$saldo_row) {
            throw new Exception("Socio ID $id_socio no encontrado en la base de datos.");
        }

        // Verificar duplicado dentro de la transacción (con el lock activo)
        $stmtDup = $conn->prepare("SELECT COUNT(*) FROM san_prepago_detalle WHERE pred_descripcion LIKE ? LIMIT 1");
        $stmtDup->execute(["%MP Ref: $payment_id%"]);
        if ((int) $stmtDup->fetchColumn() > 0) {
            $conn->rollBack();
            log_webhook("ADVERTENCIA: Recarga duplicada detectada dentro de transacción (Payment ID: {$payment_id}).");
            return false;
        }

        $saldo_actual = (float) $saldo_row['soc_mon_saldo'];
        $saldo_tras_abono = $saldo_actual + $importe_recarga;

        $sql_detalle = "INSERT INTO san_prepago_detalle (pred_descripcion, pred_importe, pred_saldo, pred_movimiento, pred_fecha, pred_id_socio, pred_id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtDetalle = $conn->prepare($sql_detalle);

        $stmtDetalle->execute(["ABONO PREPAGO (MP Ref: $payment_id)", $importe_recarga, $saldo_tras_abono, 'S', $fecha_mov, $id_socio, $id_usuario]);

        $saldo_final = $saldo_tras_abono;
        if ($incremento_monto > 0) {
            $saldo_final = $saldo_tras_abono + $incremento_monto;
            $stmtDetalle->execute(["INCREMENTO PROMOCIONAL ($porc_incremento%) (MP Ref: $payment_id)", $incremento_monto, $saldo_final, 'A', $fecha_mov, $id_socio, $id_usuario]);
        }

        $conn->prepare("UPDATE san_socios SET soc_mon_saldo = ? WHERE soc_id_socio = ?")->execute([$saldo_final, $id_socio]);

        $conn->commit();
        log_webhook("ÉXITO: Recarga procesada. Socio: {$id_socio} | Monto: {$importe_recarga} | Saldo final: {$saldo_final}");
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        if ($e->getCode() == 23000 && strpos($e->getMessage(), '1062') !== false) {
            log_webhook("ADVERTENCIA: Recarga duplicada detectada por constraint de BD (Payment ID: {$payment_id}). Ignorado.");
            return false;
        }
        log_webhook("ERROR MONEDERO: " . $e->getMessage());
        throw $e;
    } catch (Exception $e) {
        $conn->rollBack();
        log_webhook("ERROR MONEDERO: " . $e->getMessage());
        throw $e;
    }
}

/**
 * FIX CRÍTICO: Esta función faltaba completamente en el archivo original.
 * Se llamaba en línea 197 pero nunca fue definida, causando un Fatal Error
 * que mataba silenciosamente el proceso del webhook para recargas de monedero.
 */
function enviar_correo_monedero_pdo(PDO $conn, int $payment_id, array $metadata): void
{
    log_webhook("Iniciando envío de correo de recarga para Payment ID: {$payment_id}");

    $id_socio = (int) ($metadata['id_socio'] ?? $metadata['id_socio_beneficiario'] ?? 0);
    if ($id_socio <= 0) {
        log_webhook("AVISO EMAIL MONEDERO: No se pudo determinar el socio para enviar correo.");
        return;
    }

    // Obtener datos del socio
    $stmtSocio = $conn->prepare("SELECT CONCAT(soc_nombres, ' ', soc_apepat) AS nombre, soc_correo FROM san_socios WHERE soc_id_socio = ? LIMIT 1");
    $stmtSocio->execute([$id_socio]);
    $socio = $stmtSocio->fetch(PDO::FETCH_ASSOC);

    if (!$socio || empty($socio['soc_correo'])) {
        log_webhook("AVISO EMAIL MONEDERO: El socio ID {$id_socio} no tiene correo registrado.");
        return;
    }

    $correo = filter_var($socio['soc_correo'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        log_webhook("AVISO EMAIL MONEDERO: Correo inválido para socio ID {$id_socio}: {$correo}");
        return;
    }

    $nombre = trim($socio['nombre']);

    // Variables que la plantilla espera
    $importe_recarga = (float) ($metadata['importe_recarga'] ?? 0);
    $incremento_monto = (float) ($metadata['incremento_monto'] ?? 0);
    $porcentaje_incremento = (float) ($metadata['porcentaje_incremento'] ?? 0);

    // Calcular saldo final actual desde la BD
    $stmtSaldo = $conn->prepare("SELECT soc_mon_saldo FROM san_socios WHERE soc_id_socio = ? LIMIT 1");
    $stmtSaldo->execute([$id_socio]);
    $saldo_final = (float) $stmtSaldo->fetchColumn();

    $fecha_hora = date('d/m/Y H:i:s');
    $asunto = "Confirmación de Recarga de Monedero - Sandy's Gym";

    // Renderizar la plantilla
    ob_start();
    include __DIR__ . '/templates/monedero_confirmation_email.php';
    $mensaje = ob_get_clean();

    if (empty($mensaje)) {
        log_webhook("ERROR EMAIL MONEDERO: La plantilla no generó contenido.");
        return;
    }

    // Enviar el correo
    try {
        $resultado = EmailService::send($correo, $nombre, $asunto, $mensaje);
        if ($resultado) {
            log_webhook("ÉXITO EMAIL MONEDERO: Correo enviado a {$correo} para Payment ID {$payment_id}");
        } else {
            log_webhook("ERROR EMAIL MONEDERO: EmailService::send() retornó false para {$correo}. Revisar logs de mail_error.");
        }
    } catch (Exception $e) {
        log_webhook("ERROR EMAIL MONEDERO SERVICE: " . $e->getMessage());
    }
}

function pago_ya_procesado_pdo(PDO $conn, int $payment_id): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM san_pagos WHERE pag_referencia_mp = ? LIMIT 1");
    $stmt->execute([$payment_id]);
    $result = $stmt->fetchColumn();
    return $result !== false && $result !== null;
}

function registrar_pago_completo_pdo(PDO $conn, object $payment, array $metadata): int
{
    $fecha_ini = !empty($metadata['fecha_ini']) ? DateTime::createFromFormat('d-m-Y', $metadata['fecha_ini']) : null;
    $fecha_fin = !empty($metadata['fecha_fin']) ? DateTime::createFromFormat('d-m-Y', $metadata['fecha_fin']) : null;
    
    // Protección contra fechas con formato incorrecto
    $fecha_ini = $fecha_ini ? $fecha_ini->format('Y-m-d') : null;
    $fecha_fin = $fecha_fin ? $fecha_fin->format('Y-m-d') : null;
    
    $fecha_mov = date('Y-m-d H:i:s');
    $importe_pagado = (float) ($metadata['monto_pagado'] ?? ($payment->transaction_amount ?? 0));
    $codigo_usado = filter_var($metadata['codigo_usado'] ?? null, FILTER_SANITIZE_SPECIAL_CHARS);
    $tipo_promo = filter_var($metadata['tipo_promo'] ?? null, FILTER_SANITIZE_SPECIAL_CHARS);
    $payment_id = (int) $payment->id;

    /* =========================================================
       BLINDAJE DE CLAVES FORÁNEAS (FALLBACKS PARA SIMULADOR)
       ========================================================= */
       
    // 1. Forzar Usuario Verificado (Validar que realmente exista)
    $id_usuario_sis = (int) ($metadata['id_usuario'] ?? 0);
    if ($id_usuario_sis > 0) {
        $stmtUsua = $conn->prepare("SELECT 1 FROM san_usuarios WHERE usua_id_usuario = ? LIMIT 1");
        $stmtUsua->execute([$id_usuario_sis]);
        if (!$stmtUsua->fetchColumn()) {
            $id_usuario_sis = 0; // Forzar el fallback si no existe en la BD
        }
    }
    
    if ($id_usuario_sis <= 0) {
        $id_usuario_sis = defined('MP_FALLBACK_USER_ID') ? MP_FALLBACK_USER_ID : 1;
    }

    // 2. Forzar Empresa Verificada (Valor fijo)
    $id_empresa_sis = 1; 

    // 3. Forzar Socio Válido (Evita fk_id_socio_pag)
    $id_socio = (int) ($metadata['id_socio_beneficiario'] ?? 0);
    if ($id_socio > 0) {
        $stmtValidarSocio = $conn->prepare("SELECT 1 FROM san_socios WHERE soc_id_socio = ? LIMIT 1");
        $stmtValidarSocio->execute([$id_socio]);
        if (!$stmtValidarSocio->fetchColumn()) {
            $id_socio = 0; 
        }
    }

    if ($id_socio <= 0) {
        $stmtSocioDef = $conn->query("SELECT soc_id_socio FROM san_socios LIMIT 1");
        $id_socio = (int) $stmtSocioDef->fetchColumn();
        if (!$id_socio) throw new Exception("Error: No existen socios en la BD.");
    }

    // 4. Forzar Servicio Válido (Evita fk_id_servicio_pag)
    $id_servicio_leido = (int) ($metadata['id_servicio'] ?? 0);
    if ($id_servicio_leido > 0) {
        $stmtValidarServ = $conn->prepare("SELECT 1 FROM san_servicios WHERE ser_id_servicio = ? AND ser_status <> 'D' LIMIT 1");
        $stmtValidarServ->execute([$id_servicio_leido]);
        if (!$stmtValidarServ->fetchColumn()) {
            $id_servicio_leido = 0;
        }
    }

    if ($id_servicio_leido <= 0) {
        $stmtServicio = $conn->query("SELECT ser_id_servicio FROM san_servicios WHERE ser_status <> 'D' LIMIT 1");
        $id_servicio_leido = (int) $stmtServicio->fetchColumn() ?: 1; 
    }

    $conn->beginTransaction();
    try {
        // Verificar duplicado DENTRO de la transacción para evitar race condition
        $stmtDupCheck = $conn->prepare("SELECT 1 FROM san_pagos WHERE pag_referencia_mp = ? LIMIT 1");
        $stmtDupCheck->execute([$payment_id]);
        if ($stmtDupCheck->fetchColumn()) {
            $conn->rollBack();
            log_webhook("ADVERTENCIA: Pago duplicado detectado dentro de transacción (Payment ID: {$payment_id}).");
            return 0;
        }

        if (!empty($codigo_usado)) {
            if ($tipo_promo === 'Individual') {
                $conn->prepare("UPDATE san_codigos SET status = '0' WHERE codigo_generado = ?")->execute([$codigo_usado]);
            }
            $conn->prepare("INSERT INTO san_codigos_usados (id_socio, codigo_generado, fecha_usado, id_empresa) VALUES (?, ?, ?, ?)")
                ->execute([$id_socio, $codigo_usado, $fecha_mov, $id_empresa_sis]);
        }

        // DEPURACIÓN: Verificamos exactamente qué se va a insertar
        log_webhook("DEBUG PRE-INSERT -> Socio: {$id_socio} | Serv: {$id_servicio_leido} | Usua: {$id_usuario_sis} | Emp: {$id_empresa_sis}");

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
        log_webhook("ÉXITO: Pago registrado en san_pagos ID: {$id_pago_principal}");
        return $id_pago_principal;
    } catch (PDOException $e) {
        $conn->rollBack();
        if ($e->getCode() == 23000 && strpos($e->getMessage(), '1062') !== false) {
            log_webhook("ADVERTENCIA: Pago duplicado detectado por constraint de BD (Payment ID: {$payment_id}). Ignorado.");
            return 0;
        }
        log_webhook("ERROR REGISTRO PAGO: " . $e->getMessage());
        throw $e;
    } catch (Exception $e) {
        $conn->rollBack();
        log_webhook("ERROR REGISTRO PAGO: " . $e->getMessage());
        throw $e;
    }
}

function enviar_correo_confirmacion_pdo(PDO $conn, int $id_pago_principal, array $metadata): void
{
    log_webhook("Iniciando envío de correo para el pago ID: {$id_pago_principal}");

    // 1. Obtener datos del pago y del servicio (Tal como lo espera tu plantilla)
    $stmtPago = $conn->prepare("
        SELECT p.*, s.ser_descripcion, s.ser_clave 
        FROM san_pagos p 
        LEFT JOIN san_servicios s ON s.ser_id_servicio = p.pag_id_servicio 
        WHERE p.pag_id_pago = ? LIMIT 1
    ");
    $stmtPago->execute([$id_pago_principal]);
    $pago = $stmtPago->fetch(PDO::FETCH_ASSOC);

    if (!$pago) {
        log_webhook("ERROR EMAIL: No se encontró el pago {$id_pago_principal} en la BD.");
        return;
    }

    // 2. Obtener datos del socio basándonos en el pago guardado (Restaurando el arreglo $socio)
    $id_socio_guardado = (int) $pago['pag_id_socio'];
    $stmtSocio = $conn->prepare("
        SELECT CONCAT(soc_nombres, ' ', soc_apepat) AS nombre, soc_correo 
        FROM san_socios 
        WHERE soc_id_socio = ? LIMIT 1
    ");
    $stmtSocio->execute([$id_socio_guardado]);
    $socio = $stmtSocio->fetch(PDO::FETCH_ASSOC);

    // Si es un ID de prueba o el socio no tiene email, cancelamos el envío
    if (!$socio || empty($socio['soc_correo'])) {
        log_webhook("AVISO EMAIL: El socio ID {$id_socio_guardado} no tiene correo registrado.");
        return;
    }

    // 3. Preparar variables idénticas a la versión original
    $correo = filter_var($socio['soc_correo'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        log_webhook("AVISO EMAIL: Correo inválido para socio ID {$id_socio_guardado}: {$correo}");
        return;
    }
    $nombre = trim($socio['nombre']);

    $fecha_pago = $pago['pag_fecha_pago'] ? date('d/m/Y H:i:s', strtotime($pago['pag_fecha_pago'])) : 'N/A';
    $total_pagado = (float) $pago['pag_importe'];
    $servicio_nombre = trim(($pago['ser_descripcion'] ?? 'Servicio') . (!empty($pago['ser_clave']) ? ' (' . $pago['ser_clave'] . ')' : ''));
    $fecha_ini = $pago['pag_fecha_ini'] ? date('d/m/Y', strtotime($pago['pag_fecha_ini'])) : 'N/A';
    $fecha_fin = $pago['pag_fecha_fin'] ? date('d/m/Y', strtotime($pago['pag_fecha_fin'])) : 'N/A';
    $asunto = "Confirmación de tu pago - Recibo No. {$id_pago_principal}";

    // 4. Inyectar variables en la plantilla
    ob_start();
    include __DIR__ . '/templates/payment_confirmation_email.php';
    $mensaje = ob_get_clean();

    if (empty($mensaje)) {
        log_webhook("ERROR EMAIL: La plantilla no generó contenido para pago {$id_pago_principal}.");
        return;
    }

    // 5. Enviar e imprimir resultado en el log
    try {
        $resultado = EmailService::send($correo, $nombre, $asunto, $mensaje);
        if ($resultado) {
            log_webhook("ÉXITO EMAIL: Correo enviado a {$correo} para el pago {$id_pago_principal}");
        } else {
            log_webhook("ERROR EMAIL: EmailService::send() retornó false para {$correo}. Revisar logs de mail_error.");
        }
    } catch (Exception $e) {
        log_webhook("ERROR EMAIL SERVICE: " . $e->getMessage());
    }
}
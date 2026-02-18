<?php
// api/webhook_mercadopago.php
// Recibir, VALIDAR (HMAC) y PROCESAR notificaciones de Mercado Pago (SDK PHP v3).

// --- 1. AJUSTE DE ZONA HORARIA (MÉXICO) ---
date_default_timezone_set('America/Mexico_City');
// -------------------------------------------

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $ruta_base_phpmailer = __DIR__ . '/../phpmailer/src/'; // Ruta desde la carpeta 'api/'
    
    if (file_exists($ruta_base_phpmailer . 'PHPMailer.php')) {
        require_once $ruta_base_phpmailer . 'PHPMailer.php';
        require_once $ruta_base_phpmailer . 'SMTP.php';
        require_once $ruta_base_phpmailer . 'Exception.php';
    } else {
        // Si no puede cargar, escribe en el log y muere
        @file_put_contents(__DIR__ . '/../logs/webhook.log', "[" . date("Y-m-d H:i:s") . "] ERROR CRÍTICO: No se encontraron los archivos de PHPMailer en: " . $ruta_base_phpmailer . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        exit;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conn.php';           // $conn (PDO)
require_once __DIR__ . '/config.php';            // MP_ACCESS_TOKEN, MP_WEBHOOK_SECRET, MP_WEBHOOK_LOG_FILE, MP_WEBHOOK_SKIP_VALIDATION?
require_once __DIR__ . '/lib/EmailService.php';  // EmailService::send()

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;

/* ======================= LOG ======================= */
if (!defined('MP_WEBHOOK_LOG_FILE')) {
    define('MP_WEBHOOK_LOG_FILE', __DIR__ . '/../logs/webhook.log'); // fallback
}
function log_webhook(string $message): void
{
    // Ahora usa la hora de México configurada arriba
    $ts = date("Y-m-d H:i:s");
    @file_put_contents(MP_WEBHOOK_LOG_FILE, "[$ts] $message" . PHP_EOL, FILE_APPEND);
}

/* =========== Lectura de headers (case-insensitive) =========== */
function read_headers_ci(): array
{
    $h = function_exists('getallheaders') ? getallheaders() : [];
    $norm = [];
    foreach ($h as $k => $v) $norm[strtolower($k)] = $v;
    // respaldo desde $_SERVER (proxys)
    foreach ($_SERVER as $k => $v) {
        if (strpos($k, 'HTTP_') === 0) {
            $key = strtolower(str_replace('_', '-', substr($k, 5)));
            if (!isset($norm[$key])) $norm[$key] = $v;
        }
    }
    return $norm;
}

/* =========== Validación HMAC (permisiva: v3 y legacy con topic) =========== */
function mp_validate_signature_permissive(
    string $eventId,
    ?string $topic,
    string $xSignature,
    string $xRequestId,
    string $secret
): bool {
    // X-Signature: "ts=..., v1=..."
    $ts = null; $v1 = null;
    foreach (explode(',', $xSignature) as $part) {
        [$k, $v] = array_map('trim', explode('=', $part, 2) + [null, null]);
        if ($k === 'ts') $ts = $v;
        if ($k === 'v1') $v1 = $v;
    }
    if (!$ts || !$v1) return false;

    $cands = [];
    // v3 (con y sin ';')
    $cands[] = "id:{$eventId};request-id:{$xRequestId};ts:{$ts};";
    $cands[] = "id:{$eventId};request-id:{$xRequestId};ts:{$ts}";
    if ($topic) {
        // legacy id->topic (con y sin ';')
        $cands[] = "id:{$eventId};topic:{$topic};request-id:{$xRequestId};ts:{$ts};";
        $cands[] = "id:{$eventId};topic:{$topic};request-id:{$xRequestId};ts:{$ts}";
        // legacy topic->id (con y sin ';')
        $cands[] = "topic:{$topic};id:{$eventId};request-id:{$xRequestId};ts:{$ts};";
        $cands[] = "topic:{$topic};id:{$eventId};request-id:{$xRequestId};ts:{$ts}";
    }

    foreach ($cands as $manifest) {
        $calc = hash_hmac('sha256', $manifest, $secret);
        if (hash_equals($v1, $calc)) return true;
    }

    $tests = [];
    foreach ($cands as $m) $tests[] = ['manifest' => $m, 'hmac' => hash_hmac('sha256', $m, $secret)];
    log_webhook("[DEBUG] Firmas candidatas NO coincidieron. v1={$v1} pruebas=" . json_encode($tests));
    return false;
}

/* =================== INICIO DE SOLICITUD =================== */
log_webhook("--- INICIO DE NOTIFICACIÓN ---");

try {
    if (!defined('MP_ACCESS_TOKEN') || !MP_ACCESS_TOKEN) {
        throw new Exception("MP_ACCESS_TOKEN no definido.");
    }
    if (!defined('MP_WEBHOOK_SECRET')) {
        throw new Exception("MP_WEBHOOK_SECRET no definido.");
    }
    $skipValidation = defined('MP_WEBHOOK_SKIP_VALIDATION') ? (bool)MP_WEBHOOK_SKIP_VALIDATION : false;

    MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

    $requestBody = file_get_contents('php://input') ?: '';
    $headers     = read_headers_ci();

    $xSignature  = $headers['x-signature']  ?? '';
    $xRequestId  = $headers['x-request-id'] ?? '';

    // Body (puede venir vacío)
    $data = $requestBody !== '' ? json_decode($requestBody, true) : null;
    if ($requestBody !== '' && !is_array($data)) {
        log_webhook("WARN: Body no es JSON válido. body=" . $requestBody);
        $data = null;
    }

    // Resolver ID del evento en varios formatos
    $eventId = $_GET['id']
        ?? $_GET['data.id']
        ?? ($data['data']['id'] ?? null)
        ?? ($data['id'] ?? null);

    // topic legacy (query) o type (body)
    $topic = $_GET['topic'] ?? ($data['type'] ?? null);

    if (!$eventId) {
        log_webhook("ERROR: eventId ausente. query=" . json_encode($_GET) . " body=" . $requestBody);
        // 200 para evitar reintentos eternos si vino malformado
        http_response_code(200);
        exit;
    }

    if (!$skipValidation) {
        if (!$xSignature || !$xRequestId) {
            log_webhook("ERROR: faltan headers para validar. X-Signature o X-Request-Id ausentes. hdrs=" . json_encode($headers));
            http_response_code(401);
            exit;
        }

        log_webhook("Validando firma... eventId={$eventId} requestId={$xRequestId} topic=" . ($topic ?: 'N/A'));
        $isValid = mp_validate_signature_permissive($eventId, $topic, $xSignature, $xRequestId, MP_WEBHOOK_SECRET);
        if (!$isValid) {
            log_webhook('ERROR firma inválida. sig="' . $xSignature . '" query=' . json_encode($_GET));
            http_response_code(401);
            exit;
        }
        log_webhook("ÉXITO: Firma de Webhook validada correctamente.");
    } else {
        log_webhook("AVISO: Validación de firma SKIPPEADA por MP_WEBHOOK_SKIP_VALIDATION=TRUE (solo DEV).");
    }
} catch (Exception $e) {
    log_webhook("ERROR FATAL (prevalidación): " . $e->getMessage());
    http_response_code(401);
    exit;
}

/* =================== PROCESAMIENTO =================== */
$data   = isset($data) && is_array($data) ? $data : json_decode($requestBody, true);
$action = $data['action'] ?? 'N/A';
$type   = $data['type']   ?? ($_GET['topic'] ?? 'N/A'); // legacy usa ?topic
log_webhook("Body decodificado. Acción: {$action} Tipo: {$type}");

if (($data['action'] ?? null) === 'payment.updated' || $type === 'payment') {
    $payment_id = $data['data']['id'] ?? ($_GET['id'] ?? null);

    if (!$payment_id) {
        log_webhook("ERROR: evento de pago sin id (data.id / ?id).");
        http_response_code(200);
        exit;
    }

    try {
        log_webhook("Obteniendo detalles del pago {$payment_id} desde MP...");
        $payClient = new PaymentClient();
        $payment   = $payClient->get($payment_id);
        $status    = $payment->status ?? 'N/D';
        log_webhook("Detalles obtenidos. Status: {$status}");

        if ($status === 'approved') {
            log_webhook("Pago APROBADO. Verificando duplicado...");
            if (!pago_ya_procesado_pdo($conn, $payment_id)) {
                log_webhook("Pago NUEVO. Resolviendo metadata para registrar en BD...");

                /* =======================================================
                 * RESOLUCIÓN DE METADATA (orden de prioridad):
                 * 1) $payment->metadata
                 * 2) Preference->metadata (si hay preference_id)
                 * 3) san_mp_pref por pref_id
                 * 4) san_mp_pref por external_reference (más reciente)
                 * ======================================================= */
                $metadata = (array)($payment->metadata ?? []);
                $preference_id      = $payment->preference_id ?? null;
                $external_reference = $payment->external_reference ?? null;

                if (empty($metadata)) {
                    log_webhook("[Metadata] Vacía en Payment. Intentando Preference...");
                    if ($preference_id) {
                        try {
                            $prefClient = new PreferenceClient();
                            $pref       = $prefClient->get($preference_id);
                            $metadata   = (array)($pref->metadata ?? []);
                            log_webhook("[Metadata] Cargada desde Preference. ¿Vacía? " . (empty($metadata) ? 'sí' : 'no'));
                        } catch (Exception $ePref) {
                            log_webhook("[Metadata] ERROR obteniendo Preference {$preference_id}: " . $ePref->getMessage());
                        }
                    } else {
                        log_webhook("[Metadata] Payment no trae preference_id.");
                    }
                }

                if (empty($metadata) && $preference_id) {
                    // Intentar recuperar de cache local san_mp_pref por pref_id
                    try {
                        $stmt = $conn->prepare("SELECT metadata_json FROM san_mp_pref WHERE pref_id = ? LIMIT 1");
                        $stmt->execute([$preference_id]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($row && !empty($row['metadata_json'])) {
                            $decoded = json_decode($row['metadata_json'], true);
                            if (is_array($decoded)) {
                                $metadata = $decoded;
                                log_webhook("[Metadata] Recuperada de BD por pref_id={$preference_id}.");
                            }
                        } else {
                            log_webhook("[Metadata] No hay fila en san_mp_pref para pref_id={$preference_id}.");
                        }
                    } catch (Exception $eDB) {
                        log_webhook("[Metadata] ERROR consultando san_mp_pref por pref_id: " . $eDB->getMessage());
                    }
                }

                if (empty($metadata) && $external_reference) {
                    // Último recurso: buscar por external_reference el más reciente
                    try {
                        $stmt = $conn->prepare("SELECT metadata_json FROM san_mp_pref WHERE external_reference = ? ORDER BY created_at DESC LIMIT 1");
                        $stmt->execute([$external_reference]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($row && !empty($row['metadata_json'])) {
                            $decoded = json_decode($row['metadata_json'], true);
                            if (is_array($decoded)) {
                                $metadata = $decoded;
                                log_webhook("[Metadata] Recuperada de BD por external_reference={$external_reference} (último registro).");
                            }
                        } else {
                            log_webhook("[Metadata] No hay fila en san_mp_pref para external_reference={$external_reference}.");
                        }
                    } catch (Exception $eDB2) {
                        log_webhook("[Metadata] ERROR consultando san_mp_pref por external_reference: " . $eDB2->getMessage());
                    }
                }

                // Log final del estado de metadata
                log_webhook("[Metadata] Resultado final: " . (empty($metadata) ? "VACÍA" : json_encode($metadata)));

                // Registrar pago
                $id_pago_guardado = registrar_pago_completo_pdo($conn, $payment, (array)$metadata);
                log_webhook("ÉXITO: Pago guardado en san_pagos ID: {$id_pago_guardado}");

                if ($id_pago_guardado) {
                    try {
                        log_webhook("Enviando email de confirmación...");
                        enviar_correo_confirmacion_pdo($conn, $id_pago_guardado, (array)$metadata);
                        log_webhook("Email de confirmación disparado.");
                    } catch (Exception $email_error) {
                        log_webhook("ERROR EMAIL (Pago {$id_pago_guardado}): " . $email_error->getMessage());
                    }
                }
            } else {
                log_webhook("ADVERTENCIA: Pago duplicado (Payment ID: {$payment_id}). Ignorado.");
            }
        } else {
            log_webhook("INFO: Status distinto de 'approved' ({$status}). Sin acciones.");
        }

        log_webhook("Webhook procesado OK. Respondiendo 200.");
        http_response_code(200);
        exit;
    } catch (MPApiException $e) {
        $api  = $e->getApiResponse();
        $http = is_array($api) && isset($api['status'])  ? (int)$api['status'] : 0;
        $body = is_array($api) && isset($api['content']) ? json_encode($api['content']) : $e->getMessage();

        log_webhook("MPApiException al consultar pago {$payment_id}. HTTP={$http} Detalle={$body}");

        if (in_array($http, [401, 403, 404], true)) {
            log_webhook("Aviso: evento de prueba o token/ambiente no coincide. Respondiendo 200 para evitar reintentos.");
            http_response_code(200);
        } else {
            http_response_code(500);
        }
        exit;
    } catch (Exception $e) {
        log_webhook("ERROR en procesamiento (PaymentID: {$payment_id}): " . $e->getMessage());
        http_response_code(500);
        exit;
    }
} elseif ($type === 'merchant_order') {
    // Si quisieras procesar merchant_order, colócalo aquí (por ahora solo 200 OK):
    log_webhook("INFO: Evento 'merchant_order' recibido. (No se procesa).");
    http_response_code(200);
    exit;
} else {
    log_webhook("INFO: Notificación no es 'payment'/'payment.updated' (action={$action}, type={$type}). Ignorando.");
    http_response_code(200);
    exit;
}

/* =================== FUNCIONES DE NEGOCIO =================== */

function registrar_pago_completo_pdo(PDO $conn, $payment, array $metadata)
{
    log_webhook("[registrar_pago_completo_pdo] Iniciando registro... Metadata CRUDA: " . json_encode($metadata));

    $id_socio           = (int)($metadata['id_socio_beneficiario'] ?? 0);
    $id_socio_pagador   = (int)($metadata['id_socio_pagador'] ?? 0);
    $fecha_ini_str      = $metadata['fecha_ini'] ?? null;
    $fecha_fin_str      = $metadata['fecha_fin'] ?? null;

    // Conversión de fechas dd-mm-YYYY -> YYYY-mm-dd
    $fecha_ini = null;
    if (!empty($fecha_ini_str)) {
        $dt_ini = DateTime::createFromFormat('d-m-Y', $fecha_ini_str);
        if ($dt_ini) $fecha_ini = $dt_ini->format('Y-m-d');
    }
    $fecha_fin = null;
    if (!empty($fecha_fin_str)) {
        $dt_fin = DateTime::createFromFormat('d-m-Y', $fecha_fin_str);
        if ($dt_fin) $fecha_fin = $dt_fin->format('Y-m-d');
    }

    // ESTA FECHA AHORA SERÁ HORA MÉXICO
    $fecha_mov      = date('Y-m-d H:i:s'); 
    
    $importe_pagado = (float)($metadata['monto_pagado'] ?? ($payment->transaction_amount ?? 0));
    $codigo_usado   = $metadata['codigo_usado'] ?? null;
    $tipo_promo     = $metadata['tipo_promo'] ?? null;
    $payment_id     = $payment->id ?? null;

    // Leídos de metadata (pueden venir vacíos)
    $id_usuario_sis    = (int)($metadata['id_usuario']  ?? 0);
    $id_empresa_sis    = (int)($metadata['id_empresa']  ?? 0);
    $id_servicio_leido = (int)($metadata['id_servicio'] ?? 0);

    log_webhook("[Registro] Vars (antes de validar FKs): Benef={$id_socio}, Pagador={$id_socio_pagador}, Importe={$importe_pagado}, Ini=" . ($fecha_ini ?: 'N/A') . ", Fin=" . ($fecha_fin ?: 'N/A') . ", Serv={$id_servicio_leido}, Usuario={$id_usuario_sis}, Empresa={$id_empresa_sis}");

    // ========= VALIDACIONES Y FALLBACKS DE FK =========

    // (A) Socio beneficiario debe existir
    $stmt = $conn->prepare("SELECT soc_id_socio, soc_id_empresa FROM san_socios WHERE soc_id_socio = ? LIMIT 1");
    $stmt->execute([$id_socio]);
    $rowSoc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$rowSoc) {
        throw new Exception("Socio beneficiario ($id_socio) no existe; no se puede registrar el pago.");
    }
    $soc_empresa = (int)($rowSoc['soc_id_empresa'] ?? 0);

    // (B) Empresa: si metadata no trae o no existe, usar la del socio
    if ($id_empresa_sis <= 0) {
        $id_empresa_sis = $soc_empresa;
        log_webhook("[Registro] id_empresa ausente en metadata. Usando soc_id_empresa={$id_empresa_sis}");
    }
    $stmt = $conn->prepare("SELECT 1 FROM san_empresas WHERE emp_id_empresa = ? LIMIT 1");
    $stmt->execute([$id_empresa_sis]);
    if (!$stmt->fetchColumn()) {
        // último fallback: la del socio
        $id_empresa_sis = $soc_empresa;
        log_webhook("[Registro] id_empresa inválida en metadata. Forzando a soc_id_empresa={$id_empresa_sis}");
    }

    // (C) Servicio debe existir
    if ($id_servicio_leido <= 0) {
        throw new Exception("Servicio no válido (id_servicio vacío).");
    }
    $stmt = $conn->prepare("SELECT 1 FROM san_servicios WHERE ser_id_servicio = ? LIMIT 1");
    $stmt->execute([$id_servicio_leido]);
    if (!$stmt->fetchColumn()) {
        throw new Exception("Servicio {$id_servicio_leido} no existe; no se puede registrar el pago.");
    }

    // (D) Usuario: si no existe, usar fallback de config; si tampoco existe, tomar el primer usuario activo
    $valid_user_id = null;

    if ($id_usuario_sis > 0) {
        $stmt = $conn->prepare("SELECT usua_id_usuario FROM san_usuarios WHERE usua_id_usuario = ? LIMIT 1");
        $stmt->execute([$id_usuario_sis]);
        if ($stmt->fetchColumn()) {
            $valid_user_id = $id_usuario_sis;
        } else {
            log_webhook("[Registro] id_usuario={$id_usuario_sis} no existe. Buscando fallback...");
        }
    } else {
        log_webhook("[Registro] id_usuario ausente en metadata. Buscando fallback...");
    }

    if (!$valid_user_id) {
        $fallback = defined('MP_FALLBACK_USER_ID') ? (int)MP_FALLBACK_USER_ID : 0;
        if ($fallback > 0) {
            $stmt = $conn->prepare("SELECT usua_id_usuario FROM san_usuarios WHERE usua_id_usuario = ? LIMIT 1");
            $stmt->execute([$fallback]);
            if ($stmt->fetchColumn()) {
                $valid_user_id = $fallback;
                log_webhook("[Registro] Usando MP_FALLBACK_USER_ID={$valid_user_id}");
            }
        }
    }

    if (!$valid_user_id) {
        // Toma el primer usuario activo como último recurso
        $stmt = $conn->query("SELECT usua_id_usuario FROM san_usuarios WHERE (usua_status = 'A' OR 1=1) ORDER BY usua_id_usuario ASC LIMIT 1");
        $valid_user_id = (int)$stmt->fetchColumn();
        if ($valid_user_id) {
            log_webhook("[Registro] Usando primer usuario existente usua_id_usuario={$valid_user_id}");
        } else {
            throw new Exception("No hay un usuario válido para asociar el pago (FK pag_id_usuario).");
        }
    }

    log_webhook("[Registro] Vars (validadas): Empresa={$id_empresa_sis}, Usuario={$valid_user_id}, Servicio={$id_servicio_leido}");

    // ========= FIN VALIDACIONES =========

    $conn->beginTransaction();
    log_webhook("[Registro] Transacción iniciada.");

    try {
        // 1) Código promocional (si aplica)
        if (!empty($codigo_usado)) {
            log_webhook("[Registro] Aplicando código: {$codigo_usado}");
            if ($tipo_promo === 'Individual') {
                $sql_update = "UPDATE san_codigos SET status = '0' WHERE codigo_generado = ?";
                $conn->prepare($sql_update)->execute([$codigo_usado]);
                log_webhook("[Registro] Código {$codigo_usado} (Individual) marcado status 0.");
            }

            $sql_insert = "INSERT INTO san_codigos_usados (id_socio, codigo_generado, fecha_usado, id_empresa)
                           VALUES (?, ?, ?, ?)";
            $conn->prepare($sql_insert)->execute([
                $id_socio,
                $codigo_usado,
                $fecha_mov, // <-- HORA MÉXICO
                $id_empresa_sis
            ]);
            log_webhook("[Registro] Código {$codigo_usado} insertado en san_codigos_usados.");
        }

        // 2) Monedero/bonificación si aplica (placeholder)
        log_webhook("[Registro] Lógica de monedero/bonificación (si aplica) completada.");

        // 3) Insertar pago en san_pagos
        log_webhook("[Registro] Insertando en san_pagos...");
        $sql_pago = "INSERT INTO san_pagos
            (pag_id_socio, pag_fecha_pago, pag_id_servicio, pag_fecha_ini, pag_fecha_fin,
             pag_tarjeta, pag_importe, pag_tipo_pago, pag_id_usuario, pag_id_empresa, pag_referencia_mp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $conn->prepare($sql_pago)->execute([
            $id_socio,
            $fecha_mov,        // <-- HORA MÉXICO
            $id_servicio_leido,
            $fecha_ini,
            $fecha_fin,
            $importe_pagado,       // pag_tarjeta
            $importe_pagado,       // pag_importe
            'T',                   // tarjeta
            $valid_user_id,        // <-- VALIDADO / FALLBACK
            $id_empresa_sis,       // <-- VALIDADO / FALLBACK (puede venir del socio)
            $payment_id
        ]);

        $id_pago_principal = $conn->lastInsertId();
        log_webhook("[Registro] ÉXITO: san_pagos ID={$id_pago_principal}");

        $conn->commit();
        log_webhook("[Registro] COMMIT.");
        return $id_pago_principal;
    } catch (Exception $e) {
        $conn->rollBack();
        log_webhook("[Registro] ROLLBACK por error: " . $e->getMessage());
        throw $e;
    }
}

function enviar_correo_confirmacion_pdo(PDO $conn, int $id_pago_principal, array $metadata): void
{
    log_webhook("[Email] Preparando email para Pago ID: {$id_pago_principal}");

    $id_socio_beneficiario = (int)($metadata['id_socio_beneficiario'] ?? 0);
    
    $stmt = $conn->prepare("
        SELECT CONCAT(soc_nombres, ' ', soc_apepat, ' ', IFNULL(soc_apemat,'')) AS nombre,
               soc_correo
        FROM san_socios
        WHERE soc_id_socio = ?
    ");
    $stmt->execute([$id_socio_beneficiario]);
    $socio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$socio) {
        log_webhook("[Email] ERROR: Socio {$id_socio_beneficiario} no encontrado.");
        return;
    }

    $correo = $socio['soc_correo'] ?? '';
    $nombre = trim($socio['nombre'] ?? 'Usuario');

    if ($correo) {
        log_webhook("[Email] Enviando a: {$correo}");

        // ========= OBTENER DATOS DEL PAGO PARA LA PLANTILLA =========
        $stmt = $conn->prepare("
            SELECT p.*, s.ser_descripcion, s.ser_clave
            FROM san_pagos p
            LEFT JOIN san_servicios s ON s.ser_id_servicio = p.pag_id_servicio
            WHERE p.pag_id_pago = ?
            LIMIT 1
        ");
        $stmt->execute([$id_pago_principal]);
        $pago = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pago) {
            log_webhook('[Email] ERROR: Pago no encontrado para correo.');
            return;
        }

        // Variables que usa la plantilla HTML
        $fecha_pago = $pago['pag_fecha_pago']
            ? date('d/m/Y H:i:s', strtotime($pago['pag_fecha_pago']))
            : 'N/A';

        $total_pagado = (float)$pago['pag_importe'];

        $servicio_nombre = trim(
            ($pago['ser_descripcion'] ?? 'Servicio') .
            (!empty($pago['ser_clave']) ? ' (' . $pago['ser_clave'] . ')' : '')
        );

        $fecha_ini = $pago['pag_fecha_ini']
            ? date('d/m/Y', strtotime($pago['pag_fecha_ini']))
            : 'N/A';

        $fecha_fin = $pago['pag_fecha_fin']
            ? date('d/m/Y', strtotime($pago['pag_fecha_fin']))
            : 'N/A';

        // Opcionales para saldo a favor (si los manejas en metadata)
        $abono_saldo       = isset($metadata['abono_saldo']) ? (float)$metadata['abono_saldo'] : 0;
        $nuevo_saldo_total = isset($metadata['nuevo_saldo_total']) ? (float)$metadata['nuevo_saldo_total'] : null;

        $asunto = "Confirmación de tu pago - Recibo No. {$id_pago_principal}";

        // ========= RENDER DE LA PLANTILLA =========
        ob_start();
        // Asegúrate que el archivo exista: /api/templates/payment_receipt_email.php
        include __DIR__ . '/templates/payment_confirmation_email.php';
        $mensaje = ob_get_clean();

        // ========= ENVÍO =========
        $emailSent = EmailService::send($correo, $nombre, $asunto, $mensaje);

        if ($emailSent) {
            log_webhook("[Email] EmailService::send ejecutado correctamente.");
        } else {
            log_webhook("[Email] ERROR: EmailService::send devolvió false.");
        }

    } else {
        log_webhook("[Email] Socio sin correo. No se envió email.");
    }
}

function pago_ya_procesado_pdo(PDO $conexion_pdo, $payment_id): bool
{
    $stmt = $conexion_pdo->prepare("SELECT 1 FROM san_pagos WHERE pag_referencia_mp = ? LIMIT 1");
    $stmt->execute([$payment_id]);
    return $stmt->fetchColumn() !== false;
}

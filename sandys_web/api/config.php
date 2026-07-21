<?php
// /api/config.php
// Archivo de configuración centralizado

// --- Configuración de Base de Datos ---
// (Puedes poner tus credenciales de conn.php aquí si quieres)
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'tu_db');
// define('DB_USER', 'tu_usuario');
// define('DB_PASS', 'tu_pass');

// --- Configuración de Correo (SMTP) ---
define('SMTP_HOST', 'smtp.ionos.mx');
define('SMTP_USER', getenv('SMTP_USER') ?: 'prueba@sandysgym.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: ''); // ¡Protege este archivo!
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'prueba@sandysgym.com');
define('SMTP_FROM_NAME', 'Sandys Gym');

// --- Configuración de Logs ---

// 1. Define la carpeta de logs
define('LOGS_DIR', __DIR__ . '/logs/');

// 2. Obtiene el mes y año actual (ej: "2025-10")
$current_year_month = date('Y-m'); // ¡Esta es la clave!

// 3. Define los nombres de archivo dinámicos
define('MAIL_SUCCESS_LOG_FILE', LOGS_DIR . 'mail_success_' . $current_year_month . '.log');
define('MAIL_ERROR_LOG_FILE', LOGS_DIR . 'mail_error_' . $current_year_month . '.log');

// (Eliminamos la antigua constante 'MAIL_LOG_FILE')

// --- Claves de Mercado Pago ---

// Access Token de la cuenta del cliente
define('MP_ACCESS_TOKEN', getenv('MP_ACCESS_TOKEN') ?: '');

// === WEBHOOK SECRET CONFIGURADO ===
// Clave secreta obtenida del panel de Webhooks de Mercado Pago del cliente
define('MP_WEBHOOK_SECRET', getenv('MP_WEBHOOK_SECRET') ?: '');

define('MP_WEBHOOK_LOG_FILE', LOGS_DIR . 'webhook' . $current_year_month . '.log');

define('BASE_URL_APP', 'https://sandysgym.com');
define('MP_WEBHOOK_SKIP_VALIDATION', false);


// config.php
// ...
// ID de usuario por defecto para registrar pagos vía webhook (debe existir en san_usuarios)
if (!defined('MP_FALLBACK_USER_ID')) {
  define('MP_FALLBACK_USER_ID', 1); // cámbialo a un usua_id_usuario real de tu BD
}

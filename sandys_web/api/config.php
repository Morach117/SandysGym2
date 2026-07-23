<?php
define('SMTP_HOST', 'smtp.ionos.mx');
define('SMTP_USER', getenv('SMTP_USER') ?: 'prueba@sandysgym.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'prueba@sandysgym.com');
define('SMTP_FROM_NAME', 'Sandys Gym');

define('LOGS_DIR', __DIR__ . '/logs/');
$current_year_month = date('Y-m');

define('MAIL_SUCCESS_LOG_FILE', LOGS_DIR . 'mail_success_' . $current_year_month . '.log');
define('MAIL_ERROR_LOG_FILE', LOGS_DIR . 'mail_error_' . $current_year_month . '.log');

define('MP_ACCESS_TOKEN', getenv('MP_ACCESS_TOKEN') ?: '');
define('MP_WEBHOOK_SECRET', getenv('MP_WEBHOOK_SECRET') ?: '');
define('MP_WEBHOOK_LOG_FILE', LOGS_DIR . 'webhook' . $current_year_month . '.log');

if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    define('BASE_URL_APP', $protocol . $_SERVER['HTTP_HOST']);
} else {
    define('BASE_URL_APP', 'https://sandysgym.com');
}

define('MP_WEBHOOK_SKIP_VALIDATION', false);

if (!defined('MP_FALLBACK_USER_ID')) {
    define('MP_FALLBACK_USER_ID', 1);
}
?>

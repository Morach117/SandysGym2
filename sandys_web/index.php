<?php
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax'
]);

date_default_timezone_set('America/Mexico_City');
require_once __DIR__ . '/config/session.php';

$publicPages = [
    'home', 'team', 'services', 'contact', 'classes', 'about_us',
    'login', 'registration', 'validate', 'reset_password', 'registro', 'success_stories', 'faq', 'accept_invite',
    'gracias', 'pago_fallido'
];

$privatePages = [
    'user_home', 'user_information', 'user_rutina',
    'user_calculator', 'user_pago_membresia', 'routine',
    'recibo', 'mis_pagos', 'user_admin_plan', 'user_referidos', 'user_monedero', 'progreso'
];

$userPanelPages = array_merge($privatePages, ['gracias', 'pago_fallido']);

$page = isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'home';
$loggedIn = isset($_SESSION['admin']);

// LOG DE DIAGNÓSTICO Y MITIGACIÓN ITP: Detectar si se perdió la sesión al regresar de Mercado Pago
if (in_array($page, ['gracias', 'pago_fallido']) && !$loggedIn && (isset($_GET['payment_id']) || isset($_GET['external_reference']))) {
    $logFile = __DIR__ . '/logs/mp_returns.log';
    $timestamp = date("Y-m-d H:i:s");
    
    if (!isset($_GET['restored'])) {
        $currentUrl = $_SERVER['REQUEST_URI'];
        $restoreUrl = $currentUrl . (strpos($currentUrl, '?') !== false ? '&' : '?') . 'restored=1';
        
        $getParams = json_encode($_GET);
        $serverInfo = "PHP: " . phpversion() . " | UA: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . " | Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'N/A');
        @file_put_contents($logFile, "[$timestamp] [DEBUG] Paso 1: Iniciando validacion ITP.\n   - GET: $getParams\n   - SERVER: $serverInfo\n   - TARGET URL: $restoreUrl\n", FILE_APPEND);
        
        try {
            echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Procesando Pago...</title>
    <link href='https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Muli:wght@400;600&display=swap' rel='stylesheet'>
    <style>
        body {
            background-color: #050505;
            color: #ffffff;
            font-family: 'Muli', sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .loader-container {
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }
        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top: 4px solid #ef4444;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 24px auto;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
        }
        h2 {
            font-family: 'Oswald', sans-serif;
            font-size: 24px;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #ffffff;
        }
        p {
            color: #a1a1aa;
            font-size: 15px;
            margin: 0;
            max-width: 300px;
            line-height: 1.5;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class='loader-container'>
        <div class='spinner'></div>
        <h2>Asegurando conexión...</h2>
        <p>Estamos validando tu transacción de forma segura con el servidor.</p>
    </div>
    <script>
        console.log('Ejecutando JS Reload hacia:', '" . $restoreUrl . "');
        setTimeout(function() {
            window.location.replace('" . $restoreUrl . "');
        }, 300); // Pequeño retraso para que la transición no sea brusca
    </script>
</body>
</html>";
            @file_put_contents($logFile, "[$timestamp] [DEBUG] Paso 2: HTML impreso correctamente. Saliendo de PHP.\n", FILE_APPEND);
        } catch (Throwable $e) {
            $err = $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine();
            @file_put_contents($logFile, "[$timestamp] [FATAL] Crash en Paso 1 (Generando HTML): $err\n", FILE_APPEND);
        }
        exit;
    } else {
        $getParams = json_encode($_GET);
        @file_put_contents($logFile, "[$timestamp] [DEBUG] Paso 3: JS Reload completado (restored=1 detectado).\n   - GET RECIBIDO: $getParams\n", FILE_APPEND);
        $payment_id = $_GET['payment_id'] ?? 'N/A';
        $ext_ref = $_GET['external_reference'] ?? 'N/A';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        $sid = session_id();
        $cookies = urldecode(http_build_query($_COOKIE, '', '; '));
        @file_put_contents($logFile, "[$timestamp] [INFO] Sesión no restaurada tras JS Reload. Renderizando vista pública.\n   - Ref: $ext_ref | Payment: $payment_id | SID: $sid | Cookies: [$cookies]\n", FILE_APPEND);
    }
}


// Redirect to login only if trying to access a strictly private page while logged out
if (in_array($page, $privatePages) && !$loggedIn) {
    header("Location: index.php?page=login");
    exit;
}

// Evitar que usuarios ya logueados entren a login o registro
if ($loggedIn && in_array($page, ['login', 'registration', 'registro', 'reset_password'])) {
    header("Location: index.php?page=user_home");
    exit;
}

if ($loggedIn && in_array($page, $userPanelPages)) {
    include('includes/user_panel_header.php'); 
} else {
    include('includes/public_header.php');
}

$allowedPages = array_merge($publicPages, $privatePages);

if (!in_array($page, $allowedPages)) {
    $page = 'home';
}

if (file_exists(__DIR__ . "/pages/$page.php")) {
    require(__DIR__ . "/pages/$page.php");
} else {
    require(__DIR__ . "/pages/home.php");
}

include('includes/footer.php');
?>
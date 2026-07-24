<?php
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax'
]);
session_start();

$publicPages = [
    'home', 'team', 'services', 'contact', 'classes', 'about_us',
    'login', 'registration', 'validate', 'reset_password', 'registro', 'success_stories', 'faq', 'accept_invite'
];

$privatePages = [
    'user_home', 'user_information', 'user_rutina',
    'user_calculator', 'user_pago_membresia', 'routine',
    'gracias', 'pago_fallido', 'recibo', 'mis_pagos', 'user_admin_plan', 'user_referidos', 'user_monedero', 'progreso'
];

$page = isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'home';
$loggedIn = isset($_SESSION['admin']);

if (in_array($page, $privatePages) && !$loggedIn) {
    header("Location: index.php?page=login");
    exit;
}

if ($loggedIn && in_array($page, $privatePages)) {
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
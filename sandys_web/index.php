<?php
// INICIAR SESIÓN CON PARÁMETROS SEGUROS
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => isset($_SERVER['HTTPS']), // True si está en HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// --- Lógica de enrutamiento y seguridad ---
$publicPages = [
    'home', 'team', 'services', 'contact', 'classes', 'about_us',
    'login', 'registration', 'validate', 'reset_password', 'registro','success_stories','faq', 'accept_invite'
    // 'gracias' y 'pago_fallido' movidas de aquí
];
$privatePages = [
    'user_home', 'user_information', 'user_rutina',
    'user_calculator', 'user_pago_membresia', 'routine',
    'gracias', 'pago_fallido','recibo', 'mis_pagos','user_admin_plan','user_referidos','user_monedero','progreso'// <-- AÑADIDAS AQUÍ
];
$page = isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'home';
$loggedIn = isset($_SESSION['admin']); // O la variable de sesión que uses para socios

// Redirigir si no está logueado y trata de acceder a una página privada
if (in_array($page, $privatePages) && !$loggedIn) {
    header("Location: index.php?page=login");
    exit;
}

// ===============================================
// LÓGICA PARA ELEGIR EL HEADER CORRECTO
// ===============================================
if ($loggedIn && in_array($page, $privatePages)) {
    // Las páginas 'gracias' y 'pago_fallido' ahora usarán el header de usuario
    include('includes/user_panel_header.php'); 
} else {
    include('includes/public_header.php');
}
// ===============================================

// 2. INCLUIR EL CONTENIDO DE LA PÁGINA (Preveniendo LFI con whitelist)
$allowedPages = array_merge($publicPages, $privatePages);

if (!in_array($page, $allowedPages)) {
    $page = 'home'; // Fallback por defecto si se provee una página no permitida
}

if (file_exists(__DIR__ . "/pages/$page.php")) {
    require(__DIR__ . "/pages/$page.php");
} else {
    // Si la página no existe, podrías mostrar un error 404
    require(__DIR__ . "/pages/home.php");
}

// 3. INCLUIR EL PIE DE PÁGINA
include('includes/footer.php');
?>
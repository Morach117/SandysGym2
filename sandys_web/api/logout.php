<?php
session_start(); // Iniciar sesión

// Limpiar todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión en el navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy(); // Destruir la sesión en el servidor

header("location:../"); // Redireccionar a index.php
?>
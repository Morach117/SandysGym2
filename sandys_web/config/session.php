<?php
/**
 * Módulo de Gestión Global de Sesiones
 * Protege contra pérdida de sesión en Safari/iOS (ITP) y previene "headers already sent"
 */

if (session_status() === PHP_SESSION_NONE) {
    // Si aún no se ha enviado output, configuramos las cookies de sesión
    if (!headers_sent()) {
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
        
        session_start();
    } else {
        // Fallback por si acaso se han enviado headers pero necesitamos iniciar la sesión de todas formas
        @session_start();
    }
}

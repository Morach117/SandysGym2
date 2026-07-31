<?php
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/session.php';
}

if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    exit;
}

$file = isset($_GET['f']) ? basename($_GET['f']) : 'default.png';
$path = realpath(__DIR__ . '/../../imagenes/avatar/' . $file);

if ($path && file_exists($path) && strpos($path, realpath(__DIR__ . '/../../imagenes/avatar')) === 0) {
    $mime = mime_content_type($path);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    readfile($path);
} else {
    $default = realpath(__DIR__ . '/../../imagenes/avatar/default.png');
    if ($default && file_exists($default)) {
        header('Content-Type: image/png');
        readfile($default);
    } else {
        http_response_code(404);
    }
}
?>
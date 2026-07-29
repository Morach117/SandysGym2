<?php
// Token de seguridad sencillo para que nadie más pueda ver tus logs
if (!isset($_GET['token']) || $_GET['token'] !== 'sandy123') {
    http_response_code(403);
    die("Acceso denegado. Ingresa el token correcto en la URL.");
}

$file1 = __DIR__ . '/logs/procesar_pago.log';
$file2 = __DIR__ . '/logs/mp_returns.log';

if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    if (file_exists($file1)) file_put_contents($file1, '');
    if (file_exists($file2)) file_put_contents($file2, '');
    header("Location: ver_logs.php?token=sandy123");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Visor de Logs - Sandy's Gym</title>
    <style>
        body { background: #050505; color: #fff; font-family: monospace; padding: 20px; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        h2 { color: #ef4444; margin: 0; }
        .btn-clear { background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; text-decoration: none; font-family: sans-serif; font-weight: bold; }
        .btn-clear:hover { background: #dc2626; }
        pre { background: #1a1a1a; color: #10b981; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 13px; line-height: 1.4; }
    </style>
</head>
<body>

    <div class="header-bar">
        <h2>📄 procesar_pago.log</h2>
        <a href="?token=sandy123&action=clear" class="btn-clear" onclick="return confirm('¿Seguro que deseas vaciar todos los logs?');">🗑 Limpiar Logs</a>
    </div>
    <pre><?php echo file_exists($file1) ? htmlspecialchars(file_get_contents($file1)) : "El archivo aún no existe."; ?></pre>

    <h2>📄 mp_returns.log</h2>
    <pre><?php echo file_exists($file2) ? htmlspecialchars(file_get_contents($file2)) : "El archivo aún no existe."; ?></pre>

    <script>
        // Auto recargar la página cada 5 segundos para ver los cambios en vivo (opcional)
        // setTimeout(() => window.location.reload(), 5000); 
    </script>
</body>
</html>

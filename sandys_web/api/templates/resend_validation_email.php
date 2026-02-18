<?php
// /api/templates/resend_validation_email.php
// Plantilla para reenviar el código.
// Espera que $name y $validation_code estén definidos.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tu Código de Validación</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 90%; max-width: 600px; margin: 20px auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
        .header { background: #222; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .code { font-size: 24px; font-weight: bold; color: #e74c3c; display: block; margin: 20px 0; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://sergym.com/imagenes/empresa_1.png" alt="Sandy's Gym Logo" style="max-width: 150px;">
        </div>
        <div class="content">
            <h1 style="color: #333;">Código de Validación</h1>
            <p>Hola <?php echo htmlspecialchars($name); ?>,</p>
            <p>Nos pediste reenviar tu código de validación. Es el siguiente:</p>
            <strong class="code"><?php echo htmlspecialchars($validation_code); ?></strong>
            <p>¡Gracias por unirte a Sandys Gym!</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Sandys Gym. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
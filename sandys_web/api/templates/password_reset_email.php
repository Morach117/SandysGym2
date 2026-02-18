<?php
// /api/templates/password_reset_email.php
// Plantilla moderna con CSS centrado para máxima compatibilidad.
// Espera que $asunto, $nombre_usuario y $resetLink estén definidos.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($asunto); ?></title>
    <style>
        /* --- Reseteo y Estilos Globales --- */
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            color: #51545e;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .wrapper {
            width: 100%;
            background-color: #f4f7f6;
            padding: 40px 0;
        }

        .container {
            max-width: 570px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        /* --- Cabecera --- */
        .header {
            text-align: center;
            padding: 40px 0;
            background-color: #222222;
        }
        .header img {
            max-width: 160px;
        }

        /* --- Contenido --- */
        .content {
            padding: 45px 50px;
            text-align: center; 
        }
        .content h1 {
            color: #333333;
            font-size: 24px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .content p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        /* --- Botón --- */
        /* Dejamos la clase .button por si acaso, pero lo importante está en el style="" de la etiqueta <a> */
        .button {
            display: inline-block;
            background-color: #e74c3c;
            color: #ffffff; /* <- Gmail ignora esto */
            font-size: 16px;
            font-weight: 600;
            padding: 14px 28px;
            text-decoration: none; /* <- Gmail ignora esto */
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(231, 76, 60, 0.3);
        }

        /* --- Pie de Página --- */
        .footer {
            padding: 40px 50px;
            background-color: #222222;
            color: #aaaaaa;
            text-align: center;
            font-size: 13px;
            line-height: 1.5;
        }
        .footer p {
            margin: 5px 0;
            padding: 0;
        }
        .footer a {
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <img src="https://sergym.com/imagenes/empresa_1.png" alt="Sandy's Gym Logo">
            </div>

            <div class="content">
                <h1>¿Necesitas una nueva contraseña?</h1>
                <p>Hola, <?php echo htmlspecialchars($nombre_usuario ?? 'Usuario'); ?>.</p>
                <p>Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el botón de abajo para continuar. Este enlace es válido por una hora.</p>
                
                <a href="<?php echo $resetLink; ?>" target="_blank" class="button" 
                   style="display: inline-block; background-color: #e74c3c; color: #ffffff; font-size: 16px; font-weight: 600; padding: 14px 28px; text-decoration: none; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(231, 76, 60, 0.3);">
                    Restablecer Contraseña
                </a>
                
                <p style="font-size: 14px; color: #888; margin-top: 30px;">
                    Si no solicitaste esto, puedes ignorar este correo de forma segura.
                </p>
            </div>

            <div class="footer">
                <p style="font-weight: bold; color: #ffffff;">SANDY'S GYM</p>
                <p>Av. Miguel Hidalgo 308, Bienestar Social, 29077 Tuxtla Gutiérrez, Chis.</p>
                <p>
                    <a href="https://www.facebook.com/gymsandy">Facebook</a>
                    <span>&nbsp;&middot;&nbsp;</span>
                    <a href="https://www.instagram.com/sandysgym/">Instagram</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
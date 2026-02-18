<?php
// /api/templates/validation_email.php
// Plantilla moderna y optimizada para el código de validación.
// Espera que $asunto, $name y $validation_code estén definidos.
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

        /* --- Caja de Código --- */
        /* Los estilos principales están en línea */
        .code-box {
            background-color: #f8f9fa;
            border: 1px solid #eeeeee;
            border-radius: 8px;
            padding: 20px;
            margin: 25px auto;
            max-width: 200px;
        }
        .code {
            font-size: 36px;
            font-weight: bold;
            color: #e74c3c;
            letter-spacing: 5px;
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
                <h1>¡Casi listo, <?php echo htmlspecialchars($name ?? 'Usuario'); ?>!</h1>
                <p>Gracias por registrarte. Usa el siguiente código para validar tu cuenta en nuestra aplicación:</p>
                
                <div class.="code-box" style="background-color: #f8f9fa; border: 1px solid #eeeeee; border-radius: 8px; padding: 20px; margin: 25px auto; max-width: 200px;">
                    <div class="code" style="font-size: 36px; font-weight: bold; color: #e74c3c; letter-spacing: 5px; font-family: 'Courier New', Courier, monospace;">
                        <?php echo htmlspecialchars($validation_code); ?>
                    </div>
                </div>
                
                <p style="font-size: 14px; color: #888; margin-top: 30px;">
                    Si no solicitaste este código, puedes ignorar este mensaje.
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
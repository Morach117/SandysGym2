<?php
// administrador/gym/secciones/cumple/plantilla_cumple.php
// Esta plantilla hereda las variables $data_nombre y $data_mensaje de correo.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Feliz Cumpleaños! - Sandy's Gym</title>
<style>
        body { margin: 0; padding: 0; width: 100% !important; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f6; color: #51545e; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .wrapper { width: 100%; background-color: #f4f7f6; padding: 40px 0; }
        .container { max-width: 570px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .header { text-align: center; padding: 40px 0; background-color: #222222; }
        .header img { max-width: 160px; }
        .content { padding: 45px 50px; text-align: left; }
        .content h1 { color: #333333; font-size: 24px; font-weight: 600; margin-top: 0; margin-bottom: 20px; text-align: center; }
        .content p { font-size: 16px; line-height: 1.6; margin-bottom: 20px; }
        .content .greeting { text-align: center; font-size: 18px; color: #333333; }
        
        .message-box { 
            background-color: #fcfcfc; 
            border-left: 4px solid #ef4444; 
            padding: 20px; 
            margin: 30px 0; 
            font-style: italic; 
            color: #333333;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            /* white-space removido para evitar conflicto con el HTML/nl2br entrante */
            word-wrap: break-word; 
        }

        /* Forzar contención de la imagen inyectada en todos los clientes de correo */
        .message-box img {
            max-width: 100% !important;
            height: auto !important;
            display: block;
            margin-top: 15px;
            border-radius: 8px;
        }

        .social-container { text-align: center; padding: 25px 0; background: #1a1a1a; }
        .social-icon { display: inline-block; margin: 0 8px; text-decoration: none; }
        .social-icon img { width: 30px; height: 30px; vertical-align: middle; }

        .footer { padding: 30px 50px; background-color: #222222; color: #aaaaaa; text-align: center; font-size: 13px; line-height: 1.5; }
        .footer p { margin: 5px 0; padding: 0; }
        .footer a { color: #ef4444; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <img src="https://sergym.com/imagenes/empresa_1.png" alt="Sandy's Gym Logo">
            </div>
            <div class="content">
                <h1>¡Feliz Cumpleaños! 🎉</h1>
                
                <p class="greeting">
                    <strong>¡Hola, <?= htmlspecialchars($data_nombre, ENT_QUOTES, 'UTF-8') ?>!</strong>
                </p>
                
                <div class="message-box">
                    <?= $data_mensaje ?>
                </div>
                
                <p style="text-align: center; margin-top: 30px;">
                    <strong>Un abrazo fuerte,</strong><br>El equipo de Sandy's Gym 💪
                </p>
            </div>

            <div class="social-container">
                <a href="https://www.facebook.com/gymsandy" target="_blank" class="social-icon">
                    <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" alt="Facebook">
                </a>
                <a href="https://www.instagram.com/sandysgym?igsh=MXU0c3NrNWZjZzMzYw==" target="_blank" class="social-icon">
                    <img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" alt="Instagram">
                </a>
                <a href="https://www.tiktok.com/@sandysgym" target="_blank" class="social-icon">
                    <img src="https://cdn-icons-png.flaticon.com/512/3046/3046121.png" alt="TikTok">
                </a>
                <a href="https://wa.me/529618465257" target="_blank" class="social-icon">
                    <img src="https://cdn-icons-png.flaticon.com/512/733/733585.png" alt="WhatsApp">
                </a>
            </div>

            <div class="footer">
                <p style="font-weight: bold; color: #ffffff;">SANDY'S GYM</p>
                <p>Av. Miguel Hidalgo 308, Bienestar Social, 29077 Tuxtla Gutiérrez, Chis.</p>
                <p>WhatsApp: <a href="https://wa.me/529618465257">961 846 5257</a></p>
            </div>
        </div>
    </div>
</body>
</html>
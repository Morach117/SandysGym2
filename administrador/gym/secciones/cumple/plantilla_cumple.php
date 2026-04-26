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
        
        /* Estilo adaptado para la caja del mensaje personalizado */
        .message-box { 
            background-color: #fcfcfc; 
            border-left: 4px solid #e74c3c; /* Acento rojo de tu diseño */
            padding: 20px; 
            margin: 30px 0; 
            font-style: italic; 
            color: #333333;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .footer { padding: 40px 50px; background-color: #222222; color: #aaaaaa; text-align: center; font-size: 13px; line-height: 1.5; }
        .footer p { margin: 5px 0; padding: 0; }
        .footer a { color: #e74c3c; text-decoration: none; font-weight: 600; }
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
                
                <p style="text-align: center;">
                    Hoy es un día muy especial y no queríamos dejar pasar la oportunidad de felicitarte.
                </p>
                
                <div class="message-box">
                    <?= $data_mensaje ?>
                </div>
                
                <p style="text-align: center;">
                    Te deseamos mucha salud, éxito y que sigas cumpliendo todas tus metas fitness. ¡Te esperamos pronto para entrenar con todo!
                </p>
                <p style="text-align: center; margin-top: 30px;">
                    <strong>Un abrazo fuerte,</strong><br>El equipo de Sandy's Gym 💪
                </p>
            </div>
            <div class="footer">
                <p style="font-weight: bold; color: #ffffff;">SANDY'S GYM</p>
                <p>Av. Miguel Hidalgo 308, Bienestar Social, 29077 Tuxtla Gutiérrez, Chis.</p>
                <p>
                    <a href="https://www.facebook.com/gymsandy">Facebook</a> <span>&nbsp;&middot;&nbsp;</span>
                    <a href="https://www.instagram.com/sandysgym/">Instagram</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
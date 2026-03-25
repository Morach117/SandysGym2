<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación a Sandy's Gym</title>
    <style>
        /* --- Reseteo y Estilos Globales --- */
        body {
            margin: 0; padding: 0; width: 100% !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7f6; color: #51545e;
            -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;
        }
        .wrapper { width: 100%; background-color: #f4f7f6; padding: 40px 0; }
        .container {
            max-width: 570px; margin: 0 auto; background-color: #ffffff;
            border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); overflow: hidden;
        }
        /* --- Cabecera --- */
        .header { text-align: center; padding: 40px 0; background-color: #222222; }
        .header img { max-width: 160px; }
        
        /* --- Contenido --- */
        .content { padding: 45px 50px; text-align: left; }
        .content h1 {
            color: #333333; font-size: 24px; font-weight: 600;
            margin-top: 0; margin-bottom: 20px; text-align: center;
        }
        .content p { font-size: 16px; line-height: 1.6; margin-bottom: 25px; }
        .content .greeting { text-align: center; font-size: 18px; color: #333333; }
        
        /* --- Botón de Acción --- */
        .button-container { text-align: center; margin: 35px 0; }
        .button {
            background-color: #e74c3c;
            color: #ffffff !important;
            text-decoration: none;
            padding: 15px 35px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            display: inline-block;
        }

        /* --- Enlace Alternativo --- */
        .fallback-link {
            font-size: 13px;
            color: #888888;
            word-break: break-all;
            text-align: center;
            border-top: 1px solid #eeeeee;
            padding-top: 20px;
            margin-top: 30px;
        }

        /* --- Pie de Página --- */
        .footer {
            padding: 40px 50px; background-color: #222222; color: #aaaaaa;
            text-align: center; font-size: 13px; line-height: 1.5;
        }
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
                <h1>¡Ven a entrenar a Sandy's Gym!</h1>
                
                <p class="greeting">
                    <strong>¡Hola!</strong>
                </p>
                
                <p style="text-align: center;">
                    Tu amigo(a) <strong>{{SOCIO_NOMBRE}}</strong> te ha invitado a formar parte de nuestra comunidad.
                </p>
                
                <p style="text-align: center;">
                    Regístrate en línea usando el siguiente botón para agilizar tu acceso a las instalaciones y disfrutar de nuestros servicios.
                </p>

                <div class="button-container">
                    <a href="{{LINK_REGISTRO}}" class="button">Regístrate Aquí</a>
                </div>
                
                <div class="fallback-link">
                    <p style="margin-bottom: 5px;">Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
                    <a href="{{LINK_REGISTRO}}" style="color: #e74c3c;">{{LINK_REGISTRO}}</a>
                </div>
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
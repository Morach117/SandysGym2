<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Código de Verificación</title>
</head>
<body style="margin: 0; padding: 0; background-color: #050505; font-family: 'Arial', sans-serif; color: #ffffff;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #050505; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" max-width="600px" border="0" cellspacing="0" cellpadding="0" style="background-color: #121212; border: 1px solid #2a2a2a; border-radius: 12px; max-width: 600px; text-align: center; overflow: hidden;">
                    <tr>
                        <td style="padding: 40px 0; background-color: #222222; border-bottom: 2px solid #ef4444;">
                            <img src="https://sergym.com/imagenes/empresa_1.png" alt="Sandy's Gym Logo" style="max-width: 160px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #ffffff; margin-top: 0;">Verifica tu cuenta</h2>
                            <p style="font-size: 16px; color: #cccccc; line-height: 1.6;">
                                Hola <strong><?php echo htmlspecialchars($row['soc_nombres']); ?></strong>, hemos encontrado tus datos en nuestro sistema.
                            </p>
                            <p style="font-size: 16px; color: #cccccc; line-height: 1.6;">
                                Usa el siguiente código para enlazar tu cuenta en la app:
                            </p>
                            <div style="margin: 40px 0;">
                                <span style="background-color: #2a2a2a; color: #ef4444; padding: 15px 30px; border-radius: 8px; font-weight: bold; font-size: 32px; letter-spacing: 5px;">
                                    <?php echo $otp; ?>
                                </span>
                            </div>
                            <hr style="border: none; border-top: 1px solid #333; margin: 30px 0;">
                            <p style="font-size: 12px; color: #777777;">
                                Si no solicitaste este código, puedes ignorar este correo.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 50px; background-color: #222222; color: #aaaaaa; text-align: center; font-size: 13px; line-height: 1.5;">
                            <p style="margin: 5px 0; padding: 0; font-weight: bold; color: #ffffff;">SANDY'S GYM</p>
                            <p style="margin: 5px 0; padding: 0;">Av. Miguel Hidalgo 308, Bienestar Social, 29077 Tuxtla Gutiérrez, Chis.</p>
                            <p style="margin: 5px 0; padding: 0;">
                                <a href="https://www.facebook.com/gymsandy" style="color: #ef4444; text-decoration: none; font-weight: 600;">Facebook</a>
                                <span>&nbsp;&middot;&nbsp;</span>
                                <a href="https://www.instagram.com/sandysgym/" style="color: #ef4444; text-decoration: none; font-weight: 600;">Instagram</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

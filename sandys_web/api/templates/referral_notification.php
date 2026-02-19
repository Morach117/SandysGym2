<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .header { background-color: #000000; padding: 20px; text-align: center; }
        .header h1 { color: #ef4444; margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 2px; }
        .content { padding: 30px 20px; color: #333333; line-height: 1.6; }
        .highlight-box { background-color: #ecfdf5; border: 1px solid #10b981; color: #065f46; padding: 15px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .amount { font-size: 28px; font-weight: bold; color: #10b981; display: block; margin-top: 5px; }
        .footer { background-color: #f4f4f4; padding: 15px; text-align: center; font-size: 12px; color: #888; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Sandys Gym</h1>
        </div>
        <div class="content">
            <h2>¡Hola, <?php echo htmlspecialchars($nombrePadrino); ?>! 👋</h2>
            <p>¡Te traemos excelentes noticias! Tu amigo(a) <strong><?php echo htmlspecialchars($nombreNuevoSocio); ?></strong> se acaba de inscribir al gimnasio usando tu código de referido.</p>
            
            <div class="highlight-box">
                ¡Hemos abonado una recompensa a tu cuenta!
                <span class="amount">+$<?php echo number_format($monto, 2); ?> MXN</span>
            </div>

            <p style="text-align: center;">
                Tu nuevo saldo disponible es:<br>
                <strong style="font-size: 18px;">$<?php echo number_format($nuevoSaldo, 2); ?> MXN</strong>
            </p>

            <p>Gracias por ser parte de nuestra comunidad y ayudarnos a crecer. ¡Sigue invitando amigos para ganar más!</p>
        </div>
        <div class="footer">
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
            <p>&copy; <?php echo date('Y'); ?> Sandys Gym. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
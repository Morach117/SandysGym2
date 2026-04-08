<?php
// api/templates/referral_notification.php
// Plantilla HTML para la notificación de bonos por referidos
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recompensa por Referido - Sandy's Gym</title>
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .header { text-align: center; padding: 30px 20px; background-color: #222222; }
        .content { padding: 35px 30px; color: #555555; line-height: 1.7; }
        .content h1 { color: #222222; font-size: 24px; margin-top: 0; }
        .card { border-left: 5px solid #28a745; background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .summary-table { width: 100%; margin-bottom: 0; border-collapse: collapse; }
        .summary-table td { padding: 12px 0; border-bottom: 1px solid #eeeeee; }
        .summary-table tr:last-child td { border-bottom: none; }
        .summary-table .label { font-weight: bold; color: #333; }
        .summary-table .value { text-align: right; font-weight: 500; }
        .footer { background-color: #222222; color: #aaaaaa; text-align: center; padding: 25px 20px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://sergym.com/imagenes/empresa_1.png" alt="Sandy's Gym Logo" style="max-width:150px;">
        </div>
        <div class="content">
            <h1>¡Felicidades, ganaste un bono!</h1>
            <p>Hola, <strong><?php echo htmlspecialchars($nombrePadrino); ?></strong>,<br>Te traemos excelentes noticias. Tu amigo(a) <strong><?php echo htmlspecialchars($nombreNuevoSocio); ?></strong> se acaba de inscribir al gimnasio usando tu código de referido.</p>
            
            <div class="card">
                <table class="summary-table">
                    <tr>
                        <td class="label">Bono de Recompensa</td>
                        <td class="value">+$<?php echo number_format($monto, 2); ?> MXN</td>
                    </tr>
                    <tr>
                        <td class="label" style="font-size: 18px;">Saldo Actual Total</td>
                        <td class="value" style="font-size: 18px; color: #28a745;"><strong>$<?php echo number_format($nuevoSaldo, 2); ?> MXN</strong></td>
                    </tr>
                </table>
            </div>
            
            <p>Gracias por ser parte de nuestra comunidad y ayudarnos a crecer. ¡Sigue invitando amigos para ganar más!</p>
            
            <p style="font-size: 12px; color: #999999; margin-top: 20px;">
                Este es un correo automático, por favor no respondas a este mensaje.
            </p>
        </div>
        <div class="footer">
            <p style="margin:5px 0;"><strong>SANDY'S GYM</strong></p>
            <p style="margin:5px 0;">Tuxtla Gutiérrez, Chiapas.</p>
        </div>
    </div>
</body>
</html>
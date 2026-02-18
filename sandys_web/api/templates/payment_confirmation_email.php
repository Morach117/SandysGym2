<?php
// /api/templates/payment_receipt_email.php
// Plantilla avanzada de confirmación de pago, basada en la captura.
//
// --- VARIABLES ESPERADAS ---
// $asunto: Asunto del correo.
// $nombre: Nombre completo del socio (ej. "RICARDO ENRIQUE ABARCA").
// $id_pago_principal: ID del recibo (ej. "14546").
// $fecha_pago: Fecha y hora del pago (ej. "09/10/2025 23:36:52").
// $total_pagado: Monto total pagado (ej. 400.00).
// $servicio_nombre: Nombre del servicio (ej. "MENSUALIDAD").
// $fecha_ini: Fecha de inicio de vigencia.
// $fecha_fin: Fecha de fin de vigencia.
//
// --- VARIABLES OPCIONALES (para Saldo a Favor) ---
// $abono_saldo: Monto que se abonó al saldo (ej. 40.00).
// $nuevo_saldo_total: Nuevo saldo total del monedero (ej. 240.00).
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($asunto ?? 'Confirmación de Pago'); ?></title>
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
            margin-top: 0; margin-bottom: 10px; text-align: center;
        }
        .content p { font-size: 16px; line-height: 1.6; margin-bottom: 25px; }
        .content .greeting { text-align: center; }
        
        /* --- Títulos de Sección --- */
        .section-title {
            font-size: 18px; font-weight: 600; color: #333;
            margin-top: 35px; margin-bottom: 15px;
            border-bottom: 1px solid #eeeeee; padding-bottom: 10px;
        }

        /* --- Tabla de Detalles (Genérica) --- */
        .details-table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        .details-table th, .details-table td {
            padding: 12px 0; border-bottom: 1px solid #eeeeee;
            font-size: 15px; line-height: 1.5; text-align: left;
        }
        .details-table th { color: #888888; font-weight: 500; }
        .details-table td { color: #333333; font-weight: 600; text-align: right; }
        
        /* Estilo especial para la fila de Total Pagado */
        .details-table .total-row th,
        .details-table .total-row td {
            font-size: 18px;
            font-weight: bold;
            padding-top: 15px;
            border-top: 2px solid #51545e;
            border-bottom: none; /* Quitar doble borde */
        }
        .details-table .total-row td {
            color: #e74c3c; /* Color rojo de la marca */
        }

        /* --- Caja de Saldo a Favor --- */
        .wallet-box {
            background-color: #f0fdf4; /* Verde muy claro */
            border: 1px solid #dcfce7; /* Borde verde claro */
            border-radius: 8px;
            padding: 25px;
            margin-top: 30px;
        }
        .wallet-title {
            font-size: 18px;
            font-weight: 600;
            color: #22c55e; /* Verde brillante */
            margin-top: 0;
            margin-bottom: 20px;
        }
        /* Usamos la misma tabla de detalles, pero sin borde inferior en la última fila */
        .wallet-box .details-table tr:last-child th,
        .wallet-box .details-table tr:last-child td {
            border-bottom: none;
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
                <h1>Confirmación de Pago</h1>
                
                <p class="greeting">
                    <strong>Hola, <?php echo htmlspecialchars($nombre ?? 'Usuario'); ?>.</strong><br>
                    ¡Gracias por renovar tu compromiso con nosotros! Tu pago se ha procesado correctamente.
                </p>
                
                <table class="details-table" role="presentation" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <th>Recibo No.</th>
                        <td><?php echo htmlspecialchars($id_pago_principal ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Fecha de Pago</th>
                        <td><?php echo htmlspecialchars($fecha_pago ?? 'N/A'); ?></td>
                    </tr>
                    <tr class="total-row">
                        <th>Total Pagado</th>
                        <td>$<?php echo htmlspecialchars(number_format($total_pagado ?? 0, 2)); ?></td>
                    </tr>
                </table>

                <?php if (isset($nuevo_saldo_total) && isset($abono_saldo) && $abono_saldo > 0): ?>
                    <div class="wallet-box">
                        <h3 class="wallet-title">¡Saldo a tu favor!</h3>
                        <table class="details-table" role="presentation" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <th>Abono por tu pago</th>
                                <td>+$<?php echo htmlspecialchars(number_format($abono_saldo, 2)); ?></td>
                            </tr>
                            <tr>
                                <th>Nuevo saldo total</th>
                                <td>$<?php echo htmlspecialchars(number_format($nuevo_saldo_total, 2)); ?></td>
                            </tr>
                        </table>
                    </div>
                <?php endif; ?>

                <h3 class="section-title">Detalle de tu Servicio</h3>
                <table class="details-table" role="presentation" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <th>Servicio</th>
                        <td><?php echo htmlspecialchars($servicio_nombre ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Periodo de Vigencia</th>
                        <td>
                            <?php echo htmlspecialchars($fecha_ini ?? 'N/A'); ?>
                            al 
                            <?php echo htmlspecialchars($fecha_fin ?? 'N/A'); ?>
                        </td>
                    </tr>
                </table>

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
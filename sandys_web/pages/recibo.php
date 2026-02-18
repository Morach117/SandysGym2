<?php
// ---------------------------------------------
//  Recibo de Pago Detallado (Con Cálculo de Descuento)
// ---------------------------------------------

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api/config.php'; // Configuración general

// 1. Obtener ID del pago
$idPagoInt = $_GET['id_pago'] ?? null;
$paymentId = $_GET['payment_id'] ?? null;

if (!$idPagoInt && !$paymentId) {
    echo "<div style='color:white; text-align:center; padding:50px; font-family:sans-serif;'>Error: Identificador de pago no proporcionado.</div>";
    exit;
}

// 2. Consulta detallada a la BD
try {
    // Consultamos datos del pago, del servicio original y del socio
    $sql = "
        SELECT 
            p.pag_id_pago,
            p.pag_fecha_pago,
            p.pag_importe,      -- Lo que pagó realmente
            p.pag_referencia_mp,
            p.pag_tipo_pago,
            p.pag_fecha_ini,
            p.pag_fecha_fin,
            s.ser_descripcion,
            s.ser_cuota,        -- Precio original del servicio
            soc.soc_id_socio,
            CONCAT(soc.soc_nombres, ' ', soc.soc_apepat, ' ', soc.soc_apemat) AS nombre_completo,
            soc.soc_correo
        FROM san_pagos p
        LEFT JOIN san_servicios s ON p.pag_id_servicio = s.ser_id_servicio
        LEFT JOIN san_socios soc ON p.pag_id_socio = soc.soc_id_socio
        WHERE ";

    if ($idPagoInt) {
        $sql .= "p.pag_id_pago = :id";
        $param = [':id' => $idPagoInt];
    } else {
        $sql .= "p.pag_referencia_mp = :id";
        $param = [':id' => $paymentId];
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($param);
    $pago = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pago) {
        echo "<div style='color:white; text-align:center; padding:50px; font-family:sans-serif;'>Error: No se encontró el recibo.</div>";
        exit;
    }
} catch (Exception $e) {
    die("Error de sistema: " . $e->getMessage());
}

// 3. Cálculos Matemáticos (Descuento)
$precioLista = (float)$pago['ser_cuota'];
$totalPagado = (float)$pago['pag_importe'];

// El descuento es la diferencia positiva entre el precio de lista y lo pagado
$descuento = max(0, $precioLista - $totalPagado);

// Definir el subtotal visual
if ($totalPagado > $precioLista) {
    // Caso raro: Recargos (pagó más que la lista) -> Subtotal es lo pagado
    $subtotal = $totalPagado;
    $descuento = 0;
} else {
    // Caso normal o con descuento -> Subtotal es el precio de lista
    $subtotal = $precioLista;
}

// 4. Preparar variables para la vista
$folio        = str_pad($pago['pag_id_pago'], 6, "0", STR_PAD_LEFT);
$fechaPago    = date("d/m/Y H:i", strtotime($pago['pag_fecha_pago']));
$referencia   = $pago['pag_referencia_mp'] ?? 'N/A';
$metodoPago   = ($pago['pag_tipo_pago'] == 'T') ? 'Tarjeta / MercadoPago' : 'Efectivo / Otro';
$socioNombre  = $pago['nombre_completo'];
$socioId      = $pago['soc_id_socio'];
$concepto     = $pago['ser_descripcion'];
$vigencia     = date("d/m/Y", strtotime($pago['pag_fecha_ini'])) . " al " . date("d/m/Y", strtotime($pago['pag_fecha_fin']));

// Formatear monedas
$fmtSubtotal  = number_format($subtotal, 2);
$fmtDescuento = number_format($descuento, 2);
$fmtTotal     = number_format($totalPagado, 2);

// URLs
$urlPdf    = "index.php?page=ticket&id_pago={$pago['pag_id_pago']}&format=pdf";
$urlVolver = "index.php?page=mis_pagos";

?>

<style>
    .receipt-wrapper {
        background-color: #0f0f0f;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px 20px;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        position: relative;
        z-index: 10;
    }

    .receipt-paper {
        background-color: #1e1e1e;
        /* Dark Mode */
        width: 100%;
        max-width: 700px;
        border-radius: 8px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
        overflow: hidden;
        color: #e0e0e0;
        border: 1px solid #333;
    }

    /* Cabecera */
    .receipt-header {
        background-color: #252525;
        padding: 30px;
        border-bottom: 2px dashed #444;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .company-info h2 {
        margin: 0;
        font-size: 24px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #ef4444;
        /* Rojo marca */
    }

    .company-info p {
        margin: 5px 0 0;
        font-size: 13px;
        color: #888;
    }

    .receipt-meta {
        text-align: right;
    }

    .receipt-meta .folio {
        font-size: 18px;
        font-weight: bold;
        color: #fff;
        display: block;
    }

    .receipt-meta .date {
        font-size: 13px;
        color: #aaa;
    }

    /* Cuerpo */
    .receipt-body {
        padding: 30px;
    }

    /* Sección Cliente */
    .client-section {
        margin-bottom: 30px;
        background-color: #252525;
        padding: 15px;
        border-radius: 6px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .client-info h4 {
        margin: 0 0 5px;
        font-size: 12px;
        color: #888;
        text-transform: uppercase;
    }

    .client-info p {
        margin: 0;
        font-size: 15px;
        font-weight: bold;
        color: #fff;
    }

    /* Tabla de Conceptos */
    .concept-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }

    .concept-table th {
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid #444;
        color: #888;
        font-size: 12px;
        text-transform: uppercase;
    }

    .concept-table td {
        padding: 15px 10px;
        border-bottom: 1px solid #333;
        font-size: 14px;
        color: #ddd;
    }

    .concept-table td.amount {
        text-align: right;
        font-weight: bold;
        color: #fff;
    }

    .concept-desc {
        display: block;
        font-weight: bold;
        font-size: 15px;
    }

    .concept-extra {
        display: block;
        font-size: 12px;
        color: #888;
        margin-top: 4px;
    }

    /* Totales */
    .totals-section {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 30px;
    }

    .totals-box {
        width: 250px;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        font-size: 14px;
    }

    .total-row.final {
        border-top: 2px solid #444;
        margin-top: 10px;
        padding-top: 10px;
        font-size: 18px;
        font-weight: bold;
        color: #22c55e;
    }

    /* Detalles MP */
    .mp-details {
        font-size: 12px;
        color: #666;
        border-top: 1px solid #333;
        padding-top: 15px;
        margin-bottom: 25px;
    }

    .mp-details strong {
        color: #888;
    }

    /* Botones */
    .receipt-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        padding-top: 20px;
        border-top: 1px dashed #444;
    }

    .btn-action {
        padding: 12px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 13px;
        font-weight: bold;
        text-transform: uppercase;
        transition: 0.2s;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-print {
        background-color: #ef4444;
        color: #fff;
    }

    .btn-print:hover {
        background-color: #dc2626;
        color: #fff;
    }

    .btn-back {
        background-color: #333;
        color: #ccc;
    }

    .btn-back:hover {
        background-color: #444;
        color: #fff;
    }

    .status-stamp {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        background: rgba(34, 197, 94, 0.2);
        color: #22c55e;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        border: 1px solid rgba(34, 197, 94, 0.4);
        margin-top: 5px;
    }

    @media (max-width: 600px) {

        .receipt-header,
        .client-section,
        .receipt-actions {
            flex-direction: column;
            text-align: center;
        }

        .receipt-meta {
            text-align: center;
        }

        .client-info {
            width: 100%;
        }

        .client-info[style*="right"] {
            text-align: center !important;
            margin-top: 15px;
        }

        .btn-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>
<br><br><br>
<div class="receipt-wrapper">
    <div class="receipt-paper">

        <div class="receipt-header">
            <div class="company-info">
                <h2>SANDY'S GYM</h2>
                <p>Comprobante de Pago Digital</p>
                <div class="status-stamp">PAGADO / APROBADO</div>
            </div>
            <div class="receipt-meta">
                <span class="folio">Folio: #<?php echo $folio; ?></span>
                <span class="date"><?php echo $fechaPago; ?></span>
            </div>
        </div>

        <div class="receipt-body">

            <div class="client-section">
                <div class="client-info">
                    <h4>Socio / Cliente</h4>
                    <p><?php echo htmlspecialchars($socioNombre); ?></p>
                    <span style="font-size:12px; color:#888;">ID: <?php echo $socioId; ?></span>
                </div>
                <div class="client-info" style="text-align: right;">
                    <h4>Método de Pago</h4>
                    <p><?php echo htmlspecialchars($metodoPago); ?></p>
                </div>
            </div>

            <table class="concept-table">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th style="text-align:right;">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <span class="concept-desc"><?php echo htmlspecialchars($concepto); ?></span>
                            <span class="concept-extra">Vigencia: <?php echo $vigencia; ?></span>
                        </td>
                        <td class="amount">$<?php echo $fmtSubtotal; ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="totals-section">
                <div class="totals-box">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo $fmtSubtotal; ?></span>
                    </div>

                    <?php if ($descuento > 0): ?>
                        <div class="total-row" style="color: #4ade80;">
                            <span>Descuento Aplicado:</span>
                            <span>-$<?php echo $fmtDescuento; ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="total-row final">
                        <span>Total Pagado:</span>
                        <span>$<?php echo $fmtTotal; ?> MXN</span>
                    </div>
                </div>
            </div>

            <div class="mp-details">
                <p><strong>Referencia MP:</strong> <?php echo htmlspecialchars($referencia); ?></p>
                <p><strong>UUID Transacción:</strong> <?php echo md5($referencia . $idPagoInt); ?></p>
            </div>

            <div class="receipt-actions">
                <a href="<?php echo $urlVolver; ?>" class="btn-action btn-back">
                    <i class="fa fa-arrow-left"></i> Volver al Historial
                </a>
            </div>

        </div>
    </div>
</div>
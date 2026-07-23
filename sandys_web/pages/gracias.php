<?php
require_once 'conn.php';

$status      = filter_input(INPUT_GET, 'collection_status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'unknown';
$externalRef = filter_input(INPUT_GET, 'external_reference', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'N/A';
$paymentId   = filter_input(INPUT_GET, 'payment_id', FILTER_SANITIZE_NUMBER_INT) ?? 'N/A';

$isApproved = ($status === 'approved');
$isPending  = ($status === 'pending' || $status === 'in_process');

$estadoTraducido = '';
switch(strtolower($status)) {
    case 'approved': $estadoTraducido = 'Aprobado'; break;
    case 'pending':
    case 'in_process': $estadoTraducido = 'Pendiente'; break;
    case 'rejected': $estadoTraducido = 'Rechazado'; break;
    case 'cancelled': $estadoTraducido = 'Cancelado'; break;
    case 'null':
    case '':
    case 'unknown':
        $estadoTraducido = 'No completado / Cancelado'; 
        break;
    default: $estadoTraducido = ucfirst($status);
}

$tipoOperacion = 'membresia';
$detallesExtra = [];

try {
    $stmt = $conn->prepare("SELECT metadata_json FROM san_mp_pref WHERE external_reference = :ref LIMIT 1");
    $stmt->execute([':ref' => $externalRef]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['metadata_json'])) {
        $metadata = json_decode($row['metadata_json'], true) ?: [];
        $tipoOperacion = $metadata['tipo_operacion'] ?? 'membresia';

        if ($tipoOperacion === 'recarga_monedero') {
            $detallesExtra['Tipo de Operación'] = 'Recarga de Monedero';
            $detallesExtra['Monto Base'] = '$' . number_format((float)($metadata['importe_recarga'] ?? 0), 2);
            
            if (!empty($metadata['incremento_monto']) && (float)$metadata['incremento_monto'] > 0) {
                $detallesExtra['Bono Promocional'] = '+$' . number_format((float)$metadata['incremento_monto'], 2) . ' (' . ($metadata['porcentaje_incremento'] ?? 0) . '%)';
            }
        } else {
            $detallesExtra['Tipo de Operación'] = 'Pago de Membresía';
            if (!empty($metadata['id_socio_beneficiario'])) {
                $detallesExtra['Socio Beneficiario'] = 'ID #' . htmlspecialchars((string)$metadata['id_socio_beneficiario']);
            }
            if (!empty($metadata['fecha_ini']) && !empty($metadata['fecha_fin'])) {
                $detallesExtra['Vigencia'] = htmlspecialchars($metadata['fecha_ini']) . ' al ' . htmlspecialchars($metadata['fecha_fin']);
            }
        }
    }
} catch (PDOException $e) {
    error_log("Error Frontend Fetch Metadata: " . $e->getMessage());
}

$titulo     = $isApproved ? '¡Pago Aprobado!' : ($isPending ? 'Pago Pendiente' : 'Estado del Pago');
$subTitulo  = $isApproved ? '¡Gracias por tu preferencia!' : ($isPending ? 'Estamos procesando tu pago' : 'Revisemos lo ocurrido');

$mensajePrincipal = $isApproved
    ? '¡Tu transacción se ha procesado exitosamente!'
    : ($isPending
        ? 'Tu transacción está pendiente de confirmación.'
        : "El estado de tu transacción es: <strong>{$estadoTraducido}</strong>");

if ($tipoOperacion === 'recarga_monedero') {
    $mensajeSecundario = $isApproved
        ? "Tu saldo ha sido acreditado. Tu comprobante es <strong>{$paymentId}</strong>."
        : ($isPending
            ? "Esperamos la confirmación del medio de pago. El saldo se abonará automáticamente. Referencia: <strong>{$externalRef}</strong>."
            : "Si tienes dudas, contáctanos con tu referencia <strong>{$externalRef}</strong>.");
    $cardIcon = $isApproved ? 'fa-solid fa-wallet' : ($isPending ? 'fa-solid fa-hourglass-half' : 'fa-solid fa-xmark');
} else {
    $mensajeSecundario = $isApproved
        ? "Tu membresía está en proceso de activación. Comprobante: <strong>{$paymentId}</strong>."
        : ($isPending
            ? "Esperamos la confirmación del medio de pago. Se activará automáticamente. Referencia: <strong>{$externalRef}</strong>."
            : "Si tienes dudas, contáctanos con tu referencia <strong>{$externalRef}</strong>.");
    $cardIcon = $isApproved ? 'fa-solid fa-check' : ($isPending ? 'fa-solid fa-hourglass-half' : 'fa-solid fa-xmark');
}

$cardIconBg  = $isApproved ? 'icon-approved' : ($isPending ? 'icon-pending' : 'icon-unknown');

$urlInicio   = 'index.php?page=user_home';
$urlPagos    = 'index.php?page=mis_pagos';
$urlRecibo   = $isApproved ? "index.php?page=recibo&payment_id={$paymentId}" : '#';
?>
<style>
    .gracias-container {
      --bg-base: #050505;
      --bg-panel: #1a1a1a;
      --brand-red: #ef4444;
      --brand-green: #10b981;
      --brand-orange: #F28123;
      --text-main: #ffffff;
      --text-muted: #a1a1aa;
      --border-radius-pill: 50rem;
      
      background-color: var(--bg-base);
      color: var(--text-muted);
      font-family: 'Muli', sans-serif;
      margin: 0;
      padding: 40px 0;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: calc(100vh - 200px);
      width: 100%;
    }

    .payment-wrapper {
      width: 100%;
      max-width: 600px;
      padding: 20px;
      animation: fadeInUp 0.6s ease-out forwards;
    }

    .payment-card {
      background-color: var(--bg-panel);
      border-radius: 16px;
      border: 1px solid rgba(255, 255, 255, 0.05);
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
      padding: 40px 32px;
      text-align: center;
    }

    .icon-wrap {
      width: 80px; height: 80px; 
      border-radius: 50%;
      display: inline-flex; align-items: center; justify-content: center;
      margin-bottom: 24px; 
      font-size: 36px; 
      color: #fff;
      box-shadow: 0 10px 20px rgba(0,0,0,.3);
    }
    
    .icon-approved { background-color: var(--brand-green); }
    .icon-pending  { background-color: var(--brand-orange); }
    .icon-unknown  { background-color: var(--brand-red); }

    .payment-card h3 {
      font-family: 'Oswald', sans-serif;
      font-size: 28px;
      margin-bottom: 12px;
      font-weight: 700;
      color: var(--text-main);
      text-transform: uppercase;
    }
    
    .payment-card p {
      font-size: 16px;
      line-height: 1.6;
      margin-bottom: 24px;
    }
    
    .payment-card p strong { color: var(--text-main); }

    .payment-details {
      background-color: var(--bg-base);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 12px;
      padding: 20px;
      margin: 24px 0 30px;
      text-align: left;
    }

    .payment-details .detail-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .payment-details .detail-row:last-child { border-bottom: none; }
    
    .payment-details .label { font-weight: 600; color: var(--text-muted); }
    .payment-details .value { font-weight: 700; color: var(--text-main); font-family: 'Oswald', sans-serif; text-align: right;}
    .value-highlight { color: var(--brand-green) !important; }

    .btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 12px 26px;
      border-radius: var(--border-radius-pill);
      font-family: 'Oswald', sans-serif;
      text-transform: uppercase;
      font-size: 14px;
      font-weight: 600;
      letter-spacing: 0.05em;
      text-decoration: none;
      transition: all 0.3s ease;
      cursor: pointer;
      border: none;
    }
    
    .btn i { margin-right: 8px; }

    .btn-primary { background-color: var(--brand-red); color: #fff; }
    .btn-primary:hover { background-color: #dc2626; transform: translateY(-2px); }

    .btn-secondary { background-color: #2a2a2a; color: var(--text-main); }
    .btn-secondary:hover { background-color: #333; transform: translateY(-2px); }

    .tips { margin-top: 25px; font-size: 13px; color: var(--text-muted); }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>

<div class="gracias-container">
  <div class="payment-wrapper">
    <div class="payment-card">
    
    <div class="icon-wrap <?= $cardIconBg ?>">
      <i class="<?= $cardIcon ?>"></i>
    </div>

    <h3><?= $mensajePrincipal ?></h3>
    <p><?= $mensajeSecundario ?></p>

    <div class="payment-details">
      <div class="detail-row">
        <span class="label">Estado</span>
        <span class="value"><?= $estadoTraducido ?></span>
      </div>
      <div class="detail-row">
        <span class="label">Referencia</span>
        <span class="value"><?= $externalRef ?></span>
      </div>
      <div class="detail-row">
        <span class="label">ID de Transacción</span>
        <span class="value"><?= $paymentId ?></span>
      </div>
      
      <?php foreach ($detallesExtra as $label => $val): ?>
        <div class="detail-row">
          <span class="label"><?= $label ?></span>
          <span class="value <?= strpos($label, 'Bono') !== false ? 'value-highlight' : '' ?>"><?= $val ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="btns">
      <?php if ($isApproved): ?>
        <?php if ($tipoOperacion === 'membresia'): ?>
          <a href="<?= $urlRecibo ?>" class="btn btn-primary"><i class="fa-solid fa-file-invoice"></i> Ver Recibo</a>
        <?php endif; ?>
        <a href="<?= $urlPagos ?>" class="btn btn-secondary"><i class="fa-solid fa-list"></i> Mi Cuenta</a>
      <?php elseif ($isPending): ?>
        <a href="<?= $urlPagos ?>" class="btn btn-primary"><i class="fa-solid fa-clock"></i> Ver Estado</a>
      <?php endif; ?>
      <a href="<?= $urlInicio ?>" class="btn btn-secondary"><i class="fa-solid fa-house"></i> Inicio</a>
    </div>

    <div class="tips">
      <?php if ($isApproved): ?>
        La actualización en tu cuenta puede demorar unos minutos. Conserva tu ID de pago.
      <?php elseif ($isPending): ?>
        Te notificaremos cuando el pago cambie a estado aprobado.
      <?php else: ?>
        Intenta nuevamente utilizando un método de pago distinto.
      <?php endif; ?>
    </div>

  </div>
</div>
</div>
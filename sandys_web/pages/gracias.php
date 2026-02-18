<?php
// ---------------------------------------------
//  Gracias / Retorno de Mercado Pago (Front)
//  v3 - Diseño Ultra-Moderno (Glassmorphism + Animaciones)
// ---------------------------------------------

// Lee parámetros devueltos por MP
$status      = $_GET['collection_status'] ?? 'unknown';
$externalRef = $_GET['external_reference'] ?? 'N/A';
$paymentId   = $_GET['payment_id'] ?? 'N/A';

// Sanitiza para salida segura
$safeStatus  = htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
$safeRef     = htmlspecialchars($externalRef, ENT_QUOTES, 'UTF-8');
$safePayId   = htmlspecialchars($paymentId, ENT_QUOTES, 'UTF-8');

// Deriva UI por estado
$isApproved = ($status === 'approved');
$isPending  = ($status === 'pending');

// --- Títulos y Mensajes ---
$titulo     = $isApproved ? '¡Pago Aprobado!' : ($isPending ? 'Pago Pendiente' : 'Estado del Pago');
$subTitulo  = $isApproved ? '¡Gracias por unirte!' : ($isPending ? 'Estamos procesando tu pago' : 'Revisemos lo ocurrido');

$mensajePrincipal = $isApproved
    ? '¡Tu pago ha sido aprobado exitosamente!'
    : ($isPending
        ? 'Tu pago está pendiente de confirmación.'
        : "El estado de tu pago es: <strong>{$safeStatus}</strong>.");

$mensajeSecundario = $isApproved
    ? "Tu membresía para <strong>{$safeRef}</strong> está activa. Tu comprobante es <strong>{$safePayId}</strong>."
    : ($isPending
        ? "Esperamos la confirmación del medio de pago (ej. OXXO). Se activará automáticamente. Referencia: <strong>{$safeRef}</strong>."
        : "Si tienes dudas, contáctanos con tu referencia <strong>{$safeRef}</strong> y el ID de pago <strong>{$safePayId}</strong>.");

// --- Clases de UI Dinámicas ---
$heroIcon    = $isApproved ? 'fa-check-circle' : ($isPending ? 'fa-hourglass-half' : 'fa-exclamation-triangle');
$heroBadge   = $isApproved ? 'status-approved' : ($isPending ? 'status-pending' : 'status-unknown');
$cardIcon    = $isApproved ? 'fa-check' : ($isPending ? 'fa-hourglass' : 'fa-times');
$cardIconBg  = $isApproved ? 'icon-approved' : ($isPending ? 'icon-pending' : 'icon-unknown');

// Extrae el ID de socio si el formato es "SOCIO_1234"
$soloSocioId = preg_match('/(\d+)/', $externalRef, $m) ? $m[1] : $externalRef;

// Rutas de acciones (ajusta a tus páginas reales)
$urlInicio   = 'index.php?page=user_home';
$urlPagos    = 'index.php?page=mis_pagos'; // Página de "Mi Cuenta > Pagos"
$urlRecibo   = $isApproved ? "index.php?page=recibo&payment_id={$safePayId}" : '#';

// include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title><?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?> - Sandy’s Gym</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <!-- Google Font (Las de tu plantilla) -->
  <link href="https://fonts.googleapis.com/css?family=Muli:300,400,500,600,700,800,900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Oswald:300,400,500,600,700&display=swap" rel="stylesheet">

  <!-- CSS de tu plantilla (Asumimos que se cargan aquí o en el header) -->
  <link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">
  <link rel="stylesheet" href="assets/css/font-awesome.min.css" type="text/css">
  <!-- <link rel="stylesheet" href="assets/css/flaticon.css" type="text/css"> -->
  <!-- <link rel="stylesheet" href="assets/css/owl.carousel.min.css" type="text/css"> -->
  <link rel="stylesheet" href="assets/css/style.css" type="text/css">

  <style>
    /* ---------------------------------- */
    /* --- Estilos Modernos v3 --- */
    /* ---------------------------------- */

    :root {
      --brand-red: #e31b23;
      --brand-red-dark: #881337;
      --success: #10b981;
      --success-dark: #065f46;
      --pending: #f59e0b;
      --pending-dark: #78350f;
      --text-light: #f9fafb;
      --text-muted: #a1a1aa;
      --bg-dark-1: #111111;
      --bg-dark-2: #1a1a1a;
      --bg-dark-3: #252525;
    }

    /* --- Keyframes para Animaciones --- */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes bgZoom {
      from { transform: scale(1.05); }
      to { transform: scale(1); }
    }

    /* --- Base --- */
    body {
      background-color: var(--bg-dark-1);
      color: var(--text-muted);
      font-family: 'Muli', sans-serif;
    }

    /* --- Hero Section --- */
    .hero-thanks.set-bg {
      position: relative;
      background-position: center;
      background-size: cover;
      padding: 140px 0 120px;
      overflow: hidden;
    }
    .hero-thanks .hero-background-image {
      /* El JS de 'set-bg' pondrá la imagen aquí */
      position: absolute;
      inset: 0;
      background-size: cover;
      background-position: center;
      animation: bgZoom 6s ease-out forwards;
    }
    .hero-thanks::before {
      content: "";
      position: absolute; inset: 0;
      background: linear-gradient(180deg, rgba(17,17,17,0.5), rgba(17,17,17,0.85));
      z-index: 1;
    }
    .hero-thanks .container { 
      position: relative; 
      z-index: 2; 
    }
    
    .hero-thanks .hi-text {
        animation: fadeInUp 0.8s ease-out;
    }

    .hero-thanks .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 10px 20px;
      border-radius: 60px;
      font-weight: 700;
      font-size: 13px;
      font-family: 'Oswald', sans-serif;
      letter-spacing: .05em;
      text-transform: uppercase;
      color: var(--text-light);
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(8px);
    }
    .status-badge i { font-size: 16px; }
    .status-approved { color: var(--success); }
    .status-pending  { color: var(--pending); }
    .status-unknown  { color: var(--brand-red); }

    .hero-thanks .hi-text h1 {
      margin-top: 15px;
      color: var(--text-light);
      font-family: 'Oswald', sans-serif;
      font-size: 52px;
      font-weight: 700;
      text-transform: uppercase;
      line-height: 1.2;
      text-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }

    /* --- Tarjeta Principal (Glassmorphism) --- */
    .payment-card-section {
        /* 'spad' de la plantilla ya da padding. Esto centra la tarjeta. */
        margin-top: -90px;
        position: relative;
        z-index: 10;
        animation: fadeInUp 0.8s 0.2s ease-out forwards;
        opacity: 0; /* Inicia oculto para animación */
    }

    .payment-card {
      background: rgba(37, 37, 37, 0.75); /* Fondo de cristal oscuro */
      backdrop-filter: blur(16px) saturate(180%);
      -webkit-backdrop-filter: blur(16px) saturate(180%);
      border-radius: 20px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      box-shadow: 0 15px 40px rgba(0,0,0,0.4);
      padding: 40px 32px;
    }

    .payment-card .icon-wrap {
      width: 90px; height: 90px; border-radius: 50%;
      display: inline-flex; align-items: center; justify-content: center;
      margin-bottom: 24px; 
      font-size: 42px; 
      color: #fff;
      box-shadow: 0 10px 30px rgba(0,0,0,.3);
    }
    /* Gradientes para iconos */
    .icon-approved { background: linear-gradient(135deg, var(--success), var(--success-dark)); }
    .icon-pending  { background: linear-gradient(135deg, var(--pending), var(--pending-dark)); }
    .icon-unknown  { background: linear-gradient(135deg, var(--brand-red), var(--brand-red-dark)); }

    .payment-card h3 {
      font-family: 'Oswald', sans-serif;
      font-size: 30px;
      margin-bottom: 12px;
      font-weight: 700;
      letter-spacing: .2px;
      color: var(--text-light);
      text-transform: uppercase;
    }
    .payment-card p {
      color: var(--text-muted);
      font-size: 17px;
      line-height: 1.7;
      margin-bottom: 24px;
    }
    .payment-card p strong {
      color: var(--text-light);
      font-weight: 600;
    }

    /* --- Detalles del Pago --- */
    .payment-details {
      background: rgba(17, 17, 17, 0.6); /* Más oscuro */
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 20px;
      margin: 24px 0 30px;
    }
    .payment-details .row {
      padding: 8px 0;
    }
    .payment-details .row + .row { 
        border-top: 1px solid rgba(255, 255, 255, 0.07);
        padding-top: 16px;
        margin-top: 8px;
    }
    .payment-details .label { 
      color: var(--text-muted); 
      font-weight: 600;
      font-size: 15px;
    }
    .payment-details .value { 
      color: var(--text-light); 
      font-weight: 700;
      font-size: 16px;
      font-family: 'Oswald', sans-serif;
      letter-spacing: .03em;
    }

    /* --- Botones --- */
    .btns { display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; }
    
    /* Botón Primario (Heredado de tu plantilla, pero lo reforzamos) */
    .primary-btn {
      padding: 14px 28px;
      font-family: 'Oswald', sans-serif;
      text-transform: uppercase;
      font-size: 15px;
      letter-spacing: .05em;
      border-radius: 8px;
      background: var(--brand-red); /* Color de tu plantilla */
      color: #fff;
      transition: all .3s ease;
      border: none;
      box-shadow: 0 8px 25px rgba(227, 27, 35, 0.25);
    }
    .primary-btn:hover { 
      transform: translateY(-3px); 
      box-shadow: 0 12px 30px rgba(227, 27, 35, 0.4); 
      color: #fff;
      background: #f12c35;
    }
    
    /* Botón Secundario (Invertido) */
    .secondary-btn {
      display: inline-block; padding: 14px 28px; border-radius: 8px;
      font-weight: 700; text-decoration: none; transition: all .3s ease;
      font-family: 'Oswald', sans-serif;
      text-transform: uppercase;
      font-size: 15px;
      letter-spacing: .05em;
      background: #fff; 
      color: var(--bg-dark-1);
      box-shadow: 0 8px 25px rgba(255, 255, 255, 0.1);
    }
    .secondary-btn:hover { 
      transform: translateY(-3px); 
      box-shadow: 0 12px 30px rgba(255, 255, 255, 0.2); 
      color: var(--bg-dark-1);
    }

    /* Botón Ghost (Borde) */
    .ghost-btn {
      display: inline-block; padding: 14px 28px; border-radius: 8px;
      font-weight: 700; text-decoration: none; transition: all .3s ease;
      font-family: 'Oswald', sans-serif;
      text-transform: uppercase;
      font-size: 15px;
      letter-spacing: .05em;
      background: transparent; 
      color: var(--text-muted); 
      border: 2px solid var(--bg-dark-3);
    }
    .ghost-btn:hover { 
      transform: translateY(-3px);
      background: rgba(255, 255, 255, 0.05); 
      color: var(--text-light);
      border-color: var(--text-muted);
    }

    .btns i { margin-right: 8px; }

    /* --- Tips --- */
    .tips {
      margin-top: 30px;
      color: var(--text-muted);
      font-size: 14px;
      line-height: 1.6;
    }
    .tips strong { color: var(--pending); }

    /* --- Confeti (Tu JS, pero con colores de la paleta) --- */
    .confetti {
      position: fixed; inset: 0; pointer-events: none; z-index: 9999;
    }
    .confetti i {
      position: absolute;
      width: 8px; height: 12px; opacity: 0; transform: translateY(-20px) rotate(0deg);
      animation: drop 1.8s ease-out forwards;
    }
    @keyframes drop {
      10% { opacity: 1; }
      100% { transform: translateY(110vh) rotate(360deg); opacity: .9; }
    }

    /* --- Responsive --- */
    @media (max-width: 768px) {
        .hero-thanks .hi-text h1 { font-size: 40px; }
        .payment-card { padding: 32px 24px; }
    }
    @media (max-width: 576px) {
      .hero-thanks { padding: 120px 0 100px; }
      .hero-thanks .hi-text h1 { font-size: 34px; }
      .payment-card { padding: 28px 20px; }
      .payment-card h3 { font-size: 24px; }
      .payment-details .label { font-size: 14px; }
      .payment-details .value { font-size: 15px; }
      .btns { flex-direction: column; gap: 12px; }
      .primary-btn, .secondary-btn, .ghost-btn { width: 100%; }
    }
  </style>
</head>
<body>


<!-- HERO dinámico -->

<br>
<br><br>
<br>
<br><br>

<!-- Tarjeta central -->
<section class="payment-card-section spad">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-md-11">
        <div class="payment-card text-center">
          
          <div class="icon-wrap <?php echo $cardIconBg; ?>">
            <i class="fa <?php echo $cardIcon; ?>"></i>
          </div>

          <h3><?php echo $mensajePrincipal; ?></h3>
          <p><?php echo $mensajeSecundario; ?></p>

          <div class="payment-details text-left">
            <div class="row">
              <div class="col-5 label">Estado</div>
              <div class="col-7 value text-right">
                <?php echo ucfirst($safeStatus); ?>
              </div>
            </div>
            <div class="row">
              <div class="col-5 label">Referencia</div>
              <div class="col-7 value text-right">
                <?php echo $safeRef; ?>
              </div>
            </div>
            <div class="row">
              <div class="col-5 label">ID de pago</div>
              <div class="col-7 value text-right">
                <?php echo $safePayId; ?>
              </div>
            </div>
            <?php if ($soloSocioId && $soloSocioId !== $safeRef): ?>
            <div class="row">
              <div class="col-5 label">ID Socio</div>
              <div class="col-7 value text-right">
                #<?php echo htmlspecialchars($soloSocioId, ENT_QUOTES, 'UTF-8'); ?>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <div class="btns">
            <?php if ($isApproved): ?>
              <a href="<?php echo $urlRecibo; ?>" class="primary-btn">
                <i class="fa fa-download"></i> Ver Recibo
              </a>
              <a href="<?php echo $urlPagos; ?>" class="secondary-btn">
                <i class="fa fa-list"></i> Mis Pagos
              </a>
            <?php elseif ($isPending): ?>
              <a href="<?php echo $urlPagos; ?>" class="primary-btn">
                <i class="fa fa-clock-o"></i> Ver estado
              </a>
            <?php endif; ?>
              <a href="<?php echo $urlInicio; ?>" class="ghost-btn">
                <i class="fa fa-home"></i> Volver al inicio
              </a>
          </div>

          <div class="tips">
            <?php if ($isApproved): ?>
              Si no ves reflejado el pago en tu app, se actualizará en breve. Conserva tu ID de pago.
            <?php elseif ($isPending): ?>
              Cuando el pago cambie a <strong>aprobado</strong>, te enviaremos un correo de confirmación.
            <?php else: ?>
              Si el pago fue rechazado, puedes intentar nuevamente con otro método de pago.
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php // include 'includes/footer.php'; ?>




<script>
// Confeti muy simple sólo si Approved (sin librerías)
<?php if ($isApproved): ?>
(function(){
  // Colores de la paleta de esta página
  const colors = ['var(--brand-red)', 'var(--success)', '#fff', 'var(--pending)', '#06b6d4', '#a855f7'];
  const ctn = document.createElement('div');
  ctn.className = 'confetti';
  document.body.appendChild(ctn);

  for (let i=0; i<90; i++){
    const s = document.createElement('i');
    const size = 6 + Math.random()*10;
    const color = colors[Math.floor(Math.random()*colors.length)];
    
    s.style.left = (Math.random()*100)+'vw';
    s.style.top  = (-10 - Math.random()*40)+'px';
    s.style.width  = size+'px';
    s.style.height = (size*1.4)+'px';
    // Aplica el color, manejando la variable CSS
    s.style.background = color.startsWith('var(') ? getComputedStyle(document.documentElement).getPropertyValue(color.slice(4, -1).trim()) : color;
    s.style.borderRadius = (Math.random()>.5 ? '2px' : '50%');
    s.style.animationDelay = (Math.random()*0.8)+'s';
    s.style.transform = 'translateY(-20px) rotate('+(Math.random()*180)+'deg)';
    ctn.appendChild(s);
  }
  // Limpia luego de unos segundos
  setTimeout(()=> {
    if (ctn) ctn.remove();
  }, 2400);
})();
<?php endif; ?>


</script>
</body>
</html>
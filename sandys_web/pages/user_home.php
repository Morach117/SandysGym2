<?php
// --- INCLUDES ---
require_once __DIR__ . '/../conn.php';
include('./api/select_data.php'); 

// --- CONFIGURACIÓN ---
date_default_timezone_set('America/Mexico_City');

// 1. Inicializar variables
$mensajeAlerta = '';
$estadoMembresia = '';
$fechaVencimientoTexto = ''; 
$claseEstado = '';
$iconoEstado = '';
$miembroActivo = false;
$mostrarAdminPlan = false; // Por defecto oculto
$nombreSocio = htmlspecialchars(explode(' ', $selSocioData['soc_nombres'])[0]); 

// 2. Consultar el último pago ACTIVO del socio
$socioId = $selSocioData['soc_id_socio'];
$query = "SELECT pag_fecha_fin, pag_id_servicio 
          FROM san_pagos 
          WHERE pag_id_socio = :socioId AND pag_status = 'A' 
          ORDER BY pag_fecha_fin DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':socioId', $socioId, PDO::PARAM_INT);
$stmt->execute();
$pagoData = $stmt->fetch(PDO::FETCH_ASSOC);

$fechaFin = $pagoData['pag_fecha_fin'] ?? null;
$idServicioActivo = $pagoData['pag_id_servicio'] ?? 0;

// 3. CALCULAR ESTADO DE VIGENCIA (Primero calculamos si está activo)
if ($fechaFin) {
    $currentDate = new DateTime(); // Fecha actual
    $fechaFinDate = new DateTime($fechaFin);
    
    // Reseteamos horas para comparar solo fechas (evita errores por hora exacta)
    $currentDate->setTime(0, 0, 0);
    $fechaFinDate->setTime(0, 0, 0);

    $fechaFinFormateada = $fechaFinDate->format('d/m/Y');

    if ($currentDate > $fechaFinDate) {
        // --- CASO 1: VENCIDA ---
        $miembroActivo = false;
        $mensajeAlerta = "Tu membresía ha finalizado. ¡Reactívala para no perderte de nada!";
        $estadoMembresia = "Membresía Finalizada";
        $fechaVencimientoTexto = "Venció el " . $fechaFinFormateada; 
        $claseEstado = "status-expired";
        $iconoEstado = "fa-times-circle";
    } else {
        // --- CASO 2: VIGENTE (ACTIVA) ---
        $miembroActivo = true;
        $interval = $currentDate->diff($fechaFinDate);
        $diasRestantes = $interval->days;
        $fechaVencimientoTexto = "Vigente hasta el " . $fechaFinFormateada;

        if ($diasRestantes <= 3) {
            $mensajeAlerta = "¡Atención! A tu membresía le quedan solo $diasRestantes día(s). ¡Renuévala pronto!";
            $estadoMembresia = "Vence en $diasRestantes día(s)";
            $claseEstado = "status-warning";
            $iconoEstado = "fa-exclamation-triangle";
        } else {
            $estadoMembresia = "Membresía Activa";
            $claseEstado = "status-active";
            $iconoEstado = "fa-check-circle";
        }
    }
} else {
    // --- CASO 3: SIN REGISTROS ---
    $miembroActivo = false;
    $estadoMembresia = "Sin membresía activa";
    $claseEstado = "status-inactive";
    $iconoEstado = "fa-user-times"; 
    $mensajeAlerta = "No tienes una membresía activa. ¡Adquiere una para acceder a todos los beneficios!";
}

// 4. VALIDACIÓN ESTRICTA PARA MOSTRAR "ADMINISTRAR PLAN"
// Reglas: Debe ser uno de los planes permitidos Y la membresía debe estar vigente hoy.
$serviciosPlanFamiliar = [123, 125, 126];

if ($miembroActivo && in_array($idServicioActivo, $serviciosPlanFamiliar)) {
    $mostrarAdminPlan = true;
}
?>

<style>
    /* Base */
    body { background-color: #0f0f0f; color: #e0e0e0; font-family: 'Muli', sans-serif; }

    /* Hero Section */
    .user-hero {
        position: relative; padding: 80px 0 60px;
        background: linear-gradient(0deg, #0f0f0f 0%, rgba(15,15,15,0.7) 50%, #0f0f0f 100%), url('./assets/img/hero/hero-user.jpg') no-repeat center center;
        background-size: cover; border-bottom: 1px solid #222;
    }
    .hero-content { position: relative; z-index: 2; text-align: center; }
    .hero-title { font-family: 'Oswald', sans-serif; font-size: 42px; color: #fff; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1.5px; text-shadow: 0 4px 15px rgba(0,0,0,0.7); }

    /* Badge Estado */
    .membership-badge { display: inline-flex; align-items: center; gap: 8px; padding: 10px 25px; border-radius: 50px; font-size: 15px; font-weight: 700; text-transform: uppercase; backdrop-filter: blur(5px); box-shadow: 0 2px 10px rgba(0,0,0,0.3); }
    .membership-expiry { display: block; margin-top: 8px; font-size: 14px; color: #bbb; font-weight: 500; letter-spacing: 0.5px; }

    .status-active { background: rgba(34, 197, 94, 0.25); color: #4ade80; border: 1px solid #4ade80; }
    .status-warning { background: rgba(234, 179, 8, 0.25); color: #facc15; border: 1px solid #facc15; }
    .status-expired { background: rgba(239, 68, 68, 0.25); color: #f87171; border: 1px solid #f87171; }
    .status-inactive { background: rgba(107, 114, 128, 0.25); color: #9ca3af; border: 1px solid #9ca3af; }

    /* Alertas */
    .alert-custom { background: rgba(234, 179, 8, 0.15); border: 1px solid rgba(234, 179, 8, 0.4); color: #facc15; border-radius: 12px; padding: 18px 25px; margin-top: 30px; display: flex; align-items: center; justify-content: center; gap: 12px; font-size: 15px; font-weight: 500; box-shadow: 0 5px 20px rgba(0,0,0,0.4); }
    .alert-custom i { font-size: 20px; }

    /* Grid Opciones */
    .options-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin-top: 50px; margin-bottom: 60px; }

    .option-card { background-color: #1c1c1c; border: 1px solid #2a2a2a; border-radius: 16px; padding: 35px 20px; text-align: center; color: #e0e0e0; text-decoration: none; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); position: relative; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(0,0,0,0.4); }
    .option-card:hover { background-color: #2a2a2a; border-color: #ef4444; transform: translateY(-8px); box-shadow: 0 15px 40px rgba(239, 68, 68, 0.25); color: #fff; }

    .card-icon { width: 70px; height: 70px; background-color: #252525; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; transition: all 0.3s; border: 2px solid transparent; }
    .card-icon i { font-size: 30px; color: #ef4444; transition: all 0.3s; }
    
    .option-card:hover .card-icon { background-color: #ef4444; border-color: #fff; transform: scale(1.1); }
    .option-card:hover .card-icon i { color: #fff; }

    .card-title { font-family: 'Oswald', sans-serif; font-size: 19px; text-transform: uppercase; letter-spacing: 0.8px; margin: 0; color: #e0e0e0; transition: color 0.3s; }
    .option-card:hover .card-title { color: #fff; }

    .option-card.disabled { opacity: 0.4; pointer-events: none; filter: grayscale(80%); transform: none !important; box-shadow: none !important; border-color: #333 !important; }
    
    /* Accesos Rápidos */
    .history-section { border-top: 1px solid #222; padding-top: 40px; margin-bottom: 80px; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .section-title { font-family: 'Oswald', sans-serif; font-size: 26px; color: #fff; margin: 0; letter-spacing: 1px; }
    .quick-access-card { background: #1a1a1a; border-radius: 12px; padding: 30px; text-align: center; border: 1px solid #333; box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
    .quick-access-card p { color: #9ca3af; margin: 0; font-size: 15px; }
    .btn-link-red { color: #ef4444; text-decoration: none; font-size: 16px; font-weight: 700; transition: color 0.2s; border-bottom: 2px solid transparent; }
    .btn-link-red:hover { color: #ff6b6b; border-color: #ff6b6b; }

    /* Responsive */
    @media (max-width: 768px) {
        .hero-title { font-size: 38px; }
        .options-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
        .option-card { padding: 25px 10px; }
        .card-icon { width: 60px; height: 60px; margin-bottom: 15px; }
        .card-icon i { font-size: 24px; }
    }
</style>

<br><br><br>

<section class="user-hero">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">¡HOLA, <?php echo strtoupper($nombreSocio); ?>!</h1>
            
            <div style="display: inline-block;">
                <div class="membership-badge <?php echo $claseEstado; ?>">
                    <i class="fas <?php echo $iconoEstado; ?>"></i>
                    <span><?php echo $estadoMembresia; ?></span>
                </div>
                <?php if ($fechaVencimientoTexto): ?>
                    <span class="membership-expiry"><?php echo $fechaVencimientoTexto; ?></span>
                <?php endif; ?>
            </div>

            <?php if ($mensajeAlerta): ?>
                <div class="alert-custom">
                    <i class="fas fa-bell"></i>
                    <span><?php echo $mensajeAlerta; ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="container">
    
    <section class="options-grid">
        
        <a href="index.php?page=user_pago_membresia" class="option-card">
            <div class="card-icon"> <i class="fas fa-credit-card"></i> </div>
            <h3 class="card-title">Pagar Membresía</h3>
        </a>

        <a href="index.php?page=mis_pagos" class="option-card">
            <div class="card-icon"> <i class="fas fa-receipt"></i> </div>
            <h3 class="card-title">Historial de Pagos</h3>
        </a>

        <a href="index.php?page=user_rutina" class="option-card <?php echo !$miembroActivo ? 'disabled' : ''; ?>">
            <div class="card-icon"> <i class="fas fa-dumbbell"></i> </div>
            <h3 class="card-title">Mis Rutinas</h3>
        </a>

        <a href="index.php?page=user_information" class="option-card">
            <div class="card-icon"> <i class="fas fa-user-circle"></i> </div>
            <h3 class="card-title">Mi Perfil</h3>
        </a>

        <a href="index.php?page=user_monedero" class="option-card">
            <div class="card-icon"> <i class="fas fa-wallet"></i> </div>
            <h3 class="card-title">Monedero</h3>
        </a>

        <a href="index.php?page=user_calculator" class="option-card">
            <div class="card-icon"> <i class="fas fa-calculator"></i> </div>
            <h3 class="card-title">Calculadora IMC</h3>
        </a>

        <a href="index.php?page=user_referidos" class="option-card">
            <div class="card-icon"> <i class="fas fa-users"></i> </div>
            <h3 class="card-title">Mis Referidos</h3>
        </a>

        <?php if ($mostrarAdminPlan): ?>
            <a href="index.php?page=user_admin_plan" class="option-card">
                <div class="card-icon" style="background-color: #3b82f6; border-color: #3b82f6;"> 
                    <i class="fas fa-cogs" style="color: white;"></i>
                </div>
                <h3 class="card-title">Administrar Plan</h3>
            </a>
        <?php endif; ?>

    </section>

    <div class="history-section">
        <div class="section-header">
            <h4 class="section-title">Accesos Rápidos</h4>
        </div>
        <div class="quick-access-card">
            <p>
                ¿Necesitas ayuda con tu plan de entrenamiento o tienes alguna consulta? <br>
                <a href="https://wa.me/TUNUMERODEWHATSAPP?text=Hola,%20tengo%20una%20pregunta%20sobre%20mi%20membresía/rutina." target="_blank" class="btn-link-red">
                    <i class="fab fa-whatsapp mr-2"></i> Contáctanos por WhatsApp
                </a>
            </p>
        </div>
    </div>

</div>
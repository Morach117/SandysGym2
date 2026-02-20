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
$mostrarAdminPlan = false; 
$nombreSocio = htmlspecialchars(explode(' ', trim($selSocioData['soc_nombres']))[0]); 

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

// 3. CALCULAR ESTADO DE VIGENCIA
if ($fechaFin) {
    $currentDate = new DateTime(); 
    $fechaFinDate = new DateTime($fechaFin);
    
    $currentDate->setTime(0, 0, 0);
    $fechaFinDate->setTime(0, 0, 0);

    $fechaFinFormateada = $fechaFinDate->format('d/m/Y');

    if ($currentDate > $fechaFinDate) {
        $miembroActivo = false;
        $mensajeAlerta = "Tu membresía ha finalizado. ¡Reactívala para no perderte de nada!";
        $estadoMembresia = "Membresía Finalizada";
        $fechaVencimientoTexto = "Venció el " . $fechaFinFormateada; 
        $claseEstado = "status-expired";
        $iconoEstado = "fa-times-circle";
    } else {
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
    $miembroActivo = false;
    $estadoMembresia = "Sin membresía activa";
    $claseEstado = "status-inactive";
    $iconoEstado = "fa-user-times"; 
    $mensajeAlerta = "No tienes una membresía activa. ¡Adquiere una para acceder a todos los beneficios!";
}

// 4. VALIDACIÓN ESTRICTA PARA MOSTRAR "ADMINISTRAR PLAN"
$serviciosPlanFamiliar = [123, 124, 125, 126, 127, 167];

if ($miembroActivo && in_array($idServicioActivo, $serviciosPlanFamiliar)) {
    $mostrarAdminPlan = true;
}
?>

<style>
    /* --- Base --- */
    body { background-color: #050505 !important; color: #e0e0e0 !important; font-family: 'Muli', sans-serif !important; }

    /* --- Hero Dashboard --- */
    .dashboard-header {
        padding: 130px 0 30px !important;
        background: linear-gradient(180deg, rgba(239, 68, 68, 0.05) 0%, #050505 100%) !important;
        border-bottom: 1px solid #1a1a1a !important;
        text-align: center !important;
    }
    .greeting-title {
        font-family: 'Oswald', sans-serif !important;
        font-size: 38px !important;
        color: #ffffff !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
        margin-bottom: 12px !important;
    }
    .greeting-title span { color: #ef4444 !important; }

    /* --- Píldoras de Estado Modernas --- */
    .status-pill-container { display: inline-flex !important; flex-direction: column !important; align-items: center !important; gap: 6px !important; margin-bottom: 15px !important; }
    .status-pill {
        display: inline-flex !important; align-items: center !important; gap: 10px !important;
        padding: 8px 20px !important; border-radius: 50px !important; font-size: 14px !important; font-weight: 700 !important; text-transform: uppercase !important;
        background-color: #121212 !important; border: 1px solid #333 !important; box-shadow: 0 4px 15px rgba(0,0,0,0.5) !important;
    }
    
    .status-dot { width: 10px !important; height: 10px !important; border-radius: 50% !important; display: inline-block !important; }
    .status-active .status-dot { background-color: #10b981 !important; box-shadow: 0 0 10px #10b981 !important; }
    .status-active { color: #10b981 !important; border-color: rgba(16, 185, 129, 0.3) !important; }
    
    .status-warning .status-dot { background-color: #f59e0b !important; box-shadow: 0 0 10px #f59e0b !important; }
    .status-warning { color: #f59e0b !important; border-color: rgba(245, 158, 11, 0.3) !important; }
    
    .status-expired .status-dot { background-color: #ef4444 !important; box-shadow: 0 0 10px #ef4444 !important; }
    .status-expired { color: #ef4444 !important; border-color: rgba(239, 68, 68, 0.3) !important; }
    
    .status-inactive .status-dot { background-color: #6b7280 !important; }
    .status-inactive { color: #9ca3af !important; }

    .membership-expiry { font-size: 13px !important; color: #888 !important; font-weight: 500 !important; letter-spacing: 0.5px !important; }

    /* Alertas */
    .alert-custom { 
        background: rgba(239, 68, 68, 0.1) !important; border: 1px solid rgba(239, 68, 68, 0.3) !important; color: #ef4444 !important; 
        border-radius: 12px !important; padding: 15px 25px !important; display: inline-flex !important; align-items: center !important; gap: 12px !important; 
        font-size: 14px !important; font-weight: 600 !important; max-width: 600px !important; margin: 0 auto !important;
    }

    /* --- Grid de Apps (Opciones) --- */
    .dashboard-grid { 
        display: grid !important; 
        /* ESCRITORIO: Tarjetas más anchas para layout horizontal */
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important; 
        gap: 20px !important; padding: 30px 0 50px !important; 
    }

    .app-card { 
        background-color: #121212 !important; border: 1px solid #222 !important; border-radius: 16px !important; 
        padding: 20px 25px !important; text-decoration: none !important; 
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important; 
        /* ESCRITORIO: Flex Horizontal (Icono izquierda, Texto derecha) */
        display: flex !important; flex-direction: row !important; align-items: center !important; justify-content: flex-start !important; 
        box-shadow: 0 8px 20px rgba(0,0,0,0.4) !important; text-align: left !important;
    }
    .app-card:hover { 
        background-color: #1a1a1a !important; border-color: #ef4444 !important; transform: translateY(-5px) !important; 
        box-shadow: 0 12px 25px rgba(239, 68, 68, 0.15) !important; text-decoration: none !important;
    }

    .app-icon { 
        width: 60px !important; height: 60px !important; background-color: rgba(239, 68, 68, 0.05) !important; 
        border-radius: 14px !important; display: flex !important; align-items: center !important; justify-content: center !important; 
        margin-bottom: 0 !important; margin-right: 20px !important; /* Separación horizontal en escritorio */
        transition: all 0.3s !important; border: 1px solid rgba(239, 68, 68, 0.1) !important;
        flex-shrink: 0 !important;
    }
    .app-icon i { font-size: 24px !important; color: #ef4444 !important; transition: all 0.3s !important; }
    
    .app-card:hover .app-icon { background-color: #ef4444 !important; transform: scale(1.05) !important; box-shadow: 0 0 15px rgba(239, 68, 68, 0.4) !important; }
    .app-card:hover .app-icon i { color: #ffffff !important; }

    /* Forzar texto blanco puro y grueso */
    .app-title { 
        font-family: 'Oswald', sans-serif !important; 
        font-size: 18px !important; 
        text-transform: uppercase !important; 
        letter-spacing: 0.5px !important; 
        margin: 0 !important; 
        font-weight: 500 !important; 
        color: #ffffff !important; 
    }
    .app-card:hover .app-title { color: #ffffff !important; }

    .app-card.disabled { opacity: 0.5 !important; pointer-events: none !important; filter: grayscale(100%) !important; }

    /* --- Accesos Rápidos --- */
    .help-card { background: linear-gradient(135deg, #121212 0%, #1a1a1a 100%) !important; border-radius: 16px !important; padding: 25px !important; text-align: center !important; border: 1px solid #333 !important; margin-bottom: 60px !important; }
    .help-card p { color: #aaa !important; margin-bottom: 15px !important; font-size: 14px !important; }
    .btn-whatsapp { display: inline-flex !important; align-items: center !important; gap: 8px !important; background: rgba(37, 211, 102, 0.1) !important; color: #25D366 !important; padding: 10px 25px !important; border-radius: 50px !important; font-weight: bold !important; border: 1px solid rgba(37, 211, 102, 0.3) !important; transition: 0.3s !important; text-decoration: none !important; }
    .btn-whatsapp:hover { background: #25D366 !important; color: #fff !important; text-decoration: none !important; transform: translateY(-2px) !important; }

    /* 🔥 OVERRIDE GLOBAL PARA FORZAR SWEETALERT EN MODO OSCURO 🔥 */
    div.swal2-popup { background-color: #1a1a1a !important; color: #ffffff !important; border: 1px solid #333 !important; border-radius: 16px !important; }
    div.swal2-title, div.swal2-html-container { color: #ffffff !important; }
    .swal2-icon.swal2-info { border-color: #3b82f6 !important; color: #3b82f6 !important; }
    .swal2-icon.swal2-success { border-color: #10b981 !important; color: #10b981 !important; }
    .swal2-icon.swal2-error { border-color: #ef4444 !important; color: #ef4444 !important; }
    .swal2-confirm { border-radius: 8px !important; font-weight: bold !important; font-family: 'Muli', sans-serif; background-color: #ef4444 !important; color: white !important;}
    .swal2-cancel { border-radius: 8px !important; font-family: 'Muli', sans-serif; }

    /* --- Responsive Móvil --- */
    @media (max-width: 768px) {
        .dashboard-header { padding-top: 110px !important; padding-bottom: 20px !important; }
        .greeting-title { font-size: 32px !important; }
        
        /* Grid 2x2 más compacto */
        .dashboard-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 12px !important; padding-top: 20px !important; }
        
        .app-card { 
            flex-direction: column !important; /* Vuelve a apilar verticalmente en móvil */
            text-align: center !important; 
            padding: 20px 15px !important; /* Tarjetas menos altas */
        }
        
        .app-icon { 
            margin-right: 0 !important; margin-bottom: 12px !important; 
            width: 50px !important; height: 50px !important; 
        }
        .app-icon i { font-size: 20px !important; }
        
        .app-title { 
            font-size: 15px !important; /* Más legible en móvil */
            font-weight: 600 !important; 
        }
    }
</style>

<section class="dashboard-header">
    <div class="container">
        <h1 class="greeting-title">¡Hola, <span><?php echo strtoupper($nombreSocio); ?></span>!</h1>
        
        <div class="status-pill-container">
            <div class="status-pill <?php echo $claseEstado; ?>">
                <span class="status-dot"></span>
                <?php echo $estadoMembresia; ?>
            </div>
            <?php if ($fechaVencimientoTexto): ?>
                <span class="membership-expiry"><?php echo $fechaVencimientoTexto; ?></span>
            <?php endif; ?>
        </div>

        <?php if ($mensajeAlerta): ?>
            <div class="mt-2">
                <div class="alert-custom">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $mensajeAlerta; ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <section class="dashboard-grid">
        
        <a href="index.php?page=user_pago_membresia" class="app-card">
            <div class="app-icon"><i class="fas fa-credit-card"></i></div>
            <h3 class="app-title">Pagar Membresía</h3>
        </a>

        <a href="index.php?page=mis_pagos" class="app-card">
            <div class="app-icon"><i class="fas fa-receipt"></i></div>
            <h3 class="app-title">Mis Pagos</h3>
        </a>

        <a href="index.php?page=user_rutina" class="app-card <?php echo !$miembroActivo ? 'disabled' : ''; ?>">
            <div class="app-icon"><i class="fas fa-dumbbell"></i></div>
            <h3 class="app-title">Mis Rutinas</h3>
        </a>

        <a href="index.php?page=user_information" class="app-card">
            <div class="app-icon"><i class="fas fa-user-edit"></i></div>
            <h3 class="app-title">Mi Perfil</h3>
        </a>

        <a href="index.php?page=user_monedero" class="app-card">
            <div class="app-icon"><i class="fas fa-wallet"></i></div>
            <h3 class="app-title">Monedero</h3>
        </a>

        <a href="index.php?page=user_calculator" class="app-card">
            <div class="app-icon"><i class="fas fa-calculator"></i></div>
            <h3 class="app-title">IMC</h3>
        </a>

        <a href="index.php?page=user_referidos" class="app-card">
            <div class="app-icon"><i class="fas fa-users"></i></div>
            <h3 class="app-title">Referidos</h3>
        </a>

        <?php if ($mostrarAdminPlan): ?>
            <a href="index.php?page=user_admin_plan" class="app-card">
                <div class="app-icon"><i class="fas fa-users-cog"></i></div>
                <h3 class="app-title">Mi Plan</h3>
            </a>
        <?php endif; ?>

    </section>

    <div class="help-card">
        <p>¿Necesitas ayuda con tu plan de entrenamiento o tienes dudas sobre tu membresía?</p>
        <a href="https://wa.me/TUNUMERODEWHATSAPP?text=Hola,%20tengo%20una%20pregunta%20sobre%20mi%20cuenta." target="_blank" class="btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Contáctanos por WhatsApp
        </a>
    </div>
</div>

<?php 
// =========================================================================
// --- DETECTOR DE INVITACIONES MÁGICAS ---
// =========================================================================
$pendingInviteId = $_COOKIE['gym_pending_invite'] ?? $_SESSION['gym_pending_invite'] ?? null;

if ($pendingInviteId && $pendingInviteId != $socioId): 
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        const hostId = "<?php echo htmlspecialchars($pendingInviteId); ?>";
        
        $.ajax({
            url: 'api/join_plan_session.php',
            type: 'POST',
            data: { host_id: hostId, action: 'check' },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        title: '¡Invitación Especial!',
                        html: `<span style="color:#aaa; font-size:15px;"><strong>${res.host_name}</strong> te ha invitado a unirte a su Plan Grupal en Sandy's Gym.</span><br><br><span style="color:#fff; font-size:18px;">¿Aceptas la invitación?</span>`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: '#10b981', 
                        cancelButtonColor: '#333',
                        confirmButtonText: '<i class="fas fa-check mr-2"></i> Sí, unirme al plan',
                        cancelButtonText: 'Más tarde'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            unirAlPlan(hostId);
                        }
                    });
                }
            }
        });
    });

    function unirAlPlan(hostId) {
        Swal.fire({
            title: 'Vinculando cuenta...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.post('api/join_plan_session.php', { host_id: hostId, action: 'confirm' }, function(res) {
            if (res.success) {
                localStorage.removeItem('gym_pending_invite');
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Bienvenido al grupo!',
                    text: 'Ya eres parte del plan familiar.',
                    confirmButtonColor: '#ef4444'
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    icon: 'error', 
                    title: 'Ups...', 
                    text: res.message
                });
            }
        }, 'json');
    }
</script>
<?php endif; ?>
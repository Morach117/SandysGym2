<?php
// --- INCLUDES OBLIGATORIOS ---
require_once 'conn.php';
include('./api/select_data.php'); // Datos del usuario logueado ($selSocioData)

// 1. VALIDACIÓN DE SESIÓN
if (!$selSocioData) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// 2. VARIABLES DEL USUARIO ACTUAL
$miId = $selSocioData['soc_id_socio'];
$miNombre = htmlspecialchars($selSocioData['soc_nombres']);
$miTelefono = $selSocioData['soc_tel_cel']; // Usaremos el teléfono como "Código"

// 3. CONSULTA DE REFERIDOS
// Buscamos socios que tengan en 'soc_id_referido_por' MI ID
$queryRef = "SELECT soc_nombres, soc_apepat, soc_fecha_captura, soc_imagen 
             FROM san_socios 
             WHERE soc_id_referido_por = :miId 
             ORDER BY soc_fecha_captura DESC";

try {
    $stmtRef = $conn->prepare($queryRef);
    $stmtRef->bindParam(':miId', $miId, PDO::PARAM_INT);
    $stmtRef->execute();
    $listaReferidos = $stmtRef->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $listaReferidos = []; // Si falla la consulta, array vacío para no romper la página
}

// Cálculos
$totalReferidos = count($listaReferidos);
$gananciaPorReferido = 35; 
$gananciaTotal = $totalReferidos * $gananciaPorReferido;

// Construcción del Link (Ajusta 'index.php?page=registro' a tu URL real de registro)
// Detectamos el protocolo (http o https) y el host automáticamente
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
// Ajusta la ruta "/index.php?page=registro" según cómo se llame tu página de registro
$baseUrl = "$protocol://$host/index.php?page=registro"; 
$linkCompleto = $baseUrl . "&ref=" . $miTelefono;

?>

<style>
    body { background-color: #0f0f0f; color: #e0e0e0; font-family: 'Muli', sans-serif; }

    /* Hero Header */
    .referral-hero {
        padding: 140px 0 60px; /* Espacio para navbar */
        background: linear-gradient(180deg, rgba(15,15,15,0.9), #0f0f0f), url('./assets/img/hero/hero-referrals.jpg');
        background-size: cover;
        background-position: center;
        text-align: center;
        border-bottom: 1px solid #222;
    }
    .hero-title { font-family: 'Oswald', sans-serif; font-size: 38px; color: #fff; text-transform: uppercase; margin-bottom: 10px; }
    .hero-subtitle { font-size: 16px; color: #bbb; max-width: 600px; margin: 0 auto; }

    /* Tarjetas de Estadísticas */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: -40px; /* Efecto flotante sobre el hero */
        position: relative;
        z-index: 2;
    }
    .stat-card {
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 16px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .stat-number { font-family: 'Oswald', sans-serif; font-size: 42px; color: #ef4444; font-weight: 700; }
    .stat-label { font-size: 14px; color: #888; text-transform: uppercase; letter-spacing: 1px; }

    /* Sección Compartir */
    .share-section { margin-top: 60px; text-align: center; padding: 40px 20px; background: #141414; border-radius: 16px; border: 1px dashed #333; }
    
    .share-code-box {
        display: inline-flex;
        align-items: center;
        background: #000;
        border: 2px solid #ef4444;
        color: #fff;
        padding: 10px 20px;
        font-size: 20px;
        font-family: 'Oswald', sans-serif;
        border-radius: 10px;
        margin: 15px 0;
        letter-spacing: 2px;
        cursor: pointer;
        transition: 0.3s;
    }
    .share-code-box:hover { background: #ef4444; color: #fff; box-shadow: 0 0 15px rgba(239, 68, 68, 0.4); }
    .share-code-box:active { transform: scale(0.98); }
    
    .btn-whatsapp {
        background: #25D366; color: #fff; border: none; padding: 12px 30px;
        border-radius: 50px; font-weight: bold; text-decoration: none;
        display: inline-flex; align-items: center; gap: 10px; transition: 0.3s;
        font-size: 16px; margin-top: 10px;
    }
    .btn-whatsapp:hover { background: #20bd5a; color: #fff; box-shadow: 0 0 15px rgba(37, 211, 102, 0.4); text-decoration: none; }

    /* Input de Copiar Link */
    .copy-link-container {
        margin-top: 25px;
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
    }
    .copy-input-group {
        display: flex;
        background: #000;
        border: 1px solid #333;
        border-radius: 8px;
        overflow: hidden;
    }
    .copy-input {
        flex-grow: 1;
        background: transparent;
        border: none;
        color: #888;
        padding: 12px 15px;
        font-size: 13px;
        outline: none;
    }
    .copy-btn {
        background: #222;
        border: none;
        border-left: 1px solid #333;
        color: #ef4444;
        padding: 0 20px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.2s;
    }
    .copy-btn:hover { background: #333; }

    /* Lista de Referidos */
    .referrals-list { margin-top: 50px; margin-bottom: 80px; }
    .list-title { font-family: 'Oswald', sans-serif; font-size: 24px; color: #fff; margin-bottom: 20px; border-left: 4px solid #ef4444; padding-left: 15px; }
    
    .referral-item {
        background: #1a1a1a;
        border-bottom: 1px solid #2a2a2a;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: 0.2s;
    }
    .referral-item:first-child { border-top-left-radius: 12px; border-top-right-radius: 12px; }
    .referral-item:last-child { border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; border-bottom: none; }
    .referral-item:hover { background: #222; }

    .ref-info { display: flex; align-items: center; gap: 15px; }
    .ref-avatar {
        width: 45px; height: 45px; border-radius: 50%; background: #333;
        display: flex; align-items: center; justify-content: center;
        color: #666; font-size: 18px; object-fit: cover;
    }
    .ref-name { color: #e0e0e0; font-weight: 600; font-size: 15px; display: block; }
    .ref-date { color: #666; font-size: 12px; }
    .ref-status { background: rgba(34, 197, 94, 0.15); color: #4ade80; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }

    @media (max-width: 768px) {
        .stat-number { font-size: 32px; }
        .share-code-box { font-size: 18px; padding: 10px 20px; width: 100%; justify-content: center; }
        .copy-input { font-size: 11px; }
    }
</style>

<section class="referral-hero">
    <div class="container">
        <h1 class="hero-title">Gana Dinero Invitando</h1>
        <p class="hero-subtitle">Invita a tus amigos a entrenar. Por cada amigo que se inscriba con tu código, recibirás <strong>$<?= $gananciaPorReferido ?> MXN</strong> en tu monedero.</p>
    </div>
</section>

<div class="container">
    
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-number"><?= $totalReferidos ?></div>
            <div class="stat-label">Amigos Inscritos</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">$<?= number_format($gananciaTotal, 2) ?></div>
            <div class="stat-label">Ganado en Monedero</div>
        </div>
    </div>

    <div class="share-section">
        <h4 style="color:#fff; font-family:'Oswald', sans-serif;">Comparte tu Código</h4>
        <p style="color:#888; font-size:14px; margin-bottom: 20px;">Tus amigos deben dar este número o usar tu enlace al registrarse.</p>
        
        <div class="share-code-box" onclick="copiarTexto('<?= $miTelefono ?>')" title="Clic para copiar código">
            <i class="far fa-copy mr-2"></i> <span style="margin-left: 10px;"><?= $miTelefono ?></span>
        </div>
        
        <br>

        <?php 
            $mensajeWA = "¡Hola! Vente a entrenar conmigo a Sandys Gym. 🏋️‍♂️ Regístrate aquí para una promo especial: " . $linkCompleto;
            $linkWA = "https://api.whatsapp.com/send?text=" . urlencode($mensajeWA);
        ?>
        <a href="<?= $linkWA ?>" target="_blank" class="btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Enviar por WhatsApp
        </a>

        <div class="copy-link-container">
            <p style="color:#666; font-size:12px; margin-bottom: 5px; text-align: left;">Enlace directo de registro:</p>
            <div class="copy-input-group">
                <input type="text" class="copy-input" id="linkReferido" value="<?= $linkCompleto ?>" readonly>
                <button type="button" class="copy-btn" onclick="copiarLinkInput()">
                    <i class="fas fa-link"></i> COPIAR
                </button>
            </div>
        </div>
    </div>

    <div class="referrals-list">
        <h3 class="list-title">Historial de Referidos</h3>
        
        <?php if ($totalReferidos > 0): ?>
            <div class="referral-group">
                <?php foreach ($listaReferidos as $ref): ?>
                    <div class="referral-item">
                        <div class="ref-info">
                            <?php if (!empty($ref['soc_imagen'])): ?>
                                <img src="./imagenes/avatar/<?= htmlspecialchars($ref['soc_imagen']) ?>" class="ref-avatar" alt="Foto">
                            <?php else: ?>
                                <div class="ref-avatar"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                            
                            <div>
                                <span class="ref-name"><?= htmlspecialchars($ref['soc_nombres'] . ' ' . $ref['soc_apepat']) ?></span>
                                <span class="ref-date">Se unió el <?= date('d/m/Y', strtotime($ref['soc_fecha_captura'])) ?></span>
                            </div>
                        </div>
                        <div class="ref-status">
                            <i class="fas fa-check-circle mr-1"></i> +$<?= $gananciaPorReferido ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center p-5" style="background: #1a1a1a; border-radius: 12px; border: 1px solid #333;">
                <i class="fas fa-user-friends" style="font-size: 40px; color: #333; margin-bottom: 15px;"></i>
                <p style="color: #666; margin-top: 10px;">Aún no has invitado a nadie.<br>¡Comparte tu enlace y empieza a ganar!</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Función para copiar texto arbitrario (el código corto)
    function copiarTexto(texto) {
        navigator.clipboard.writeText(texto).then(function() {
            mostrarToast('Código copiado al portapapeles');
        }, function(err) {
            console.error('Error al copiar: ', err);
        });
    }

    // Función para copiar lo que hay en el input del link
    function copiarLinkInput() {
        var copyText = document.getElementById("linkReferido");
        copyText.select();
        copyText.setSelectionRange(0, 99999); // Para móviles
        
        navigator.clipboard.writeText(copyText.value).then(function() {
            mostrarToast('Enlace copiado correctamente');
        });
    }

    // Helper para mostrar la alerta bonita
    function mostrarToast(mensaje) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            background: '#1a1a1a',
            color: '#fff',
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: 'success',
            title: mensaje
        });
    }
</script>
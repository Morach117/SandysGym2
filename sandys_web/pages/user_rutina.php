<?php
// --- 1. INICIAR SESIÓN (SOLO SI NO ESTÁ ACTIVA) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. VERIFICAR ACCESO (Solo ID necesario ahora) ---
if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['soc_id_socio'])) {
    header('Location: index.php?page=login&error=session_expired');
    exit;
}

// --- 3. INCLUIR CONEXIÓN A BD ---
// Verificar si la conexión fue exitosa
if (!isset($conn) || !$conn instanceof PDO) {
     die("Error crítico: No se pudo establecer la conexión con la base de datos.");
}

// --- 4. OBTENER ID DEL USUARIO DESDE LA SESIÓN ---
$socioId = $_SESSION['admin']['soc_id_socio'];

// --- 5. CONSULTAR DATOS FALTANTES DEL SOCIO (Nombre y Género) ---
$socioNombres = 'Socio';
$generoUsuario = 'M'; // Asumir Masculino por defecto

try {
    $querySocio = "SELECT soc_nombres, soc_genero FROM san_socios WHERE soc_id_socio = :socioId LIMIT 1";
    $stmtSocio = $conn->prepare($querySocio);
    $stmtSocio->bindParam(':socioId', $socioId, PDO::PARAM_INT);
    $stmtSocio->execute();
    $socioDataFromDB = $stmtSocio->fetch(PDO::FETCH_ASSOC);

    if ($socioDataFromDB) {
        $socioNombres = explode(' ', trim($socioDataFromDB['soc_nombres'] ?? $socioNombres))[0]; // Solo el primer nombre
        $generoUsuario = $socioDataFromDB['soc_genero'] ?? $generoUsuario;
    }
} catch (PDOException $e) {
    error_log("Error DB al obtener datos del socio ID {$socioId}: " . $e->getMessage());
}

// --- 6. VERIFICAR ESTADO DE MEMBRESÍA DESDE LA BD ---
date_default_timezone_set('America/Mexico_City');
$currentDate = new DateTime();
$miembroActivo = false;
try {
    $queryMem = "SELECT pag_fecha_fin FROM san_pagos WHERE pag_id_socio = :socioId AND pag_status = 'A' ORDER BY pag_fecha_fin DESC LIMIT 1";
    $stmtMem = $conn->prepare($queryMem);
    $stmtMem->bindParam(':socioId', $socioId, PDO::PARAM_INT);
    $stmtMem->execute();
    $fechaFin = $stmtMem->fetchColumn();
    if ($fechaFin) {
        $fechaFinDate = new DateTime($fechaFin);
        // Reseteamos horas para comparar solo fechas
        $currentDate->setTime(0, 0, 0);
        $fechaFinDate->setTime(0, 0, 0);

        if ($currentDate <= $fechaFinDate) {
            $miembroActivo = true;
        }
    }
} catch (PDOException $e) {
    error_log("Error DB al verificar membresía para socio ID {$socioId}: " . $e->getMessage());
}

// --- 7. DETERMINAR GÉNERO (Numérico) ---
$gen = ($generoUsuario === 'M' || $generoUsuario === 'm') ? 1 : 2;

// --- 8. DEFINIR LAS TARJETAS DE NIVEL ---
$niveles = [
    1 => [ 'level_num' => 1, 'nombre' => 'Principiante', 'imagen_bg' => 'https://images.pexels.com/photos/4056723/pexels-photo-4056723.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1', 'fa_icon_class' => 'fa-solid fa-person-running', 'texto' => 'Ideal para quienes están comenzando. ¡Empezar es fácil!'],
    2 => [ 'level_num' => 2, 'nombre' => 'Intermedio', 'imagen_bg' => 'https://images.pexels.com/photos/1552249/pexels-photo-1552249.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1', 'fa_icon_class' => 'fa-solid fa-dumbbell', 'texto' => 'Para mejorar tu rendimiento. ¡Supera tus límites!'],
    3 => [ 'level_num' => 3, 'nombre' => 'Avanzado', 'imagen_bg' => 'https://images.pexels.com/photos/1552242/pexels-photo-1552242.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1', 'fa_icon_class' => 'fa-solid fa-medal', 'texto' => 'Para atletas experimentados. ¡Ponte a prueba!']
];
?>

<style>
    /* --- Base --- */
    .class-timetable-section {
        padding-top: 140px; /* Padding extra para no chocar con el navbar */
        padding-bottom: 80px;
        background-color: #050505;
        min-height: 100vh;
        font-family: 'Muli', sans-serif;
    }

    /* --- Títulos --- */
    .section-title { margin-bottom: 50px; }
    .section-title span { 
        color: #ef4444; font-weight: bold; text-transform: uppercase; 
        letter-spacing: 2px; font-size: 14px; display: block; margin-bottom: 10px;
    }
    .section-title h2 { color: #ffffff; font-family: 'Oswald', sans-serif; font-size: 38px; text-transform: uppercase; letter-spacing: 1px; }
    .section-title p { color: #aaa; font-size: 16px; margin-top: 15px; }

    /* --- Tarjetas de Nivel --- */
    .level-card {
        border: none; border-radius: 20px; overflow: hidden; height: 350px;
        transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.3s ease;
        position: relative; cursor: pointer; display: block; text-decoration: none !important;
        margin-bottom: 30px; /* Separación en móviles */
    }
    
    .level-card:hover { 
        transform: translateY(-8px); 
        box-shadow: 0 15px 35px rgba(239, 68, 68, 0.25) !important; 
    }

    /* Imagen de Fondo */
    .level-card-bg {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background-size: cover; background-position: center; transition: transform 0.6s ease;
    }
    .level-card:hover .level-card-bg { transform: scale(1.1); }

    /* 🔥 Overlay Mejorado (Más oscuro para contraste) 🔥 */
    .level-card-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.6) 50%, rgba(0, 0, 0, 0.3) 100%);
        z-index: 1; transition: background 0.4s ease;
    }
    .level-card:hover .level-card-overlay { background: linear-gradient(to top, rgba(239, 68, 68, 0.85) 0%, rgba(0, 0, 0, 0.7) 100%); }

    /* Contenido Interno */
    .level-card .card-body { position: relative; z-index: 2; padding: 30px 20px; display: flex; flex-direction: column; justify-content: flex-end; height: 100%; text-align: center; }

    /* Icono */
    .level-icon { font-size: 45px; color: #fff; margin-bottom: 15px; transition: transform 0.3s ease, color 0.3s ease; }
    .level-card:hover .level-icon { transform: scale(1.15); color: #fff; }

    /* Texto */
    .card-title { color: #ffffff; font-family: 'Oswald', sans-serif; font-size: 26px; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px; }
    
    /* Párrafo con sombra para legibilidad */
    .card-text { 
        color: #e0e0e0; font-size: 14px; line-height: 1.6; margin-bottom: 25px; 
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8); font-weight: 500;
    }

    /* 🔥 NUEVO: Botón (Call to Action) en lugar de solo texto 🔥 */
    .btn-rutina {
        display: inline-block; padding: 10px 25px; border-radius: 50px;
        background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.3);
        color: #fff; font-weight: bold; text-transform: uppercase; font-size: 13px; letter-spacing: 1px;
        transition: all 0.3s ease; backdrop-filter: blur(5px); align-self: center;
    }
    .level-card:hover .btn-rutina { background: #fff; color: #ef4444; border-color: #fff; }

    /* --- Membresía Expirada --- */
    .alert-custom-warning {
        background-color: #1a1a1a; border: 1px solid #444; border-top: 4px solid #facc15;
        border-radius: 12px; padding: 40px 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        color: #e0e0e0; max-width: 600px; margin: 0 auto;
    }
    .alert-custom-warning i.main-icon { font-size: 50px; color: #facc15; margin-bottom: 20px; display: block; }
    .alert-custom-warning h4 { font-family: 'Oswald', sans-serif; color: #fff; text-transform: uppercase; font-size: 24px; margin-bottom: 15px; }
    .alert-custom-warning p { color: #aaa; margin-bottom: 25px; font-size: 15px; }
    
    .btn-warning-custom { background: #facc15; color: #000; font-weight: bold; border-radius: 8px; padding: 12px 25px; transition: 0.3s; border: none; }
    .btn-warning-custom:hover { background: #eab308; color: #000; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(250, 204, 21, 0.3); text-decoration: none;}
    .btn-outline-custom { background: transparent; border: 1px solid #555; color: #ccc; font-weight: bold; border-radius: 8px; padding: 12px 25px; transition: 0.3s; }
    .btn-outline-custom:hover { background: #333; color: #fff; border-color: #777; text-decoration: none;}

    @media (max-width: 768px) {
        .class-timetable-section { padding-top: 120px; }
        .section-title h2 { font-size: 30px; }
        .level-card { height: 300px; margin-bottom: 25px; } /* Menos altas en celular */
        .level-icon { font-size: 35px; }
        .card-title { font-size: 22px; }
    }
</style>

<section class="class-timetable-section">
    <div class="container">
        <?php if ($miembroActivo): ?>
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title text-center">
                         <span>¡HOLA, <?= strtoupper(htmlspecialchars($socioNombres)) ?>!</span>
                        <h2>ELIGE TU PUNTO DE PARTIDA</h2>
                        <p>Selecciona el nivel que mejor describa tu experiencia para mostrarte las rutinas adecuadas.</p>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <?php foreach ($niveles as $nivel): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="index.php?page=routine&level=<?= $nivel['level_num'] ?>&gender=<?= $gen ?>" class="level-card">
                            <div class="level-card-bg set-bg" data-setbg="<?= htmlspecialchars($nivel['imagen_bg']) ?>"></div>
                            <div class="level-card-overlay"></div>
                            
                            <div class="card-body">
                                <i class="<?= htmlspecialchars($nivel['fa_icon_class']) ?> level-icon"></i>
                                <h3 class="card-title"><?= htmlspecialchars($nivel['nombre']) ?></h3>
                                <p class="card-text"><?= htmlspecialchars($nivel['texto']) ?></p>
                                <span class="btn-rutina">Ver Rutinas <i class="fas fa-arrow-right ml-1"></i></span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                     <div class="alert-custom-warning">
                        <i class="fas fa-lock main-icon"></i>
                        <h4>Membresía Expirada</h4>
                        <p>Tu acceso a los planes de entrenamiento ha finalizado. Renueva tu membresía hoy mismo para desbloquear tus rutinas y seguir avanzando.</p>
                        
                        <div class="d-flex justify-content-center flex-wrap" style="gap: 15px;">
                            <a href="index.php?page=user_pago_membresia" class="btn-warning-custom">
                                <i class="fas fa-credit-card mr-2"></i> Renovar Membresía
                            </a>
                            <a href="https://wa.me/TUNUMERODEWHATSAPP" target="_blank" class="btn-outline-custom">
                                 <i class="fas fa-headset mr-2"></i> Soporte
                             </a>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Inicializar imágenes de fondo dinámicas
        $('.set-bg').each(function() {
            var bg = $(this).data('setbg');
            if (bg) {
                $(this).css('background-image', 'url(' + bg + ')');
            }
        });
    });
</script>
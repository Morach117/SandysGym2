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
     // Considera loggear este error
}

// --- 4. OBTENER ID DEL USUARIO DESDE LA SESIÓN ---
// Confiamos en que soc_id_socio existe gracias a la verificación anterior
$socioId = $_SESSION['admin']['soc_id_socio'];

// --- 5. CONSULTAR DATOS FALTANTES DEL SOCIO (Nombre y Género) ---
// Inicializar variables con valores por defecto
$socioNombres = 'Socio';
$generoUsuario = 'M'; // Asumir Masculino por defecto si falla la consulta

try {
    $querySocio = "SELECT soc_nombres, soc_genero FROM san_socios WHERE soc_id_socio = :socioId LIMIT 1";
    $stmtSocio = $conn->prepare($querySocio);
    $stmtSocio->bindParam(':socioId', $socioId, PDO::PARAM_INT);
    $stmtSocio->execute();
    $socioDataFromDB = $stmtSocio->fetch(PDO::FETCH_ASSOC);

    if ($socioDataFromDB) {
        // Si encontramos los datos, los usamos
        $socioNombres = $socioDataFromDB['soc_nombres'] ?? $socioNombres; // Usar ?? por si la columna es NULL
        $generoUsuario = $socioDataFromDB['soc_genero'] ?? $generoUsuario; // Usar ?? por si la columna es NULL
    } else {
        // Raro: El ID de sesión existe pero no se encontró en la BD. Podría pasar si se borró el usuario.
        error_log("Advertencia: No se encontraron datos para el socio ID {$socioId} en user_rutina.php");
        // Mantenemos los valores por defecto.
    }
} catch (PDOException $e) {
    error_log("Error DB al obtener datos del socio ID {$socioId}: " . $e->getMessage());
    // Mantenemos los valores por defecto si falla la consulta.
}

// --- 6. VERIFICAR ESTADO DE MEMBRESÍA DESDE LA BD ---
// (Esta lógica no cambia)
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
        if ($currentDate <= $fechaFinDate) {
            $miembroActivo = true;
        }
    }
} catch (PDOException $e) {
    error_log("Error DB al verificar membresía para socio ID {$socioId}: " . $e->getMessage());
}

// --- 7. DETERMINAR GÉNERO (Numérico) ---
// Se usa $generoUsuario obtenido de la consulta a la BD (o el valor por defecto)
$gen = ($generoUsuario === 'M' || $generoUsuario === 'm') ? 1 : 2;

// --- 8. DEFINIR LAS TARJETAS DE NIVEL ---
$niveles = [ /* ... Tu array $niveles ... */ ];
// Copio el array para que el código esté completo
$niveles = [
    1 => [ 'level_num' => 1, 'nombre' => 'Principiante', 'imagen_bg' => 'https://images.pexels.com/photos/4056723/pexels-photo-4056723.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1', 'fa_icon_class' => 'fa-solid fa-person-running', 'texto' => 'Ideal para quienes están comenzando. ¡Empezar es fácil!'],
    2 => [ 'level_num' => 2, 'nombre' => 'Intermedio', 'imagen_bg' => 'https://images.pexels.com/photos/1552249/pexels-photo-1552249.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1', 'fa_icon_class' => 'fa-solid fa-dumbbell', 'texto' => 'Para mejorar tu rendimiento. ¡Supera tus límites!'],
    3 => [ 'level_num' => 3, 'nombre' => 'Avanzado', 'imagen_bg' => 'https://images.pexels.com/photos/1552242/pexels-photo-1552242.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1', 'fa_icon_class' => 'fa-solid fa-medal', 'texto' => 'Para atletas experimentados. ¡Ponte a prueba!']
];
?>

<section class="class-timetable-section spad" style="background-color: #151515;">
    <div class="container">
        <?php if ($miembroActivo): ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title text-center text-white mb-5">
                         <span>Bienvenido/a, <?= htmlspecialchars($socioNombres) ?>!</span>
                        <h2 style="color: #FFF;">Define tu Punto de Partida</h2>
                        <p class="text-secondary">Selecciona el nivel que mejor describe tu experiencia actual.</p>
                    </div>
                </div>
            </div>
            <div class="row text-center justify-content-center">
                <?php foreach ($niveles as $nivel): ?>
                    <div class="col-12 col-md-4 mb-4">
                        <div class="level-card h-100 shadow-lg position-relative overflow-hidden rounded">
                            <a href="index.php?page=routine&level=<?= $nivel['level_num'] ?>&gender=<?= $gen ?>" class="text-decoration-none d-block h-100">
                                <div class="level-card-bg set-bg" data-setbg="<?= htmlspecialchars($nivel['imagen_bg']) ?>"></div>
                                <div class="level-card-overlay"></div>
                                <div class="card-body d-flex flex-column align-items-center justify-content-center text-white position-relative h-100 p-4">
                                    <i class="<?= htmlspecialchars($nivel['fa_icon_class']) ?> mb-3 level-icon" style="font-size: 3rem; color: white;"></i>
                                    <h3 class="card-title font-weight-bold text-uppercase"><?= htmlspecialchars($nivel['nombre']) ?></h3>
                                    <p class="card-text small opacity-90"><?= htmlspecialchars($nivel['texto']) ?></p>
                                    <span class="level-select-indicator mt-3">Seleccionar Nivel &rarr;</span>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-12 text-center">
                     <div class="alert alert-warning border border-warning" role="alert" style="background-color: #fff3cd; color: #856404; margin-top: 50px;">
                        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Membresía Expirada</h4>
                        <p>Tu acceso a las rutinas ha finalizado. Por favor, renueva tu membresía para continuar.</p>
                        <hr style="border-top-color: #ffeeba;">
                        <a href="index.php?page=pagar_membresia" class="btn btn-warning mt-2">
                            <i class="fas fa-credit-card"></i> Renovar Ahora
                        </a>
                        <a href="index.php?page=contact" class="btn btn-outline-secondary mt-2">
                             <i class="fas fa-headset"></i> Contactar Soporte
                         </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    .class-timetable-section {
        padding-top: 120px;
        padding-bottom: 120px;
    }

    .section-title h2 {
        color: #ffffff !important;
    }

    .section-title p.text-secondary {
        color: #adb5bd !important;
        font-size: 1rem;
        margin-top: 0.5rem;
    }

    .level-card {
        transition: transform 0.3s ease-out, box-shadow 0.3s ease-out;
        cursor: pointer;
        border: none;
    }

    .level-card:hover {
        transform: translateY(-10px) scale(1.03);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4) !important;
    }

    .level-card-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        transition: transform 0.5s ease;
    }

    .level-card:hover .level-card-bg {
        transform: scale(1.1);
    }

    .level-card-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.4));
        z-index: 1;
        transition: background 0.3s ease;
    }

    .level-card:hover .level-card-overlay {
        background: linear-gradient(to top, rgba(243, 97, 0, 0.7), rgba(0, 0, 0, 0.5));
    }

    .level-card .card-body {
        z-index: 2;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);
    }

    .level-card .level-icon {
        transition: transform 0.3s ease, color 0.3s ease;
    }

    .level-card:hover .level-icon {
        transform: scale(1.1);
        color: #ffc107 !important;
    }

    .level-card h3.card-title {
        color: #ffffff !important;
        font-family: 'Oswald', sans-serif;
        font-size: 1.75rem;
        margin-bottom: 0.75rem;
    }

    .level-card p.card-text {
        color: #e0e0e0 !important;
        opacity: 1 !important;
        max-width: 85%;
        margin-left: auto;
        margin-right: auto;
    }

    .level-select-indicator {
        font-weight: bold;
        color: #ffc107;
        opacity: 0;
        transition: opacity 0.3s ease, transform 0.3s ease;
        transform: translateY(10px);
    }

    .level-card:hover .level-select-indicator {
        opacity: 1;
        transform: translateY(0);
    }

    /* Estilos mejorados para membresía expirada */
    .alert-warning {
        background-color: #fff3cd;
        /* Fondo amarillo pálido */
        color: #856404;
        /* Texto oscuro */
        border-color: #ffeeba;
        /* Borde más claro */
    }

    .alert-warning .alert-heading {
        color: #664d03;
    }

    /* Título más oscuro */
    .alert-warning hr {
        border-top-color: #ffeeba;
    }

    .alert-warning .btn-warning {
        color: #000;
        background-color: #ffc107;
        border-color: #ffc107;
    }

    .alert-warning .btn-warning:hover {
        background-color: #e0a800;
        border-color: #d39e00;
    }

    .alert-warning .btn-outline-secondary {
        color: #6c757d;
        border-color: #6c757d;
    }

    .alert-warning .btn-outline-secondary:hover {
        background-color: #6c757d;
        color: #fff;
    }

    .alert-warning i {
        margin-right: 0.5em;
    }

    /* Espacio para iconos en botones y título */
</style>

<script>
    $(document).ready(function() {
        // Inicializar imágenes de fondo
        $('.set-bg').each(function() {
            var bg = $(this).data('setbg');
            if (bg) {
                $(this).css('background-image', 'url(' + bg + ')');
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log("--- DEBUG: Datos del Socio en user_rutina.php ---");

        try {
            // Convertir array PHP $selSocioData a objeto JS
            const socioDataJS = <?php echo json_encode($selSocioData ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?>;
            console.log("Datos completos de $selSocioData:", socioDataJS);

            // Mostrar variables individuales
            const socioNombresJS = <?php echo json_encode($socioNombres ?? 'No definido'); ?>;
            const generoUsuarioJS = <?php echo json_encode($generoUsuario ?? 'No definido'); ?>;
            const genNumericoJS = <?php echo json_encode($gen ?? 'No definido'); ?>;
            const miembroActivoJS = <?php echo json_encode($miembroActivo ?? false); ?>;

            console.log("Nombre Socio (PHP -> JS):", socioNombresJS);
            console.log("Género Usuario (PHP -> JS):", generoUsuarioJS);
            console.log("Género Numérico (PHP -> JS):", genNumericoJS);
            console.log("Membresía Activa (PHP -> JS):", miembroActivoJS);

        } catch (e) {
            console.error("Error al procesar datos PHP en JavaScript:", e);
            // CORRECCIÓN AQUÍ: Usar json_encode también en el catch
            console.log("Datos PHP crudos ($selSocioData) (como JSON): ", <?php echo json_encode($selSocioData ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?>);
        }

        console.log("--- FIN DEBUG ---");
    });
</script>
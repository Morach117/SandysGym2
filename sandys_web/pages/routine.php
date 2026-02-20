<?php
// pages/routine.php

// 1) Parámetros desde la URL
$level  = isset($_GET['level']) ? (int)$_GET['level'] : 1;
$gender = isset($_GET['gender']) ? (int)$_GET['gender'] : 1;

$niveles = [1 => 'Principiante', 2 => 'Intermedio', 3 => 'Avanzado'];
$generos = [1 => 'Hombre', 2 => 'Mujer'];
$nombreNivelActual  = $niveles[$level] ?? '...';
$nombreGeneroActual = $generos[$gender] ?? '...';
?>

<link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />

<style>
    /* --- 1. GENERAL Y LAYOUT --- */
    .class-timetable-section {
        background-color: #050505; /* Fondo negro profundo */
        padding-top: 140px; /* Evita que el navbar lo pise */
        padding-bottom: 70px;
        min-height: 100vh;
        font-family: 'Muli', sans-serif;
    }

    .section-title span { color: #ef4444; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
    .section-title h2 { color: #ffffff; font-family: 'Oswald', sans-serif; font-size: 38px; text-transform: uppercase; letter-spacing: 1px; }

    /* --- 2. TABS (NAVEGACIÓN DE RUTINAS) --- */
    .nav-tabs { border-bottom: 1px solid #333; margin-bottom: 20px; }
    .nav-tabs .nav-item { margin-bottom: -1px; }
    .nav-tabs .nav-link {
        border: 1px solid transparent;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        color: #888;
        background: transparent;
        margin-right: 5px;
        padding: 12px 25px;
        font-weight: 700;
        transition: all .3s;
        text-transform: uppercase;
        font-size: 14px;
        letter-spacing: 0.5px;
    }
    .nav-tabs .nav-link.active {
        color: #fff;
        background: #ef4444; /* Rojo principal */
        border-color: #ef4444;
    }
    .nav-tabs .nav-link:hover:not(.active) { border-color: #333; color: #fff; background: #1a1a1a; }

    /* Contenido Tabs */
    .tab-content {
        background: transparent !important;
        border: none;
        color: #e0e0e0;
    }
    .tab-pane { padding: 0; }

    /* --- 3. TARJETA DEL EJERCICIO (CARD) --- */
    .exercise-card {
        padding: 30px;
        background-color: #121212; /* Gris muy oscuro */
        border-radius: 16px;
        border: 1px solid #222;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }

    /* 🔥 Título del Ejercicio (Centrado para equilibrio visual) 🔥 */
    .exercise-card h4 {
        color: #ffffff;
        font-family: 'Oswald', sans-serif;
        text-transform: uppercase;
        font-size: 24px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px dashed #333;
        text-align: center; /* Centrado para que no quede hueco a la derecha */
        letter-spacing: 1px;
    }

    /* Categoría (Músculo) */
    .category-badge {
        display: inline-block;
        background-color: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
        padding: 5px 15px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 15px;
        letter-spacing: 1px;
    }

    /* --- 4. ESTADÍSTICAS (Series/Reps) --- */
    .exercise-stats {
        display: flex;
        justify-content: space-around;
        background-color: #0a0a0a;
        border: 1px solid #222;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        text-align: center;
    }
    .stat-item { color: #e0e0e0; width: 33%; border-right: 1px solid #222; }
    .stat-item:last-child { border-right: none; }
    
    .stat-item .stat-value {
        font-family: 'Oswald', sans-serif;
        font-size: 28px;
        color: #facc15; /* Amarillo de alerta */
        display: block;
        line-height: 1.2;
        margin-bottom: 5px;
    }
    .stat-item .stat-label { font-size: 11px; color: #888; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; }

    /* --- 5. GUÍA DEL EJERCICIO (Texto) --- */
    .exercise-guide { font-size: 14px; line-height: 1.6; color: #aaa; text-align: left;}
    .exercise-guide h5 { color: #fff; font-family: 'Oswald', sans-serif; font-size: 18px; text-transform: uppercase; margin: 20px 0 10px; letter-spacing: 0.5px; }
    .exercise-guide h5 i { color: #ef4444; margin-right: 10px; width: 20px; text-align: center; }
    .exercise-guide p { margin-bottom: 15px; padding-left: 30px; }

    /* --- 6. CONTENEDOR DE MEDIOS (VIDEO/IMAGEN 16:9) --- */
    /* Mantenemos proporción 16:9 perfecta */
    .media-wrapper {
        position: relative;
        width: 100%;
        padding-top: 56.25%; /* 16:9 Aspect Ratio */
        background-color: #000;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #333;
        margin-bottom: 20px;
    }
    .media-wrapper .video-js, 
    .media-wrapper img {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        object-fit: cover; /* Evita franjas negras */
    }

    /* 🔥 SOLUCIÓN A LA IMAGEN ROTA 🔥 */
    /* Si la imagen no se carga, el navegador no mostrará el icono roto horrible */
    img { font-size: 0; color: transparent; text-indent: -9999px; }

    /* --- 7. PERSONALIZACIÓN VIDEO.JS --- */
    .vjs-default-skin .vjs-progress-holder .vjs-play-progress, .vjs-default-skin .vjs-volume-level { background-color: #ef4444; }
    .vjs-default-skin .vjs-big-play-button {
        background: rgba(239, 68, 68, 0.8) !important;
        border-color: transparent !important;
        border-radius: 50%;
        width: 60px !important; height: 60px !important;
        line-height: 60px !important; font-size: 30px !important;
        margin-top: -30px !important; margin-left: -30px !important;
        transition: 0.3s;
    }
    .vjs-default-skin:hover .vjs-big-play-button { transform: scale(1.1); background: #ef4444 !important; }

    /* --- 8. BOTÓN VOLVER --- */
    .btn-return {
        background-color: transparent; border: 2px solid #ef4444; color: #fff;
        padding: 14px 40px; border-radius: 50px; font-family: 'Oswald', sans-serif;
        font-size: 16px; text-transform: uppercase; font-weight: 600; letter-spacing: 1px;
        transition: all 0.3s; text-decoration: none; display: inline-block;
    }
    .btn-return:hover { background-color: #ef4444; box-shadow: 0 5px 20px rgba(239, 68, 68, 0.4); color: #fff; text-decoration: none; }

    /* --- RESPONSIVO --- */
    @media (max-width: 991px) {
        /* Hacemos que la tarjeta sea más delgada en PC para evitar huecos si no hay texto largo */
        .exercise-card { max-width: 800px; margin-left: auto; margin-right: auto; }
    }
    @media (max-width: 768px) {
        .class-timetable-section { padding-top: 110px; }
        .section-title h2 { font-size: 28px; }
        .exercise-card { padding: 20px 15px; }
        .stat-item .stat-value { font-size: 22px; }
        .stat-item .stat-label { font-size: 10px; }
        .nav-tabs .nav-link { padding: 10px 15px; font-size: 12px; }
    }
</style>

<section class="class-timetable-section">
    <div class="container">
        
        <div class="row justify-content-center">
            <div class="col-lg-10 text-center">
                <div class="section-title mb-5">
                    <span>Rutina Personalizada</span>
                    <h2 id="routineTitle">Cargando Rutina...</h2>
                    <p>Nivel: <strong><?= htmlspecialchars($nombreNivelActual) ?></strong> | Género: <strong><?= htmlspecialchars($nombreGeneroActual) ?></strong></p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-10 offset-lg-1" id="routineContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-danger" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted" style="font-size: 16px;">Obteniendo tus ejercicios, preparate...</p>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-lg-12 text-center">
                <a href="index.php?page=user_rutina" class="btn-return"><i class="fas fa-arrow-left mr-2"></i> Volver a Selección de Nivel</a>
            </div>
        </div>

    </div>
</section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>

<script>
    $(function() {
        // Asumo que tu función loadRoutine() está definida en otro archivo JS que se carga al final
        if(typeof loadRoutine === "function") {
            loadRoutine(<?= $level ?>, <?= $gender ?>);
        } else {
            console.error("La función loadRoutine no está definida. Revisa tus scripts.");
        }
    });
</script>
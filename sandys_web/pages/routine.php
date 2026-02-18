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
    /* assets/css/routine.css */
    .class-timetable-section {
        background-color: #151515;
        padding-bottom: 70px;
    }

    /* Tabs */
    .nav-tabs {
        border-bottom: 1px solid #495057;
    }

    .nav-tabs .nav-item {
        margin-bottom: -1px;
    }

    .nav-tabs .nav-link {
        border: 1px solid transparent;
        border-top-left-radius: .35rem;
        border-top-right-radius: .35rem;
        color: #adb5bd;
        background: transparent;
        margin-right: 5px;
        padding: .85rem 1.35rem;
        font-weight: bold;
        transition: all .2s;
        text-transform: uppercase;
        font-size: .9rem;
    }

    .nav-tabs .nav-link.active {
        color: #151515;
        background: #ffc107;
        border-color: #495057 #495057 #ffc107;
    }

    .nav-tabs .nav-link:hover {
        border-color: #495057;
        color: #ffca2c;
        background: #343a40;
    }

    /* Contenido Tabs */
    .tab-content {
        background: #212529 !important;
        border: 1px solid #495057;
        border-top: none;
        color: #adb5bd;
    }

    .tab-pane {
        padding: 1.5rem;
    }

    .spinner-border {
        width: 3rem;
        height: 3rem;
    }

    /* ====== Tarjeta de ejercicio ====== */
    .exercise-card {
        padding: 1.5rem;
        background-color: #2c3034;
        border-radius: 8px;
        border: 1px solid #495057;
        margin-bottom: 2rem;
    }

    .exercise-card h4 {
        color: #ffc107;
        font-family: 'Oswald', sans-serif;
        text-transform: uppercase;
        font-size: 1.75rem;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid #495057;
        padding-bottom: .75rem;
    }

    .exercise-stats {
        display: flex;
        justify-content: space-around;
        background-color: #212529;
        border-radius: 5px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .stat-item {
        color: #e9ecef;
    }

    .stat-item .stat-value {
        font-family: 'Oswald', sans-serif;
        font-size: 1.5rem;
        color: #ffc107;
        display: block;
        line-height: 1.2;
    }

    .stat-item .stat-label {
        font-size: .8rem;
        color: #adb5bd;
        text-transform: uppercase;
    }

    .exercise-guide {
        font-size: .95rem;
        line-height: 1.7;
        color: #ced4da;
    }

    .exercise-guide h5 {
        color: #e9ecef;
        font-family: 'Oswald', sans-serif;
        font-size: 1.1rem;
        text-transform: uppercase;
        margin: 1rem 0 .5rem;
        letter-spacing: .5px;
    }

    .exercise-guide h5 i {
        color: #ffc107;
        margin-right: 8px;
        width: 20px;
    }

    .exercise-guide p {
        color: #adb5bd;
        margin-bottom: 1rem;
        padding-left: 28px;
    }

    .border-secondary {
        border-color: #495057 !important;
    }

    /* ====== Video.js ====== */
    .video-js-container {
        width: 100%;
        max-height: 280px;
        border-radius: 5px;
        overflow: hidden;
        background: #000;
    }

    .video-js {
        width: 100%;
        height: 280px;
    }

    .vjs-default-skin .vjs-progress-holder .vjs-play-progress,
    .vjs-default-skin .vjs-volume-level {
        background-color: #ffc107;
    }

    .vjs-default-skin .vjs-big-play-button {
        background: rgba(243, 97, 0, .7) !important;
        border-color: #ffc107 !important;
        border-radius: 50%;
        width: 2em !important;
        height: 2em !important;
        line-height: 2em !important;
        font-size: 2.5em !important;
        margin-top: -1.25em !important;
        margin-left: -1em !important;
    }

    .video-js.vjs-fullscreen,
    .video-js.vjs-fullscreen .vjs-tech {
        height: 100% !important;
        max-height: 100vh !important;
        width: 100% !important;
    }
</style>

<section class="class-timetable-section spad">
    <br>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title text-center mb-5">
                    <span>Tu Plan de Entrenamiento</span>
                    <h2 id="routineTitle" style="color:#ffffff;">Cargando Rutina...</h2>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-10 offset-lg-1" id="routineContainer">
                <div class="text-center text-white py-5">
                    <div class="spinner-border text-warning" role="status"><span class="sr-only">Cargando...</span></div>
                    <p class="mt-3 text-muted">Obteniendo tus ejercicios...</p>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-lg-12 text-center">
                <a href="index.php?page=user_rutina" class="primary-btn btn-normal">Volver a Selección de Nivel</a>
            </div>
        </div>
    </div>
</section>

<!-- Dependencias -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<!-- Video.js -->
<script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>

<!-- Tu lógica -->

<script>
    $(function() {
        loadRoutine(<?= $level ?>, <?= $gender ?>);

        // set-bg (si usas ese patrón en tu template)
        $('.set-bg').each(function() {
            var bg = $(this).data('setbg');
            if (bg) $(this).css('background-image', 'url(' + bg + ')');
        });
    });
</script>
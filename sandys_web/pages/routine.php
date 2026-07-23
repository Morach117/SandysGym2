<?php
$level  = isset($_GET['level']) ? (int)$_GET['level'] : 1;
$gender = isset($_GET['gender']) ? (int)$_GET['gender'] : 1;

$niveles = [1 => 'Principiante', 2 => 'Intermedio', 3 => 'Avanzado'];
$generos = [1 => 'Hombre', 2 => 'Mujer'];
$nombreNivelActual  = $niveles[$level] ?? '...';
$nombreGeneroActual = $generos[$gender] ?? '...';
?>

<link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css" rel="stylesheet">

<style>
    :root {
        --bg-base: #050505;
        --bg-panel: #1a1a1a;
        --brand-red: #ef4444;
        --brand-green: #10b981;
        --brand-orange: #F28123;
        --text-main: #ffffff;
        --text-muted: #a1a1aa;
        --border-radius-pill: 50rem;
    }

    .class-timetable-section {
        background-color: var(--bg-base);
        padding-top: 140px;
        padding-bottom: 70px;
        min-height: 100vh;
        font-family: 'Muli', sans-serif;
    }

    .section-title span { color: var(--brand-red); font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
    .section-title h2 { color: var(--text-main); font-family: 'Oswald', sans-serif; font-size: 38px; text-transform: uppercase; letter-spacing: 1px; }

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
        color: var(--text-main);
        background: var(--brand-red);
        border-color: var(--brand-red);
    }
    .nav-tabs .nav-link:hover:not(.active) { border-color: #333; color: var(--text-main); background: var(--bg-panel); }
    .tab-content { background: transparent !important; border: none; color: #e0e0e0; }
    .tab-pane { padding: 0; }

    .exercise-card {
        padding: 30px;
        background-color: #121212;
        border-radius: 16px;
        border: 1px solid #222;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .exercise-card h4 {
        color: var(--text-main);
        font-family: 'Oswald', sans-serif;
        text-transform: uppercase;
        font-size: 24px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px dashed #333;
        text-align: center;
        letter-spacing: 1px;
    }
    .category-badge {
        display: inline-block;
        background-color: rgba(239, 68, 68, 0.15);
        color: var(--brand-red);
        border: 1px solid rgba(239, 68, 68, 0.3);
        padding: 5px 15px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 15px;
        letter-spacing: 1px;
    }

    .exercise-stats {
        display: flex; justify-content: space-around;
        background-color: #0a0a0a; border: 1px solid #222;
        border-radius: 12px; padding: 20px; margin-bottom: 25px; text-align: center;
    }
    .stat-item { color: #e0e0e0; width: 33%; border-right: 1px solid #222; }
    .stat-item:last-child { border-right: none; }
    .stat-item .stat-value {
        font-family: 'Oswald', sans-serif; font-size: 28px; color: #facc15;
        display: block; line-height: 1.2; margin-bottom: 5px;
    }
    .stat-item .stat-label { font-size: 11px; color: #888; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; }

    .exercise-guide { font-size: 14px; line-height: 1.6; color: #aaa; text-align: left;}
    .exercise-guide h5 { color: #fff; font-family: 'Oswald', sans-serif; font-size: 18px; text-transform: uppercase; margin: 20px 0 10px; letter-spacing: 0.5px; }
    .exercise-guide h5 i { color: var(--brand-red); margin-right: 10px; width: 20px; text-align: center; }
    .exercise-guide p { margin-bottom: 15px; padding-left: 30px; }

    .media-wrapper {
        position: relative; width: 100%; padding-top: 56.25%;
        background-color: #000; border-radius: 12px; overflow: hidden;
        border: 1px solid #333; margin-bottom: 20px;
    }
    .media-wrapper .video-js, .media-wrapper img {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;
    }
    img { font-size: 0; color: transparent; text-indent: -9999px; }
    .vjs-default-skin .vjs-progress-holder .vjs-play-progress, .vjs-default-skin .vjs-volume-level { background-color: var(--brand-red); }
    .vjs-default-skin .vjs-big-play-button {
        background: rgba(239, 68, 68, 0.8) !important; border-color: transparent !important;
        border-radius: 50%; width: 60px !important; height: 60px !important;
        line-height: 60px !important; font-size: 30px !important;
        margin-top: -30px !important; margin-left: -30px !important; transition: 0.3s;
    }
    .vjs-default-skin:hover .vjs-big-play-button { transform: scale(1.1); background: var(--brand-red) !important; }

    .btn-return {
        background-color: transparent; border: 2px solid var(--brand-red); color: var(--text-main);
        padding: 14px 40px; border-radius: var(--border-radius-pill); font-family: 'Oswald', sans-serif;
        font-size: 16px; text-transform: uppercase; font-weight: 600; letter-spacing: 1px;
        transition: all 0.3s; text-decoration: none; display: inline-block;
    }
    .btn-return:hover { background-color: var(--brand-red); box-shadow: 0 5px 20px rgba(239, 68, 68, 0.4); color: #fff; text-decoration: none; }

    .action-bar { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 25px; }
    .btn-action-pill {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 12px 24px; border-radius: var(--border-radius-pill);
        font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 13px; font-weight: 600;
        letter-spacing: 0.05em; border: none; cursor: pointer; transition: all 0.3s ease; text-decoration: none;
    }
    .btn-action-pill i { margin-right: 8px; }
    .btn-orange { background-color: var(--brand-orange); color: #fff; }
    .btn-orange:hover { background-color: #d96f1c; transform: translateY(-2px); color: #fff; }
    .btn-dark { background-color: #252525; color: var(--text-main); }
    .btn-dark:hover { background-color: #333; transform: translateY(-2px); color: #fff; }

    .empty-routine-container {
        background-color: var(--bg-panel); border: 1px solid rgba(255,255,255,0.05);
        border-radius: 16px; padding: 50px 30px; max-width: 550px; margin: 40px auto; display: none;
    }
    .empty-icon-wrap { font-size: 48px; color: var(--brand-red); margin-bottom: 20px; }
    .empty-routine-container h3 { font-family: 'Oswald', sans-serif; color: var(--text-main); font-size: 24px; text-transform: uppercase; margin-bottom: 10px; }
    .empty-routine-container p { color: var(--text-muted); font-size: 15px; margin-bottom: 30px; }

    .floating-timer {
        position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%);
        background-color: var(--bg-panel); border: 2px solid var(--brand-red);
        color: var(--text-main); padding: 10px 30px; border-radius: var(--border-radius-pill);
        font-family: 'Oswald', sans-serif; font-size: 24px; font-weight: bold;
        box-shadow: 0 10px 30px rgba(0,0,0,0.8); z-index: 9999;
        transition: bottom 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex; align-items: center; gap: 15px;
    }
    .floating-timer.active { bottom: 30px; }
    .floating-timer.done { border-color: var(--brand-green); color: var(--brand-green); }
    .floating-timer i { cursor: pointer; color: var(--text-muted); transition: 0.3s; }
    .floating-timer i:hover { color: var(--text-main); }
    
    .timer-controls { display: flex; gap: 8px; margin: 0 15px; }
    .btn-timer-adj {
        background-color: transparent; border: 1px solid var(--brand-red); color: var(--text-main);
        border-radius: 5px; padding: 2px 8px; font-family: 'Oswald', sans-serif; font-size: 14px;
        cursor: pointer; transition: all 0.2s;
    }
    .btn-timer-adj:hover { background-color: var(--brand-red); color: #fff; transform: translateY(-2px); }

    @media (max-width: 991px) { .exercise-card { max-width: 800px; margin-left: auto; margin-right: auto; } }
    @media (max-width: 768px) {
        .class-timetable-section { padding-top: 110px; }
        .section-title h2 { font-size: 28px; }
        .exercise-card { padding: 20px 15px; }
        .stat-item .stat-value { font-size: 22px; }
        .stat-item .stat-label { font-size: 10px; }
        .nav-tabs .nav-link { padding: 10px 15px; font-size: 12px; }
        .timer-controls { margin: 0 10px; gap: 5px; }
        .btn-timer-adj { font-size: 12px; padding: 2px 6px; }
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
            <div class="col-lg-10 offset-lg-1">
                
                <div class="text-center py-5" id="loaderState">
                    <div class="spinner-border text-danger" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted" style="font-size: 16px;">Obteniendo tus ejercicios, prepárate...</p>
                </div>

                <div class="empty-routine-container text-center" id="emptyState">
                    <div class="empty-icon-wrap">
                        <i class="fa-solid fa-dumbbell fa-bounce"></i>
                    </div>
                    <h3>Tu plan está en fase de personalización</h3>
                    <p>Nuestros entrenadores están diseñando los bloques específicos para tu perfil de <strong><?= htmlspecialchars($nombreNivelActual) ?></strong>.</p>
                    <div class="action-bar">
                        <a href="index.php?page=imc" class="btn-action-pill btn-orange">
                            <i class="fa-solid fa-calculator"></i> Actualizar IMC
                        </a>
                    </div>
                </div>

                <div id="routineContainer"></div>

            </div>
        </div>

        <div class="row mt-5">
            <div class="col-lg-12 text-center">
                <a href="index.php?page=user_rutina" class="btn-return"><i class="fas fa-arrow-left mr-2"></i> Volver a Selección de Nivel</a>
            </div>
        </div>

    </div>
</section>

<div class="floating-timer" id="restTimer">
    <span id="timeDisplay"><i class="fa-solid fa-stopwatch"></i> 00:00</span>
    
    <div class="timer-controls">
        <button type="button" class="btn-timer-adj" id="subTime" title="Quitar 30 seg">-30s</button>
        <button type="button" class="btn-timer-adj" id="addTime" title="Añadir 30 seg">+30s</button>
    </div>

    <i class="fa-solid fa-times-circle" id="closeTimer" title="Cerrar temporizador"></i>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<script>
    function playNativeBeep() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            
            const audioCtx = new AudioContext();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(800, audioCtx.currentTime);
            
            gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime); 

            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            oscillator.start();
            
            setTimeout(() => { oscillator.stop(); }, 400);
            
        } catch (e) {
            console.log("No se pudo reproducir el beep: ", e);
        }
    }

    let timerInterval;
    let timeLeft = 0;
    const restTimerEl = document.getElementById('restTimer');
    const timeDisplayEl = document.getElementById('timeDisplay');
    const closeTimerBtn = document.getElementById('closeTimer');
    const addTimeBtn = document.getElementById('addTime');
    const subTimeBtn = document.getElementById('subTime');

    function startRestTimer(seconds) {
        clearInterval(timerInterval);
        restTimerEl.classList.add('active');
        restTimerEl.classList.remove('done');
        
        timeLeft = seconds;
        updateTimerUI(timeLeft);

        timerInterval = setInterval(() => {
            timeLeft--;
            updateTimerUI(timeLeft);
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                restTimerEl.classList.add('done');
                timeDisplayEl.innerHTML = "<i class='fa-solid fa-bell fa-shake'></i> ¡A ENTRENAR!";
                playNativeBeep();
                setTimeout(() => { restTimerEl.classList.remove('active'); }, 5000);
            }
        }, 1000);
    }

    function updateTimerUI(seconds) {
        if (seconds < 0) seconds = 0;
        const m = Math.floor(seconds / 60).toString().padStart(2, '0');
        const s = (seconds % 60).toString().padStart(2, '0');
        timeDisplayEl.innerHTML = `<i class="fa-solid fa-stopwatch"></i> Descanso: ${m}:${s}`;
    }

    addTimeBtn.addEventListener('click', () => {
        if (timeLeft > 0) {
            timeLeft += 30;
            updateTimerUI(timeLeft);
        }
    });

    subTimeBtn.addEventListener('click', () => {
        if (timeLeft > 30) {
            timeLeft -= 30;
            updateTimerUI(timeLeft);
        } else {
            timeLeft = 1;
        }
    });

    closeTimerBtn.addEventListener('click', () => {
        clearInterval(timerInterval);
        restTimerEl.classList.remove('active');
    });

    $(function() {
        $(document).on('click', '.log-progress-btn', function() {
            const idEjercicio = parseInt($(this).attr('data-ejercicio-id'), 10);
            const nombreEjercicio = $(this).attr('data-ejercicio-nombre');

            Swal.fire({
                title: 'Registrar Rendimiento',
                html: `
                    <div style="text-align: left; font-family: 'Muli', sans-serif;">
                        <p style="margin-bottom: 15px; color: #ffffff;"><strong>${nombreEjercicio}</strong></p>
                        <label style="color: #a1a1aa; font-size:12px;">Peso levantado (KG):</label>
                        <input type="number" id="peso_kg" class="swal2-input" placeholder="Ej. 60.5" step="0.5" min="0" style="background: #050505; color: #fff; border: 1px solid #333; margin-top:5px; margin-bottom:15px;">
                        <label style="color: #a1a1aa; font-size:12px;">Repeticiones logradas:</label>
                        <input type="number" id="reps" class="swal2-input" placeholder="Ej. 10" min="1" step="1" style="background: #050505; color: #fff; border: 1px solid #333; margin-top:5px;">
                    </div>
                `,
                background: '#1a1a1a',
                color: '#ffffff',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#ef4444',
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-save"></i> Guardar y Descansar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const peso = document.getElementById('peso_kg').value;
                    const reps = document.getElementById('reps').value;
                    
                    if (!peso || !reps) return Swal.showValidationMessage('Ambos campos son obligatorios');
                    if (isNaN(idEjercicio) || idEjercicio === 0) return Swal.showValidationMessage('Error interno: ID del ejercicio no válido.');
                    
                    return { 
                        peso: parseFloat(peso), 
                        reps: parseInt(reps, 10), 
                        id_ejercicio: idEjercicio 
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api/registrar_progreso.php', { 
                        method: 'POST', 
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(result.value) 
                    })
                    .then(response => response.json())
                    .then(res => {
                        if (res.status === 'success') {
                            Swal.fire({ icon: 'success', title: '¡Registrado!', text: 'Iniciando temporizador...', background: '#1a1a1a', color: '#ffffff', showConfirmButton: false, timer: 1500 })
                            .then(() => { startRestTimer(90); });
                        } else {
                            throw new Error(res.message);
                        }
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Error', text: err.message, background: '#1a1a1a', color: '#ffffff' });
                    });
                }
            });
        });

        $(document).on('click', '.calc-rm-btn', function() {
            Swal.fire({
                title: 'Calculadora 1RM',
                html: `
                    <div style="text-align: left; font-family: 'Muli', sans-serif;">
                        <label style="color: #a1a1aa; font-size:12px;">Peso levantado (KG):</label>
                        <input type="number" id="rm_peso" class="swal2-input" placeholder="Ej. 100" step="0.5" min="1" style="background: #050505; color: #fff; border: 1px solid #333; margin-top:5px; margin-bottom:15px;">
                        <label style="color: #a1a1aa; font-size:12px;">Repeticiones (Max 12):</label>
                        <input type="number" id="rm_reps" class="swal2-input" placeholder="Ej. 5" min="1" max="12" step="1" style="background: #050505; color: #fff; border: 1px solid #333; margin-top:5px;">
                    </div>
                `,
                background: '#1a1a1a',
                color: '#ffffff',
                confirmButtonColor: '#F28123',
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-bolt"></i> Calcular',
                cancelButtonText: 'Cerrar',
                preConfirm: () => {
                    const w = parseFloat(document.getElementById('rm_peso').value);
                    const r = parseInt(document.getElementById('rm_reps').value);
                    if (!w || !r || r < 1 || w <= 0) return Swal.showValidationMessage('Ingresa valores válidos.');
                    if (r > 12) return Swal.showValidationMessage('La fórmula pierde precisión con más de 12 reps.');
                    return Math.round(w * (1 + (r / 30)));
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Tu 1RM Estimado',
                        html: `<h1 style="color: #10b981; font-size: 48px; margin: 20px 0;">${result.value} <span style="font-size:24px;">KG</span></h1>
                               <p style="color: #a1a1aa; font-size: 14px;">Utiliza este valor para calcular los porcentajes de carga de tus series.</p>`,
                        background: '#1a1a1a',
                        color: '#ffffff',
                        confirmButtonColor: '#ef4444'
                    });
                }
            });
        });

        if(typeof loadRoutine === "function") {
            loadRoutine(<?= $level ?>, <?= $gender ?>);
        } else {
            console.error("La función loadRoutine no está definida. Revisa tus scripts (assets/js/routine.js).");
            $('#loaderState').hide();
            $('#emptyState').show();
        }
    });
</script>
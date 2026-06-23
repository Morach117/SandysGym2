<?php
// pages/progreso.php

// --- INCLUDES OBLIGATORIOS ---
require_once 'conn.php';
include('./api/select_data.php');

// 1. VALIDACIÓN DE SESIÓN ESTRICTA
if (empty($selSocioData['soc_id_socio'])) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

$miId = (int)$selSocioData['soc_id_socio'];

// 2. CONSULTA DE HISTORIAL (Optimizada para fechas)
$queryProgreso = "SELECT 
                    p.pro_id_ejercicio, 
                    p.pro_peso_kg, 
                    p.pro_repeticiones, 
                    p.pro_fecha,
                    e.nombre_ejercicio
                  FROM san_rutinas_progreso p
                  INNER JOIN ejercicios e ON p.pro_id_ejercicio = e.id_ejercicio
                  WHERE p.pro_id_socio = :miId
                  ORDER BY p.pro_fecha ASC";

try {
    $stmt = $conn->prepare($queryProgreso);
    $stmt->bindParam(':miId', $miId, PDO::PARAM_INT);
    $stmt->execute();
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDOException [progreso]: " . $e->getMessage());
    $historial = [];
}

// 3. PROCESAMIENTO DE DATOS Y KPI's
$estadisticas = [];
$diasUnicos = [];
$totalSeries = 0;
$tonelajeTotal = 0;

foreach ($historial as $reg) {
    $idEj = $reg['pro_id_ejercicio'];
    $peso = (float)$reg['pro_peso_kg'];
    $reps = (int)$reg['pro_repeticiones'];
    $fechaCorta = date('Y-m-d', strtotime($reg['pro_fecha']));
    
    // Contadores globales
    $diasUnicos[$fechaCorta] = true; 
    $totalSeries++;
    $tonelajeTotal += ($peso * $reps);

    if (!isset($estadisticas[$idEj])) {
        $estadisticas[$idEj] = [
            'nombre' => $reg['nombre_ejercicio'],
            'primer_peso' => $peso,
            'ultimo_peso' => $peso,
            'peso_maximo' => $peso,
            'total_series' => 0
        ];
    }

    $estadisticas[$idEj]['ultimo_peso'] = $peso;
    $estadisticas[$idEj]['total_series']++;
    
    if ($peso > $estadisticas[$idEj]['peso_maximo']) {
        $estadisticas[$idEj]['peso_maximo'] = $peso;
    }
}

$totalDiasEntrenados = count($diasUnicos);

// Ordenar por volumen de series (Top Ejercicios)
usort($estadisticas, function($a, $b) {
    return $b['total_series'] <=> $a['total_series'];
});

// 4. PREPARACIÓN DE DATOS PARA CHART.JS (Top 6 ejercicios con progreso)
$chartLabels = [];
$chartDataInicio = [];
$chartDataActual = [];
$contadorGrafica = 0;

foreach ($estadisticas as $stat) {
    if ($contadorGrafica >= 6) break;
    if ($stat['ultimo_peso'] > 0 || $stat['primer_peso'] > 0) {
        $chartLabels[] = $stat['nombre'];
        $chartDataInicio[] = $stat['primer_peso'];
        $chartDataActual[] = $stat['ultimo_peso'];
        $contadorGrafica++;
    }
}
?>

<style>
    body { background-color: #050505; color: #e0e0e0; font-family: 'Muli', sans-serif; }

    /* Hero Header Compacto */
    .progress-hero {
        padding: 120px 0 40px;
        background: linear-gradient(180deg, rgba(5,5,5,0.9), #050505), url('./assets/img/hero/hero-progress.jpg');
        background-size: cover; background-position: center; text-align: center; border-bottom: 1px solid #1a1a1a;
    }
    .hero-title { font-family: 'Oswald', sans-serif; font-size: 34px; color: #fff; text-transform: uppercase; margin-bottom: 5px; }

    /* Tarjetas KPI Amigables */
    .kpi-wrapper {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-top: -30px; position: relative; z-index: 2;
    }
    .kpi-card {
        background: #1a1a1a; border: 1px solid #333; border-radius: 16px; padding: 20px;
        display: flex; align-items: center; gap: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.5);
    }
    .kpi-icon {
        width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px;
    }
    .icon-fire { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .icon-weight { background: rgba(242, 129, 35, 0.1); color: #F28123; }
    .icon-calendar { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    
    .kpi-data { display: flex; flex-direction: column; }
    .kpi-value { font-family: 'Oswald', sans-serif; font-size: 26px; color: #fff; font-weight: 700; line-height: 1.2; }
    .kpi-label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Contenedor Gráfica y Lista */
    .dashboard-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 40px; margin-bottom: 60px;
    }
    .panel-box {
        background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 16px; padding: 25px;
    }
    .panel-title { font-family: 'Oswald', sans-serif; font-size: 20px; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

    /* Lista Estilizada */
    .progress-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 15px 0; border-bottom: 1px dashed #333;
    }
    .progress-item:last-child { border-bottom: none; }
    .prog-name { font-weight: 600; color: #fff; font-size: 15px; }
    .prog-sub { color: #666; font-size: 12px; }
    
    .badge-gain { background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 13px; }
    .badge-loss { background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 13px; }
    .badge-flat { background: rgba(255, 255, 255, 0.05); color: #aaa; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 13px; }

    .btn-back {
        position: absolute; top: 90px; left: 20px; z-index: 100; display: inline-flex; align-items: center; background: #1a1a1a;
        color: #fff !important; border: 1px solid #333; padding: 8px 20px; border-radius: 50px; text-decoration: none !important; transition: 0.3s;
    }
    .btn-back:hover { background: #ef4444; border-color: #ef4444; box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4); }

    @media (max-width: 992px) {
        .dashboard-grid { grid-template-columns: 1fr; }
    }
</style>

<a href="index.php?page=user_home" class="btn-back"><i class="fa-solid fa-arrow-left mr-2"></i> Volver</a>

<section class="progress-hero">
    <div class="container">
        <h1 class="hero-title">Tu Rendimiento</h1>
    </div>
</section>

<div class="container">
    
    <div class="kpi-wrapper">
        <div class="kpi-card">
            <div class="kpi-icon icon-calendar"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="kpi-data">
                <span class="kpi-value"><?= $totalDiasEntrenados ?></span>
                <span class="kpi-label">Días de Constancia</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon icon-fire"><i class="fa-solid fa-fire"></i></div>
            <div class="kpi-data">
                <span class="kpi-value"><?= $totalSeries ?></span>
                <span class="kpi-label">Series Completadas</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon icon-weight"><i class="fa-solid fa-dumbbell"></i></div>
            <div class="kpi-data">
                <span class="kpi-value"><?= number_format($tonelajeTotal, 0) ?> <span style="font-size: 14px; color:#888;">KG</span></span>
                <span class="kpi-label">Volumen Total Movido</span>
            </div>
        </div>
    </div>

    <?php if ($totalDiasEntrenados > 0): ?>
        <div class="dashboard-grid">
            
            <div class="panel-box">
                <h3 class="panel-title"><i class="fa-solid fa-chart-line" style="color: #F28123;"></i> Evolución de Fuerza (Top 6)</h3>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="fuerzaChart"></canvas>
                </div>
            </div>

            <div class="panel-box">
                <h3 class="panel-title"><i class="fa-solid fa-trophy" style="color: #10b981;"></i> Récords y Progresión</h3>
                <div class="list-wrapper" style="max-height: 300px; overflow-y: auto; padding-right: 10px;">
                    <?php foreach ($estadisticas as $stat): 
                        $diferencia = $stat['ultimo_peso'] - $stat['primer_peso'];
                        if ($diferencia > 0) { $clase = 'badge-gain'; $txt = '+'.$diferencia.' kg'; }
                        elseif ($diferencia < 0) { $clase = 'badge-loss'; $txt = $diferencia.' kg'; }
                        else { $clase = 'badge-flat'; $txt = '='; }
                    ?>
                        <div class="progress-item">
                            <div>
                                <div class="prog-name"><?= htmlspecialchars($stat['nombre']) ?></div>
                                <div class="prog-sub">Max: <?= $stat['peso_maximo'] ?> kg | <?= $stat['total_series'] ?> series</div>
                            </div>
                            <div class="<?= $clase ?>"><?= $txt ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    <?php else: ?>
        <div class="text-center p-5 mt-5 panel-box">
            <i class="fa-solid fa-seedling" style="font-size: 40px; color: #444; margin-bottom: 15px;"></i>
            <h4 style="color: #fff; font-family: 'Oswald', sans-serif;">Comienza tu viaje</h4>
            <p style="color: #888;">Tus estadísticas y gráficos aparecerán aquí en cuanto registres tu primera serie.</p>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    <?php if ($totalDiasEntrenados > 0): ?>
    const ctx = document.getElementById('fuerzaChart').getContext('2d');
    
    // Inyección segura de JSON desde PHP
    const labels = <?= json_encode($chartLabels) ?>;
    const dataInicio = <?= json_encode($chartDataInicio) ?>;
    const dataActual = <?= json_encode($chartDataActual) ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Peso Inicial (kg)',
                    data: dataInicio,
                    backgroundColor: 'rgba(255, 255, 255, 0.1)',
                    borderColor: 'rgba(255, 255, 255, 0.3)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Último Peso (kg)',
                    data: dataActual,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)', // Verde Sandys Gym
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#bbb', font: { family: 'Muli' } } },
                tooltip: { backgroundColor: '#111', titleColor: '#fff', bodyColor: '#ccc', borderColor: '#333', borderWidth: 1 }
            },
            scales: {
                y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#888' }, beginAtZero: true },
                x: { grid: { display: false }, ticks: { color: '#888', font: { size: 10 } } }
            }
        }
    });
    <?php endif; ?>
});
</script>
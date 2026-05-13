<?php
global $conn;
$faqs = [];
$error_faq = null;

if (!isset($conn) || !($conn instanceof PDO)) {
    $path_conexion = __DIR__ . '/conn.php'; 
    if (file_exists($path_conexion)) {
        require_once $path_conexion;
    } else {
        $error_faq = "Error: Dependencia de base de datos no resuelta.";
    }
}

if (!$error_faq && isset($conn)) {
    try {
        $stmtFaq = $conn->prepare("SELECT pregunta, respuesta FROM san_faq WHERE estado = 1 ORDER BY orden ASC, id_faq DESC");
        $stmtFaq->execute();
        $faqs = $stmtFaq->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error DB FAQ: " . $e->getMessage());
        $error_faq = "Error al procesar la solicitud de preguntas.";
    }
}
?>

<style>
    .gym-faq-section {
        background-color: #050505;
        /* Incremento de 80px a 180px para despejar el navbar fijo */
        padding-top: 180px; 
        padding-bottom: 100px;
        min-height: 80vh;
    }
    .gym-page-title {
        text-align: center;
        color: #ffffff;
        font-size: 42px;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 60px;
        letter-spacing: 1.5px;
    }
    .gym-faq-container {
        max-width: 850px;
        margin: 0 auto;
    }
    .gym-faq-item {
        background-color: #1a1a1a;
        border: 1px solid #333333;
        border-radius: 10px;
        margin-bottom: 16px;
        transition: all 0.3s ease;
    }
    .gym-faq-item:hover {
        border-color: #ef4444;
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.1);
    }
    .gym-faq-summary {
        padding: 20px 25px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 16px;
        font-weight: 700;
        color: #ffffff;
        list-style: none;
        text-transform: uppercase;
    }
    .gym-faq-summary::-webkit-details-marker {
        display: none;
    }
    .gym-faq-icon {
        background-color: #050505;
        color: #ef4444;
        min-width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        border: 1px solid #333333;
        transition: transform 0.3s ease;
    }
    details[open] .gym-faq-icon {
        transform: rotate(180deg);
        border-color: #ef4444;
    }
    .gym-faq-content {
        padding: 0 25px 25px 25px;
        color: #b3b3b3;
        line-height: 1.8;
        border-top: 1px solid #333333;
        margin-top: 5px;
        padding-top: 20px;
        font-size: 15px;
    }
    .gym-alert-box {
        background-color: #1a1a1a;
        border: 1px solid #333333;
        padding: 40px 20px;
        border-radius: 10px;
        text-align: center;
    }
    .gym-alert-error {
        border-color: #ef4444;
        color: #ef4444;
    }
</style>
<section class="gym-faq-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h2 class="gym-page-title">PREGUNTAS FRECUENTES</h2>
                <div class="gym-faq-container">
                    
                    <?php if ($error_faq): ?>
                        <div class="gym-alert-box gym-alert-error">
                            <i class="fa-solid fa-triangle-exclamation" style="font-size: 32px; margin-bottom: 15px;"></i>
                            <h4 style="color: #ef4444; font-weight: bold; margin: 0;"><?= htmlspecialchars($error_faq) ?></h4>
                        </div>
                    <?php elseif (count($faqs) > 0): ?>
                        <?php foreach ($faqs as $faq): ?>
                            <details class="gym-faq-item">
                                <summary class="gym-faq-summary">
                                    <span style="margin-right: 20px;"><?= htmlspecialchars($faq['pregunta'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="gym-faq-icon">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </span>
                                </summary>
                                <div class="gym-faq-content">
                                    <?= nl2br(htmlspecialchars($faq['respuesta'], ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="gym-alert-box" style="color: #a9a9a9;">
                            <i class="fa-solid fa-clipboard-question" style="font-size: 40px; margin-bottom: 15px;"></i>
                            <h5 style="color: #ffffff; margin: 0;">No hay preguntas frecuentes registradas por el momento.</h5>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</section>
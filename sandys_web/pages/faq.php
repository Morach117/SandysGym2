<?php
global $conn;
$faqs = [];
$error_faq = null;

if (!isset($conn) || !($conn instanceof PDO)) {
    $path_conexion = __DIR__ . '/../conn.php'; 
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
    :root {
        --bg-color: #050505; 
        --input-bg: #1a1a1a; 
        --accent-red: #ef4444; 
        --accent-green: #10b981; 
        --accent-orange: #F28123;
        --text-muted: #888888;
    }

    .gym-faq-section {
        background-color: var(--bg-color);
        padding-top: 140px;
        padding-bottom: 80px;
        min-height: 80vh;
    }

    .gym-faq-container {
        max-width: 850px;
        margin: 0 auto;
    }

    .gym-faq-item {
        background: var(--input-bg);
        border: 1px solid #333333;
        border-radius: 12px;
        margin-bottom: 16px;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .gym-faq-item:hover {
        border-color: var(--accent-orange);
        box-shadow: 0 5px 15px rgba(242, 129, 35, 0.1);
    }

    details[open].gym-faq-item {
        border-color: var(--accent-orange);
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
        letter-spacing: 0.5px;
        transition: background-color 0.3s ease;
    }

    .gym-faq-summary::-webkit-details-marker {
        display: none;
    }

    .gym-faq-icon {
        background-color: var(--bg-color);
        color: var(--accent-orange);
        min-width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        border: 1px solid #333333;
        transition: all 0.3s ease;
    }

    details[open] .gym-faq-icon {
        transform: rotate(180deg);
        background-color: var(--accent-orange);
        color: var(--bg-color);
        border-color: var(--accent-orange);
    }

    .gym-faq-content {
        padding: 0 25px 25px 25px;
        color: #cccccc;
        line-height: 1.8;
        border-top: 1px solid #333333;
        margin-top: 5px;
        padding-top: 20px;
        font-size: 15px;
    }

    .gym-alert-box {
        background: var(--input-bg);
        border: 1px solid #333333;
        padding: 40px 20px;
        border-radius: 12px;
        text-align: center;
    }
    .gym-alert-error {
        border-color: var(--accent-red);
        color: var(--accent-red);
    }
</style>

<section class="gym-faq-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                
                <div class="section-title text-center mb-5">
                    <h2 style="font-size: 42px; text-transform: uppercase; font-weight: 800; color: #ffffff; margin-bottom: 0;">
                        Preguntas Frecuentes
                    </h2>
                </div>

                <div class="gym-faq-container">
                    
                    <?php if ($error_faq): ?>
                        <div class="gym-alert-box gym-alert-error">
                            <i class="fa-solid fa-triangle-exclamation" style="font-size: 32px; margin-bottom: 15px;"></i>
                            <h4 style="color: var(--accent-red); font-weight: bold; margin: 0;"><?= htmlspecialchars($error_faq) ?></h4>
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
                        <div class="gym-alert-box" style="color: var(--text-muted);">
                            <i class="fa-solid fa-clipboard-question" style="font-size: 40px; margin-bottom: 15px; color: var(--accent-orange);"></i>
                            <h5 style="color: #ffffff; margin: 0;">No hay preguntas frecuentes registradas por el momento.</h5>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</section>
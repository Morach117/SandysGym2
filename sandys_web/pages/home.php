<?php
// Módulo: Vista Pública Landing Page (Frontend)
// Requiere conexión PDO instanciada en $conn

try {
    if (!isset($conn)) {
        throw new Exception("Variable PDO \$conn no encontrada.");
    }

    // 1. Obtener Configuración de UI (Colores)
    $stmtC = $conn->prepare("SELECT * FROM san_landing_config WHERE id = 1 LIMIT 1");
    $stmtC->execute();
    $configUI = $stmtC->fetch(PDO::FETCH_ASSOC);

    // Si no hay configuración, definir fallbacks seguros por defecto
    if (!$configUI) {
        $configUI = [
            'color_bg' => '#050505', 'color_input' => '#1a1a1a', 
            'color_accent_red' => '#ef4444', 'color_accent_green' => '#10b981', 
            'color_accent_orange' => '#F28123', 'color_text_muted' => '#888888'
        ];
    }

    // 2. Obtener Hero (Solo Imágenes - CORREGIDO)
    // Se eliminan 'subtitulo' y 'titulo_html' porque fueron eliminados de la base de datos
    $stmtH = $conn->prepare("SELECT img_desktop, img_mobile FROM san_landing_hero WHERE estado = 1 ORDER BY id_hero DESC");
    $stmtH->execute();
    $heroes = $stmtH->fetchAll(PDO::FETCH_ASSOC);

    // 3. Obtener Planes
    $stmtP = $conn->prepare("SELECT nombre, precio, frecuencia, beneficios_json, url_boton FROM san_landing_planes WHERE estado = 1 ORDER BY orden ASC, precio ASC");
    $stmtP->execute();
    $planes = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($planes as &$plan) {
        $plan['beneficios'] = json_decode($plan['beneficios_json'], true) ?: [];
        unset($plan['beneficios_json']);
    }

    // 4. Obtener Galería
    $stmtG = $conn->prepare("SELECT imagen_url, es_wide FROM san_landing_galeria WHERE estado = 1 ORDER BY id_galeria DESC");
    $stmtG->execute();
    $galeria = $stmtG->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<div style='color:red; background:#111; padding:20px;'>Error de Base de Datos: " . htmlspecialchars($e->getMessage()) . "</div>");
} catch (Exception $e) {
    die("<div style='color:red; background:#111; padding:20px;'>Error Crítico: " . htmlspecialchars($e->getMessage()) . "</div>");
}
?>

<style>
    :root {
        --bg-color:
            <?= htmlspecialchars($configUI['color_bg']) ?>
        ;
        --input-bg:
            <?= htmlspecialchars($configUI['color_input']) ?>
        ;
        --accent-red:
            <?= htmlspecialchars($configUI['color_accent_red']) ?>
        ;
        --accent-green:
            <?= htmlspecialchars($configUI['color_accent_green']) ?>
        ;
        --accent-orange:
            <?= htmlspecialchars($configUI['color_accent_orange']) ?>
        ;
        --text-muted:
            <?= htmlspecialchars($configUI['color_text_muted']) ?>
        ;
    }

    body {
        background-color: var(--bg-color);
        color: #ffffff;
        font-family: 'Muli', sans-serif;
    }

    .fade-update {
        animation: fadeInUpdate 0.8s ease-in-out;
    }

    @keyframes fadeInUpdate {
        0% {
            opacity: 0;
            transform: translateY(10px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .rounded-pill {
        border-radius: 50rem !important;
    }

    /* HERO */
    .hero-item-container {
        position: relative;
        width: 100%;
        height: 85vh;
        overflow: hidden;
        background-color: var(--bg-color);
    }

    .hero-img-desktop {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    .hero-img-mobile {
        display: none;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    /* AMENITIES */
    .amenity-card {
        background: var(--input-bg);
        border: 1px solid #333;
        border-radius: 16px;
        padding: 40px 20px;
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        height: 100%;
    }

    .amenity-card:hover {
        transform: translateY(-5px);
        border-color: var(--accent-orange);
        box-shadow: 0 10px 20px rgba(242, 129, 35, 0.1);
    }

    .amenity-icon {
        font-size: 45px;
        color: var(--accent-orange);
        margin-bottom: 20px;
    }

    .amenity-title {
        font-size: 18px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 10px;
        color: #ffffff;
    }

    /* PRICING */
    .plan-card {
        background: var(--input-bg);
        border: 1px solid #333;
        border-radius: 16px;
        padding: 32px 24px;
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: transform 0.3s ease, border-color 0.3s ease;
        position: relative;
    }

    .plan-card:hover {
        transform: translateY(-5px);
        border-color: var(--accent-orange);
    }

    .plan-card.highlight {
        border: 2px solid var(--accent-orange);
    }

    .plan-badge {
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--accent-orange);
        color: var(--bg-color);
        font-weight: 800;
        font-size: 12px;
        padding: 4px 16px;
        border-radius: 50rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .plan-title {
        color: #ffffff;
        font-size: 24px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 16px;
    }

    .plan-price-wrapper {
        margin-bottom: 24px;
        padding-bottom: 24px;
        border-bottom: 1px solid #333;
    }

    .plan-currency {
        font-size: 20px;
        vertical-align: top;
        color: var(--accent-green);
    }

    .plan-amount {
        font-size: 42px;
        font-weight: 800;
        line-height: 1;
        color: #ffffff;
    }

    .plan-frequency {
        font-size: 14px;
        color: var(--text-muted);
    }

    .plan-benefits {
        flex-grow: 1;
        margin-bottom: 32px;
    }

    .plan-benefits li {
        position: relative;
        padding-left: 32px;
        margin-bottom: 16px;
        font-size: 15px;
        color: #cccccc;
        line-height: 1.4;
    }

    .plan-benefits li i {
        position: absolute;
        left: 0;
        top: 3px;
        color: var(--accent-green);
        font-size: 18px;
    }

    .plan-btn {
        background: var(--accent-orange);
        color: var(--bg-color) !important;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 14px;
        border: none;
        transition: filter 0.3s;
        text-align: center;
        text-decoration: none;
        display: block;
    }

    .plan-btn:hover {
        filter: brightness(1.1);
        text-decoration: none;
    }

    /* APP SECTION */
    .app-section {
        background: linear-gradient(135deg, #111111 0%, var(--bg-color) 100%);
        border-top: 1px solid #222;
        border-bottom: 1px solid #222;
        padding: 80px 0;
    }

    .app-image-wrapper img {
        width: 100%;
        max-width: 400px;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.8);
    }

    /* GALLERY */
    .gallery-section {
        padding: 80px 0;
    }

    .gs-item {
        height: 250px;
        background-size: cover;
        background-position: center;
        margin-bottom: 20px;
        border-radius: 12px;
        position: relative;
        transition: transform 0.3s ease;
    }

    .gs-item:hover {
        transform: scale(1.02);
        z-index: 2;
    }

    .gs-item.grid-wide {
        height: 520px;
    }

    /* CTA */
    .cta-bottom-section {
        background-color: var(--accent-orange);
        padding: 60px 0;
        text-align: center;
        color: var(--bg-color);
    }

    @media (max-width: 768px) {
        .hero-img-desktop {
            display: none;
        }

        .hero-img-mobile {
            display: block;
        }

        .app-image-wrapper {
            margin-top: 40px;
            text-align: center;
        }
    }
</style>

<div id="app-landing">
    <section class="hero-section">
        <div class="hs-slider owl-carousel" id="hero-container"></div>
    </section>

    <section class="amenities-section" style="padding: 80px 0;">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-12 text-center">
                    <h2
                        style="font-size: 38px; text-transform: uppercase; font-weight: 800; color: #ffffff; margin-bottom: 10px;">
                        Vive la experiencia <span style="color: var(--accent-orange);">Sandys Gym</span>
                    </h2>
                    <p style="color: var(--text-muted); font-size: 18px;">Instalaciones de primer nivel diseñadas para
                        tus objetivos.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="amenity-card"><i class="fa-solid fa-dumbbell amenity-icon"></i>
                        <h4 class="amenity-title">Peso Libre e Integrado</h4>
                        <p class="text-muted m-0">Equipamiento moderno para todo tipo de rutinas.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="amenity-card"><i class="fa-solid fa-person-running amenity-icon"></i>
                        <h4 class="amenity-title">Zona Cardio</h4>
                        <p class="text-muted m-0">Caminadoras, elípticas y escaladoras de última generación.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="amenity-card"><i class="fa-solid fa-people-group amenity-icon"></i>
                        <h4 class="amenity-title">Clases Grupales</h4>
                        <p class="text-muted m-0">Zumba, Cross, funcional y más actividades guiadas.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="amenity-card"><i class="fa-solid fa-shower amenity-icon"></i>
                        <h4 class="amenity-title">Regaderas y Lockers</h4>
                        <p class="text-muted m-0">Vestidores amplios, seguros y con agua caliente.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pricing-section spad" style="background-color: var(--bg-color); padding: 80px 0;">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title text-center mb-5">
                        <h2
                            style="font-size: 42px; text-transform: uppercase; font-weight: 800; color: #ffffff; margin-bottom: 0;">
                            Elige tu plan y <span style="color: var(--accent-orange);">entrena ya</span></h2>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center" id="planes-container"></div>
        </div>
    </section>

    <section class="app-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2
                        style="font-size: 38px; text-transform: uppercase; font-weight: 800; color: #ffffff; margin-bottom: 20px;">
                        Lleva tu entrenamiento<br><span style="color: var(--accent-orange);">al siguiente nivel</span>
                    </h2>
                    <p style="color: #cccccc; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">Al formar parte
                        de la familia Sandys Gym, tienes acceso a un seguimiento continuo. Consulta tus rutinas,
                        verifica tu progreso de evaluaciones corporales y mantente al tanto de nuestros avisos
                        directamente desde tu perfil de usuario.</p>
                    <a href="index.php?page=login" class="btn plan-btn rounded-pill d-inline-block px-5">Acceder a mi
                        Portal</a>
                </div>
                <div class="col-lg-6 text-lg-right app-image-wrapper">
                    <img src="https://images.unsplash.com/photo-1594882645126-14020914d58d?q=80&w=800&auto=format&fit=crop"
                        alt="Sandys Gym Ecosystem">
                </div>
            </div>
        </div>
    </section>

    <div class="gallery-section">
        <div class="container">
            <div class="row mb-4">
                <div class="col-lg-12 text-center">
                    <h2 style="font-size: 36px; text-transform: uppercase; font-weight: 800; color: #ffffff;">Nuestras
                        <span style="color: var(--accent-orange);">Instalaciones</span></h2>
                </div>
            </div>
            <div class="row" id="galeria-container"></div>
        </div>
    </div>

    <section class="cta-bottom-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 text-lg-left text-center mb-3 mb-lg-0">
                    <h2 style="font-size: 32px; font-weight: 800; text-transform: uppercase; margin: 0;">¿Listo para
                        transformar tu vida?</h2>
                    <p style="margin: 0; font-size: 18px; font-weight: 500;">Visítanos en Tuxtla y comienza hoy mismo.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-right text-center">
                    <a href="index.php?page=inscribite" class="btn rounded-pill px-5 py-3"
                        style="background-color: var(--bg-color); color: #ffffff; font-weight: 800; text-transform: uppercase; font-size: 16px;">¡Inscríbete
                        Ahora!</a>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    const dbLandingData = {
        hero: <?= json_encode($heroes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        planes: <?= json_encode($planes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        galeria: <?= json_encode($galeria, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };

    function cargarLanding() {
        renderizarHero(dbLandingData.hero);
        renderizarPlanes(dbLandingData.planes);
        renderizarGaleria(dbLandingData.galeria);
    }

    function renderizarHero(heroes) {
        const contenedor = $('#hero-container');
        if (contenedor.hasClass('owl-loaded')) {
            contenedor.trigger('destroy.owl.carousel');
            contenedor.removeClass('owl-hidden');
        }

        let html = '';
        const basePath = 'assets/img/hero/';

        if (heroes.length === 0) {
            html = '<div class="text-center text-muted" style="padding: 100px 0;">No hay elementos configurados en el Hero.</div>';
        }

        heroes.forEach(h => {
            let deskImg = h.img_desktop.startsWith('http') ? h.img_desktop : basePath + h.img_desktop;
            let mobImg = h.img_mobile.startsWith('http') ? h.img_mobile : basePath + h.img_mobile;

            html += `
        <div class="hero-item-container fade-update">
            <img src="${deskImg}" class="hero-img-desktop" alt="Hero Desktop" fetchpriority="high">
            <img src="${mobImg}" class="hero-img-mobile" alt="Hero Mobile" fetchpriority="high">
        </div>`;
        });

        contenedor.html(html);

        if ($.fn.owlCarousel && heroes.length > 0) {
            contenedor.owlCarousel({ loop: heroes.length > 1, margin: 0, nav: false, items: 1, dots: false, animateOut: 'fadeOut', animateIn: 'fadeIn', smartSpeed: 1200, autoHeight: false, autoplay: true });
        }
    }

    function renderizarPlanes(planes) {
        const contenedor = document.getElementById('planes-container');
        let html = '';

        if (planes.length === 0) return contenedor.innerHTML = '<div class="col-12 text-center text-muted">No hay planes disponibles.</div>';

        planes.forEach((p, index) => {
            let lis = '';
            if (Array.isArray(p.beneficios)) p.beneficios.forEach(b => lis += `<li><i class="fa-solid fa-circle-check"></i> ${b}</li>`);
            let precioF = parseFloat(p.precio).toFixed(2);
            let highlightClass = index === 1 ? 'highlight' : '';
            let badgeHtml = index === 1 ? `<span class="plan-badge">Más Popular</span>` : '';

            html += `
        <div class="col-lg-4 col-md-6 fade-update mb-4 d-flex">
            <div class="plan-card w-100 ${highlightClass}">
                ${badgeHtml}
                <h3 class="plan-title">Plan <b style="color: var(--accent-red);">${p.nombre}</b></h3>
                <div class="plan-price-wrapper">
                    <span class="plan-currency">$</span><span class="plan-amount">${precioF}</span><span class="plan-frequency">/ ${p.frecuencia}</span>
                </div>
                <div class="plan-benefits"><ul class="list-unstyled m-0">${lis}</ul></div>
                <a href="${p.url_boton}" class="btn plan-btn rounded-pill btn-block w-100">¡Inscríbete ya!</a>
            </div>
        </div>`;
        });
        contenedor.innerHTML = html;
    }

    function renderizarGaleria(galeria) {
        const contenedor = document.getElementById('galeria-container');
        let html = '';
        const basePath = 'assets/img/gallery/';

        if (galeria.length === 0) return contenedor.innerHTML = '<div class="col-12 text-center text-muted">No hay imágenes en la galería.</div>';

        galeria.forEach(g => {
            let cols = g.es_wide == 1 ? 'col-lg-6 col-md-12' : 'col-lg-3 col-md-6';
            let claseWide = g.es_wide == 1 ? 'grid-wide' : '';
            let imgSrc = g.imagen_url.startsWith('http') ? g.imagen_url : basePath + g.imagen_url;

            html += `
        <div class="${cols} fade-update">
            <div class="gs-item ${claseWide}" style="background-image: url('${imgSrc}');">
                <a href="${imgSrc}" class="thumb-icon image-popup"><i class="fa-solid fa-image"></i></a>
            </div>
        </div>`;
        });
        contenedor.innerHTML = html;
    }

    document.addEventListener('DOMContentLoaded', cargarLanding);
</script>
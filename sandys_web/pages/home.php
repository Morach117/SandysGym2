<?php
try {
    if (!isset($conn)) throw new Exception("Variable PDO \$conn no encontrada.");

    $stmtC = $conn->prepare("SELECT * FROM san_landing_config WHERE id = 1 LIMIT 1");
    $stmtC->execute();
    $configUI = $stmtC->fetch(PDO::FETCH_ASSOC);

    if (!$configUI) {
        $configUI = [
            'color_bg' => '#050505', 'color_input' => '#1a1a1a', 'color_accent_red' => '#ef4444', 
            'color_accent_green' => '#10b981', 'color_accent_orange' => '#F28123', 'color_text_muted' => '#888888',
            'app_titulo' => 'Lleva tu entrenamiento', 'app_subtitulo' => 'al siguiente nivel', 'app_desc' => 'Únete y mantén tu progreso.', 'app_btn_url' => '#', 'app_imagen' => '',
            'cta_titulo' => '¿Listo para transformar tu vida?', 'cta_desc' => 'Visítanos y comienza hoy.', 'cta_btn_url' => '#'
        ];
    }

    $stmtH = $conn->prepare("SELECT id_hero, img_desktop, img_mobile FROM san_landing_hero WHERE estado = 1 ORDER BY id_hero DESC");
    $stmtH->execute();
    $heroes = $stmtH->fetchAll(PDO::FETCH_ASSOC);

    $stmtA = $conn->prepare("SELECT id_amenidad, icono, titulo, descripcion FROM san_landing_amenidades WHERE estado = 1 ORDER BY id_amenidad ASC");
    $stmtA->execute();
    $amenidades = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    $stmtP = $conn->prepare("SELECT id_plan, nombre, precio, frecuencia, beneficios_json, url_boton FROM san_landing_planes WHERE estado = 1 ORDER BY orden ASC, precio ASC");
    $stmtP->execute();
    $planes = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    foreach ($planes as &$plan) {
        $plan['beneficios'] = json_decode($plan['beneficios_json'], true) ?: [];
        unset($plan['beneficios_json']);
    }

    $stmtG = $conn->prepare("SELECT id_galeria, imagen_url, es_wide FROM san_landing_galeria WHERE estado = 1 ORDER BY id_galeria DESC");
    $stmtG->execute();
    $galeria = $stmtG->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("<div style='color:red; background:#111; padding:20px;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>");
}
?>

<style>
    :root {
        --bg-color: <?= htmlspecialchars($configUI['color_bg']) ?>;
        --input-bg: <?= htmlspecialchars($configUI['color_input']) ?>;
        --accent-red: <?= htmlspecialchars($configUI['color_accent_red']) ?>;
        --accent-green: <?= htmlspecialchars($configUI['color_accent_green']) ?>;
        --accent-orange: <?= htmlspecialchars($configUI['color_accent_orange']) ?>;
        --text-muted: <?= htmlspecialchars($configUI['color_text_muted']) ?>;
    }
    body { background-color: var(--bg-color); color: #ffffff; font-family: 'Muli', sans-serif; }
    .fade-update { animation: fadeInUpdate 0.8s ease-in-out; }
    @keyframes fadeInUpdate { 0% { opacity: 0; transform: translateY(10px); } 100% { opacity: 1; transform: translateY(0); } }
    .rounded-pill { border-radius: 50rem !important; }

    .hero-item-container { position: relative; width: 100%; height: 85vh; overflow: hidden; background-color: var(--bg-color); }
    .hero-img-desktop { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center; }
    .hero-img-mobile { display: none; width: 100%; height: 100%; object-fit: cover; object-position: center; }

    .amenity-card { background: var(--input-bg); border: 1px solid #333; border-radius: 16px; padding: 40px 20px; text-align: center; transition: transform 0.3s ease, border-color 0.3s ease; height: 100%; }
    .amenity-card:hover { transform: translateY(-5px); border-color: var(--accent-orange); }
    .amenity-icon { font-size: 45px; color: var(--accent-orange); margin-bottom: 20px; }
    .amenity-title { font-size: 18px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; color: #ffffff; }

    .plan-card { background: var(--input-bg); border: 1px solid #333; border-radius: 16px; padding: 32px 24px; display: flex; flex-direction: column; height: 100%; transition: transform 0.3s ease, border-color 0.3s ease; position: relative; }
    .plan-card:hover { transform: translateY(-5px); border-color: var(--accent-orange); }
    .plan-card.highlight { border: 2px solid var(--accent-orange); }
    .plan-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: var(--accent-orange); color: var(--bg-color); font-weight: 800; font-size: 12px; padding: 4px 16px; border-radius: 50rem; text-transform: uppercase; letter-spacing: 1px; }
    .plan-title { color: #ffffff; font-size: 24px; font-weight: 700; text-transform: uppercase; margin-bottom: 16px; }
    .plan-price-wrapper { margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #333; }
    .plan-currency { font-size: 20px; vertical-align: top; color: var(--accent-green); }
    .plan-amount { font-size: 42px; font-weight: 800; line-height: 1; color: #ffffff; }
    .plan-frequency { font-size: 14px; color: var(--text-muted); }
    .plan-benefits { flex-grow: 1; margin-bottom: 32px; }
    .plan-benefits li { position: relative; padding-left: 32px; margin-bottom: 16px; font-size: 15px; color: #cccccc; }
    .plan-benefits li i { position: absolute; left: 0; top: 3px; color: var(--accent-green); font-size: 18px; }
    .plan-btn { background: var(--accent-orange); color: var(--bg-color) !important; font-weight: 700; text-transform: uppercase; padding: 14px; border: none; transition: filter 0.3s; text-align: center; display: block; text-decoration: none; }
    .plan-btn:hover { filter: brightness(1.1); text-decoration: none; }

    .app-section { background: linear-gradient(135deg, #111111 0%, var(--bg-color) 100%); border-top: 1px solid #222; border-bottom: 1px solid #222; padding: 80px 0; }
    .app-image-wrapper img { width: 100%; max-width: 400px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.8); }

    .gallery-section { padding: 80px 0; }
    .gs-item { height: 250px; background-size: cover; background-position: center; margin-bottom: 20px; border-radius: 12px; position: relative; transition: transform 0.3s ease; }
    .gs-item:hover { transform: scale(1.02); z-index: 2; }
    .gs-item.grid-wide { height: 520px; }

    .cta-bottom-section { background-color: var(--accent-orange); padding: 60px 0; text-align: center; color: var(--bg-color); }

    @media (max-width: 768px) { .hero-img-desktop { display: none; } .hero-img-mobile { display: block; } .app-image-wrapper { margin-top: 40px; text-align: center; } }
</style>

<?php if(isset($_GET['edit_mode']) && $_GET['edit_mode'] == 1): ?>
<style>
    .editable-element { 
        cursor: pointer !important; 
        position: relative; 
        transition: outline 0.2s;
        /* Bloqueo estricto de selección de texto */
        -webkit-user-select: none; 
        -moz-user-select: none; 
        -ms-user-select: none; 
        user-select: none; 
    }
    .editable-element:hover { outline: 3px dashed var(--accent-orange) !important; outline-offset: -3px; }
    .editable-element:hover::after { 
        content: '✎ Doble Clic para editar'; 
        position: absolute; 
        top: 0; right: 0; 
        background: var(--accent-orange); color: #fff; 
        padding: 4px 10px; font-size: 11px; font-weight: bold; 
        border-radius: 0 0 0 8px; z-index: 999; 
        pointer-events: none; 
    }
</style>
<?php endif; ?>

<div id="app-landing">
    <section class="hero-section"><div class="hs-slider owl-carousel" id="hero-container"></div></section>

    <section class="amenities-section" style="padding: 80px 0;">
        <div class="container">
            <div class="row mb-5"><div class="col-lg-12 text-center"><h2 style="font-size: 38px; text-transform: uppercase; font-weight: 800; color: #ffffff; margin-bottom: 10px;">Vive la experiencia <span style="color: var(--accent-orange);">Sandys Gym</span></h2></div></div>
            <div class="row">
                <?php foreach ($amenidades as $am): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="amenity-card <?= isset($_GET['edit_mode']) ? 'editable-element' : '' ?>" 
                        <?php if(isset($_GET['edit_mode'])): ?>
                        data-info='<?= htmlspecialchars(json_encode($am), ENT_QUOTES, 'UTF-8') ?>' 
                        ondblclick='notificarParent("amenidad", JSON.parse(this.dataset.info))'
                        <?php endif; ?>>
                        <i class="<?= htmlspecialchars($am['icono']) ?> amenity-icon"></i>
                        <h4 class="amenity-title"><?= htmlspecialchars($am['titulo']) ?></h4>
                        <p class="text-muted m-0"><?= htmlspecialchars($am['descripcion']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="pricing-section spad" style="background-color: var(--bg-color); padding: 80px 0;">
        <div class="container">
            <div class="row"><div class="col-lg-12"><div class="section-title text-center mb-5"><h2 style="font-size: 42px; text-transform: uppercase; font-weight: 800; color: #ffffff; margin-bottom: 0;">Elige tu plan y <span style="color: var(--accent-orange);">entrena ya</span></h2></div></div></div>
            <div class="row justify-content-center" id="planes-container"></div>
        </div>
    </section>

    <section class="app-section <?= isset($_GET['edit_mode']) ? 'editable-element' : '' ?>" <?= isset($_GET['edit_mode']) ? "ondblclick='notificarParent(\"seccion_app\", null)'" : '' ?>>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 style="font-size: 38px; text-transform: uppercase; font-weight: 800; color: #ffffff; margin-bottom: 20px;">
                        <?= htmlspecialchars($configUI['app_titulo']) ?><br><span style="color: var(--accent-orange);"><?= htmlspecialchars($configUI['app_subtitulo']) ?></span>
                    </h2>
                    <p style="color: #cccccc; font-size: 16px; line-height: 1.6; margin-bottom: 30px;"><?= htmlspecialchars($configUI['app_desc']) ?></p>
                    <a href="<?= htmlspecialchars($configUI['app_btn_url']) ?>" class="btn plan-btn rounded-pill d-inline-block px-5">Acceder a mi Portal</a>
                </div>
                <div class="col-lg-6 text-lg-right app-image-wrapper">
                    <?php $app_img_src = !empty($configUI['app_imagen']) ? 'assets/img/hero/' . $configUI['app_imagen'] : 'https://images.unsplash.com/photo-1594882645126-14020914d58d?q=80&w=800&auto=format&fit=crop'; ?>
                    <img src="<?= htmlspecialchars($app_img_src) ?>" alt="App Image">
                </div>
            </div>
        </div>
    </section>

    <div class="gallery-section">
        <div class="container">
            <div class="row mb-4"><div class="col-lg-12 text-center"><h2 style="font-size: 36px; text-transform: uppercase; font-weight: 800; color: #ffffff;">Nuestras <span style="color: var(--accent-orange);">Instalaciones</span></h2></div></div>
            <div class="row" id="galeria-container"></div>
        </div>
    </div>

    <section class="cta-bottom-section <?= isset($_GET['edit_mode']) ? 'editable-element' : '' ?>" <?= isset($_GET['edit_mode']) ? "ondblclick='notificarParent(\"seccion_cta\", null)'" : '' ?>>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 text-lg-left text-center mb-3 mb-lg-0">
                    <h2 style="font-size: 32px; font-weight: 800; text-transform: uppercase; margin: 0;"><?= htmlspecialchars($configUI['cta_titulo']) ?></h2>
                    <p style="margin: 0; font-size: 18px; font-weight: 500;"><?= htmlspecialchars($configUI['cta_desc']) ?></p>
                </div>
                <div class="col-lg-4 text-lg-right text-center">
                    <a href="<?= htmlspecialchars($configUI['cta_btn_url']) ?>" class="btn rounded-pill px-5 py-3" style="background-color: var(--bg-color); color: #ffffff; font-weight: 800; text-transform: uppercase; font-size: 16px;">¡Inscríbete Ahora!</a>
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
    
    const editMode = <?= isset($_GET['edit_mode']) ? 'true' : 'false' ?>;

// 2. Actualización JS: Limpieza segura y comunicación estricta
function notificarParent(tipo, data) {
    if (typeof editMode !== 'undefined' && editMode) {
        
        // A. Purgar selección del OS con fallback de compatibilidad (Chrome/Edge/Safari vs Firefox)
        let sel = window.getSelection ? window.getSelection() : null;
        if (sel) {
            if (sel.empty) {
                sel.empty(); 
            } else if (sel.removeAllRanges && sel.rangeCount > 0) {
                sel.removeAllRanges(); 
            }
        }
        
        // B. Validar que estamos dentro de un iFrame y sanitizar el payload
        if (window.parent && window.parent !== window) {
            // Forzar serialización para destruir cualquier referencia circular/DOM que cause DataCloneError
            let payloadSeguro = data ? JSON.parse(JSON.stringify(data)) : null;
            
            window.parent.postMessage({ 
                accion: 'abrir_modal', 
                tipo: tipo, 
                data: payloadSeguro 
            }, '*');
        }
    }
}

    function renderizarHero(heroes) {
        const contenedor = $('#hero-container');
        if (contenedor.hasClass('owl-loaded')) { contenedor.trigger('destroy.owl.carousel'); contenedor.removeClass('owl-hidden'); }
        let html = ''; const basePath = 'assets/img/hero/';

        if (heroes.length === 0) html = '<div class="text-center text-muted" style="padding: 100px 0;">No hay elementos en Hero.</div>';

        heroes.forEach((h, index) => {
            let deskImg = h.img_desktop.startsWith('http') ? h.img_desktop : basePath + h.img_desktop;
            let mobImg = h.img_mobile.startsWith('http') ? h.img_mobile : basePath + h.img_mobile;
            let editAttr = editMode ? `class="hero-item-container fade-update editable-element" ondblclick='notificarParent("hero", dbLandingData.hero[${index}])'` : `class="hero-item-container fade-update"`;
            html += `<div ${editAttr}><img src="${deskImg}" class="hero-img-desktop" fetchpriority="high"><img src="${mobImg}" class="hero-img-mobile" fetchpriority="high"></div>`;
        });
        contenedor.html(html);
        if ($.fn.owlCarousel && heroes.length > 0) contenedor.owlCarousel({ loop: heroes.length > 1, margin: 0, nav: false, items: 1, dots: false, animateOut: 'fadeOut', animateIn: 'fadeIn', smartSpeed: 1200, autoHeight: false, autoplay: true });
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
            let editAttr = editMode ? `class="plan-card w-100 ${highlightClass} editable-element" ondblclick='notificarParent("plan", dbLandingData.planes[${index}])'` : `class="plan-card w-100 ${highlightClass}"`;

            html += `
            <div class="col-lg-4 col-md-6 fade-update mb-4 d-flex">
                <div ${editAttr}>
                    ${badgeHtml}
                    <h3 class="plan-title">Plan <b style="color: var(--accent-red);">${p.nombre}</b></h3>
                    <div class="plan-price-wrapper"><span class="plan-currency">$</span><span class="plan-amount">${precioF}</span><span class="plan-frequency">/ ${p.frecuencia}</span></div>
                    <div class="plan-benefits"><ul class="list-unstyled m-0">${lis}</ul></div>
                    <a href="${p.url_boton}" class="btn plan-btn rounded-pill btn-block w-100">¡Inscríbete ya!</a>
                </div>
            </div>`;
        });
        contenedor.innerHTML = html;
    }

    function renderizarGaleria(galeria) {
        const contenedor = document.getElementById('galeria-container');
        let html = ''; const basePath = 'assets/img/gallery/';
        if (galeria.length === 0) return contenedor.innerHTML = '<div class="col-12 text-center text-muted">No hay imágenes.</div>';

        galeria.forEach((g, index) => {
            let cols = g.es_wide == 1 ? 'col-lg-6 col-md-12' : 'col-lg-3 col-md-6';
            let claseWide = g.es_wide == 1 ? 'grid-wide' : '';
            let imgSrc = g.imagen_url.startsWith('http') ? g.imagen_url : basePath + g.imagen_url;
            let editAttr = editMode ? `class="gs-item ${claseWide} editable-element" ondblclick='notificarParent("galeria", dbLandingData.galeria[${index}])'` : `class="gs-item ${claseWide}"`;

            html += `<div class="${cols} fade-update"><div ${editAttr} style="background-image: url('${imgSrc}');"><a href="${imgSrc}" class="thumb-icon image-popup"><i class="fa-solid fa-image"></i></a></div></div>`;
        });
        contenedor.innerHTML = html;
    }

    document.addEventListener('DOMContentLoaded', () => { renderizarHero(dbLandingData.hero); renderizarPlanes(dbLandingData.planes); renderizarGaleria(dbLandingData.galeria); });
</script>
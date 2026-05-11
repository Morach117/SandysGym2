<style>
    :root {
        --bg-color: #050505; --input-bg: #1a1a1a; --accent-red: #ef4444; 
        --accent-green: #10b981; --accent-orange: #F28123;
    }
    .fade-update { animation: fadeInUpdate 0.8s ease-in-out; }
    @keyframes fadeInUpdate { 0% { opacity: 0; transform: translateY(10px); } 100% { opacity: 1; transform: translateY(0); } }
    .rounded-pill { border-radius: 50rem !important; }
</style>

<div id="app-landing">
    <section class="hero-section">
        <div class="hs-slider owl-carousel" id="hero-container">
            <div class="text-center text-muted" style="padding: 100px 0;">Cargando interfaz...</div>
        </div>
    </section>

    <section class="pricing-section spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title">
                        <h2 style="font-size: 46px; text-transform: uppercase; font-weight: 700; color: #ffffff; margin-bottom: 0;">NUESTROS PLANES</h2>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center" id="planes-container"></div>
        </div>
    </section>

    <div class="gallery-section">
        <div class="gallery" id="galeria-container">
            <div class="grid-sizer"></div>
        </div>
    </div>
</div>

<script>
async function cargarLanding() {
    try {
        // CAMBIO CRÍTICO: Apuntar al nuevo Endpoint aislado
        const response = await fetch('api_landing.php');
        const json = await response.json();

        if (json.exito) {
            renderizarHero(json.data.hero);
            renderizarPlanes(json.data.planes);
            renderizarGaleria(json.data.galeria);
        } else {
            console.error("Error del servidor:", json.mensaje);
        }
    } catch (error) {
        console.error("Fetch Error:", error);
    }
}

function renderizarHero(heroes) {
    const contenedor = $('#hero-container');
    if (contenedor.hasClass('owl-loaded')) {
        contenedor.trigger('destroy.owl.carousel');
        contenedor.removeClass('owl-hidden');
    }
    
    let html = '';
    heroes.forEach(h => {
        html += `
        <div class="hs-item set-bg fade-update" data-setbg="./assets/img/hero/${h.imagen_bg}" style="background-image: url('./assets/img/hero/${h.imagen_bg}');">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 offset-lg-6">
                        <div class="hi-text">
                            <span>${h.subtitulo}</span>
                            <h1>${h.titulo_html}</h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    });

    contenedor.html(html);

    if($.fn.owlCarousel) {
        contenedor.owlCarousel({ loop: true, margin: 0, nav: true, items: 1, dots: false, animateOut: 'fadeOut', animateIn: 'fadeIn', smartSpeed: 1200, autoHeight: false, autoplay: true });
    }
}

function renderizarPlanes(planes) {
    const contenedor = document.getElementById('planes-container');
    if (planes.length === 0) {
        contenedor.innerHTML = '<div class="col-12 text-center text-muted">No hay planes activos.</div>';
        return;
    }

    let html = '';
    planes.forEach(p => {
        let lis = '';
        p.beneficios.forEach(b => { lis += `<li>${b}</li>`; });
        let precioF = parseFloat(p.precio).toFixed(1);

        html += `
        <div class="col-lg-4 col-md-8 fade-update" style="margin-bottom: 30px;">
            <div class="ps-item" style="background: var(--input-bg); border: 1px solid #333;">
                <h3 style="color: var(--accent-red);">${p.nombre}</h3>
                <div class="pi-price">
                    <h2 style="color: #fff;">$ ${precioF}</h2>
                    <span style="color: var(--accent-green);">${p.frecuencia}</span>
                </div>
                <ul style="color: #ccc;">${lis}</ul>
                <a href="${p.url_boton}" class="primary-btn pricing-btn rounded-pill" style="background: var(--accent-orange); color:#050505; border:none;">Inscribirse ahora</a>
            </div>
        </div>`;
    });
    contenedor.innerHTML = html;
}

function renderizarGaleria(galeria) {
    const contenedor = document.getElementById('galeria-container');
    let html = '<div class="grid-sizer"></div>';

    galeria.forEach(g => {
        let claseWide = g.es_wide == 1 ? 'grid-wide' : '';
        html += `
        <div class="gs-item ${claseWide} set-bg fade-update" data-setbg="./assets/img/gallery/${g.imagen_url}" style="background-image: url('./assets/img/gallery/${g.imagen_url}');">
            <a href="./assets/img/gallery/${g.imagen_url}" class="thumb-icon image-popup">
                <i class="fa fa-picture-o"></i>
            </a>
        </div>`;
    });
    contenedor.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', () => {
    cargarLanding();
});
</script>
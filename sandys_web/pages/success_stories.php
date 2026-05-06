<?php
// Archivo exclusivo de vista: Casos de Éxito
// Requiere que conexion.php defina la instancia de PDO en la variable $conn
require_once("conn.php");

try {
    $stmt = $conn->prepare("SELECT cliente_nombre, foto_antes, foto_despues, video_url, testimonio FROM san_historias WHERE estado = 1 ORDER BY fecha_registro DESC");
    $stmt->execute();
    $casos_exito = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al consultar casos de éxito: ' . $e->getMessage());
    $casos_exito = [];
}
?>
<style>
    /* Corrección de superposición del Navbar */
    .success-stories-section {
        background-color: #050505;
        padding-top: 140px; /* Fuerza el margen superior para esquivar el navbar fixed/absolute */
        padding-bottom: 60px;
    }

    /* Estilos de la Tarjeta */
    .success-card {
        background-color: #1a1a1a;
        border: 1px solid #333;
        border-radius: 20px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        width: 100%;
    }
    .success-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(242, 129, 35, 0.15);
    }
    .client-name {
        color: #ffffff;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 1.5rem;
    }
    .img-label {
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 1px;
        margin-bottom: 0.5rem;
        display: block;
    }
    .label-antes { color: #ef4444; }
    .label-despues { color: #10b981; }
    
    /* Contenedor de imagen responsivo con Relación de Aspecto 1:1 */
    .img-container {
        position: relative;
        width: 100%;
        padding-top: 100%; 
        border-radius: 12px;
        overflow: hidden;
    }
    .img-container img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border: 2px solid transparent;
    }
    .img-container.antes img { border-color: #ef4444; }
    .img-container.despues img { border-color: #10b981; }
    
    .testimonial-text {
        color: #a0a0a0;
        font-size: 0.95rem;
        font-style: italic;
        margin: 1.5rem 0;
        line-height: 1.5;
    }
    .btn-evolucion {
        background-color: #F28123;
        color: #050505;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
        transition: background-color 0.3s ease;
    }
    .btn-evolucion:hover {
        background-color: #e0761e;
        color: #050505;
    }

    /* Hacks para Owl Carousel - Altura uniforme de las tarjetas */
    .success-slider .owl-stage {
        display: flex;
        align-items: stretch;
    }
    .success-slider .owl-item {
        display: flex;
    }
    .success-slider .item {
        width: 100%;
        display: flex;
        padding: 10px; /* Espaciado entre elementos del carrusel */
    }
    
    /* Estilos para los dots del carrusel en Dark Mode */
    .success-slider .owl-dots {
        text-align: center;
        margin-top: 20px;
    }
    .success-slider .owl-dot span {
        width: 12px;
        height: 12px;
        margin: 5px 7px;
        background: #333 !important;
        display: block;
        transition: opacity 200ms ease;
        border-radius: 30px;
    }
    .success-slider .owl-dot.active span {
        background: #F28123 !important;
    }
</style>

<section class="success-stories-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="section-title">
                    <h2 style="font-size: 46px; text-transform: uppercase; font-weight: 800; color: #ffffff; margin-bottom: 50px;">Casos de Éxito</h2>
                </div>
            </div>
        </div>
        
        <?php if (!empty($casos_exito)): ?>
            <!-- Contenedor del Carrusel -->
            <div class="success-slider owl-carousel">
                <?php foreach ($casos_exito as $caso): ?>
                <div class="item">
                    <!-- d-flex flex-column asegura que las tarjetas tengan la misma altura y el botón baje -->
                    <div class="success-card p-4 d-flex flex-column">
                        <h4 class="client-name text-center"><?= htmlspecialchars($caso['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></h4>
                        
                        <div class="row mb-3">
                            <div class="col-6 pr-2 text-center">
                                <span class="img-label label-antes">ANTES</span>
                                <div class="img-container antes">
                                    <img src="../imagenes/exito/<?= htmlspecialchars($caso['foto_antes'], ENT_QUOTES, 'UTF-8') ?>" alt="Antes" loading="lazy">
                                </div>
                            </div>
                            <div class="col-6 pl-2 text-center">
                                <span class="img-label label-despues">DESPUÉS</span>
                                <div class="img-container despues">
                                    <img src="../imagenes/exito/<?= htmlspecialchars($caso['foto_despues'], ENT_QUOTES, 'UTF-8') ?>" alt="Después" loading="lazy">
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($caso['testimonio'])): ?>
                            <div class="testimonial-text text-center flex-grow-1">
                                "<?= htmlspecialchars($caso['testimonio'], ENT_QUOTES, 'UTF-8') ?>"
                            </div>
                        <?php else: ?>
                            <!-- Espaciador si no hay testimonio -->
                            <div class="flex-grow-1"></div>
                        <?php endif; ?>

                        <?php if (!empty($caso['video_url'])): ?>
                            <div class="mt-auto pt-3">
                                <button class="btn btn-block rounded-pill btn-evolucion py-2 w-100" onclick="verVideoExito('../imagenes/exito/<?= htmlspecialchars($caso['video_url'], ENT_QUOTES, 'UTF-8') ?>')">
                                    <i class="fa-solid fa-play mr-2"></i> Ver Evolución
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12 text-center">
                    <p style="color: #a0a0a0; font-size: 1.2rem;">Aún no hay casos de éxito registrados.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Scripts Requeridos -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Inicialización del Owl Carousel
$(document).ready(function(){
    if(typeof $.fn.owlCarousel !== 'undefined') {
        $('.success-slider').owlCarousel({
            loop: true,
            margin: 15,
            nav: false,
            dots: true,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            responsive: {
                0: { items: 1 },
                768: { items: 2 },
                992: { items: 3 } /* Muestra 3 tarjetas en pantallas grandes */
            }
        });
    } else {
        console.error("Owl Carousel no está cargado en la plantilla.");
    }
});

function verVideoExito(url_video) {
    Swal.fire({
        background: '#1a1a1a',
        color: '#ffffff',
        html: `
            <div style="text-align: left; margin-bottom: 15px;">
                <h3 style="color: #10b981; margin: 0; font-weight: 800; text-transform: uppercase;"><i class="fa-solid fa-video"></i> Proceso</h3>
            </div>
            <video width="100%" controls autoplay style="border-radius: 12px; border: 1px solid #333; background: #000;">
                <source src="${url_video}" type="video/mp4">
                <source src="${url_video}" type="video/webm">
                Tu navegador no soporta el tag de video.
            </video>
        `,
        showConfirmButton: false,
        showCloseButton: true,
        width: '90%',
        maxWidth: '600px',
        customClass: {
            closeButton: 'text-white'
        }
    });
}
</script>
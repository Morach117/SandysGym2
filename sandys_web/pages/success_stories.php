<?php
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
.success-stories-section {
    background-color: #050505;
    padding-top: 140px;
    padding-bottom: 60px;
}

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

.label-antes {
    color: #ef4444;
}

.label-despues {
    color: #10b981;
}

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
    object-fit: contain;
    border: 2px solid transparent;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.img-container img:hover {
    transform: scale(1.05);
}

.custom-image-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 99999;
    display: flex;
    justify-content: center;
    align-items: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.4s cubic-bezier(0.25, 0.8, 0.25, 1), visibility 0.4s;
}

.custom-image-modal.active {
    opacity: 1;
    visibility: visible;
}

.custom-modal-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
    transform: scale(0.7);
    opacity: 0;
    transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1), opacity 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.custom-image-modal.active .custom-modal-content {
    transform: scale(1);
    opacity: 1;
}

.custom-modal-content img {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 12px;
    object-fit: contain;
    display: block;
    box-shadow: 0 15px 35px rgba(0,0,0,0.5);
    border: 2px solid #333;
}

.custom-modal-close {
    position: absolute;
    top: -40px;
    right: -40px;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 2.5rem;
    cursor: pointer;
    transition: color 0.3s, transform 0.3s;
    line-height: 1;
    z-index: 2;
}

.custom-modal-close:hover {
    color: #F28123;
    transform: scale(1.1);
}

@media (max-width: 768px) {
    .custom-modal-close {
        top: -40px;
        right: 0;
    }
}

.img-container.antes img {
    border-color: #ef4444;
}

.img-container.despues img {
    border-color: #10b981;
}

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
    padding: 10px;
}

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
                    <h2
                        style="font-size: 46px; text-transform: uppercase; font-weight: 800; color: #ffffff; margin-bottom: 50px;">
                        Casos de Éxito</h2>
                </div>
            </div>
        </div>

        <?php if (!empty($casos_exito)): ?>
        <div class="success-slider owl-carousel">
            <?php foreach ($casos_exito as $caso): ?>
            <div class="item">
                <div class="success-card p-4 d-flex flex-column">
                    <h4 class="client-name text-center">
                        <?= htmlspecialchars($caso['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></h4>

                    <div class="row mb-3">
                        <div class="col-6 pr-2 text-center">
                            <span class="img-label label-antes">ANTES</span>
                            <div class="img-container antes">
                                <img src="https://sergym.com/imagenes/exito/<?= htmlspecialchars($caso['foto_antes'], ENT_QUOTES, 'UTF-8') ?>"
                                     alt="Antes" loading="lazy" onclick="openImageModal(this.src, 'antes')">
                            </div>
                        </div>
                        <div class="col-6 pl-2 text-center">
                            <span class="img-label label-despues">DESPUÉS</span>
                            <div class="img-container despues">
                                <img src="https://sergym.com/imagenes/exito/<?= htmlspecialchars($caso['foto_despues'], ENT_QUOTES, 'UTF-8') ?>"
                                     alt="Después" loading="lazy" onclick="openImageModal(this.src, 'despues')">
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($caso['testimonio'])): ?>
                    <div class="testimonial-text text-center flex-grow-1">
                        "<?= htmlspecialchars($caso['testimonio'], ENT_QUOTES, 'UTF-8') ?>"
                    </div>
                    <?php else: ?>
                    <div class="flex-grow-1"></div>
                    <?php endif; ?>

                    <?php if (!empty($caso['video_url'])): ?>
                    <div class="mt-auto pt-3">
                        <button class="btn btn-block rounded-pill btn-evolucion py-2 w-100"
                            onclick="verVideoExito('https://sergym.com/imagenes/exito/<?= htmlspecialchars($caso['video_url'], ENT_QUOTES, 'UTF-8') ?>')">
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

<div id="imageModal" class="custom-image-modal" onclick="closeImageModal()">
    <div class="custom-modal-content" onclick="event.stopPropagation()">
        <button class="custom-modal-close" onclick="closeImageModal()">&times;</button>
        <img id="modalImage" src="" alt="Vista a gran escala">
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    if (typeof $.fn.owlCarousel !== 'undefined') {
        $('.success-slider').owlCarousel({
            loop: true,
            margin: 15,
            nav: false,
            dots: true,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            responsive: {
                0: {
                    items: 1
                },
                768: {
                    items: 2
                },
                992: {
                    items: 3
                }
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

function openImageModal(imgSrc, type) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    
    if (type === 'antes') {
        modalImg.style.borderColor = '#ef4444';
    } else if (type === 'despues') {
        modalImg.style.borderColor = '#10b981';
    } else {
        modalImg.style.borderColor = '#333';
    }

    modalImg.src = imgSrc;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    setTimeout(() => {
        document.getElementById('modalImage').src = '';
    }, 400); 
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});
</script>
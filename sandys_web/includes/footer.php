<style>
    .modern-footer {
        background-color: #050505;
        border-top: 2px solid #ef4444; /* Línea de acento superior */
        color: #e0e0e0;
        padding: 60px 0 20px;
        font-family: 'Muli', sans-serif;
    }
    
    .footer-logo { max-width: 180px; margin-bottom: 20px; }
    
    .footer-about p {
        color: #9ca3af;
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 25px;
        max-width: 90%;
    }
    
    .social-links a {
        display: inline-flex; align-items: center; justify-content: center;
        width: 42px; height: 42px;
        background: #121212; border: 1px solid #222; color: #fff;
        border-radius: 50%; margin-right: 12px; font-size: 16px;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        text-decoration: none;
    }
    .social-links a:hover {
        background: #ef4444; border-color: #ef4444;
        transform: translateY(-4px); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
    }

    /* Grid de Contacto Compacto */
    .contact-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 15px;
    }
    
    .contact-item {
        display: flex; align-items: center; gap: 15px;
        padding: 16px; background: #121212; border: 1px solid #1f1f1f;
        border-radius: 12px; text-decoration: none;
        transition: all 0.3s ease;
    }
    .contact-item:hover {
        background: #1a1a1a; border-color: #ef4444;
        transform: translateY(-3px); text-decoration: none;
    }
    
    .ci-icon {
        width: 48px; height: 48px;
        background: rgba(239, 68, 68, 0.1); color: #ef4444;
        border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0; transition: 0.3s;
    }
    .contact-item:hover .ci-icon { background: #ef4444; color: #fff; }
    
    .ci-text { display: flex; flex-direction: column; }
    .ci-text span {
        font-size: 11px; color: #ef4444; text-transform: uppercase;
        font-weight: 700; letter-spacing: 0.5px; margin-bottom: 2px;
    }
    .ci-text p {
        margin: 0; color: #ffffff; font-size: 14px; font-weight: 600; line-height: 1.3;
    }
    
    .footer-bottom {
        margin-top: 50px; padding-top: 25px;
        border-top: 1px solid #1f1f1f;
        font-size: 13px; color: #777;
    }

    @media (max-width: 991px) {
        .footer-about { text-align: center; margin-bottom: 40px; }
        .footer-about p { max-width: 100%; margin: 0 auto 20px auto; }
        .social-links { justify-content: center; }
    }
</style>

<footer class="modern-footer">
    <div class="container">
        <div class="row align-items-center">
            
            <div class="col-lg-4 col-md-12 footer-about">
                <a href="index.php?page=home"><img src="./assets/img/logo.png" alt="Sandy's Gym" class="footer-logo"></a>
                <p>Transforma tu cuerpo y mente con nosotros. Ofrecemos el mejor equipo, clases dinámicas y un ambiente motivador para que alcances todas tus metas.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/gymsandy" target="_blank" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/sandysgym?igsh=MXU0c3NrNWZjZzMzYw==" target="_blank" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>

            <div class="col-lg-8 col-md-12">
                <div class="contact-grid">
                    
                    <a href="https://maps.app.goo.gl/7Q4cw2HbzGhikVR69" target="_blank" class="contact-item">
                        <div class="ci-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="ci-text">
                            <span>Nuestra Ubicación</span>
                            <p>Av. Miguel Hidalgo #308, Bienestar Social</p>
                        </div>
                    </a>
                    
                    <a href="https://wa.me/529618465257" target="_blank" class="contact-item">
                        <div class="ci-icon"><i class="fa-brands fa-whatsapp"></i></div>
                        <div class="ci-text">
                            <span>Escríbenos</span>
                            <p>+52 961 846 5257</p>
                        </div>
                    </a>

                    <a href="mailto:Sandysgym@hotmail.com" class="contact-item">
                        <div class="ci-icon"><i class="fa-solid fa-envelope"></i></div>
                        <div class="ci-text">
                            <span>Correo Electrónico</span>
                            <p>Sandysgym@hotmail.com</p>
                        </div>
                    </a>

                </div>
            </div>

        </div>
        
        <div class="footer-bottom text-center">
            <p>&copy; <?php echo date('Y'); ?> Sandy's Gym. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>

<div class="search-model">
    <div class="h-100 d-flex align-items-center justify-content-center">
        <div class="search-close-switch">+</div>
        <form class="search-model-form">
            <input type="text" id="search-input" placeholder="Buscar aquí...">
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="./assets/js/bootstrap.min.js"></script>
<script src="./assets/js/jquery.magnific-popup.min.js"></script>
<script src="./assets/js/masonry.pkgd.min.js"></script>
<script src="./assets/js/jquery.barfiller.js"></script>
<script src="./assets/js/jquery.slicknav.js"></script>
<script src="./assets/js/owl.carousel.min.js"></script>
<script src="./assets/js/main.js"></script>

<?php
$currentPage = isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'home';
$pageScript = "assets/js/pages/{$currentPage}.js";

if (file_exists($pageScript)) {
    echo '<script src="' . $pageScript . '"></script>';
}
?>

</body>
</html>
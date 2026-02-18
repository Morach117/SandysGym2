<div class="gettouch-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="gt-text">
                    <i class="fa-solid fa-location-dot"></i>
                    <p>
                        <a href="https://www.google.com/maps/place/Sandys+Gym/@16.7423359,-92.0955938,17z/data=!3m1!4b1!4m6!3m5!1s0x87d61f151921300f:0x75d5024258b3f463!8m2!3d16.7423308!4d-92.0930189!16s%2Fg%2F11b6dx_7w8?entry=ttu" target="_blank" style="text-decoration: none; color: inherit;">
                            Avenida Miguel Hidalgo #308, Colonia Bienestar Social
                        </a>
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="gt-text">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                    <ul>
                        <li>
                            <a href="tel:+529618465257" style="text-decoration: none; color: inherit;">+52 961 846 5257</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="gt-text email">
                    <i class="fa-solid fa-envelope"></i>
                    <p>
                        <a href="mailto:Sandysgym@hotmail.com" style="text-decoration: none; color: inherit;">Sandysgym@hotmail.com</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<section class="footer-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <div class="fs-about">
                    <div class="fa-logo">
                        <a href="index.php?page=home"><img src="./assets/img/logo.png" alt=""></a>
                    </div>
                    <p>Transforma tu cuerpo y mente con nosotros. Ofrecemos el mejor equipo, clases dinámicas y un ambiente motivador para que alcances todas tus metas.</p>
                    <div class="fa-social">
                        <a href="https://www.facebook.com/gymsandy" target="_blank"><i class="fa-brands fa-facebook"></i></a>
                        <a href="#"><i class="fa-brands fa-twitter"></i></a>
                        <a href="#"><i class="fa-brands fa-youtube"></i></a>
                        <a href="https://www.instagram.com/sandysgym?igsh=MXU0c3NrNWZjZzMzYw==" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                        <a href="mailto:Sandysgym@hotmail.com"><i class="fa-solid fa-envelope"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
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
// Carga el script JS específico para la página actual
$currentPage = isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'home';

// NOTA: Verifica que esta ruta sea la correcta. En la imagen original
// tus scripts (login.js, etc.) estaban en 'js/pages/'.
// Si los moviste a 'assets/js/custom/' o 'assets/js/pages/',
// asegúrate de que esta ruta sea la correcta.
$pageScript = "assets/js/pages/{$currentPage}.js";

if (file_exists($pageScript)) {
    echo '<script src="' . $pageScript . '"></script>';
}
?>

</body>

</html>
<style>
.modern-footer {
    background-color: #050505;
    border-top: 2px solid #ef4444;
    /* Línea de acento superior */
    color: #e0e0e0;
    padding: 60px 0 20px;
    font-family: 'Muli', sans-serif;
}

.footer-logo {
    max-width: 180px;
    margin-bottom: 20px;
}

.footer-about p {
    color: #9ca3af;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 25px;
    max-width: 90%;
}

.social-links a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    background: #121212;
    border: 1px solid #222;
    color: #fff;
    border-radius: 50%;
    margin-right: 12px;
    font-size: 16px;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    text-decoration: none;
}

.social-links a:hover {
    background: #ef4444;
    border-color: #ef4444;
    transform: translateY(-4px);
    box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
}

/* Grid de Contacto Compacto */
.contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 15px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 16px;
    background: #121212;
    border: 1px solid #1f1f1f;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    height: 100%;
}

.contact-item:hover {
    background: #1a1a1a;
    border-color: #ef4444;
    transform: translateY(-3px);
    text-decoration: none;
}

.ci-icon {
    width: 48px;
    height: 48px;
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    transition: 0.3s;
}

.contact-item:hover .ci-icon {
    background: #ef4444;
    color: #fff;
}

.ci-text {
    display: flex;
    flex-direction: column;
}

.ci-text span {
    font-size: 11px;
    color: #ef4444;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.ci-text p {
    margin: 0;
    color: #ffffff;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.3;
}

.footer-bottom {
    margin-top: 50px;
    padding-top: 25px;
    border-top: 1px solid #1f1f1f;
    font-size: 13px;
    color: #777;
}

@media (max-width: 991px) {
    .footer-about {
        text-align: center;
        margin-bottom: 40px;
    }

    .footer-about p {
        max-width: 100%;
        margin: 0 auto 20px auto;
    }

    .social-links {
        justify-content: center;
    }
}
</style>

<footer class="modern-footer">
    <div class="container">
        <div class="row align-items-center">

            <div class="col-lg-4 col-md-12 footer-about">
                <a href="index.php?page=home"><img src="./assets/img/logo.png" alt="Sandy's Gym"
                        class="footer-logo"></a>
                <p>Transforma tu cuerpo y mente con nosotros. Ofrecemos el mejor equipo y un ambiente motivador para que
                    alcances todas tus metas.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/gymsandy" target="_blank" title="Facebook"><i
                            class="fa-brands fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/sandysgym?igsh=MXU0c3NrNWZjZzMzYw==" target="_blank"
                        title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>

            <div class="col-lg-8 col-md-12">
                <div class="contact-grid">

                    <a href="https://maps.app.goo.gl/7Q4cw2HbzGhikVR69" target="_blank" class="contact-item">
                        <div class="ci-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="ci-text">
                            <span>Nuestra Ubicación</span>
                            <p>Av. Miguel Hidalgo #308, Bienestar Social<br>C.P.: 29077, Tuxtla Gutiérrez, Chiapas</p>
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
            <p style="margin-top: 8px; font-size: 13px;">
                <a href="#" onclick="showTerms(); return false;" style="color: #9ca3af; margin-right: 20px; text-decoration: underline; transition: 0.3s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'">Términos y Condiciones</a>
                <a href="#" onclick="showPrivacy(); return false;" style="color: #9ca3af; text-decoration: underline; transition: 0.3s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'">Aviso de Privacidad</a>
            </p>
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

<script>
function showTerms() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Términos y Condiciones',
            html: `<div style="text-align: left; font-size: 14px; line-height: 1.6; max-height: 350px; overflow-y: auto; padding-right: 10px; color: #fff;">
                    <strong>1. Aceptación de los Términos</strong><br>
                    Al acceder y utilizar los servicios y la plataforma web de Sandy's Gym, usted acepta estar sujeto a estos Términos y Condiciones. Si no está de acuerdo, por favor no utilice la aplicación ni los servicios del gimnasio.<br><br>
                    <strong>2. Membresías y Pagos</strong><br>
                    Las membresías son personales e intransferibles. Los pagos realizados no son reembolsables ni transferibles. Las tarifas y promociones publicadas están sujetas a cambios sin previo aviso.<br><br>
                    <strong>3. Uso de Instalaciones y Equipo</strong><br>
                    Los socios deben respetar las normas de conducta del gimnasio, utilizar vestimenta deportiva adecuada, toalla de mano y limpiar el equipo después de su uso. Es obligatorio regresar los pesos y mancuernas a sus estantes correspondientes.<br><br>
                    <strong>4. Responsabilidad y Salud</strong><br>
                    El usuario declara encontrarse en condiciones físicas y de salud óptimas para realizar ejercicio físico. Sandy's Gym no se hace responsable por lesiones derivadas del mal uso del equipo, imprudencia o negligencia médica preexistente.<br><br>
                    <strong>5. Códigos y Promociones</strong><br>
                    Los códigos de descuento por referido o reactivación son válidos exclusivamente bajo las condiciones y vigencias asignadas. Sandy's Gym se reserva el derecho de cancelar cupones generados con datos falsos o cuentas duplicadas.<br><br>
                    <strong>6. Modificaciones</strong><br>
                    Nos reservamos el derecho de modificar estos términos en cualquier momento. El uso continuo del sistema implicará la aceptación de los nuevos términos y condiciones.
                   </div>`,
            icon: 'info',
            background: '#1a1a1a',
            color: '#ffffff',
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Cerrar'
        });
    } else {
        alert("Términos y Condiciones:\\n\\n1. Las membresías son personales e intransferibles.\\n2. Los pagos realizados no son reembolsables.\\n3. El usuario declara encontrarse en óptimas condiciones de salud para realizar ejercicio físico.");
    }
}

function showPrivacy() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Aviso de Privacidad',
            html: `<div style="text-align: left; font-size: 14px; line-height: 1.6; max-height: 350px; overflow-y: auto; padding-right: 10px; color: #fff;">
                    Sandy's Gym, con domicilio en Av. Miguel Hidalgo #308, Bienestar Social, C.P. 29077, Tuxtla Gutiérrez, Chiapas, es responsable de recabar sus datos personales, del uso que se le dé a los mismos y de su protección.<br><br>
                    <strong>Datos Personales Recabados:</strong> Nombres completos, número de teléfono celular (WhatsApp), correo electrónico, fecha de nacimiento (mes), fotos de perfil e información de contacto de emergencia.<br><br>
                    <strong>Finalidad del Tratamiento:</strong> 
                    Los datos recopilados serán utilizados exclusivamente para el control de accesos al gimnasio, procesamiento y validación segura de mensualidades, aplicación de promociones y descuentos, comunicación de avisos importantes y contacto inmediato en caso de emergencias médicas.<br><br>
                    <strong>Derechos ARCO:</strong> 
                    Usted tiene derecho a acceder, rectificar y cancelar sus datos personales, así como a oponerse al tratamiento de los mismos (Derechos ARCO), enviando su solicitud directamente al correo de contacto oficial: <strong>Sandysgym@hotmail.com</strong>.
                   </div>`,
            icon: 'info',
            background: '#1a1a1a',
            color: '#ffffff',
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Cerrar'
        });
    } else {
        alert("Aviso de Privacidad:\\n\\nSandy's Gym recopila tus datos personales únicamente para fines de control de acceso, mensualidades y contacto en caso de emergencia. Puedes solicitar su rectificación o eliminación en Sandysgym@hotmail.com.");
    }
}
</script>

</body>

</html>
<?php
require_once 'conn.php'; 

$alert_script = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_contact'])) {
    try {
        $nombre = htmlspecialchars(strip_tags(trim($_POST['nombre'])));
        $correo = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
        $telefono = htmlspecialchars(strip_tags(trim($_POST['telefono'])));
        $mensaje = htmlspecialchars(strip_tags(trim($_POST['mensaje'])));

        if(empty($nombre) || empty($correo) || empty($mensaje)) {
            throw new Exception("Por favor, completa todos los campos obligatorios.");
        }
        if(!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del correo no es válido.");
        }

        $sql = "INSERT INTO san_contactos (nombre, correo, telefono, mensaje) VALUES (:nombre, :correo, :telefono, :mensaje)";
        $stmt = $conn->prepare($sql); 
        
        $stmt->execute([
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':telefono' => $telefono,
            ':mensaje' => $mensaje
        ]);

        $alert_script = "
            Swal.fire({
                title: '¡Mensaje Enviado!',
                text: 'Nos pondremos en contacto contigo pronto.',
                icon: 'success',
                background: '#1a1a1a',
                color: '#ffffff',
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Aceptar'
            });
        ";

    } catch (Exception $e) {
        $error_msg = $e->getMessage();
        $alert_script = "
            Swal.fire({
                title: 'Ocurrió un error',
                text: '{$error_msg}',
                icon: 'error',
                background: '#1a1a1a',
                color: '#ffffff',
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Revisar'
            });
        ";
    }
}
?>

<style>
    .gym-dark-input {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
        border: 1px solid #333333 !important;
        border-radius: 8px !important;
        width: 100%;
        margin-bottom: 20px;
        padding: 14px 20px;
        transition: all 0.3s;
    }

    .gym-dark-input:focus {
        border-color: #ef4444 !important;
        outline: none;
        box-shadow: 0 0 5px rgba(239, 68, 68, 0.5);
    }

    .gym-dark-input:-webkit-autofill {
        -webkit-box-shadow: 0 0 0 30px #1a1a1a inset !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    .btn-pill-submit {
        background-color: #ef4444;
        color: #fff;
        border: none;
        padding: 12px 35px;
        border-radius: 50px !important;
        font-weight: 600;
        text-transform: uppercase;
        transition: background-color 0.3s ease;
        cursor: pointer;
    }

    .btn-pill-submit:hover {
        background-color: #dc2626;
    }
</style>

<section class="breadcrumb-section set-bg" data-setbg="./assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Contáctanos</h2>
                    <div class="bt-option">
                        <a href="index.php?page=home">Inicio</a>
                        <a href="#">Páginas</a>
                        <span>Contáctanos</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="contact-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <div class="section-title contact-title">
                    <span>Contáctanos</span>
                    <h2>PONTE EN CONTACTO</h2>
                </div>
                <div class="contact-widget">
                    <div class="cw-text">
                        <i class="fa fa-map-marker"></i>
                        <p>
                            <a href="https://maps.app.goo.gl/TUVxvRMakLJ5PnfE9" target="_blank"
                                style="text-decoration: none; color: inherit;">
                                Avenida Miguel Hidalgo #308, Colonia Bienestar Social
                            </a>
                        </p>
                    </div>
                    <div class="cw-text">
                        <i class="fab fa-whatsapp" style="color: #10b981;"></i>
                        <ul>
                            <li>
                                <a href="https://wa.me/529618465257?text=Hola,%20solicito%20información%20sobre%20las%20membresías%20de%20Sandys%20Gym"
                                    target="_blank"
                                    style="text-decoration: none; color: inherit; transition: color 0.3s ease;"
                                    onmouseover="this.style.color='#10b981'" onmouseout="this.style.color='inherit'">
                                    +52 961 846 5257
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="cw-text email">
                        <i class="fa fa-envelope"></i>
                        <p>
                            <a href="mailto:Sandysgym@hotmail.com"
                                style="text-decoration: none; color: inherit;">Sandysgym@hotmail.com</a>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="leave-comment">
                    <form action="" method="POST" id="gymContactForm">
                        <input type="text" name="nombre" class="gym-dark-input" placeholder="Nombre completo *" required>
                        <input type="email" name="correo" class="gym-dark-input" placeholder="Correo electrónico *" required>
                        <input type="tel" name="telefono" class="gym-dark-input" placeholder="Teléfono / WhatsApp">
                        <textarea name="mensaje" class="gym-dark-input" placeholder="¿En qué podemos ayudarte? (Ej. Dudas sobre membresías, horarios, clases...) *" required></textarea>
                        
                        <button type="submit" name="submit_contact" class="btn-pill-submit">Enviar Mensaje</button>
                    </form>
                </div>
            </div>
            
        </div>
        <div class="map">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3820.5913517640565!2d-93.10272442423029!3d16.74722962092983!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85ed277341079137%3A0xbd8772c946dd4bdf!2sSandy%C2%B4s%20Gym!5e0!3m2!1ses-419!2smx!4v1722706551196!5m2!1ses-419!2smx"
                width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if(!empty($alert_script)) echo $alert_script; ?>
    });
</script>
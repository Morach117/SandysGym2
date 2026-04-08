<?php
include('conn.php');
include('./query/select_data.php');

// Definir la variable $userName
$userName = '';

// Verificar si el usuario ha iniciado sesión
if (isset($_SESSION['admin'])) {
    // Obtener el nombre del usuario si está iniciado sesión
    $user_email = $_SESSION['admin']['soc_correo'];
    $consulta = "SELECT soc_nombres, soc_apepat, soc_apemat FROM san_socios WHERE soc_correo = :user_email";
    
    // Preparar la consulta
    $stmt = $conn->prepare($consulta);
    
    // Vincular el parámetro
    $stmt->bindParam(':user_email', $user_email);
    
    // Ejecutar la consulta
    $stmt->execute();
    
    // Verificar si se encontró el usuario
    if ($stmt->rowCount() > 0) {
        // Obtener el nombre del usuario y mostrarlo
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $userName = $row['soc_nombres'] . ' ' . $row['soc_apepat'] . ' ' . $row['soc_apemat'];
    }

    // Cerrar la cursor
    $stmt->closeCursor();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sandys</title>

    <link href="https://fonts.googleapis.com/css?family=Muli:300,400,500,600,700,800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Oswald:300,400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


    <link rel="stylesheet" href="./assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/flaticon.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/owl.carousel.min.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/barfiller.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/magnific-popup.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/style.css" type="text/css">

    <style>
        /* =========================================
           MEJORAS DE DISEÑO MODERNO (HEADER)
           ========================================= */

        /* Corrección de color para Iconos Sociales en Móvil (Fondo blanco) */
        .canvas-social {
            margin-top: 20px;
            display: flex;
            gap: 15px;
        }
        .canvas-social .social-icon i {
            font-size: 22px;
            color: #111111 !important; /* Color oscuro para ser visible en blanco */
            transition: all 0.3s ease;
        }
        .canvas-social .social-icon i:hover {
            color: #f36100 !important;
        }

        /* Iconos Sociales Escritorio (Fondo oscuro) */
        .to-social a {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        .to-social a i {
            font-size: 20px;
            color: #ffffff;
            transition: all 0.3s ease;
        }
        .to-social a i:hover {
            color: #f36100;
            transform: translateY(-3px);
        }

        /* Ajuste del menú de escritorio para ocupar hasta las redes */
        @media (min-width: 992px) {
            .nav-menu ul {
                display: flex;
                justify-content: flex-end; /* Alinea los botones hacia la derecha */
                gap: 40px; /* Separación uniforme entre los botones */
                margin-right: 20px; /* Separación con las redes sociales */
            }
            .nav-menu ul li {
                margin-right: 0 !important; /* Resetea márgenes residuales de la plantilla */
            }
        }

        /* Botón Iniciar Sesión Moderno (Estilo Píldora) */
        .login-btn, .user-logged-btn {
            color: #ffffff !important;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 10px 24px;
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            transition: all 0.3s ease;
            text-decoration: none !important;
        }
        .login-btn i, .user-logged-btn i {
            margin-right: 8px;
            color: #f36100;
            font-size: 16px;
        }
        .login-btn:hover, .user-logged-btn:hover, .user-logged-btn[aria-expanded="true"] {
            background: #ffffff;
            color: #000000 !important;
            border-color: #ffffff;
        }

        /* Botón Inscribirse Modernizado */
        .btn-inscribete {
            border-radius: 30px;
            padding: 12px 28px;
            font-weight: 700;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(243, 97, 0, 0.2);
            white-space: nowrap;
        }
        .btn-inscribete:hover {
            box-shadow: 0 6px 20px rgba(243, 97, 0, 0.4);
        }

        /* Estilo específico para Iniciar sesión en el menú móvil */
        .mobile-login-link {
            color: #111111 !important;
            font-weight: 600;
        }
        .mobile-login-link i {
            color: #f36100;
            margin-right: 8px;
        }
    </style>
</head>

<body>

    <div class="offcanvas-menu-overlay"></div>
    <div class="offcanvas-menu-wrapper">
        <div class="canvas-close">
            <i class="fa fa-close"></i>
        </div>
        <nav class="canvas-menu mobile-menu">
            <ul>
                <li><a href="index.php?page=home">Inicio</a></li>
                <li><a href="index.php?page=success_stories">Historias de éxito</a></li>
                <li><a href="index.php?page=faq">Preguntas frecuentes</a></li>
                <li><a href="index.php?page=contact">Contacto</a></li>
                
                <li style="border-top: 1px solid #e5e5e5; margin-top: 10px; padding-top: 10px;"></li>
                
                <?php if ($userName): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle mobile-login-link" id="userDropdownMobile" role="button" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-user"></i> <?php echo $userName; ?>
                        </a>
                        <ul class="dropdown-menu" id="userMenuMobile" aria-labelledby="userDropdownMobile">
                            <li><a class="dropdown-item" href="index.php?page=user_home">Inicio</a></li>
                            <li><a class="dropdown-item" href="index.php?page=user">Ver datos</a></li>
                            <li><a class="dropdown-item" href="index.php?page=user_information">Modificar información</a></li>
                            <li><a class="dropdown-item" href="./query/logout.php">Cerrar sesión</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="index.php?page=login" class="mobile-login-link"><i class="fa fa-user"></i> Iniciar Sesión</a></li>
                <?php endif; ?>
            </ul>
            <div class="canvas-social">
                <a href="https://www.facebook.com/gymsandy" target="_blank" class="social-icon"><i class="fa fa-facebook"></i></a>
                <a href="https://www.instagram.com/sandysgym?igsh=MXU0c3NrNWZjZzMzYw==" target="_blank" class="social-icon"><i class="fa fa-instagram"></i></a>
                <a href="https://www.tiktok.com/@sandysgym" target="_blank" class="social-icon"><i class="fab fa-tiktok"></i></a>
            </div>

        </nav>
        <div id="mobile-menu-wrap"></div>
    </div>
    <header class="header-section">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-2">
                    <div class="logo">
                        <a href="index.php?page=home">
                            <img src="./assets/img/logo.png" alt="Sandys Gym Logo">
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <nav class="nav-menu">
                        <ul>
                            <li><a href="index.php?page=home">Inicio</a></li>
                            <li><a href="index.php?page=success_stories">Historias de éxito</a></li>
                            <li><a href="index.php?page=faq">Preguntas frecuentes</a></li>
                            <li><a href="index.php?page=contact">Contacto</a></li>
                        </ul>
                    </nav>
                </div>
                
                <div class="col-lg-4">
                    <div class="top-option d-flex justify-content-end align-items-center">
                        
                        <div class="to-social d-none d-lg-flex align-items-center" style="gap: 15px; margin-right: 20px; padding-right: 20px; border-right: 1px solid rgba(255, 255, 255, 0.2);">
                            <a href="https://www.facebook.com/gymsandy" target="_blank"><i class="fa fa-facebook"></i></a>
                            <a href="https://www.instagram.com/sandysgym?igsh=MXU0c3NrNWZjZzMzYw==" target="_blank"><i class="fa fa-instagram"></i></a>
                            <a href="https://www.tiktok.com/@sandysgym" target="_blank"><i class="fab fa-tiktok"></i></a>
                        </div>
                        
                        <div class="d-none d-lg-block" style="margin-right: 15px;">
                            <?php if ($userName): ?>
                                <div class="dropdown">
                                    <a href="#" class="dropdown-toggle user-logged-btn" id="userDropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-user"></i> <?php echo $userName; ?>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right" id="userMenu" aria-labelledby="userDropdown">
                                        <a class="dropdown-item" href="index.php?page=user_home">Inicio</a>
                                        <a class="dropdown-item" href="index.php?page=user">Ver datos</a>
                                        <a class="dropdown-item" href="index.php?page=user_information">Modificar información</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="./query/logout.php">Cerrar sesión</a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <a href="index.php?page=login" class="login-btn"><i class="fa fa-user"></i> Iniciar Sesión</a>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <a href="index.php?page=inscribite" class="primary-btn btn-inscribete">Inscríbete Ya</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="canvas-open">
                <i class="fa fa-bars"></i>
            </div>
        </div>
    </header>
    <script src="./assets/js/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="./assets/js/bootstrap.min.js"></script>
    <script src="./assets/js/jquery.magnific-popup.min.js"></script>
    <script src="./assets/js/masonry.pkgd.min.js"></script>
    <script src="./assets/js/jquery.barfiller.js"></script>
    <script src="./assets/js/jquery.slicknav.js"></script>
    <script src="./assets/js/owl.carousel.min.js"></script>
    <script src="./assets/js/main.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var userDropdown = document.getElementById("userDropdown");
        var userMenu = document.getElementById("userMenu");
        var userDropdownMobile = document.getElementById("userDropdownMobile");
        var userMenuMobile = document.getElementById("userMenuMobile");
        
        if (userDropdown && userMenu) {
            userDropdown.addEventListener("click", function(event) {
                event.preventDefault();
                event.stopPropagation();
                userMenu.classList.toggle("show");
            });
            
            document.addEventListener("click", function(event) {
                if (!userDropdown.contains(event.target)) {
                    userMenu.classList.remove("show");
                }
            });
        }

        if (userDropdownMobile && userMenuMobile) {
            userDropdownMobile.addEventListener("click", function(event) {
                event.preventDefault();
                event.stopPropagation();
                userMenuMobile.classList.toggle("show");
            });
            
            document.addEventListener("click", function(event) {
                if (!userDropdownMobile.contains(event.target)) {
                    userMenuMobile.classList.remove("show");
                }
            });
        }
    });
    </script>
</body>

</html>
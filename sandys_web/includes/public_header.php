<?php
include('conn.php');
include('./query/select_data.php');

// Solo necesitamos saber si el usuario ha iniciado sesión
$isLoggedIn = isset($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sandys Gym</title>

    <link href="https://fonts.googleapis.com/css?family=Muli:300,400,500,600,700,800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Oswald:300,400,500,600,700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

    <link rel="stylesheet" href="./assets/css/bootstrap.min.css" type="text/css">
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
            color: #111111 !important;
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

        /* Ajuste del menú de escritorio para ocupar hasta las redes (EVITA QUE SE EMPALMEN) */
        @media (min-width: 992px) {
            .nav-menu ul {
                display: flex;
                gap: 40px; /* Separación uniforme entre los botones */
                margin-right: 20px; /* Separación con las redes sociales */
            }
            .nav-menu ul li {
                margin-right: 0 !important;
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
            white-space: nowrap; /* Evita que el texto salte a dos líneas */
        }
        .login-btn i, .user-logged-btn i {
            margin-right: 8px;
            color: #f36100;
            font-size: 16px;
        }
        .login-btn:hover, .user-logged-btn:hover {
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
                
                <?php if ($isLoggedIn): ?>
                    <li><a href="index.php?page=user_home" class="mobile-login-link"><i class="fa-solid fa-user"></i> MI CUENTA</a></li>
                <?php else: ?>
                    <li><a href="index.php?page=login" class="mobile-login-link"><i class="fa-solid fa-user"></i> INICIAR SESIÓN</a></li>
                <?php endif; ?>
            </ul>
            <div class="canvas-social">
                <a href="https://www.facebook.com/gymsandy" target="_blank" class="social-icon"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="https://www.instagram.com/sandysgym?igsh=MXU0c3NrNWZjZzMzYw==" target="_blank" class="social-icon"><i class="fa-brands fa-instagram"></i></a>
                <a href="https://www.tiktok.com/@sandysgym" target="_blank" class="social-icon"><i class="fa-brands fa-tiktok"></i></a>
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
                            <img src="./assets/img/logo.png" alt="Logo Sandys Gym" style="width: 200px !important; height: auto !important; max-height: none !important;">
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
                            <a href="https://www.facebook.com/gymsandy" target="_blank"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="https://www.instagram.com/sandysgym?igsh=MXU0c3NrNWZjZzMzYw==" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                            <a href="https://www.tiktok.com/@sandysgym" target="_blank"><i class="fa-brands fa-tiktok"></i></a>
                        </div>
                        
                        <div class="d-none d-lg-block" style="margin-right: 15px;">
                            <?php if ($isLoggedIn): ?>
                                <a href="index.php?page=user_home" class="user-logged-btn">
                                    <i class="fa-solid fa-user"></i> MI CUENTA
                                </a>
                            <?php else: ?>
                                <a href="index.php?page=login" class="login-btn">
                                    <i class="fa-solid fa-user"></i> INICIAR SESIÓN
                                </a>
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
</body>
</html>
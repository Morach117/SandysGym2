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
           VARIABLES GLOBALES (DARK MODE ESTRICTO)
           ========================================= */
        :root {
            --bg-color: #050505; 
            --input-bg: #1a1a1a; 
            --accent-red: #ef4444; 
            --accent-green: #10b981; 
            --accent-orange: #F28123;
        }

        body, html {
            background-color: var(--bg-color);
            color: #ffffff;
        }

        .header-section {
            background-color: var(--bg-color);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* =========================================
           MEJORAS DE DISEÑO MODERNO (HEADER)
           ========================================= */

        /* Ajustes Menu Offcanvas (Móvil) */
        .offcanvas-menu-wrapper { background: var(--input-bg) !important; }
        .canvas-close i { color: #ffffff !important; }
        .slicknav_menu .slicknav_icon-bar { background-color: #ffffff !important; }
        .slicknav_nav { background: var(--bg-color) !important; }
        .slicknav_nav a { color: #ffffff !important; }
        .slicknav_nav a:hover { color: var(--accent-orange) !important; }

        /* Iconos Sociales Móvil */
        .canvas-social {
            margin-top: 20px;
            display: flex;
            gap: 15px;
        }
        .canvas-social .social-icon i {
            font-size: 22px;
            color: #ffffff !important;
            transition: all 0.3s ease;
        }
        .canvas-social .social-icon i:hover { color: var(--accent-orange) !important; }

        /* Iconos Sociales Escritorio */
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
            color: var(--accent-orange);
            transform: translateY(-3px);
        }

        /* Ajuste del menú de escritorio */
        @media (min-width: 992px) {
            .nav-menu ul {
                display: flex;
                gap: 40px;
                margin-right: 20px;
            }
            .nav-menu ul li { margin-right: 0 !important; }
            .nav-menu ul li a:hover { color: var(--accent-orange) !important; }
        }

        /* Botón Iniciar Sesión Moderno */
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
            border-radius: 50rem;
            transition: all 0.3s ease;
            text-decoration: none !important;
            white-space: nowrap; 
        }
        .login-btn i, .user-logged-btn i {
            margin-right: 8px;
            color: var(--accent-orange);
            font-size: 16px;
            transition: color 0.3s ease;
        }
        .login-btn:hover, .user-logged-btn:hover {
            background: var(--accent-orange);
            color: var(--bg-color) !important;
            border-color: var(--accent-orange);
        }
        .login-btn:hover i, .user-logged-btn:hover i {
            color: var(--bg-color);
        }

        /* Botón Inscribirse (Escritorio) */
        .btn-inscribete {
            background-color: var(--accent-orange) !important;
            color: var(--bg-color) !important;
            border-radius: 50rem !important;
            padding: 12px 28px !important;
            font-weight: 700 !important;
            letter-spacing: 1px;
            border: none;
            box-shadow: 0 4px 15px rgba(242, 129, 35, 0.2);
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        .btn-inscribete:hover {
            filter: brightness(1.1);
            box-shadow: 0 6px 20px rgba(242, 129, 35, 0.4);
            color: var(--bg-color) !important;
        }

        /* Iniciar sesión (Menú móvil) */
        .mobile-login-link { color: #ffffff !important; font-weight: 600; }
        .mobile-login-link i { color: var(--accent-orange); margin-right: 8px; }
        .mobile-login-link:hover, .mobile-login-link:hover i { color: var(--accent-orange) !important; }

        /* =========================================
           HEADER RESPONSIVE Y CONTROLES MÓVILES
           ========================================= */
        .img-logo {
            width: 200px;
            height: auto;
        }

        /* Ocultar controles móviles en escritorio por defecto */
        .mobile-controls { display: none; }

        @media (max-width: 991px) {
            .header-section {
                padding: 15px 0; /* Reduce padding vertical */
            }
            .img-logo {
                width: 130px !important; /* Logo más pequeño para que quepa todo */
            }
            
            /* Mostrar bloque contenedor absoluto a la derecha */
            .mobile-controls {
                display: flex !important;
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
                align-items: center;
                gap: 15px;
            }

            /* Botón Inscríbete Compacto para Móvil */
            .btn-inscribete-mobile {
                background-color: var(--accent-orange) !important;
                color: var(--bg-color) !important;
                border-radius: 50rem !important;
                padding: 6px 14px !important;
                font-weight: 700 !important;
                font-size: 11px;
                letter-spacing: 0.5px;
                text-transform: uppercase;
                text-decoration: none !important;
                white-space: nowrap;
                box-shadow: 0 2px 10px rgba(242, 129, 35, 0.2);
            }
            
            /* Reseteamos el canvas-open de la plantilla original */
            .canvas-open {
                position: static !important;
                transform: none !important;
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .canvas-open i {
                font-size: 26px;
                color: #ffffff;
                cursor: pointer;
            }
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
                
                <li style="border-top: 1px solid #333; margin-top: 10px; padding-top: 10px;"></li>
                
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
    
    <header class="header-section position-relative">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-6 col-lg-2">
                    <div class="logo">
                        <a href="index.php?page=home">
                            <img src="./assets/img/logo.png" alt="Logo Sandys Gym" class="img-logo">
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-6 d-none d-lg-block">
                    <nav class="nav-menu">
                        <ul>
                            <li><a href="index.php?page=home">Inicio</a></li>
                            <li><a href="index.php?page=success_stories">Historias de éxito</a></li>
                            <li><a href="index.php?page=faq">Preguntas frecuentes</a></li>
                            <li><a href="index.php?page=contact">Contacto</a></li>
                        </ul>
                    </nav>
                </div>
                
                <div class="col-lg-4 d-none d-lg-flex">
                    <div class="top-option d-flex justify-content-end align-items-center w-100">
                        
                        <div class="to-social d-none d-lg-flex align-items-center" style="gap: 15px; margin-right: 20px; padding-right: 20px; border-right: 1px solid rgba(255, 255, 255, 0.2);">
                            <a href="https://www.facebook.com/gymsandy" target="_blank"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="https://www.instagram.com/sandysgym?igsh=MXU0c3NrNWZjZzMzYw==" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                            <a href="https://www.tiktok.com/@sandysgym" target="_blank"><i class="fa-brands fa-tiktok"></i></a>
                        </div>
                        
                        <div style="margin-right: 15px;">
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

            <div class="mobile-controls d-flex d-lg-none">
                <a href="index.php?page=inscribite" class="btn-inscribete-mobile">¡Inscríbete ya!</a>
                <div class="canvas-open">
                    <i class="fa fa-bars"></i>
                </div>
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
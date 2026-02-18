<?php
include('conn.php');
include('./api/select_data.php');

// Definir la variable $userName
$userName = '';

// Verificar si el usuario ha iniciado sesión
if (isset($_SESSION['admin'])) {
    // Obtener el nombre del usuario si está iniciado sesión
    $user_email = $_SESSION['admin']['soc_correo'];
    $consulta = "SELECT soc_nombres, soc_apepat, soc_apemat FROM san_socios WHERE soc_correo = :user_email";
    $stmt = $conn->prepare($consulta);
    $stmt->bindParam(':user_email', $user_email);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $userName = $row['soc_nombres'] . ' ' . $row['soc_apepat'] . ' ' . $row['soc_apemat'];
    }
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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/owl.carousel.min.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/barfiller.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/magnific-popup.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/style.css" type="text/css">

    <style>
        /* Tus estilos personalizados aquí */
        .canvas-social .social-icon i {
            font-size: 24px; margin: 0 10px; color: #000; transition: color 0.3s;
        }
        .canvas-social .social-icon i:hover {
            color: #F28123;
        }
    </style>
</head>

<body>
    <div class="offcanvas-menu-overlay"></div>
    <div class="offcanvas-menu-wrapper">
        <div class="canvas-close">
            <i class="fa-solid fa-xmark"></i> </div>
        <div class="canvas-search search-switch">
            <i class="fa-solid fa-magnifying-glass"></i> </div>
        <nav class="canvas-menu mobile-menu">
            <ul>
                <li><a href="index.php?page=home">Inicio</a></li>
                <li><a href="index.php?page=about_us">Sobre Nosotros</a></li>
                <li><a href="index.php?page=classes">Clases</a></li>
                <li><a href="index.php?page=services">Servicios</a></li>
                <li><a href="index.php?page=team">Nuestro Equipo</a></li>
                <li><a href="index.php?page=contact">Contacto</a></li>
                
                <?php if ($userName): ?>
                    <li>
                        <a href="index.php?page=user_home">
                            <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($userName); ?>
                        </a>
                        </li>
                <?php else: ?>
                    <li><a href="index.php?page=login"><i class="fa-solid fa-user"></i> Iniciar Sesión</a></li> 
                <?php endif; ?>
                </ul>
            <div class="canvas-social">
                <a href="https://www.facebook.com/gymsandy" target="_blank" class="social-icon"><i class="fa-brands fa-facebook"></i></a>
                <a href="https://www.instagram.com/sandysgym?igsh=MXU0c3NrNWZjZzMzYw==" target="_blank" class="social-icon"><i class="fa-brands fa-instagram"></i></a>
                <a href="https://www.tiktok.com/@sandysgym" target="_blank" class="social-icon"><i class="fa-brands fa-tiktok"></i></a>
            </div>
        </nav>
        <div id="mobile-menu-wrap"></div>
    </div>
    <header class="header-section">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-3">
                    <div class="logo">
                        <a href="index.php?page=home"><img src="./assets/img/logo.png" alt=""></a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <nav class="nav-menu">
                        <ul>
                            <li><a href="index.php?page=home">Inicio</a></li>
                            <li><a href="index.php?page=about_us">Sobre Nosotros</a></li>
                            <li><a href="index.php?page=classes">Clases</a></li>
                            <li><a href="index.php?page=services">Servicios</a></li>
                            <li><a href="index.php?page=team">Nuestro Equipo</a></li>
                            <li><a href="index.php?page=contact">Contacto</a></li>
                        </ul>
                    </nav>
                </div>
                <div class="col-lg-3">
                    <div class="top-option d-flex justify-content-end align-items-center">
                        <div class="to-search search-switch mr-3 d-none d-lg-block">
                            <i class="fa-solid fa-magnifying-glass"></i> </div>
                        <div class="to-social mr-3 d-none d-lg-block">
                            <a href="https://www.facebook.com/gymsandy" target="_blank"><i class="fa-brands fa-facebook"></i></a>
                            <a href="#"><i class="fa-brands fa-twitter"></i></a>
                            <a href="#"><i class="fa-brands fa-youtube"></i></a>
                            <a href="https://www.instagram.com/sandysgym?igsh=MXU0c3NrNWZjZzMzYw==" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                        
                        <div class="mr-3 d-none d-lg-block">
                            <?php if ($userName): ?>
                                <a href="index.php?page=user_home">
                                    <i class="fa-solid fa-user"></i> 
                                </a>
                            <?php else: ?>
                                <a href="index.php?page=login"><i class="fa-solid fa-user"></i></a> 
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="index.php?page=inscribite" class="primary-btn btn-inscribete">Inscríbete ya</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="canvas-open">
                <i class="fa-solid fa-bars"></i> </div>
        </div>
    </header>

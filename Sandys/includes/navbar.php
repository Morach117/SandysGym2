<?php

include('conn.php');
include('./query/select_data.php');

// Definir la variable $userName
$userName = '';
$subscribeMessage = '';

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
    
    // Obtener el mensaje personalizado (suponiendo que está almacenado en la misma tabla o una similar)
    $consultaMensaje = "SELECT subscribe_message FROM san_socios WHERE soc_correo = :user_email";
    $stmt = $conn->prepare($consultaMensaje);
    $stmt->bindParam(':user_email', $user_email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $subscribeMessage = $row['subscribe_message'];
    }
    
    $stmt->closeCursor();
}
?>

<!DOCTYPE html>
<html lang="zxx" class="no-js">
<head>
    <!-- Mobile Specific Meta -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Favicon-->
    <!-- <link rel="shortcut icon" href="img/fav.png"> -->
    <!-- Author Meta -->
    <meta name="author" content="CodePixar">
    <!-- Meta Description -->
    <meta name="description" content="">
    <!-- Meta Keyword -->
    <meta name="keywords" content="">
    <!-- meta character set -->
    <meta charset="UTF-8">
    <!-- Site Title -->
    <title>Sandys Gym</title>
    <!-- CSS -->
    <link rel="stylesheet" href="./assets/css/linearicons.css">
    <link rel="stylesheet" href="./assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="./assets/css/themify-icons.css">
    <link rel="stylesheet" href="./assets/css/bootstrap.css">
    <link rel="stylesheet" href="./assets/css/owl.carousel.css">
    <link rel="stylesheet" href="./assets/css/nice-select.css">
    <link rel="stylesheet" href="./assets/css/nouislider.min.css">
    <link rel="stylesheet" href="./assets/css/ion.rangeSlider.css" />
    <link rel="stylesheet" href="./assets/css/ion.rangeSlider.skinFlat.css" />
    <link rel="stylesheet" href="./assets/css/magnific-popup.css">
    <link rel="stylesheet" href="./assets/css/main.css">

    <style>/* Estilos generales */
.navbar-nav .nav-link.btn {
    margin-left: 10px;
    padding: 10px 20px;
}

/* Estilos específicos para móviles */
@media (max-width: 768px) {
    .navbar-nav .nav-link.btn {
        margin-left: 0;
        margin-top: 10px;
        text-align: center;
    }
}
</style>
</head>
<body>

<header class="header_area sticky-header">
    <div class="main_menu">
        <nav class="navbar navbar-expand-lg navbar-light main_box">
            <div class="container">
                <a class="navbar-brand logo_h" href="index.php"><img src="./assets/img/logo.png" alt="Gym Logo"></a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <div class="collapse navbar-collapse offset" id="navbarSupportedContent">
                    <ul class="nav navbar-nav menu_nav ml-auto">
                        <li class="nav-item active"><a class="nav-link" href="index.php">Inicio</a></li>
                        <li class="nav-item submenu dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                                aria-expanded="false">Servicios</a>
                            <ul class="dropdown-menu">
                                <li class="nav-item"><a class="nav-link" href="trainers.html">Entrenadores</a></li>
                                <li class="nav-item"><a class="nav-link" href="classes.html">Clases</a></li>
                                <li class="nav-item"><a class="nav-link" href="equipment.html">Equipamiento</a></li>
                                <li class="nav-item"><a class="nav-link" href="pricing.html">Precios</a></li>
                            </ul>
                        </li>
                        <li class="nav-item submenu dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                                aria-expanded="false">Blog</a>
                            <ul class="dropdown-menu">
                                <li class="nav-item"><a class="nav-link" href="blog.html">Artículos</a></li>
                                <li class="nav-item"><a class="nav-link" href="tips.html">Consejos</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="contact.html">Contacto</a></li>
                    </ul>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown">
                            <?php if ($userName): ?>
                                <a href="#" class="nav-link dropdown-toggle user-icon" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="lnr lnr-user"></span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                    <span class="dropdown-item-text"><?php echo $userName; ?></span>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="index.php?page=user">Ver datos</a>
                                    <a class="dropdown-item" href="index.php?page=user_information">Modificar información</a>
                                    <a class="dropdown-item" href="#">Opción 3</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="./query/logout.php">Cerrar sesión</a>
                                </div>
                            <?php else: ?>
                                <a href="index.php?page=login" class="nav-link user-icon">
                                    <span class="lnr lnr-user"></span>
                                </a>
                            <?php endif; ?>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white" href="subscribe.php">¡Inscríbete ya!</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
    <div class="search_input" id="search_input_box">
        <div class="container">
            <form class="d-flex justify-content-between">
                <input type="text" class="form-control" id="search_input" placeholder="Buscar aquí">
                <button type="submit" class="btn"></button>
                <span class="lnr lnr-cross" id="close_search" title="Cerrar búsqueda"></span>
            </form>
        </div>
    </div>
</header>
</body>
</html>


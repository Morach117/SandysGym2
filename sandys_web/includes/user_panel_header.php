<?php
// --- Lógica para obtener datos y estado de la membresía (sin cambios) ---
include_once(__DIR__ . '/../conn.php');
include_once(__DIR__ . '/../api/select_data.php');


date_default_timezone_set('America/Mexico_City');
$currentDate = new DateTime();
$socioId = $selSocioData['soc_id_socio'];
$query = "SELECT pag_fecha_fin FROM san_pagos WHERE pag_id_socio = :socioId AND pag_status = 'A' ORDER BY pag_fecha_fin DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':socioId', $socioId, PDO::PARAM_INT);
$stmt->execute();
$fechaFin = $stmt->fetchColumn();

$miembroActivo = true;
if ($fechaFin) {
    if (new DateTime() > new DateTime($fechaFin)) {
        $miembroActivo = false;
    }
} else {
    $miembroActivo = false;
}

// Obtenemos la página actual para la clase 'active'
$page = $_GET['page'] ?? 'user_home'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Usuario | Sandys Gym</title>
    <link href="https://fonts.googleapis.com/css?family=Muli:300,400,500,600,700,800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Oswald:300,400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="./assets/css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="./assets/css/style.css">

    <style>
        /* Hacemos que el header siempre sea oscuro en el panel de usuario */
        .header-section {
            background: #000000;
        }

        /* Estilo para el link de 'Rutinas' cuando está deshabilitado */
        .nav-menu ul li a.disabled,
        .mobile-menu ul li a.disabled {
            color: #555 !important;
            pointer-events: none;
            cursor: not-allowed;
        }
        
        /* Estilo para resaltar el botón de Salir */
        .nav-menu ul li a.logout-btn,
        .mobile-menu ul li a.logout-btn {
            background-color: #F28123;
            color: white !important;
            padding: 10px 20px !important;
            border-radius: 4px;
            margin-left: 15px;
            transition: background-color 0.3s ease;
        }
        .nav-menu ul li a.logout-btn:hover,
        .mobile-menu ul li a.logout-btn:hover {
            background-color: #d46c1a;
        }
        .nav-menu ul li a.logout-btn i,
        .mobile-menu ul li a.logout-btn i {
             margin-right: 8px;
        }

        /* Estilos para el menú social en móvil */
        .canvas-social .social-icon i {
            font-size: 24px;
            margin: 0 10px;
            color: #fff;
            transition: color 0.3s;
        }
        .canvas-social .social-icon i:hover {
            color: #F28123;
        }
    </style>
</head>
<body>
    <div id="preloder"><div class="loader"></div></div>

    <div class="offcanvas-menu-overlay"></div>
    <div class="offcanvas-menu-wrapper">
        <div class="canvas-close">
            <i class="fa-solid fa-xmark"></i> 
        </div>
        <nav class="canvas-menu mobile-menu">
            <ul>
                <li class="<?php echo ($page == 'user_home') ? 'active' : ''; ?>">
                    <a href="index.php?page=user_home"><i class="fas fa-tachometer-alt"></i> Panel</a>
                </li>
                <li class="<?php echo ($page == 'user_pago_membresia') ? 'active' : ''; ?>">
                    <a href="index.php?page=user_pago_membresia"><i class="fas fa-credit-card"></i> Pagar Membresía</a>
                </li>
                <li class="<?php echo ($page == 'mis_pagos') ? 'active' : ''; ?>">
                    <a href="index.php?page=mis_pagos"><i class="fas fa-receipt"></i> Mis Pagos</a>
                </li>
                <li class="<?php echo ($page == 'user_rutina') ? 'active' : ''; ?>">
                    <a href="index.php?page=user_rutina" class="<?php echo !$miembroActivo ? 'disabled' : ''; ?>">
                        <i class="fas fa-dumbbell"></i> Rutinas
                    </a>
                </li>
                <li class="<?php echo ($page == 'user_information') ? 'active' : ''; ?>">
                    <a href="index.php?page=user_information"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                </li>
                <li class="<?php echo ($page == 'user_calculator') ? 'active' : ''; ?>">
                    <a href="index.php?page=user_calculator"><i class="fas fa-calculator"></i> IMC</a>
                </li>
                <li>
                    <a href="./api/logout.php" class="logout-btn" style="margin-left: 0; text-align: center;">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </li>
            </ul>
        </nav>
        <div id="mobile-menu-wrap"></div>
        <div class="canvas-social" style="text-align: center; padding-top: 20px;">
            <a href="https://www.facebook.com/gymsandy" target="_blank" class="social-icon"><i class="fa-brands fa-facebook"></i></a>
            <a href="https://www.instagram.com/sandysgym?igsh=MXU0c3NrNWZjZzMzYw==" target="_blank" class="social-icon"><i class="fa-brands fa-instagram"></i></a>
            <a href="https://www.tiktok.com/@sandysgym" target="_blank" class="social-icon"><i class="fa-brands fa-tiktok"></i></a>
        </div>
    </div>
    <header class="header-section">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-3">
                    <div class="logo">
                        <a href="index.php?page=user_home">
                            <img src="./assets/img/logo.png" alt="Logo Sandys Gym">
                        </a>
                    </div>
                </div>
                <div class="col-lg-9">
                    <nav class="nav-menu">
                        <ul>
                            <li class="<?php echo ($page == 'user_home') ? 'active' : ''; ?>">
                                <a href="index.php?page=user_home"><i class="fas fa-tachometer-alt"></i> Panel</a>
                            </li>
                            <li class="<?php echo ($page == 'user_pago_membresia') ? 'active' : ''; ?>">
                                <a href="index.php?page=user_pago_membresia"><i class="fas fa-credit-card"></i> Pagar Membresía</a>
                            </li>
                            <li class="<?php echo ($page == 'mis_pagos') ? 'active' : ''; ?>">
                                <a href="index.php?page=mis_pagos"><i class="fas fa-receipt"></i> Mis Pagos</a>
                            </li>
                            <li class="<?php echo ($page == 'user_rutina') ? 'active' : ''; ?>">
                                <a href="index.php?page=user_rutina" class="<?php echo !$miembroActivo ? 'disabled' : ''; ?>">
                                    <i class="fas fa-dumbbell"></i> Rutinas
                                </a>
                            </li>
                            <li class="<?php echo ($page == 'user_information') ? 'active' : ''; ?>">
                                <a href="index.php?page=user_information"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                            </li>
                            <li class="<?php echo ($page == 'user_calculator') ? 'active' : ''; ?>">
                                <a href="index.php?page=user_calculator"><i class="fas fa-calculator"></i> IMC</a>
                            </li>
                            <li>
                                <a href="./api/logout.php" class="logout-btn">
                                    <i class="fas fa-sign-out-alt"></i> Salir
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            <div class="canvas-open"><i class="fa-solid fa-bars"></i></div>
        </div>
    </header>
</body>
</html>
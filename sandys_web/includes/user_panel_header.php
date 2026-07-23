<?php
include_once(__DIR__ . '/../conn.php');
include_once(__DIR__ . '/../api/select_data.php');

date_default_timezone_set('America/Mexico_City');
$currentDate = new DateTime();
$socioId = $selSocioData['soc_id_socio'];

$fotoPerfil = null;
if (!empty($selSocioData['soc_imagen'])) {
    $nombreArchivo = basename($selSocioData['soc_imagen']);
    $fotoPerfil = '../imagenes/avatar/' . $nombreArchivo;
}

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
    .header-section { background: #000000; padding: 15px 0; border-bottom: 1px solid #1a1a1a; }

    /* --- 1. MENÚ ESCRITORIO --- */
    .nav-menu { display: flex; justify-content: flex-end; align-items: center; } 
    .nav-menu ul { display: flex; align-items: center; margin: 0; padding: 0; }
    .nav-menu ul li { list-style: none; display: flex; align-items: center; margin-left: 20px; }
    .nav-menu ul li a { 
        display: flex; align-items: center; gap: 8px;
        color: #ffffff; font-family: 'Oswald', sans-serif; font-size: 15px; 
        text-transform: uppercase; letter-spacing: 0.5px; transition: 0.3s; text-decoration: none;
    }
    .nav-menu ul li.active > a { color: #F28123; }
    .nav-menu ul li a:hover { color: #F28123; }
    .nav-menu ul li a i { font-size: 16px; margin-top: -2px; }
    .nav-menu ul li a.disabled { color: #555 !important; pointer-events: none; cursor: not-allowed; }
    
    /* --- DROPDOWN DE USUARIO --- */
    .user-dropdown { position: relative; display: flex; align-items: center; cursor: pointer; padding: 10px 0; }
    .user-dropdown-toggle { display: flex; align-items: center; gap: 8px; color: #fff; transition: 0.3s; }
    .user-dropdown-toggle img { 
        width: 42px; height: 42px; border-radius: 50%; 
        object-fit: cover; border: 2px solid #F28123; 
        background-color: #1a1a1a; 
    }
    .user-dropdown-toggle .fa-user-circle { font-size: 42px; color: #F28123; }
    .user-dropdown:hover .user-dropdown-toggle { opacity: 0.8; }
    
    .user-dropdown-menu { 
        position: absolute; top: 100%; right: 0; background: #1a1a1a; 
        min-width: 200px; border-radius: 8px; padding: 10px 0; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.6); opacity: 0; visibility: hidden; 
        transform: translateY(10px); transition: 0.3s ease; z-index: 1000; border: 1px solid #333;
    }
    .user-dropdown:hover .user-dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }
    
    .user-dropdown-menu a { 
        display: flex; padding: 12px 20px; color: #fff; font-family: 'Oswald', sans-serif; 
        font-size: 14px; text-decoration: none; transition: 0.2s; gap: 10px; width: 100%;
    }
    .user-dropdown-menu a i { width: 20px; text-align: center; color: #888; }
    .user-dropdown-menu a:hover { background: #050505; color: #F28123; padding-left: 25px; }
    .user-dropdown-menu a:hover i { color: #F28123; }
    
    .user-dropdown-menu hr { border-color: #333; margin: 5px 0; }
    
    .user-dropdown-menu a.logout-link { color: #ef4444; }
    .user-dropdown-menu a.logout-link i { color: #ef4444; }
    .user-dropdown-menu a.logout-link:hover { background: #ef4444; color: #fff; }
    .user-dropdown-menu a.logout-link:hover i { color: #fff; }

    /* --- 2. MENÚ LATERAL MÓVIL --- */
    .slicknav_menu { display: none !important; }

    @media (min-width: 992px) {
        .offcanvas-menu-wrapper,
        .offcanvas-menu-overlay { display: none !important; }
    }

    .offcanvas-menu-wrapper {
        background-color: #121212 !important; border-left: 1px solid #222;
        padding: 40px 30px; overflow-y: auto;
    }
    
    .offcanvas-menu-wrapper.show-offcanvas-menu-wrapper,
    .offcanvas-menu-wrapper.active { display: flex !important; flex-direction: column; }
    
    .canvas-header {
        display: flex; flex-direction: column; align-items: center;
        margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #222; 
    }

    .canvas-close { align-self: flex-end; cursor: pointer; margin-bottom: 15px; }
    .canvas-close i { font-size: 26px; color: #888; transition: 0.3s; padding: 5px; }
    .canvas-close i:hover { color: #F28123; }

    .canvas-social { display: flex; justify-content: center; gap: 15px; width: 100%; }
    .canvas-social .social-icon i {
        font-size: 18px; color: #ccc; background: #1a1a1a;
        width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
        border-radius: 50%; transition: all 0.3s; border: 1px solid #333;
    }
    .canvas-social .social-icon i:hover { color: #fff; background: #F28123; border-color: #F28123; }

    .custom-mobile-menu { width: 100%; margin-bottom: 30px; }
    .custom-mobile-menu ul { padding: 0; margin: 0; list-style: none; }
    .custom-mobile-menu ul li { border-bottom: 1px solid #222; } 
    .custom-mobile-menu ul li:last-child { border-bottom: none; }
    
    .custom-mobile-menu ul li a {
        display: flex; align-items: center; gap: 15px;
        color: #e0e0e0 !important; font-family: 'Oswald', sans-serif; 
        font-size: 17px; padding: 18px 10px; text-transform: uppercase;
        text-decoration: none; transition: 0.3s;
    }
    .custom-mobile-menu ul li.active > a { color: #F28123 !important; }
    .custom-mobile-menu ul li a:hover { color: #F28123 !important; padding-left: 15px; background: rgba(255,255,255,0.02); }
    .custom-mobile-menu ul li a i { font-size: 18px; width: 25px; text-align: center; color: #777; transition: 0.3s; }
    .custom-mobile-menu ul li.active a i, .custom-mobile-menu ul li a:hover i { color: #F28123; }
    
    .custom-mobile-menu ul li a.disabled { color: #555 !important; pointer-events: none; }
    .custom-mobile-menu ul li a.disabled i { color: #444; }

    .logout-mobile-container { margin-top: auto; padding-top: 10px; }
    .logout-mobile-btn {
        display: flex; justify-content: center; align-items: center; gap: 10px;
        background-color: rgba(239, 68, 68, 0.1); color: #ef4444 !important;
        padding: 15px !important; border-radius: 8px;
        border: 1px solid rgba(239, 68, 68, 0.3); font-weight: bold;
        font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 17px;
        text-decoration: none; transition: 0.3s; width: 100%;
    }
    .logout-mobile-btn:hover { background-color: #ef4444; color: #fff !important; text-decoration: none; }

    .canvas-open { color: #fff; font-size: 26px; cursor: pointer; }
</style>
</head>
<body>
    <div id="preloder"><div class="loader"></div></div>

    <div class="offcanvas-menu-overlay"></div>
    <div class="offcanvas-menu-wrapper">
        <div class="canvas-header">
            <div class="canvas-close">
                <i class="fa-solid fa-xmark"></i> 
            </div>
            
            <div class="mobile-user-profile" style="text-align: center; margin-bottom: 25px; width: 100%;">
                <?php if ($fotoPerfil): ?>
                    <img src="<?php echo htmlspecialchars($fotoPerfil, ENT_QUOTES, 'UTF-8'); ?>" alt="" 
                         style="width: 75px; height: 75px; border-radius: 50%; border: 2px solid #F28123; object-fit: cover;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                    <i class="fas fa-user-circle" style="display:none; font-size: 75px; color: #F28123;"></i>
                <?php else: ?>
                    <i class="fas fa-user-circle" style="font-size: 75px; color: #F28123;"></i>
                <?php endif; ?>
                <div style="color: #fff; font-family: 'Oswald', sans-serif; font-size: 16px; margin-top: 10px; letter-spacing: 1px;">MI CUENTA</div>
            </div>

            <div class="canvas-social">
                <a href="https://www.facebook.com/gymsandy" target="_blank" class="social-icon"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="https://www.instagram.com/sandysgym" target="_blank" class="social-icon"><i class="fa-brands fa-instagram"></i></a>
                <a href="https://www.tiktok.com/@sandysgym" target="_blank" class="social-icon"><i class="fa-brands fa-tiktok"></i></a>
            </div>
        </div>

        <nav class="custom-mobile-menu">
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
                <li class="<?php echo ($page == 'user_calculator') ? 'active' : ''; ?>">
                    <a href="index.php?page=user_calculator"><i class="fas fa-calculator"></i> IMC</a>
                </li>
            </ul>
        </nav>
        
        <div class="logout-mobile-container">
            <a href="./api/logout.php" class="logout-mobile-btn">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </div>

    <header class="header-section">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-3 col-6">
                    <div class="logo">
                        <a href="index.php?page=user_home">
                            <img src="./assets/img/logo.png" alt="Logo Sandys Gym" style="max-height: 45px;">
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-9 d-none d-lg-block">
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
                            <li class="<?php echo ($page == 'user_calculator') ? 'active' : ''; ?>">
                                <a href="index.php?page=user_calculator"><i class="fas fa-calculator"></i> IMC</a>
                            </li>
                            
                            <li class="user-dropdown">
                                <div class="user-dropdown-toggle">
                                    <?php if ($fotoPerfil): ?>
                                        <img src="<?php echo htmlspecialchars($fotoPerfil, ENT_QUOTES, 'UTF-8'); ?>" alt="" 
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                                        <i class="fas fa-user-circle" style="display:none;"></i>
                                    <?php else: ?>
                                        <i class="fas fa-user-circle"></i>
                                    <?php endif; ?>
                                    <i class="fas fa-chevron-down" style="font-size: 11px;"></i>
                                </div>
                                <div class="user-dropdown-menu">
                                    <a href="index.php?page=user_information"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                                    <a href="index.php?page=user_monedero"><i class="fas fa-wallet"></i> Monedero</a>
                                    <hr>
                                    <a href="./api/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                                </div>
                            </li>
                        </ul>
                    </nav>
                </div>

                <div class="col-6 d-lg-none text-right">
                    <div class="canvas-open"><i class="fa-solid fa-bars"></i></div>
                </div>
            </div>
        </div>
    </header>
</body>
</html>
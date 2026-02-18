<?php
ob_start();

require_once("../funciones_globales/funciones_conexion.php");
require_once("../funciones_globales/funciones_phpBB.php");
require_once("../funciones_globales/funciones_comunes.php");
require_once("funciones/sesiones.php");
?>

<!DOCTYPE html>
<html lang="es-MX">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gymnasio | SERGYM</title>

    <link href="../css/bootstrap.css" rel="stylesheet">
    <link href="../css/css.css" rel="stylesheet">

    <script src="../js/jquery-2.1.0.min.js"></script>
    <script src="../js/bootstrap/modal.js"></script>
    <script src="../js/bootstrap/dropdown.js"></script>
    <script src="../js/bootstrap/collapse.js"></script>
    <script src="../js/datepicker/jquery-ui-1.10.4.custom.min.js"></script>
    <link href="../js/datepicker/jquery-ui.css" rel="stylesheet" type="text/css" />

    <?php
    $js_version = "?20200102";
    if (file_exists("js/js.js")) echo "<script type='application/javascript' src='js/js.js$js_version'></script>";
    if (file_exists("js/js_$seccion.js")) echo "<script type='application/javascript' src='js/js_$seccion.js$js_version'></script>";
    if (file_exists("js/js_$seccion" . "_$item.js")) echo "<script type='application/javascript' src='js/js_$seccion" . "_$item.js$js_version'></script>";
    ?>
</head>

<body>
    <div class="container">
        <nav class="navbar navbar-default" role="navigation">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="."><span class="glyphicon glyphicon-home"></span> <?= $empresa_abr ?></a>
                </div>
                <div id="navbar" class="collapse navbar-collapse">
                    <ul class="nav navbar-nav">
                        <li class="dropdown <?= ($seccion == 'socios') ? 'active' : '' ?>">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                <span class="glyphicon glyphicon-user"></span> Socios <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href=".?s=socios"><span class="glyphicon glyphicon-list"></span> Lista de Socios</a></li>
                                <li><a href=".?s=socios&i=lista_vigentes"><span class="glyphicon glyphicon-ok-circle"></span> Socios Vigentes</a></li>
                                <li><a href=".?s=socios&i=lista_vencidos"><span class="glyphicon glyphicon-remove-circle"></span> Socios Vencidos</a></li>
                                <li><a href=".?s=socios&i=duplicados"><span class="glyphicon glyphicon-pause"></span> Duplicados</a></li>
                                <li role="separator" class="divider"></li>
                                <li><a href=".?s=socios&i=nuevo"><span class="glyphicon glyphicon-plus-sign"></span> Nuevo Socio</a></li>
                            </ul>
                        </li>
                        <style>
                            @media (min-width: 768px) {
                                .dropdown:hover .dropdown-menu {
                                    display: block;
                                    margin-top: 0;
                                }
                            }
                        </style>
                        <li class="<?= ($seccion == 'horas') ? 'active' : '' ?>"><a href=".?s=horas"><span class="glyphicon glyphicon-time"></span> Horas</a></li>
                        <li class="<?= ($seccion == 'visitas') ? 'active' : '' ?>"><a href=".?s=visitas"><span class="glyphicon glyphicon-time"></span> Visitas</a></li>
                        <li class="<?= ($seccion == 'venta') ? 'active' : '' ?>"><a href=".?s=venta"><span class="glyphicon glyphicon-shopping-cart"></span> Venta</a></li>
                        <li class="<?= ($seccion == 'prepagos') ? 'active' : '' ?>"><a href=".?s=prepagos"><span class="glyphicon glyphicon-usd"></span> Monedero</a></li>
                        <li class="dropdown <?= ($seccion == 'videos') ? 'active' : '' ?>">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                <span class="glyphicon glyphicon-film"></span> Videos <span class="caret"></span>
                            </a>

                            <ul class="dropdown-menu">
                                <li>
                                    <a href=".?s=videos&i=index">
                                        <span class="glyphicon glyphicon-th-list"></span> Rutina <?= ($item == 'Rutina') ? $focus : '' ?>
                                    </a>
                                </li>
                                <li>
                                    <a href=".?s=videos&i=videos">
                                        <span class="glyphicon glyphicon-play-circle"></span> Video <?= ($item == 'Video') ? $focus : '' ?>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                    <ul class="nav navbar-nav navbar-right">
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="glyphicon glyphicon-th"></span> <?= $nombres ?><b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li><a href=".?s=perfil"><span class="glyphicon glyphicon-user"></span> Perfil</a></li>
                                <?= $administrador ?>
                                <li class="divider"></li>
                                <li><a href=".?s=salir"><span class="glyphicon glyphicon-log-out"></span> Salir</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="row">
            <div class="col-md-12">
                <div class="well">
                    <?php
                    // Carga de archivos PHP (sin cambios)
                    if (file_exists("funciones/funciones_$seccion.php")) require_once("funciones/funciones_$seccion.php");
                    if (file_exists("funciones/funciones_$seccion" . "_$item.php")) require_once("funciones/funciones_$seccion" . "_$item.php");
                    if (file_exists("secciones/$seccion/$item.php")) require_once("secciones/$seccion/$item.php");
                    elseif (file_exists("secciones/$seccion/index.php")) require_once("secciones/$seccion/index.php");
                    else require_once("secciones/inicio/index.php");
                    mysqli_close($conexion);
                    ?>
                </div>
            </div>
        </div>

    </div>
    <footer class="footer">
        <div class="container">
            <div class="row text-muted">
                <div class="col-md-12 text-center">
                    <a href="http://sergym.com">http://sergym.com</a> | Gymnasio | SERGYM &copy; <?= date('Y') ?> | Servicios Generales y de Mantenimiento | <?= date('d/m/Y h:i:s a') ?>
                </div>
            </div>
        </div>
    </footer>

    <div id="ticket_cliente"></div>
    <div class="modal fade" id="modal_principal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"></div>
</body>

</html>
<?php
session_start();
$_SESSION['admin'] = [
    'soc_id_socio' => 12,
    'soc_correo' => 'test@example.com',
    'adminnakalogin' => true,
];
session_write_close();

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/SandysGym2/sandys_web/index.php?page=user_home';
$_GET['page'] = 'user_home';
$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = 80;
require 'index.php';

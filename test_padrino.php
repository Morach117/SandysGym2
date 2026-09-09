<?php
require_once 'conn.php';
require_once 'sandys_web/api/lib/UserService.php';

$us = new UserService($conn);

// Hacemos public a findPadrino solo para probar
$reflection = new ReflectionClass($us);
$method = $reflection->getMethod('findPadrino');
$method->setAccessible(true);

$tel_referido = '9611967322';
$result = $method->invokeArgs($us, [$tel_referido]);

var_dump($result);

// Revisemos la base de datos a ver qué números hay
$stmt = $conn->query("SELECT soc_id_socio, soc_nombres, soc_tel_cel FROM san_socios LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

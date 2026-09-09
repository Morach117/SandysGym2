<?php
require_once 'c:\xampp\htdocs\SandysGym2\sandys_web\conn.php';
$stmt = $conn->prepare("SELECT soc_id_socio, soc_tel_cel, soc_nombres FROM san_socios WHERE soc_tel_cel LIKE '%961%196%7322%' OR REPLACE(REPLACE(REPLACE(REPLACE(soc_tel_cel, ' ', ''), '-', ''), '(', ''), ')', '') = '9611967322'");
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

<?php
require 'conn.php';
$stmt = $conn->query("SELECT soc_tel_cel, soc_correo FROM san_socios WHERE soc_correo_status = 0 AND soc_tel_cel != '' LIMIT 1");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

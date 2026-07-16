<?php
require 'c:/xampp/htdocs/SandysGym2/sandys_web/conn.php';
$stmt = $conn->query("DESCRIBE san_socios validation_code");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>

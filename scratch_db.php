<?php
require 'c:/xampp/htdocs/SandysGym2/sandys_web/conn.php';
$stmt = $conn->query('DESCRIBE san_codigos');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

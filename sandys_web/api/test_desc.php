<?php
require_once 'conn.php';
$stmt = $conn->query("DESCRIBE san_socios");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

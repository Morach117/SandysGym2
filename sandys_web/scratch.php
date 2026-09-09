<?php
require 'conn.php';
$stmt = $conn->query('DESCRIBE san_socios');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($columns as $col) {
    if ($col['Field'] == 'san_password') {
        echo "san_password type: " . $col['Type'] . "\n";
    }
}

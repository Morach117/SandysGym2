<?php
session_start();
$_SESSION['admin'] = [
    'soc_id_socio' => 99999, // ID that does not exist
    'soc_correo' => 'doesnotexist@example.com',
    'adminnakalogin' => true,
];
session_write_close();

$cookie = "PHPSESSID=" . session_id();

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/SandysGym2/sandys_web/index.php?page=user_home");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIE, $cookie);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow, so we can see the redirect
$response = curl_exec($ch);
echo "RESPONSE 1 (user_home):\n$response\n\n";

// Now follow the redirect
curl_setopt($ch, CURLOPT_URL, "http://localhost/SandysGym2/sandys_web/index.php?page=login");
$response2 = curl_exec($ch);
echo "RESPONSE 2 (login):\n$response2\n";

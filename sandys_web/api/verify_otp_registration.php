<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../conn.php';

header('Content-Type: application/json');

if (!isset($_POST['otp']) || !isset($_POST['email'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
    exit;
}

$otp_input = trim($_POST['otp']);
$email_input = trim($_POST['email']);

if (!isset($_SESSION['registro_otp']) || !isset($_SESSION['registro_email']) || $_SESSION['registro_email'] !== $email_input) {
    echo json_encode(['success' => false, 'message' => 'Sesión de verificación expirada. Por favor, vuelve a ingresar tu correo.']);
    exit;
}

if ($otp_input !== (string)$_SESSION['registro_otp']) {
    echo json_encode(['success' => false, 'message' => 'El código de verificación es incorrecto.']);
    exit;
}

// Si es correcto, obtenemos los datos del usuario
$query = "SELECT soc_correo, soc_nombres, soc_apepat, soc_apemat, soc_tel_cel, soc_genero, MONTH(soc_fecha_nacimiento) as mes_nacimiento 
          FROM san_socios WHERE soc_correo = :email LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':email', $email_input, PDO::PARAM_STR);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Eliminamos el OTP de la sesión para evitar reusarlo
    unset($_SESSION['registro_otp']);
    
    echo json_encode([
        'success' => true, 
        'data' => [
            'email' => $row['soc_correo'],
            'name' => $row['soc_nombres'],
            'paternal_surname' => $row['soc_apepat'],
            'maternal_surname' => $row['soc_apemat'],
            'telefono' => $row['soc_tel_cel'],
            'genero' => ($row['soc_genero'] == 'Femenino' || $row['soc_genero'] == 'F') ? 'Femenino' : 'Masculino',
            'mes_nacimiento' => str_pad($row['mes_nacimiento'], 2, '0', STR_PAD_LEFT)
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontraron datos del usuario.']);
}

$stmt->closeCursor();
$conn = null;
?>

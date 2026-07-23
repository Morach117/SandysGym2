<?php
if (isset($_SESSION['admin']['soc_correo'])) {
    $socioCorreo = $_SESSION['admin']['soc_correo'];

    $consulta = "SELECT * FROM san_socios WHERE soc_correo = :socioCorreo";
    $stmt = $conn->prepare($consulta);
    $stmt->bindParam(':socioCorreo', $socioCorreo);
    $stmt->execute();
    $selSocioData = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} else {
    $selSocioData = array();
}
?>

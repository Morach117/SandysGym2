<?php
// pages/accept_invite.php

// 1. Capturar la referencia (ID en Base64)
$ref = $_GET['ref'] ?? '';
$idTitular = base64_decode($ref);

// Si entran al link sin código válido, los mandamos al home usando JS
if (empty($ref) || !is_numeric($idTitular)) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}
?>

<div style="background-color: #050505; height: 100vh; width: 100vw; position: fixed; top: 0; left: 0; z-index: 9999; display: flex; justify-content: center; align-items: center; color: white; font-family: sans-serif;">
    <h3>Procesando invitación...</h3>
</div>

<script>
    // 2. Obtenemos el ID desencriptado
    const idTitular = "<?php echo htmlspecialchars($idTitular); ?>";
    
    if (idTitular) {
        // GUARDADO INFALIBLE 1: LocalStorage
        localStorage.setItem('gym_pending_invite', idTitular);
        
        // GUARDADO INFALIBLE 2: Cookie generada desde JavaScript (Dura 2 horas)
        // Esto evita el error de PHP "Headers already sent"
        document.cookie = "gym_pending_invite=" + idTitular + "; path=/; max-age=" + (2 * 3600);
    }

    // 3. Redirigir directamente a la página de inscripción
    window.location.replace("index.php?page=login");
</script>
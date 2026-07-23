<?php
$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

$isLoggedIn = isset($_SESSION['admin']);
$redirectPage = $isLoggedIn ? 'user_home' : 'login';
?>

<div style="background-color: #050505; height: 100vh; width: 100vw; position: fixed; top: 0; left: 0; z-index: 9999; display: flex; justify-content: center; align-items: center; color: white; font-family: sans-serif;">
    <h3>Procesando invitación...</h3>
</div>

<script>
    const inviteToken = "<?php echo htmlspecialchars($token); ?>";
    const redirectPage = "<?php echo $redirectPage; ?>";
    
    if (inviteToken) {
        localStorage.setItem('gym_invite_token', inviteToken);
        document.cookie = "gym_invite_token=" + inviteToken + "; path=/; max-age=" + (2 * 3600);
    }

    window.location.replace("index.php?page=" + redirectPage);
</script>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_GET['email']) && isset($_GET['code'])) {
    require_once __DIR__ . '/../conn.php';
    
    $email_get = trim($_GET['email']);
    $code_get = trim($_GET['code']);
    
    try {
        $stmt = $conn->prepare("SELECT soc_id_socio, soc_correo_status, validation_expires FROM san_socios WHERE soc_correo = :email AND validation_code = :code");
        $stmt->bindParam(':email', $email_get);
        $stmt->bindParam(':code', $code_get);
        $stmt->execute();
        $user_val = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_val) {
            if ($user_val['soc_correo_status'] == 1) {
                $_SESSION['sweet_alert'] = ['type' => 'info', 'message' => 'Tu cuenta ya estaba verificada.'];
            } elseif ($user_val['validation_expires'] !== null && strtotime($user_val['validation_expires']) < time()) {
                $_SESSION['sweet_alert'] = ['type' => 'error', 'message' => 'El enlace ha expirado. Por favor, solicita uno nuevo.'];
            } else {
                $conn->beginTransaction();
                $updateStmt = $conn->prepare("UPDATE san_socios SET soc_correo_status = 1, validation_code = NULL, validation_expires = NULL WHERE soc_id_socio = :id");
                $updateStmt->bindParam(':id', $user_val['soc_id_socio']);
                $updateStmt->execute();
                $conn->commit();
                
                $_SESSION['sweet_alert'] = ['type' => 'success', 'message' => '¡Cuenta verificada correctamente! Ya puedes iniciar sesión.'];
            }
            echo "<script>window.location.href = 'index.php?page=login';</script>";
            exit;
        } else {
            $_SESSION['sweet_alert'] = ['type' => 'error', 'message' => 'Enlace de verificación inválido.'];
            echo "<script>window.location.href = 'index.php?page=login';</script>";
            exit;
        }
    } catch (Exception $e) {
        error_log("Error validación GET: " . $e->getMessage());
    }
}
?>
<link href="https://fonts.googleapis.com/css2?family=Muli:wght@300;400;700&family=Oswald:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* --- 1. GENERAL --- */
    body { background-color: #050505; color: #e0e0e0; font-family: 'Muli', sans-serif; }

    /* --- 2. LAYOUT --- */
    .login_box_area {
        padding-top: 140px; padding-bottom: 80px; min-height: 100vh;
        display: flex; align-items: center; justify-content: center; box-sizing: border-box;
    }
    .login-container {
        display: flex; width: 100%; max-width: 900px;
        background-color: #121212; border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
        border: 1px solid #2a2a2a; overflow: hidden;
    }

    /* --- 3. COLUMNA IZQUIERDA --- */
    .login_box_img {
        position: relative; width: 45%;
        background: linear-gradient(135deg, rgba(34, 10, 10, 0.85) 0%, rgba(0, 0, 0, 0.95) 100%), url('./assets/img/login.jpg');
        background-size: cover; background-position: center;
        display: flex; align-items: center; justify-content: center;
    }
    .img-overlay { position: relative; z-index: 2; padding: 40px; text-align: center; width: 100%; }
    .img-overlay h4 { font-family: 'Oswald', sans-serif; font-size: 32px; color: #fff; margin-bottom: 15px; text-transform: uppercase; }
    .img-overlay p { color: #aaa; margin-bottom: 25px; font-size: 15px; line-height: 1.6; }

    /* --- 4. FORMULARIO DERECHA --- */
    .login_form_inner { width: 55%; padding: 50px 40px; background-color: #121212; display: flex; flex-direction: column; justify-content: center; }
    .login_form_inner h3 { font-family: 'Oswald', sans-serif; font-size: 28px; color: #fff; text-transform: uppercase; margin-bottom: 5px; }
    .welcome-text { margin-bottom: 30px; color: #888; font-size: 14px; text-align: left; }
    #userEmail { color: #e0e0e0; }

    /* --- 5. INPUTS DE VALIDACIÓN --- */
    .code-inputs-container {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-bottom: 30px;
    }
    
    .code-input {
        background-color: #1a1a1a !important; 
        border: 1px solid #333 !important; 
        color: #fff !important;
        height: 55px !important; 
        width: 45px !important;
        border-radius: 10px !important;
        padding: 0 !important; 
        font-size: 24px !important; 
        font-weight: bold;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: none !important;
    }
    
    .code-input:focus { 
        border-color: #ef4444 !important; 
        background-color: #121212 !important;
        outline: none;
    }

    /* --- 6. BOTONES Y OPCIONES --- */
    .form-options { display: flex; justify-content: center; align-items: center; margin-top: 25px; font-size: 14px; color: #aaa; }
    .form-options a { color: #ef4444; text-decoration: none; font-weight: 600; transition: 0.3s; }
    .form-options a:hover { color: #ff6b6b; }
    
    .primary-btn { 
        background-color: #ef4444; color: white; border: none; padding: 16px; 
        border-radius: 10px; font-family: 'Oswald', sans-serif; font-size: 16px; 
        text-transform: uppercase; font-weight: 700; cursor: pointer; width: 100%; transition: 0.3s; 
    }
    .primary-btn:hover { background-color: #d12f2f; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3); }

    /* 🔥 OVERRIDE GLOBAL PARA FORZAR SWEETALERT EN MODO OSCURO 🔥 */
    div.swal2-popup {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
        border: 1px solid #333 !important;
        border-radius: 12px !important;
    }
    div.swal2-title, div.swal2-html-container { color: #ffffff !important; }
    .swal2-icon.swal2-error { border-color: #ef4444 !important; color: #ef4444 !important; }
    .swal2-icon.swal2-error [class^=swal2-x-mark-line] { background-color: #ef4444 !important; }
    .swal2-icon.swal2-warning { border-color: #facc15 !important; color: #facc15 !important; }
    .swal2-icon.swal2-success { border-color: #10b981 !important; color: #10b981 !important; }
    .swal2-icon.swal2-success [class^=swal2-success-line] { background-color: #10b981 !important; }
    .swal2-icon.swal2-success .swal2-success-ring { border-color: rgba(16, 185, 129, 0.3) !important; }
    .swal2-confirm { background-color: #ef4444 !important; color: white !important; font-weight: bold !important; border-radius: 8px !important; }

    /* --- RESPONSIVE --- */
    @media (max-width: 991px) {
        .login_box_img { display: none !important; }
        .login-container { max-width: 450px; flex-direction: column; }
        .login_form_inner { width: 100%; padding: 40px 25px; }
    }
</style>

<section class="login_box_area">
    <div class="login-container">
        
        <div class="login_box_img d-none d-lg-flex">
            <div class="img-overlay">
                <h4>¡Ya casi estás!</h4>
                <p>Estás a un solo paso de activar tu cuenta y unirte a la familia Sandys Gym.</p>
            </div>
        </div>
        
        <div class="login_form_inner">
            <h3>Verifica tu Cuenta</h3>
            <p class="welcome-text">
                Hemos enviado un código de 6 dígitos a:
                <br>
                <strong id="userEmail">tu@correo.com</strong>
            </p>
            
            <form class="login_form" id="validationForm" method="post" novalidate="novalidate">
                
                <div class="code-inputs-container">
                    <input type="text" class="form-control code-input" maxlength="1" required>
                    <input type="text" class="form-control code-input" maxlength="1" required>
                    <input type="text" class="form-control code-input" maxlength="1" required>
                    <input type="text" class="form-control code-input" maxlength="1" required>
                    <input type="text" class="form-control code-input" maxlength="1" required>
                    <input type="text" class="form-control code-input" maxlength="1" required>
                    <input type="hidden" name="validation_code" id="validation_code">
                    <input type="hidden" id="resend_email_fallback" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? $_SESSION['email'] ?? $_GET['email'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="primary-btn">VALIDAR CUENTA</button>

                <div class="form-options">
                    <a href="#" id="resendCodeLink">¿No recibiste el código? Reenviar</a>
                </div>
            </form>
        </div>
    </div>
</section>
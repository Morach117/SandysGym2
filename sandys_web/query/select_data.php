<?php
// It's good practice to ensure a session has been started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Initialize the variable with a default empty array.
// This prevents "undefined variable" errors on pages if the user is not logged in.
$selSocioData = [];

// 2. Check if the user's session data is set.
if (isset($_SESSION['admin']['soc_correo'])) {
    
    try {
        // 3. Prepare and execute the database query in a secure way.
        $socioCorreo = $_SESSION['admin']['soc_correo'];

        $stmt = $conn->prepare("SELECT * FROM san_socios WHERE soc_correo = :socioCorreo LIMIT 1");
        $stmt->bindParam(':socioCorreo', $socioCorreo, PDO::PARAM_STR);
        $stmt->execute();

        // 4. Fetch the data and assign it.
        // fetch() returns false if no user is found.
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $selSocioData = $data;
        } else {
            // SECURITY: The user has a valid session, but their account was deleted
            // from the database. We should destroy the invalid session to log them out.
            session_destroy();
        }

    } catch (PDOException $e) {
        // 5. Gracefully handle any database errors.
        // On a live website, you would log this error instead of displaying it.
        // error_log('Database query failed: ' . $e->getMessage());
        
        // Ensure the variable is empty so the rest of the site doesn't break.
        $selSocioData = [];
    }
}
?>
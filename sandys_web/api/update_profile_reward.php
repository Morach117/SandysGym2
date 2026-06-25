<?php
// api/update_profile_reward.php

ob_start();
ini_set('display_errors', 0); 
error_reporting(E_ALL);

// Incrementar límite de memoria a 512M para decodificar fotos de celulares de gama alta (>48MP)
ini_set('memory_limit', '512M'); 

require_once __DIR__ . '/../conn.php'; 

$response = ['status' => 'error', 'message' => 'Error general'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

try {
    $idSocio = isset($_POST['id_socio']) ? intval($_POST['id_socio']) : 0;
    
    $nombres        = strtoupper(trim($_POST['nombres'] ?? ''));
    $apPaterno      = strtoupper(trim($_POST['ap_paterno'] ?? ''));
    $apMaterno      = strtoupper(trim($_POST['ap_materno'] ?? ''));
    $genero         = isset($_POST['genero']) ? $_POST['genero'] : '';
    $mesNac         = trim($_POST['mes_nacimiento'] ?? ''); 
    $direccion      = strtoupper(trim($_POST['direccion'] ?? '')); 
    $telCel         = trim($_POST['tel_cel'] ?? '');
    
    $emerNombres    = strtoupper(trim($_POST['emer_nombres'] ?? ''));
    $emerTel        = trim($_POST['emer_tel'] ?? '');
    $emerParentesco = strtoupper(trim($_POST['emer_parentesco'] ?? ''));

    if ($idSocio <= 0) throw new Exception("ID de socio inválido.");

    // Se define fuera del IF para que esté disponible durante el unlink()
    $directorioDestino = __DIR__ . '/../../imagenes/avatar/';
    $nombreArchivoFinal = null;

    // ---------------------------------------------------------
    // PROCESAR FOTO Y CONVERTIR A JPG CON REDIMENSIONAMIENTO
    // ---------------------------------------------------------
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
        
        $uploadError = $_FILES['foto_perfil']['error'];
        
        if ($uploadError !== UPLOAD_ERR_OK) {
            $erroresUpload = [
                UPLOAD_ERR_INI_SIZE   => 'La foto es demasiado pesada y supera el límite del servidor.',
                UPLOAD_ERR_FORM_SIZE  => 'La foto es demasiado pesada.',
                UPLOAD_ERR_PARTIAL    => 'El archivo se subió a medias. Intenta de nuevo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal en el servidor.',
                UPLOAD_ERR_CANT_WRITE => 'Error de permisos al escribir la foto en el disco.'
            ];
            $mensajeError = $erroresUpload[$uploadError] ?? 'Error desconocido al subir la foto.';
            throw new Exception($mensajeError);
        }

        $fileTmpPath = $_FILES['foto_perfil']['tmp_name'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmpPath);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception("Formato de imagen inválido o archivo corrupto.");
        }

        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0777, true);
        }

        list($origWidth, $origHeight) = getimagesize($fileTmpPath);
        if (!$origWidth || !$origHeight) throw new Exception("La imagen está corrupta.");

        $orientation = 1;
        if ($mimeType === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($fileTmpPath);
            if ($exif && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];
            }
        }
        
        switch ($mimeType) {
            case 'image/jpeg': $imgRes = @imagecreatefromjpeg($fileTmpPath); break;
            case 'image/png':  $imgRes = @imagecreatefrompng($fileTmpPath);  break;
            case 'image/webp': $imgRes = @imagecreatefromwebp($fileTmpPath); break;
            default: $imgRes = false;
        }

        if (!$imgRes) throw new Exception("Error al procesar la imagen nativa.");

        if ($orientation != 1) {
            $deg = 0;
            switch ($orientation) {
                case 3: $deg = 180; break;
                case 6: $deg = 270; list($origWidth, $origHeight) = [$origHeight, $origWidth]; break; 
                case 8: $deg = 90;  list($origWidth, $origHeight) = [$origHeight, $origWidth]; break;  
            }
            if ($deg) {
                $rotatedImg = imagerotate($imgRes, $deg, 0);
                if ($rotatedImg !== false) {
                    imagedestroy($imgRes); 
                    $imgRes = $rotatedImg; 
                }
            }
        }

        $maxWidth = 800;
        $maxHeight = 800;
        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
        
        if ($ratio < 1) {
            $newWidth = round($origWidth * $ratio);
            $newHeight = round($origHeight * $ratio);
        } else {
            $newWidth = $origWidth;
            $newHeight = $origHeight;
        }

        $resizedImg = imagecreatetruecolor($newWidth, $newHeight);
        
        $white = imagecolorallocate($resizedImg, 255, 255, 255);
        imagefill($resizedImg, 0, 0, $white);
        
        imagecopyresampled($resizedImg, $imgRes, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        $nombreArchivoFinal = $idSocio . "_" . time() . ".jpg"; 
        $rutaCompleta = $directorioDestino . $nombreArchivoFinal;

        if (!imagejpeg($resizedImg, $rutaCompleta, 85)) {
            throw new Exception("Error de permisos al escribir la foto finalizada.");
        }

        imagedestroy($imgRes);
        imagedestroy($resizedImg);
    }

    // ---------------------------------------------------------
    // LÓGICA DE RECOMPENSA Y ACTUALIZACIÓN
    // ---------------------------------------------------------
    $estanTodosLosDatos = (
        !empty($nombres) && !empty($apPaterno) && !empty($genero) &&
        !empty($mesNac) && !empty($telCel) && !empty($direccion) && 
        !empty($emerNombres) && !empty($emerTel) && !empty($emerParentesco)
    );

    // Se agrega soc_imagen a la consulta para saber qué borrar
    $checkQuery = "SELECT soc_mon_saldo, perfil_completado_reward, soc_tel_cel, soc_fecha_nacimiento, soc_imagen FROM san_socios WHERE soc_id_socio = :id";
    $stmtCheck = $conn->prepare($checkQuery);
    $stmtCheck->bindParam(':id', $idSocio);
    $stmtCheck->execute();
    $datosActuales = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$datosActuales) throw new Exception("Socio no encontrado.");

    $imagenAnterior = $datosActuales['soc_imagen'] ?? 'noavatar.jpg';
    $yaRecibio = (int)($datosActuales['perfil_completado_reward'] ?? 0) === 1;
    $estabaIncompleto = empty($datosActuales['soc_tel_cel']) || $datosActuales['soc_fecha_nacimiento'] == '0000-00-00';
    $darRecompensa = (!$yaRecibio && $estabaIncompleto && $estanTodosLosDatos);

    $fechaSQL = $datosActuales['soc_fecha_nacimiento'];
    if (!empty($mesNac) && $fechaSQL == '0000-00-00') {
        $fechaSQL = "2000-" . str_pad($mesNac, 2, "0", STR_PAD_LEFT) . "-01"; 
    }

    $conn->beginTransaction();

    $sql = "UPDATE san_socios SET 
            soc_nombres = :nom, soc_apepat = :apat, soc_apemat = :amat, 
            soc_genero = :gen, soc_fecha_nacimiento = :fnac, soc_tel_cel = :tel, 
            soc_direccion = :dir, soc_emer_nombres = :enom, soc_emer_tel = :etel, 
            soc_emer_parentesco = :epar";
    
    if ($darRecompensa) $sql .= ", soc_mon_saldo = soc_mon_saldo + 35, perfil_completado_reward = 1";
    if ($nombreArchivoFinal) $sql .= ", soc_imagen = :foto";

    $sql .= " WHERE soc_id_socio = :id";
    $stmt = $conn->prepare($sql);
    
    $params = [
        ':nom' => $nombres, ':apat' => $apPaterno, ':amat' => $apMaterno,
        ':gen' => $genero, ':fnac' => $fechaSQL, ':tel' => $telCel, ':dir' => $direccion,
        ':enom' => $emerNombres, ':etel' => $emerTel, ':epar' => $emerParentesco,
        ':id' => $idSocio
    ];
    if ($nombreArchivoFinal) $params[':foto'] = $nombreArchivoFinal;

    $stmt->execute($params);

    if ($darRecompensa) {
        $saldoNuevo = $datosActuales['soc_mon_saldo'] + 35;
        $sqlMov = "INSERT INTO san_prepago_detalle 
                   (pred_id_socio, pred_fecha, pred_movimiento, pred_importe, pred_saldo, pred_descripcion, pred_id_usuario) 
                   VALUES (:id, NOW(), 'A', 35.00, :saldo, 'Bonificación Perfil Completado', 1)";
        $stmtMov = $conn->prepare($sqlMov);
        $stmtMov->execute([':id' => $idSocio, ':saldo' => $saldoNuevo]);
    }

    $conn->commit();

    // ---------------------------------------------------------
    // LIMPIEZA DE IMAGEN ANTERIOR (Ejecutar solo tras commit exitoso)
    // ---------------------------------------------------------
    if ($nombreArchivoFinal && $imagenAnterior !== 'noavatar.jpg' && !empty($imagenAnterior)) {
        $rutaAnterior = $directorioDestino . $imagenAnterior;
        if (file_exists($rutaAnterior)) {
            @unlink($rutaAnterior); // Silenciador de errores por si hay bloqueo de IO
        }
    }

    $response = [
        'status' => 'success', 
        'rewardGiven' => $darRecompensa,
        'message' => $darRecompensa ? '¡Felicidades! Ganaste $35 MXN.' : 'Datos actualizados con éxito.',
        'newImage' => $nombreArchivoFinal 
    ];

} catch (Throwable $e) { 
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

ob_clean();
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
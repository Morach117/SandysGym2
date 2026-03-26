<?php
// api/update_profile_reward.php

// Evitamos que los errores nativos rompan la respuesta JSON
ini_set('display_errors', 0); 
error_reporting(E_ALL);

// Aumentamos la memoria para que soporte procesar fotos de celulares modernos (12MP - 48MP)
ini_set('memory_limit', '256M'); 

header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    // ---------------------------------------------------------
    // PROCESAR FOTO Y CONVERTIR A JPG
    // ---------------------------------------------------------
    $nombreArchivoFinal = null;

    // Verificamos si se envió un archivo de foto
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['name'] !== '') {
        
        $uploadError = $_FILES['foto_perfil']['error'];
        
        // Si hay error al subir, lanzamos la excepción en lugar de ignorarlo
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

        $directorioDestino = __DIR__ . '/../../imagenes/avatar/';
        if (!is_dir($directorioDestino)) mkdir($directorioDestino, 0777, true);

        $fileTmpPath = $_FILES['foto_perfil']['tmp_name'];
        $imageInfo = getimagesize($fileTmpPath);
        
        if ($imageInfo === false) {
            throw new Exception("El archivo seleccionado no es una imagen válida.");
        }

        $mimeType = $imageInfo['mime'];
        
        // Crear el recurso de imagen según el tipo original
        switch ($mimeType) {
            case 'image/jpeg': $imgRes = imagecreatefromjpeg($fileTmpPath); break;
            case 'image/png':  $imgRes = imagecreatefrompng($fileTmpPath);  break;
            case 'image/webp': $imgRes = imagecreatefromwebp($fileTmpPath); break;
            default:
                throw new Exception("Formato no soportado. Por favor, sube una imagen en formato JPG o PNG.");
        }

        if ($imgRes) {
            // Nombre final siempre con extensión .jpg
            $nombreArchivoFinal = $idSocio . ".jpg";
            $rutaCompleta = $directorioDestino . $nombreArchivoFinal;

            // CONVERSIÓN: Guardar como JPG con calidad del 80% (Comprime el tamaño físico)
            imagejpeg($imgRes, $rutaCompleta, 80);
            imagedestroy($imgRes); // Liberar memoria
        } else {
            throw new Exception("Error al intentar procesar los colores de la imagen.");
        }
    }

    // ---------------------------------------------------------
    // LÓGICA DE RECOMPENSA
    // ---------------------------------------------------------
    $estanTodosLosDatos = (
        !empty($nombres) && !empty($apPaterno) && !empty($genero) &&
        !empty($mesNac) && !empty($telCel) && !empty($direccion) && 
        !empty($emerNombres) && !empty($emerTel) && !empty($emerParentesco)
    );

    $checkQuery = "SELECT soc_mon_saldo, perfil_completado_reward, soc_tel_cel, soc_fecha_nacimiento FROM san_socios WHERE soc_id_socio = :id";
    $stmtCheck = $conn->prepare($checkQuery);
    $stmtCheck->bindParam(':id', $idSocio);
    $stmtCheck->execute();
    $datosActuales = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$datosActuales) throw new Exception("Socio no encontrado.");

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

    echo json_encode([
        'status' => 'success', 
        'rewardGiven' => $darRecompensa,
        'message' => $darRecompensa ? '¡Felicidades! Ganaste $35 MXN.' : 'Datos actualizados con éxito.'
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
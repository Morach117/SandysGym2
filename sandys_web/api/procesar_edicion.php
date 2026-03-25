<?php
// Incluir el archivo de conexión a la base de datos
include '../conn.php';

// MEJORA: Definir el tipo de contenido al principio.
header('Content-Type: application/json');

// MEJORA: Una función helper para enviar respuestas JSON limpias y salir.
function json_response($status, $message, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// 1. VERIFICAR MÉTODO DE SOLICITUD
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response('error', 'Error: Método de solicitud no válido.', 405);
}

// 2. VALIDAR DATOS OBLIGATORIOS
if (empty($_POST["id_socio"]) || empty($_POST["nombres"]) || empty($_POST["ap_paterno"])) {
    json_response('error', 'Error: Faltan datos obligatorios (ID, Nombres, Apellido Paterno).', 400);
}

// 3. OBTENER Y SANITIZAR TODAS LAS ENTRADAS
try {
    $idSocio = $_POST["id_socio"]; 

    // Campos de texto (Sanitizados contra XSS)
    $nombres        = htmlspecialchars($_POST["nombres"] ?? '', ENT_QUOTES, 'UTF-8');
    $apePaterno     = htmlspecialchars($_POST["ap_paterno"] ?? '', ENT_QUOTES, 'UTF-8');
    $apeMaterno     = !empty($_POST["ap_materno"]) ? htmlspecialchars($_POST["ap_materno"], ENT_QUOTES, 'UTF-8') : null;
    $direccion      = !empty($_POST["direccion"]) ? htmlspecialchars($_POST["direccion"], ENT_QUOTES, 'UTF-8') : null;
    $colonia        = !empty($_POST["colonia"]) ? htmlspecialchars($_POST["colonia"], ENT_QUOTES, 'UTF-8') : null;
    $telFijo        = !empty($_POST["tel_fijo"]) ? htmlspecialchars($_POST["tel_fijo"], ENT_QUOTES, 'UTF-8') : null;
    $telCel         = !empty($_POST["tel_cel"]) ? htmlspecialchars($_POST["tel_cel"], ENT_QUOTES, 'UTF-8') : null;
    $emerNombres    = !empty($_POST["emer_nombres"]) ? htmlspecialchars($_POST["emer_nombres"], ENT_QUOTES, 'UTF-8') : null;
    $emerParentesco = !empty($_POST["emer_parentesco"]) ? htmlspecialchars($_POST["emer_parentesco"], ENT_QUOTES, 'UTF-8') : null;
    $emerDireccion  = !empty($_POST["emer_direccion"]) ? htmlspecialchars($_POST["emer_direccion"], ENT_QUOTES, 'UTF-8') : null;
    $emerTel        = !empty($_POST["emer_tel"]) ? htmlspecialchars($_POST["emer_tel"], ENT_QUOTES, 'UTF-8') : null;
    $observaciones  = !empty($_POST["observaciones"]) ? htmlspecialchars($_POST["observaciones"], ENT_QUOTES, 'UTF-8') : null;

    $genero = $_POST["genero"] ?? null; 
    $turno  = $_POST["turno"] ?? null;
    $fechaNacimiento = !empty($_POST["fecha_nacimiento"]) ? $_POST["fecha_nacimiento"] : null;

} catch (Exception $e) {
    json_response('error', 'Error al procesar los datos de entrada.', 500);
}

// ---------------------------------------------------------
// PROCESAR FOTO / SELFIE Y GUARDARLA EN LA CARPETA
// ---------------------------------------------------------
$rutaFoto = null;
if (isset($_FILES['foto_perfil'])) {
    $errorUpload = $_FILES['foto_perfil']['error'];

    // Solo procesamos si realmente se envió un archivo (UPLOAD_ERR_OK = 0)
    if ($errorUpload === UPLOAD_ERR_OK) {
        
        // RUTA RELATIVA: Subimos dos niveles desde 'sandys_web/query/' hasta 'gym/' y entramos a 'imagenes/avatar/'
        $directorioDestino = '../../imagenes/avatar/';
        
        // Verificamos si la carpeta existe. Si no, intentamos crearla.
        if (!is_dir($directorioDestino)) {
            if (!mkdir($directorioDestino, 0777, true)) {
                // Si falla la creación de la carpeta, arrojamos un error y detenemos todo.
                json_response('error', 'Error del Servidor: No se pudo crear el directorio de avatares en: ' . $directorioDestino, 500);
            }
        }

        $extension = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
        $extensionesValidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($extension, $extensionesValidas)) {
            $nombreArchivo = 'perfil_' . $idSocio . '_' . time() . '.' . $extension;
            $rutaCompleta = $directorioDestino . $nombreArchivo;

            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $rutaCompleta)) {
                // Ruta para la Base de Datos. Como los archivos HTML suelen cargar desde 'gym/sandys_web/',
                // subir un solo nivel ('../') suele ser suficiente para acceder a 'imagenes/avatar/'.
                $rutaFoto = '../imagenes/avatar/' . $nombreArchivo; 
            } else {
                json_response('error', "Error del Servidor: No se pudo mover la imagen a la ruta: $rutaCompleta", 500);
            }
        } else {
            json_response('error', 'Formato de imagen no válido. Usa JPG, PNG o WEBP.', 400);
        }
    } elseif ($errorUpload !== UPLOAD_ERR_NO_FILE) {
        // Detener y avisar si hubo un error de límite de peso
        $errores = [
            1 => 'La foto pesa demasiado (límite del servidor PHP php.ini).',
            2 => 'La foto pesa demasiado (límite del formulario HTML).',
            3 => 'La subida se interrumpió a la mitad.',
            6 => 'Falta carpeta temporal de subida en el servidor.',
            7 => 'Error de escritura en disco al intentar guardar temporalmente.'
        ];
        $msj = $errores[$errorUpload] ?? 'Error desconocido al subir la imagen (Código: ' . $errorUpload . ').';
        json_response('error', $msj, 400);
    }
}

// 4. REALIZAR LA ACTUALIZACIÓN EN LA BASE DE DATOS
try {
    // Armamos la consulta base
    $sql = "UPDATE san_socios SET 
            soc_nombres = :nombres, soc_apepat = :ape_paterno, soc_apemat = :ape_materno,
            soc_genero = :genero, soc_turno = :turno, soc_direccion = :direccion, 
            soc_colonia = :colonia, soc_tel_fijo = :tel_fijo, soc_tel_cel = :tel_cel, 
            soc_fecha_nacimiento = :fecha_nacimiento, soc_emer_nombres = :emer_nombres,
            soc_emer_parentesco = :emer_parentesco, soc_emer_direccion = :emer_direccion,
            soc_emer_tel = :emer_tel, soc_observaciones = :observaciones";

    // Si se subió la foto con éxito, agregamos el campo al UPDATE
    if ($rutaFoto) {
        $sql .= ", soc_imagen = :foto";
    }

    $sql .= " WHERE soc_id_socio = :id_socio";

    $stmt = $conn->prepare($sql);
    
    // Tus bindParam originales
    $stmt->bindParam(':nombres', $nombres);
    $stmt->bindParam(':ape_paterno', $apePaterno);
    $stmt->bindParam(':ape_materno', $apeMaterno, $apeMaterno === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':genero', $genero, $genero === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':turno', $turno, $turno === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':direccion', $direccion, $direccion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':colonia', $colonia, $colonia === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':tel_fijo', $telFijo, $telFijo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':tel_cel', $telCel, $telCel === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':fecha_nacimiento', $fechaNacimiento, $fechaNacimiento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':emer_nombres', $emerNombres, $emerNombres === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':emer_parentesco', $emerParentesco, $emerParentesco === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':emer_direccion', $emerDireccion, $emerDireccion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':emer_tel', $emerTel, $emerTel === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':observaciones', $observaciones, $observaciones === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':id_socio', $idSocio, PDO::PARAM_INT);
    
    // Vinculamos la foto si es necesario
    if ($rutaFoto) {
        $stmt->bindParam(':foto', $rutaFoto);
    }
    
    $stmt->execute();

    json_response('success', 'La información del socio se ha actualizado correctamente.');

} catch (PDOException $e) {
    error_log('Error en actualizacion de socio: ' . $e->getMessage()); 
    json_response('error', 'Error al actualizar la información. Contacte al administrador.', 500);
}
?>
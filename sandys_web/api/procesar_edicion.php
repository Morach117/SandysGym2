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
// MEJORA: Validar que los campos clave no estén vacíos.
if (empty($_POST["id_socio"]) || empty($_POST["nombres"]) || empty($_POST["ap_paterno"])) {
    json_response('error', 'Error: Faltan datos obligatorios (ID, Nombres, Apellido Paterno).', 400);
}

// 3. OBTENER Y SANITIZAR TODAS LAS ENTRADAS
// MEJORA (Seguridad XSS): Sanitizamos *toda* la información de texto que 
// viene del usuario usando htmlspecialchars() antes de guardarla.
try {
    $idSocio = $_POST["id_socio"]; // El ID no necesita sanitización si es un número, pero se valida en el WHERE.

    // Campos de texto (Sanitizados contra XSS)
    $nombres      = htmlspecialchars($_POST["nombres"] ?? '', ENT_QUOTES, 'UTF-8');
    $apePaterno   = htmlspecialchars($_POST["ap_paterno"] ?? '', ENT_QUOTES, 'UTF-8');
    $apeMaterno   = !empty($_POST["ap_materno"]) ? htmlspecialchars($_POST["ap_materno"], ENT_QUOTES, 'UTF-8') : null;
    $direccion    = !empty($_POST["direccion"]) ? htmlspecialchars($_POST["direccion"], ENT_QUOTES, 'UTF-8') : null;
    $colonia      = !empty($_POST["colonia"]) ? htmlspecialchars($_POST["colonia"], ENT_QUOTES, 'UTF-8') : null;
    $telFijo      = !empty($_POST["tel_fijo"]) ? htmlspecialchars($_POST["tel_fijo"], ENT_QUOTES, 'UTF-8') : null;
    $telCel       = !empty($_POST["tel_cel"]) ? htmlspecialchars($_POST["tel_cel"], ENT_QUOTES, 'UTF-8') : null;
    $emerNombres  = !empty($_POST["emer_nombres"]) ? htmlspecialchars($_POST["emer_nombres"], ENT_QUOTES, 'UTF-8') : null;
    $emerParentesco = !empty($_POST["emer_parentesco"]) ? htmlspecialchars($_POST["emer_parentesco"], ENT_QUOTES, 'UTF-8') : null;
    $emerDireccion  = !empty($_POST["emer_direccion"]) ? htmlspecialchars($_POST["emer_direccion"], ENT_QUOTES, 'UTF-8') : null;
    $emerTel      = !empty($_POST["emer_tel"]) ? htmlspecialchars($_POST["emer_tel"], ENT_QUOTES, 'UTF-8') : null;
    $observaciones  = !empty($_POST["observaciones"]) ? htmlspecialchars($_POST["observaciones"], ENT_QUOTES, 'UTF-8') : null;

    // Campos con valores específicos (no necesitan sanitización de HTML, solo validación)
    $genero = $_POST["genero"] ?? null; // Asumimos que viene de un <select> o <radio>
    $turno  = $_POST["turno"] ?? null;

    // Campos de fecha (se deben validar, pero `bindParam` los trata como strings)
    $fechaNacimiento = !empty($_POST["fecha_nacimiento"]) ? $_POST["fecha_nacimiento"] : null;
    // (Idealmente, aquí validarías que $fechaNacimiento sea una fecha válida)

} catch (Exception $e) {
    json_response('error', 'Error al procesar los datos de entrada.', 500);
}

// 4. REALIZAR LA ACTUALIZACIÓN EN LA BASE DE DATOS
try {
    // Tu consulta preparada (¡esto ya es seguro contra SQLi!)
    $stmt = $conn->prepare("UPDATE san_socios SET 
        soc_nombres = :nombres, soc_apepat = :ape_paterno, soc_apemat = :ape_materno,
        soc_genero = :genero, soc_turno = :turno, soc_direccion = :direccion, 
        soc_colonia = :colonia, soc_tel_fijo = :tel_fijo, soc_tel_cel = :tel_cel, 
        soc_fecha_nacimiento = :fecha_nacimiento, soc_emer_nombres = :emer_nombres,
        soc_emer_parentesco = :emer_parentesco, soc_emer_direccion = :emer_direccion,
        soc_emer_tel = :emer_tel, soc_observaciones = :observaciones
        WHERE soc_id_socio = :id_socio");
    
    // Tus bindParam (¡esto ya es seguro!)
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
    
    $stmt->execute();

    // MEJORA: Usar la función helper para la respuesta de éxito
    json_response('success', 'La información del socio se ha actualizado correctamente.');

} catch (PDOException $e) {
    // MEJORA (Seguridad): No expongas el error de la BD al usuario.
    // Regístralo en tu log de servidor y envía un mensaje genérico.
    error_log('Error en actualizacion de socio: ' . $e->getMessage()); // Esto se guarda en el log de errores de PHP/Apache
    
    json_response('error', 'Error al actualizar la información. Contacte al administrador.', 500);
}
?>
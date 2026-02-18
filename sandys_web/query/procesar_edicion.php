<?php
// Incluir el archivo de conexión a la base de datos
include '../conn.php';

// Array para almacenar la respuesta
$response = array();

// Verificar si se recibieron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar si se recibió el ID del socio
    if (isset($_POST["id_socio"])) {
        
        // --- INICIO DE LA CORRECCIÓN ---

        // Obtener y sanitizar los datos del formulario de forma segura
        $idSocio            = $_POST["id_socio"];
        $nombres            = $_POST["nombres"] ?? ''; // Asumimos que estos son obligatorios
        $apePaterno         = $_POST["ap_paterno"] ?? '';
        
        // Usamos el operador de fusión de nulos (??) para todos los campos opcionales.
        // Si el campo no llega, se le asigna NULL.
        $apeMaterno         = $_POST["ap_materno"] ?? null;
        $genero             = $_POST["genero"] ?? null;
        $turno              = $_POST["turno"] ?? null; // <-- ESTA LÍNEA CORRIGE TU ERROR
        $direccion          = $_POST["direccion"] ?? null;
        $colonia            = $_POST["colonia"] ?? null;
        $telFijo            = $_POST["tel_fijo"] ?? null;
        $telCel             = $_POST["tel_cel"] ?? null;
        $fechaNacimiento    = $_POST["fecha_nacimiento"] ?? null;
        $emerNombres        = $_POST["emer_nombres"] ?? null;
        $emerParentesco     = $_POST["emer_parentesco"] ?? null;
        $emerDireccion      = $_POST["emer_direccion"] ?? null;
        $emerTel            = $_POST["emer_tel"] ?? null;
        $observaciones      = $_POST["observaciones"] ?? null;
        
        // El correo no debe cambiar, lo leemos de la base de datos o lo recibimos como readonly
        // No es necesario actualizarlo si el campo en el form es readonly.

        // --- FIN DE LA CORRECCIÓN ---

        // Realizar la actualización en la base de datos
        try {
            // La consulta SQL se mantiene igual
            $stmt = $conn->prepare("UPDATE san_socios SET 
                soc_nombres = :nombres, soc_apepat = :ape_paterno, soc_apemat = :ape_materno,
                soc_genero = :genero, soc_turno = :turno, soc_direccion = :direccion, 
                soc_colonia = :colonia, soc_tel_fijo = :tel_fijo, soc_tel_cel = :tel_cel, 
                soc_fecha_nacimiento = :fecha_nacimiento, soc_emer_nombres = :emer_nombres,
                soc_emer_parentesco = :emer_parentesco, soc_emer_direccion = :emer_direccion,
                soc_emer_tel = :emer_tel, soc_observaciones = :observaciones
                WHERE soc_id_socio = :id_socio");
            
            // Los bindParam se mantienen iguales
            $stmt->bindParam(':nombres', $nombres);
            $stmt->bindParam(':ape_paterno', $apePaterno);
            $stmt->bindParam(':ape_materno', $apeMaterno);
            $stmt->bindParam(':genero', $genero);
            $stmt->bindParam(':turno', $turno);
            $stmt->bindParam(':direccion', $direccion);
            $stmt->bindParam(':colonia', $colonia);
            $stmt->bindParam(':tel_fijo', $telFijo);
            $stmt->bindParam(':tel_cel', $telCel);
            $stmt->bindParam(':fecha_nacimiento', $fechaNacimiento);
            $stmt->bindParam(':emer_nombres', $emerNombres);
            $stmt->bindParam(':emer_parentesco', $emerParentesco);
            $stmt->bindParam(':emer_direccion', $emerDireccion);
            $stmt->bindParam(':emer_tel', $emerTel);
            $stmt->bindParam(':observaciones', $observaciones);
            $stmt->bindParam(':id_socio', $idSocio);
            
            $stmt->execute();

            $response['status'] = 'success';
            $response['message'] = 'La información del socio se ha actualizado correctamente.';

        } catch (PDOException $e) {
            $response['status'] = 'error';
            $response['message'] = 'Error al actualizar la información: ' . $e->getMessage();
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Error: No se recibió el ID del socio.';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Error: Método de solicitud no válido.';
}

// Devolver la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
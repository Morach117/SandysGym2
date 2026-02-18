<?php
include "../../funciones_globales/funciones_conexion.php";

$conn = obtener_conexion();

if ($conn) {
    // Sanear la entrada para evitar SQL Injection
    $telefono = isset($_GET['telefono']) ? mysqli_real_escape_string($conn, $_GET['telefono']) : null;
    $id_socio = isset($_GET['id_socio']) ? $_GET['id_socio'] : null;


    // Determinar si la entrada es un teléfono o un ID válido
    $id_valido = false;
    $valor_a_buscar = null;
    $sql = null;

    // Validar si el valor ingresado es un número de teléfono de 10 dígitos o un ID
    if (preg_match("/^\d{10}$/", $telefono)) {
        // Es un número de teléfono válido
        $sql = "SELECT COUNT(*) as existe FROM san_socios WHERE soc_tel_cel = ?";
    } elseif (preg_match("/^\d+$/", $telefono)) {
        // Es un ID válido (solo números)
        $sql = "SELECT COUNT(*) as existe FROM san_socios WHERE soc_id_socio = ?";
    } else {
        // Si no es un número de teléfono ni un ID válido, retornar error
        echo json_encode(["error" => "El número de teléfono o ID es inválido"]);
        exit();
    }

    // Preparar y ejecutar la consulta
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $telefono); // Usar el valor $telefono
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    // Verificar si la consulta devolvió resultados
    if (!$row || $row['existe'] == 0) {
        echo json_encode([
            "existe" => false,
            "referido" => false,
            "alerta" => "El usuario no está registrado."
        ]);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        exit;
    }

    // Extraer el valor encontrado

    mysqli_stmt_close($stmt);

    // Verificar si el usuario está en san_referidos y activo
    $existe_referido = false;
    $sql_referido = "SELECT COUNT(*) as registrado FROM san_referidos WHERE id_socio = ? AND estado = 'activo'";
    $stmt_referido = mysqli_prepare($conn, $sql_referido);
    mysqli_stmt_bind_param($stmt_referido, "s", $id_socio);
    mysqli_stmt_execute($stmt_referido);
    $result_referido = mysqli_stmt_get_result($stmt_referido);
    $row_referido = mysqli_fetch_assoc($result_referido);
    $existe_referido = $row_referido && $row_referido['registrado'] > 0;
    mysqli_stmt_close($stmt_referido);

    mysqli_close($conn);

    // Retornar la respuesta JSON
    echo json_encode([
        "existe" => true,
        "id_socio" => $id_socio,
        "referido" => $existe_referido,
        "alerta" => $existe_referido ? "El usuario ya ha sido registrado como referido." : ""
    ]);
} else {
    echo json_encode(["error" => "Error en la conexión"]);
}

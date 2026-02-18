<?php
include "../../funciones_globales/funciones_conexion.php";

$conn = obtener_conexion();

if ($conn) {
    // Sanear la entrada para evitar SQL Injection
    $query = mysqli_real_escape_string($conn, $_GET['q']);
    
    // Consulta mejorada para permitir coincidencias en cualquier orden
    $sql = "SELECT soc_id_socio, CONCAT(soc_nombres, ' ', soc_apepat, ' ', soc_apemat) AS nombre
            FROM san_socios 
            WHERE soc_id_empresa = 1 
            AND (
                CONCAT(soc_nombres, ' ', soc_apepat, ' ', soc_apemat) LIKE ? OR
                CONCAT(soc_apepat, ' ', soc_nombres, ' ', soc_apemat) LIKE ? OR
                CONCAT(soc_apepat, ' ', soc_apemat, ' ', soc_nombres) LIKE ?
            )";
    
    // Preparar la consulta
    $stmt = mysqli_prepare($conn, $sql);
    
    // Definir el valor de la variable de búsqueda, que es el texto con el comodín '%'
    $searchQuery = "%" . $query . "%";
    
    // Vincular los parámetros
    mysqli_stmt_bind_param($stmt, "sss", $searchQuery, $searchQuery, $searchQuery);

    // Ejecutar la consulta
    mysqli_stmt_execute($stmt);
    
    // Obtener el resultado
    $result = mysqli_stmt_get_result($stmt);

    $socios = array();
    
    // Si hay resultados, agregarlos al array
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $socios[] = $row;
        }
    }

    // Retornar los datos en formato JSON
    echo json_encode($socios);
    
    // Cerrar la conexión
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
} else {
    echo json_encode(array());
}
?>

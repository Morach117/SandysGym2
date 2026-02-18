<?php
// Es una buena práctica iniciar la sesión por si necesitas datos del usuario, como el id_empresa.
// Si no usas sesiones, puedes ignorar esta línea.
session_start();

require_once '../../funciones_globales/funciones_conexion.php';

// 1. LLAMAMOS A LA FUNCIÓN para obtener la conexión.
$conexion = obtener_conexion();

// 2. Definimos el ID de la empresa.
// Lo ideal es que venga de una variable de sesión después de que el usuario inicie sesión.
// Si no, puedes poner un valor fijo para probar. ¡Recuerda ajustar esto!
$id_empresa = isset($_SESSION['id_empresa']) ? $_SESSION['id_empresa'] : 1;


// 3. Verificación de seguridad: si la conexión falló, termina el script.
if (!$conexion) {
    http_response_code(500);
    header('Content-Type: application/json');
    // Se añade el error específico de MySQL para facilitar la depuración
    echo json_encode(['error' => 'No se pudo establecer la conexión con la base de datos.', 'detalle' => mysqli_connect_error()]);
    exit;
}


// Parámetros que envía DataTables
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue = isset($_POST['search']['value']) ? mysqli_real_escape_string($conexion, $_POST['search']['value']) : '';

// Columnas para el ordenamiento
$orderColumnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 1;
$orderColumnName = isset($_POST['columns'][$orderColumnIndex]['data']) ? mysqli_real_escape_string($conexion, $_POST['columns'][$orderColumnIndex]['data']) : 'socio';
$orderDir = isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) == 'desc' ? 'DESC' : 'ASC';


// ===================================================================
// AQUÍ ESTÁ LA CORRECCIÓN DEFINITIVA
// ===================================================================
// Medida de seguridad: Si el navegador pide ordenar por "contador" (que no existe en la base de datos),
// forzamos el ordenamiento a una columna que sí existe, como "socio".
if ($orderColumnName == 'contador') {
    $orderColumnName = 'socio';
}
// ===================================================================


$orderBy = "ORDER BY $orderColumnName $orderDir";

// Construcción de la condición de búsqueda
$condicion = "";
if (!empty($searchValue)) {
    $condicion = " AND (LOWER(CONCAT(soc_apepat, ' ', soc_apemat, ' ', soc_nombres)) LIKE LOWER('%$searchValue%'))";
}

// Query para el total de registros FILTRADOS
$query_filtrados = "SELECT COUNT(DISTINCT soc_id_socio) AS total
                    FROM san_socios
                    WHERE soc_id_empresa = $id_empresa $condicion";
$res_filtrados = mysqli_query($conexion, $query_filtrados);
$row_filtrados = mysqli_fetch_assoc($res_filtrados);
$recordsFiltered = $row_filtrados ? $row_filtrados['total'] : 0;

// Query para el total de registros SIN FILTRAR
$query_total = "SELECT COUNT(DISTINCT soc_id_socio) AS total
                FROM san_socios
                WHERE soc_id_empresa = $id_empresa";
$res_total = mysqli_query($conexion, $query_total);
$row_total = mysqli_fetch_assoc($res_total);
$recordsTotal = $row_total ? $row_total['total'] : 0;


// Query principal para obtener los datos de la página actual
$query_data = "SELECT 
                    soc_id_socio AS id_socio,
                    CONCAT(soc_apepat, ' ', soc_apemat, ' ', soc_nombres) AS socio,
                    ROUND(IFNULL(soc_mon_saldo, 0), 2) AS saldo
               FROM san_socios
               WHERE soc_id_empresa = $id_empresa
               $condicion
               GROUP BY soc_id_socio
               $orderBy
               LIMIT $start, $length";

$resultado = mysqli_query($conexion, $query_data);

$data = [];
$contador = $start + 1;
if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        // Formateamos el saldo para que siempre tenga el signo de pesos
        $fila['saldo'] = '$' . number_format($fila['saldo'], 2);
        
        // Agregamos el contador
        $fila['contador'] = $contador;

        $data[] = $fila;
        $contador++;
    }
}

// Estructura de la respuesta JSON que DataTables espera
$response = [
    "draw" => $draw,
    "recordsTotal" => intval($recordsTotal),
    "recordsFiltered" => intval($recordsFiltered),
    "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response);
?>
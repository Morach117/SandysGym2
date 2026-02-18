<?php
// api_prepago_detalle.php

// Incluir los archivos necesarios para la conexión a la BD y funciones
session_start();
require_once '../../funciones_globales/funciones_conexion.php';
require_once '../../funciones_globales/funciones_comunes.php';

// Obtener conexión
$conexion = obtener_conexion();
if (!$conexion) {
    // Si falla la conexión, devuelve un error en formato JSON
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión a la base de datos."]);
    exit;
}

// Obtener el ID del socio desde la petición AJAX de DataTables
$id_socio = isset($_POST['id_socio']) ? intval($_POST['id_socio']) : 0;

// --- LÓGICA DE DATATABLES ---

// Parámetros de DataTables
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue = isset($_POST['search']['value']) ? mysqli_real_escape_string($conexion, $_POST['search']['value']) : '';

// Ordenamiento
$orderColumnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$orderColumnName = isset($_POST['columns'][$orderColumnIndex]['data']) ? mysqli_real_escape_string($conexion, $_POST['columns'][$orderColumnIndex]['data']) : 'id_pdetalle';
$orderDir = isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) == 'asc' ? 'ASC' : 'DESC';

// Búsqueda
$condicionBusqueda = '';
if (!empty($searchValue)) {
    $condicionBusqueda = " AND pred_descripcion LIKE '%$searchValue%'";
}

// --- QUERIES ---

// 1. Total de registros sin filtrar
$query_total = "SELECT COUNT(pred_id_pdetalle) AS total FROM san_prepago_detalle WHERE pred_id_socio = $id_socio";
$res_total = mysqli_query($conexion, $query_total);
$recordsTotal = $res_total ? mysqli_fetch_assoc($res_total)['total'] : 0;

// 2. Total de registros con el filtro aplicado
$query_filtrado = "SELECT COUNT(pred_id_pdetalle) AS total FROM san_prepago_detalle WHERE pred_id_socio = $id_socio $condicionBusqueda";
$res_filtrado = mysqli_query($conexion, $query_filtrado);
$recordsFiltered = $res_filtrado ? mysqli_fetch_assoc($res_filtrado)['total'] : 0;

// 3. Datos para la página actual
$query_data = "SELECT
                   pred_id_pdetalle AS id_pdetalle,
                   pred_descripcion AS p_descripcion,
                   ROUND(pred_importe, 2) AS importe,
                   ROUND(pred_saldo, 2) AS saldo,
                   CASE pred_movimiento WHEN 'R' THEN 'Resta' WHEN 'S' THEN 'Suma' END AS movimiento,
                   DATE_FORMAT(pred_fecha, '%d-%m-%Y') AS fecha,
                   LOWER(DATE_FORMAT(pred_fecha, '%r')) AS hora
               FROM san_prepago_detalle
               WHERE pred_id_socio = $id_socio $condicionBusqueda
               ORDER BY $orderColumnName $orderDir
               LIMIT $start, $length";

$resultado_data = mysqli_query($conexion, $query_data);
$data = [];
if ($resultado_data) {
    while ($fila = mysqli_fetch_assoc($resultado_data)) {
        // Formateamos los datos antes de enviarlos
        $fila['importe'] = '$' . number_format($fila['importe'], 2);
        $fila['saldo'] = '$' . number_format($fila['saldo'], 2);
        $data[] = $fila;
    }
}

// --- RESPUESTA JSON ---

// Preparamos la respuesta final que DataTables espera
$response = [
    "draw" => $draw,
    "recordsTotal" => intval($recordsTotal),
    "recordsFiltered" => intval($recordsFiltered),
    "data" => $data
];

// Enviamos la respuesta
header('Content-Type: application/json');
echo json_encode($response);
?>
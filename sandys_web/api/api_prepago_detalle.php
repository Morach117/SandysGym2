<?php
// api_prepago_detalle.php

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Incluir conexión PDO usando tu estándar
require_once '../conn.php'; 

// Validar que la conexión PDO exista
if (!isset($conn) || !$conn instanceof PDO) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión a la base de datos."]);
    exit;
}

// Obtener el ID del socio desde la petición AJAX
$id_socio = isset($_POST['id_socio']) ? intval($_POST['id_socio']) : 0;

// --- PARÁMETROS DE DATATABLES ---
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue = $_POST['search']['value'] ?? '';

// Validar columna de ordenamiento para evitar inyección en el ORDER BY
$columnasValidas = ['id_pdetalle', 'p_descripcion', 'importe', 'saldo', 'movimiento', 'fecha', 'hora'];
$orderColumnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$orderColumnName = $columnasValidas[$orderColumnIndex] ?? 'id_pdetalle';
$orderDir = (isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';

try {
    // 1. Total de registros sin filtrar
    $stmtTotal = $conn->prepare("SELECT COUNT(pred_id_pdetalle) FROM san_prepago_detalle WHERE pred_id_socio = :id_socio");
    $stmtTotal->bindParam(':id_socio', $id_socio, PDO::PARAM_INT);
    $stmtTotal->execute();
    $recordsTotal = $stmtTotal->fetchColumn();

    // 2. Total de registros con el filtro aplicado
    $searchCondition = "";
    if (!empty($searchValue)) {
        $searchCondition = " AND pred_descripcion LIKE :search";
    }

    $stmtFiltrado = $conn->prepare("SELECT COUNT(pred_id_pdetalle) FROM san_prepago_detalle WHERE pred_id_socio = :id_socio" . $searchCondition);
    $stmtFiltrado->bindParam(':id_socio', $id_socio, PDO::PARAM_INT);
    if (!empty($searchValue)) {
        $searchParam = "%$searchValue%";
        $stmtFiltrado->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    $stmtFiltrado->execute();
    $recordsFiltered = $stmtFiltrado->fetchColumn();

    // 3. Datos para la página actual
    $query_data = "SELECT
                    pred_id_pdetalle AS id_pdetalle,
                    pred_descripcion AS p_descripcion,
                    ROUND(pred_importe, 2) AS importe,
                    ROUND(pred_saldo, 2) AS saldo,
                    CASE pred_movimiento WHEN 'R' THEN 'Resta' WHEN 'S' THEN 'Suma' ELSE pred_movimiento END AS movimiento,
                    DATE_FORMAT(pred_fecha, '%d-%m-%Y') AS fecha,
                    LOWER(DATE_FORMAT(pred_fecha, '%r')) AS hora
                   FROM san_prepago_detalle
                   WHERE pred_id_socio = :id_socio $searchCondition
                   ORDER BY $orderColumnName $orderDir
                   LIMIT :start, :length";

    $stmtData = $conn->prepare($query_data);
    $stmtData->bindParam(':id_socio', $id_socio, PDO::PARAM_INT);
    
    if (!empty($searchValue)) {
        $stmtData->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    
    // PDO requiere que LIMIT use enteros específicamente
    $stmtData->bindValue(':start', $start, PDO::PARAM_INT);
    $stmtData->bindValue(':length', $length, PDO::PARAM_INT);
    
    $stmtData->execute();
    $resultados = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    // Formatear la moneda
    $data = [];
    foreach ($resultados as $fila) {
        $fila['importe'] = '$' . number_format($fila['importe'], 2);
        $fila['saldo'] = '$' . number_format($fila['saldo'], 2);
        $data[] = $fila;
    }

    // 4. Enviar Respuesta JSON a DataTables
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => intval($recordsTotal),
        "recordsFiltered" => intval($recordsFiltered),
        "data" => $data
    ]);

} catch (PDOException $e) {
    // Manejo de errores seguro
    error_log("Error en DataTables Monedero: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Error al consultar los datos."]);
}
?>
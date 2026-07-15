<?php
// api/api_prepago_detalle.php

// 1. Asegurar que no se imprima ningún espacio en blanco, warning o error de PHP antes del JSON
ob_start();
header('Content-Type: application/json; charset=utf-8');

// Iniciar la sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir conexión PDO
require_once __DIR__ . '/../conn.php';

// Función para vaciar el buffer e imprimir la respuesta JSON
function responder_json(array $respuesta) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($respuesta);
    exit;
}

// 2. Valida la sesión activa
$session_id_socio = $_SESSION['admin']['soc_id_socio'] ?? null;
$post_id_socio = isset($_POST['id_socio']) ? (int)$_POST['id_socio'] : null;

// Obtener token CSRF de la petición (POST o cabeceras HTTP)
$post_csrf_token = $_POST['csrf_token'] ?? null;
if (empty($post_csrf_token)) {
    // Buscar en cabeceras HTTP
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['X-CSRF-Token'])) {
            $post_csrf_token = $headers['X-CSRF-Token'];
        }
    }
    if (empty($post_csrf_token) && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $post_csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
}

$session_csrf_token = $_SESSION['csrf_token'] ?? null;

// Verificar sesión y CSRF token
if (
    empty($session_id_socio) || 
    empty($post_id_socio) || 
    (int)$post_id_socio !== (int)$session_id_socio ||
    empty($session_csrf_token) || 
    empty($post_csrf_token) || 
    !hash_equals($session_csrf_token, (string)$post_csrf_token)
) {
    responder_json([
        "draw" => 0, 
        "recordsTotal" => 0, 
        "recordsFiltered" => 0, 
        "data" => [], 
        "error" => "Error de sesión o seguridad."
    ]);
}

// Verificar conexión PDO
if (!isset($conn) || !$conn instanceof PDO) {
    responder_json([
        "draw" => 0, 
        "recordsTotal" => 0, 
        "recordsFiltered" => 0, 
        "data" => [], 
        "error" => "Error crítico: No se pudo conectar a la base de datos."
    ]);
}

// 3. Captura los parámetros estándar de DataTables
$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 0;
$start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
$searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

// Validar ordenamiento para evitar inyecciones SQL en ORDER BY
$columnasValidas = [
    0 => 'pred_id_pdetalle',
    1 => 'pred_descripcion',
    2 => 'pred_importe',
    3 => 'pred_saldo',
    4 => 'pred_movimiento',
    5 => 'pred_fecha',
    6 => 'pred_fecha'
];

$orderColumnIndex = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
$orderColumnName = $columnasValidas[$orderColumnIndex] ?? 'pred_id_pdetalle';
$orderDir = (isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';

try {
    // A. Consultar total de registros sin filtrar
    $stmtTotal = $conn->prepare("SELECT COUNT(pred_id_pdetalle) FROM san_prepago_detalle WHERE pred_id_socio = :id_socio");
    $stmtTotal->bindValue(':id_socio', $post_id_socio, PDO::PARAM_INT);
    $stmtTotal->execute();
    $recordsTotal = (int)$stmtTotal->fetchColumn();

    // B. Construir filtro de búsqueda
    $searchCondition = "";
    if ($searchValue !== "") {
        $searchCondition = " AND (pred_descripcion LIKE :search OR pred_movimiento LIKE :search)";
    }

    // C. Consultar total de registros filtrados
    $stmtFiltrado = $conn->prepare("SELECT COUNT(pred_id_pdetalle) FROM san_prepago_detalle WHERE pred_id_socio = :id_socio" . $searchCondition);
    $stmtFiltrado->bindValue(':id_socio', $post_id_socio, PDO::PARAM_INT);
    if ($searchValue !== "") {
        $searchParam = "%" . $searchValue . "%";
        $stmtFiltrado->bindValue(':search', $searchParam, PDO::PARAM_STR);
    }
    $stmtFiltrado->execute();
    $recordsFiltered = (int)$stmtFiltrado->fetchColumn();

    // D. Obtener registros paginados
    $queryData = "SELECT 
                    pred_id_pdetalle AS id_pdetalle,
                    pred_descripcion AS p_descripcion,
                    pred_importe AS importe,
                    pred_saldo AS saldo,
                    CASE pred_movimiento WHEN 'R' THEN 'Resta' WHEN 'S' THEN 'Suma' WHEN 'A' THEN 'Suma' ELSE pred_movimiento END AS movimiento,
                    DATE_FORMAT(pred_fecha, '%d-%m-%Y') AS fecha,
                    LOWER(DATE_FORMAT(pred_fecha, '%r')) AS hora
                  FROM san_prepago_detalle
                  WHERE pred_id_socio = :id_socio" . $searchCondition . "
                  ORDER BY " . $orderColumnName . " " . $orderDir . "
                  LIMIT :start, :length";

    $stmtData = $conn->prepare($queryData);
    $stmtData->bindValue(':id_socio', $post_id_socio, PDO::PARAM_INT);
    if ($searchValue !== "") {
        $stmtData->bindValue(':search', $searchParam, PDO::PARAM_STR);
    }
    $stmtData->bindValue(':start', $start, PDO::PARAM_INT);
    $stmtData->bindValue(':length', $length, PDO::PARAM_INT);
    $stmtData->execute();

    $resultados = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    // 4. Mapear y formatear exactamente las columnas requeridas
    $data = [];
    foreach ($resultados as $fila) {
        $data[] = [
            "id_pdetalle"   => (int)$fila['id_pdetalle'],
            "p_descripcion" => $fila['p_descripcion'],
            "importe"       => '$' . number_format((float)$fila['importe'], 2),
            "saldo"         => '$' . number_format((float)$fila['saldo'], 2),
            "movimiento"    => $fila['movimiento'],
            "fecha"         => $fila['fecha'],
            "hora"          => $fila['hora']
        ];
    }

    // Retornar la respuesta final
    responder_json([
        "draw"            => $draw,
        "recordsTotal"    => $recordsTotal,
        "recordsFiltered" => $recordsFiltered,
        "data"            => $data
    ]);

} catch (PDOException $e) {
    // Registrar error internamente
    error_log("Error en api_prepago_detalle.php: " . $e->getMessage());
    
    // Retornar error genérico compatible con DataTables
    responder_json([
        "draw"            => $draw,
        "recordsTotal"    => 0,
        "recordsFiltered" => 0,
        "data"            => [],
        "error"           => "Error al consultar los datos."
    ]);
}
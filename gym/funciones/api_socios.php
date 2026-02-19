<?php
// api_socios.php

require_once '../../funciones_globales/funciones_conexion.php';
require_once '../../funciones_globales/funciones_comunes.php';

// --- <<< CAMBIO #1: ESTABLECER ZONA HORARIA DE PHP >>> ---
// Se usa la función nativa de PHP, NO una consulta de base de datos.
date_default_timezone_set('America/Mexico_City');

// Establecer conexión a la BD
$conexion = obtener_conexion();
if (!$conexion) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo establecer la conexión con la base de datos.']);
    exit;
}
// --- <<< CAMBIO #2: ESTABLECER ZONA HORARIA DE LA CONEXIÓN MYSQL >>> ---
// Sincroniza la sesión de la base de datos con la zona horaria de PHP.
mysqli_query($conexion, "SET time_zone = '-06:00'");

session_start();
$id_empresa = isset($_SESSION['id_empresa']) ? intval($_SESSION['id_empresa']) : 1; 

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;

if (empty($id_empresa)) {
    header('Content-Type: application/json');
    echo json_encode(["draw" => $draw, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [], "error" => "Sesión no válida o ID de empresa no encontrado."]);
    exit;
}

// Parámetros de DataTables
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$orderColumnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 3; 
$orderColumnName = isset($_POST['columns'][$orderColumnIndex]['data']) ? mysqli_real_escape_string($conexion, $_POST['columns'][$orderColumnIndex]['data']) : 'nombres';
$orderDir = isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) == 'desc' ? 'DESC' : 'ASC';

$searchValue = isset($_POST['search']['value']) ? mysqli_real_escape_string($conexion, $_POST['search']['value']) : '';
$pag_opciones = isset($_POST['pag_opciones']) ? intval($_POST['pag_opciones']) : 0;

// --- <<< CAMBIO #3: SIMPLIFICACIÓN DE LA FECHA >>> ---
// Ahora que PHP está sincronizado, podemos usar date() de forma segura y eficiente.
$fecha_mov = date('Y-m-d');

$condicion = "";
if (!empty($searchValue)) {
    $condicion .= " AND (LOWER(CONCAT(s.soc_apepat, ' ', s.soc_apemat, ' ', s.soc_nombres)) LIKE LOWER('%$searchValue%') 
                       OR LOWER(s.soc_correo) LIKE LOWER('%$searchValue%') 
                       OR s.soc_tel_cel LIKE '%$searchValue%')";
}

// =========== APLICAR FILTRO DE OPCIONES ===========
if ( $pag_opciones > 0 ) {
    switch( $pag_opciones ) {
        case 1: // Socios agregados hoy
            $condicion .= " AND DATE(s.soc_fecha_captura) = '$fecha_mov'";
            break;
        
        case 2: // Socios que pagaron hoy
            $condicion .= " AND s.soc_id_socio IN (SELECT pag_id_socio FROM san_pagos WHERE DATE(pag_fecha_pago) = '$fecha_mov')";
            break;
        
        case 3: // Los que se vencen hoy
            $condicion .= " AND p.pag_fecha_fin = '$fecha_mov'";
            break;

        case 4: // Socios vencidos
            $condicion .= " AND (p.pag_fecha_fin < '$fecha_mov' OR p.pag_id_pago IS NULL)";
            break;
    }
}

$from_clause = "
    FROM san_socios s
    LEFT JOIN san_pagos p ON p.pag_id_pago = (
        SELECT pag_id_pago 
        FROM san_pagos
        WHERE pag_id_socio = s.soc_id_socio
        ORDER BY pag_fecha_fin DESC, pag_id_pago DESC
        LIMIT 1
    )
    WHERE s.soc_id_empresa = $id_empresa
";

// CONTEOS
$queryTotal = "SELECT COUNT(soc_id_socio) AS total FROM san_socios WHERE soc_id_empresa = $id_empresa";
$resTotal = mysqli_query($conexion, $queryTotal);
$recordsTotal = $resTotal ? intval(mysqli_fetch_assoc($resTotal)['total']) : 0;

$queryFiltered = "SELECT COUNT(s.soc_id_socio) AS total " . $from_clause . $condicion;
$resFiltered = mysqli_query($conexion, $queryFiltered);
$recordsFiltered = $resFiltered ? intval(mysqli_fetch_assoc($resFiltered)['total']) : 0;


// LÓGICA DE ORDENAMIENTO
$active_condition = "p.pag_fecha_ini <= '$fecha_mov' AND p.pag_fecha_fin >= '$fecha_mov'";
$secondary_order = "$orderColumnName $orderDir";

$orderByClause = "ORDER BY
    CASE WHEN $active_condition THEN 0 ELSE 1 END ASC,
    p.pag_fecha_ini DESC,
    CASE
        WHEN (s.soc_tel_cel IS NOT NULL AND s.soc_tel_cel <> '') AND (s.soc_correo IS NOT NULL AND s.soc_correo <> '') THEN 1
        WHEN (s.soc_tel_cel IS NOT NULL AND s.soc_tel_cel <> '') OR (s.soc_correo IS NOT NULL AND s.soc_correo <> '') THEN 2
        ELSE 3
    END ASC,
    $secondary_order";


// CONSULTA DE DATOS PARA LA PÁGINA ACTUAL
$queryData = "
    SELECT
        s.soc_id_socio AS id_socio,
        p.pag_id_pago AS id_pago,
        p.pag_fecha_ini,
        p.pag_fecha_fin,
        p.pag_fecha_pago, 
        s.soc_fecha_captura, 
        CONCAT(s.soc_apepat, ' ', s.soc_apemat, ' ', s.soc_nombres) AS nombres,
        s.soc_correo,
        s.soc_tel_cel,
        CASE 
            WHEN p.pag_id_pago IS NULL THEN 'Sin Pago'
            WHEN $active_condition THEN CONCAT(DATE_FORMAT(p.pag_fecha_ini, '%d-%m-%Y'), ' al ', DATE_FORMAT(p.pag_fecha_fin, '%d-%m-%Y'))
            WHEN p.pag_fecha_ini > '$fecha_mov' THEN 'Vigencia Futura'
            ELSE 'Pago Vencido'
        END AS status_pago
    " . $from_clause . $condicion . "
    " . $orderByClause . "
    LIMIT $start, $length";

$resultado = mysqli_query($conexion, $queryData);

// FORMATEO DE DATOS
$data = [];
$contador = $start + 1;
if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        if (file_exists("../../imagenes/avatar/$fila[id_socio].jpg")) {
            $fotografia = "<img src='../imagenes/avatar/$fila[id_socio].jpg' class='img-responsive' width='40px' />";
        } else {
            $fotografia = "<img src='../imagenes/avatar/noavatar.jpg' class='img-responsive' width='40px' />";
        }

        $acciones = "<div class='btn-group'>
                        <a class='pointer' data-toggle='dropdown'><span class='glyphicon glyphicon-chevron-down'></span></a>
                        <ul class='dropdown-menu'>
                            <li><a href='.?s=socios&i=datosg&id_socio=$fila[id_socio]'><span class='glyphicon glyphicon-edit'></span> Actualizar</a></li>
                            <li><a href='.?s=socios&i=pagos&id_socio=$fila[id_socio]'><span class='glyphicon glyphicon-usd'></span> Pagos</a></li>
                            <li><a href='?s=prepagos&i=editar&id_socio=$fila[id_socio]'><span class='glyphicon glyphicon-credit-card'></span> Monedero</a></li>
                            <li><a href='.?s=socios&i=fotografia&id_socio=$fila[id_socio]'><span class='glyphicon glyphicon-picture'></span> Fotografía</a></li>
                            <li><a href='.?s=socios&i=fechas&id_socio=$fila[id_socio]&id_pago=$fila[id_pago]'><span class='glyphicon glyphicon-calendar'></span> Fechas</a></li>
                            <li><a href='.?s=socios&i=eliminar&id_socio=$fila[id_socio]'><span class='glyphicon glyphicon-remove'></span> Eliminar</a></li>
                        </ul>
                    </div>";
        
        $data[] = [
            "contador"    => $contador, 
            "acciones"    => $acciones, 
            "id_socio"    => $fila['id_socio'],
            "nombres"     => $fila['nombres'], 
            "soc_correo"  => $fila['soc_correo'], 
            "soc_tel_cel" => $fila['soc_tel_cel'],
            "status_pago" => $fila['status_pago'], 
            "foto"        => "<a href='.?s=socios&i=fotografia&id_socio=$fila[id_socio]'>$fotografia</a>"
        ];
        $contador++;
    }
}

// RESPUESTA FINAL JSON
$response = [
    "draw"            => $draw,
    "recordsTotal"    => $recordsTotal,
    "recordsFiltered" => $recordsFiltered,
    "data"            => $data
];

header('Content-Type: application/json');
echo json_encode($response);
?>
<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once '../conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['soc_id_socio'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
    exit;
}

$socioId = (int)$_SESSION['admin']['soc_id_socio'];

if (!isset($conn) || !$conn instanceof PDO) {
    error_log("Fallo crítico en get_routine.php: No hay conexión a BD.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
    exit;
}

date_default_timezone_set('America/Mexico_City');
$currentDate = new DateTime();
$miembroActivo = false;

try {
    $query = "SELECT pag_fecha_fin FROM san_pagos WHERE pag_id_socio = :socioId AND pag_status = 'A' ORDER BY pag_fecha_fin DESC LIMIT 1";
    $stmtMem = $conn->prepare($query);
    $stmtMem->bindParam(':socioId', $socioId, PDO::PARAM_INT);
    $stmtMem->execute();
    $fechaFin = $stmtMem->fetchColumn();

    if ($fechaFin) {
        $fechaFinDate = new DateTime($fechaFin);
        if ($currentDate <= $fechaFinDate) $miembroActivo = true;
    }
} catch (PDOException $e) {
    error_log("Error DB al verificar membresía: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al verificar el estado de tu membresía.']);
    exit;
}

if (!$miembroActivo) {
    http_response_code(403);
    echo json_encode(['success' => false, 'membership_inactive' => true, 'message' => 'Tu membresía ha expirado o no está activa. Renueva para acceder a las rutinas.']);
    exit;
}

$nivelActual = isset($_GET['level']) ? (int)$_GET['level'] : 1;
$generoActual = isset($_GET['gender']) ? (int)$_GET['gender'] : 1;
if (!in_array($nivelActual, [1, 2, 3], true)) $nivelActual = 1;
if (!in_array($generoActual, [1, 2], true)) $generoActual = 1;

$nombresNiveles = [1 => 'Principiante', 2 => 'Intermedio', 3 => 'Avanzado'];
$nombresGeneros = [1 => 'Hombre', 2 => 'Mujer'];
$rutinaPorGrupo = [];

try {
    $sql = "SELECT
                gm.id_grupo, gm.nombre_grupo,
                re.orden_ejercicio, re.series, re.repeticiones, re.descanso_seg,
                e.id_ejercicio, e.nombre_ejercicio, e.descripcion, e.video_url, e.poster_url, e.recomendaciones
            FROM rutina_ejercicios re
            INNER JOIN ejercicios e ON re.id_ejercicio = e.id_ejercicio
            INNER JOIN grupos_musculares gm ON re.id_grupo_muscular = gm.id_grupo
            WHERE re.id_nivel = :nivel AND re.genero = :genero
            ORDER BY gm.nombre_grupo ASC, re.orden_ejercicio ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':nivel', $nivelActual, PDO::PARAM_INT);
    $stmt->bindParam(':genero', $generoActual, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $fila) {
        $idGrupo = (int)$fila['id_grupo'];
        if (!isset($rutinaPorGrupo[$idGrupo])) {
            $rutinaPorGrupo[$idGrupo] = [
                'id_grupo' => $idGrupo,
                'nombre_grupo' => $fila['nombre_grupo'],
                'ejercicios' => []
            ];
        }
        $rutinaPorGrupo[$idGrupo]['ejercicios'][] = [
            'id_ejercicio' => $fila['id_ejercicio'],
            'nombre' => $fila['nombre_ejercicio'],
            'descripcion' => $fila['descripcion'],
            'video_url' => $fila['video_url'],
            'poster_url' => $fila['poster_url'],
            'recomendaciones' => $fila['recomendaciones'],
            'series' => $fila['series'],
            'repeticiones' => $fila['repeticiones'],
            'descanso' => ($fila['descanso_seg'] !== null ? $fila['descanso_seg'] . ' seg' : 'N/A'),
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'nivel' => $nombresNiveles[$nivelActual] ?? 'Desconocido',
        'genero' => $nombresGeneros[$generoActual] ?? 'Desconocido',
        'rutinaPorGrupo' => array_values($rutinaPorGrupo)
    ]);
} catch (PDOException $e) {
    error_log("Error DB al obtener rutina: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al consultar la rutina desde la base de datos.']);
    $conn = null;
}
?>
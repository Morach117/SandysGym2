<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../conn.php';

date_default_timezone_set('America/Mexico_City');
header('Content-Type: application/json');

if (empty($_SESSION['admin']['soc_id_socio'])) {
    echo json_encode(['error' => 'Sesión no válida']);
    exit;
}

$id_socio = (int)$_SESSION['admin']['soc_id_socio'];
$servicio_id_str = $_POST['servicio'] ?? '';

if (empty($servicio_id_str)) {
    echo json_encode(['error' => 'Sin servicio']);
    exit;
}

try {
    $sql = "SELECT pag_fecha_fin 
            FROM san_pagos 
            WHERE pag_id_socio = ? AND pag_status = 'A' 
            ORDER BY pag_fecha_fin DESC 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_socio]);
    $ultimo_pago = $stmt->fetch(PDO::FETCH_ASSOC);

    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0);

    if ($ultimo_pago) {
        $ultima_fecha_fin = DateTime::createFromFormat('Y-m-d', $ultimo_pago['pag_fecha_fin']);
        $ultima_fecha_fin->setTime(0, 0, 0);

        if ($ultima_fecha_fin >= $hoy) {
            $fecha_inicio = clone $ultima_fecha_fin;
            $fecha_inicio->modify('+1 day');
        } else {
            $fecha_inicio = clone $hoy;
        }
    } else {
        $fecha_inicio = clone $hoy;
    }

    if (strpos($servicio_id_str, '-') === false) {
        throw new Exception("Formato de servicio inválido");
    }

    list($id_servicio, $meses, $dias) = array_pad(explode('-', $servicio_id_str), 3, 0);
    $meses = (int)$meses;
    $dias = (int)$dias;

    if ($meses < 0 || $dias < 0) throw new Exception("Periodo inválido");

    $fecha_fin = clone $fecha_inicio;
    
    if ($meses == 0 && $dias == 0) {
        // Servicio de hoy solamente, fecha_fin es la misma que fecha_inicio
    } else {
        // Si hay meses, se suman (sin desbordamiento)
        if ($meses > 0) {
            $año_actual = (int)$fecha_fin->format('Y');
            $mes_actual = (int)$fecha_fin->format('n');
            $dia_actual = (int)$fecha_fin->format('j');
            
            $target_month   = ($mes_actual - 1 + $meses) % 12 + 1;
            $target_year    = $año_actual + intdiv($mes_actual - 1 + $meses, 12);
            $dias_en_target = (int)date('t', strtotime(sprintf('%04d-%02d-01', $target_year, $target_month)));
            $target_day     = min($dia_actual, $dias_en_target);
            
            $fecha_fin->setDate($target_year, $target_month, $target_day);
        }
        
        // Si hay días, se suman
        if ($dias > 0) {
            $fecha_fin->modify("+$dias days");
        }
        
        // Se resta 1 día para que el rango sea inclusivo (ej: del 1 al 15 = 15 días)
        $fecha_fin->modify("-1 day");
    }

    echo json_encode([
        'status' => 'success',
        'fecha_inicio' => $fecha_inicio->format('d-m-Y'),
        'fecha_fin'    => $fecha_fin->format('d-m-Y'),
        'mensaje' => ($ultimo_pago && $ultima_fecha_fin >= $hoy) ? 'Continuidad aplicada' : 'Inicio hoy'
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
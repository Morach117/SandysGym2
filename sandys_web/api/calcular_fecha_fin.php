<?php
// api/calcular_fecha_fin.php

// 1. Iniciar sesión y conexión (NECESARIO para saber quién es el socio)
session_start();
require_once __DIR__ . '/../conn.php'; // Ajusta la ruta a tu conn.php si es necesario

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');
header('Content-Type: application/json'); // Responderemos con JSON

// Verificar sesión
if (empty($_SESSION['admin']['soc_id_socio'])) {
    echo json_encode(['error' => 'Sesión no válida']);
    exit;
}

$id_socio = (int)$_SESSION['admin']['soc_id_socio'];
$servicio_id_str = $_POST['servicio'] ?? '';

// Si no hay servicio seleccionado, no hacemos nada
if (empty($servicio_id_str)) {
    echo json_encode(['error' => 'Sin servicio']);
    exit;
}

try {
    // --- PASO A: Determinar la Fecha de Inicio Automática ---
    
    // Consultamos la última fecha de vencimiento de este socio
    // Buscamos el último pago activo o el último registrado
    $sql = "SELECT pag_fecha_fin 
            FROM san_pagos 
            WHERE pag_id_socio = ? AND pag_status = 'A' 
            ORDER BY pag_fecha_fin DESC 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_socio]);
    $ultimo_pago = $stmt->fetch(PDO::FETCH_ASSOC);

    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0); // Ignorar horas, solo fechas

    if ($ultimo_pago) {
        $ultima_fecha_fin = DateTime::createFromFormat('Y-m-d', $ultimo_pago['pag_fecha_fin']);
        $ultima_fecha_fin->setTime(0, 0, 0);

        // Lógica de Continuidad:
        if ($ultima_fecha_fin >= $hoy) {
            // CASO 1: Su membresía sigue activa (o vence hoy).
            // La nueva inicia al día siguiente del vencimiento para mantener continuidad.
            $fecha_inicio = clone $ultima_fecha_fin;
            $fecha_inicio->modify('+1 day');
        } else {
            // CASO 2: Su membresía ya venció hace días.
            // La nueva inicia HOY (no cobramos tiempo muerto).
            $fecha_inicio = clone $hoy;
        }
    } else {
        // CASO 3: Es su primer pago o no tiene historial.
        // Inicia HOY.
        $fecha_inicio = clone $hoy;
    }

    // --- PASO B: Calcular la Fecha de Fin ---

    // Extraer meses del ID (formato "123-1")
    list($id_servicio, $meses) = explode('-', $servicio_id_str);
    $meses = (int)$meses;

    if ($meses <= 0) throw new Exception("Meses inválidos");

    // Clonamos la fecha de inicio calculada para sumar los meses
    $fecha_fin = clone $fecha_inicio;
    $fecha_fin->modify("+$meses months");
    $fecha_fin->modify("-1 day"); // Restamos 1 día para el vencimiento exacto

    // --- PASO C: Devolver ambas fechas al Javascript ---
    echo json_encode([
        'status' => 'success',
        // Formato para mostrar en los inputs (dd-mm-YYYY)
        'fecha_inicio' => $fecha_inicio->format('d-m-Y'),
        'fecha_fin'    => $fecha_fin->format('d-m-Y'),
        // Mensaje opcional para depuración
        'mensaje' => ($ultimo_pago && $ultima_fecha_fin >= $hoy) ? 'Continuidad aplicada' : 'Inicio hoy'
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
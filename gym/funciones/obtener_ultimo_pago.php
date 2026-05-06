<?php
// Incluir el archivo de conexión
include "../../funciones_globales/funciones_conexion.php";

// Sanitización estricta de la entrada
$id_socio = isset($_GET['id_socio']) ? intval($_GET['id_socio']) : 0;

if (!$id_socio) {
    echo json_encode(array("success" => false, "error" => "No se proporcionó un ID de socio válido."));
    exit;
}

// Obtener la conexión
$conexion = obtener_conexion();
if (!$conexion) {
    echo json_encode(array("success" => false, "error" => "No se pudo conectar a la base de datos."));
    exit;
}

// Establecer la zona horaria
date_default_timezone_set('America/Mexico_City');
$hoy = date('Y-m-d');

// Consulta única: Extraer los 2 últimos pagos
$query = "SELECT pag_status, pag_fecha_fin FROM san_pagos 
          WHERE pag_id_socio = $id_socio 
          ORDER BY pag_id_pago DESC LIMIT 2";
          
$result = mysqli_query($conexion, $query);

$fecha_referencia = null;

if ($result && mysqli_num_rows($result) > 0) {
    // Extraer el pago más reciente absoluto
    $ultimo_pago = mysqli_fetch_assoc($result);
    
    if ($ultimo_pago['pag_status'] === 'A') {
        // Regla 1: Último pago Activo
        $fecha_referencia = $ultimo_pago['pag_fecha_fin'];
    } elseif ($ultimo_pago['pag_status'] === 'E') {
        // Regla 2: Último pago Cancelado ('E'). Retrocedemos al pago anterior.
        $pago_anterior = mysqli_fetch_assoc($result);
        if ($pago_anterior && $pago_anterior['pag_status'] === 'A') {
            $fecha_referencia = $pago_anterior['pag_fecha_fin'];
        }
    }
}

// Lógica de validación de fechas (4 días de tolerancia)
if ($fecha_referencia) {
    $fecha_ultimo_pago = date('Y-m-d', strtotime($fecha_referencia));
    $fecha_limite = date('Y-m-d', strtotime($fecha_ultimo_pago . ' + 4 days'));
    
    if ($hoy <= $fecha_limite) {
        // Dentro de tolerancia
        $habilitar_fecha = false;
        $fecha = date('d-m-Y', strtotime($fecha_referencia));
    } else {
        // Fuera de tolerancia, reiniciar vigencia
        $habilitar_fecha = true;
        $fecha = date('d-m-Y'); 
    }
} else {
    // Sin historial válido o ambos cancelados
    $habilitar_fecha = true;
    $fecha = date('d-m-Y');
}

// Output final
echo json_encode(array(
    "success" => true, 
    "fecha_pago" => $fecha, 
    "habilitar_fecha" => $habilitar_fecha
));

// Cerrar conexión
mysqli_close($conexion);
?>
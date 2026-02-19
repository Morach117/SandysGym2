<?php
// funciones/verificar_uso_cumpleanos.php

// 1. ACTIVAR REPORTES DE ERROR (Para evitar la pantalla negra vacía)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. INCLUIR CONEXIÓN (CORREGIDO A ../../ según tu estructura)
// Si esto falla, te saldrá un error de "No such file or directory"
if (file_exists("../../funciones_globales/funciones_conexion.php")) {
    include "../../funciones_globales/funciones_conexion.php";
} elseif (file_exists("../funciones_globales/funciones_conexion.php")) {
    include "../funciones_globales/funciones_conexion.php";
} else {
    die(json_encode(['success'=>false, 'mensaje'=>'Error: No se encuentra el archivo de conexión']));
}

$conexion = obtener_conexion();

// Recuperar parámetros
$id_socio = isset($_GET['id_socio']) ? intval($_GET['id_socio']) : 0;
$anio_actual = date('Y');
$mes_actual = date('m');
$codigo_cumple = '10W02Z95'; 

$response = array(
    'success' => false,
    'fecha_nacimiento' => '',
    'es_mes_cumple' => false,
    'ya_uso_descuento' => false,
    'mensaje' => ''
);

if ($id_socio > 0 && $conexion) {
    
    // PASO 1: OBTENER FECHA DE NACIMIENTO
    $query_socio = "SELECT soc_fecha_nacimiento FROM san_socios WHERE soc_id_socio = $id_socio LIMIT 1";
    $res_socio = mysqli_query($conexion, $query_socio);
    
    if ($res_socio && $fila = mysqli_fetch_assoc($res_socio)) {
        $fecha_nac = $fila['soc_fecha_nacimiento'];
        $response['fecha_nacimiento'] = $fecha_nac;
        $response['success'] = true;

        // Validamos que la fecha sea válida
        if ($fecha_nac && $fecha_nac != '0000-00-00' && $fecha_nac != '1900-01-01') {
            
            // Extraer el mes
            $mes_nacimiento = date('m', strtotime($fecha_nac));
            
            if ($mes_nacimiento == $mes_actual) {
                $response['es_mes_cumple'] = true;

                // PASO 2: VERIFICAR SI YA SE USÓ EL CÓDIGO
                $sql_uso = "SELECT COUNT(*) as total 
                            FROM san_pagos 
                            WHERE pag_id_socio = $id_socio 
                            AND pag_codigo_promocion = '$codigo_cumple' 
                            AND YEAR(pag_fecha_pago) = $anio_actual
                            AND pag_status = 'A'";

                $result_uso = mysqli_query($conexion, $sql_uso);
                $row_uso = mysqli_fetch_assoc($result_uso);
                
                if ($row_uso['total'] > 0) {
                    $response['ya_uso_descuento'] = true;
                    $response['mensaje'] = 'Ya usó su descuento este año.';
                } else {
                    $response['mensaje'] = 'Descuento disponible.';
                }
            } else {
                $response['mensaje'] = 'No es el mes de cumpleaños.';
            }
        } else {
            $response['mensaje'] = 'Fecha incorrecta o por defecto (1900).';
        }
    } else {
        $response['mensaje'] = 'No se encontró al socio en la BD.';
    }
} else {
    $response['mensaje'] = 'Error de conexión o ID inválido.';
}

// Limpiamos cualquier salida de texto anterior (errores warnings) para que sea JSON puro
ob_clean(); 
header('Content-Type: application/json');
echo json_encode($response);
?>
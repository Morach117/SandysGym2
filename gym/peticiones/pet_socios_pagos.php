<?php
require_once("../../funciones_globales/funciones_phpBB.php");

$envio = isset($_POST['envio']) ? true : false;
$fecha = request_var('fecha', '');   // formato esperado: dd-mm-yyyy
$servicio = request_var('servicio', ''); // formato esperado: servicio-meses

if ($envio) {
    if (strpos($fecha, '-') !== false && strpos($servicio, '-') !== false) {
        list($dia, $mes, $año) = explode('-', $fecha);
        list($servicio_nombre, $meses) = explode('-', $servicio);

        if (checkdate($mes, $dia, $año) && is_numeric($meses) && strlen($año) == 4) {
            $fecha_inicial = DateTime::createFromFormat('d-m-Y', $fecha);
            $es_bisiesto = date('L', strtotime("$año-01-01")); // 1 si es bisiesto, 0 si no

            // Si es enero y el día es 29, 30 o 31, ajustamos antes de sumar los meses
            if ($mes == 1) {
                if ($dia == 29 && $es_bisiesto) {
                    // Si es bisiesto, dejamos 29 de enero
                } elseif ($dia > 28) {
                    // Si no es bisiesto o si el día es 30 o 31, lo ajustamos a 28 de enero
                    $fecha_inicial->setDate($año, 1, 28);
                }
            }

            // Sumamos los meses
            $fecha_inicial->modify("+$meses months");

            // Verificamos si el nuevo mes es febrero
            if ($fecha_inicial->format('m') == 2) {
                $nuevo_año = $fecha_inicial->format('Y');
                $es_bisiesto_nuevo = date('L', strtotime("$nuevo_año-01-01"));

                // Si originalmente era 29 de enero y el nuevo año es bisiesto, dejamos 29 de febrero
                if ($dia == 29 && $es_bisiesto_nuevo) {
                    $fecha_inicial->setDate($nuevo_año, 2, 29);
                } else {
                    // En cualquier otro caso, lo ajustamos al 28 de febrero
                    $fecha_inicial->setDate($nuevo_año, 2, 28);
                }
            }

            echo $fecha_inicial->format('d-m-Y');
        } else {
            echo "Error: La fecha o el período de servicio no son válidos.";
        }
    } else {
        echo "Error: El formato de fecha o servicio es incorrecto.";
    }
}
?>

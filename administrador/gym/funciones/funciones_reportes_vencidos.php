<?php
// Asegúrate de que la conexión a la base de datos está incluida en este archivo
include "../../../funciones_globales/funciones_conexion.php";
$conexion = obtener_conexion();

// Imprime los datos enviados para depuración
// Uncomment para ver los datos
// print_r($_POST);

$action = isset($_POST['action']) ? $_POST['action'] : '';
$id_socio = isset($_POST['id_socio']) ? intval($_POST['id_socio']) : 0;

// Establece el tipo de contenido como JSON
header('Content-Type: application/json');

// Maneja las solicitudes según la acción
$response = array();
switch ($action) {
    case 'mostrarHistorial':
        $response = mostrarHistorial($id_socio);
        break;

    case 'editarDatos':
        $response = editarDatos($id_socio);
        break;

    case 'actualizarTelefono':
        $response = actualizarTelefono($id_socio);
        break;

    case 'guardarCambios':
        $response = guardarCambios($_POST);
        break;

    case 'guardarTelefono':
        $response = guardarTelefono($_POST);
        break;

    default:
        $response = array('error' => 'Acción no válida.');
        break;
}

echo json_encode($response);

function mostrarHistorial($id_socio)
{
    global $conexion;

    $id_empresa = 1;
    $id_consorcio = 1;
    $id_giro = 1;
    $fecha_mov = date('Y-m-d');

    $query = "SELECT pag_id_pago,
                     pag_id_socio,
                     pag_status AS status,
                     ser_descripcion,
                     LOWER(DATE_FORMAT(pag_fecha_pago, '%d-%m-%Y %r')) AS fecha_pago,
                     DATE_FORMAT(pag_fecha_ini, '%d-%m-%Y') AS fecha_ini,
                     DATE_FORMAT(pag_fecha_fin, '%d-%m-%Y') AS fecha_fin,
                     ROUND(pag_importe, 2) AS importe,
                     IF('$fecha_mov' > pag_fecha_fin, 'VENCIDO', 'VIGENTE') AS vigencia
              FROM san_pagos 
              INNER JOIN san_servicios ON ser_id_servicio = pag_id_servicio
              WHERE pag_id_socio = $id_socio
              AND pag_id_empresa = $id_empresa
              AND ser_id_consorcio = $id_consorcio
              AND ser_id_giro = $id_giro
              ORDER BY pag_id_pago DESC";

    $resultado = mysqli_query($conexion, $query);
    $historial = array();

    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $opciones = ($fila['vigencia'] == 'VIGENTE' && $fila['status'] == 'A') ?
                "<a href='.?s=socios&i=eliminarp&id_pago=$fila[pag_id_pago]&id_socio=$fila[pag_id_socio]'><span class='text-danger glyphicon glyphicon-remove-sign'></span></a>" : "";

            $class = ($fila['status'] == 'E') ? 'danger' : '';

            $historial[] = array(
                'opciones' => $opciones,
                'descripcion' => $fila['ser_descripcion'],
                'fecha_pago' => $fila['fecha_pago'],
                'fecha_ini' => $fila['fecha_ini'],
                'fecha_fin' => $fila['fecha_fin'],
                'importe' => number_format($fila['importe'], 2),
                'vigencia' => $fila['vigencia'],
                'class' => $class
            );
        }
    } else {
        $historial['error'] = "Error al obtener el historial: " . mysqli_error($conexion);
    }

    return $historial;
}




function editarDatos($id_socio)
{
    global $conexion;
    $query = "SELECT * FROM san_socios WHERE soc_id_socio = $id_socio";
    $resultado = mysqli_query($conexion, $query);
    $datos = array();

    if ($resultado) {
        $fila = mysqli_fetch_assoc($resultado);
        $datos = array(
            'id_socio' => $fila['soc_id_socio'],
            'nombres' => $fila['soc_nombres'],
            'apepat' => $fila['soc_apepat'],
            'apemat' => $fila['soc_apemat'],
            'correo' => $fila['soc_correo'],
            'telefono' => $fila['soc_tel_cel'],
            'fecha_nacimiento' => $fila['soc_fecha_nacimiento']
        );
    } else {
        $datos['error'] = "Error al obtener los datos.";
    }

    return $datos;
}

function actualizarTelefono($id_socio)
{
    global $conexion;
    $query = "SELECT soc_tel_cel FROM san_socios WHERE soc_id_socio = $id_socio";
    $resultado = mysqli_query($conexion, $query);
    $datos = array();

    if ($resultado) {
        $fila = mysqli_fetch_assoc($resultado);
        $datos = array(
            'id_socio' => $id_socio,
            'telefono' => $fila['soc_tel_cel']
        );
    } else {
        $datos['error'] = "Error al obtener el teléfono.";
    }

    return $datos;
}

function guardarCambios($data)
{
    global $conexion;
    $id_socio = intval($data['id_socio']);
    $nombres = mysqli_real_escape_string($conexion, $data['nombres']);
    $apepat = mysqli_real_escape_string($conexion, $data['apepat']);
    $apemat = mysqli_real_escape_string($conexion, $data['apemat']);
    $correo = mysqli_real_escape_string($conexion, $data['correo']);
    $telefono = mysqli_real_escape_string($conexion, $data['telefono']);
    $fecha_nacimiento = mysqli_real_escape_string($conexion, $data['fecha_nacimiento']);

    $query = "UPDATE san_socios 
              SET soc_nombres = '$nombres', 
                  soc_apepat = '$apepat', 
                  soc_apemat = '$apemat',
                  soc_correo = '$correo',
                  soc_tel_cel = '$telefono',
                  soc_fecha_nacimiento = '$fecha_nacimiento' 
              WHERE soc_id_socio = $id_socio";
    $resultado = mysqli_query($conexion, $query);

    if ($resultado) {
        return array('success' => "Cambios guardados correctamente.");
    } else {
        return array('error' => "Error al guardar los cambios: " . mysqli_error($conexion));
    }
}

function guardarTelefono($data)
{
    global $conexion;
    $id_socio = intval($data['id_socio']);
    $telefono = mysqli_real_escape_string($conexion, $data['telefono']);

    $query = "UPDATE san_socios SET soc_tel_cel = '$telefono' WHERE soc_id_socio = $id_socio";
    $resultado = mysqli_query($conexion, $query);

    if ($resultado) {
        return array('success' => "Teléfono actualizado correctamente.");
    } else {
        return array('error' => "Error al actualizar el teléfono: " . mysqli_error($conexion));
    }
}

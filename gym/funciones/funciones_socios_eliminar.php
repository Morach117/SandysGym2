<?php
// funciones_socios_eliminar.php

// 1. INICIO DE SESIÓN SEGURO PARA PREVENIR NOTICES Y HABILITAR CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. DECLARACIÓN DE LA FUNCIÓN DE ELIMINACIÓN (Con control de transacciones)

if (!function_exists('eliminar_socio')) {
    function eliminar_socio($id_socio) {
        global $conexion;
        
        if ($id_socio <= 0) {
            return ['num' => 0, 'msj' => 'ID de socio inválido o no proporcionado.'];
        }

        mysqli_autocommit($conexion, FALSE);

        try {
            // Eliminar fotografía
            $ruta_foto = "../imagenes/avatar/{$id_socio}.jpg";
            if (file_exists($ruta_foto)) {
                @unlink($ruta_foto);
            }
            $ruta_foto_upper = "../imagenes/avatar/{$id_socio}.JPG";
            if (file_exists($ruta_foto_upper)) {
                @unlink($ruta_foto_upper);
            }

            // Tablas dependientes a desvincular / eliminar antes de borrar el registro del socio
            $tablas_dependientes = [
                // 1. Desvincular referencias de otros socios (titulares / referidos)
                "UPDATE san_socios SET soc_id_referido_por = 0 WHERE soc_id_referido_por = $id_socio",
                "UPDATE san_socios SET soc_id_titular_grupo = 0 WHERE soc_id_titular_grupo = $id_socio",

                // 2. Tablas de apartado y cotización (y sus detalles)
                "DELETE FROM san_sapartado_detalle WHERE sade_id_sapartado IN (SELECT sa_id_sapartado FROM san_sapartado WHERE sa_id_cliente = $id_socio)",
                "DELETE FROM san_sapartado_historico WHERE sah_id_cliente = $id_socio",
                "DELETE FROM san_sapartado WHERE sa_id_cliente = $id_socio",
                "DELETE FROM san_cotizacion_detalle WHERE cod_id_cotizacion IN (SELECT cot_id_cotizacion FROM san_cotizacion WHERE cot_id_cliente = $id_socio)",
                "DELETE FROM san_cotizacion WHERE cot_id_cliente = $id_socio",

                // 3. Tablas de crédito (y sus subtablas)
                "DELETE FROM san_credito_calendario WHERE credc_id_credito IN (SELECT cred_id_credito FROM san_credito WHERE cred_id_aval = $id_socio OR cred_id_venta IN (SELECT ven_id_venta FROM san_venta WHERE ven_id_socio = $id_socio))",
                "DELETE FROM san_credito_facturacion WHERE cfc_id_credito IN (SELECT cred_id_credito FROM san_credito WHERE cred_id_aval = $id_socio OR cfc_id_venta IN (SELECT ven_id_venta FROM san_venta WHERE ven_id_socio = $id_socio))",
                "DELETE FROM san_credito_pagos WHERE credd_id_credito IN (SELECT cred_id_credito FROM san_credito WHERE cred_id_aval = $id_socio OR credd_id_venta IN (SELECT ven_id_venta FROM san_venta WHERE ven_id_socio = $id_socio))",
                "DELETE FROM san_credito WHERE cred_id_aval = $id_socio OR cred_id_venta IN (SELECT ven_id_venta FROM san_venta WHERE ven_id_socio = $id_socio)",

                // 4. Tablas de ventas (y sus detalles/históricos)
                "DELETE FROM san_monedero_historico WHERE monh_id_venta IN (SELECT ven_id_venta FROM san_venta WHERE ven_id_socio = $id_socio)",
                "DELETE FROM san_venta_detalle WHERE vende_id_venta IN (SELECT ven_id_venta FROM san_venta WHERE ven_id_socio = $id_socio)",
                "DELETE FROM san_venta_servicio WHERE vense_id_venta IN (SELECT ven_id_venta FROM san_venta WHERE ven_id_socio = $id_socio)",
                "DELETE FROM san_venta_historico WHERE venh_id_socio = $id_socio",
                "DELETE FROM san_venta WHERE ven_id_socio = $id_socio",

                // 5. Prepagos y monederos
                "DELETE FROM san_prepago_detalle WHERE pred_id_socio = $id_socio",
                "DELETE FROM san_prepago WHERE prep_id_socio = $id_socio",

                // 6. Pagos
                "DELETE FROM san_pagos WHERE pag_id_socio = $id_socio",

                // 7. Códigos, promociones, referidos, rutinas e invitaciones
                "DELETE FROM san_codigos_usados WHERE id_socio = $id_socio",
                "DELETE FROM san_codigos WHERE id_socio = $id_socio",
                "DELETE FROM san_referidos WHERE id_socio = $id_socio",
                "DELETE FROM san_rutinas_progreso WHERE pro_id_socio = $id_socio",
                "DELETE FROM san_plan_invitaciones WHERE id_socio_titular = $id_socio",
                "DELETE FROM email_logs WHERE id_socio = $id_socio"
            ];

            foreach ($tablas_dependientes as $sql) {
                if (!mysqli_query($conexion, $sql)) {
                    throw new Exception("Error SQL en dependencias: " . mysqli_error($conexion));
                }
            }

            // Eliminar registro principal
            $query_socio = "DELETE FROM san_socios WHERE soc_id_socio = $id_socio";
            if (!mysqli_query($conexion, $query_socio)) {
                throw new Exception("Error SQL en registro principal: " . mysqli_error($conexion));
            }

            mysqli_commit($conexion);
            return ['num' => 1, 'msj' => 'Socio eliminado con éxito.'];

        } catch (Exception $e) {
            mysqli_rollback($conexion);
            return ['num' => 0, 'msj' => 'Error crítico: ' . $e->getMessage()];
        } finally {
            mysqli_autocommit($conexion, TRUE);
        }
    }
}


// 3. LÓGICA DEL CONTROLADOR DE LA VISTA
// Priorizamos POST (formulario) pero admitimos GET (URL) de forma segura
$id_socio = isset($_POST['id_socio']) ? (int)$_POST['id_socio'] : (isset($_GET['id_socio']) ? (int)$_GET['id_socio'] : 0);
$enviar   = isset($_POST['enviar']) ? (int)$_POST['enviar'] : 0;
$seccion  = isset($_GET['s']) ? $_GET['s'] : 'socios'; 
$item     = isset($_GET['i']) ? $_GET['i'] : 'eliminar';

// Se valida que el ID exista antes de consultar
if ($id_socio === 0) {
    header("Location: .?s=socios");
    exit;
}

$datos = obtener_datos_socio(); // Se asume que esta función usa $_GET['id_socio'] o globals internamente

if (!$datos) {
    header("Location: .?s=socios");
    exit;
}

// GENERACIÓN DE TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($enviar) {
    // VALIDACIÓN DE TOKEN CSRF
    if (!isset($_POST['csrf_token']) || hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']) === false) {
        die('Error 403: Validación CSRF fallida. Petición rechazada.');
    }

    // Se inyecta el ID capturado como parámetro
    $exito = eliminar_socio($id_socio);
    
    if ($exito['num'] == 1) {
        header("Location: .?s=socios");
        exit;
    } else {
        mostrar_mensaje_div($exito['msj'], 'danger');
    }
}

$nombre = $datos['soc_nombres'] . " " . $datos['soc_apepat'] . " " . $datos['soc_apemat'];
$ruta_foto_vista = "../imagenes/avatar/$id_socio.jpg";

if (file_exists($ruta_foto_vista)) {
    $fotografia = "<img src='$ruta_foto_vista' class='img-thumbnail' style='width:100%' />";
} else {
    $fotografia = "<img src='../imagenes/avatar/noavatar.jpg' class='img-thumbnail' style='width:100%' />";
}
?>

<!-- 4. RENDERIZADO DE LA VISTA HTML -->
<div class="row">
    <div class="col-md-12">
        <h4 class="text-info">
            <span class="glyphicon glyphicon-folder-open"></span> Datos Generales
        </h4>
    </div>
</div>

<hr/>

<div class="row">
    <label class="col-md-1">Socio</label>
    <!-- SANITIZACIÓN XSS -->
    <div class="col-md-5"><?= htmlspecialchars(strtoupper($nombre), ENT_QUOTES, 'UTF-8') ?></div>
    
    <label class="col-md-6">¿Estás seguro de Eliminar este Socio?</label>
</div>

<div class="row">
    <div class="col-md-6">  
        <?= $fotografia ?>
    </div>
    
    <div class="col-md-6">
        <p class="text-danger">Si se elimina, también serán eliminados los pagos que haya efectuado, así como prepagos y todo el histórico de venta y ya no aparecerá en ninguna de las estadísticas de cortes.</p>
    </div>
</div>

<!-- SANITIZACIÓN EN ACTION DEL FORMULARIO -->
<form method="post" action=".?s=<?= htmlspecialchars($seccion, ENT_QUOTES, 'UTF-8') ?>&i=<?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?>">
    <div class="row">
        <div class="col-md-12">
            <!-- CASTING A ENTERO PARA SEGURIDAD -->
            <input type="hidden" name="id_socio" value="<?= $id_socio ?>" />
            
            <!-- CAMPO OCULTO CSRF -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />
            
            <button type="submit" name="enviar" value="1" class="btn btn-danger">
                <span class="glyphicon glyphicon-trash"></span> Si, Eliminar todo
            </button>
            <button type="button" name="cancel" class="btn btn-default" onclick="location.href='.?s=socios'">
                <span class="glyphicon glyphicon-remove"></span> No, Cancelar
            </button>
        </div>
    </div>
</form>
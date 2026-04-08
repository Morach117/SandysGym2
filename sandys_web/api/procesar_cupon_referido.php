<?php
// api/procesar_cupon_referido.php

session_start();
// Ajustamos la ruta asumiendo que este archivo está dentro de la carpeta 'api/'
require_once __DIR__ . '/../conn.php'; 

// =========================================================================
// Función nativa del sistema para generar el formato exacto de código
// =========================================================================
function generar_codigo_promocion()
{
    $numeros = '0123456789';
    $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $codigo = '';

    // Generar 2 números
    for ($i = 0; $i < 2; $i++) {
        $codigo .= $numeros[rand(0, strlen($numeros) - 1)];
    }
    // Generar 1 letra
    $codigo .= $letras[rand(0, strlen($letras) - 1)];
    // Generar 2 números
    for ($i = 0; $i < 2; $i++) {
        $codigo .= $numeros[rand(0, strlen($numeros) - 1)];
    }
    // Generar 1 letra
    $codigo .= $letras[rand(0, strlen($letras) - 1)];
    // Generar 2 números
    for ($i = 0; $i < 2; $i++) {
        $codigo .= $numeros[rand(0, strlen($numeros) - 1)];
    }
    return $codigo;
}

// =========================================================================
// Procesamiento de la petición POST
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_cupon']) && isset($_POST['id_socio'])) {
    
    $idSocio = (int)$_POST['id_socio'];

    try {
        $conn->beginTransaction();

        // 1. Doble validación: Asegurarnos de que tiene padrino y no ha generado uno ya
        // Nota: Agregamos una verificación por un prefijo o nombre para saber que es de referido
        $stmtVal = $conn->prepare("
            SELECT s.soc_id_referido_por, s.soc_nombres,
                   (SELECT COUNT(*) 
                    FROM san_codigos c 
                    INNER JOIN san_promociones p ON c.id_promocion = p.id_promocion
                    WHERE p.titulo LIKE 'REFERIDO-%' AND p.titulo LIKE ?
                   ) as tiene_cupon
            FROM san_socios s 
            WHERE s.soc_id_socio = ?
        ");
        $likeParam = "%-" . $idSocio; // Ejemplo: REFERIDO-1045
        $stmtVal->execute([$likeParam, $idSocio]);
        $validacion = $stmtVal->fetch(PDO::FETCH_ASSOC);

        if ($validacion && $validacion['soc_id_referido_por'] > 0 && $validacion['tiene_cupon'] == 0) {
            
            $fechaActual = date('Y-m-d');
            // Definimos la vigencia del cupón (ejemplo: 30 días para usarlo)
            $vigenciaFinal = date('Y-m-d', strtotime($fechaActual . ' + 30 days'));
            
            // Aquí defines el valor del descuento. 
            // Tu sistema anterior usaba 'porcentaje_descuento', asumimos que si es un número fijo
            // o porcentaje lo adaptas aquí. Si el cliente quiere regalar $35, pon 35.
            $descuentoAsignado = 35; 
            
            $tituloPromo = "REFERIDO-" . $idSocio; // Identificador único para evitar duplicados

            // 2. Insertar en san_promociones primero (como lo hace el administrador)
            $sqlPromo = "INSERT INTO san_promociones 
                         (titulo, fecha_generada, vigencia_inicial, vigencia_final, porcentaje_descuento, utilizado, tipo_promocion, fecha_creacion) 
                         VALUES (?, ?, ?, ?, ?, '0', 'Individual', ?)";
            $stmtPromo = $conn->prepare($sqlPromo);
            $stmtPromo->execute([
                $tituloPromo, 
                $fechaActual, 
                $fechaActual, 
                $vigenciaFinal, 
                $descuentoAsignado, 
                $fechaActual
            ]);

            $idPromocionInsertada = $conn->lastInsertId();

            // 3. Opcional pero recomendado: Ligar el descuento a servicios específicos
            // Si el cupón aplica para cualquier servicio, podrías omitir esto o insertarlo 
            // para todos los servicios. Aquí está comentado por si lo necesitas:
            /*
            $serviciosPermitidos = [1, 2, 3]; // IDs de las mensualidades
            foreach($serviciosPermitidos as $idServicio) {
                $conn->prepare("INSERT INTO san_descuentos_promociones (id_promocion, id_servicio, permitir_descuento) VALUES (?, ?, 1)")
                     ->execute([$idPromocionInsertada, $idServicio]);
            }
            */

            // 4. Generar el código único usando tu función
            $codigoFinal = generar_codigo_promocion();

            // 5. Insertar el código en la tabla san_codigos ligado a la promoción
            $sqlCodigo = "INSERT INTO san_codigos (codigo_generado, id_promocion, status) VALUES (?, ?, 1)";
            $stmtCodigo = $conn->prepare($sqlCodigo);
            $stmtCodigo->execute([$codigoFinal, $idPromocionInsertada]);

            $conn->commit();
            
            // Redirigir de vuelta al perfil con éxito
            header("Location: ../index.php?page=user_home&success=cupon_generado");
            exit;

        } else {
            $conn->rollBack();
            header("Location: ../index.php?page=user_home&error=cupon_invalido");
            exit;
        }

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error generando cupón de referido: " . $e->getMessage());
        header("Location: ../index.php?page=user_home&error=error_sistema");
        exit;
    }
} else {
    header("Location: ../index.php");
    exit;
}
?>
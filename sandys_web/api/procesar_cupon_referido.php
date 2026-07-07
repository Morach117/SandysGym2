<?php
// api/procesar_cupon_referido.php

session_start();
// Ajustamos la ruta asumiendo que este archivo está dentro de la carpeta 'api/'
require_once __DIR__ . '/../conn.php'; 

header('Content-Type: application/json');

function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

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
        $likeParam = "%-" . $idSocio;
        $stmtVal->execute([$likeParam, $idSocio]);
        $validacion = $stmtVal->fetch(PDO::FETCH_ASSOC);

        if ($validacion && $validacion['soc_id_referido_por'] > 0 && $validacion['tiene_cupon'] == 0) {
            
            $fechaActual = date('Y-m-d');
            $vigenciaFinal = date('Y-m-d', strtotime($fechaActual . ' + 30 days'));
            $descuentoAsignado = 35; 
            
            $tituloPromo = "REFERIDO-" . $idSocio;

            // 2. Insertar en san_promociones primero
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

            // 4. Generar el código único
            $codigoFinal = generar_codigo_promocion();

            // 5. Insertar el código en la tabla san_codigos ligado a la promoción
            $sqlCodigo = "INSERT INTO san_codigos (codigo_generado, id_promocion, status) VALUES (?, ?, 1)";
            $stmtCodigo = $conn->prepare($sqlCodigo);
            $stmtCodigo->execute([$codigoFinal, $idPromocionInsertada]);

            $conn->commit();
            
            json_response([
                'success' => true,
                'codigo' => $codigoFinal,
                'message' => 'Cupón generado exitosamente.'
            ]);

        } else {
            $conn->rollBack();
            json_response(['success' => false, 'message' => 'Ya has generado tu cupón o no tienes uno disponible.'], 400);
        }

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log("Error generando cupón de referido: " . $e->getMessage());
        json_response(['success' => false, 'message' => 'Error de sistema al generar cupón.'], 500);
    }
} else {
    json_response(['success' => false, 'message' => 'Petición inválida.'], 400);
}
?>
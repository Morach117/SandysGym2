<?php
require_once __DIR__ . '/conn.php';

$socioId = 5857;
$mensaje = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['resetear'])) {
    try {
        $conn->beginTransaction();

        // 1. Obtener IDs de promociones a eliminar para este socio
        $stmtPromoIds = $conn->prepare("SELECT id_promocion FROM san_promociones WHERE titulo IN (?, ?)");
        $stmtPromoIds->execute(["REFERIDO-$socioId", "REACTIVACION-$socioId"]);
        $promos = $stmtPromoIds->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($promos)) {
            $inClause = implode(',', array_fill(0, count($promos), '?'));
            
            // Eliminar de san_codigos
            $stmtDelCod = $conn->prepare("DELETE FROM san_codigos WHERE id_promocion IN ($inClause) OR id_socio = ?");
            $paramsCod = array_merge($promos, [$socioId]);
            $stmtDelCod->execute($paramsCod);

            // Eliminar de san_descuentos_promociones
            $stmtDelDP = $conn->prepare("DELETE FROM san_descuentos_promociones WHERE id_promocion IN ($inClause)");
            $stmtDelDP->execute($promos);

            // Eliminar de san_promociones
            $stmtDelP = $conn->prepare("DELETE FROM san_promociones WHERE id_promocion IN ($inClause)");
            $stmtDelP->execute($promos);
        } else {
            // Eliminar códigos por id_socio por si acaso
            $stmtDelCod = $conn->prepare("DELETE FROM san_codigos WHERE id_socio = ?");
            $stmtDelCod->execute([$socioId]);
        }

        // 2. Asignar padrino/referente = 1 para habilitar el regalo de bienvenida
        $stmtUpdSocio = $conn->prepare("UPDATE san_socios SET soc_id_referido_por = 1 WHERE soc_id_socio = ?");
        $stmtUpdSocio->execute([$socioId]);

        $conn->commit();
        $mensaje = "<div style='background:#10b981; color:#fff; padding:15px; border-radius:8px; margin-bottom:20px;'>
            <strong>¡Datos reseteados con éxito!</strong><br>
            Se han borrado las promociones y códigos anteriores del socio 5857 y se le asignó un padrino (soc_id_referido_por = 1).<br>
            Ahora puedes ingresar a la web con este usuario para probar la generación del código desde el botón de la página.
        </div>";
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $mensaje = "<div style='background:#ef4444; color:#fff; padding:15px; border-radius:8px; margin-bottom:20px;'>
            Error al resetear: " . htmlspecialchars($e->getMessage()) . "
        </div>";
    }
}

// Consultar estado actual
$stmtEstado = $conn->prepare("
    SELECT s.soc_id_socio, s.soc_nombres, s.soc_apepat, s.soc_id_referido_por,
           c.codigo_generado, p.titulo, p.utilizado
    FROM san_socios s
    LEFT JOIN san_codigos c ON c.id_socio = s.soc_id_socio
    LEFT JOIN san_promociones p ON c.id_promocion = p.id_promocion
    WHERE s.soc_id_socio = ?
");
$stmtEstado->execute([$socioId]);
$registros = $stmtEstado->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reset de Pruebas - Código de Bienvenida</title>
    <style>
        body { background-color: #121212; color: #e0e0e0; font-family: sans-serif; padding: 30px; }
        .card { background: #1e1e1e; border: 1px solid #333; border-radius: 12px; padding: 25px; max-width: 800px; margin: 0 auto; }
        h1, h2 { color: #fff; }
        .btn-reset { background-color: #ef4444; color: #fff; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px; }
        .btn-reset:hover { background-color: #dc2626; }
        pre { background: #000; color: #10b981; padding: 15px; border-radius: 8px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Simulador / Reset de Pruebas (Socio ID: <?= $socioId ?>)</h1>
        
        <?= $mensaje ?>

        <h2>Estado Actual del Socio <?= $socioId ?>:</h2>
        <?php if (!empty($registros)): ?>
            <ul>
                <li><strong>Nombre:</strong> <?= htmlspecialchars($registros[0]['soc_nombres'] . ' ' . $registros[0]['soc_apepat']) ?></li>
                <li><strong>Referido Por (Padrino):</strong> <?= $registros[0]['soc_id_referido_por'] ?> (<?= $registros[0]['soc_id_referido_por'] > 0 ? 'Habilitado para ver Banner' : 'Sin padrino - No verá Banner' ?>)</li>
                <li><strong>Códigos Generados Actuales:</strong>
                    <ul>
                        <?php 
                        $tieneCodigos = false;
                        foreach ($registros as $reg) {
                            if ($reg['codigo_generado']) {
                                $tieneCodigos = true;
                                echo "<li>Código: <strong>" . htmlspecialchars($reg['codigo_generado']) . "</strong> | Promo: " . htmlspecialchars($reg['titulo']) . " | Utilizado: " . ($reg['utilizado'] == '1' ? 'SÍ' : 'NO') . "</li>";
                            }
                        }
                        if (!$tieneCodigos) echo "<li><em>Ninguno (Listo para probar generación desde la web)</em></li>";
                        ?>
                    </ul>
                </li>
            </ul>
        <?php endif; ?>

        <form method="POST" style="margin-top: 25px;">
            <button type="submit" name="resetear" class="btn-reset">
                🔄 LIMPIAR BD Y PREPARAR SOCIO <?= $socioId ?> PARA PRUEBA
            </button>
        </form>

        <hr style="border-color: #333; margin: 30px 0;">

        <h2>Código SQL para ejecutar manualmente en phpMyAdmin:</h2>
        <p>Si prefieres ejecutar las sentencias SQL manualmente en tu gestor de base de datos:</p>
        <pre>
-- 1. Asignar padrino/referente al socio 5857 para habilitar el regalo de bienvenida
UPDATE san_socios SET soc_id_referido_por = 1 WHERE soc_id_socio = 5857;

-- 2. Eliminar referencias en san_descuentos_promociones
DELETE FROM san_descuentos_promociones 
WHERE id_promocion IN (
    SELECT id_promocion FROM san_promociones WHERE titulo IN ('REFERIDO-5857', 'REACTIVACION-5857')
);

-- 3. Eliminar códigos generados para el socio 5857
DELETE FROM san_codigos 
WHERE id_socio = 5857 
   OR id_promocion IN (
       SELECT id_promocion FROM san_promociones WHERE titulo IN ('REFERIDO-5857', 'REACTIVACION-5857')
   );

-- 4. Eliminar las promociones asociadas al socio 5857
DELETE FROM san_promociones WHERE titulo IN ('REFERIDO-5857', 'REACTIVACION-5857');
        </pre>
    </div>
</body>
</html>

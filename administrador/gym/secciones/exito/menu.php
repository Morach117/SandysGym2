<?php
// Seguridad: Sanitización de la variable de entorno para la vista
$item = isset($_GET['i']) ? htmlspecialchars($_GET['i']) : 'exito';

// Helper para estados activos y visuales en Bootstrap 3 Legacy
function activo($item_actual, $item_esperado) {
    return ($item_actual === $item_esperado) ? 'active' : '';
}

function chevron($item_actual, $item_esperado) {
    return ($item_actual === $item_esperado) ? '<span class="pull-right glyphicon glyphicon-chevron-right"></span>' : '';
}
?>

<div class="list-group">
    <a class="list-group-item <?= activo($item, 'exito') ?>" href=".?s=exito">
        <span class="glyphicon glyphicon-star"></span> Gestión de Casos de Éxito
        <?= chevron($item, 'exito') ?>
    </a>

    <a class="list-group-item <?= activo($item, 'plantillas_gestion') ?>" href=".?s=exito&i=plantillas_gestion">
        <span class="glyphicon glyphicon-list-alt"></span> Plantillas de Correo
        <?= chevron($item, 'plantillas_gestion') ?>
    </a>
</div>
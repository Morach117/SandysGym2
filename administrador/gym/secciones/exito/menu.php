<?php
// Seguridad: Sanitización básica de la variable de entorno para la vista
$item = isset($_GET['i']) ? htmlspecialchars($_GET['i']) : 'ver';

// Helper para evitar repetición de lógica en las vistas
function activo($item_actual, $item_esperado) {
    return ($item_actual === $item_esperado) ? 'active' : '';
}

function chevron($item_actual, $item_esperado) {
    // Uso nativo de la clase pull-right de Bootstrap 3
    return ($item_actual === $item_esperado) ? "<span class='pull-right glyphicon glyphicon-chevron-right'></span>" : '';
}
?>

<div class="list-group">
    <a class="list-group-item <?= activo($item, 'ver') ?>" href=".?s=cumple&i=ver">
        <span class="glyphicon glyphicon-calendar"></span> Listado de Cumpleañeros
        <?= chevron($item, 'ver') ?>
    </a>
    
    <a class="list-group-item <?= activo($item, 'cumple_reporte') ?>" href=".?s=cumple&i=cumple_reporte">
        <span class="glyphicon glyphicon-stats"></span> Reporte Concentrado
        <?= chevron($item, 'cumple_reporte') ?>
    </a>

    <a class="list-group-item <?= activo($item, 'plantillas_gestion') ?>" href=".?s=cumple&i=plantillas_gestion">
        <span class="glyphicon glyphicon-list-alt"></span> Plantillas de Correo
        <?= chevron($item, 'plantillas_gestion') ?>
    </a>

    <a class="list-group-item <?= activo($item, 'modificar_descuento') ?>" href=".?s=cumple&i=modificar_descuento">
        <span class="glyphicon glyphicon-usd"></span> Modificar Descuento
        <?= chevron($item, 'modificar_descuento') ?>
    </a>
</div>
<?php
    // Marcador de posición activa para el módulo actual
    $focus = "<span style='float:right' class='glyphicon glyphicon-chevron-right'></span>";
    
    // Determinación del módulo activo mediante el parámetro 's'
    $modulo_actual = $_GET['s'] ?? '';
?>

<div class="list-group">

    <a class="list-group-item <?= ($modulo_actual == 'landing') ? 'list-group-item-info' : '' ?>" href=".?s=landing">
        <span class="glyphicon glyphicon-blackboard"></span> Landing Page
        <?= ($modulo_actual == 'landing') ? $focus : '' ?>
    </a>
</div>
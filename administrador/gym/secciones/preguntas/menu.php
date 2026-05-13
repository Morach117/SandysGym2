<?php
    // Marcador de posición activa para el módulo actual
    $focus = "<span style='float:right' class='glyphicon glyphicon-chevron-right'></span>";
    
    // Determinación del módulo activo mediante el parámetro 's'
    $modulo_actual = $_GET['s'] ?? '';
?>

<div class="list-group">

    <a class="list-group-item <?= ($modulo_actual == 'preguntas') ? 'list-group-item-info' : '' ?>" href=".?s=preguntas">
        <span class="glyphicon glyphicon-question-sign"></span> Preguntas Frecuentes <?= ($modulo_actual == 'preguntas') ? $focus : '' ?>
    </a>

</div>
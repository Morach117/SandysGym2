<?php
    $focus  = "<span style='float:right' class='glyphicon glyphicon-chevron-right'></span>";
?>

<div class="list-group">
    <a class="list-group-item active" href=".?s=configuracion"><span class="glyphicon glyphicon-cog"></span> Administración</a>
    <a class="list-group-item" href=".?s=reportes"><span class="glyphicon glyphicon-list-alt"></span> Reportes</a>
    <a class="list-group-item" href=".?s=catalogos"><span class="glyphicon glyphicon-list"></span> Catalogos</a>
    <a class="list-group-item" href=".?s=cumple"><span class="glyphicon glyphicon-gift"></span> Cumpleaños</a>
    <a class="list-group-item" href=".?s=promociones"><span class="glyphicon glyphicon-tags"></span> Promociones</a>
    <a class="list-group-item" href=".?s=preguntas"><span class="glyphicon glyphicon-question-sign"></span> Preguntas Frecuentes <?= $focus ?></a>
    <a class="list-group-item" href=".?s=exito"><span class="glyphicon glyphicon-star"></span> Casos de Éxito <?= $focus ?></a>
</div>
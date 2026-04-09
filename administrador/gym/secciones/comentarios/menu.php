<?php
    // Mantengo tu lógica de diseño original
    $focus = "<span style='float:right' class='glyphicon glyphicon-chevron-right'></span>";
?>

<div class="list-group">
    <a class="list-group-item" href=".?s=comentarios">
        <span class="glyphicon glyphicon-envelope"></span> Ver Comentarios 
        <?= ($item == 'ver') ? $focus : '' ?>
    </a>

    <a class="list-group-item" href=".?s=comentarios&i=mensajes_nuevos">
        <span class="glyphicon glyphicon-bell"></span> Mensajes Nuevos 
        <?= ($item == 'no_leidos') ? $focus : '' ?>
    </a>
</div>
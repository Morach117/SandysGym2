<?php
    // Este ícono de flecha está perfecto
    $focus = "<span style='float:right' class='glyphicon glyphicon-chevron-right'></span>";
?>

<div class="list-group">
    <a class="list-group-item active" href=".?s=videos"><span class="glyphicon glyphicon-list-alt"></span> Rutinas</a>
    
    <a class="list-group-item" href=".?s=videos&i=index"><span class="glyphicon glyphicon-th-list"></span> Rutina <?= ($item == 'Rutina') ? $focus : '' ?></a>
    
    <a class="list-group-item" href=".?s=videos&i=videos"><span class="glyphicon glyphicon-play-circle"></span> Video <?= ($item == 'Video') ? $focus : '' ?></a>

</div>
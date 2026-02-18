<div class="row">
    <div class="col-md-12">
        <h4 class="text-info">
            <span class="glyphicon glyphicon-edit"></span> Editar Monedero Electrónico
        </h4>
    </div>
</div>
<hr />

<?php
$id_socio = request_var('id_socio', 0);

if (!$id_socio) {
    header("Location: .?s=prepagos");
    exit;
}

// ESTE BLOQUE PARA GUARDAR EL ABONO SE QUEDA INTACTO
if ($enviar) {
    $exito = actualizar_prepago();

    if ($exito['num'] == 1) {
        // Se podría simplificar el header para solo recargar la página
        header("Location: .?s=prepagos&i=editar&id_socio=$exito[IDS]&status=success");
        exit;
    } else {
        mostrar_mensaje_div($exito['num'] . '. ' . $exito['msj'], 'danger');
    }
}

// Esta función sigue siendo necesaria para mostrar el nombre y saldo
$prepago = obtener_prepago();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap.min.js"></script>

<div class="row">
    <div class="col-md-1">Socio</div>
    <div class="col-md-11"><strong><?= htmlspecialchars($prepago['nombre']) ?></strong></div>
</div>
<div class="row">
    <div class="col-md-1">Saldo</div>
    <div class="col-md-11"><strong>$<?= number_format($prepago['saldo'], 2) ?></strong></div>
</div>

<form action=".?s=prepagos&i=editar&id_socio=<?= $id_socio ?>" method="post">
    <div class="row">
        <div class="col-md-1">Agregar</div>
        <div class="col-md-2">
            <input type="number" step="0.01" name="prep_importe" class="form-control" required="required" placeholder="0.00" />
        </div>
        <div class="col-md-9">
            <input type="hidden" name="id_socio" value="<?= $prepago['id_socio'] ?>" />
            <input type="submit" name="enviar" class="btn btn-primary" value="Guardar" />
            <a href="javascript:history.back()" class="btn btn-default">Regresar</a>
        </div>
    </div>
</form>

<hr />

<div class="row">
    <div class="col-md-12">
        <h4 class="text-info">
            <span class="glyphicon glyphicon-list-alt"></span> Historial de Movimientos
        </h4>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <table id="historial-prepago" class="table table-hover table-striped h6" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th class="text-right">Importe</th>
                    <th class="text-right">Saldo</th>
                    <th>Movimiento</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#historial-prepago').DataTable({
            "processing": true,
            "serverSide": true,
            "order": [
                [0, "desc"]
            ],
            "ajax": {
                "url": "../gym/funciones/api_prepago_detalle.php", // Asegúrate que la ruta sea correcta
                "type": "POST",
                "data": function(d) {
                    d.id_socio = <?= $prepago['id_socio'] ?>;
                }
            },
            "columns": [{
                    "data": "id_pdetalle"
                },
                {
                    "data": "p_descripcion"
                },
                {
                    "data": "importe",
                    "className": "text-right"
                },
                {
                    "data": "saldo",
                    "className": "text-right"
                },
                {
                    "data": "movimiento"
                },
                {
                    "data": "fecha"
                },
                {
                    "data": "hora"
                }
            ],
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json"
            }
        });
    });
</script>
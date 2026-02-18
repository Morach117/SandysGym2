<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap.min.js"></script>

<div class="row">
    <div class="col-md-12">
        <h4 class="text-info">
            <span class="glyphicon glyphicon-user"></span> Socios con Monedero Electrónico
        </h4>
    </div>
</div>

<hr />

<div class="row">
    <div class="col-md-12">
        <table id="tabla-socios" class="table table-hover table-condensed" style="width:100%">
            <thead>
                <tr>
                    <th class="text-center">#</th>

                    <th>Nombre Completo</th>
                    <th class="text-right">Monedero</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#tabla-socios').DataTable({
            "processing": true,
            "serverSide": true,

            // --- INICIO DE LA MODIFICACIÓN ---
            // Se intercambian 'f' (buscador) y 'l' (entradas) para que el buscador quede a la izquierda.
            "dom": '<"row"<"col-sm-3"f><"col-sm-3"l>>rtip',
            "ajax": {
                "url": "../gym/funciones/prepagos.php",
                "type": "POST",
                "error": function(xhr, error, thrown) {
                    console.log("Error en la petición AJAX a prepagos.php");
                    $('#tabla-socios_processing').hide();
                    alert("Error al cargar los datos. Revisa la consola del navegador (F12).");
                }
            },

            "columns": [{
                    "data": "contador",
                    "orderable": false,
                    "className": "text-center"
                }, {
                    "data": "socio"
                },
                {
                    "data": "saldo",
                    "className": "text-right"
                }
            ],

            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json"
            },
            "createdRow": function(row, data, dataIndex) {
                $(row).css('cursor', 'pointer');
                $(row).on('click', function() {
                    window.location.href = '.?s=prepagos&i=editar&id_socio=' + data.id_socio;
                });
            }
        });
    });
</script>
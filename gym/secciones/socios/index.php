<?php
// Ya no necesitas llamar a lista_socios() aquí, DataTables lo hará por AJAX.
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap.min.js"></script>

<div class="container-fluid">
    <div class="panel panel-default">
        <div class="panel-body">

            <div class="row">
                <div class="col-md-12">
                    <h4 class="text-info" style="margin-top: 0; margin-bottom: 20px;">
                        <span class="glyphicon glyphicon-list"></span> Lista de Socios
                    </h4>
                </div>
            </div>

            <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-4">
                    <label for="filtro-opciones">Mostrar:</label>
                    <select id="filtro-opciones" class="form-control">
                        <option value="0">Todos los socios</option>
                        <option value="1">Socios agregados hoy</option>
                        <option value="2">Socios que pagaron hoy</option>
                        <option value="3">Los que se vencen hoy</option>
                        <option value="4">Socios vencidos</option>
                    </select>
                </div>
            </div>
            <hr style="margin-top: 0;" />

            <div class="row">
                <div class="col-md-12">
                    <table id="tabla-socios" class="table table-hover table-condensed table-striped nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Opc.</th>
                                <th>Nombre Completo</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th>Vigencia</th>
                                <th>Foto</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        var tabla = $('#tabla-socios').DataTable({
            "processing": true,
            "serverSide": true,
            "responsive": false,
            "scrollX": true,
            "order": [
                [2, "asc"] // Ajustado el índice de ordenamiento (ya que quitamos una columna)
            ],

            "ajax": {
                "url": "../gym/funciones/api_socios.php",
                "type": "POST",
                "data": function(d) {
                    d.pag_opciones = $('#filtro-opciones').val();
                }
            },

            "columns": [{
                    "data": "contador",
                    "orderable": false,
                    "searchable": false
                },
                {
                    "data": "acciones",
                    "orderable": false,
                    "searchable": false
                },
                // SE ELIMINÓ LA COLUMNA DE DATOS 'id_socio' DE AQUÍ
                {
                    "data": "nombres"
                },
                {
                    "data": "soc_correo"
                },
                {
                    "data": "soc_tel_cel"
                },
                {
                    "data": "status_pago",
                    "searchable": false
                },
                {
                    "data": "foto",
                    "orderable": false,
                    "searchable": false
                }
            ],
            
            // ESTO APLICA EL COLOR ROJO SI FALTAN DATOS
            "createdRow": function(row, data, dataIndex) {
                // Verificamos si el correo o el teléfono vienen vacíos o nulos
                if (data.soc_correo === "" || data.soc_correo === null || data.soc_tel_cel === "" || data.soc_tel_cel === null) {
                    $(row).css('background-color', '#ffe6e6');
                }
            },

            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json"
            },

            "dom": "<'row'<'col-sm-3'f><'col-sm-3'l>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-5'i><'col-sm-7'p>>"
        });

        $('#filtro-opciones').on('change', function() {
            tabla.draw();
        });
    });
</script>
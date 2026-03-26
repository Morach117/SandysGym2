<?php
// pages/lista_socios_dt.php
// Ya no necesitas llamar a lista_socios() aquí, DataTables lo hará por AJAX.
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap.min.css">

<style>
/* 1. Forzamos colores de fila con !important */
table.dataTable tbody tr.fila-alerta td {
    background-color: #ffe6e6 !important;
    /* ROJO: Faltan datos */
}

table.dataTable tbody tr.fila-validada td {
    background-color: #e6ffe6 !important;
    /* VERDE: Ya registrado */
}

/* 2. Centramos verticalmente todo el contenido de la tabla */
#tabla-socios tbody td {
    vertical-align: middle;
}

/* 3. Arreglo para Dropdown encimado */
.table-responsive {
    overflow: visible !important;
}

#tabla-socios_wrapper .dataTables_scrollBody {
    overflow: visible !important;
}

.dropdown-menu {
    z-index: 2000 !important;
    position: absolute !important;
}
</style>

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
                        <span class="glyphicon glyphicon-list"></span> Lista de Socios (Modo Profesional)
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
                    <table id="tabla-socios" class="table table-hover table-condensed table-striped nowrap"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Opc.</th>
                                <th>Nombre Completo</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th>Vigencia</th>
                                <th class="text-center">Foto</th>
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

<div class="modal fade" id="modalFoto" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content" style="background-color: #1a1a1a; border: 1px solid #333; border-radius: 8px;">
            <div class="modal-header" style="border-bottom: 1px solid #333; padding: 10px 15px;">
                <button type="button" class="close" data-dismiss="modal"
                    style="color: #fff; opacity: 1; margin-top: 2px;">&times;</button>
                <h4 class="modal-title text-info"
                    style="font-family: 'Oswald'; text-transform: uppercase; font-size: 16px;">
                    <span class="glyphicon glyphicon-picture"></span> Foto de Perfil
                </h4>
            </div>
            <div class="modal-body text-center" style="padding: 10px;">
                <img id="imgModal" src="" class="img-responsive img-thumbnail"
                    style="margin: 0 auto; max-height: 450px; border: 1px solid #444;" alt="Cargando...">
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
            [2, "asc"]
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
                "searchable": false,
                "className": "text-center" // Forzamos centrado de celda
            }
        ],

        // APLICA LOS COLORES DE FILA
        "createdRow": function(row, data, dataIndex) {
            // Prioridad 1: Rojo si faltan datos de contacto
            if (data.soc_correo === "" || data.soc_correo === null || data.soc_tel_cel === "" ||
                data.soc_tel_cel === null) {
                $(row).addClass('fila-alerta');
            }
            // Prioridad 2: Verde si la cuenta web está activada (soc_correo_status == 1)
            else if (data.soc_correo_status == 1 || data.soc_correo_status === "1") {
                $(row).addClass('fila-validada');
            }
            // Para todos los demás, no agregamos clase y Bootstrap los pinta de blanco/gris.
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

    // -----------------------------------------------------------
    // LÓGICA JAVASCRIPT PARA EL MODAL DE FOTOS
    // -----------------------------------------------------------
    $('#tabla-socios tbody').on('click', '.btn-ver-foto', function(e) {
        e.preventDefault();
        // 1. Obtener la URL almacenada en el atributo data-src del botón
        var fotoUrl = $(this).attr('data-src');

        // 2. Actualizar el src de la imagen vacía dentro del modal
        $('#imgModal').attr('src', fotoUrl);

        // 3. Abrir la ventana modal programáticamente
        $('#modalFoto').modal('show');
    });

    // Limpiar la imagen cuando se cierra el modal para evitar ver la foto anterior al abrir uno nuevo
    $('#modalFoto').on('hidden.bs.modal', function() {
        $('#imgModal').attr('src', '');
    });
});
</script>
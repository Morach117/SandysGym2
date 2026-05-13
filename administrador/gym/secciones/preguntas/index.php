<!-- Dependencias previas de Bootstrap 3 y jQuery asumidas en el layout -->
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h3 class="text-info"><span class="glyphicon glyphicon-question-sign"></span> Gestión de Preguntas
                Frecuentes</h3>
            <hr>
            <button class="btn btn-primary btn-sm" id="btnNuevo">
                <span class="glyphicon glyphicon-plus"></span> Nueva Pregunta
            </button>
            <br><br>
            <div class="table-responsive">
                <table class="table table-condensed table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="30%">Pregunta</th>
                            <th width="45%">Respuesta</th>
                            <th width="10%">Estado</th>
                            <th width="10%" class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbFaqBody">
                        <!-- Contenido cargado vía AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Formulario FAQ -->
<div class="modal fade" id="modalFaq" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="frmFaq">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modalTitle">Nueva Pregunta Frecuente</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" id="id_faq" name="id_faq" value="0">

                    <div class="form-group">
                        <label for="pregunta">Pregunta:</label>
                        <input type="text" class="form-control input-sm" id="pregunta" name="pregunta" required
                            autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="respuesta">Respuesta:</label>
                        <textarea class="form-control input-sm" id="respuesta" name="respuesta" rows="4"
                            required></textarea>
                    </div>
                    <div class="form-group">
    <label for="orden">Orden de Prioridad:</label>
    <input type="number" class="form-control input-sm" id="orden" name="orden" value="0" required>
    <span class="help-block"><small>0 = Default (Cronológico). Usar valores negativos (-1) o positivos (1) para forzar posición.</small></span>
</div>

                    <div class="form-group">
                        <label for="estado">Estado:</label>
                        <select class="form-control input-sm" id="estado" name="estado">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm"><span
                            class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    cargarTabla();

    function cargarTabla() {
        $.ajax({
            url: 'secciones/preguntas/ajax_faq.php',
            type: 'GET',
            data: {
                accion: 'listar'
            },
            success: function(response) {
                $('#tbFaqBody').html(response);
            }
        });
    }
// Resetear al abrir "Nueva Pregunta"
$('#btnNuevo').click(function() {
    $('#frmFaq')[0].reset();
    $('#id_faq').val('0');
    $('#orden').val('0'); // Inyección del valor por defecto
    $('#estado').val('1'); 
    $('#modalTitle').text('Nueva Pregunta Frecuente');
    $('#modalFaq').modal('show');
});

    $('#frmFaq').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'secciones/preguntas/ajax_faq.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#modalFaq').modal('hide');
                    cargarTabla();
                } else {
                    alert('Error: ' + res.msg);
                }
            }
        });
    });

// Extraer valor al abrir "Editar"
$(document).on('click', '.btn-editar', function() {
    $('#id_faq').val($(this).data('id'));
    $('#pregunta').val($(this).data('pregunta'));
    $('#respuesta').val($(this).data('respuesta'));
    $('#orden').val($(this).data('orden')); // Mapeo desde el atributo data-orden
    $('#estado').val($(this).data('estado'));
    $('#modalTitle').text('Editar Pregunta Frecuente');
    $('#modalFaq').modal('show');
});
    $(document).on('click', '.btn-eliminar', function() {
        if (confirm('¿Estás seguro de eliminar esta pregunta frecuente?')) {
            var id = $(this).data('id');
            $.ajax({
                url: 'secciones/preguntas/ajax_faq.php',
                type: 'POST',
                data: {
                    accion: 'eliminar',
                    id_faq: id
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        cargarTabla();
                    } else {
                        alert('Error al eliminar: ' + res.msg);
                    }
                }
            });
        }
    });
});
</script>
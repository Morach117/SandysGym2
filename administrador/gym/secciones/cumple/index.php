<?php
/**
 * Módulo: cumple_gestion.php
 * Descripción: Gestión de cumpleañeros, integración con plantillas de DB y envío de correos.
 */

// 1. SEGURIDAD Y FILTROS
$mes_filtro = isset($_GET['mes_filtro']) ? (int) $_GET['mes_filtro'] : (int) date('n');
$id_empresa_esc = mysqli_real_escape_string($conexion, $id_empresa);

$meses_nombres = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// 2. OBTENCIÓN DE PLANTILLAS DESDE BD
$plantillas_db = [];
$query_plan = "SELECT plan_nombre, plan_cuerpo FROM san_plantillas_correo WHERE plan_id_empresa = '$id_empresa_esc'";
$res_plan = mysqli_query($conexion, $query_plan);
if ($res_plan) {
    while ($row = mysqli_fetch_assoc($res_plan)) {
        $plantillas_db[$row['plan_nombre']] = $row['plan_cuerpo'];
    }
}
$plantillas_json = json_encode($plantillas_db, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// 3. FUNCIÓN DE CARGA DE SOCIOS
function obtener_socios_cumpleaños($mes_filtro, $meses_nombres, $id_empresa_esc) {
    global $conexion;
    $query = "SELECT 
                soc_id_socio AS id_socio,
                soc_nombres AS nombre_pila,
                CONCAT(soc_apepat, ' ', soc_apemat, ' ', soc_nombres) AS nombres_completos,
                MONTH(soc_fecha_nacimiento) AS mes_nacimiento,
                soc_tel_cel,
                soc_correo
            FROM san_socios
            WHERE soc_id_empresa = '$id_empresa_esc' 
            AND MONTH(soc_fecha_nacimiento) = $mes_filtro";

    $resultado = mysqli_query($conexion, $query);
    $datos = "";
    $i = 1;

    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $correo = $fila['soc_correo'];
            $nombre = $fila['nombre_pila'];
            $correo_badge = !empty($correo) ? "<span class='label label-info'>$correo</span>" : '<span class="label label-default">Sin correo</span>';
            
            $btn_correo = !empty($correo)
                ? "<button type='button' class='btn btn-primary btn-xs btn-correo-individual' data-correo='$correo' data-nombre='$nombre' onclick='abrirModalCorreo(\"$correo\", \"$nombre\")'><span class='glyphicon glyphicon-envelope'></span> Enviar</button>"
                : "<button type='button' class='btn btn-default btn-xs' disabled><span class='glyphicon glyphicon-ban-circle'></span> N/A</button>";

            $datos .= "<tr>
                        <td>$i</td>
                        <td>{$fila['nombres_completos']}</td>
                        <td>{$meses_nombres[(int)$fila['mes_nacimiento']]}</td>
                        <td>{$fila['soc_tel_cel']}</td>
                        <td>$correo_badge</td>
                        <td class='text-center'>$btn_correo</td>
                      </tr>";
            $i++;
        }
        return $datos;
    }
    return "<tr><td colspan='6' class='text-danger'>Error SQL: " . mysqli_error($conexion) . "</td></tr>";
}
?>

<div class="well well-sm">
    <form method="GET" action="">
        <input type="hidden" name="s" value="<?= htmlspecialchars($_GET['s'] ?? '') ?>">
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="input-group">
                    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span> Mes</span>
                    <select name="mes_filtro" class="form-control input-sm">
                        <?php foreach ($meses_nombres as $num => $nombre): ?>
                            <option value="<?= $num ?>" <?= ($num == $mes_filtro) ? 'selected' : '' ?>><?= $nombre ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-info btn-sm">
                            <span class="glyphicon glyphicon-search"></span> Filtrar
                        </button>
                    </span>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="panel panel-info">
    <div class="panel-heading">
        <div class="row">
            <div class="col-xs-8">
                <h3 class="panel-title" style="margin-top: 5px;">
                    <span class="glyphicon glyphicon-gift"></span> Cumpleañeros de <?= $meses_nombres[$mes_filtro] ?>
                </h3>
            </div>
            <div class="col-xs-4 text-right">
                <button type="button" class="btn btn-success btn-sm" onclick="abrirModalMasivo()">
                    <span class="glyphicon glyphicon-send"></span> Campaña Grupal
                </button>
            </div>
        </div>
    </div>
    <div class="panel-body table-responsive">
        <table id="tabla_cumple" class="table table-hover table-condensed table-bordered text-center" style="width:100%">
            <thead>
                <tr class="info">
                    <th class="text-center">#</th>
                    <th class="text-center">Nombre Completo</th>
                    <th class="text-center">Mes</th>
                    <th class="text-center">Teléfono</th>
                    <th class="text-center">Correo</th>
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?= obtener_socios_cumpleaños($mes_filtro, $meses_nombres, $id_empresa_esc) ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalCorreo" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><span class="glyphicon glyphicon-pencil"></span> Redactar Mensaje</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Plantilla:</label>
                    <div class="input-group">
                        <select class="form-control input-sm select_plantilla" onchange="cargarPlantilla('individual')">
                            </select>
                        <span class="input-group-btn">
                            <a href="?s=plantillas" class="btn btn-default btn-sm" title="Gestionar Plantillas"><span class="glyphicon glyphicon-cog"></span></a>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Destinatario:</label>
                    <input type="text" id="correo_destino" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Cuerpo del Correo:</label>
                    <textarea id="mensaje_cuerpo" class="form-control" rows="8"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="procesarEnvioIndividual()"><span class="glyphicon glyphicon-send"></span> Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMasivo" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title text-white"><span class="glyphicon glyphicon-send"></span> Envío Masivo del Mes</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <span class="glyphicon glyphicon-info-sign"></span> Se enviará un correo personalizado a cada socio con email registrado.
                </div>
                <div class="form-group">
                    <label>Seleccionar Plantilla Base:</label>
                    <select class="form-control input-sm select_plantilla" id="select_plantilla_masiva" onchange="cargarPlantilla('masivo')">
                        </select>
                </div>
                <div class="form-group">
                    <label>Vista Previa (Texto Original):</label>
                    <textarea id="mensaje_cuerpo_masivo" class="form-control" rows="5" readonly></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnConfirmaMasivo" onclick="procesarEnvioMasivo()" disabled>
                    <span class="glyphicon glyphicon-ok"></span> Confirmar Envío Masivo
                </button>
            </div>
        </div>
    </div>
</div>


<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap.min.js"></script>

<script>
// Datos inyectados desde PHP
const plantillas = <?= $plantillas_json ?>;

$(document).ready(function() {
    // Inicializar DataTable (Backend Style)
    $('#tabla_cumple').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json" },
        "pageLength": 25,
        "dom": 'Tfgtip'
    });

    actualizarSelectsPlantillas();
});

function actualizarSelectsPlantillas() {
    let options = '<option value="">-- Seleccionar Plantilla --</option>';
    for (const name in plantillas) {
        options += `<option value="${name}">${name}</option>`;
    }
    $('.select_plantilla').html(options);
}

function abrirModalCorreo(correo, nombre) {
    $('#correo_destino').val(correo);
    $('#mensaje_cuerpo').data('nombre', nombre);
    $('.select_plantilla').val('');
    $('#mensaje_cuerpo').val('');
    $('#modalCorreo').modal('show');
}

function cargarPlantilla(contexto) {
    if (contexto === 'individual') {
        const key = $('#modalCorreo .select_plantilla').val();
        const nombre = $('#mensaje_cuerpo').data('nombre') || 'Socio';
        if (plantillas[key]) {
            let texto = plantillas[key].replace(/\[NOMBRE\]/g, nombre);
            $('#mensaje_cuerpo').val(texto);
        }
    } else {
        const key = $('#select_plantilla_masiva').val();
        if (plantillas[key]) {
            $('#mensaje_cuerpo_masivo').val(plantillas[key]);
            $('#btnConfirmaMasivo').prop('disabled', false);
        } else {
            $('#mensaje_cuerpo_masivo').val('');
            $('#btnConfirmaMasivo').prop('disabled', true);
        }
    }
}

function procesarEnvioIndividual() {
    const btn = $('#modalCorreo .btn-primary');
    const datos = {
        email: $('#correo_destino').val(),
        mensaje: $('#mensaje_cuerpo').val(),
        socio: $('#mensaje_cuerpo').data('nombre')
    };

    if (!datos.mensaje.trim()) return alert("Mensaje vacío.");

    btn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh"></i> Enviando...');

    $.ajax({
        url: 'secciones/cumple/correo.php',
        type: 'POST',
        data: datos,
        dataType: 'json',
        success: function(res) {
            if (res.exito) {
                alert("Correo enviado.");
                $('#modalCorreo').modal('hide');
            } else alert("Error: " + res.mensaje);
        },
        error: () => alert("Error de conexión."),
        complete: () => btn.prop('disabled', false).html('<span class="glyphicon glyphicon-send"></span> Enviar')
    });
}

function abrirModalMasivo() {
    if ($('.btn-correo-individual').length === 0) return alert("Sin destinatarios.");
    $('#select_plantilla_masiva').val('');
    $('#mensaje_cuerpo_masivo').val('');
    $('#btnConfirmaMasivo').prop('disabled', true);
    $('#modalMasivo').modal('show');
}

function procesarEnvioMasivo() {
    const key = $('#select_plantilla_masiva').val();
    const btn = $('#btnConfirmaMasivo');
    
    if (!confirm("¿Iniciar envío masivo a todos los socios listados?")) return;

    btn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh"></i> Procesando Cola...');

    let queue = [];
    $('.btn-correo-individual').each(function() {
        queue.push({
            email: $(this).data('correo'),
            nombre: $(this).data('nombre'),
            mensaje: plantillas[key].replace(/\[NOMBRE\]/g, $(this).data('nombre'))
        });
    });

    // Envío secuencial para evitar saturación de buffer
    enviarCola(queue, 0, btn);
}

function enviarCola(cola, index, btn) {
    if (index >= cola.length) {
        alert("Proceso masivo finalizado.");
        $('#modalMasivo').modal('hide');
        btn.prop('disabled', false).html('<span class="glyphicon glyphicon-ok"></span> Confirmar Envío Masivo');
        return;
    }

    $.ajax({
        url: 'secciones/cumple/correo.php',
        type: 'POST',
        data: { email: cola[index].email, mensaje: cola[index].mensaje, socio: cola[index].nombre },
        complete: () => enviarCola(cola, index + 1, btn)
    });
}
</script>
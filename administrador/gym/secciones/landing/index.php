<?php
global $conexion;

// 1. HELPER PARA TABLAS
function generar_filas($resultado, $tipo) {
    if (!$resultado || mysqli_num_rows($resultado) == 0) {
        return "<tr id='fila_vacia_{$tipo}'><td colspan='6' class='text-center text-muted'>No hay registros.</td></tr>";
    }
    $datos = ""; $i = 1;
    while ($f = mysqli_fetch_assoc($resultado)) {
        $estado_badge = ($f['estado'] == 1) ? "<span class='label label-success'>Activo</span>" : "<span class='label label-danger'>Oculto</span>";
        $id = $f["id_{$tipo}"];
        
        $datos .= "<tr id='fila_{$tipo}_{$id}'><td>{$i}</td>";
        
        if ($tipo === 'hero') {
            $datos .= "<td>" . htmlspecialchars($f['subtitulo']) . "</td>
                       <td>" . htmlspecialchars($f['titulo_html']) . "</td>
                       <td><img src='../assets/img/hero/" . htmlspecialchars($f['imagen_bg']) . "' style='height:40px; object-fit:cover;' class='img-thumbnail'></td>";
        } elseif ($tipo === 'plan') {
            $datos .= "<td>" . htmlspecialchars($f['nombre']) . "</td>
                       <td>$ " . number_format($f['precio'], 2) . "</td>
                       <td>" . htmlspecialchars($f['frecuencia']) . "</td>";
        } elseif ($tipo === 'galeria') {
            $wide_badge = ($f['es_wide'] == 1) ? "<span class='label label-info'>Wide</span>" : "<span class='label label-default'>Normal</span>";
            $datos .= "<td><img src='../assets/img/gallery/" . htmlspecialchars($f['imagen_url']) . "' style='height:40px; object-fit:cover;' class='img-thumbnail'></td>
                       <td>{$wide_badge}</td>";
        }

        $datos .= "<td id='td_estado_{$tipo}_{$id}'>{$estado_badge}</td>
                   <td>
                       <div class='btn-group'>
                           <button class='btn btn-xs btn-warning' onclick='cambiarEstado(\"{$tipo}\", {$id}, {$f['estado']})' title='Cambiar Estado'><span class='glyphicon glyphicon-refresh'></span></button>
                           <button class='btn btn-xs btn-danger' onclick='eliminarRegistro(\"{$tipo}\", {$id})' title='Eliminar'><span class='glyphicon glyphicon-trash'></span></button>
                       </div>
                   </td></tr>";
        $i++;
    }
    return $datos;
}

$var_hero = generar_filas(mysqli_query($conexion, "SELECT * FROM san_landing_hero ORDER BY id_hero DESC"), 'hero');
$var_planes = generar_filas(mysqli_query($conexion, "SELECT * FROM san_landing_planes ORDER BY orden ASC, precio ASC"), 'plan');
$var_galeria = generar_filas(mysqli_query($conexion, "SELECT * FROM san_landing_galeria ORDER BY id_galeria DESC"), 'galeria');
?>

<div class="row">
    <div class="col-md-12">
        <h4 class="text-info"><span class="glyphicon glyphicon-blackboard"></span> Gestión de Landing Page</h4>
        <hr />

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#tab_hero" aria-controls="tab_hero" role="tab"
                    data-toggle="tab">1. Hero (Carrusel)</a></li>
            <li role="presentation"><a href="#tab_planes" aria-controls="tab_planes" role="tab" data-toggle="tab">2.
                    Planes</a></li>
            <li role="presentation"><a href="#tab_galeria" aria-controls="tab_galeria" role="tab" data-toggle="tab">3.
                    Galería</a></li>
            <li role="presentation"><a href="#tab_preview" aria-controls="tab_preview" role="tab" data-toggle="tab"
                    style="color: #F28123;"><strong><span class="glyphicon glyphicon-eye-open"></span> Vista Previa
                        Live</strong></a></li>
        </ul>

        <div class="tab-content" style="margin-top: 20px;">
            <div role="tabpanel" class="tab-pane active" id="tab_hero">
                <button class="btn btn-sm btn-primary mb-3" onclick="$('#modalHero').modal('show')"><span
                        class="glyphicon glyphicon-plus"></span> Nuevo Hero</button>
                <div class="table-responsive" style="margin-top: 15px;">
                    <table class="table table-hover table-condensed table-striped" id="tabla_hero">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Subtítulo</th>
                                <th>Título HTML</th>
                                <th>Imagen</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody><?= $var_hero ?></tbody>
                    </table>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane" id="tab_planes">
                <button class="btn btn-sm btn-primary mb-3" onclick="$('#modalPlan').modal('show')"><span
                        class="glyphicon glyphicon-plus"></span> Nuevo Plan</button>
                <div class="table-responsive" style="margin-top: 15px;">
                    <table class="table table-hover table-condensed table-striped" id="tabla_plan">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th>Frecuencia</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody><?= $var_planes ?></tbody>
                    </table>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane" id="tab_galeria">
                <button class="btn btn-sm btn-primary mb-3" onclick="$('#modalGaleria').modal('show')"><span
                        class="glyphicon glyphicon-plus"></span> Nueva Imagen</button>
                <div class="table-responsive" style="margin-top: 15px;">
                    <table class="table table-hover table-condensed table-striped" id="tabla_galeria">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Imagen</th>
                                <th>Formato Wide</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody><?= $var_galeria ?></tbody>
                    </table>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane" id="tab_preview">
                <div class="alert alert-info">
                    <span class="glyphicon glyphicon-info-sign"></span> La vista previa carga la interfaz pública. Usa
                    el botón "Sincronizar Cambios" dentro de la ventana de abajo.
                </div>
                <div style="border: 2px solid #ccc; border-radius: 4px; overflow: hidden; background: #050505;">
                    <?php
            // Detección dinámica del host
            $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            
            // CONFIGURACIÓN REQUERIDA: 
            // Si el frontend de la landing está en una subcarpeta, defínela aquí (Ej: "/frontend/index.php").
            // Si está en la raíz del host, déjalo como "/"
            $ruta_frontend = "/sandys_web"; 
            
            $url_iframe = $protocolo . "://" . $host . $ruta_frontend;
        ?>
                    <iframe src="<?= $url_iframe ?>" style="width: 100%; height: 700px; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHero" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title" style="color:#fff;"><span class="glyphicon glyphicon-picture"></span> Añadir
                    Hero</h4>
            </div>
            <form id="formHero" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="nuevo_hero">
                <div class="modal-body">
                    <div class="form-group"><label>Subtítulo:</label><input type="text" name="subtitulo"
                            class="form-control" required></div>
                    <div class="form-group"><label>Título (Permite &lt;strong&gt;):</label><input type="text"
                            name="titulo_html" class="form-control" required></div>
                    <div class="form-group"><label>Imagen Fondo (1920x1080):</label><input type="file" name="imagen_bg"
                            class="form-control" accept="image/*" required></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPlan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title" style="color:#fff;"><span class="glyphicon glyphicon-usd"></span> Añadir Plan
                </h4>
            </div>
            <form id="formPlan">
                <input type="hidden" name="accion" value="nuevo_plan">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group"><label>Nombre del Plan:</label><input type="text" name="nombre"
                                    class="form-control" required></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group"><label>Precio:</label><input type="number" step="0.01" name="precio"
                                    class="form-control" required></div>
                        </div>
                    </div>
                    <div class="form-group"><label>Frecuencia / Subtexto:</label><input type="text" name="frecuencia"
                            class="form-control" placeholder="Ej: MENSUAL" required></div>
                    <div class="form-group">
                        <label>Beneficios (Lista):</label>
                        <div id="contenedor_beneficios">
                            <input type="text" name="beneficios[]" class="form-control" style="margin-bottom:5px;"
                                required>
                        </div>
                        <button type="button" class="btn btn-xs btn-default"
                            onclick="$('#contenedor_beneficios').append('<input type=\\\'text\\\' name=\\\'beneficios[]\\\' class=\\\'form-control\\\' style=\\\'margin-bottom:5px;\\\'>')"><span
                                class="glyphicon glyphicon-plus"></span> Añadir Beneficio</button>
                    </div>
                    <div class="form-group"><label>URL Botón:</label><input type="text" name="url_boton"
                            class="form-control" value="index.php?page=inscribite"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGaleria" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title" style="color:#fff;"><span class="glyphicon glyphicon-camera"></span> Añadir
                    Galería</h4>
            </div>
            <form id="formGaleria" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="nueva_galeria">
                <div class="modal-body">
                    <div class="form-group"><label>Imagen:</label><input type="file" name="imagen_url"
                            class="form-control" accept="image/*" required></div>
                    <div class="checkbox"><label><input type="checkbox" name="es_wide" value="1"> ¿Formato Panorámico
                            (Wide)?</label></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function procesarForm(formId) {
    $('#' + formId).on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        $.ajax({
            url: 'secciones/landing/acciones_landing.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(r) {
                if (r.exito) {
                    location.reload(); // Recarga simple de tabla para mantener integridad
                } else {
                    alert(r.mensaje);
                }
            },
            error: () => alert("Error de servidor.")
        });
    });
}
procesarForm('formHero');
procesarForm('formPlan');
procesarForm('formGaleria');

function cambiarEstado(tipo, id, estadoActual) {
    $.post('secciones/landing/acciones_landing.php', {
        accion: 'estado_' + tipo,
        id: id,
        estado: estadoActual
    }, function(r) {
        if (r.exito) location.reload();
        else alert(r.mensaje);
    }, 'json');
}

function eliminarRegistro(tipo, id) {
    if (confirm('¿Eliminar permanentemente?')) {
        $.post('secciones/landing/acciones_landing.php', {
            accion: 'eliminar_' + tipo,
            id: id
        }, function(r) {
            if (r.exito) location.reload();
            else alert(r.mensaje);
        }, 'json');
    }
}
</script>
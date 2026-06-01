<?php
global $conexion;

function generar_filas($resultado, $tipo)
{
    if (!$resultado || mysqli_num_rows($resultado) == 0) {
        $cols = ($tipo === 'hero' || $tipo === 'galeria') ? 4 : 6;
        return "<tr id='fila_vacia_{$tipo}'><td colspan='{$cols}' class='text-center text-muted'>No hay registros.</td></tr>";
    }

    $datos = "";
    $i = 1;

    while ($f = mysqli_fetch_assoc($resultado)) {
        $id = (int) $f["id_{$tipo}"];
        $estado = (int) $f['estado'];
        $estado_badge = ($estado === 1) ? "<span class='label label-success'>Activo</span>" : "<span class='label label-danger'>Oculto</span>";

        $json_data = htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8');
        $datos .= "<tr id='fila_{$tipo}_{$id}'><td>{$i}</td>";

        if ($tipo === 'hero') {
            $img_desk = htmlspecialchars($f['img_desktop'], ENT_QUOTES, 'UTF-8');
            $img_mob = htmlspecialchars($f['img_mobile'], ENT_QUOTES, 'UTF-8');

            $src_desk = (strpos($img_desk, 'http') === 0) ? $img_desk : "/sandys_web/assets/img/hero/{$img_desk}";
            $src_mob = (strpos($img_mob, 'http') === 0) ? $img_mob : "/sandys_web/assets/img/hero/{$img_mob}";

            $datos .= "<td>
                           <img src='{$src_desk}' style='height:40px; width:60px; object-fit:cover;' class='img-thumbnail' title='Desktop'>
                           <img src='{$src_mob}' style='height:40px; width:30px; object-fit:cover;' class='img-thumbnail' title='Mobile'>
                       </td>";
        } elseif ($tipo === 'plan') {
            $datos .= "<td>" . htmlspecialchars($f['nombre'], ENT_QUOTES, 'UTF-8') . "</td>
                       <td>$ " . number_format((float) $f['precio'], 2) . "</td>
                       <td>" . htmlspecialchars($f['frecuencia'], ENT_QUOTES, 'UTF-8') . "</td>";
        } elseif ($tipo === 'galeria') {
            $img_path = htmlspecialchars($f['imagen_url'], ENT_QUOTES, 'UTF-8');
            $src_gal = (strpos($img_path, 'http') === 0) ? $img_path : "/sandys_web/assets/img/gallery/{$img_path}";
            $wide_badge = ((int) $f['es_wide'] === 1) ? "<span class='label label-info'>Wide</span>" : "<span class='label label-default'>Normal</span>";
            $datos .= "<td><img src='{$src_gal}' style='height:40px; object-fit:cover;' class='img-thumbnail'></td>
                       <td>{$wide_badge}</td>";
        }

        $datos .= "<td id='td_estado_{$tipo}_{$id}'>{$estado_badge}</td>
                   <td>
                       <div class='btn-group'>
                           <button class='btn btn-xs btn-info' onclick='abrirModalEditar(\"{$tipo}\", this)' data-info='{$json_data}' title='Editar'><span class='glyphicon glyphicon-pencil'></span></button>
                           <button class='btn btn-xs btn-warning' onclick='cambiarEstado(\"{$tipo}\", {$id}, {$estado})' title='Cambiar Estado'><span class='glyphicon glyphicon-refresh'></span></button>
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

$res_config = mysqli_query($conexion, "SELECT * FROM san_landing_config WHERE id = 1 LIMIT 1");
$config_ui = mysqli_fetch_assoc($res_config);
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
            <li role="presentation"><a href="#tab_colores" aria-controls="tab_colores" role="tab"
                    data-toggle="tab"><strong><span class="glyphicon glyphicon-tint"></span> Diseño
                        (Colores)</strong></a></li>
            <li role="presentation"><a href="#tab_preview" aria-controls="tab_preview" role="tab" data-toggle="tab"
                    style="color: #F28123;"><strong><span class="glyphicon glyphicon-eye-open"></span> Vista Previa
                        Live</strong></a></li>
        </ul>

        <div class="tab-content" style="margin-top: 20px;">
            <div role="tabpanel" class="tab-pane active" id="tab_hero">
                <button class="btn btn-sm btn-primary mb-3" onclick="abrirModalNuevo('hero')"><span
                        class="glyphicon glyphicon-plus"></span> Nuevo Hero</button>
                <div class="table-responsive" style="margin-top: 15px;">
                    <table class="table table-hover table-condensed table-striped" id="tabla_hero">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Imágenes (Desk/Mob)</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody><?= $var_hero ?></tbody>
                    </table>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane" id="tab_planes">
                <button class="btn btn-sm btn-primary mb-3" onclick="abrirModalNuevo('plan')"><span
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
                <button class="btn btn-sm btn-primary mb-3" onclick="abrirModalNuevo('galeria')"><span
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

            <div role="tabpanel" class="tab-pane" id="tab_colores">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Personalización de Paleta de Colores</strong></div>
                    <div class="panel-body">
                        <form id="formColores">
                            <input type="hidden" name="accion" value="guardar_colores">
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>Fondo Principal (--bg-color)</label>
                                    <input type="color" name="color_bg" class="form-control"
                                        value="<?= htmlspecialchars($config_ui['color_bg']) ?>">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Fondo Elementos (--input-bg)</label>
                                    <input type="color" name="color_input" class="form-control"
                                        value="<?= htmlspecialchars($config_ui['color_input']) ?>">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Acento Naranja (--accent-orange)</label>
                                    <input type="color" name="color_accent_orange" class="form-control"
                                        value="<?= htmlspecialchars($config_ui['color_accent_orange']) ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>Acento Verde (--accent-green)</label>
                                    <input type="color" name="color_accent_green" class="form-control"
                                        value="<?= htmlspecialchars($config_ui['color_accent_green']) ?>">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Acento Rojo (--accent-red)</label>
                                    <input type="color" name="color_accent_red" class="form-control"
                                        value="<?= htmlspecialchars($config_ui['color_accent_red']) ?>">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Texto Secundario (--text-muted)</label>
                                    <input type="color" name="color_text_muted" class="form-control"
                                        value="<?= htmlspecialchars($config_ui['color_text_muted']) ?>">
                                </div>
                            </div>
                            <hr>
                            <button type="submit" class="btn btn-primary"><span
                                    class="glyphicon glyphicon-floppy-disk"></span> Guardar Colores</button>
                        </form>
                    </div>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane" id="tab_preview">
                <div class="alert alert-info">
                    <span class="glyphicon glyphicon-info-sign"></span> Modifica los colores en la pestaña "Diseño" para
                    ver los cambios en tiempo real aquí.
                </div>
<div style="border: 2px solid #ccc; border-radius: 4px; overflow: hidden; background: #050505;">
                    <?php
                    $host = $_SERVER['HTTP_HOST'];

                    // Validación estricta de entorno
                    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
                        // Entorno de desarrollo local
                        $url_iframe = "http://localhost/SandysGym2/sandys_web/index.php";
                    } else {
                        // Entorno de producción
                        $url_iframe = "https://sandysgym.com/";
                    }
                    ?>
                    <iframe src="<?= htmlspecialchars($url_iframe, ENT_QUOTES, 'UTF-8') ?>" id="preview-iframe"
                        style="width: 100%; height: 700px; border: none;"></iframe>
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
                <h4 class="modal-title" style="color:#fff;"><span class="glyphicon glyphicon-picture"></span> <span
                        class="titulo-modal">Añadir Hero</span></h4>
            </div>
            <form id="formHero" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="nuevo_hero">
                <div class="modal-body">
                    <div class="alert alert-warning"><span class="glyphicon glyphicon-info-sign"></span> La imagen debe
                        contener los textos integrados desde diseño.</div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Imagen Desktop (1920x1080):</label>
                            <input type="file" name="img_desktop" class="form-control file-input" accept="image/*"
                                required>
                            <small class="help-block file-help" style="display:none;">Deja vacío para mantener
                                actual.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Imagen Mobile (800x1200):</label>
                            <input type="file" name="img_mobile" class="form-control file-input" accept="image/*"
                                required>
                            <small class="help-block file-help" style="display:none;">Deja vacío para mantener
                                actual.</small>
                        </div>
                    </div>
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
                <h4 class="modal-title" style="color:#fff;"><span class="glyphicon glyphicon-usd"></span> <span
                        class="titulo-modal">Añadir Plan</span></h4>
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
                            onclick="$('#contenedor_beneficios').append('<input type=\'text\' name=\'beneficios[]\' class=\'form-control\' style=\'margin-bottom:5px;\'>')"><span
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
                <h4 class="modal-title" style="color:#fff;"><span class="glyphicon glyphicon-camera"></span> <span
                        class="titulo-modal">Añadir Galería</span></h4>
            </div>
            <form id="formGaleria" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="nueva_galeria">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Imagen:</label>
                        <input type="file" name="imagen_url" class="form-control file-input" accept="image/*" required>
                        <small class="help-block file-help" style="display:none;">Deja vacío para mantener
                            actual.</small>
                    </div>
                    <div class="checkbox"><label><input type="checkbox" name="es_wide" value="1"> ¿Formato Panorámico
                            (Wide)?</label></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    function capitalizar(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

    function resetFormModal(tipo, esNuevo) {
        let form = $('#form' + capitalizar(tipo));
        form[0].reset();
        form.find('input[name="id"]').remove();

        let modalTitle = esNuevo ? 'Añadir ' : 'Editar ';
        $('#modal' + capitalizar(tipo)).find('.titulo-modal').text(modalTitle + capitalizar(tipo));

        if (esNuevo) {
            form.find('input[name="accion"]').val('nuevo_' + tipo);
            form.find('.file-input').prop('required', true);
            form.find('.file-help').hide();
            if (tipo === 'plan') {
                $('#contenedor_beneficios').html('<input type="text" name="beneficios[]" class="form-control" style="margin-bottom:5px;" required>');
            }
        } else {
            form.find('input[name="accion"]').val('editar_' + tipo);
            form.find('.file-input').prop('required', false);
            form.find('.file-help').show();
        }
        return form;
    }

    function abrirModalNuevo(tipo) {
        resetFormModal(tipo, true);
        $('#modal' + capitalizar(tipo)).modal('show');
    }

    function abrirModalEditar(tipo, btn) {
        let data = $(btn).data('info');
        let form = resetFormModal(tipo, false);
        form.append('<input type="hidden" name="id" value="' + data['id_' + tipo] + '">');

        if (tipo === 'plan') {
            form.find('input[name="nombre"]').val(data.nombre);
            form.find('input[name="precio"]').val(data.precio);
            form.find('input[name="frecuencia"]').val(data.frecuencia);
            form.find('input[name="url_boton"]').val(data.url_boton);

            let beneficios = JSON.parse(data.beneficios_json || '[]');
            let container = $('#contenedor_beneficios');
            container.empty();
            if (beneficios.length > 0) {
                beneficios.forEach(b => container.append('<input type="text" name="beneficios[]" class="form-control" style="margin-bottom:5px;" value="' + b + '" required>'));
            } else {
                container.append('<input type="text" name="beneficios[]" class="form-control" style="margin-bottom:5px;" required>');
            }
        } else if (tipo === 'galeria') {
            form.find('input[name="es_wide"]').prop('checked', data.es_wide == 1);
        }

        $('#modal' + capitalizar(tipo)).modal('show');
    }

    function procesarForm(formId) {
        $('#' + formId).on('submit', function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajax({
                url: 'secciones/landing/acciones_landing.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function (r) {
                    if (r.exito) location.reload(); else alert(r.mensaje);
                },
                error: () => alert("Error de servidor.")
            });
        });
    }

    procesarForm('formHero');
    procesarForm('formPlan');
    procesarForm('formGaleria');
    procesarForm('formColores');

    function cambiarEstado(tipo, id, estadoActual) {
        $.post('secciones/landing/acciones_landing.php', { accion: 'estado_' + tipo, id: id, estado: estadoActual }, function (r) {
            if (r.exito) location.reload(); else alert(r.mensaje);
        }, 'json');
    }

    function eliminarRegistro(tipo, id) {
        if (confirm('¿Eliminar permanentemente?')) {
            $.post('secciones/landing/acciones_landing.php', { accion: 'eliminar_' + tipo, id: id }, function (r) {
                if (r.exito) location.reload(); else alert(r.mensaje);
            }, 'json');
        }
    }

    // Lógica de Live Preview (Actualización CSS Variable en iFrame)
    const cssVarMap = {
        'color_bg': '--bg-color',
        'color_input': '--input-bg',
        'color_accent_orange': '--accent-orange',
        'color_accent_green': '--accent-green',
        'color_accent_red': '--accent-red',
        'color_text_muted': '--text-muted'
    };

    $('#formColores input[type="color"]').on('input', function () {
        let inputName = $(this).attr('name');
        let cssVar = cssVarMap[inputName];
        let hexValue = $(this).val();
        let iframe = document.getElementById('preview-iframe');

        if (iframe && iframe.contentDocument) {
            iframe.contentDocument.documentElement.style.setProperty(cssVar, hexValue);
        }
    });
</script>
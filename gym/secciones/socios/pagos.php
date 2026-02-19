<?php
$eliminar = request_var('eliminar', false);
$servicio = request_var('servicio', '');
$pag_fecha_pago = request_var('pag_fecha_pago', date('d-m-Y'));
$pag_fecha_ini = request_var('pag_fecha_ini', '');
$pag_fecha_fin = request_var('pag_fecha_fin', '');
$id_socio = request_var('id_socio', 0);

// Variables de integrantes
$integrante1 = request_var('integrante1', 0);
$integrante2 = request_var('integrante2', 0);
$pareja = request_var('pareja', 0);
$referidos = request_var('referidos', '');

// Fechas integrantes
$pag_fecha_ini1 = request_var('pag_fecha_ini1', '');
$pag_fecha_fin1 = request_var('pag_fecha_fin1', '');
$pag_fecha_ini2 = request_var('pag_fecha_ini2', '');
$pag_fecha_fin2 = request_var('pag_fecha_fin2', '');
$pag_fecha_ini_pareja = request_var('pag_fecha_ini_pareja', '');
$pag_fecha_fin_pareja = request_var('pag_fecha_fin_pareja', '');

$id_pago = request_var('IDP', 0);
$pag_efectivo = request_var('pag_efectivo', '');
$pag_tarjeta = request_var('pag_tarjeta', '');
$token = request_var('token', '');
$pag_importe = '';
$id_servicio = 0;
$servicio_cve = '';
$class_oculto = 'hide';
$op_fecha_pago = "";
$volver = ".?s=socios";

$codigo_promocion = isset($_POST['codigo_promocion']) ? $_POST['codigo_promocion'] : '';

// Para el paginado
$pag_opciones = request_var('pag_opciones', 0);
$pag_busqueda = request_var('pag_busqueda', '');
$pag_fechai = request_var('pag_fechai', '');
$pag_fechaf = request_var('pag_fechaf', '');
$pag_item = request_var('item', '');
$pag_blq = request_var('blq', 0);
$pag_pag = request_var('pag', 0);

// Construcción de URL de retorno
if ($pag_item) $volver .= "&i=$pag_item";
if ($pag_opciones) $volver .= "&pag_opciones=$pag_opciones";
if ($pag_busqueda) $volver .= "&pag_busqueda=$pag_busqueda";
if ($pag_fechai) $volver .= "&pag_fechai=$pag_fechai";
if ($pag_fechaf) $volver .= "&pag_fechaf=$pag_fechaf";
if ($pag_blq) $volver .= "&bql=$pag_blq";
if ($pag_pag) $volver .= "&pag=$pag_pag";

if (!$id_socio) {
    header("Location: .?s=socios");
    exit;
}

if ($id_pago && $token) {
    $impresion = checar_impresion_pagos();
    $chk_token = hash_hmac('md5', $id_pago, $gbl_key);

    if ($chk_token == $token && $impresion == 'S')
        echo "<script>mostrar_modal_pago($id_pago, '$token')</script>";
}

$servicios = obtener_servicios($servicio);

if ($servicio) {
    list($id_servicio, $meses) = explode('-', $servicio);
    $datos_servicio = obtener_servicio($id_servicio);
    $servicio_cve = $datos_servicio['clave'];
    if ($servicio_cve == 'MEN PARCIAL') $class_oculto = '';
}

if (file_exists("../imagenes/avatar/$id_socio.jpg"))
    $fotografia = "<img src='../imagenes/avatar/$id_socio.jpg' class='img-thumbnail' style='width:100%' />";
else
    $fotografia = "<img src='../imagenes/avatar/noavatar.jpg' class='img-thumbnail' style='width:100%' />";

if ($eliminar) {
    $mensaje = eliminar_pago_socio();
    if ($mensaje['num'] == 1)
        mostrar_mensaje_div($mensaje['msj'], 'success');
    else
        mostrar_mensaje_div($mensaje['num'] . ". " . $mensaje['msj'], 'danger');
}

// Solo superadministrador
if ($rol == 'S') {
    $op_fecha_pago = "<div class='row'>
                        <label class='col-md-5'>Fecha pago</label>
                        <div class='col-md-7'>
                            <input type='text' class='form-control' name='pag_fecha_pago' id='pag_fecha_pago' maxlength='10' value='$pag_fecha_pago' />
                        </div>
                    </div>";
}

if ($enviar) {
    $pag_importe = request_var('pag_importe', 0.0);
    $validar = validar_pago_socio();

    if ($validar['num'] == 1) {
        $exito = guardar_pago_socio();
        if ($exito['num'] == 1) {
            header("Location: .?s=socios&i=pagos&id_socio=$exito[IDS]&IDP=$exito[IDP]&token=$exito[tkn]");
            exit;
        } else
            mostrar_mensaje_div($exito['num'] . ". " . $exito['msj'], 'danger');
    } else
        mostrar_mensaje_div($validar['msj'], 'warning');
}

$nombre = obtener_datos_socio();
$tabla = lista_pagos_socio();
$archivo_img = nombre_archivo_imagen($id_socio);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<div class="row">
    <div class="col-md-12">
        <h4 class="text-info"><span class="glyphicon glyphicon-usd"></span> Captura de Pagos</h4>
    </div>
</div>

<hr />

<form role="form" method="post" action=".?s=socios&i=pagos" name="form_pago" enctype="multipart/form-data">
    <div class="row">
        <label class="col-md-3">Socio</label>
        <label class="col-md-9">
            <?= $nombre['soc_apepat'] . " " . $nombre['soc_apemat'] . " " . $nombre['soc_nombres'] ?>
        </label>
    </div>

    <div class="row">
        <label class="col-md-3">Saldo Monedero $:</label>
        <label class="col-md-9" style="color: red;">
            $<?= $nombre['soc_mon_saldo'] ?>
        </label>
    </div>

    <div class="row">
        <label class="col-md-3">Descuento del Cliente (%)</label>
        <label class="col-md-9">
            <?= $nombre['soc_descuento'] ?>%
        </label>
    </div>

    <div class="row">
        <label class="col-md-3">Archivo de Img</label>
        <label class="col-md-9">
            <?= $archivo_img ?>
        </label>
        <input type="hidden" id="id_socio" name="id_socio" value="<?= $id_socio ?>" />
        <input type="hidden" id="fecha_nacimiento_hidden" value="<?= $nombre['soc_fecha_nacimiento'] ?>" />
        <input type="hidden" id="descuento_cliente_hidden" value="<?= $nombre['soc_descuento'] ?>" />
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="row">
                <label class="col-md-5">Fecha de pago</label>
                <div class="col-md-7">
                    <input type="text" class="form-control" value="<?= fecha_generica(date('d-m-Y')); ?>" readonly="on" />
                </div>
            </div>

            <?= $op_fecha_pago ?>

            <div class="row">
                <label class="col-md-5">Servicio</label>
                <div class="col-md-7">
                    <select class="form-control" name="servicio" id="servicio" required>
                        <?= $servicios ?>
                    </select>
                </div>
            </div>

            <div class="modal fade" id="modalSeleccionIntegrantes" tabindex="-1" role="dialog" aria-labelledby="modalSeleccionIntegrantesLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalSeleccionIntegrantesLabel">Seleccionar Integrantes</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Por favor, selecciona los 2 integrantes restantes para el plan:</p>

                            <div class="form-group">
                                <label for="buscarIntegrante1">Buscar Integrante 1:</label>
                                <input type="text" class="form-control" id="buscarIntegrante1" placeholder="Buscar socio...">
                                <select class="form-control mt-2" id="integrante1" name="integrante1">
                                    <option value="">Selecciona un socio</option>
                                </select>
                            </div>

                            <div id="seleccionFechas1" style="display: none;">
                                <hr>
                                <div class="row">
                                    <label class="col-md-5">Fecha inicial Integrante 1</label>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="pag_fecha_ini1" id="pag_fecha_ini1" value="<?= $pag_fecha_ini1 ?>" maxlength="10" autocomplete="off" readonly="on" />
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <label class="col-md-5">Fecha vencimiento Integrante 1</label>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="pag_fecha_fin1" id="pag_fecha_fin1" value="<?= $pag_fecha_fin1 ?>" autocomplete="off" readonly="on" />
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <label for="buscarIntegrante2">Buscar Integrante 2:</label>
                                <input type="text" class="form-control" id="buscarIntegrante2" placeholder="Buscar socio...">
                                <select class="form-control mt-2" id="integrante2" name="integrante2">
                                    <option value="">Selecciona un socio</option>
                                </select>
                            </div>

                            <div id="seleccionFechas2" style="display: none;">
                                <hr>
                                <div class="row">
                                    <label class="col-md-5">Fecha inicial Integrante 2</label>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="pag_fecha_ini2" id="pag_fecha_ini2" maxlength="10" value="<?= $pag_fecha_ini2 ?>" autocomplete="off" readonly="on" />
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <label class="col-md-5">Fecha vencimiento Integrante 2</label>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="pag_fecha_fin2" id="pag_fecha_fin2" value="<?= $pag_fecha_fin2 ?>" autocomplete="off" readonly="on" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-primary" id="guardarIntegrantes">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalMensualidadPareja" tabindex="-1" role="dialog" aria-labelledby="modalSeleccionParejaLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalSeleccionParejaLabel">Seleccionar Pareja</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Por favor, selecciona el integrante para el plan de pareja:</p>
                            <div class="form-group">
                                <label for="buscarPareja">Buscar Pareja:</label>
                                <input type="text" class="form-control" id="buscarPareja" placeholder="Buscar socio...">
                                <select class="form-control mt-2" id="pareja" name="pareja">
                                    <option value="">Selecciona un socio</option>
                                </select>
                            </div>
                            <div id="seleccionFechas" style="display: none;">
                                <hr>
                                <div class="row">
                                    <label class="col-md-5">Fecha inicial</label>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="pag_fecha_ini_pareja" id="pag_fecha_ini_pareja" maxlength="10" value="<?= $pag_fecha_ini_pareja ?>" autocomplete="off" readonly="on" />
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <label class="col-md-5">Fecha vencimiento</label>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="pag_fecha_fin_pareja" id="pag_fecha_fin_pareja" value="<?= $pag_fecha_fin_pareja ?>" autocomplete="off" readonly="on" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-primary" id="guardarPareja">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row" id="selected-members" style="display: none;">
                <label class="col-md-5">Integrantes seleccionados</label>
                <div class="col-md-7">
                    <ul id="lista-integrantes" class="list-group">
                        </ul>
                </div>
            </div>

            <div class="row">
                <label class="col-md-5">Método de pago</label>
                <div class="col-md-7">
                    <select class="form-control" name="m_pago" id="m_pago" required>
                        <option value="E" selected>Efectivo</option>
                        <option value="T">Tarjeta</option>
                        <option value="M">Monedero</option>
                    </select>
                </div>
            </div>

            <div class="row <?= $class_oculto ?>" id="importe">
                <label class="col-md-offset-5 col-md-4"><em>Importe a pagar</em></label>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="pag_importe" maxlength="5" value="<?= $pag_importe ?>" />
                </div>
            </div>

            <div class="row">
                <label class="col-md-5">Fecha inicial</label>
                <div class="col-md-7">
                    <input type="text" class="form-control" name="pag_fecha_ini" id="pag_fecha_ini" required="required" maxlength="10" value="<?= $pag_fecha_ini ?>" autocomplete="off" readonly="on" />
                </div>
            </div>

            <div class="row">
                <label class="col-md-5">Fecha vencimiento</label>
                <div class="col-md-7">
                    <input type="text" class="form-control" name="pag_fecha_fin" id="pag_fecha_fin" value="<?= $pag_fecha_fin ?>" autocomplete="off" readonly="on" />
                </div>
            </div>
            <div class="row">
                <label class="col-md-5">Código de Promoción</label>
                <div class="col-md-7">
                    <input type="text" class="form-control" name="codigo_promocion" id="codigo_promocion" value="<?= $codigo_promocion ?>" autocomplete="off" />
                </div>
            </div>

            <div class="row">
                <label class="col-md-5">¿Tiene referidos?</label>
                <div class="col-md-7">
                    <input type="checkbox" id="tiene_referidos" name="tiene_referidos" />
                </div>
            </div>

            <div class="row" id="referidos-section" style="display: none;">
                <label class="col-md-5">Captura de Referidos</label>
                <div class="col-md-5">
                    <div class="input-group">
                        <input type="text" class="form-control" id="referidos" name="referidos" placeholder="Ingrese el teléfono" maxlength="10">
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <i id="icono-validacion" class="fas fa-circle text-muted"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary" id="buscar-referido">
                        <i class="fa fa-search"></i> Buscar
                    </button>
                </div>
            </div>

            <div class="modal fade" id="modalReferidos" tabindex="-1" role="dialog" aria-labelledby="modalReferidosLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalReferidosLabel">Buscar Usuario</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="text" id="search-input" class="form-control" placeholder="Ingrese número de teléfono">
                            <br>
                            <ul id="lista-referidos" class="list-group"></ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h4 class="text-info" style="font-size: 1.5em;"><strong>Detalle del Pago</strong></h4>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12" style="font-size: 18px;">
                    <p style="font-size: 18px; font-weight: bold;">Subtotal: <span id="subtotal"></span></p>
                    <p style="font-size: 18px; font-weight: bold;">Descuento: <span id="descuento"></span></p>
                    <p style="font-size: 18px; font-weight: bold;">Total: <span id="total"></span></p>
                </div>
            </div>
        </div>

        <div class="col-md-5" align="center">
            <div class="row">
                <div class="col-md-12">
                    <?= $fotografia ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <input type="file" name="avatar" />
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="monedero-section" style="display: none;">
        <label class="col-md-5">Saldo del monedero</label>
        <div class="col-md-7">
            <input type="text" class="form-control" id="saldo_monedero" name="saldo_monedero" value="" readonly />
        </div>
    </div>

    <div class="row" id="efectivo-section" style="display: none;">
        <label class="col-md-5">Cantidad a pagar en efectivo</label>
        <div class="col-md-7">
            <input type="text" class="form-control" id="cantidad_efectivo" name="cantidad_efectivo" value="0" />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <input type="hidden" name="pag_opciones" value="<?= $pag_opciones ?>" />
            <input type="hidden" name="pag_busqueda" value="<?= $pag_busqueda ?>" />
            <input type="hidden" name="pag_fechai" value="<?= $pag_fechai ?>" />
            <input type="hidden" name="pag_fechaf" value="<?= $pag_fechaf ?>" />
            <input type="hidden" name="pag_item" value="<?= $pag_item ?>" />
            <input type="hidden" name="blq" value="<?= $pag_blq ?>" />
            <input type="hidden" name="pag" value="<?= $pag_pag ?>" />

            <input type="submit" name="enviar" value="Cobrar y guardar" class="btn btn-primary" />
            <input type="button" name="Regresar" value="Regresar" class="btn btn-default" onclick="location.href='<?= $volver ?>'" />
        </div>
    </div>
</form>

<div class="row">
    <div class="col-md-12">
        <h5 class="text-info"><strong>Historico de pagos</strong></h5>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <table class="table table-hover h6">
            <thead>
                <th></th>
                <th>Servicio pagado</th>
                <th>Fecha de pago</th>
                <th>Fecha inicial</th>
                <th>Vencimiento</th>
                <th class="text-right">Importe</th>
            </thead>
            <tbody>
                <?= $tabla ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var descuentoCumpleanosAplicado = false;
        var idSocioGlobal = $('#id_socio').val();

        // --------------------------------------------------------
        // FUNCIONES AUXILIARES
        // --------------------------------------------------------

        function obtenerUltimoPago(id_socio, callback) {
            $.ajax({
                url: './funciones/obtener_ultimo_pago.php',
                type: 'GET',
                data: {
                    id_socio: id_socio
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (id_socio == idSocioGlobal) {
                            // Si es el socio principal, llenamos el input principal
                            var fechaInput = document.getElementById('pag_fecha_ini');
                            fechaInput.value = response.fecha_pago;

                            if (response.habilitar_fecha) {
                                fechaInput.removeAttribute('readonly');
                                fechaInput.setAttribute('autocomplete', 'on');
                            } else {
                                fechaInput.setAttribute('readonly', 'on');
                                fechaInput.setAttribute('autocomplete', 'off');
                            }
                        }
                        callback(response.fecha_pago);
                    } else {
                        console.error('Error al obtener la fecha del último pago:', response.error);
                        callback(null);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al obtener la fecha del último pago:', error);
                    callback(null);
                }
            });
        }

        function calcular_servicio_principal() {
            var servicio = $('#servicio').val();
            var fecha_ini_input = $('#pag_fecha_ini');

            // Mostrar u ocultar el importe manual
            if (servicio == '5-1') {
                $('#importe').show();
            } else {
                $('#importe').hide();
            }

            // Validar modales especiales
            if (servicio === '123-1') {
                $('#modalSeleccionIntegrantes').modal('show');
            } else if (servicio === '167-1') {
                $('#modalMensualidadPareja').modal('show');
            }

            function peticionCalculo(fecha) {
                $.post("peticiones/pet_socios_pagos.php", {
                    fecha: fecha,
                    servicio: servicio,
                    envio: true
                }, function(datos) {
                    $('#pag_fecha_fin').val(datos);
                    obtenerCuotaServicio(); // Calcular montos
                });
            }

            if (!fecha_ini_input.prop('readonly')) {
                peticionCalculo(fecha_ini_input.val());
            } else {
                obtenerUltimoPago(idSocioGlobal, function(fecha_ini) {
                    if (fecha_ini) {
                        peticionCalculo(fecha_ini);
                    }
                });
            }
        }

        // Asignar evento al cambio de servicio principal
        $('#servicio').change(calcular_servicio_principal);
        // Asignar evento al cambio de fecha manual
        $('#pag_fecha_ini').change(calcular_servicio_principal);


        // --------------------------------------------------------
        // LÓGICA DE DESCUENTOS Y PAGOS
        // --------------------------------------------------------

        function obtenerCuotaServicio() {
            var servicioSeleccionado = document.getElementById("servicio").value;
            var id_servicio = servicioSeleccionado.split('-')[0];

            if (!id_servicio) return;

            $.ajax({
                url: "./funciones/obtener_cuota_servicio.php",
                type: "GET",
                data: {
                    id_servicio: id_servicio
                },
                dataType: 'json',
                success: function(respuesta) {
                    if (respuesta.success) {
                        var cuota = parseFloat(respuesta.cuota);
                        $("#subtotal").text(cuota.toFixed(2));

                        // 1. Intentamos verificar cumpleaños (que tiene prioridad)
                        verificarCumpleanos(function(esCumple) {
                            if (!esCumple) {
                                // 2. Si no es cumple o ya se usó, ver descuento normal
                                if (!descuentoCumpleanosAplicado) {
                                    var descuentoCliente = parseFloat($('#descuento_cliente_hidden').val());
                                    if (!isNaN(descuentoCliente) && descuentoCliente > 0) {
                                        aplicarDescuentoCliente(descuentoCliente);
                                    } else {
                                        $("#descuento").text('0.00');
                                        $("#total").text(cuota.toFixed(2));
                                    }
                                }
                            }
                        });

                    } else {
                        console.error("Error cuota:", respuesta.error);
                    }
                }
            });
        }

        function aplicarDescuentoCliente(descuentoCliente) {
            if (descuentoCumpleanosAplicado) return;
            var cuota = parseFloat($("#subtotal").text());
            var montoDescontadoCliente = cuota * (descuentoCliente / 100);
            var totalConDescuento = cuota - montoDescontadoCliente;
            $("#descuento").text(montoDescontadoCliente.toFixed(2));
            $("#total").text(totalConDescuento.toFixed(2));
        }

        function aplicarDescuentoPromocional(codigo) {
            // Permitimos aplicar si no hay descuento de cumple previo o si es EL descuento de cumple
            if (descuentoCumpleanosAplicado && codigo !== '10W02Z95') return;

            var servicioSeleccionado = $("#servicio").val();
            var id_servicio = servicioSeleccionado.split('-')[0];

            // Verificamos si el servicio permite promos
            verificarDescuentosPromocionales(id_servicio);

            $.ajax({
                url: "./funciones/verificar_codigo_promocional.php",
                data: {
                    codigo_promocion: codigo
                },
                dataType: 'json',
                success: function(resp) {
                    if (resp.success) {
                        var descPorc = parseFloat(resp.porcentaje_descuento);
                        var cuota = parseFloat($("#subtotal").text());

                        // Sumar descuento cliente si existe
                        var descCliente = parseFloat($('#descuento_cliente_hidden').val());
                        var totalDescPorc = descPorc;

                        if (!isNaN(descCliente)) {
                            totalDescPorc += descCliente;
                        }

                        var montoDesc = cuota * (totalDescPorc / 100);
                        var totalFinal = cuota - montoDesc;

                        $("#descuento").text(montoDesc.toFixed(2));
                        $("#total").text(totalFinal.toFixed(2));
                    } else {
                        alert("Error: " + resp.error);
                    }
                }
            });
        }

        // --- FUNCIÓN DE CUMPLEAÑOS CORREGIDA ---
        function verificarCumpleanos(callback) {
            var fechaNacString = $('#fecha_nacimiento_hidden').val(); // Formato esperado: 2000-05-01
            if (!fechaNacString) {
                if (callback) callback(false);
                return;
            }

            // Usar split para evitar problemas de zona horaria con new Date()
            var partes = fechaNacString.split('-');
            // partes[0] = Año, partes[1] = Mes, partes[2] = Día
            if (partes.length < 2) {
                if (callback) callback(false);
                return;
            }

            var mesNacimiento = parseInt(partes[1], 10); // Convertir a entero (1-12)
            var fechaActual = new Date();
            var mesActual = fechaActual.getMonth() + 1; // getMonth es 0-11, sumamos 1

            if (mesNacimiento === mesActual) {
                // Es el mes de cumpleaños. Ahora verificamos en BD si ya lo usó este año.
                $.ajax({
                    url: './funciones/verificar_uso_cumpleanos.php', // ARCHIVO NUEVO
                    type: 'GET',
                    data: {
                        id_socio: idSocioGlobal
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (!resp.usado) {
                            alert("¡Feliz cumpleaños! Tienes un descuento especial por ser tu mes.");
                            $("#codigo_promocion").val("10W02Z95");
                            descuentoCumpleanosAplicado = true;
                            aplicarDescuentoPromocional("10W02Z95");
                            if (callback) callback(true);
                        } else {
                            // Ya usó el descuento este año
                            if (callback) callback(false);
                        }
                    },
                    error: function() {
                        console.log("Error verificando uso de cumpleaños");
                        if (callback) callback(false);
                    }
                });
            } else {
                if (callback) callback(false);
            }
        }

        function verificarDescuentosPromocionales(id_servicio) {
            $.ajax({
                url: "./funciones/verificar_descuentos_promocionales.php",
                data: {
                    id_servicio: id_servicio
                },
                dataType: 'json',
                success: function(resp) {
                    if (!resp.success) {
                        alert("El servicio seleccionado no permite descuentos promocionales.");
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                }
            });
        }

        // Evento cambio manual de código promo
        $("#codigo_promocion").change(function() {
            if (this.value) aplicarDescuentoPromocional(this.value);
        });

        // --------------------------------------------------------
        // LÓGICA DE MODALES (Integrantes / Pareja / Referidos)
        // --------------------------------------------------------

        function buscarClientesAjax(query, selectElement) {
            if (query.length <= 2) return;
            $.ajax({
                url: './funciones/buscar_clientes.php',
                data: {
                    q: query
                },
                success: function(data) {
                    var socios = JSON.parse(data);
                    var opts = '<option value="">Selecciona un socio</option>';
                    socios.forEach(function(s) {
                        opts += `<option value="${s.soc_id_socio}" data-fecha-inicial="${s.fecha_inicial}" data-fecha-fin="${s.fecha_fin}">${s.nombre}</option>`;
                    });
                    $(selectElement).html(opts);
                }
            });
        }

        $('#buscarIntegrante1').on('input', function() {
            buscarClientesAjax(this.value, '#integrante1');
        });
        $('#buscarIntegrante2').on('input', function() {
            buscarClientesAjax(this.value, '#integrante2');
        });
        $('#buscarPareja').on('input', function() {
            buscarClientesAjax(this.value, '#pareja');
        });

        // Calculo de fechas internas en modales
        function actualizarFechasModal(selectObj, inputIni, inputFin, divContainer) {
            var opt = $(selectObj).find(':selected');
            var fIni = opt.data('fecha-inicial');
            var fFin = opt.data('fecha-fin');
            var servicio = $('#servicio').val();

            if (fIni && fFin) {
                $(inputIni).val(fIni);
                $(inputFin).val(fFin); // Valor temporal
                $(divContainer).slideDown();

                // Recalcular vencimiento real basado en servicio actual
                // Usamos la logica de pet_socios_pagos pero para el integrante
                $.post("peticiones/pet_socios_pagos.php", {
                    fecha: fIni,
                    servicio: servicio,
                    envio: true
                }, function(datos) {
                    $(inputFin).val(datos);
                });

            } else {
                $(divContainer).slideUp();
            }
        }

        $('#integrante1').change(function() {
            actualizarFechasModal(this, '#pag_fecha_ini1', '#pag_fecha_fin1', '#seleccionFechas1');
        });
        $('#integrante2').change(function() {
            actualizarFechasModal(this, '#pag_fecha_ini2', '#pag_fecha_fin2', '#seleccionFechas2');
        });
        $('#pareja').change(function() {
            actualizarFechasModal(this, '#pag_fecha_ini_pareja', '#pag_fecha_fin_pareja', '#seleccionFechas');
        });

        // Botones guardar modal
        $('#guardarIntegrantes').click(function() {
            if (!$('#integrante1').val() || !$('#integrante2').val()) {
                alert("Selecciona ambos integrantes");
                return;
            }
            $('#modalSeleccionIntegrantes').modal('hide');
        });
        $('#guardarPareja').click(function() {
            if (!$('#pareja').val()) {
                alert("Selecciona la pareja");
                return;
            }
            $('#modalMensualidadPareja').modal('hide');
        });

        // Mostrar lista visual
        function mostrarIntegrantesSeleccionados() {
            var lista = $('#lista-integrantes');
            lista.empty();
            var textos = [];
            
            var i1 = $('#integrante1 option:selected').text();
            if (i1 && i1 !== 'Selecciona un socio') textos.push(i1);
            
            var i2 = $('#integrante2 option:selected').text();
            if (i2 && i2 !== 'Selecciona un socio') textos.push(i2);
            
            var ip = $('#pareja option:selected').text();
            if (ip && ip !== 'Selecciona un socio') textos.push(ip);

            if (textos.length > 0) {
                $('#selected-members').show();
                textos.forEach(t => lista.append('<li class="list-group-item">' + t + '</li>'));
            } else {
                $('#selected-members').hide();
            }
        }

        $('#modalSeleccionIntegrantes, #modalMensualidadPareja').on('hidden.bs.modal', mostrarIntegrantesSeleccionados);

        // --------------------------------------------------------
        // REFERIDOS
        // --------------------------------------------------------
        $('#tiene_referidos').change(function() {
            $('#referidos-section').toggle(this.checked);
        });
        $('#buscar-referido').click(function() {
            $('#modalReferidos').modal('show');
        });

        $('#search-input').on('keyup', function() {
            var q = $(this).val().trim();
            if (q.length < 3) return;
            $.ajax({
                url: './funciones/buscar_clientes.php',
                data: {
                    q: q
                },
                success: function(data) {
                    var socios = JSON.parse(data);
                    var ul = $('#lista-referidos');
                    ul.empty();
                    if (!socios.length) {
                        ul.append('<li class="list-group-item">Sin resultados</li>');
                        return;
                    }
                    socios.forEach(s => {
                        var li = $(`<li class="list-group-item list-group-item-action">${s.nombre}</li>`);
                        li.click(function() {
                            $('#referidos').val(s.soc_id_socio); // Asumimos ID como referencia
                            verificarTelefono(s.soc_id_socio);
                            $('#modalReferidos').modal('hide');
                        });
                        ul.append(li);
                    });
                }
            });
        });

        var debounceTimer;
        $('#referidos').on('input', function() {
            var val = $(this).val().trim();
            clearTimeout(debounceTimer);
            if (val.length >= 10 || /^\d+$/.test(val)) {
                debounceTimer = setTimeout(function() {
                    verificarTelefono(val);
                }, 500);
            }
        });

        function verificarTelefono(input) {
            var icono = $('#icono-validacion');
            $.ajax({
                url: './funciones/verificar_telefono.php',
                data: {
                    telefono: input,
                    id_socio: idSocioGlobal
                },
                success: function(resp) {
                    var data = JSON.parse(resp);
                    if (data.existe) {
                        if (data.referido) {
                            icono.removeClass().addClass('fas fa-times-circle text-danger');
                            alert('Este socio ya fue referido antes.');
                            $('#referidos').val('');
                            $('#codigo_promocion').val('');
                        } else {
                            icono.removeClass().addClass('fas fa-check-circle text-success');
                            $('#codigo_promocion').val("11d11l12");
                            aplicarDescuentoPromocional("11d11l12");
                        }
                    } else {
                        icono.removeClass().addClass('fas fa-times-circle text-danger');
                        alert('No encontrado.');
                    }
                }
            });
        }

        // --------------------------------------------------------
        // MONEDERO
        // --------------------------------------------------------
        $('#m_pago').change(function() {
            var metodo = $(this).val();
            if (metodo === 'M') {
                $.ajax({
                    url: './funciones/saldo_monedero.php',
                    data: {
                        id_socio: idSocioGlobal
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.success) {
                            var saldo = parseFloat(resp.saldo_monedero);
                            var total = parseFloat($("#subtotal").text()); // Usamos subtotal base o total con desc?
                            // Lo lógico es pagar el total final
                            var totalPagar = parseFloat($("#total").text());
                            if (isNaN(totalPagar)) totalPagar = total;

                            $('#monedero-section').show();
                            $('#saldo_monedero').val(saldo.toFixed(2));

                            if (saldo < totalPagar) {
                                $('#efectivo-section').show();
                                $('#cantidad_efectivo').val((totalPagar - saldo).toFixed(2));
                            } else {
                                $('#efectivo-section').hide();
                            }
                        }
                    }
                });
            } else {
                $('#efectivo-section, #monedero-section').hide();
            }
        });

    });
</script>
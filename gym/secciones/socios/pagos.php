<?php
$eliminar = request_var('eliminar', false);
$servicio = request_var('servicio', '');
$pag_fecha_pago = request_var('pag_fecha_pago', date('d-m-Y'));
$pag_fecha_ini = request_var('pag_fecha_ini', '');
$pag_fecha_fin = request_var('pag_fecha_fin', '');
$id_socio = request_var('id_socio', 0);
$integrante1 = request_var('integrante1', 0);
$integrante2 = request_var('integrante2', 0);
$pareja = request_var('pareja', 0);
$referidos = request_var('referidos', '');
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

//para el paginado
$pag_opciones = request_var('pag_opciones', 0);
$pag_busqueda = request_var('pag_busqueda', '');
$pag_fechai = request_var('pag_fechai', '');
$pag_fechaf = request_var('pag_fechaf', '');
$pag_item = request_var('item', '');
$pag_blq = request_var('blq', 0);
$pag_pag = request_var('pag', 0);

if ($pag_item)
    $volver .= "&i=$pag_item";

if ($pag_opciones)
    $volver .= "&pag_opciones=$pag_opciones";

if ($pag_busqueda)
    $volver .= "&pag_busqueda=$pag_busqueda";

if ($pag_fechai)
    $volver .= "&pag_fechai=$pag_fechai";

if ($pag_fechaf)
    $volver .= "&pag_fechaf=$pag_fechaf";

if ($pag_blq)
    $volver .= "&bql=$pag_blq";

if ($pag_pag)
    $volver .= "&pag=$pag_pag";

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

/*MEN PARCIAL solo se utiliza en socios es decir en s=socio y todos los item(i) que lo puedan contener. index,  js, funciones
configuracion, configuracion -> mensualidades*/
if ($servicio) {
    list($id_servicio, $meses) = explode('-', $servicio);

    $servicio_cve = obtener_servicio($id_servicio);

    $servicio_cve = $servicio_cve['clave'];

    if ($servicio_cve == 'MEN PARCIAL')
        $class_oculto = '';
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

//solo superadministrador
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
        <input type="hidden" id="id_socio" value="<?= $id_socio ?>" />
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="row">
                <label class="col-md-5">Fecha de pago</label>
                <div class="col-md-7">
                    <input type="text" class="form-control" value="<?= fecha_generica(date('d-m-Y')); ?>"
                        readonly="on" />
                </div>
            </div>

            <?= $op_fecha_pago ?>

            <div class="row">
                <label class="col-md-5">Servicio</label>
                <div class="col-md-7">
                    <select class="form-control" name="servicio" id="servicio"
                        onchange="calcular_servicio(); abrirModalSiPlanTresIntegrantes();" required>
                        <?= $servicios ?>
                    </select>
                </div>
            </div>
            <script>
                $(document).ready(function() {
                    $('#servicio').change(function() {
                        var servicioSeleccionado = $(this).val();

                        if (servicioSeleccionado === '123-1') {
                            $('#modalSeleccionIntegrantes').modal('show');
                        } else if (servicioSeleccionado === '167-1') {
                            $('#modalMensualidadPareja').modal('show');
                        }
                    });
                });
            </script>

            <!-- Modal Selección de Integrantes -->
            <div class="modal fade" id="modalSeleccionIntegrantes" tabindex="-1" role="dialog"
                aria-labelledby="modalSeleccionIntegrantesLabel" aria-hidden="true">
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

                            <!-- Integrante 1 -->
                            <div class="form-group">
                                <label for="buscarIntegrante1">Buscar Integrante 1:</label>
                                <input type="text" class="form-control" id="buscarIntegrante1"
                                    placeholder="Buscar socio...">
                                <select class="form-control mt-2" id="integrante1" name="integrante1">
                                    <option value="">Selecciona un socio</option>
                                </select>
                            </div>

                            <!-- Fechas para Integrante 1 -->
                            <div id="seleccionFechas1" style="display: none;">
                                <hr>
                                <div class="row">
                                    <label class="col-md-5">Fecha inicial Integrante 1</label>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="pag_fecha_ini1"
                                            id="pag_fecha_ini1" value="<?= $pag_fecha_ini1 ?>"
                                            onchange="calcular_servicio()" required="required" maxlength="10"
                                            autocomplete="off" readonly="on" />
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <label class="col-md-5">Fecha vencimiento Integrante 1</label>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="pag_fecha_fin1"
                                            id="pag_fecha_fin1" value="<?= $pag_fecha_fin1 ?>" autocomplete="off"
                                            readonly="on" />
                                    </div>
                                </div>
                            </div>

                            <!-- Integrante 2 -->
                            <div class="form-group mt-3">
                                <label for="buscarIntegrante2">Buscar Integrante 2:</label>
                                <input type="text" class="form-control" id="buscarIntegrante2"
                                    placeholder="Buscar socio...">
                                <select class="form-control mt-2" id="integrante2" name="integrante2">
                                    <option value="">Selecciona un socio</option>
                                </select>
                            </div>

                            <!-- Fechas para Integrante 2 -->
                            <div id="seleccionFechas2" style="display: none;">
                                <hr>
                                <div class="row">
                                    <label class="col-md-5">Fecha inicial Integrante 2</label>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="pag_fecha_ini2"
                                            id="pag_fecha_ini2" onchange="calcular_servicio()" required="required"
                                            maxlength="10" value="<?= $pag_fecha_ini2 ?>" autocomplete="off"
                                            readonly="on" />
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <label class="col-md-5">Fecha vencimiento Integrante 2</label>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="pag_fecha_fin2"
                                            id="pag_fecha_fin2" value="<?= $pag_fecha_fin2 ?>" autocomplete="off"
                                            readonly="on" />
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

            <script>
                $(document).ready(function() {
                    // Función para buscar integrantes y obtener sus datos
                    function buscarIntegrantes(input, select) {
                        var query = $(input).val();
                        if (query.length > 2) {
                            $.ajax({
                                url: './funciones/buscar_clientes.php',
                                method: 'GET',
                                data: {
                                    q: query
                                },
                                success: function(data) {
                                    var socios = JSON.parse(data);
                                    var options = '<option value="">Selecciona un socio</option>';
                                    socios.forEach(function(socio) {
                                        options +=
                                            `<option value="${socio.soc_id_socio}" data-fecha-inicial="${socio.fecha_inicial}" data-fecha-fin="${socio.fecha_fin}">${socio.nombre}</option>`;
                                    });
                                    $(select).html(options);
                                },
                                error: function() {
                                    alert('Error al buscar socios.');
                                }
                            });
                        }
                    }

                    // Manejar la búsqueda de integrantes
                    $('#buscarIntegrante1').on('input', function() {
                        buscarIntegrantes(this, '#integrante1');
                    });
                    $('#buscarIntegrante2').on('input', function() {
                        buscarIntegrantes(this, '#integrante2');
                    });

                    // Función para obtener el último pago de un socio
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
                                    callback(response.fecha_pago);
                                } else {
                                    console.error('Error al obtener la fecha del último pago:', response
                                        .error);
                                    callback(null);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error al obtener la fecha del último pago:', error);
                                callback(null);
                            }
                        });
                    }

                    // Función para calcular el servicio
                    function calcular_servicio(integranteSelect, fechaIniInput, fechaFinInput) {
                        var id_socio = $(integranteSelect).val();
                        var servicio = $('#servicio')
                            .val(); // Suponiendo que el servicio está en un select con ID "servicio"

                        if (!id_socio) return;

                        // Obtener el último pago antes de calcular el servicio
                        obtenerUltimoPago(id_socio, function(fecha_ini) {
                            if (fecha_ini) {
                                $(fechaIniInput).val(fecha_ini);
                                $.post("peticiones/pet_socios_pagos.php", {
                                    fecha: fecha_ini,
                                    servicio: servicio,
                                    envio: true
                                }, function(datos) {
                                    $(fechaFinInput).val(datos);
                                });
                            } else {
                                console.error('No se pudo obtener la fecha del último pago.');
                            }
                        });
                    }

                    // Función para actualizar fechas de integrantes
                    function actualizarFechas(integranteSelect, fechaIniInput, fechaFinInput, fechasContainer) {
                        var selectedOption = $(integranteSelect).find(':selected');
                        var fechaInicial = selectedOption.data('fecha-inicial') || '';
                        var fechaFin = selectedOption.data('fecha-fin') || '';

                        if (fechaInicial && fechaFin) {
                            $(fechaIniInput).val(fechaInicial);
                            $(fechaFinInput).val(fechaFin);
                            $(fechasContainer).slideDown();
                            calcular_servicio(integranteSelect, fechaIniInput, fechaFinInput);
                        } else {
                            $(fechaIniInput).val('');
                            $(fechaFinInput).val('');
                            $(fechasContainer).slideUp();
                        }
                    }

                    // Detectar cambios en los selects para actualizar fechas y calcular el servicio
                    $('#integrante1').change(function() {
                        actualizarFechas('#integrante1', '#pag_fecha_ini1', '#pag_fecha_fin1',
                            '#seleccionFechas1');
                    });

                    $('#integrante2').change(function() {
                        actualizarFechas('#integrante2', '#pag_fecha_ini2', '#pag_fecha_fin2',
                            '#seleccionFechas2');
                    });

                    // Validar antes de cerrar el modal
                    $('#guardarIntegrantes').click(function() {
                        var integrante1 = $('#integrante1').val();
                        var integrante2 = $('#integrante2').val();
                        var fechaIni1 = $('#pag_fecha_ini1').val();
                        var fechaFin1 = $('#pag_fecha_fin1').val();
                        var fechaIni2 = $('#pag_fecha_ini2').val();
                        var fechaFin2 = $('#pag_fecha_fin2').val();

                        if (!integrante1 || !integrante2) {
                            alert('Por favor, selecciona los 2 integrantes restantes.');
                            return;
                        }

                        if (!fechaIni1 || !fechaFin1 || !fechaIni2 || !fechaFin2) {
                            alert('Por favor, selecciona las fechas para ambos integrantes.');
                            return;
                        }

                        $('#modalSeleccionIntegrantes').modal('hide');
                    });
                });
            </script>




            <!-- Modal Selección de Pareja -->
            <div class="modal fade" id="modalMensualidadPareja" tabindex="-1" role="dialog"
                aria-labelledby="modalSeleccionParejaLabel" aria-hidden="true">
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

                            <!-- Integrante -->
                            <div class="form-group">
                                <label for="buscarPareja">Buscar Pareja:</label>
                                <input type="text" class="form-control" id="buscarPareja" placeholder="Buscar socio...">
                                <select class="form-control mt-2" id="pareja" name="pareja">
                                    <option value="">Selecciona un socio</option>
                                </select>
                            </div>

                            <!-- Fechas para Integrante -->
                            <div id="seleccionFechas" style="display: none;">
                                <hr>
                                <div class="row">
                                    <label class="col-md-5">Fecha inicial</label>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="pag_fecha_ini_pareja"
                                            id="pag_fecha_ini_pareja" onchange="calcular_servicio()" required="required"
                                            maxlength="10" value="<?= $pag_fecha_ini_pareja ?>" autocomplete="off"
                                            readonly="on" />
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <label class="col-md-5">Fecha vencimiento</label>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="pag_fecha_fin_pareja"
                                            id="pag_fecha_fin_pareja" value="<?= $pag_fecha_fin_pareja ?>"
                                            autocomplete="off" readonly="on" />
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

            <script>
                $(document).ready(function() {
                    function buscarPareja() {
                        var query = $('#buscarPareja').val();
                        if (query.length > 2) {
                            $.ajax({
                                url: './funciones/buscar_clientes.php',
                                method: 'GET',
                                data: {
                                    q: query
                                },
                                success: function(data) {
                                    var socios = JSON.parse(data);
                                    var options = '<option value="">Selecciona un socio</option>';
                                    socios.forEach(function(socio) {
                                        options +=
                                            `<option value="${socio.soc_id_socio}" data-fecha-inicial="${socio.fecha_inicial}" data-fecha-fin="${socio.fecha_fin}">${socio.nombre}</option>`;
                                    });
                                    $('#pareja').html(options);
                                },
                                error: function() {
                                    alert('Error al buscar socios.');
                                }
                            });
                        }
                    }

                    $('#buscarPareja').on('input', buscarPareja);

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
                                    callback(response.fecha_pago);
                                } else {
                                    callback(null);
                                }
                            },
                            error: function() {
                                callback(null);
                            }
                        });
                    }

                    function calcular_servicio() {
                        var id_socio = $('#pareja').val();
                        var servicio = $('#servicio').val();
                        if (!id_socio) return;

                        obtenerUltimoPago(id_socio, function(fecha_ini) {
                            if (fecha_ini) {
                                $('#pag_fecha_ini_pareja').val(fecha_ini);
                                $.post("peticiones/pet_socios_pagos.php", {
                                    fecha: fecha_ini,
                                    servicio: servicio,
                                    envio: true
                                }, function(datos) {
                                    $('#pag_fecha_fin_pareja').val(datos);
                                });
                            }
                        });
                    }

                    $('#pareja').change(function() {
                        var selectedOption = $(this).find(':selected');
                        var fechaInicial = selectedOption.data('fecha-inicial') || '';
                        var fechaFin = selectedOption.data('fecha-fin') || '';
                        if (fechaInicial && fechaFin) {
                            $('#pag_fecha_ini_pareja').val(fechaInicial);
                            $('#pag_fecha_fin_pareja').val(fechaFin);
                            $('#seleccionFechas').slideDown();
                            calcular_servicio();
                        } else {
                            $('#seleccionFechas').slideUp();
                        }
                    });

                    $('#guardarPareja').click(function() {
                        var pareja = $('#pareja').val();
                        var fechaIni = $('#pag_fecha_ini_pareja').val();
                        var fechaFin = $('#pag_fecha_fin_pareja').val();

                        if (!pareja) {
                            alert('Por favor, selecciona un integrante.');
                            return;
                        }
                        if (!fechaIni || !fechaFin) {
                            alert('Por favor, selecciona las fechas para el integrante.');
                            return;
                        }

                        $('#modalMensualidadPareja').modal('hide');
                    });
                });
            </script>




            <div class="row" id="selected-members" style="display: none;">
                <label class="col-md-5">Integrantes seleccionados</label>
                <div class="col-md-7">
                    <ul id="lista-integrantes" class="list-group">
                        <!-- Aquí se mostrarán los integrantes seleccionados -->
                    </ul>
                </div>
            </div>

            <script>
                $(document).ready(function() {
                    function mostrarIntegrantesSeleccionados() {
                        var integrantes = [];
                        var integrante1 = $('#integrante1 option:selected').text();
                        var integrante2 = $('#integrante2 option:selected').text();
                        var integrantePareja = $('#integrante option:selected').text();

                        if (integrante1 && integrante1 !== 'Selecciona un socio') integrantes.push(integrante1);
                        if (integrante2 && integrante2 !== 'Selecciona un socio') integrantes.push(integrante2);
                        if (integrantePareja && integrantePareja !== 'Selecciona un socio') integrantes.push(
                            integrantePareja);

                        var listaIntegrantes = $('#lista-integrantes');
                        listaIntegrantes.empty();

                        if (integrantes.length > 0) {
                            $('#selected-members').show();
                            integrantes.forEach(function(integrante) {
                                listaIntegrantes.append('<li class="list-group-item">' + integrante +
                                    '</li>');
                            });
                        } else {
                            $('#selected-members').hide();
                        }
                    }

                    $('#guardarIntegrantes, #guardarIntegrante').click(function() {
                        mostrarIntegrantesSeleccionados();
                    });

                    $('#modalSeleccionIntegrantes, #modalMensualidadPareja').on('hidden.bs.modal', function() {
                        mostrarIntegrantesSeleccionados();
                    });
                });
            </script>


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
                    <input type="text" class="form-control" name="pag_importe" maxlength="5"
                        value="<?= $pag_importe ?>" />
                </div>
            </div>

            <div class="row">
                <label class="col-md-5">Fecha inicial</label>
                <div class="col-md-7">
                    <input type="text" class="form-control" name="pag_fecha_ini" id="pag_fecha_ini"
                        onchange="calcular_servicio()" required="required" maxlength="10" value="<?= $pag_fecha_ini ?>"
                        autocomplete="off" readonly="on" />
                </div>
            </div>

            <div class="row">
                <label class="col-md-5">Fecha vencimiento</label>
                <div class="col-md-7">
                    <input type="text" class="form-control" name="pag_fecha_fin" id="pag_fecha_fin"
                        value="<?= $pag_fecha_fin ?>" autocomplete="off" readonly="on" />
                </div>
            </div>
            <div class="row">
                <label class="col-md-5">Código de Promoción</label>
                <div class="col-md-7">
                    <input type="text" class="form-control" name="codigo_promocion" id="codigo_promocion"
                        value="<?= $codigo_promocion ?>" autocomplete="off" />
                </div>
            </div>

            <!-- Checkbox para activar la sección de referidos -->
            <div class="row">
                <label class="col-md-5">¿Tiene referidos?</label>
                <div class="col-md-7">
                    <input type="checkbox" id="tiene_referidos" name="tiene_referidos" />
                </div>
            </div>

            <!-- Campo de Referidos con Botón de Búsqueda -->
            <div class="row" id="referidos-section" style="display: none;">
                <label class="col-md-5">Captura de Referidos</label>
                <div class="col-md-5">
                    <div class="input-group">
                        <input type="text" class="form-control" id="referidos" name="referidos"
                            placeholder="Ingrese el teléfono" maxlength="10">
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

            <!-- Modal para Seleccionar Usuario -->
            <div class="modal fade" id="modalReferidos" tabindex="-1" role="dialog"
                aria-labelledby="modalReferidosLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalReferidosLabel">Buscar Usuario</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="text" id="search-input" class="form-control"
                                placeholder="Ingrese número de teléfono">
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

            <input type="hidden" name="id_socio" value="<?= $id_socio ?>" />
            <input type="submit" name="enviar" value="Cobrar y guardar" class="btn btn-primary" />
            <input type="button" name="Regresar" value="Regresar" class="btn btn-default"
                onclick="location.href='<?= $volver ?>'" />
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
                        // Establecer el valor del campo de fecha inicial
                        document.getElementById('pag_fecha_ini').value = response.fecha_pago;

                        // Habilitar o deshabilitar el campo según la bandera habilitar_fecha
                        if (response.habilitar_fecha) {
                            // Habilitar el campo de fecha
                            document.getElementById('pag_fecha_ini').removeAttribute('readonly');
                            document.getElementById('pag_fecha_ini').setAttribute('autocomplete', 'on');
                        } else {
                            // Mantener el campo de fecha bloqueado
                            document.getElementById('pag_fecha_ini').setAttribute('readonly', 'on');
                            document.getElementById('pag_fecha_ini').setAttribute('autocomplete',
                                'off');
                        }

                        // Ejecutar el callback con la fecha obtenida
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



        function calcular_servicio() {
            var servicio = document.getElementById('servicio').value;
            var id_socio = document.getElementById('id_socio').value;
            var fecha_ini_input = document.getElementById('pag_fecha_ini');

            // Mostrar u ocultar el importe según el servicio seleccionado
            if (servicio == '5-1') {
                document.getElementById('importe').style.display = 'block';
            } else {
                document.getElementById('importe').style.display = 'none';
            }

            // Función para calcular el servicio, ya sea con la fecha automática o manual
            function calcularServicioConFecha(fecha_ini) {
                $.post("peticiones/pet_socios_pagos.php", {
                    fecha: fecha_ini,
                    servicio: servicio,
                    envio: true
                }, function(datos) {
                    document.getElementById('pag_fecha_fin').value = datos;
                    obtenerCuotaServicio(); // Calcular los totales después de obtener la fecha de fin
                });
            }

            // Verificar si el campo de fecha está habilitado para selección manual
            if (!fecha_ini_input.readOnly) {
                // Si el campo no está en modo 'readonly', usa la fecha seleccionada manualmente
                calcularServicioConFecha(fecha_ini_input.value);
            } else {
                // Si el campo es 'readonly', obtener la fecha del último pago de la base de datos
                obtenerUltimoPago(id_socio, function(fecha_ini) {
                    if (fecha_ini) {
                        fecha_ini_input.value = fecha_ini; // Establecer la fecha inicial obtenida
                        calcularServicioConFecha(fecha_ini);
                    } else {
                        console.error('No se pudo obtener la fecha del último pago.');
                    }
                });
            }
        }


        // Asignar la función calcular_servicio al evento onchange del select de servicio
        document.getElementById('servicio').onchange = calcular_servicio;

        function obtenerCuotaServicio() {
            var servicioSeleccionado = document.getElementById("servicio").value;
            var id_servicio = servicioSeleccionado.split('-')[0];

            if (!id_servicio) {
                console.error("Error: El id_servicio es inválido.");
                return;
            }

            var xhr = new XMLHttpRequest();

            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        var respuesta = JSON.parse(xhr.responseText);

                        if (respuesta.success) {
                            var cuota = parseFloat(respuesta.cuota);

                            // Mostrar la cuota sin aplicar ningún descuento
                            document.getElementById("subtotal").textContent = cuota.toFixed(2);

                            // Verificar si es el mes de cumpleaños del cliente
                            verificarCumpleanos();

                            // Si no es el mes de cumpleaños, verificar y aplicar otros descuentos
                            if (!descuentoCumpleanosAplicado) {
                                // Verificar si el cliente tiene un descuento almacenado
                                var descuentoCliente = parseFloat(
                                    <?= json_encode($nombre['soc_descuento']); ?>);
                                if (!isNaN(descuentoCliente)) {
                                    aplicarDescuentoCliente(descuentoCliente);
                                } else {
                                    document.getElementById("descuento").textContent = '0.00';
                                    document.getElementById("total").textContent = cuota.toFixed(2);
                                }
                            }
                        } else {
                            console.error("Error al obtener la cuota del servicio:", respuesta.error);
                        }
                    } else {
                        console.error('Error al realizar la solicitud:', xhr.status);
                    }
                }
            };

            xhr.open("GET", "./funciones/obtener_cuota_servicio.php?id_servicio=" + id_servicio, true);
            xhr.send();
        }

        function aplicarDescuentoCliente(descuentoCliente) {
            if (descuentoCumpleanosAplicado) return;

            var cuota = parseFloat(document.getElementById("subtotal").textContent);
            var montoDescontadoCliente = cuota * (descuentoCliente / 100);
            var totalConDescuentoCliente = cuota - montoDescontadoCliente;

            document.getElementById("descuento").textContent = montoDescontadoCliente.toFixed(2);
            document.getElementById("total").textContent = totalConDescuentoCliente.toFixed(2);
        }

        function aplicarDescuentoPromocional(codigo_promocion) {
            if (descuentoCumpleanosAplicado) return;

            var servicioSeleccionado = document.getElementById("servicio").value;
            var id_servicio = servicioSeleccionado.split('-')[0];

            verificarDescuentosPromocionales(id_servicio);

            var xhrPromocion = new XMLHttpRequest();
            xhrPromocion.onreadystatechange = function() {
                if (xhrPromocion.readyState === XMLHttpRequest.DONE) {
                    if (xhrPromocion.status === 200) {
                        var respuestaPromocion = JSON.parse(xhrPromocion.responseText);
                        if (respuestaPromocion.success) {
                            var descuentoPromocion = parseFloat(respuestaPromocion.porcentaje_descuento);
                            var cuota = parseFloat(document.getElementById("subtotal").textContent);

                            var descuentoTotal = 0;

                            var descuentoCliente = parseFloat(<?= json_encode($nombre['soc_descuento']); ?>);
                            if (!isNaN(descuentoCliente)) {
                                descuentoTotal += descuentoCliente;
                            }

                            descuentoTotal += descuentoPromocion;

                            var montoDescontadoTotal = cuota * (descuentoTotal / 100);
                            var totalConDescuentoTotal = cuota - montoDescontadoTotal;

                            document.getElementById("descuento").textContent = montoDescontadoTotal.toFixed(2);
                            document.getElementById("total").textContent = totalConDescuentoTotal.toFixed(2);
                        } else {
                            alert("Error: " + respuestaPromocion.error);
                        }
                    } else {
                        console.error('Error al realizar la solicitud para verificar el código promocional:',
                            xhrPromocion.status);
                    }
                }
            };

            xhrPromocion.open("GET", "./funciones/verificar_codigo_promocional.php?codigo_promocion=" +
                codigo_promocion, true);
            xhrPromocion.send();
        }

        function verificarCumpleanos() {

            var fechaNacimientoString = "<?= $nombre['soc_fecha_nacimiento']; ?>";

            var fechaNacimiento = new Date(fechaNacimientoString + "T00:00:00");

            var fechaActual = new Date();

            if (fechaNacimiento.getMonth() === fechaActual.getMonth()) {
                alert("¡Feliz cumpleaños! Tienes un descuento especial.");
                document.getElementById("codigo_promocion").value = "22M40G20";
                aplicarDescuentoPromocional("22M40G20");
                descuentoCumpleanosAplicado = true;
            } else {
            }
        }

        function verificarDescuentosPromocionales(id_servicio) {
            if (descuentoCumpleanosAplicado) return;

            var xhrDescuentos = new XMLHttpRequest();
            xhrDescuentos.onreadystatechange = function() {
                if (xhrDescuentos.readyState === XMLHttpRequest.DONE) {
                    if (xhrDescuentos.status === 200) {
                        var respuestaDescuentos = JSON.parse(xhrDescuentos.responseText);
                        if (!respuestaDescuentos.success) {
                            // Mostrar una alerta si el servicio no tiene descuentos promocionales permitidos
                            alert("El servicio seleccionado no tiene descuentos promocionales permitidos.");

                            // Recargar la página después de 2 segundos
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    } else {
                        console.error(
                            'Error al realizar la solicitud para verificar los descuentos promocionales:',
                            xhrDescuentos.status);
                    }
                }
            };

            xhrDescuentos.open("GET", "./funciones/verificar_descuentos_promocionales.php?id_servicio=" +
                id_servicio, true);
            xhrDescuentos.send();
        }

        $(document).ready(function() {
            // Mostrar u ocultar la sección de referidos
            $('#tiene_referidos').change(function() {
                $('#referidos-section').toggle(this.checked);
            });

            // Evento para abrir el modal de búsqueda
            $('#buscar-referido').click(function() {
                $('#modalReferidos').modal('show');
            });

            let debounceTimer;

            function verificarTelefono(input) {
                let icono = $('#icono-validacion');
                var idSocio = document.getElementById('id_socio').value;


                if (input) {
                    let esTelefono = /^\d{10}$/.test(input);
                    let esID = /^\d+$/.test(input);

                    if (esTelefono || esID) {
                        $.ajax({
                            url: './funciones/verificar_telefono.php',
                            method: 'GET',
                            data: {
                                telefono: input,
                                id_socio: idSocio // Enviar id_socio en la solicitud si está disponible
                            },
                            success: function(response) {
                                try {
                                    let data = JSON.parse(response);


                                    if (data.existe) {
                                        if (data.referido) {
                                            // Si el usuario ya es un referido, marcar como error y deshabilitar input
                                            icono.removeClass().addClass('fas fa-times-circle text-danger');
                                            alert('El Socio ya ha sido registrado como referido.');

                                            // Deshabilitar el campo de teléfono para evitar cambios
                                            $('#referidos').prop('disabled', true);
                                            $('#referidos').val('');
                                            $('#codigo_promocion').val('');

                                        } else {
                                            // Si el usuario existe y NO es referido, aplicar descuento y mantener el input habilitado
                                            icono.removeClass().addClass('fas fa-check-circle text-success');

                                            let codigoPromocion = "11d11l12";
                                            $('#codigo_promocion').val(codigoPromocion);
                                            aplicarDescuentoPromocional(codigoPromocion);

                                            // Mantener el campo habilitado
                                            $('#referidos').prop('disabled', false);
                                        }
                                    } else {
                                        // Si el usuario no existe, marcar error y dejar habilitado el input
                                        icono.removeClass().addClass('fas fa-times-circle text-danger');
                                        alert('El número o ID ingresado no está registrado. Use la búsqueda.');
                                        $('#referidos').val('');
                                        $('#codigo_promocion').val('');
                                        $('#telefono').prop('disabled', false);
                                    }
                                } catch (error) {
                                    console.error("Error al procesar la respuesta del servidor:", error, response);
                                    alert('Error en la respuesta del servidor.');
                                }
                            },
                            error: function() {
                                alert('Error al verificar el número.');
                            }
                        });
                    } else {
                        icono.removeClass().addClass('fas fa-circle text-muted');
                    }
                } else {
                    icono.removeClass().addClass('fas fa-circle text-muted');
                }



            }




            // Evento de entrada en el campo de teléfono/ID
            $('#referidos').on('input', function() {
                let input = $(this).val().trim();

                // Limpiar el temporizador anterior si existe
                clearTimeout(debounceTimer);

                // Solo realizar la validación si el campo tiene un valor de teléfono válido o ID
                if (input) {
                    // Si es un número de teléfono válido (10 dígitos) o un ID válido (solo números)
                    let esTelefono = /^\d{10}$/.test(
                        input); // Verifica si es un teléfono de 10 dígitos
                    let esID = /^\d+$/.test(input); // Verifica si es un ID (solo números)

                    // Solo realizar la consulta cuando el número esté completo
                    if (esTelefono || esID) {
                        // Agregar un retraso para evitar enviar la solicitud mientras el usuario escribe
                        debounceTimer = setTimeout(function() {
                            verificarTelefono(
                                input); // Llamar a la función para verificar el teléfono o ID
                        }, 500); // Espera 500 ms después de que el usuario deje de escribir
                    } else {
                        icono.removeClass().addClass('fas fa-circle text-muted'); // Icono neutral
                    }
                } else {
                    icono.removeClass().addClass('fas fa-circle text-muted'); // Icono neutral
                }
            });

            // Buscar usuarios en el modal
            $('#search-input').on('keyup', function() {
                let query = $(this).val().trim();
                if (query.length < 3) return;

                $.ajax({
                    url: './funciones/buscar_clientes.php',
                    method: 'GET',
                    data: {
                        q: query
                    },
                    success: function(data) {
                        let lista = $('#lista-referidos');
                        lista.empty();

                        let socios = JSON.parse(data);
                        if (socios.length === 0) {
                            lista.append(
                                '<li class="list-group-item">No se encontraron resultados.</li>'
                            );
                            return;
                        }

                        socios.forEach(socio => {
                            let li = $(
                                `<li class="list-group-item list-group-item-action">${socio.nombre} - ${socio.soc_id_socio}</li>`
                            );
                            li.data('telefono', socio.soc_id_socio);
                            li.click(function() {
                                // Al seleccionar un referido, asignar su ID o teléfono al campo
                                $('#referidos').val($(this).data(
                                    'telefono'));

                                // Verificar el número o ID al seleccionar el referido
                                verificarTelefono($(this).data(
                                    'telefono'));

                                // Cerrar el modal
                                $('#modalReferidos').modal('hide');
                            });
                            lista.append(li);
                        });
                    },
                    error: function() {
                        alert('Error al buscar referidos.');
                    }
                });
            });
        });

        document.getElementById("codigo_promocion").onchange = function() {
            var codigo_promocion = this.value;
            if (codigo_promocion) {
                aplicarDescuentoPromocional(codigo_promocion);
            }
        };

        // Función para manejar el cambio de método de pago
        $('#m_pago').change(function() {
            var metodoPago = $(this).val();
            if (metodoPago === 'M') {
                var idSocio = $('#id_socio').val();
                $.ajax({
                    url: './funciones/saldo_monedero.php',
                    type: 'GET',
                    data: {
                        id_socio: idSocio
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var saldoMonedero = parseFloat(response.saldo_monedero);
                            var importeServicio = parseFloat(document.getElementById("subtotal")
                                .textContent);

                            if (saldoMonedero < importeServicio) {
                                $('#efectivo-section').show();
                                $('#monedero-section').show();
                                $('#saldo_monedero').val(saldoMonedero.toFixed(2));

                                var cantidadFaltante = importeServicio - saldoMonedero;
                                $('#cantidad_efectivo').val(cantidadFaltante.toFixed(2));
                            } else {
                                $('#efectivo-section').hide();
                                $('#monedero-section').show();
                                $('#saldo_monedero').val(saldoMonedero.toFixed(2));
                            }
                        } else {
                            console.error('Error al obtener el saldo del monedero:', response
                                .error);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al obtener el saldo del monedero:', error);
                    }
                });
            } else {
                $('#efectivo-section').hide();
                $('#monedero-section').hide();
            }
        });
    });
</script>
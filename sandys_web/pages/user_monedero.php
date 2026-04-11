<?php
// --- 1. INICIAR SESIÓN (SOLO SI NO ESTÁ ACTIVA) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. VERIFICAR ACCESO ---
if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['soc_id_socio'])) {
    header('Location: index.php?page=login&error=session_expired');
    exit;
}

// --- 3. INCLUIR CONEXIÓN A BD ---
if (!isset($conn) || !$conn instanceof PDO) {
    die("<div style='color:white; text-align:center; padding:50px;'>Error crítico: No se pudo establecer la conexión con la base de datos.</div>");
}

// --- 4. OBTENER ID DEL SOCIO DE LA SESIÓN ---
$id_socio = (int) $_SESSION['admin']['soc_id_socio'];
$id_empresa = 1;

// --- 5. OBTENER DATOS DEL MONEDERO DEL SOCIO ---
function obtener_prepago_pdo($conexion_pdo, $id_socio, $id_empresa)
{
    try {
        $query = "SELECT CONCAT(soc_apepat, ' ', soc_apemat, ' ', soc_nombres) AS nombre,
                         soc_mon_saldo AS saldo,
                         soc_id_socio AS id_socio
                  FROM san_socios
                  WHERE soc_id_socio = :socioId 
                  AND soc_id_empresa = :empresaId LIMIT 1";

        $stmt = $conexion_pdo->prepare($query);
        $stmt->bindParam(':socioId', $id_socio, PDO::PARAM_INT);
        $stmt->bindParam(':empresaId', $id_empresa, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error DB al obtener saldo de monedero: " . $e->getMessage());
        return false;
    }
}

$prepago = obtener_prepago_pdo($conn, $id_socio, $id_empresa);

if (!$prepago) {
    echo "<div style='color:white; text-align:center; padding:100px;'>Error: No se pudieron cargar los datos de tu monedero.</div>";
    exit;
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<style>
    /* Base */
    body {
        background-color: #050505 !important;
        color: #e0e0e0;
        font-family: 'Muli', sans-serif;
    }

    .membership-payment {
        padding: 120px 0 60px;
        min-height: 100vh;
    }

    /* 🔥 Títulos (Corrección Móvil) 🔥 */
    .page-title {
        font-family: 'Oswald', sans-serif;
        font-size: 32px;
        color: #fff;
        text-transform: uppercase;
        margin-bottom: 5px;
        letter-spacing: 1px;
        word-wrap: break-word;
        overflow-wrap: break-word;
        line-height: 1.2;
        /* Evita que las letras se encimen */
    }

    .page-subtitle {
        color: #9ca3af;
        font-size: 15px;
        margin-bottom: 30px;
    }

    /* Tarjetas */
    .payment-card,
    .summary-card,
    .history-card {
        background-color: #121212;
        border: 1px solid #2a2a2a;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        margin-bottom: 25px;
    }

    .card-header-title {
        font-family: 'Oswald', sans-serif;
        font-size: 20px;
        color: #fff;
        text-transform: uppercase;
        margin-bottom: 20px;
        border-bottom: 1px solid #333;
        padding-bottom: 12px;
    }

    /* 🔥 Inputs (Corrección Textos Largos) 🔥 */
    .form-label {
        color: #bbb;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 6px;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control {
        background-color: #1a1a1a !important;
        border: 1px solid #444 !important;
        color: #ffffff !important;
        border-radius: 8px !important;
        padding: 12px 15px !important;
        height: auto !important;
        font-size: 15px !important;
        box-shadow: none !important;
        width: 100%;
    }

    .form-control:focus {
        border-color: #ef4444 !important;
        background-color: #121212 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
        outline: none;
    }

    /* Elipsis para que "URIBE xd xd..." no rompa la pantalla */
    .form-control[readonly] {
        background-color: #0a0a0a !important;
        color: #888888 !important;
        border-color: #222 !important;
        cursor: not-allowed;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .input-saldo {
        color: #10b981 !important;
        font-weight: bold;
        font-size: 18px !important;
    }

    /* Botones */
    .primary-btn {
        background-color: #ef4444;
        color: #fff;
        border: none;
        padding: 14px;
        border-radius: 8px;
        font-family: 'Oswald', sans-serif;
        text-transform: uppercase;
        font-size: 16px;
        letter-spacing: 1px;
        font-weight: 700;
        width: 100%;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .primary-btn:hover {
        background-color: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.5);
        color: #fff;
    }

    /* Resumen y Seguridad */
    .list-group-item {
        background-color: transparent !important;
        border-color: #333 !important;
        padding: 12px 0;
        color: #ddd;
    }

    .summary-total {
        font-size: 22px;
        color: #ef4444;
        font-weight: bold;
    }

    .security-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: rgba(34, 197, 94, 0.05);
        border: 1px dashed rgba(34, 197, 94, 0.3);
        padding: 12px;
        border-radius: 10px;
        margin-top: 20px;
    }

    .security-badge i {
        font-size: 20px;
        color: #10b981;
    }

    .security-text {
        font-size: 12px;
        color: #a7f3d0;
        margin: 0;
        line-height: 1.3;
    }

    /* DATATABLES DARK MODE OVERRIDES */
    table.dataTable {
        border-collapse: collapse !important;
        width: 100% !important;
        color: #e0e0e0;
        font-size: 14px;
    }

    table.dataTable thead th {
        border-bottom: 2px solid #333 !important;
        color: #fff;
        font-family: 'Oswald', sans-serif;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
        padding: 10px;
    }

    table.dataTable tbody tr {
        background-color: #121212 !important;
    }

    table.dataTable tbody tr:hover {
        background-color: #1a1a1a !important;
    }

    table.dataTable tbody td {
        border-top: 1px solid #222 !important;
        border-bottom: 1px solid #222 !important;
        padding: 10px;
        vertical-align: middle;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_processing,
    .dataTables_wrapper .dataTables_paginate {
        color: #bbb !important;
        font-size: 13px;
        margin-bottom: 10px;
    }

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        background-color: #1a1a1a;
        border: 1px solid #444;
        color: #fff;
        border-radius: 4px;
        padding: 4px 8px;
        margin-left: 8px;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
        outline: none;
        border-color: #ef4444;
    }

    .page-item.disabled .page-link {
        background-color: #1a1a1a !important;
        border-color: #333 !important;
        color: #555 !important;
    }

    .page-item .page-link {
        background-color: #121212 !important;
        border-color: #333 !important;
        color: #bbb !important;
    }

    .page-item.active .page-link {
        background-color: #ef4444 !important;
        border-color: #ef4444 !important;
        color: #fff !important;
    }

    .badge-suma {
        background-color: rgba(16, 185, 129, 0.1);
        color: #10b981;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 12px;
        border: 1px solid rgba(16, 185, 129, 0.3);
        display: inline-block;
    }

    .badge-resta {
        background-color: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 12px;
        border: 1px solid rgba(239, 68, 68, 0.3);
        display: inline-block;
    }

    table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before,
    table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control:before {
        background-color: #ef4444 !important;
        border: 2px solid #121212 !important;
    }

    @media (max-width: 991px) {
        .membership-payment {
            padding: 100px 0 30px;
        }

        .page-title {
            font-size: 24px;
        }
    }

    /* Botón de Regresar Estilo Frontend (Dark Mode) */
    .btn-back {
        display: inline-flex;
        align-items: center;
        background: #1a1a1a;
        color: #ffffff;
        border: 1px solid #333;
        padding: 8px 20px;
        border-radius: 50px;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-bottom: 25px;
        font-size: 14px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-back:hover {
        background: #ef4444;
        /* Rojo Sandy's */
        color: #fff;
        border-color: #ef4444;
        transform: translateX(-5px);
        text-decoration: none;
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
    }

    .btn-back i {
        margin-right: 8px;
    }
    .saldo-display-container {
        background: linear-gradient(145deg, #1a1a1a 0%, #0a0a0a 100%);
        border: 1px solid #333;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        margin-bottom: 25px;
        box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);
    }

    .form-label-saldo {
        color: #888;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 8px;
        display: block;
    }

    .input-saldo-resaltado {
        background: transparent !important;
        border: none !important;
        color: #10b981 !important; /* Verde Neón Sandy's */
        font-family: 'Oswald', sans-serif;
        font-size: 42px !important; /* Tamaño aumentado considerablemente */
        font-weight: 700 !important;
        text-align: center !important;
        width: 100%;
        height: auto !important;
        padding: 0 !important;
        letter-spacing: 1px;
        text-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
    }

    /* Quitar el borde azul de enfoque en este campo específico */
    .input-saldo-resaltado:focus {
        box-shadow: none !important;
        outline: none !important;
    }
</style>

<section class="membership-payment">
    <div class="container-fluid" style="max-width: 1400px;">
        <div class="row mb-3">
            <div class="col-12 text-center text-lg-left pl-lg-4">
                <h2 class="page-title">Tu Monedero Electrónico</h2>
                <p class="page-subtitle">Recarga saldo de forma segura y consulta tu historial de movimientos.</p>
            </div>
        </div>
        <div class="row">
            <div class="col-12 mb-3">
                <a href="index.php?page=user_home" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Volver al Inicio
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-5 col-xl-4">

                <div class="payment-card">
                    <h3 class="card-header-title">Realizar Recarga</h3>
                    <div id="mensajeError" class="alert alert-danger"
                        style="display: none; background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; font-size:13px;">
                    </div>

                    <form id="pagoMonederoForm">
                        <input type="hidden" id="id_socio" value="<?= $prepago['id_socio'] ?>" />

                        <div class="form-group mb-3">
                            <label for="nombre_socio" class="form-label">Socio Titular</label>
                            <input type="text" id="nombre_socio" class="form-control"
                                value="<?= htmlspecialchars($prepago['nombre']); ?>" readonly>
                        </div>

                        <div class="saldo-display-container">
                            <label for="saldo_actual" class="form-label-saldo">Saldo Disponible</label>
                            <input type="text" id="saldo_actual" class="input-saldo-resaltado"
                                value="$<?= number_format($prepago['saldo'], 2); ?>" readonly>
                        </div>

                        <div class="form-group mb-4">
                            <label for="prep_importe" class="form-label">Monto a Recargar (MXN)</label>
                            <input type="number" step="0.01" min="1" id="prep_importe" name="prep_importe"
                                class="form-control" required placeholder="0.00" oninput="actualizarResumen()">
                        </div>

                        <div id="boton-container">
                            <button id="btn-generar-pago" type="button" class="primary-btn">
                                Pagar con MP <i class="fas fa-wallet ml-2"></i>
                            </button>
                        </div>

                        <div class="mt-3">
                            <div id="wallet_container"></div>
                        </div>
                    </form>
                </div>

                <div class="summary-card">
                    <h4 class="card-header-title" style="font-size: 18px;">Resumen</h4>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center"
                            style="border-bottom: none !important;">
                            <span
                                style="font-size: 14px; font-weight: 700; color:#fff; text-transform: uppercase;">Total
                                a Pagar</span>
                            <strong id="previewTotal" class="summary-total">$0.00</strong>
                        </li>
                    </ul>
                    <div class="security-badge">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <p class="security-text" style="font-weight: bold; color: #fff;">Pago seguro y encriptado
                            </p>
                            <p class="security-text">Procesado por Mercado Pago</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-7 col-xl-8">
                <div class="history-card" style="height: 100%;">
                    <h3 class="card-header-title"><i class="fas fa-history mr-2"></i> Historial de Movimientos</h3>

                    <div class="table-responsive-custom mt-3">
                        <table id="historial-prepago" class="table table-hover dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Descripción</th>
                                    <th class="text-right">Importe</th>
                                    <th class="text-right">Saldo</th>
                                    <th class="text-center">Tipo</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
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
</section>
<link href="https://cdn.datatables.net/v/bs4/dt-1.13.6/r-2.5.0/datatables.min.css" rel="stylesheet">

<script>
    (function () {
        // --- Lógica del Resumen visual ---
        window.actualizarResumen = function () {
            const importeInput = document.getElementById('prep_importe').value;
            const totalElement = document.getElementById('previewTotal');

            let monto = parseFloat(importeInput);
            if (isNaN(monto) || monto < 0) { monto = 0; }

            const formatoMoneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(monto);
            totalElement.textContent = formatoMoneda;
        };

        // --- Lógica del Botón (AHORA CON JQUERY Y PREVENT DEFAULT) ---
        function inicializarEventos() {
            console.log("Activando botón de Mercado Pago..."); // Esto debe salir en tu consola (F12)

            $('#btn-generar-pago').on('click', function (e) {
                e.preventDefault(); // Evita cualquier comportamiento nativo del formulario

                console.log("¡Botón clickeado!"); // Confirma que el evento funciona

                const importe = $('#prep_importe').val();
                const idSocio = $('#id_socio').val();

                if (!importe || parseFloat(importe) <= 0) {
                    $('#mensajeError').text("Ingresa un monto válido a recargar.").show();
                    return;
                }
                $('#mensajeError').hide();

                // Cambiar estado del botón a cargando
                const $btn = $(this);
                const originalText = $btn.html();
                $btn.html('Generando... <i class="fas fa-spinner fa-spin ml-2"></i>');
                $btn.prop('disabled', true);

                // Llamada AJAX para crear la preferencia de Mercado Pago
                $.ajax({
                    type: "POST",
                    url: "api/procesar_recarga_monedero.php",
                    data: { importe: importe, id_socio: idSocio },
                    dataType: "json",
                    success: function (response) {
                        console.log("Respuesta del servidor:", response);

                        if (response.status === 'success' && response.url) {
                            window.location.href = response.url;
                        } else {
                            $('#mensajeError').text(response.message || 'Error al generar el pago.').show();
                            $btn.html(originalText);
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function (xhr) {
                        console.error("Error del servidor: ", xhr.responseText);
                        $('#mensajeError').text('Error de conexión con el servidor.').show();
                        $btn.html(originalText);
                        $btn.prop('disabled', false);
                    }
                });
            });
        }

        // --- Lógica de DataTables ---
        function iniciarTabla() {
            $('#historial-prepago').DataTable({
                "processing": true,
                "serverSide": true,
                "responsive": true,
                "pageLength": 5,
                "lengthMenu": [5, 10, 25],
                "order": [[0, "desc"]],
                "ajax": {
                    "url": "api/api_prepago_detalle.php",
                    "type": "POST",
                    "data": function (d) {
                        d.id_socio = $('#id_socio').val();
                    }
                },
                "columns": [
                    { "data": "id_pdetalle" },
                    { "data": "p_descripcion" },
                    { "data": "importe", "className": "text-right" },
                    { "data": "saldo", "className": "text-right" },
                    {
                        "data": "movimiento",
                        "className": "text-center",
                        "render": function (data, type, row) {
                            if (data === 'Suma') {
                                return '<span class="badge-suma"><i class="fas fa-arrow-up mr-1"></i> Suma</span>';
                            } else if (data === 'Resta') {
                                return '<span class="badge-resta"><i class="fas fa-arrow-down mr-1"></i> Resta</span>';
                            }
                            return data;
                        }
                    },
                    { "data": "fecha" },
                    { "data": "hora" }
                ],
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json"
                }
            });
        }

        function cargarDataTables() {
            if ($.fn.DataTable) {
                iniciarTabla();
                return;
            }

            $.getScript("https://cdn.datatables.net/v/bs4/dt-1.13.6/r-2.5.0/datatables.min.js", function () {
                iniciarTabla();
            });
        }

        // Vigilamos hasta que jQuery despierte
        var intentos = 0;
        var checkJquery = setInterval(function () {
            if (window.jQuery) {
                clearInterval(checkJquery);
                inicializarEventos();
                cargarDataTables();
            }
            intentos++;
            if (intentos > 150) clearInterval(checkJquery);
        }, 100);
    })();
</script>
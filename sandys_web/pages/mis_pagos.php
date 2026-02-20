<?php
// ---------------------------------------------
//  Mis Pagos - Historial del Socio (Dark Mode + Card View Responsive)
// ---------------------------------------------

require_once __DIR__ . '/../conn.php';

$idSocio = $_SESSION['admin']['soc_id_socio'] ?? 0;

if ($idSocio <= 0) {
    echo "<div style='color:white; text-align:center; padding:150px 20px 50px;'>Debes iniciar sesión para ver tus pagos.</div>";
    return; 
}

try {
    $sql = "
        SELECT 
            p.pag_id_pago,
            p.pag_fecha_pago,
            p.pag_importe,
            p.pag_referencia_mp,
            p.pag_tipo_pago,
            p.pag_fecha_ini,
            p.pag_fecha_fin,
            s.ser_descripcion
        FROM san_pagos p
        LEFT JOIN san_servicios s ON p.pag_id_servicio = s.ser_id_servicio
        WHERE p.pag_id_socio = :idSocio
        ORDER BY p.pag_id_pago DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idSocio' => $idSocio]);
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "<div style='color:white; text-align:center; padding:150px 20px 50px;'>Error: " . $e->getMessage() . "</div>";
    $pagos = [];
}
?>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">

<style>
    /* --- 1. FONDO Y CONTENEDOR --- */
    .payments-wrapper {
        background-color: #050505;
        min-height: 100vh;
        /* 🔥 SOLUCIÓN: Padding superior de 140px para esquivar el navbar flotante 🔥 */
        padding: 140px 20px 60px; 
        font-family: 'Muli', sans-serif;
        color: #e0e0e0;
    }
    .payments-container { max-width: 1100px; margin: 0 auto; }

    /* --- 2. HEADER Y BOTÓN NUEVO --- */
    .page-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 25px; flex-wrap: wrap; gap: 15px;
    }
    .page-title h2 { font-family: 'Oswald', sans-serif; font-size: 32px; color: #fff; margin: 0; letter-spacing: 1px; }
    .page-title p { color: #9ca3af; font-size: 14px; margin: 5px 0 0 0; }

    .btn-new-payment {
        background-color: #ef4444; color: #fff; padding: 12px 24px;
        border-radius: 8px; text-decoration: none; font-weight: bold;
        font-size: 14px; display: inline-flex; align-items: center; gap: 8px;
        transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }
    .btn-new-payment:hover { background-color: #dc2626; transform: translateY(-2px); color: #fff; text-decoration: none; }

    /* --- 3. TARJETA DE LA TABLA --- */
    .table-card {
        background-color: #121212; border: 1px solid #2a2a2a;
        border-radius: 16px; padding: 25px; box-shadow: 0 20px 50px rgba(0,0,0,0.6);
    }

    /* --- 4. PERSONALIZACIÓN PROFUNDA DE DATATABLES --- */
    table.dataTable.no-footer { border-bottom: none !important; }
    table.dataTable { border-collapse: collapse !important; width: 100% !important; }

    /* Cabecera de la Tabla */
    table.dataTable thead th {
        background-color: #0a0a0a !important; color: #aaa !important;
        font-size: 12px; text-transform: uppercase; letter-spacing: 1px;
        border-bottom: 2px solid #333 !important; padding: 18px 15px !important; font-weight: 700;
    }

    /* Filas de la Tabla */
    table.dataTable tbody td {
        padding: 20px 15px !important; font-size: 14px; color: #fff !important;
        border-bottom: 1px solid #222 !important; vertical-align: middle;
        background-color: #121212 !important; transition: 0.3s;
    }
    table.dataTable tbody tr:hover td { background-color: #1a1a1a !important; }

    /* --- 5. CONTROLES (BUSCADOR Y PAGINACIÓN) --- */
    .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, 
    .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_processing {
        color: #aaa !important; font-size: 13px; margin-bottom: 20px;
    }

    /* Buscador corregido */
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        background-color: #1a1a1a !important; border: 1px solid #444 !important;
        color: #fff !important; border-radius: 6px !important; padding: 8px 12px !important;
        outline: none; transition: border 0.3s;
    }
    .dataTables_wrapper .dataTables_filter input:focus,
    .dataTables_wrapper .dataTables_length select:focus { border-color: #ef4444 !important; }

    /* Paginación */
    .dataTables_wrapper .dataTables_paginate { margin-top: 20px !important; padding-top: 10px !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #fff !important; background: #1a1a1a !important; border: 1px solid #333 !important;
        border-radius: 6px !important; margin: 0 3px !important; padding: 6px 14px !important; transition: all 0.2s;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled) {
        background: #333 !important; border-color: #555 !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #ef4444 !important; color: #fff !important; border-color: #ef4444 !important; font-weight: bold;
    }

    /* --- 6. ELEMENTOS INTERNOS DE CELDAS --- */
    .col-date span { display: block; }
    .date-main { font-weight: 700; color: #fff; font-size: 15px; }
    .date-sub { font-size: 12px; color: #888; margin-top: 3px; }

    .service-name { font-weight: 600; font-size: 15px; color: #fff; display: block; }
    .service-dates { 
        font-size: 11px; color: #aaa; background: #222; 
        padding: 4px 8px; border-radius: 4px; margin-top: 6px; display: inline-block;
    }

    .amount-text { font-family: 'Oswald', sans-serif; font-size: 18px; color: #10b981; letter-spacing: 0.5px; font-weight: 500;}

    /* Badge de estado */
    .badge-status { 
        padding: 6px 12px; border-radius: 30px; font-size: 11px; 
        font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; 
    }
    .badge-paid { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }

    /* Botón de Ojo (PDF) */
    .action-btn { 
        display: inline-flex; width: 40px; height: 40px; border-radius: 8px; 
        background-color: #1a1a1a; color: #ef4444; align-items: center; justify-content: center; 
        transition: 0.2s; text-decoration: none; border: 1px solid #333;
    }
    .action-btn:hover { background-color: #ef4444; color: white; border-color: #ef4444; }

    /* Empty State */
    .empty-state { text-align: center; padding: 50px 20px; }
    .empty-state i { font-size: 50px; color: #333; margin-bottom: 20px; }
    .empty-state h3 { color: #fff; font-family: 'Oswald', sans-serif; margin-bottom: 10px; }
    .empty-state p { color: #888; }

    /* 🔥 7. MAGIA RESPONSIVA (CARD VIEW) PARA MÓVILES 🔥 */
    @media (max-width: 768px) {
        .payments-wrapper { padding: 120px 15px 40px; } /* Ajuste de cabecera móvil */
        .page-header { flex-direction: column; align-items: flex-start; }
        .btn-new-payment { width: 100%; justify-content: center; padding: 15px; }
        .table-card { padding: 15px; background-color: transparent; border: none; box-shadow: none; }
        
        .dataTables_wrapper .dataTables_filter input { width: 100%; margin: 10px 0 0 0 !important; }
        .dataTables_wrapper .dataTables_filter label { display: block; text-align: left; }
        .dataTables_wrapper .dataTables_length { display: none; } /* Ocultar "Mostrar X registros" en móvil */

        /* Transformar la tabla en tarjetas */
        table.dataTable thead { display: none; } /* Ocultar cabeceras */
        table.dataTable tbody tr {
            display: block;
            background-color: #121212 !important;
            border: 1px solid #2a2a2a !important;
            border-radius: 12px;
            margin-bottom: 15px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.4);
        }
        table.dataTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px dashed #222 !important;
            padding: 12px 0 !important;
            background-color: transparent !important;
            text-align: right;
        }
        table.dataTable tbody td:last-child { border-bottom: none !important; padding-bottom: 0 !important; }
        
        /* Crear "falsas cabeceras" para cada dato en móvil */
        table.dataTable tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            color: #888;
            font-size: 12px;
            text-transform: uppercase;
            float: left;
            text-align: left;
            margin-right: auto;
        }

        /* Ajustes internos para las tarjetas */
        .col-date, .service-name { text-align: right; }
        .date-main { font-size: 14px; }
        .service-dates { margin-top: 4px; }
        .action-btn { width: 100%; margin-top: 10px; }
    }
</style>

<div class="payments-wrapper">
    <div class="payments-container">
        
        <div class="page-header">
            <div class="page-title">
                <h2>Historial de Pagos</h2>
                <p>Consulta tus recibos y el estado de tus membresías.</p>
            </div>
            <a href="index.php?page=user_pago_membresia" class="btn-new-payment">
                <i class="fa fa-credit-card"></i> NUEVO PAGO
            </a>
        </div>

        <div class="table-card">
            
            <?php if (count($pagos) > 0): ?>
                <table id="tablaPagos" class="custom-table display" style="width:100%">
                    <thead>
                        <tr>
                            <th># Folio</th>
                            <th>Fecha Pago</th>
                            <th>Servicio</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $pago): 
                            $fechaPago = date("d/m/Y", strtotime($pago['pag_fecha_pago']));
                            $horaPago  = date("H:i", strtotime($pago['pag_fecha_pago']));
                            $vigencia  = date("d/m", strtotime($pago['pag_fecha_ini'])) . " - " . date("d/m", strtotime($pago['pag_fecha_fin']));
                            $monto     = number_format($pago['pag_importe'], 2);
                        ?>
                        <tr>
                            <td data-label="Folio" style="color:#888;">
                                #<?php echo str_pad($pago['pag_id_pago'], 5, "0", STR_PAD_LEFT); ?>
                            </td>
                            
                            <td data-label="Fecha" class="col-date" data-order="<?php echo $pago['pag_fecha_pago']; ?>"> 
                                <div>
                                    <span class="date-main"><?php echo $fechaPago; ?></span>
                                    <span class="date-sub"><?php echo $horaPago; ?></span>
                                </div>
                            </td>
                            
                            <td data-label="Servicio">
                                <div>
                                    <span class="service-name"><?php echo htmlspecialchars($pago['ser_descripcion'] ?? 'Servicio'); ?></span>
                                    <span class="service-dates"><?php echo $vigencia; ?></span>
                                </div>
                            </td>
                            
                            <td data-label="Total Pagado">
                                <span class="amount-text">$<?php echo $monto; ?></span>
                            </td>
                            
                            <td data-label="Estado">
                                <span class="badge-status badge-paid">Aprobado</span>
                            </td>
                            
                            <td data-label="" style="text-align: right; width: 100%;">
                                <a href="index.php?page=recibo&id_pago=<?php echo $pago['pag_id_pago']; ?>" class="action-btn" title="Ver Recibo">
                                    <i class="fa fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No tienes pagos registrados</h3>
                    <p>Una vez que adquieras tu membresía, tus recibos aparecerán aquí.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
(function() {
    function iniciarTabla() {
        $('#tablaPagos').DataTable({
            "pageLength": 5,
            "lengthMenu": [[5, 10, 25, -1], [5, 10, 25, "Todos"]],
            "ordering": false,
            "stripeClasses": [], 
            "language": {
                "emptyTable": "No hay pagos registrados",
                "info": "Mostrando _START_ a _END_ de _TOTAL_",
                "infoEmpty": "0 registros",
                "infoFiltered": "(filtrado de _MAX_)",
                "lengthMenu": "Mostrar _MENU_",
                "search": "Buscar recibo:",
                "zeroRecords": "No se encontraron coincidencias",
                "paginate": { "first": "<<", "last": ">>", "next": "Siguiente >", "previous": "< Anterior" }
            }
        });
    }

    function cargarDataTables() {
        if ($.fn.DataTable) { iniciarTabla(); return; }
        var script = document.createElement('script');
        script.src = "https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js";
        script.onload = iniciarTabla;
        document.head.appendChild(script);
    }

    var intentos = 0;
    var checkJquery = setInterval(function() {
        if (window.jQuery) {
            clearInterval(checkJquery);
            cargarDataTables();
        }
        intentos++;
        if(intentos > 100) clearInterval(checkJquery);
    }, 100);
})();
</script>
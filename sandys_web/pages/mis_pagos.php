<?php
// ---------------------------------------------
//  Mis Pagos - Historial del Socio (Dark Mode + Paginación)
// ---------------------------------------------

require_once __DIR__ . '/../conn.php';

$idSocio = $_SESSION['admin']['soc_id_socio'] ?? 0;

if ($idSocio <= 0) {
    echo "<div style='color:white; text-align:center; padding:50px;'>Debes iniciar sesión para ver tus pagos.</div>";
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
    echo "Error: " . $e->getMessage();
    $pagos = [];
}
?>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">

<style>
    /* --- 1. FONDO Y CONTENEDOR --- */
    .payments-wrapper {
        background-color: #0f0f0f;
        min-height: 100vh;
        padding: 40px 20px;
        font-family: 'Muli', sans-serif;
        color: #e0e0e0;
    }
    .payments-container {
        max-width: 1100px;
        margin: 0 auto;
    }

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
    .btn-new-payment:hover { background-color: #dc2626; transform: translateY(-2px); color: #fff; }

    /* --- 3. TARJETA DE LA TABLA --- */
    .table-card {
        background-color: #1a1a1a;
        border: 1px solid #333;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.6);
    }

    /* --- 4. PERSONALIZACIÓN PROFUNDA DE DATATABLES --- */
    
    /* Eliminar bordes y fondos por defecto de la tabla */
    table.dataTable.no-footer { border-bottom: 1px solid #333 !important; }
    table.dataTable { border-collapse: collapse !important; }

    /* Cabecera de la Tabla */
    table.dataTable thead th {
        background-color: #222 !important;
        color: #9ca3af !important;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-bottom: 2px solid #444 !important;
        padding: 15px !important;
        font-weight: 700;
    }

    /* Filas de la Tabla */
    table.dataTable tbody td {
        padding: 18px 15px !important;
        font-size: 14px;
        color: #fff !important;
        border-bottom: 1px solid #2a2a2a !important;
        vertical-align: middle;
        background-color: #1a1a1a !important; /* Fondo base oscuro */
    }

    /* Hover en filas */
    table.dataTable tbody tr:hover td {
        background-color: #252525 !important; /* Un poco más claro al pasar mouse */
        color: #fff !important;
    }

    /* --- 5. CONTROLES (BUSCADOR Y PAGINACIÓN) --- */
    
    /* Texto general de controles */
    .dataTables_wrapper .dataTables_length, 
    .dataTables_wrapper .dataTables_filter, 
    .dataTables_wrapper .dataTables_info, 
    .dataTables_wrapper .dataTables_processing {
        color: #aaa !important;
        font-size: 13px;
        margin-bottom: 20px;
    }

    /* Inputs (Buscar y Select) - Quitar estilo blanco del navegador */
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        background-color: #0f0f0f !important;
        border: 1px solid #444 !important;
        color: #fff !important;
        border-radius: 6px !important;
        padding: 8px 12px !important;
        outline: none;
        transition: border 0.3s;
    }
    .dataTables_wrapper .dataTables_filter input:focus,
    .dataTables_wrapper .dataTables_length select:focus {
        border-color: #ef4444 !important; /* Borde rojo al escribir */
    }

    /* Paginación (Números abajo) */
    .dataTables_wrapper .dataTables_paginate {
        margin-top: 20px !important;
        padding-top: 10px !important;
    }

    /* Botones de paginación individuales */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #fff !important;
        background: #222 !important; /* Fondo gris oscuro */
        border: 1px solid #333 !important;
        border-radius: 6px !important;
        margin: 0 3px !important;
        padding: 6px 14px !important;
        transition: all 0.2s;
    }

    /* Hover en paginación */
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled) {
        background: #333 !important;
        color: #fff !important;
        border-color: #555 !important;
    }

    /* Botón Activo (Página actual) */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #ef4444 !important; /* Rojo */
        color: #fff !important;
        border-color: #ef4444 !important;
        font-weight: bold;
    }

    /* Botones deshabilitados */
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background: #1a1a1a !important;
    }

    /* --- 6. ELEMENTOS INTERNOS DE CELDAS --- */
    .col-date span { display: block; }
    .date-main { font-weight: 700; color: #fff; font-size: 14px; }
    .date-sub { font-size: 11px; color: #777; margin-top: 2px; }

    .service-name { font-weight: 600; font-size: 14px; color: #eee; display: block; }
    .service-dates { 
        font-size: 11px; color: #999; 
        background: rgba(255,255,255,0.05); 
        padding: 3px 8px; border-radius: 4px; 
        margin-top: 5px; display: inline-block;
    }

    .amount-text { 
        font-family: 'Oswald', sans-serif; 
        font-size: 16px; color: #22c55e; letter-spacing: 0.5px; 
    }

    /* Badge de estado */
    .badge-status { 
        padding: 6px 12px; border-radius: 30px; font-size: 10px; 
        font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; 
    }
    .badge-paid { 
        background-color: rgba(34, 197, 94, 0.15); 
        color: #4ade80; 
        border: 1px solid rgba(34, 197, 94, 0.3); 
    }

    /* Botón de Ojo */
    .action-btn { 
        display: inline-flex; width: 36px; height: 36px; 
        border-radius: 8px; background-color: #252525; 
        color: #ccc; align-items: center; justify-content: center; 
        transition: 0.2s; text-decoration: none; border: 1px solid #333;
    }
    .action-btn:hover { 
        background-color: #ef4444; color: white; border-color: #ef4444; 
    }

    /* Responsivo */
    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: flex-start; }
        .btn-new-payment { width: 100%; justify-content: center; }
        .table-card { padding: 15px; }
        .dataTables_wrapper .dataTables_filter input { width: 100%; margin-left: 0 !important; }
        .dataTables_wrapper .dataTables_filter { text-align: left !important; margin-top: 10px; }
    }
</style>
<br><br><br><br>

<div class="payments-wrapper">
    <div class="payments-container">
        
        <div class="page-header">
            <div class="page-title">
                <h2>Historial de Pagos</h2>
                <p>Consulta tus recibos y el estado de tus membresías.</p>
            </div>
            <a href="index.php?page=user_pago_membresia" class="btn-new-payment">
                <i class="fa fa-credit-card"></i> Nuevo Pago
            </a>
        </div>

        <div class="table-card">
            
            <?php if (count($pagos) > 0): ?>
                <div class="table-responsive">
                    <table id="tablaPagos" class="custom-table display">
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
                                <td style="color:#777;">
                                    #<?php echo str_pad($pago['pag_id_pago'], 5, "0", STR_PAD_LEFT); ?>
                                </td>
                                
                                <td class="col-date" data-order="<?php echo $pago['pag_fecha_pago']; ?>"> <span class="date-main"><?php echo $fechaPago; ?></span>
                                    <span class="date-sub"><?php echo $horaPago; ?></span>
                                </td>
                                
                                <td>
                                    <span class="service-name"><?php echo htmlspecialchars($pago['ser_descripcion'] ?? 'Servicio'); ?></span>
                                    <span class="service-dates"><?php echo $vigencia; ?></span>
                                </td>
                                
                                <td><span class="amount-text">$<?php echo $monto; ?></span></td>
                                
                                <td><span class="badge-status badge-paid">Aprobado</span></td>
                                
                                <td style="text-align: right;">
                                    <a href="index.php?page=recibo&id_pago=<?php echo $pago['pag_id_pago']; ?>" class="action-btn" title="Ver Recibo">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa fa-folder-open-o"></i></div>
                    <h3>No tienes pagos registrados</h3>
                    <p>Tus pagos aparecerán aquí.</p>
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
            "ordering": false, // Desactiva ordenamiento automático
            // Quitamos la clase 'display' o 'stripe' para controlar el fondo manualmente
            "stripeClasses": [], 
            "language": {
                "emptyTable": "No hay pagos registrados",
                "info": "Mostrando _START_ a _END_ de _TOTAL_",
                "infoEmpty": "0 registros",
                "infoFiltered": "(filtrado de _MAX_)",
                "lengthMenu": "Mostrar _MENU_",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron coincidencias",
                "paginate": { "first": "<<", "last": ">>", "next": ">", "previous": "<" }
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
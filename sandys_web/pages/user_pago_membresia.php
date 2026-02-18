<?php
// =================================================================
// INICIO DEL BLOQUE PHP (Lógica original intacta)
// =================================================================

// 2. Verificar la sesión del socio
if (empty($_SESSION['admin']['soc_id_socio'])) {
    echo "<div style='color:white; text-align:center; padding:50px;'>Error: Sesión no válida. Por favor, inicie sesión de nuevo.</div>";
    exit;
}

// 3. Obtener IDs de la sesión
$id_socio = (int)$_SESSION['admin']['soc_id_socio'];
$id_empresa = 1;
$id_consorcio = 1;
$id_giro = 1;

// 4. Funciones de BD (adaptadas a PDO)
function obtener_datos_socio_pdo($conexion_pdo, $id_socio, $id_empresa)
{
    $query = "SELECT * FROM san_socios WHERE soc_id_socio = ? AND soc_id_empresa = ?";
    $stmt = $conexion_pdo->prepare($query);
    $stmt->execute([$id_socio, $id_empresa]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtener_servicios_pdo($conexion_pdo, $id_consorcio, $id_giro)
{
    $query = "SELECT ser_id_servicio AS id_servicio, 
                     ser_descripcion AS descripcion,
                     ROUND( ser_cuota, 2 ) AS cuota,
                     ser_meses AS meses
              FROM   san_servicios 
              WHERE ser_tipo = 'PERIODO'
                    AND ser_id_consorcio = ?
                    AND ser_id_giro = ?
                    AND ser_status != 'D'
              ORDER BY ser_descripcion ASC";

    $stmt = $conexion_pdo->prepare($query);
    $stmt->execute([$id_consorcio, $id_giro]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 5. Obtener los datos para la vista
$selSocioData = obtener_datos_socio_pdo($conn, $id_socio, $id_empresa);
$listaServicios = obtener_servicios_pdo($conn, $id_consorcio, $id_giro);

if (!$selSocioData) {
    echo "<div style='color:white;'>Error: No se pudieron cargar los datos del socio.</div>";
    exit;
}
// =================================================================
// FIN DEL BLOQUE PHP
// =================================================================
?>

<style>
    /* Base */
    body {
        background-color: #0f0f0f; /* Negro profundo */
        color: #e0e0e0;
        font-family: 'Muli', sans-serif;
    }

    /* Sección Principal */
    .membership-payment {
        padding: 60px 0;
        min-height: 100vh;
    }

    /* Títulos */
    .page-title {
        font-family: 'Oswald', sans-serif;
        font-size: 36px;
        color: #fff;
        text-transform: uppercase;
        margin-bottom: 10px;
        letter-spacing: 1px;
    }
    .page-subtitle {
        color: #9ca3af;
        font-size: 16px;
        margin-bottom: 40px;
    }

    /* Tarjetas (Formulario y Resumen) */
    .payment-card, .summary-card {
        background-color: #1a1a1a; /* Gris oscuro */
        border: 1px solid #333;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
        margin-bottom: 30px;
    }

    .card-header-title {
        font-family: 'Oswald', sans-serif;
        font-size: 22px;
        color: #fff;
        text-transform: uppercase;
        margin-bottom: 20px;
        border-bottom: 1px solid #333;
        padding-bottom: 15px;
    }

    /* Inputs y Selects */
    .form-label {
        color: #ccc;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
    }

    .form-control, .custom-select {
        background-color: #0f0f0f !important;
        border: 1px solid #444 !important;
        color: #fff !important;
        border-radius: 8px !important;
        padding: 12px 15px !important;
        height: auto !important;
        font-size: 15px;
        transition: border-color 0.3s;
    }

    .form-control:focus, .custom-select:focus {
        border-color: #ef4444 !important; /* Rojo al enfocar */
        box-shadow: none !important;
        outline: none;
    }

    /* Input Readonly (Socio) */
    .form-control[readonly] {
        background-color: #252525 !important;
        color: #888 !important;
        cursor: not-allowed;
    }

    /* Botón de Pago */
    .primary-btn {
        background-color: #ef4444;
        color: #fff;
        border: none;
        padding: 15px;
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
    }
    .primary-btn:hover {
        background-color: #dc2626;
        transform: translateY(-2px);
        color: #fff;
    }

    /* Botón Aplicar Cupón */
    .btn-outline-secondary {
        border-color: #444;
        color: #ccc;
        background: #252525;
    }
    .btn-outline-secondary:hover {
        background: #333;
        color: #fff;
        border-color: #555;
    }

    /* Resumen Sticky */
    @media (min-width: 992px) {
        .summary-sticky {
            position: sticky;
            top: 2rem;
        }
    }

    /* Lista de Resumen */
    .list-group-item {
        background-color: transparent !important;
        border-color: #333 !important;
        padding: 15px 0;
        color: #ddd;
    }
    
    .summary-total {
        font-size: 24px;
        color: #ef4444; /* Rojo precio */
    }

    /* Seguridad MP */
    .security-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.2);
        padding: 10px;
        border-radius: 8px;
        margin-top: 20px;
    }
    .security-text { font-size: 12px; color: #4ade80; margin: 0; }

</style>
<br><br><br>
<section class="membership-payment">
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-lg-10 text-center">
                <h2 class="page-title">Realizar Pago de Membresía</h2>
                <p class="page-subtitle">Selecciona tu servicio y completa el pago de forma segura.</p>
            </div>
        </div>

        <div class="row">

            <div class="col-lg-7">
                <div class="payment-card">

                    <h3 class="card-header-title">Detalles del Pago</h3>

                    <div id="mensajeError" class="alert alert-danger" style="display: none; background-color: rgba(220, 53, 69, 0.2); border-color: #dc3545; color: #ff8b94;"></div>

                    <form id="pagoMembresiaForm">
                        <div class="row">

                            <div class="col-12 form-group mb-4">
                                <label for="nombre_socio" class="form-label">Socio</label>
                                <input type="text" id="nombre_socio" name="nombre_socio" class="form-control"
                                    value="<?php echo htmlspecialchars($selSocioData['soc_nombres'] . ' ' . $selSocioData['soc_apepat'] . ' ' . $selSocioData['soc_apemat']); ?>" readonly>
                            </div>

                            <div class="col-12 form-group mb-4">
                                <label for="servicio" class="form-label">Selecciona el Servicio</label>
                                <select id="servicio" name="servicio" class="custom-select form-control" required>
                                    <option value="" selected disabled>-- Elige una membresía --</option>
                                    <?php foreach ($listaServicios as $servicio): ?>
                                        <option value="<?php echo $servicio['id_servicio'] . '-' . $servicio['meses']; ?>"
                                            data-cuota="<?php echo $servicio['cuota']; ?>">
                                            <?php echo htmlspecialchars($servicio['descripcion']) . " ($" . number_format($servicio['cuota'], 2) . ")"; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="dateFieldsContainer" class="col-12" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 form-group mb-4">
                                        <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                        <input type="text" id="fecha_inicio" name="fecha_inicio" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-6 form-group mb-4">
                                        <label for="fecha_fin" class="form-label">Fecha de Vencimiento</label>
                                        <input type="text" id="fecha_fin" name="fecha_fin" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 form-group mb-4">
                                <label for="codigo_promocion" class="form-label">Código de Promoción (Opcional)</label>
                                <div class="input-group">
                                    <input type="text" id="codigo_promocion" name="codigo_promocion" class="form-control" placeholder="Ej. 22M40G20">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="aplicarCuponBtn">Aplicar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-2">
                                <button id="realizarPagoBtn" type="submit" class="primary-btn">
                                    Pagar Ahora <i class="fas fa-chevron-right ml-2"></i>
                                    <span id="loader" style="display: none;">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    </span>
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="summary-sticky">
                    <div id="resumenPago" class="summary-card">
                        
                        <h4 class="card-header-title" style="font-size: 18px; border:none; margin-bottom:10px;">Resumen de Compra</h4>

                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Subtotal</span>
                                <strong id="previewSubtotal" style="font-size: 18px;">$0.00</strong>
                            </li>

                            <li id="filaDescuento" class="list-group-item d-flex justify-content-between align-items-center" style="display: none; color: #4ade80 !important;">
                                <span>Descuento (<span id="previewDescuentoNombre"></span>)</span>
                                <strong id="previewDescuentoMonto">-$0.00</strong>
                            </li>

                            <li class="list-group-item d-flex justify-content-between align-items-center pt-4" style="border-top: 1px dashed #444 !important;">
                                <span style="font-size: 18px; font-weight: 700;">Total a Pagar</span>
                                <strong id="previewTotal" class="summary-total">$0.00</strong>
                            </li>
                        </ul>

                        <div class="security-badge">
                            <i class="fas fa-lock text-success"></i>
                            <div>
                                <p class="security-text" style="margin-bottom: 2px;">Pagos 100% Seguros vía</p>
                                <p class="security-text" style="font-weight: bold;">Mercado Pago</p>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
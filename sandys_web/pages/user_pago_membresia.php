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
        background-color: #050505 !important; /* Negro profundo */
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
        background-color: #121212; /* Gris más oscuro premium */
        border: 1px solid #2a2a2a;
        border-radius: 16px;
        padding: 35px 30px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
        margin-bottom: 30px;
    }

    .card-header-title {
        font-family: 'Oswald', sans-serif;
        font-size: 24px;
        color: #fff;
        text-transform: uppercase;
        margin-bottom: 25px;
        border-bottom: 1px solid #333;
        padding-bottom: 15px;
    }

    /* Inputs y Selects */
    .form-label {
        color: #bbb;
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 8px;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control, .custom-select {
        background-color: #1a1a1a !important; /* Fondo del input oscuro pero visible */
        border: 1px solid #444 !important;
        color: #ffffff !important; /* 🔥 TEXTO BLANCO BRILLANTE PARA QUE SE VEA BIEN 🔥 */
        border-radius: 8px !important;
        padding: 14px 15px !important;
        height: auto !important;
        font-size: 15px !important;
        transition: all 0.3s;
        box-shadow: none !important;
    }

    /* Color del placeholder para que se vea bien en fondo oscuro */
    .form-control::placeholder {
        color: #888888 !important;
        opacity: 1;
    }

    /* Fix para autocompletado de Chrome (Evita que se ponga amarillo/blanco) */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active{
        -webkit-box-shadow: 0 0 0 30px #1a1a1a inset !important;
        -webkit-text-fill-color: white !important;
        transition: background-color 5000s ease-in-out 0s;
    }

    .form-control:focus, .custom-select:focus {
        border-color: #ef4444 !important; /* Rojo al enfocar */
        background-color: #121212 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
        outline: none;
    }

    /* 🔥 Input Readonly (Socio) - Estilo para que se note que NO es editable 🔥 */
    .form-control[readonly] {
        background-color: #0a0a0a !important;
        color: #888888 !important;
        border-color: #222 !important;
        cursor: not-allowed;
        font-weight: 600;
    }

    /* Select Dropdown (Mejorando la flechita nativa) */
    .custom-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ef4444' d='M10.293 3.293 6 7.586 1.707 3.293A1 1 0 0 0 .293 4.707l5 5a1 1 0 0 0 1.414 0l5-5a1 1 0 1 0-1.414-1.414z'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 15px center !important;
        padding-right: 40px !important;
    }
    .custom-select option {
        background-color: #1a1a1a;
        color: #fff;
    }

    /* ========================================================
       CORRECCIÓN DEFINITIVA: Input del Cupón
       (Usando su ID exacto para aplastar las reglas de Bootstrap)
       ======================================================== */
    .input-group {
        display: flex;
    }
    
    input#codigo_promocion,
    .input-group > .form-control {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
        border: 1px solid #444 !important;
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
        box-shadow: none !important;
    }

    input#codigo_promocion:focus,
    .input-group > .form-control:focus {
        background-color: #121212 !important;
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
        outline: none !important;
    }

    .input-group-append .btn-outline-secondary {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
        background-color: #333 !important; 
        color: #fff !important;
        border: 1px solid #444 !important;
        border-left: none !important;
        font-weight: bold;
        padding: 0 20px;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .input-group-append .btn-outline-secondary:hover {
        background-color: #ef4444 !important;
        border-color: #ef4444 !important;
    }

    /* Botón de Pago Principal */
    .primary-btn {
        background-color: #ef4444;
        color: #fff;
        border: none;
        padding: 16px;
        border-radius: 8px;
        font-family: 'Oswald', sans-serif;
        text-transform: uppercase;
        font-size: 18px;
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

    /* Resumen Sticky */
    @media (min-width: 992px) {
        .summary-sticky {
            position: sticky;
            top: 120px;
        }
    }

    /* Lista de Resumen */
    .list-group-item {
        background-color: transparent !important;
        border-color: #333 !important;
        padding: 18px 0;
        color: #ddd;
    }
    
    .summary-total {
        font-size: 26px;
        color: #ef4444; 
        font-weight: bold;
    }

    /* Seguridad MP */
    .security-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        background: rgba(34, 197, 94, 0.05);
        border: 1px dashed rgba(34, 197, 94, 0.3);
        padding: 15px;
        border-radius: 12px;
        margin-top: 25px;
    }
    .security-badge i { font-size: 24px; color: #10b981; }
    .security-text { font-size: 13px; color: #a7f3d0; margin: 0; line-height: 1.4; }

    /* --- RESPONSIVE MÓVIL --- */
    @media (max-width: 768px) {
        .membership-payment { padding: 120px 0 40px; } 
        .page-title { font-size: 28px; line-height: 1.2; }
        .page-subtitle { font-size: 14px; margin-bottom: 30px; }
        .payment-card, .summary-card { padding: 25px 20px; }
        .summary-total { font-size: 22px; }
    }
</style>

<br><br><br>
<section class="membership-payment">
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-lg-10 text-center">
                <h2 class="page-title">Pago de Membresía</h2>
                <p class="page-subtitle">Selecciona tu plan y completa tu pago de forma segura.</p>
            </div>
        </div>

        <div class="row">

            <div class="col-lg-7">
                <div class="payment-card">

                    <h3 class="card-header-title">Detalles de Facturación</h3>

                    <div id="mensajeError" class="alert alert-danger" style="display: none; background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444;"></div>

                    <form id="pagoMembresiaForm">
                        <div class="row">

                            <div class="col-12 form-group mb-4">
                                <label for="nombre_socio" class="form-label">Titular de la cuenta</label>
                                <input type="text" id="nombre_socio" name="nombre_socio" class="form-control"
                                    value="<?php echo htmlspecialchars($selSocioData['soc_nombres'] . ' ' . $selSocioData['soc_apepat'] . ' ' . $selSocioData['soc_apemat']); ?>" readonly>
                            </div>

                            <div class="col-12 form-group mb-4">
                                <label for="servicio" class="form-label">Plan a Contratar</label>
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
                                        <label for="fecha_inicio" class="form-label">Inicia el</label>
                                        <input type="text" id="fecha_inicio" name="fecha_inicio" class="form-control" readonly style="background-color: #0a0a0a !important; color: #aaa !important;">
                                    </div>
                                    <div class="col-md-6 form-group mb-4">
                                        <label for="fecha_fin" class="form-label">Vence el</label>
                                        <input type="text" id="fecha_fin" name="fecha_fin" class="form-control" readonly style="background-color: #0a0a0a !important; color: #aaa !important;">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 form-group mb-4">
                                <label for="codigo_promocion" class="form-label">¿Tienes un cupón?</label>
                                <div class="input-group">
                                    <input type="text" id="codigo_promocion" name="codigo_promocion" class="form-control" placeholder="Ingresa tu código promocional">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="aplicarCuponBtn">APLICAR</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-2">
                                <button id="realizarPagoBtn" type="submit" class="primary-btn">
                                    Pagar Ahora <i class="fas fa-arrow-right ml-2"></i>
                                    <span id="loader" style="display: none; margin-left: 10px;">
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
                        
                        <h4 class="card-header-title" style="font-size: 20px; border-bottom: 2px solid #222; margin-bottom:15px;">Resumen del Pedido</h4>

                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted" style="font-size: 16px;">Subtotal</span>
                                <strong id="previewSubtotal" style="font-size: 18px; color:#fff;">$0.00</strong>
                            </li>

                            <li id="filaDescuento" class="list-group-item d-flex justify-content-between align-items-center" style="display: none; color: #10b981 !important;">
                                <span style="font-size: 15px;"><i class="fas fa-tag mr-1"></i> Descuento (<span id="previewDescuentoNombre"></span>)</span>
                                <strong id="previewDescuentoMonto">-$0.00</strong>
                            </li>

                            <li class="list-group-item d-flex justify-content-between align-items-center pt-4" style="border-top: 1px dashed #444 !important; margin-top: 10px;">
                                <span style="font-size: 16px; font-weight: 700; color:#fff; text-transform: uppercase;">Total</span>
                                <strong id="previewTotal" class="summary-total">$0.00</strong>
                            </li>
                        </ul>

                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <p class="security-text" style="margin-bottom: 2px;">Transacción encriptada y segura.</p>
                                <p class="security-text" style="font-weight: bold; color: #fff;">Procesada por Mercado Pago</p>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
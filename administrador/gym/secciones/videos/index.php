<?php
// --- 1. INCLUDES Y CONFIGURACIÓN ---
// Asumimos que la sesión ya está iniciada por el index.php de tu admin
// session_start(); 

// Verificar si el usuario es admin (ejemplo básico)
// if (!isset($_SESSION['admin'])) { die("Acceso denegado."); }

// Verificar conexión mysqli
if (!isset($conexion) || $conexion->connect_error) {
    die("Error crítico: Conexión BD (MySQLi) fallida: " . ($conexion->connect_error ?? 'Error desconocido'));
}

// --- 2. FUNCIONES PHP (Solo para mostrar la página) ---

/**
 * Obtiene opciones HTML para un <select>
 */
function generar_opciones_select($conexion, $tabla, $colId, $colNombre, $seleccionado = null)
{
    $opciones = "";
    $query = "SELECT $colId, $colNombre FROM $tabla ORDER BY $colNombre ASC";
    $resultado = mysqli_query($conexion, $query);
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $id = $fila[$colId];
            $nombre = htmlspecialchars($fila[$colNombre]);
            $selectedAttr = ($id == $seleccionado) ? 'selected' : '';
            $opciones .= "<option value='{$id}' {$selectedAttr}>{$nombre}</option>";
        }
        mysqli_free_result($resultado);
    }
    return $opciones;
}

/**
 * Obtiene las asignaciones de rutina para la carga inicial de la tabla
 */
function obtener_rutina_ejercicios_admin($filtro_nivel = null, $filtro_genero = null)
{
    global $conexion;
    if (!isset($conexion) || $conexion->connect_error) {
        return "<tr><td colspan='10' class='text-center text-danger'>Error: Conexión BD no disponible.</td></tr>";
    }
    try {
        // Consulta base
        $query = "SELECT
                    re.id_rutina_ejercicio, re.orden_ejercicio, re.series, re.repeticiones, re.descanso_seg,
                    n.nombre_nivel,
                    CASE re.genero WHEN 1 THEN 'Hombre' WHEN 2 THEN 'Mujer' ELSE 'N/A' END AS nombre_genero,
                    gm.nombre_grupo,
                    e.nombre_ejercicio,
                    -- Guardar IDs para data-*
                    re.id_nivel, re.genero, re.id_grupo_muscular, re.id_ejercicio
                  FROM
                    rutina_ejercicios re
                  INNER JOIN niveles n ON re.id_nivel = n.id_nivel
                  INNER JOIN grupos_musculares gm ON re.id_grupo_muscular = gm.id_grupo
                  INNER JOIN ejercicios e ON re.id_ejercicio = e.id_ejercicio";

        $whereConditions = [];
        $params = [];
        $types = "";
        if (!is_null($filtro_nivel) && $filtro_nivel !== '') {
            $whereConditions[] = "re.id_nivel = ?";
            $params[] = $filtro_nivel;
            $types .= "i";
        }
        if (!is_null($filtro_genero) && $filtro_genero !== '') {
            $whereConditions[] = "re.genero = ?";
            $params[] = $filtro_genero;
            $types .= "i";
        }
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }
        $query .= " ORDER BY n.id_nivel ASC, re.genero ASC, gm.nombre_grupo ASC, re.orden_ejercicio ASC";

        $stmt = mysqli_prepare($conexion, $query);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
        }
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if (!$resultado) {
            throw new Exception("Error al ejecutar consulta: " . mysqli_stmt_error($stmt));
        }

        $datosHtml = "";
        $i = 1;
        if (mysqli_num_rows($resultado) > 0) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $id_rutina = (int)$fila['id_rutina_ejercicio'];
                $nivel = htmlspecialchars($fila['nombre_nivel']);
                $genero = htmlspecialchars($fila['nombre_genero']);
                $grupo = htmlspecialchars($fila['nombre_grupo']);
                $ejercicio = htmlspecialchars($fila['nombre_ejercicio']);
                $orden = (int)$fila['orden_ejercicio'];
                $series = htmlspecialchars($fila['series']);
                $reps = htmlspecialchars($fila['repeticiones']);
                $descanso = htmlspecialchars($fila['descanso_seg']) . 's';
                $acciones = "
                    <button type='button' class='btn btn-sm btn-info btn-editar-asignacion'
                            data-id='{$id_rutina}' data-nivel='{$fila['id_nivel']}' data-genero='{$fila['genero']}'
                            data-grupo='{$fila['id_grupo_muscular']}' data-ejercicio='{$fila['id_ejercicio']}'
                            data-orden='{$orden}' data-series='{$series}' data-reps='{$reps}' data-descanso='{$fila['descanso_seg']}'
                            title='Editar Asignación'><i class='fas fa-edit'></i></button>
                    <button type='button' class='btn btn-sm btn-danger btn-eliminar-asignacion'
                            data-id='{$id_rutina}' data-descripcion='{$nivel} {$genero} - {$grupo}: {$ejercicio}'
                            title='Eliminar Asignación'><i class='fas fa-trash'></i></button>";
                $datosHtml .= "<tr data-rutina-id='{$id_rutina}'>
                                 <td>{$i}</td> <td>{$nivel}</td> <td>{$genero}</td> <td>{$grupo}</td>
                                 <td>{$ejercicio}</td> <td>{$orden}</td> <td>{$series}</td>
                                 <td>{$reps}</td> <td>{$descanso}</td> <td class='acciones'>{$acciones}</td>
                               </tr>";
                $i++;
            }
            mysqli_free_result($resultado);
        } else {
            $datosHtml = "";
        }
        mysqli_stmt_close($stmt);
        return $datosHtml;
    } catch (Exception $e) {
        error_log("Error en obtener_rutina_ejercicios_admin (MySQLi): " . $e->getMessage());
        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }
        return "<tr><td colspan='10' class='text-center text-danger'>Error al cargar datos.</td></tr>";
    }
}

// --- 3. LÓGICA DE CARGA DE PÁGINA ---
$filtro_nivel_sel = $_POST['filtro_nivel'] ?? '';
$filtro_genero_sel = $_POST['filtro_genero'] ?? '';
$tabla_rutinas_html = obtener_rutina_ejercicios_admin($filtro_nivel_sel, $filtro_genero_sel);

// Obtener opciones para los <select> del modal y filtros
$opciones_niveles = generar_opciones_select($conexion, 'niveles', 'id_nivel', 'nombre_nivel', $filtro_nivel_sel);
$opciones_generos = "<option value='1' " . ($filtro_genero_sel == 1 ? 'selected' : '') . ">Hombre</option>
                     <option value='2' " . ($filtro_genero_sel == 2 ? 'selected' : '') . ">Mujer</option>";
$opciones_grupos = generar_opciones_select($conexion, 'grupos_musculares', 'id_grupo', 'nombre_grupo');
$opciones_ejercicios = generar_opciones_select($conexion, 'ejercicios', 'id_ejercicio', 'nombre_ejercicio');

// mysqli_close($conexion); // No cerrar, puede ser necesario en el pie de página
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador de Asignación de Rutinas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">

    <style>
        /* --- Estilos Generales --- */
        .container-fluid {
            width: 100%;
            padding: 15px;
            margin: auto;
            box-sizing: border-box;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
            box-sizing: border-box;
        }

        .col-md-3,
        .col-md-4,
        .col-md-5,
        .col-md-8,
        .col-md-9,
        .col-md-12 {
            position: relative;
            width: 100%;
            padding: 0 15px;
            box-sizing: border-box;
        }

        @media (min-width: 768px) {
            .col-md-3 {
                flex: 0 0 25%;
                max-width: 25%;
            }

            .col-md-4 {
                flex: 0 0 33.33%;
                max-width: 33.33%;
            }

            .col-md-5 {
                flex: 0 0 41.66%;
                max-width: 41.66%;
            }

            .col-md-8 {
                flex: 0 0 66.66%;
                max-width: 66.66%;
            }

            .col-md-9 {
                flex: 0 0 75%;
                max-width: 75%;
            }

            .col-md-12 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .align-self-end {
                align-self: flex-end;
            }
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        .mb-4 {
            margin-bottom: 1.5rem !important;
        }

        .mt-3 {
            margin-top: 1rem !important;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .text-muted {
            color: #6c757d !important;
        }

        hr {
            border: 0;
            border-top: 1px solid #dee2e6;
            margin: 1rem 0;
        }

        /* --- Título y Botón --- */
        .main-title {
            color: #333;
            font-weight: bold;
            font-size: 1.75rem;
            margin-bottom: 0;
        }

        .main-title i {
            margin-right: 10px;
            color: #17a2b8;
        }

        .btn {
            display: inline-block;
            font-weight: 400;
            color: #fff;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            user-select: none;
            background-color: transparent;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 0.9rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: all .15s ease-in-out;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }

        .btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .btn i {
            margin-right: 5px;
        }

        .btn-sm {
            padding: .25rem .5rem;
            font-size: .8rem;
            line-height: 1.5;
            border-radius: .2rem;
        }

        /* --- Card y Tabla --- */
        .card {
            background-color: #fff;
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            margin-top: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .card-header {
            padding: 0.75rem 1.25rem;
            margin-bottom: 0;
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            font-weight: bold;
            color: #5a5c69;
        }

        .card-body {
            padding: 1.25rem;
        }

        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
        }

        #tabla_rutinas_admin {
            width: 100%;
            margin-bottom: 1rem;
            color: #333;
        }

        #tabla_rutinas_admin th,
        #tabla_rutinas_admin td {
            padding: 0.75rem;
            vertical-align: middle;
            border-top: 1px solid #e3e3e3;
        }

        #tabla_rutinas_admin thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #e3e3e3;
            background-color: #f8f9fc;
            color: #5a5c69;
            text-align: left;
            white-space: nowrap;
        }

        #tabla_rutinas_admin tbody tr:hover {
            background-color: #f1f1f1;
        }

        #tabla_rutinas_admin td.acciones {
            white-space: nowrap;
            text-align: right;
        }

        /* DataTables */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border-radius: .25rem;
            border: 1px solid #ced4da;
            padding: .375rem .75rem;
            margin-left: 0.5em;
            display: inline-block;
            width: auto;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5em 1em;
            margin-left: 2px;
            border: 1px solid #dee2e6;
            border-radius: .25rem;
            color: #007bff;
            cursor: pointer;
            background: white;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
            text-decoration: none;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            color: #6c757d;
            background-color: white;
            border-color: #dee2e6;
            cursor: default;
        }

        /* --- Formulario Filtros --- */
        .form-filters label {
            font-weight: bold;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-filters select {
            display: block;
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            box-sizing: border-box;
        }

        /* --- Modal Básico --- */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            padding-top: 50px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 700px;
            border-radius: 0.3rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .5);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            padding: 1rem 1rem;
            border-bottom: 1px solid #dee2e6;
            background-color: #f8f9fa;
            border-top-left-radius: calc(.3rem - 1px);
            border-top-right-radius: calc(.3rem - 1px);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .modal-title {
            margin-bottom: 0;
            line-height: 1.5;
            font-size: 1.25rem;
        }

        .close-button {
            float: right;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            color: #000;
            text-shadow: 0 1px 0 #fff;
            opacity: .5;
            cursor: pointer;
            background: transparent;
            border: 0;
            padding: 1rem 1rem;
            margin: -1rem -1rem -1rem auto;
        }

        .close-button:hover {
            opacity: .75;
            text-decoration: none;
        }

        .modal-body {
            position: relative;
            flex: 1 1 auto;
            padding: 1rem;
        }

        .modal-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            padding: 0.75rem;
            border-top: 1px solid #dee2e6;
            border-bottom-right-radius: calc(.3rem - 1px);
            border-bottom-left-radius: calc(.3rem - 1px);
        }

        .modal-footer>* {
            margin: 0.25rem;
        }

        /* Formulario Modal */
        #formAsignacion .form-group {
            margin-bottom: 1rem;
        }

        #formAsignacion label {
            font-weight: bold;
            margin-bottom: 0.5rem;
            display: block;
        }

        #formAsignacion select,
        #formAsignacion input[type="number"],
        #formAsignacion input[type="text"] {
            display: block;
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
            box-sizing: border-box;
        }

        #formAsignacion input:focus,
        #formAsignacion select:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row mb-3 align-items-center">
            <div class="col-md-9">
                <h4 class="main-title">
                    <i class="fas fa-tasks"></i> Administrador de Asignación de Rutinas
                </h4>
            </div>
            <div class="col-md-3 text-right">
                <button class="btn btn-success" id="btnAgregarAsignacion">
                    <i class="fas fa-plus-circle"></i> Agregar Asignación
                </button>
            </div>
        </div>

        <hr />

        <form method="post" action="" class="form-filters mb-4">
            <div class="row">
                <div class="col-md-4">
                    <label for="filtro_nivel">Filtrar por Nivel:</label>
                    <select name="filtro_nivel" id="filtro_nivel" onchange="this.form.submit()">
                        <option value="">-- Todos los Niveles --</option>
                        <?php echo $opciones_niveles; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filtro_genero">Filtrar por Género:</label>
                    <select name="filtro_genero" id="filtro_genero" onchange="this.form.submit()">
                        <option value="">-- Ambos Géneros --</option>
                        <?php echo $opciones_generos; ?>
                    </select>
                </div>
                <div class="col-md-4" style="align-self: flex-end; text-align: left; padding-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                    <a href="?s=videos" class="btn btn-secondary"><i class="fas fa-times"></i> Limpiar</a>
                </div>
            </div>
        </form>


        <div class="card">
            <div class="card-header">
                <i class="fas fa-list mr-2"></i> Asignaciones Actuales
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabla_rutinas_admin" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nivel</th>
                                <th>Género</th>
                                <th>Grupo Muscular</th>
                                <th>Ejercicio</th>
                                <th>Orden</th>
                                <th>Series</th>
                                <th>Reps</th>
                                <th>Descanso</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $tabla_rutinas_html; // Imprime las filas generadas por PHP 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    <div id="modalAsignacion" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAsignacionLabel">Agregar/Editar Asignación</h5>
                <button type="button" class="close-button" onclick="document.getElementById('modalAsignacion').style.display='none'" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formAsignacion">
                    <input type="hidden" id="rutina_ejercicio_id" name="rutina_ejercicio_id">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="id_nivel">Nivel:</label>
                            <select id="id_nivel" name="id_nivel" required>
                                <option value="">Seleccione...</option>
                                <?php echo $opciones_niveles; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="genero">Género:</label>
                            <select id="genero" name="genero" required>
                                <option value="">Seleccione...</option>
                                <?php echo $opciones_generos; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="id_grupo_muscular">Grupo Muscular:</label>
                        <select id="id_grupo_muscular" name="id_grupo_muscular" required>
                            <option value="">Seleccione...</option>
                            <?php echo $opciones_grupos; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_ejercicio">Ejercicio:</label>
                        <select id="id_ejercicio" name="id_ejercicio" required>
                            <option value="">Seleccione...</option>
                            <?php echo $opciones_ejercicios; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="orden_ejercicio">Orden:</label>
                            <input type="number" id="orden_ejercicio" name="orden_ejercicio" value="0" min="0" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="series">Series:</label>
                            <input type="number" id="series" name="series" min="1" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="repeticiones">Repeticiones:</label>
                            <input type="text" id="repeticiones" name="repeticiones" placeholder="Ej: 8-12, 15" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="descanso_seg">Descanso (seg):</label>
                            <input type="number" id="descanso_seg" name="descanso_seg" min="0" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAsignacion').style.display='none'">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarAsignacion">Guardar Cambios</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="js/admin_rutinas.js"></script>
</body>



</body>

</html>
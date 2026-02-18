<?php
// --- 1. INCLUDES Y CONFIGURACIÓN ---
// Asumimos que $conexion (mysqli) ya está definida globalmente
// (Si no, descomenta la siguiente línea y ajusta la ruta)
// require_once '../conn_mysqli.php'; 

// Verificar conexión mysqli
if (!isset($conexion) || $conexion->connect_error) {
    die("Error crítico: Conexión BD (MySQLi) fallida: " . ($conexion->connect_error ?? 'Error desconocido'));
}

/**
 * Obtiene los ejercicios de la BD (mysqli) y genera las filas HTML.
 * @return string HTML con las filas de la tabla o mensajes.
 */
function obtener_ejercicios_videos_admin_simple()
{
    global $conexion;
    if (!isset($conexion)) {
        return "<tr><td colspan='4' class='text-center text-danger'>Error: Conexión no disponible.</td></tr>";
    }

    // --- ¡CORRECCIÓN AQUÍ! ---
    // Define la RUTA WEB (URL) a tu carpeta de videos.
    // Esta ruta es relativa a la ubicación de esta página de admin.
    // Basado en tus rutas anteriores, parece que debes subir 2 niveles 
    // y luego entrar a 'sandys_web'. Ajusta si es necesario.
    $ruta_web_videos = "./../sandys_web/assets/videos/";
    // ----------------------------

    try {
        $query = "SELECT id_ejercicio, nombre_ejercicio, video_url, poster_url, descripcion, recomendaciones
                  FROM ejercicios ORDER BY nombre_ejercicio ASC";
        $resultado = mysqli_query($conexion, $query);
        if (!$resultado) { throw new Exception(mysqli_error($conexion)); }

        $datosHtml = "";
        $i = 1;
        if (mysqli_num_rows($resultado) > 0) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $id = (int)$fila['id_ejercicio'];
                $nombre = htmlspecialchars($fila['nombre_ejercicio'] ?? '', ENT_QUOTES, 'UTF-8');
                
                // Nombre del archivo de video (guardado en la BD)
                $videoNombreArchivo = $fila['video_url'] ?? '';
                // Nombre del archivo de poster (guardado en la BD)
                $posterNombreArchivo = $fila['poster_url'] ?? '';

                // Escapamos solo los nombres de archivo para los data-*
                $videoUrlData = htmlspecialchars($videoNombreArchivo, ENT_QUOTES, 'UTF-8');
                $posterUrlData = htmlspecialchars($posterNombreArchivo, ENT_QUOTES, 'UTF-8');
                $descripcionData = htmlspecialchars($fila['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
                $recomendacionesData = htmlspecialchars($fila['recomendaciones'] ?? '', ENT_QUOTES, 'UTF-8');

                // --- ¡CORRECCIÓN AQUÍ! ---
                // Construir la RUTA COMPLETA para el enlace <a>
                $videoUrlCompleta = $ruta_web_videos . $videoNombreArchivo;
                
                $videoDisplay = basename($videoNombreArchivo); // Mostrar solo nombre de archivo
                if (strlen($videoDisplay) > 40) { 
                    $videoDisplay = substr($videoDisplay, 0, 18) . '...' . substr($videoDisplay, -18);
                }
                
                // El <a> usa la RUTA COMPLETA, el título usa la RUTA COMPLETA
                $videoLink = $videoNombreArchivo ? "<a href='{$videoUrlCompleta}' target='_blank' title='{$videoUrlCompleta}'>{$videoDisplay}</a>" : "<span class='text-muted'>N/A</span>";
                // ----------------------------

                // Los botones data-* siguen usando SOLO EL NOMBRE DE ARCHIVO
                $acciones = "
                    <button type='button' class='btn btn-info btn-editar-video'
                            data-id='{$id}'
                            data-nombre='{$nombre}'
                            data-video='{$videoUrlData}' 
                            data-poster='{$posterUrlData}'
                            data-descripcion='{$descripcionData}'
                            data-recomendaciones='{$recomendacionesData}'
                            title='Editar'><i class='fas fa-edit'></i> Editar</button>
                    <button type='button' class='btn btn-danger btn-eliminar-video'
                            data-id='{$id}'
                            data-nombre='{$nombre}'
                            title='Eliminar'><i class='fas fa-trash'></i> Eliminar</button>
                ";

                $datosHtml .= "<tr>
                                 <td>{$i}</td>
                                 <td>{$nombre}</td>
                                 <td>{$videoLink}</td>
                                 <td class='acciones'>{$acciones}</td>
                               </tr>";
                $i++;
            }
            mysqli_free_result($resultado);
        } else {
             $datosHtml = "";
        }
        return $datosHtml;
    } catch (Exception $e) {
        error_log("Error al obtener ejercicios admin (MySQLi): " . $e->getMessage());
        return "<tr><td colspan='4' class='text-center text-danger'>Error al cargar datos.</td></tr>";
    }
}

// --- 2. OBTENER LOS DATOS PARA LA TABLA ---
$tabla_ejercicios_html = obtener_ejercicios_videos_admin_simple();
?>

<!DOCTYPE html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador de Videos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">

    <style>
        /* --- Estilos Generales --- */
        /* body { font-family: sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; color: #333; } */
        /* Removido body */
        .container-fluid {
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
            margin-right: auto;
            margin-left: auto;
            box-sizing: border-box;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
            box-sizing: border-box;
        }

        .col-md-9,
        .col-md-3,
        .col-md-12 {
            position: relative;
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
            box-sizing: border-box;
        }

        @media (min-width: 768px) {
            .col-md-9 {
                flex: 0 0 75%;
                max-width: 75%;
            }

            .col-md-3 {
                flex: 0 0 25%;
                max-width: 25%;
            }

            .col-md-12 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        .mb-3 {
            margin-bottom: 1rem !important;
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
            margin-top: 1rem;
            margin-bottom: 1rem;
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

        .btn i {
            margin-right: 5px;
        }

        /* --- Estilos Tabla y DataTables --- */
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

        #tabla_videos_admin {
            width: 100%;
            margin-bottom: 1rem;
            color: #333;
        }

        #tabla_videos_admin th,
        #tabla_videos_admin td {
            padding: 0.75rem;
            vertical-align: middle;
            border-top: 1px solid #e3e3e3;
        }

        #tabla_videos_admin thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #e3e3e3;
            background-color: #f8f9fc;
            color: #5a5c69;
            text-align: left;
        }

        #tabla_videos_admin tbody tr:hover {
            background-color: #f1f1f1;
        }

        #tabla_videos_admin td.acciones {
            white-space: nowrap;
            text-align: right;
        }

        #tabla_videos_admin td a {
            color: #007bff;
            text-decoration: none;
        }

        #tabla_videos_admin td a:hover {
            text-decoration: underline;
        }

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

        /* --- Estilos Modal Básico --- */
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
        #formVideo .form-group {
            margin-bottom: 1rem;
        }

        #formVideo label {
            font-weight: bold;
            margin-bottom: 0.5rem;
            display: block;
        }

        #formVideo input[type="text"],
        #formVideo input[type="url"],
        #formVideo textarea,
        #formVideo input[type="file"] {
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

        #formVideo input[type="file"] {
            padding: .2rem .75rem;
        }

        #formVideo textarea {
            min-height: 80px;
            resize: vertical;
        }

        #formVideo input:focus,
        #formVideo textarea:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        }

        #formVideo small {
            color: #6c757d;
            display: block;
            margin-top: .25rem;
        }

        #formVideo .current-file-info {
            font-size: 0.85rem;
            font-style: italic;
            color: #555;
            margin-top: 5px;
        }

        #formVideo .current-file-info span {
            font-weight: bold;
        }

        #formVideo .current-file-info a {
            margin-left: 10px;
        }

        /* Vista Previa Video */
        #video_preview_container video {
            max-height: 300px;
            width: 100%;
            border-radius: 5px;
            background-color: #000;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row mb-3 align-items-center">
            <div class="col-md-9">
                <h4 class="main-title">
                    <i class="fas fa-video"></i> Administrador de Videos de Rutinas
                </h4>
            </div>
            <div class="col-md-3 text-right">
                <button class="btn btn-success" id="btnAgregarNuevoVideo">
                    <i class="fas fa-plus-circle"></i> Agregar Nuevo Video
                </button>
            </div>
        </div>

        <hr />

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list mr-2"></i> Lista de Ejercicios y Videos
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabla_videos_admin" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre Ejercicio</th>
                                <th>Archivo Video</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $tabla_ejercicios_html; // PHP genera las filas aquí 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    <div id="modalVideo" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVideoLabel">Agregar/Editar Video</h5>
                <button type="button" class="close-button" onclick="document.getElementById('modalVideo').style.display='none'" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formVideo" enctype="multipart/form-data">
                    <input type="hidden" id="ejercicio_id" name="ejercicio_id">

                    <div class="form-group" id="video_preview_container" style="display:none; margin-bottom: 1rem;">
                        <label>Vista Previa del Video Actual:</label>
                        <video width="100%" controls preload="metadata">
                            <source src="" type="video/mp4">
                            Tu navegador no soporta la vista previa.
                        </video>
                    </div>
                    <div class="form-group">
                        <label for="nombre_ejercicio">Nombre del Ejercicio:</label>
                        <input type="text" id="nombre_ejercicio" name="nombre_ejercicio" required>
                    </div>

                    <div class="form-group">
                        <label for="video_file">Subir Video (MP4):</label>
                        <input type="file" id="video_file" name="video_file" accept="video/mp4">
                        <small>Formato recomendado: MP4. Dejar vacío si no quieres cambiar el video.</small>
                        <div id="current_video_info" class="current-file-info" style="display: none;">Video Actual: <span></span></div>
                    </div>

                    <div class="form-group">
                        <label for="poster_file">Subir Poster (JPG, PNG) (Opcional):</label>
                        <input type="file" id="poster_file" name="poster_file" accept="image/jpeg, image/png">
                        <small>Dejar vacío si no quieres cambiar.</small>
                        <div id="current_poster_info" class="current-file-info" style="display: none;">Poster Actual: <span></span></div>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción (Opcional):</label>
                        <textarea id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="recomendaciones">Recomendaciones (Opcional):</label>
                        <textarea id="recomendaciones" name="recomendaciones" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalVideo').style.display='none'">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarVideo">Guardar Cambios</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="js/admin_videos.js"></script>

</body>

</html>
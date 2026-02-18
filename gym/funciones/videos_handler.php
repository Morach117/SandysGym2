<?php
// api/videos_handler.php

// --- 1. CONFIGURACIÓN INICIAL ---
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// --- 2. INCLUDES Y VERIFICACIÓN DE SESIÓN/CONEXIÓN ---
session_start();
include "../../funciones_globales/funciones_conexion.php";

$conexion = obtener_conexion();
// Respuesta por defecto
$response = ['success' => false, 'message' => 'Acción no válida o datos no proporcionados.'];

// Verificar conexión
if (!isset($conexion) || $conexion->connect_error) {
    $response['message'] = 'Error crítico de conexión a BD.';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

// Verificar si es admin
// if (!isset($_SESSION['admin'])) { ... }

// --- 3. DEFINIR RUTAS DE SUBIDA (Actualizadas) ---
// Rutas de *servidor* para MOVER archivos (desde la ubicación de este script PHP)
define('UPLOAD_DIR_VIDEOS', '../../sandys_web/assets/videos/');
define('UPLOAD_DIR_POSTERS', '../../sandys_web/assets/img/posters/');

/**
 * Procesa la subida de un archivo.
 * @return string|null Devuelve SOLO EL NOMBRE del archivo si tiene éxito, o null.
 */
function procesar_subida_archivo($fileKey, $uploadDir, $prefijo)
{
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES[$fileKey]['tmp_name'];

        // Limpiar nombre y hacerlo único
        $fileName = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES[$fileKey]['name']));
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = $prefijo . time() . '_' . uniqid() . '.' . $fileExtension;

        $destPath = $uploadDir . $newFileName;

        // Asegurarse que el directorio exista
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            // *** CAMBIO: Devolver solo el nombre del archivo ***
            return $newFileName;
        } else {
            throw new Exception("Error: No se pudo mover el archivo '{$fileName}' a '{$destPath}'. Verifica permisos.");
        }
    }
    return null; // No se subió archivo
}

// --- 4. MANEJAR ACCIONES AJAX ---
if (isset($_POST['action'])) {
    try {
        // --- ACCIÓN: GUARDAR (Agregar o Editar) ---
        if ($_POST['action'] === 'guardar_video') {
            $id = $_POST['ejercicio_id'] ?? null;
            $nombre = $_POST['nombre_ejercicio'];
            $descripcion = $_POST['descripcion'] ?? null;
            $recomendaciones = $_POST['recomendaciones'] ?? null;

            if (empty($nombre)) {
                throw new Exception('El nombre del ejercicio es obligatorio.');
            }

            // Procesar subidas (devuelven solo el nombre del archivo o NULL)
            $nuevoNombreVideo = procesar_subida_archivo('video_file', UPLOAD_DIR_VIDEOS, 'video_');
            $nuevoNombrePoster = procesar_subida_archivo('poster_file', UPLOAD_DIR_POSTERS, 'poster_');

            if (empty($id)) {
                // --- INSERTAR ---
                $query = "INSERT INTO ejercicios (nombre_ejercicio, descripcion, recomendaciones, video_url, poster_url) 
                          VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conexion, $query);
                if (!$stmt) throw new Exception("Error al preparar INSERT: " . mysqli_error($conexion));
                mysqli_stmt_bind_param($stmt, "sssss", $nombre, $descripcion, $recomendaciones, $nuevoNombreVideo, $nuevoNombrePoster);
            } else {
                // --- ACTUALIZAR ---
                // 1. Obtener nombres de archivos antiguos de la BD
                $querySel = "SELECT video_url, poster_url FROM ejercicios WHERE id_ejercicio = ?";
                $stmtSel = mysqli_prepare($conexion, $querySel);
                mysqli_stmt_bind_param($stmtSel, "i", $id);
                mysqli_stmt_execute($stmtSel);
                $resSel = mysqli_stmt_get_result($stmtSel);
                $archivosAntiguos = mysqli_fetch_assoc($resSel);
                mysqli_stmt_close($stmtSel);

                // 2. Determinar qué nombre de archivo guardar
                $videoAGuardar = $nuevoNombreVideo ? $nuevoNombreVideo : $archivosAntiguos['video_url'];
                $posterAGuardar = $nuevoNombrePoster ? $nuevoNombrePoster : $archivosAntiguos['poster_url'];

                // 3. Preparar el UPDATE
                $query = "UPDATE ejercicios SET 
                            nombre_ejercicio = ?, descripcion = ?, recomendaciones = ?, 
                            video_url = ?, poster_url = ?
                          WHERE id_ejercicio = ?";
                $stmt = mysqli_prepare($conexion, $query);
                if (!$stmt) throw new Exception("Error al preparar UPDATE: " . mysqli_error($conexion));
                mysqli_stmt_bind_param($stmt, "sssssi", $nombre, $descripcion, $recomendaciones, $videoAGuardar, $posterAGuardar, $id);
            }

            // Ejecutar consulta
            if (mysqli_stmt_execute($stmt)) {
                // 4. Si fue exitoso, borrar archivos antiguos (si se subieron nuevos)
                // *** CAMBIO: Reconstruir ruta del servidor para borrar ***
                if ($nuevoNombreVideo && !empty($archivosAntiguos['video_url'])) {
                    $rutaArchivoAntiguo = UPLOAD_DIR_VIDEOS . $archivosAntiguos['video_url'];
                    if (file_exists($rutaArchivoAntiguo)) @unlink($rutaArchivoAntiguo);
                }
                if ($nuevoNombrePoster && !empty($archivosAntiguos['poster_url'])) {
                    $rutaArchivoAntiguo = UPLOAD_DIR_POSTERS . $archivosAntiguos['poster_url'];
                    if (file_exists($rutaArchivoAntiguo)) @unlink($rutaArchivoAntiguo);
                }

                $response['success'] = true;
                $response['message'] = '¡Ejercicio guardado correctamente!';
            } else {
                throw new Exception("Error al ejecutar guardado: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        }

        // --- ACCIÓN: ELIMINAR ---
        elseif ($_POST['action'] === 'eliminar_video') {
            $id = $_POST['id_ejercicio'] ?? null;
            if (empty($id)) {
                throw new Exception('No se proporcionó ID para eliminar.');
            }

            // 1. Obtener nombres de archivos ANTES de borrar de la BD
            $querySel = "SELECT video_url, poster_url FROM ejercicios WHERE id_ejercicio = ?";
            $stmtSel = mysqli_prepare($conexion, $querySel);
            mysqli_stmt_bind_param($stmtSel, "i", $id);
            mysqli_stmt_execute($stmtSel);
            $resSel = mysqli_stmt_get_result($stmtSel);
            $archivos = mysqli_fetch_assoc($resSel);
            mysqli_stmt_close($stmtSel);

            // 2. Preparar el DELETE
            $query = "DELETE FROM ejercicios WHERE id_ejercicio = ?";
            $stmt = mysqli_prepare($conexion, $query);
            if (!$stmt) throw new Exception("Error al preparar DELETE: " . mysqli_error($conexion));
            mysqli_stmt_bind_param($stmt, "i", $id);

            // 3. Ejecutar DELETE
            if (mysqli_stmt_execute($stmt)) {
                // 4. Si se borró de la BD, borrar archivos del servidor
                // *** CAMBIO: Reconstruir ruta del servidor para borrar ***
                if ($archivos) {
                    if (!empty($archivos['video_url'])) {
                        $rutaVideoServidor = UPLOAD_DIR_VIDEOS . $archivos['video_url'];
                        if (file_exists($rutaVideoServidor)) @unlink($rutaVideoServidor);
                    }
                    if (!empty($archivos['poster_url'])) {
                        $rutaPosterServidor = UPLOAD_DIR_POSTERS . $archivos['poster_url'];
                        if (file_exists($rutaPosterServidor)) @unlink($rutaPosterServidor);
                    }
                }
                $response['success'] = true;
                $response['message'] = '¡Ejercicio eliminado!';
            } else {
                throw new Exception("Error al ejecutar eliminación: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        }
    } catch (Exception $e) {
        // Manejar cualquier error
        error_log("Error AJAX en videos_handler.php: " . $e->getMessage());
        $response['message'] = $e->getMessage();
        http_response_code(500);
    }
}

// --- 5. ENVIAR RESPUESTA JSON FINAL ---
echo json_encode($response);
exit; // Terminar script aquí.

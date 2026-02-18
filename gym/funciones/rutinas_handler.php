<?php
// --- 1. CONFIGURACIÓN INICIAL ---

include "../../funciones_globales/funciones_conexion.php";

$conexion = obtener_conexion();

// Suprimir errores de PHP para una respuesta JSON limpia
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json'); // La respuesta SIEMPRE será JSON

// --- 2. INICIAR SESIÓN Y CONEXIÓN ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 3. RESPUESTA POR DEFECTO ---
$response = ['success' => false, 'message' => 'Acción no reconocida o datos no proporcionados.'];

// --- 4. VERIFICACIONES ---
// Verificar si hay conexión
if (!isset($conexion) || $conexion->connect_error) {
    $response['message'] = 'Error crítico de conexión a BD.';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

// Verificar si es admin (¡importante!)
// if (!isset($_SESSION['admin'])) {
//     $response['message'] = 'Acceso denegado. Se requiere sesión de administrador.';
//     http_response_code(403);
//     echo json_encode($response);
//     exit;
// }

// Verificar si se envió una acción
if (isset($_POST['action'])) {

    try {
        // --- ACCIÓN: GUARDAR (Agregar o Editar) ---
        if ($_POST['action'] === 'guardar_asignacion') {
            
            // Recoger datos del formulario
            $id = $_POST['rutina_ejercicio_id'] ?? null;
            $nivel = $_POST['id_nivel'];
            $genero = $_POST['genero'];
            $grupo = $_POST['id_grupo_muscular'];
            $ejercicio = $_POST['id_ejercicio'];
            $orden = $_POST['orden_ejercicio'];
            $series = $_POST['series'];
            $reps = $_POST['repeticiones'];
            $descanso = $_POST['descanso_seg'];

            // Validar datos básicos (puedes añadir más validaciones)
            if (empty($nivel) || empty($genero) || empty($grupo) || empty($ejercicio) || empty($series) || empty($reps)) {
                throw new Exception('Faltan campos obligatorios.');
            }

            if (empty($id)) {
                // --- INSERTAR (Agregar Nuevo) ---
                $query = "INSERT INTO rutina_ejercicios (id_nivel, genero, id_grupo_muscular, id_ejercicio, orden_ejercicio, series, repeticiones, descanso_seg)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conexion, $query);
                if (!$stmt) throw new Exception("Error al preparar INSERT: " . mysqli_error($conexion));
                mysqli_stmt_bind_param($stmt, "iiiiisss", $nivel, $genero, $grupo, $ejercicio, $orden, $series, $reps, $descanso);
            } else {
                // --- ACTUALIZAR (Editar) ---
                $query = "UPDATE rutina_ejercicios SET
                            id_nivel = ?, genero = ?, id_grupo_muscular = ?, id_ejercicio = ?,
                            orden_ejercicio = ?, series = ?, repeticiones = ?, descanso_seg = ?
                          WHERE id_rutina_ejercicio = ?";
                $stmt = mysqli_prepare($conexion, $query);
                if (!$stmt) throw new Exception("Error al preparar UPDATE: " . mysqli_error($conexion));
                mysqli_stmt_bind_param($stmt, "iiiiisssi", $nivel, $genero, $grupo, $ejercicio, $orden, $series, $reps, $descanso, $id);
            }

            // Ejecutar
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = '¡Asignación guardada correctamente!';
            } else {
                throw new Exception("Error al ejecutar guardado: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        }

        // --- ACCIÓN: ELIMINAR ---
        elseif ($_POST['action'] === 'eliminar_asignacion') {
            $id = $_POST['rutina_ejercicio_id'] ?? null;
            if (empty($id)) {
                throw new Exception('No se proporcionó ID para eliminar.');
            }

            $query = "DELETE FROM rutina_ejercicios WHERE id_rutina_ejercicio = ?";
            $stmt = mysqli_prepare($conexion, $query);
            if (!$stmt) throw new Exception("Error al preparar DELETE: " . mysqli_error($conexion));

            mysqli_stmt_bind_param($stmt, "i", $id);

            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = '¡Asignación eliminada!';
            } else {
                throw new Exception("Error al ejecutar eliminación: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        }
    
    } catch (Exception $e) {
        // Manejar cualquier error
        error_log("Error AJAX en rutinas_handler.php: " . $e->getMessage());
        $response['message'] = $e->getMessage();
        http_response_code(500); // Enviar un código de error de servidor
    }
}

// --- 5. ENVIAR RESPUESTA JSON FINAL ---
echo json_encode($response);
exit; // Terminar script aquí.
?>
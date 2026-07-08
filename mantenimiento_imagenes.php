<?php
// 1. Configuración de la base de datos
$host     = 'db5002171142.hosting-data.io';
$db_name  = 'dbs1756575'; 
$username = 'dbu577361';       
$password = 'Sandys_empresas_2';      

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 2. Configuración de rutas
$directorio_imagenes = $_SERVER['DOCUMENT_ROOT'] . '/imagenes/avatar/';
$extensiones = ['jpg', 'jpeg', 'png']; 

$mensaje = "";

// 3. PROCESAR ACCIONES (Cuando se envía un formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update_stmt = $pdo->prepare("UPDATE san_socios SET soc_imagen = :nueva_imagen WHERE soc_id_socio = :id");

    // ACCIÓN A: CORREGIR TODOS DE GOLPE
    if (isset($_POST['arreglar_todos'])) {
        // Traemos todos los registros para verificarlos
        $stmt_check = $pdo->query("SELECT soc_id_socio, soc_imagen FROM san_socios");
        $todos = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
        $corregidos = 0;

        foreach ($todos as $s) {
            $id = $s['soc_id_socio'];
            $img_bd = $s['soc_imagen'];

            foreach ($extensiones as $ext) {
                if (file_exists($directorio_imagenes . $id . '.' . $ext)) {
                    $nombre_correcto = $id . '.' . $ext;
                    
                    // Si el archivo existe pero en la BD dice otra cosa, lo actualizamos
                    if ($img_bd !== $nombre_correcto) {
                        $update_stmt->execute([
                            ':nueva_imagen' => $nombre_correcto,
                            ':id'           => $id
                        ]);
                        $corregidos++;
                    }
                    break; // Ya encontramos su imagen, pasamos al siguiente socio
                }
            }
        }
        $mensaje = "<h3 style='color: green;'>¡Proceso masivo completado! Se corrigieron $corregidos registros exitosamente.</h3>";
    } 
    
    // ACCIÓN B: CORREGIR SOLO UNO (Manual o de la lista)
    elseif (isset($_POST['arreglar_individual'])) {
        $id_especifico = !empty($_POST['id_manual']) ? (int)$_POST['id_manual'] : (int)$_POST['id_lista'];

        if ($id_especifico > 0) {
            $stmt = $pdo->prepare("SELECT soc_imagen FROM san_socios WHERE soc_id_socio = :id");
            $stmt->execute([':id' => $id_especifico]);
            $socio = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($socio) {
                $img_bd_actual = $socio['soc_imagen'];
                $archivo_fisico_encontrado = false;
                $nombre_archivo_final = '';

                foreach ($extensiones as $ext) {
                    if (file_exists($directorio_imagenes . $id_especifico . '.' . $ext)) {
                        $archivo_fisico_encontrado = true;
                        $nombre_archivo_final = $id_especifico . '.' . $ext; 
                        break; 
                    }
                }

                if ($archivo_fisico_encontrado && $img_bd_actual !== $nombre_archivo_final) {
                    $update_stmt->execute([
                        ':nueva_imagen' => $nombre_archivo_final,
                        ':id'           => $id_especifico
                    ]);
                    $mensaje = "<h3 style='color: green;'>¡El ID $id_especifico fue corregido con éxito! (Nuevo nombre: $nombre_archivo_final)</h3>";
                } else if ($archivo_fisico_encontrado && $img_bd_actual === $nombre_archivo_final) {
                    $mensaje = "<h3 style='color: blue;'>El ID $id_especifico ya estaba correcto.</h3>";
                } else {
                    $mensaje = "<h3 style='color: red;'>No se encontró archivo físico para el ID $id_especifico.</h3>";
                }
            }
        }
    }
}

// 4. ESCANEO PARA LA INTERFAZ (Se ejecuta DESPUÉS de las correcciones para mostrar datos actualizados)
$stmt_lista = $pdo->query("SELECT soc_id_socio, soc_imagen FROM san_socios");
$todos_socios = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);

$socios_con_error = [];

foreach ($todos_socios as $s) {
    $id = $s['soc_id_socio'];
    $img_bd = $s['soc_imagen'];

    // Buscar si existe el archivo físico
    foreach ($extensiones as $ext) {
        if (file_exists($directorio_imagenes . $id . '.' . $ext)) {
            $nombre_correcto = $id . '.' . $ext;
            
            // Si el archivo físico EXISTE pero en la BD está mal vinculado (o vacío)
            if ($img_bd !== $nombre_correcto) {
                $socios_con_error[] = [
                    'id' => $id,
                    'actual' => $img_bd ? $img_bd : 'Sin imagen',
                    'correcto' => $nombre_correcto
                ];
            }
            break; 
        }
    }
}

$total_errores = count($socios_con_error);
?>

<h2>Panel de Corrección de Imágenes</h2>

<?= $mensaje ?>

<?php if ($total_errores > 0): ?>
    <p>Se detectaron <strong><?= $total_errores ?></strong> registros que tienen foto en el servidor pero el nombre está mal en la base de datos.</p>

    <hr>
    
    <form method="POST" action="" onsubmit="return confirm('¿Estás seguro de corregir los <?= $total_errores ?> registros de un solo golpe?');">
        <h3>Opción Rápida:</h3>
        <input type="submit" name="arreglar_todos" value="Corregir TODOS de una vez" style="padding: 10px; background-color: #28a745; color: white; border: none; cursor: pointer;">
    </form>

    <hr>

    <form method="POST" action="">
        <h3>Opción Manual:</h3>
        
        <label><strong>1. Escribe el ID específico (Opcional)</strong></label><br>
        <input type="number" name="id_manual" placeholder="Ej: 11040"><br><br>
        
        <label><strong>2. O selecciona un socio con error de la lista</strong></label><br>
        <select name="id_lista">
            <option value="">-- Selecciona un socio con error --</option>
            <?php foreach ($socios_con_error as $error): ?>
                <option value="<?= $error['id'] ?>">
                    ID: <?= $error['id'] ?> | BD dice: "<?= $error['actual'] ?>" | Debería ser: "<?= $error['correcto'] ?>"
                </option>
            <?php endforeach; ?>
        </select><br><br>
        
        <input type="submit" name="arreglar_individual" value="Corregir Individualmente">
    </form>

<?php else: ?>
    <hr>
    <h3 style="color: blue;">¡Excelente! No se detectaron errores. Todos los socios que tienen archivo físico están correctamente vinculados en la base de datos.</h3>
<?php endif; ?>
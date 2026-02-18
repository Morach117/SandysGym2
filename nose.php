<?php
// Configuración de la Base de Datos
$host = 'localhost';
$usuario = 'root';
$contrasena = 'tu_contraseña';
$nombre_bd = 'nombre_de_tu_bd';

// Conexión
$mysqli = new mysqli($host, $usuario, $contrasena, $nombre_bd);
if ($mysqli->connect_error) {
    die('Error de conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8');

// Nombre del archivo de respaldo
$fecha = date("Y-m-d_H-i-s");
$nombre_archivo = "respaldo_{$nombre_bd}_{$fecha}.sql";

// Preparar el archivo para la descarga
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($nombre_archivo) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

$handle = fopen('php://output', 'w');

// Obtener todas las tablas
$tablas = [];
$resultado = $mysqli->query("SHOW TABLES");
while ($fila = $resultado->fetch_row()) {
    $tablas[] = $fila[0];
}

$salida_sql = "";

// Recorrer cada tabla
foreach ($tablas as $tabla) {
    // Añadir la sentencia DROP TABLE
    $salida_sql .= "DROP TABLE IF EXISTS `{$tabla}`;\n\n";

    // Añadir la sentencia CREATE TABLE
    $resultado_create = $mysqli->query("SHOW CREATE TABLE `{$tabla}`");
    $fila_create = $resultado_create->fetch_row();
    $salida_sql .= $fila_create[1] . ";\n\n";

    // Obtener y añadir los datos
    $resultado_datos = $mysqli->query("SELECT * FROM `{$tabla}`");
    $num_campos = $resultado_datos->field_count;
    
    while ($fila_datos = $resultado_datos->fetch_row()) {
        $salida_sql .= "INSERT INTO `{$tabla}` VALUES(";
        for ($j = 0; $j < $num_campos; $j++) {
            // Escapar caracteres especiales
            $fila_datos[$j] = $mysqli->real_escape_string($fila_datos[$j]);
            $fila_datos[$j] = str_replace("\n", "\\n", $fila_datos[$j]);
            
            if (isset($fila_datos[$j])) {
                $salida_sql .= '"' . $fila_datos[$j] . '"';
            } else {
                $salida_sql .= '""';
            }
            if ($j < ($num_campos - 1)) {
                $salida_sql .= ',';
            }
        }
        $salida_sql .= ");\n";
    }
    $salida_sql .= "\n\n";
}

// Escribir el contenido en el archivo de salida
fwrite($handle, $salida_sql);
fclose($handle);
$mysqli->close();
exit;
?>
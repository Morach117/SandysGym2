<?php
require_once(__DIR__ . "/../../funciones_globales/funciones_conexion.php");
require_once(__DIR__ . "/../../funciones_globales/funciones_phpBB.php");

/**
 * Consulta la base de datos para obtener la duración real (meses y días) del servicio/membresía.
 * Usa consultas preparadas PDO/MySQLi para prevenir inyección SQL y optimizar el rendimiento.
 *
 * @param int $id_servicio
 * @return array|null Retorna ['meses' => int, 'dias' => int, 'descripcion' => string] o null si no se encuentra
 */
if (!function_exists('obtener_duracion_servicio_bd')) {
    function obtener_duracion_servicio_bd($id_servicio)
    {
        if ($id_servicio <= 0) {
            return null;
        }

        // 1. Intentar con PDO (cumpliendo con la directriz de seguridad y planes de consulta)
        try {
            $host = getenv('DB_HOST') ?: "localhost";
            $db   = getenv('DB_NAME') ?: "dbs1756575";
            $user = getenv('DB_USER') ?: "root";
            $pass = getenv('DB_PASS') ?: "";

            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 1,
            ];
            $pdo = new PDO($dsn, $user, $pass, $options);
            $stmt = $pdo->prepare("SELECT ser_meses, ser_dias, ser_descripcion FROM san_servicios WHERE ser_id_servicio = ? LIMIT 1");
            $stmt->execute([$id_servicio]);
            $fila = $stmt->fetch();

            if ($fila) {
                return [
                    'meses'       => isset($fila['ser_meses']) ? (int)$fila['ser_meses'] : 0,
                    'dias'        => isset($fila['ser_dias']) ? (int)$fila['ser_dias'] : 0,
                    'descripcion' => isset($fila['ser_descripcion']) ? trim($fila['ser_descripcion']) : ''
                ];
            }
        } catch (Throwable $e) {
            // En caso de fallo con PDO, intentar fallback con mysqli
        }

        // 2. Fallback con MySQLi usando la conexión global del sistema con timeout rápido
        try {
            if (function_exists('obtener_conexion')) {
                mysqli_report(MYSQLI_REPORT_OFF);
                $conexion = @obtener_conexion();
                if ($conexion) {
                    $stmt = mysqli_prepare($conexion, "SELECT ser_meses, ser_dias, ser_descripcion FROM san_servicios WHERE ser_id_servicio = ? LIMIT 1");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "i", $id_servicio);
                        mysqli_stmt_execute($stmt);
                        $resultado = mysqli_stmt_get_result($stmt);
                        if ($resultado && $fila = mysqli_fetch_assoc($resultado)) {
                            mysqli_stmt_close($stmt);
                            @mysqli_close($conexion);
                            return [
                                'meses'       => isset($fila['ser_meses']) ? (int)$fila['ser_meses'] : 0,
                                'dias'        => isset($fila['ser_dias']) ? (int)$fila['ser_dias'] : 0,
                                'descripcion' => isset($fila['ser_descripcion']) ? trim($fila['ser_descripcion']) : ''
                            ];
                        }
                        mysqli_stmt_close($stmt);
                    }
                    @mysqli_close($conexion);
                }
            }
        } catch (Throwable $e) {
            // Continuar si hay error
        }

        return null;
    }
}

$envio    = isset($_POST['envio']) || isset($_GET['envio']) || !empty($_POST['servicio']) || !empty($_GET['servicio']);
$fecha    = request_var('fecha', '');
$servicio = request_var('servicio', '');

if ($envio) {
    $fecha_limpia    = str_replace('/', '-', trim($fecha));
    $servicio_limpio = trim($servicio);

    if (empty($fecha_limpia) || empty($servicio_limpio)) {
        echo "Error: El formato de fecha o servicio es incorrecto.";
        exit;
    }

    // Validación de fecha (admite d-m-Y o Y-m-d)
    $partes_fecha = explode('-', $fecha_limpia);
    if (count($partes_fecha) !== 3) {
        echo "Error: El formato de fecha o servicio es incorrecto.";
        exit;
    }

    if (strlen($partes_fecha[0]) === 4) {
        $año = (int)$partes_fecha[0];
        $mes = (int)$partes_fecha[1];
        $dia = (int)$partes_fecha[2];
    } else {
        $dia = (int)$partes_fecha[0];
        $mes = (int)$partes_fecha[1];
        $año = (int)$partes_fecha[2];
    }

    if (!checkdate($mes, $dia, $año) || $año < 1900 || $año > 2100) {
        echo "Error: La fecha o el período de servicio no son válidos.";
        exit;
    }

    // Desglosar el parámetro de servicio (soporta 'id', 'id-meses', 'id-meses-dias')
    $partes_servicio = explode('-', $servicio_limpio);
    $id_servicio     = isset($partes_servicio[0]) ? (int)$partes_servicio[0] : 0;
    $meses_param     = isset($partes_servicio[1]) ? (int)$partes_servicio[1] : 0;
    $dias_param      = isset($partes_servicio[2]) ? (int)$partes_servicio[2] : 0;

    $meses = $meses_param;
    $dias  = $dias_param;

    // Consultar en la base de datos la membresía
    $datos_bd = obtener_duracion_servicio_bd($id_servicio);

    if ($datos_bd !== null) {
        $meses = $datos_bd['meses'];
        $dias  = $datos_bd['dias'];

        // Si en la base de datos ambos valores están en 0, analizar la descripción como soporte
        if ($meses === 0 && $dias === 0 && !empty($datos_bd['descripcion'])) {
            $desc = $datos_bd['descripcion'];
            if (preg_match('/(\d+)\s*(?:d[ií]as?|days?)/i', $desc, $m)) {
                $dias = (int)$m[1];
            } elseif (preg_match('/(\d+)\s*(?:mes(?:es)?|months?)/i', $desc, $m)) {
                $meses = (int)$m[1];
            } elseif (preg_match('/quincen(?:a|al)/i', $desc)) {
                $dias = 15;
            } elseif (preg_match('/seman(?:a|al)/i', $desc)) {
                $dias = 7;
            } elseif (preg_match('/anual(?:idad)?/i', $desc)) {
                $meses = 12;
            } elseif (preg_match('/semestr(?:e|al)/i', $desc)) {
                $meses = 6;
            } elseif (preg_match('/trimestr(?:e|al)/i', $desc)) {
                $meses = 3;
            } elseif (preg_match('/bimestr(?:e|al)/i', $desc)) {
                $meses = 2;
            } elseif (preg_match('/mensual(?:idad)?/i', $desc)) {
                $meses = 1;
            }
        }
    }

    $meses = max(0, $meses);
    $dias  = max(0, $dias);

    $fecha_inicial = new DateTime();
    $fecha_inicial->setDate($año, $mes, $dia);
    $fecha_inicial->setTime(0, 0, 0);

    // Sumar meses si la membresía incluye meses
    if ($meses > 0) {
        $target_month   = ($mes - 1 + $meses) % 12 + 1;
        $target_year    = $año + intdiv($mes - 1 + $meses, 12);
        $dias_en_target = (int)date('t', strtotime(sprintf('%04d-%02d-01', $target_year, $target_month)));
        $target_day     = min($dia, $dias_en_target);
        $fecha_inicial->setDate($target_year, $target_month, $target_day);
    }

    // Sumar días (para membresías de 15 días, 7 días, o cualquier cantidad que indique la BD)
    if ($dias > 0) {
        $fecha_inicial->modify("+$dias days");
    }

    echo $fecha_inicial->format('d-m-Y');
}
?>

<?php
// /api/lib/UserService.php

require_once __DIR__ . '/EmailService.php';

class UserService {
    private $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    // --- FUNCIONES DE BÚSQUEDA ---
    private function findUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT soc_id_socio FROM san_socios WHERE soc_correo = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    private function findUserByPhone($telefono) {
        $tel = preg_replace('/[^0-9]/', '', $telefono);
        $stmt = $this->conn->prepare("SELECT soc_id_socio FROM san_socios WHERE REPLACE(REPLACE(REPLACE(REPLACE(soc_tel_cel, ' ', ''), '-', ''), '(', ''), ')', '') = ? AND (soc_correo_status = 0 OR soc_correo_status IS NULL)");
        $stmt->execute([$tel]);
        return $stmt->fetch();
    }
    
    private function findPadrino($telefono) {
        $tel = preg_replace('/[^0-9]/', '', $telefono);
        $stmt = $this->conn->prepare("SELECT soc_id_socio, soc_nombres, soc_correo FROM san_socios WHERE REPLACE(REPLACE(REPLACE(REPLACE(soc_tel_cel, ' ', ''), '-', ''), '(', ''), ')', '') = ?");
        $stmt->execute([$tel]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function findUserByName($name, $paternal, $maternal) {
        $q = "SELECT soc_id_socio FROM san_socios WHERE UPPER(TRIM(soc_nombres)) = UPPER(?) AND UPPER(TRIM(soc_apepat)) = UPPER(?) AND (soc_correo_status = 0 OR soc_correo_status IS NULL)";
        $p = [$name, $paternal];
        if (!empty($maternal)) { $q .= " AND UPPER(TRIM(soc_apemat)) = UPPER(?)"; $p[] = $maternal; }
        $q .= " LIMIT 1";
        $stmt = $this->conn->prepare($q);
        $stmt->execute($p);
        return $stmt->fetch();
    }

    /**
     * REGISTRO PRINCIPAL
     */
    public function registerOrUpdate($name, $paternal, $maternal, $email, $rawPassword, $telefono, $genero, $fecha_nacimiento_sql, $referral_code = null) {
        
        $password = password_hash($rawPassword, PASSWORD_DEFAULT);
        // Generar un código de validación seguro de 6 dígitos
        $val_code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $fecha = date("Y-m-d H:i:s");
        
        // 1. LÓGICA REFERIDO (SOLO VINCULACIÓN, SIN DINERO INICIAL)
        $idPadrino = 0;
        $saldoInicial = 0.00; // El nuevo usuario siempre entra con $0 en el monedero

        if (!empty($referral_code)) {
            $ref_clean = preg_replace('/[^0-9]/', '', $referral_code);
            $tel_clean = preg_replace('/[^0-9]/', '', $telefono);

            if ($ref_clean !== $tel_clean) {
                $datosPadrino = $this->findPadrino($ref_clean);
                if ($datosPadrino) {
                    // Guardamos el ID del padrino para saber quién lo invitó.
                    // Nadie recibe dinero en este punto.
                    $idPadrino = $datosPadrino['soc_id_socio'];
                }
            }
        }

        $genero_db = !empty($genero) ? strtoupper(substr($genero, 0, 1)) : 'M';
        $userByEmail = $this->findUserByEmail($email);

        try {
            if ($userByEmail) {
                // Lanzar excepción para evitar secuestro de cuentas
                throw new Exception("Correo ya existe, favor de introducir uno diferente");
            } else {
                // INSERT NUEVO
                $query = "INSERT INTO san_socios (
                            soc_nombres, soc_apepat, soc_apemat, soc_correo, san_password, 
                            soc_fecha_captura, soc_fecha_nacimiento, soc_genero, 
                            validation_code, validation_expires, soc_id_usuario, soc_id_empresa, soc_id_consorcio, 
                            soc_tel_cel, soc_correo_status, is_active,
                            soc_id_referido_por, soc_mon_saldo 
                          ) VALUES (
                            ?, ?, ?, ?, ?, 
                            ?, ?, ?, 
                            ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), 1, 1, 1, 
                            ?, 0, 0,
                            ?, ? 
                          )";
                
                $stmt = $this->conn->prepare($query);
                $stmt->execute([
                    $name, $paternal, $maternal, $email, $password, 
                    $fecha, $fecha_nacimiento_sql, $genero_db, 
                    $val_code, $telefono,
                    $idPadrino, $saldoInicial 
                ]);
                
                // Ya NO llamamos a darRecompensaPadrino() aquí.
                // Queda pendiente para cuando pague en caja.
            }
            return $val_code;

        } catch (PDOException $e) {
            error_log("Error UserService: " . $e->getMessage()); 
            return false;
        }
    }

    // --- FUNCIONES PRIVADAS (Y PUBLICAS PARA LA CAJA) ---

    private function registrarHistorial($idSocio, $monto, $concepto, $idSistema) {
        $fecha = date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare("SELECT soc_mon_saldo FROM san_socios WHERE soc_id_socio = ?");
        $stmt->execute([$idSocio]);
        $saldo = $stmt->fetchColumn();

        $sql = "INSERT INTO san_prepago_detalle (pred_id_socio, pred_fecha, pred_movimiento, pred_importe, pred_saldo, pred_descripcion, pred_id_usuario) VALUES (?, ?, 'A', ?, ?, ?, ?)";
        $this->conn->prepare($sql)->execute([$idSocio, $fecha, $monto, $saldo, $concepto, $idSistema]);
    }

    /**
     * Esta función debe ser llamada desde tu script de VENTAS/CAJA
     * cuando el nuevo socio pague su primera mensualidad.
     */
    public function darRecompensaPadrino($idPadrino, $padrinoData, $nombreNuevoSocio) {
        $idSistema = 1; 
        
        // Obtenemos el monto dinámico desde la configuración de la base de datos
        $monto = $this->getMontoReferido();

        if ($monto <= 0) return; // Si la configuración está en 0, no damos bono

        try {
            // 1. Update Saldo
            $this->conn->prepare("UPDATE san_socios SET soc_mon_saldo = soc_mon_saldo + ? WHERE soc_id_socio = ?")->execute([$monto, $idPadrino]);
            
            // 2. Historial
            $this->registrarHistorial($idPadrino, $monto, "Referido: $nombreNuevoSocio", $idSistema);

            // 3. Email (Lógica con Plantilla)
            if (!empty($padrinoData['soc_correo'])) {
                
                // Obtenemos el nuevo saldo REAL de la base de datos para mostrarlo en el correo
                $stmtSaldo = $this->conn->prepare("SELECT soc_mon_saldo FROM san_socios WHERE soc_id_socio = ?");
                $stmtSaldo->execute([$idPadrino]);
                $nuevoSaldo = $stmtSaldo->fetchColumn();

                // Variables para la plantilla
                $nombrePadrino = $padrinoData['soc_nombres'];
                $asunto = "¡Ganaste $" . number_format($monto, 0) . " MXN! 💰";

                ob_start();
                $rutaPlantilla = __DIR__ . '/../templates/referral_notification.php';
                
                if (file_exists($rutaPlantilla)) {
                    include $rutaPlantilla;
                } else {
                    echo "<h1>¡Felicidades $nombrePadrino!</h1><p>Tu referido $nombreNuevoSocio pagó su inscripción. Ganaste $$monto.</p>";
                }
                
                $mensajeHTML = ob_get_clean();

                @EmailService::send($padrinoData['soc_correo'], $nombrePadrino, $asunto, $mensajeHTML);
            }
        } catch (Exception $e) {
            error_log("Error bono padrino: " . $e->getMessage());
        }
    }

    /**
     * Obtiene dinámicamente el monto para referidos de la base de datos
     */
    private function getMontoReferido() {
        try {
            // Consultamos la tabla consorcios asumiendo el ID 1 
            $stmt = $this->conn->prepare("SELECT con_referidos FROM san_consorcios WHERE con_id_consorcio = 1 LIMIT 1");
            $stmt->execute();
            $monto = $stmt->fetchColumn();
            
            return !empty($monto) ? (float)$monto : 70.00;
        } catch (PDOException $e) {
            error_log("Error obteniendo bono de referido: " . $e->getMessage());
            return 70.00;
        }
    }
}
?>
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
        $val_code = substr(str_shuffle("0123456789"), 0, 4);
        $fecha = date("Y-m-d H:i:s");
        
        // 1. LÓGICA REFERIDO
        $idPadrino = 0;
        $saldoInicial = 0.00; 
        $datosPadrino = null;

        if (!empty($referral_code)) {
            $ref_clean = preg_replace('/[^0-9]/', '', $referral_code);
            $tel_clean = preg_replace('/[^0-9]/', '', $telefono);

            if ($ref_clean !== $tel_clean) {
                $datosPadrino = $this->findPadrino($ref_clean);
                if ($datosPadrino) {
                    $idPadrino = $datosPadrino['soc_id_socio'];
                    $saldoInicial = 35.00; 
                }
            }
        }

        $genero_db = !empty($genero) ? strtoupper(substr($genero, 0, 1)) : 'M';
        $userByEmail = $this->findUserByEmail($email);

        try {
            if ($userByEmail) {
                // UPDATE
                $sql = "UPDATE san_socios SET san_password=?, validation_code=?, soc_nombres=?, soc_apepat=?, soc_apemat=?, soc_fecha_captura=?, soc_tel_cel=?, soc_genero=?, soc_fecha_nacimiento=?, soc_correo_status=0 WHERE soc_correo=?";
                $this->conn->prepare($sql)->execute([$password, $val_code, $name, $paternal, $maternal, $fecha, $telefono, $genero_db, $fecha_nacimiento_sql, $email]);
            
            } else {
                // INSERT NUEVO
                $query = "INSERT INTO san_socios (
                            soc_nombres, soc_apepat, soc_apemat, soc_correo, san_password, 
                            soc_fecha_captura, soc_fecha_nacimiento, soc_genero, 
                            validation_code, soc_id_usuario, soc_id_empresa, soc_id_consorcio, 
                            soc_tel_cel, soc_correo_status, is_active,
                            soc_id_referido_por, soc_mon_saldo 
                          ) VALUES (
                            ?, ?, ?, ?, ?, 
                            ?, ?, ?, 
                            ?, 1, 1, 1, 
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
                
                $idNuevoSocio = $this->conn->lastInsertId();

                // HISTORIAL NUEVO USUARIO
                if ($saldoInicial > 0 && $idNuevoSocio) {
                    $this->registrarHistorial($idNuevoSocio, $saldoInicial, "Bono de Bienvenida (Referido)", 1);
                }

                // RECOMPENSA PADRINO
                if ($idPadrino > 0) {
                    $this->darRecompensaPadrino($idPadrino, $datosPadrino, "$name $paternal");
                }
            }
            return $val_code;

        } catch (PDOException $e) {
            error_log("Error UserService: " . $e->getMessage()); 
            return false;
        }
    }

    // --- FUNCIONES PRIVADAS ---

    private function registrarHistorial($idSocio, $monto, $concepto, $idSistema) {
        $fecha = date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare("SELECT soc_mon_saldo FROM san_socios WHERE soc_id_socio = ?");
        $stmt->execute([$idSocio]);
        $saldo = $stmt->fetchColumn();

        $sql = "INSERT INTO san_prepago_detalle (pred_id_socio, pred_fecha, pred_movimiento, pred_importe, pred_saldo, pred_descripcion, pred_id_usuario) VALUES (?, ?, 'A', ?, ?, ?, ?)";
        $this->conn->prepare($sql)->execute([$idSocio, $fecha, $monto, $saldo, $concepto, $idSistema]);
    }

    private function darRecompensaPadrino($idPadrino, $padrinoData, $nombreNuevoSocio) {
        $monto = 35.00;
        $idSistema = 1; 

        try {
            // 1. Update Saldo
            $this->conn->prepare("UPDATE san_socios SET soc_mon_saldo = soc_mon_saldo + ? WHERE soc_id_socio = ?")->execute([$monto, $idPadrino]);
            
            // 2. Historial
            $this->registrarHistorial($idPadrino, $monto, "Referido: $nombreNuevoSocio", $idSistema);

            // 3. Email (Lógica Mejorada con Plantilla)
            if (!empty($padrinoData['soc_correo'])) {
                
                // Obtenemos el nuevo saldo REAL de la base de datos para mostrarlo en el correo
                $stmtSaldo = $this->conn->prepare("SELECT soc_mon_saldo FROM san_socios WHERE soc_id_socio = ?");
                $stmtSaldo->execute([$idPadrino]);
                $nuevoSaldo = $stmtSaldo->fetchColumn();

                // Variables para la plantilla
                $nombrePadrino = $padrinoData['soc_nombres'];
                $asunto = "¡Ganaste $" . number_format($monto, 0) . " MXN! 💰";

                // --- AQUÍ ESTÁ LA MAGIA DEL TEMPLATE ---
                ob_start();
                $rutaPlantilla = __DIR__ . '/../templates/referral_notification.php';
                
                if (file_exists($rutaPlantilla)) {
                    include $rutaPlantilla;
                } else {
                    // Fallback simple por si borran el archivo
                    echo "<h1>¡Felicidades $nombrePadrino!</h1><p>Tu referido $nombreNuevoSocio se registró. Ganaste $$monto.</p>";
                }
                
                $mensajeHTML = ob_get_clean();
                // ---------------------------------------

                @EmailService::send($padrinoData['soc_correo'], $nombrePadrino, $asunto, $mensajeHTML);
            }
        } catch (Exception $e) {
            error_log("Error bono padrino: " . $e->getMessage());
        }
    }
}
?>
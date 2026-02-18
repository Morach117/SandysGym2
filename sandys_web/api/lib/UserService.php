<?php
// /api/lib/UserService.php

class UserService {
    private $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    // --- FUNCIONES DE BÚSQUEDA ---

    private function findUserByEmail($email) {
        $checkStmt = $this->conn->prepare("SELECT soc_id_socio FROM san_socios WHERE soc_correo = ?");
        $checkStmt->execute([$email]);
        return $checkStmt->fetch();
    }

    /**
     * Busca un socio por teléfono QUE AÚN NO TENGA CUENTA VALIDADA.
     */
    private function findUserByPhone($telefono) {
        // <-- ACTUALIZADO: Comprueba el status de validación, no si el correo está vacío -->
        $query = "SELECT soc_id_socio FROM san_socios 
                  WHERE TRIM(soc_tel_cel) = ? 
                  AND (soc_correo_status = 0 OR soc_correo_status IS NULL)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$telefono]);
        return $stmt->fetch();
    }

    /**
     * Busca un socio por nombre completo QUE AÚN NO TENGA CUENTA VALIDADA.
     */
    private function findUserByName($name, $paternal, $maternal) {
        
        // <-- ACTUALIZADO: Comprueba el status de validación, no si el correo está vacío -->
        $query = "SELECT soc_id_socio FROM san_socios 
                  WHERE UPPER(TRIM(soc_nombres)) = UPPER(?) 
                    AND UPPER(TRIM(soc_apepat)) = UPPER(?) 
                    AND (soc_correo_status = 0 OR soc_correo_status IS NULL)";
        
        $params = [$name, $paternal];

        // SÓLO si el usuario escribió un apellido materno, lo añadimos a la búsqueda.
        if (!empty($maternal)) {
            $query .= " AND UPPER(TRIM(soc_apemat)) = UPPER(?)";
            $params[] = $maternal;
        }

        $query .= " LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    // --- FIN FUNCIONES DE BÚSQUEDA ---


    /**
     * Registra un usuario nuevo o ACTUALIZA un socio existente sin cuenta.
     */
    public function registerOrUpdate($name, $paternal, $maternal, $email, $rawPassword, $telefono) {
        
        $password = password_hash($rawPassword, PASSWORD_DEFAULT);
        $validation_code = substr(str_shuffle("0123456789"), 0, 4);
        $fecha_captura = date("Y-m-d H:i:s");
        
        $userByEmail = $this->findUserByEmail($email);

        try {
            if ($userByEmail) {
                // CASO 1: El correo YA EXISTE.
                // Actualiza el socio encontrado por correo.
                $query = "UPDATE san_socios 
                          SET san_password = ?, validation_code = ?, soc_nombres = ?, soc_apepat = ?, soc_apemat = ?, soc_fecha_captura = ?, soc_tel_cel = ?, soc_correo_status = 0
                          WHERE soc_correo = ?"; // <-- Añade soc_correo_status = 0 para forzar re-validación
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$password, $validation_code, $name, $paternal, $maternal, $fecha_captura, $telefono, $email]);
            
            } else {
                // CASO 2: El correo es NUEVO.
                // Buscamos si el socio ya existe (por teléfono o nombre) para no duplicarlo.
                
                $socio_id_to_update = false;
                
                // 2.1. Buscar por teléfono (función ya actualizada)
                $userByPhone = $this->findUserByPhone($telefono);
                
                if ($userByPhone) {
                    $socio_id_to_update = $userByPhone['soc_id_socio'];
                } else {
                    // 2.2. Si no, buscar por nombre (función ya actualizada)
                    $userByName = $this->findUserByName($name, $paternal, $maternal);
                    if ($userByName) {
                        $socio_id_to_update = $userByName['soc_id_socio'];
                    }
                }

                if ($socio_id_to_update) {
                    // CASO 3: ENCONTRAMOS AL SOCIO (por tel o nombre).
                    // Actualizamos su registro con la nueva info digital.
                    $query = "UPDATE san_socios 
                              SET san_password = ?, validation_code = ?, soc_nombres = ?, soc_apepat = ?, soc_apemat = ?, 
                                  soc_fecha_captura = ?, soc_correo = ?, soc_tel_cel = ?, soc_correo_status = 0
                              WHERE soc_id_socio = ?"; // <-- Añade soc_correo_status = 0
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([$password, $validation_code, $name, $paternal, $maternal, $fecha_captura, $email, $telefono, $socio_id_to_update]);
                
                } else {
                    // CASO 4: NO ENCONTRAMOS NADA.
                    // Este es un socio 100% nuevo. Lo insertamos.
                    $query = "INSERT INTO san_socios 
                                (soc_nombres, soc_apepat, soc_apemat, soc_correo, san_password, soc_fecha_captura, 
                                 soc_fecha_nacimiento, validation_code, soc_id_usuario, soc_id_empresa, soc_id_consorcio, soc_tel_cel, soc_correo_status) 
                              VALUES (?, ?, ?, ?, ?, ?, '0000-00-00', ?, 17, 1, 1, ?, 0)"; // <-- Añade soc_correo_status = 0
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([$name, $paternal, $maternal, $email, $password, $fecha_captura, $validation_code, $telefono]);
                }
            }
            
            return $validation_code; // Devolvemos el código en todos los casos de éxito

        } catch (PDOException $e) {
            // log_error("PDOException en UserService: " . $e->getMessage()); 
            return false;
        }
    }
}
?>
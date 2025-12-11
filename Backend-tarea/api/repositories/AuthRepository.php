<?php
namespace Api\Repositories;

use Api\Core\DB;
use PDO;
use PDOException;

/**
 * Repositorio de Autenticación
 * ADAPTADO AL ESQUEMA REAL: 'intentos_login' y 'auditoria_auth'
 */
class AuthRepository {

    private $conexion;
    private $db;

    public function __construct() {
        $this->db = DB::obtenerInstancia();
        $this->conexion = $this->db->obtenerConexion();
    }

    /**
     * Busca usuario por email.
     */
    public function obtenerPorEmail($email) {
        try {
            $sql = "SELECT id, nombre_completo, email, password_hash, rol, id_sucursal, activo 
                    FROM usuarios 
                    WHERE email = :email LIMIT 1";
            
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute(['email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obtenerPorEmail: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica bloqueo por IP (Anti-Fuerza Bruta).
     * Tabla: intentos_login (email, ip_origen, fecha_intento)
     */
    public function estaBloqueado($ip) {
        try {
            $sql = "SELECT COUNT(*) FROM intentos_login 
                    WHERE ip_origen = :ip 
                    AND fecha_intento > (NOW() - INTERVAL 2 MINUTE)";
            
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute(['ip' => $ip]);
            
            return $stmt->fetchColumn() >= 3;

        } catch (PDOException $e) {
            error_log("Error estaBloqueado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra intento fallido.
     */
    public function registrarIntentoFallido($email, $ip) {
        try {
            $sql = "INSERT INTO intentos_login (email, ip_origen, fecha_intento) 
                    VALUES (:email, :ip, NOW())";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute(['email' => $email, 'ip' => $ip]);
        } catch (PDOException $e) {
            error_log("Error registrarIntentoFallido: " . $e->getMessage());
        }
    }

    /**
     * Limpia intentos tras login exitoso.
     */
    public function limpiarIntentos($ip) {
        try {
            $sql = "DELETE FROM intentos_login WHERE ip_origen = :ip";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute(['ip' => $ip]);
        } catch (PDOException $e) {
            error_log("Error limpiarIntentos: " . $e->getMessage());
        }
    }

    /**
     * Auditoría de seguridad.
     * Tabla: auditoria_auth (id_usuario, evento, detalles, creado_en)
     * NOTA: Tu tabla NO tiene columna 'ip_address', así que guardamos la IP en 'detalles'.
     */
    public function auditar($id_usuario, $evento, $ip_origen) {
        try {
            $sql = "INSERT INTO auditoria_auth (id_usuario, evento, detalles, creado_en) 
                    VALUES (:uid, :evt, :det, NOW())";
            
            // Concatenamos la IP en los detalles porque la tabla no tiene columna dedicada
            $detalles = "IP: " . $ip_origen;

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                'uid' => $id_usuario,
                'evt' => $evento,
                'det' => $detalles
            ]);
        } catch (PDOException $e) {
            error_log("Error auditar: " . $e->getMessage());
        }
    }
}
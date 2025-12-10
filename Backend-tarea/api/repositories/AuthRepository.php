<?php
namespace Api\Repositories;

use Api\Core\DB;
use Api\Entities\Usuario;
use PDO;
use PDOException;

/**
 * Repositorio de Autenticación
 *
 * Maneja todas las operaciones SQL relacionadas con:
 * - Login de usuarios
 * - Control de intentos fallidos (lockout)
 * - Auditoría de eventos de seguridad
 */
class AuthRepository {

    private $conexion;

    /**
     * Constructor
     */
    public function __construct() {
        $db = DB::obtenerInstancia();
        $this->conexion = $db->obtenerConexion();
    }

    /**
     * Verifica si un email está bloqueado por múltiples intentos fallidos
     *
     * Regla de negocio: 3 intentos fallidos en 2 minutos = bloqueo temporal
     *
     * @param string $email Email a verificar
     * @param string $ip_origen IP del cliente
     * @return array ['bloqueado' => bool, 'intentos' => int, 'tiempo_restante' => int]
     */
    public function verificarBloqueo($email, $ip_origen) {
        try {
            // Eliminar intentos antiguos (más de 2 minutos)
            $this->limpiarIntentosAntiguos($email);

            // Contar intentos recientes (últimos 2 minutos)
            $sql = "SELECT COUNT(*) as intentos,
                           MAX(fecha_intento) as ultimo_intento
                    FROM intentos_login
                    WHERE email = :email
                    AND fecha_intento >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute(['email' => $email]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            $intentos = (int) $resultado['intentos'];
            $bloqueado = $intentos >= 3;

            // Calcular tiempo restante de bloqueo
            $tiempo_restante = 0;
            if ($bloqueado && $resultado['ultimo_intento']) {
                $ultimo_intento = strtotime($resultado['ultimo_intento']);
                $tiempo_transcurrido = time() - $ultimo_intento;
                $tiempo_restante = max(0, 120 - $tiempo_transcurrido); // 120 seg = 2 min
            }

            return [
                'bloqueado' => $bloqueado,
                'intentos' => $intentos,
                'tiempo_restante' => $tiempo_restante
            ];

        } catch (PDOException $e) {
            error_log("Error en verificarBloqueo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca un usuario por email
     *
     * @param string $email Email del usuario
     * @return Usuario|null Objeto Usuario o null si no existe
     */
    public function buscarPorEmail($email) {
        try {
            $sql = "SELECT u.*, s.nombre as nombre_sucursal, s.zona_horaria
                    FROM usuarios u
                    LEFT JOIN sucursales s ON u.id_sucursal = s.id
                    WHERE u.email = :email
                    AND u.activo = 1
                    LIMIT 1";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute(['email' => $email]);
            $datos = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$datos) {
                return null;
            }

            return new Usuario($datos);

        } catch (PDOException $e) {
            error_log("Error en buscarPorEmail: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registra un intento fallido de login
     *
     * @param string $email Email del intento
     * @param string $ip_origen IP del cliente
     * @return bool True si se registró correctamente
     */
    public function registrarIntentoFallido($email, $ip_origen) {
        try {
            $sql = "INSERT INTO intentos_login (email, ip_origen, fecha_intento)
                    VALUES (:email, :ip, NOW())";

            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute([
                'email' => $email,
                'ip' => $ip_origen
            ]);

        } catch (PDOException $e) {
            error_log("Error en registrarIntentoFallido: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Limpia intentos de login antiguos (más de 2 minutos)
     *
     * @param string $email Email a limpiar
     * @return bool True si se limpiaron correctamente
     */
    private function limpiarIntentosAntiguos($email) {
        try {
            $sql = "DELETE FROM intentos_login
                    WHERE email = :email
                    AND fecha_intento < DATE_SUB(NOW(), INTERVAL 2 MINUTE)";

            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute(['email' => $email]);

        } catch (PDOException $e) {
            error_log("Error en limpiarIntentosAntiguos: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Limpia todos los intentos de un email tras login exitoso
     *
     * @param string $email Email a limpiar
     * @return bool True si se limpiaron correctamente
     */
    public function limpiarIntentosLogin($email) {
        try {
            $sql = "DELETE FROM intentos_login WHERE email = :email";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute(['email' => $email]);

        } catch (PDOException $e) {
            error_log("Error en limpiarIntentosLogin: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registra un evento de auditoría de autenticación
     *
     * @param int|null $id_usuario ID del usuario (null si es intento fallido)
     * @param string $evento Tipo de evento (LOGIN_EXITOSO, LOGIN_FALLIDO, etc.)
     * @param string $detalles Información adicional
     * @return bool True si se registró correctamente
     */
    public function registrarAuditoria($id_usuario, $evento, $detalles = '') {
        try {
            $sql = "INSERT INTO auditoria_auth (id_usuario, evento, detalles, creado_en)
                    VALUES (:id_usuario, :evento, :detalles, NOW())";

            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute([
                'id_usuario' => $id_usuario,
                'evento' => $evento,
                'detalles' => $detalles
            ]);

        } catch (PDOException $e) {
            error_log("Error en registrarAuditoria: " . $e->getMessage());
            throw $e;
        }
    }
}

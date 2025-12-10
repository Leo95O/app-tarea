<?php
namespace Api\Repositories;

use Api\Core\DB;
use Api\Entities\Sucursal;
use Api\Entities\Usuario;
use PDO;
use PDOException;

/**
 * Repositorio de Sucursales
 *
 * Maneja todas las operaciones SQL relacionadas con sucursales:
 * - Creación atómica (Sucursal + Gerente General)
 * - CRUD completo
 * - Soft Delete (cambio a estado INACTIVA)
 */
class SucursalRepository {

    private $conexion;
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = DB::obtenerInstancia();
        $this->conexion = $this->db->obtenerConexion();
    }

    /**
     * Crea una sucursal con su Gerente General en una transacción atómica
     *
     * Regla de negocio: No se permite crear sucursales vacías.
     * Si falla la creación del GG, se revierte la sucursal.
     *
     * @param array $datos_sucursal Datos de la sucursal
     * @param array $datos_gerente Datos del Gerente General
     * @return array ['exito' => bool, 'id_sucursal' => int, 'id_gerente' => int, 'error' => string]
     */
    public function crearAtomica($datos_sucursal, $datos_gerente) {
        try {
            // Iniciar transacción
            $this->db->iniciarTransaccion();

            // 1. Insertar sucursal
            $sql_sucursal = "INSERT INTO sucursales (nombre, direccion, telefono, zona_horaria, estado, creado_en)
                            VALUES (:nombre, :direccion, :telefono, :zona_horaria, 'ACTIVA', NOW())";

            $stmt = $this->conexion->prepare($sql_sucursal);
            $stmt->execute([
                'nombre' => $datos_sucursal['nombre'],
                'direccion' => $datos_sucursal['direccion'],
                'telefono' => $datos_sucursal['telefono'] ?? null,
                'zona_horaria' => $datos_sucursal['zona_horaria']
            ]);

            $id_sucursal = $this->db->obtenerUltimoId();

            // 2. Insertar Gerente General asociado a la sucursal
            $password_hash = password_hash($datos_gerente['password'], PASSWORD_BCRYPT);

            $sql_gerente = "INSERT INTO usuarios (id_sucursal, id_gerente_directo, rol, nombre_completo, email, password_hash, activo, creado_en)
                           VALUES (:id_sucursal, NULL, 'GG', :nombre_completo, :email, :password_hash, 1, NOW())";

            $stmt = $this->conexion->prepare($sql_gerente);
            $stmt->execute([
                'id_sucursal' => $id_sucursal,
                'nombre_completo' => $datos_gerente['nombre_completo'],
                'email' => $datos_gerente['email'],
                'password_hash' => $password_hash
            ]);

            $id_gerente = $this->db->obtenerUltimoId();

            // Confirmar transacción
            $this->db->confirmarTransaccion();

            return [
                'exito' => true,
                'id_sucursal' => (int) $id_sucursal,
                'id_gerente' => (int) $id_gerente,
                'error' => null
            ];

        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->revertirTransaccion();

            error_log("Error en crearAtomica: " . $e->getMessage());

            // Detectar error de email duplicado
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'email') !== false) {
                return [
                    'exito' => false,
                    'id_sucursal' => null,
                    'id_gerente' => null,
                    'error' => 'El email del Gerente General ya está registrado en el sistema'
                ];
            }

            return [
                'exito' => false,
                'id_sucursal' => null,
                'id_gerente' => null,
                'error' => 'Error al crear la sucursal. Intente nuevamente.'
            ];
        }
    }

    /**
     * Obtiene todas las sucursales (solo para CEO)
     *
     * @param bool $solo_activas Si solo debe retornar las activas
     * @return array Lista de sucursales
     */
    public function obtenerTodas($solo_activas = false) {
        try {
            $sql = "SELECT s.*,
                          (SELECT COUNT(*) FROM usuarios WHERE id_sucursal = s.id) as total_usuarios,
                          (SELECT CONCAT(u.nombre_completo, ' (', u.email, ')')
                           FROM usuarios u
                           WHERE u.id_sucursal = s.id AND u.rol = 'GG'
                           LIMIT 1) as gerente_general
                   FROM sucursales s";

            if ($solo_activas) {
                $sql .= " WHERE s.estado = 'ACTIVA'";
            }

            $sql .= " ORDER BY s.creado_en DESC";

            $stmt = $this->conexion->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerTodas: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene una sucursal por ID
     *
     * @param int $id ID de la sucursal
     * @return Sucursal|null Objeto Sucursal o null si no existe
     */
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM sucursales WHERE id = :id LIMIT 1";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute(['id' => $id]);
            $datos = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$datos) {
                return null;
            }

            return new Sucursal($datos);

        } catch (PDOException $e) {
            error_log("Error en obtenerPorId: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza los datos de una sucursal
     *
     * @param int $id ID de la sucursal
     * @param array $datos Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function actualizar($id, $datos) {
        try {
            $campos_actualizables = ['nombre', 'direccion', 'telefono', 'zona_horaria'];
            $campos_a_actualizar = [];
            $valores = [];

            foreach ($campos_actualizables as $campo) {
                if (isset($datos[$campo])) {
                    $campos_a_actualizar[] = "{$campo} = :{$campo}";
                    $valores[$campo] = $datos[$campo];
                }
            }

            if (empty($campos_a_actualizar)) {
                return false;
            }

            $valores['id'] = $id;
            $sql = "UPDATE sucursales SET " . implode(', ', $campos_a_actualizar) . " WHERE id = :id";

            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute($valores);

        } catch (PDOException $e) {
            error_log("Error en actualizar: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Desactiva una sucursal (Soft Delete)
     *
     * Cambia el estado a INACTIVA en lugar de eliminarla.
     * Esto preserva la integridad histórica.
     *
     * @param int $id ID de la sucursal
     * @return bool True si se desactivó correctamente
     */
    public function desactivar($id) {
        try {
            $sql = "UPDATE sucursales SET estado = 'INACTIVA' WHERE id = :id";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute(['id' => $id]);

        } catch (PDOException $e) {
            error_log("Error en desactivar: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reactiva una sucursal
     *
     * @param int $id ID de la sucursal
     * @return bool True si se reactivó correctamente
     */
    public function reactivar($id) {
        try {
            $sql = "UPDATE sucursales SET estado = 'ACTIVA' WHERE id = :id";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute(['id' => $id]);

        } catch (PDOException $e) {
            error_log("Error en reactivar: " . $e->getMessage());
            throw $e;
        }
    }
}

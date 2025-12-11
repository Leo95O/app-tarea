<?php
namespace Api\Repositories;

use Api\Core\DB;
use PDO;
use PDOException;

class UsuarioRepository {
    private $db;
    private $conexion;

    public function __construct() {
        $this->db = DB::obtenerInstancia();
        $this->conexion = $this->db->obtenerConexion();
    }

    /**
     * Crea un nuevo usuario.
     * @param array $datos [nombre_completo, email, password, rol, id_sucursal]
     * @return array Resultado
     */
    public function crear($datos) {
        try {
            $hash = password_hash($datos['password'], PASSWORD_BCRYPT);

            $sql = "INSERT INTO usuarios (
                        id_sucursal, id_gerente_directo, rol, 
                        nombre_completo, email, password_hash, 
                        activo, creado_en
                    ) VALUES (
                        :sucursal, NULL, :rol, 
                        :nombre, :email, :pass, 
                        1, NOW()
                    )";
            
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                'sucursal' => $datos['id_sucursal'],
                'rol'      => $datos['rol'],
                'nombre'   => $datos['nombre_completo'],
                'email'    => $datos['email'],
                'pass'     => $hash
            ]);

            return ['exito' => true, 'id' => $this->db->obtenerUltimoId()];

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['exito' => false, 'error' => 'El correo electrónico ya está registrado.'];
            }
            error_log("Error Crear Usuario: " . $e->getMessage());
            return ['exito' => false, 'error' => 'Error de base de datos.'];
        }
    }

    /**
     * Obtiene lista de usuarios para desplegables (Selects).
     * @param array $solicitante Datos del usuario logueado (JWT)
     * @return array Lista filtrada
     */
    public function listarParaSelector($solicitante) {
        $rol = $solicitante['rol'];
        $sucursal = $solicitante['id_sucursal'];
        $id = $solicitante['id'];

        $sql = "SELECT id, nombre_completo, rol, email FROM usuarios WHERE activo = 1";
        $params = [];

        switch ($rol) {
            case 'CEO':
                break;
            case 'GG':
                $sql .= " AND id_sucursal = :sucursal";
                $params['sucursal'] = $sucursal;
                break;
            case 'GERENTE':
                $sql .= " AND id_sucursal = :sucursal AND (rol = 'COLABORADOR' OR id = :id)";
                $params['sucursal'] = $sucursal;
                $params['id'] = $id;
                break;
            case 'COLABORADOR':
                $sql .= " AND id = :id";
                $params['id'] = $id;
                break;
        }

        $sql .= " ORDER BY nombre_completo ASC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si un email existe.
     */
    public function existeEmail($email) {
        $stmt = $this->conexion->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetch();
    }

    /**
     * Búsqueda ligera para Autocomplete (ID, Nombre, Rol, Sucursal).
     * @param string $termino Texto a buscar (puede ser vacío)
     * @param array $usuario_solicitante Datos del usuario logueado
     * @return array Resultados
     */
    public function buscarParaAsignacion($termino, $usuario_solicitante) {
        $sql = "SELECT id, nombre_completo, rol, id_sucursal 
                FROM usuarios 
                WHERE activo = 1";
        
        $params = [];

        // Si hay término, filtramos. Si no, trae cualquiera (limitado a 10)
        if (!empty($termino)) {
            $sql .= " AND nombre_completo LIKE :termino";
            $params['termino'] = '%' . $termino . '%';
        }

        // Lógica de visibilidad
        if ($usuario_solicitante['rol'] === 'GG') {
            $sql .= " AND id_sucursal = :sucursal";
            $params['sucursal'] = $usuario_solicitante['id_sucursal'];
        } elseif ($usuario_solicitante['rol'] === 'GERENTE') {
            $sql .= " AND id_sucursal = :sucursal AND rol IN ('COLABORADOR', 'GERENTE')";
            $params['sucursal'] = $usuario_solicitante['id_sucursal'];
        }

        $sql .= " ORDER BY nombre_completo ASC LIMIT 10";

        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error Buscar Usuario: " . $e->getMessage());
            return [];
        }
    }
}

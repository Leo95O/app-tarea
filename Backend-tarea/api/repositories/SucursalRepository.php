<?php
namespace Api\Repositories;

use Api\Core\DB;
use PDO;
use PDOException;

class SucursalRepository {

    private $conexion;
    private $db;

    public function __construct() {
        $this->db = DB::obtenerInstancia();
        $this->conexion = $this->db->obtenerConexion();
    }

    public function obtenerZonaHoraria($id_sucursal) {
        try {
            $stmt = $this->conexion->prepare("SELECT zona_horaria FROM sucursales WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id_sucursal]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return null;
        }
    }

    public function obtenerPorId($id) {
        try {
            $stmt = $this->conexion->prepare("SELECT * FROM sucursales WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
}
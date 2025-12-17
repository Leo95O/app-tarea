<?php
namespace Api\Repositories;

use Api\Core\DB;
use PDO;
use PDOException;

class TareaRepository {
    private $db;
    private $conexion;

    public function __construct() {
        $this->db = DB::obtenerInstancia();
        $this->conexion = $this->db->obtenerConexion();
    }

    // LISTAR
    public function listarPorUsuario($id_usuario, $rol, $id_sucursal, $filtros = []) {
        try {
        $sqlUpdate = "UPDATE tareas 
                      SET estado_ejecucion = 'VENCIDA', 
                          estado_ciclo_vida = 'FINALIZADA_VENCIDA'
                      WHERE fecha_fin_actual < NOW() 
                        AND estado_ejecucion NOT IN ('COMPLETADA', 'VENCIDA')
                        AND estado_ciclo_vida != 'INACTIVA'";
        
        $this->conexion->exec($sqlUpdate); // Ejecuta la actualización silenciosamente
    } catch (PDOException $e) {
        // Ignoramos errores aquí para no detener el listado por un problema de update
        error_log("Error actualizando vencimientos: " . $e->getMessage());
    }        
        // SQL Base
        $sql = "SELECT t.*, 
                       c.nombre_completo as nombre_creador, 
                       a.nombre_completo as nombre_asignado 
                FROM tareas t
                LEFT JOIN usuarios c ON t.id_creador = c.id
                LEFT JOIN usuarios a ON t.id_asignado = a.id
                WHERE t.estado_ciclo_vida != 'INACTIVA' ";
        
        $params = [];
        $rol = strtoupper(trim($rol));

        // Filtros por Rol
        if ($rol === 'GG' || $rol === 'GERENTE') {
            $sql .= " AND t.id_sucursal = :sucursal";
            $params['sucursal'] = $id_sucursal;
        } 
        elseif ($rol === 'COLABORADOR') {
            // Usar dos parámetros distintos para evitar fallo en PDO antiguo
            $sql .= " AND (t.id_asignado = :id_asig OR t.id_creador = :id_crea)";
            $params['id_asig'] = $id_usuario;
            $params['id_crea'] = $id_usuario;
        }
        // CEO ve todo

        $sql .= " ORDER BY t.fecha_fin_actual ASC";

        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { 
            error_log("SQL Error Listar: " . $e->getMessage());
            return []; 
        }
    }

    // CREAR
    public function crear($tarea, $subtareas) {
        try {
            $this->db->iniciarTransaccion();

            $sql = "INSERT INTO tareas (
                        id_sucursal, id_creador, id_asignado, titulo, descripcion, prioridad, categoria_asignacion, 
                        fecha_inicio, fecha_fin_original, fecha_fin_actual, es_iniciativa, 
                        estado_ejecucion, estado_validacion, estado_ciclo_vida, creado_en
                    ) VALUES (
                        :id_sucursal, :id_creador, :id_asignado, :titulo, :descripcion, :prioridad, :categoria_asignacion, 
                        :fecha_inicio, :fecha_fin_orig, :fecha_fin_act, :es_iniciativa, 
                        'PROGRAMADA', 'SIN_VALIDAR', 'ACTIVA', NOW()
                    )";
            
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                'id_sucursal' => $tarea['id_sucursal'],
                'id_creador' => $tarea['id_creador'],
                'id_asignado' => $tarea['id_asignado'],
                'titulo' => $tarea['titulo'],
                'descripcion' => $tarea['descripcion'],
                'prioridad' => $tarea['prioridad'],
                'categoria_asignacion' => $tarea['categoria_asignacion'],
                'fecha_inicio' => $tarea['fecha_inicio'],
                'fecha_fin_orig' => $tarea['fecha_fin'],
                'fecha_fin_act' => $tarea['fecha_fin'],
                'es_iniciativa' => $tarea['es_iniciativa']
            ]);

            $id_tarea = $this->db->obtenerUltimoId();

            if (!empty($subtareas)) {
                $sqlSub = "INSERT INTO subtareas (id_tarea, titulo, completada, id_creador, creado_en) VALUES (:id_tarea, :titulo, 0, :id_creador, NOW())";
                $stmtSub = $this->conexion->prepare($sqlSub);
                foreach ($subtareas as $sub) {
                    $stmtSub->execute([
                        'id_tarea' => $id_tarea,
                        'titulo' => $sub['titulo'],
                        'id_creador' => $tarea['id_creador']
                    ]);
                }
            }

            $this->db->confirmarTransaccion();
            return ['exito' => true, 'id_tarea' => $id_tarea];

        } catch (PDOException $e) {
            $this->db->revertirTransaccion();
            return ['exito' => false, 'error' => 'SQL ERROR: ' . $e->getMessage()];
        }
    }
    
    public function contarIniciativasHoy($id_usuario) {
        $sql = "SELECT COUNT(*) FROM tareas 
                WHERE id_creador = :id 
                  AND es_iniciativa = 1 
                  AND DATE(creado_en) = CURDATE() 
                  AND estado_ciclo_vida != 'INACTIVA'";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute(['id' => $id_usuario]);
        return (int) $stmt->fetchColumn();
    }
    
    public function obtenerPorId($id) { 
        $stmt = $this->conexion->prepare("SELECT * FROM tareas WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function marcarCompletada($id) { 
        $this->conexion->prepare("UPDATE tareas SET estado_ejecucion='COMPLETADA' WHERE id=:id")->execute(['id'=>$id]);
    }

    public function eliminar($id, $es_iniciativa) {
        $estado = $es_iniciativa ? 'ABANDONADA' : 'INACTIVA';
        $this->conexion->prepare("UPDATE tareas SET estado_ciclo_vida=:e, eliminado_en=NOW() WHERE id=:id")->execute(['e'=>$estado, 'id'=>$id]);
    }

    // --- NUEVOS MÉTODOS DEL FLUJO V6 ---

    public function solicitarValidacion($id) {
        $sql = "UPDATE tareas 
                SET estado_ejecucion='COMPLETADA', 
                    estado_validacion='POR_VALIDAR' 
                WHERE id=:id";
        $this->conexion->prepare($sql)->execute(['id' => $id]);
    }

    public function validar($id, $id_validador) {
        $sql = "UPDATE tareas 
                SET estado_validacion='VALIDADA', 
                    id_validador=:val, 
                    fecha_validacion=NOW(), 
                    estado_ciclo_vida='ACTIVA'
                WHERE id=:id";
        $this->conexion->prepare($sql)->execute(['id' => $id, 'val' => $id_validador]);
    }

    public function rechazar($id) {
        $sql = "UPDATE tareas 
                SET estado_ejecucion='EN_PROGRESO', 
                    estado_validacion='SIN_VALIDAR', 
                    contador_rechazos=contador_rechazos+1 
                WHERE id=:id";
        $this->conexion->prepare($sql)->execute(['id' => $id]);
    }
}

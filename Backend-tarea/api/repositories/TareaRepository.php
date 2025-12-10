<?php
namespace Api\Repositories;

use Api\Core\DB;
use Api\Entities\Tarea;
use Api\Entities\Subtarea;
use PDO;
use PDOException;
use DateTime;

/**
 * Repositorio de Tareas
 *
 * Implementa la Matriz de Visibilidad V6 y toda la lógica SQL de tareas.
 * Este es el repositorio más complejo del sistema.
 */
class TareaRepository {

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
     * Crea una tarea con sus subtareas
     *
     * @param array $datos_tarea Datos de la tarea
     * @param array $subtareas Lista de subtareas (opcional)
     * @return array ['exito' => bool, 'id_tarea' => int, 'error' => string]
     */
    public function crear($datos_tarea, $subtareas = []) {
        try {
            $this->db->iniciarTransaccion();

            // Preparar datos de inserción
            $es_iniciativa = isset($datos_tarea['es_iniciativa']) && $datos_tarea['es_iniciativa'];

            // Si es iniciativa, forzar autoasignación
            if ($es_iniciativa) {
                $datos_tarea['id_asignado'] = $datos_tarea['id_creador'];
                $datos_tarea['categoria_asignacion'] = 'ESPECIFICA';
            }

            // Insertar tarea
            $sql = "INSERT INTO tareas (
                        id_sucursal, id_creador, id_asignado,
                        titulo, descripcion, prioridad, categoria_asignacion,
                        fecha_inicio, fecha_fin_original, fecha_fin_actual,
                        es_iniciativa, creado_en
                    ) VALUES (
                        :id_sucursal, :id_creador, :id_asignado,
                        :titulo, :descripcion, :prioridad, :categoria_asignacion,
                        :fecha_inicio, :fecha_fin, :fecha_fin,
                        :es_iniciativa, NOW()
                    )";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                'id_sucursal' => $datos_tarea['id_sucursal'],
                'id_creador' => $datos_tarea['id_creador'],
                'id_asignado' => $datos_tarea['id_asignado'] ?? null,
                'titulo' => $datos_tarea['titulo'],
                'descripcion' => $datos_tarea['descripcion'] ?? '',
                'prioridad' => $datos_tarea['prioridad'] ?? 'MEDIA',
                'categoria_asignacion' => $datos_tarea['categoria_asignacion'] ?? 'ESPECIFICA',
                'fecha_inicio' => $datos_tarea['fecha_inicio'],
                'fecha_fin' => $datos_tarea['fecha_fin'],
                'es_iniciativa' => $es_iniciativa ? 1 : 0
            ]);

            $id_tarea = $this->db->obtenerUltimoId();

            // Insertar subtareas si existen
            if (!empty($subtareas)) {
                foreach ($subtareas as $subtarea) {
                    $this->crearSubtarea($id_tarea, $subtarea, $datos_tarea['id_creador']);
                }
            }

            $this->db->confirmarTransaccion();

            return [
                'exito' => true,
                'id_tarea' => (int) $id_tarea,
                'error' => null
            ];

        } catch (PDOException $e) {
            $this->db->revertirTransaccion();
            error_log("Error en crear tarea: " . $e->getMessage());

            return [
                'exito' => false,
                'id_tarea' => null,
                'error' => 'Error al crear la tarea'
            ];
        }
    }

    /**
     * Crea una subtarea
     *
     * @param int $id_tarea ID de la tarea padre
     * @param array|string $subtarea Datos de la subtarea o título
     * @param int $id_creador ID del creador
     * @return bool True si se creó correctamente
     */
    private function crearSubtarea($id_tarea, $subtarea, $id_creador) {
        $titulo = is_array($subtarea) ? $subtarea['titulo'] : $subtarea;

        $sql = "INSERT INTO subtareas (id_tarea, titulo, completada, id_creador, creado_en)
                VALUES (:id_tarea, :titulo, 0, :id_creador, NOW())";

        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            'id_tarea' => $id_tarea,
            'titulo' => $titulo,
            'id_creador' => $id_creador
        ]);
    }

    /**
     * Obtiene tareas con filtros de visibilidad según rol (Matriz V6)
     *
     * MATRIZ DE VISIBILIDAD:
     * - CEO: Ve TODO (todas las sucursales)
     * - GG: Ve toda su sucursal
     * - GERENTE: Ve sus colaboradores directos + sus propias tareas
     * - COLABORADOR: Ve solo sus tareas asignadas e iniciativas
     *
     * @param array $usuario_autenticado Datos del usuario autenticado (del JWT)
     * @param array $filtros Filtros opcionales (estado, prioridad, etc.)
     * @return array Lista de tareas filtradas
     */
    public function obtenerTareasConFiltros($usuario_autenticado, $filtros = []) {
        try {
            $rol = $usuario_autenticado['rol'];
            $id_usuario = $usuario_autenticado['id'];
            $id_sucursal = $usuario_autenticado['id_sucursal'];

            // Base query con joins
            $sql = "SELECT t.*,
                          uc.nombre_completo as creador_nombre,
                          ua.nombre_completo as asignado_nombre,
                          uv.nombre_completo as validador_nombre,
                          s.zona_horaria
                   FROM tareas t
                   INNER JOIN usuarios uc ON t.id_creador = uc.id
                   LEFT JOIN usuarios ua ON t.id_asignado = ua.id
                   LEFT JOIN usuarios uv ON t.id_validador = uv.id
                   INNER JOIN sucursales s ON t.id_sucursal = s.id
                   WHERE 1=1";

            $parametros = [];

            // Aplicar filtros de visibilidad según rol
            switch ($rol) {
                case 'CEO':
                    // CEO ve TODO, no agregar filtros
                    break;

                case 'GG':
                    // GG ve toda su sucursal
                    $sql .= " AND t.id_sucursal = :id_sucursal";
                    $parametros['id_sucursal'] = $id_sucursal;
                    break;

                case 'GERENTE':
                    // GERENTE ve:
                    // 1. Sus propias tareas
                    // 2. Tareas de sus colaboradores directos
                    // 3. Bolsas de su nivel o inferiores
                    $sql .= " AND (
                                t.id_asignado = :id_usuario
                                OR t.id_creador = :id_usuario
                                OR t.id_asignado IN (
                                    SELECT id FROM usuarios WHERE id_gerente_directo = :id_usuario2
                                )
                                OR (t.categoria_asignacion IN ('BOLSA_GERENTE', 'BOLSA_COLABORADOR', 'BOLSA_AMBOS')
                                    AND t.id_asignado IS NULL
                                    AND t.id_sucursal = :id_sucursal)
                              )";
                    $parametros['id_usuario'] = $id_usuario;
                    $parametros['id_usuario2'] = $id_usuario;
                    $parametros['id_sucursal'] = $id_sucursal;
                    break;

                case 'COLABORADOR':
                    // COLABORADOR ve:
                    // 1. Sus tareas asignadas
                    // 2. Sus iniciativas
                    // 3. Bolsas de colaborador
                    $sql .= " AND (
                                t.id_asignado = :id_usuario
                                OR (t.id_creador = :id_usuario2 AND t.es_iniciativa = 1)
                                OR (t.categoria_asignacion IN ('BOLSA_COLABORADOR', 'BOLSA_AMBOS')
                                    AND t.id_asignado IS NULL
                                    AND t.id_sucursal = :id_sucursal)
                              )";
                    $parametros['id_usuario'] = $id_usuario;
                    $parametros['id_usuario2'] = $id_usuario;
                    $parametros['id_sucursal'] = $id_sucursal;
                    break;
            }

            // Excluir tareas eliminadas (INACTIVA) para roles bajos
            if ($rol !== 'CEO') {
                $sql .= " AND t.estado_ciclo_vida != 'INACTIVA'";
            }

            // Excluir iniciativas eliminadas (soft delete)
            $sql .= " AND t.eliminado_en IS NULL";

            // Aplicar filtros adicionales
            if (!empty($filtros['estado_ejecucion'])) {
                $sql .= " AND t.estado_ejecucion = :estado_ejecucion";
                $parametros['estado_ejecucion'] = $filtros['estado_ejecucion'];
            }

            if (!empty($filtros['prioridad'])) {
                $sql .= " AND t.prioridad = :prioridad";
                $parametros['prioridad'] = $filtros['prioridad'];
            }

            if (isset($filtros['es_iniciativa'])) {
                $sql .= " AND t.es_iniciativa = :es_iniciativa";
                $parametros['es_iniciativa'] = $filtros['es_iniciativa'] ? 1 : 0;
            }

            // Ordenar
            $sql .= " ORDER BY t.fecha_fin_actual ASC, t.prioridad DESC, t.creado_en DESC";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($parametros);
            $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Actualizar estados automáticos y cargar relaciones
            $tareas_procesadas = [];
            foreach ($tareas as $tarea_data) {
                // Actualizar estados según fecha/hora
                $tarea_data = $this->calcularEstadoEjecucion($tarea_data);

                // Cargar subtareas
                $tarea_data['subtareas'] = $this->obtenerSubtareas($tarea_data['id']);

                $tareas_procesadas[] = $tarea_data;
            }

            return $tareas_procesadas;

        } catch (PDOException $e) {
            error_log("Error en obtenerTareasConFiltros: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calcula el estado de ejecución según la fecha/hora actual
     *
     * @param array $tarea_data Datos de la tarea
     * @return array Datos actualizados
     */
    private function calcularEstadoEjecucion($tarea_data) {
        $ahora = new DateTime('now', new \DateTimeZone($tarea_data['zona_horaria']));
        $fecha_inicio = new DateTime($tarea_data['fecha_inicio'], new \DateTimeZone($tarea_data['zona_horaria']));
        $fecha_fin = new DateTime($tarea_data['fecha_fin_actual'], new \DateTimeZone($tarea_data['zona_horaria']));

        // Si está validada o finalizada, no cambiar estado
        if (in_array($tarea_data['estado_ciclo_vida'], ['VALIDADA', 'FINALIZADA_VENCIDA'])) {
            return $tarea_data;
        }

        // Calcular estado automático
        if ($ahora < $fecha_inicio) {
            $nuevo_estado = 'PROGRAMADA';
        } elseif ($ahora >= $fecha_inicio && $ahora <= $fecha_fin) {
            $nuevo_estado = ($tarea_data['estado_ejecucion'] === 'COMPLETADA') ? 'COMPLETADA' : 'EN_PROGRESO';
        } else {
            // Vencida
            $nuevo_estado = ($tarea_data['estado_ejecucion'] === 'COMPLETADA') ? 'COMPLETADA' : 'VENCIDA';
        }

        // Actualizar en BD si cambió
        if ($nuevo_estado !== $tarea_data['estado_ejecucion']) {
            $this->actualizarEstadoEjecucion($tarea_data['id'], $nuevo_estado);
            $tarea_data['estado_ejecucion'] = $nuevo_estado;
        }

        return $tarea_data;
    }

    /**
     * Actualiza el estado de ejecución de una tarea
     *
     * @param int $id_tarea ID de la tarea
     * @param string $nuevo_estado Nuevo estado
     * @return bool True si se actualizó
     */
    private function actualizarEstadoEjecucion($id_tarea, $nuevo_estado) {
        $sql = "UPDATE tareas SET estado_ejecucion = :estado WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute(['estado' => $nuevo_estado, 'id' => $id_tarea]);
    }

    /**
     * Obtiene las subtareas de una tarea
     *
     * @param int $id_tarea ID de la tarea
     * @return array Lista de subtareas
     */
    private function obtenerSubtareas($id_tarea) {
        $sql = "SELECT s.*, u.nombre_completo as creador_nombre
                FROM subtareas s
                INNER JOIN usuarios u ON s.id_creador = u.id
                WHERE s.id_tarea = :id_tarea
                ORDER BY s.creado_en ASC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute(['id_tarea' => $id_tarea]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una tarea por ID (con permisos)
     *
     * @param int $id ID de la tarea
     * @param array $usuario_autenticado Usuario que solicita
     * @return array|null Datos de la tarea o null
     */
    public function obtenerPorId($id, $usuario_autenticado) {
        // Usar el mismo filtro de visibilidad
        $tareas = $this->obtenerTareasConFiltros($usuario_autenticado);

        foreach ($tareas as $tarea) {
            if ($tarea['id'] == $id) {
                return $tarea;
            }
        }

        return null;
    }

    /**
     * Marca una tarea como completada (por el asignado)
     *
     * @param int $id_tarea ID de la tarea
     * @return bool True si se completó
     */
    public function marcarCompletada($id_tarea) {
        $sql = "UPDATE tareas
                SET estado_ejecucion = 'COMPLETADA',
                    estado_validacion = 'POR_VALIDAR'
                WHERE id = :id";

        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute(['id' => $id_tarea]);
    }

    /**
     * Elimina una tarea (Soft Delete para iniciativas, INACTIVA para asignadas)
     *
     * @param int $id_tarea ID de la tarea
     * @param bool $es_iniciativa Si es tarea de iniciativa
     * @return bool True si se eliminó
     */
    public function eliminar($id_tarea, $es_iniciativa) {
        if ($es_iniciativa) {
            // Soft Delete: Marcar como abandonada
            $sql = "UPDATE tareas SET eliminado_en = NOW() WHERE id = :id";
        } else {
            // Inactivar: El jefe elimina la tarea asignada
            $sql = "UPDATE tareas SET estado_ciclo_vida = 'INACTIVA' WHERE id = :id";
        }

        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute(['id' => $id_tarea]);
    }

    /**
     * Actualiza el estado de una subtarea
     *
     * @param int $id_subtarea ID de la subtarea
     * @param bool $completada Nuevo estado
     * @return bool True si se actualizó
     */
    public function actualizarSubtarea($id_subtarea, $completada) {
        $sql = "UPDATE subtareas SET completada = :completada WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            'completada' => $completada ? 1 : 0,
            'id' => $id_subtarea
        ]);
    }
}

<?php
namespace Api\Controllers;

use Api\Core\Response;
use Api\Repositories\TareaRepository;
use Api\Validators\TareaValidator;
use Api\Core\JWTMiddleware;
use Slim\Slim;

/**
 * Controlador de Tareas
 *
 * Orquesta todas las operaciones CRUD de tareas aplicando:
 * - Validaciones de negocio (TareaValidator)
 * - Filtros de visibilidad según rol (Matriz V6)
 * - Control de permisos por tipo de tarea
 */
class TareaController {

    private $tareaRepository;
    private $tareaValidator;

    /**
     * Constructor
     */
    public function __construct() {
        $this->tareaRepository = new TareaRepository();

        // Inyectar conexión al validador para límites de iniciativas
        $conexion = \Api\Core\DB::obtenerInstancia()->obtenerConexion();
        $this->tareaValidator = new TareaValidator($conexion);
    }

    /**
     * POST / - Crear nueva tarea
     *
     * Tipos de tarea:
     * - Tipo A (Asignada): Sin límite de duración
     * - Tipo B (Iniciativa): Max 10/día, Max 9 horas, autoasignada
     *
     * @param Slim $app Instancia de Slim
     */
    public function crear(Slim $app) {
        try {
            // Obtener usuario autenticado desde JWT
            $usuario_autenticado = JWTMiddleware::obtenerUsuarioAutenticado();

            // Extraer datos del request
            $datos = json_decode($app->request()->getBody(), true);

            // Validar datos de entrada
            $validacion = $this->tareaValidator->validarCreacion(
                $datos,
                $usuario_autenticado['id']
            );

            if (!$validacion['valido']) {
                $respuesta = Response::advertencia(
                    "Errores de validación",
                    ['errores' => $validacion['errores']]
                );
                return Response::enviar($app, $respuesta, 400);
            }

            // Preparar datos de la tarea
            $datos_tarea = [
                'id_sucursal' => $usuario_autenticado['id_sucursal'],
                'id_creador' => $usuario_autenticado['id'],
                'id_asignado' => $datos['id_asignado'] ?? null,
                'titulo' => $datos['titulo'],
                'descripcion' => $datos['descripcion'] ?? '',
                'prioridad' => $datos['prioridad'] ?? 'MEDIA',
                'categoria_asignacion' => $datos['categoria_asignacion'] ?? 'ESPECIFICA',
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin' => $datos['fecha_fin'],
                'es_iniciativa' => isset($datos['es_iniciativa']) && $datos['es_iniciativa']
            ];

            // Extraer subtareas si existen
            $subtareas = $datos['subtareas'] ?? [];

            // Crear tarea en BD
            $resultado = $this->tareaRepository->crear($datos_tarea, $subtareas);

            if (!$resultado['exito']) {
                $respuesta = Response::error($resultado['error']);
                return Response::enviar($app, $respuesta, 500);
            }

            $respuesta = Response::exito(
                ['id_tarea' => $resultado['id_tarea']],
                "Tarea creada exitosamente"
            );
            return Response::enviar($app, $respuesta, 201);

        } catch (\Exception $e) {
            error_log("Error en TareaController::crear: " . $e->getMessage());
            $respuesta = Response::error("Error al crear la tarea");
            return Response::enviar($app, $respuesta, 500);
        }
    }

    /**
     * GET / - Listar tareas con filtros de visibilidad
     *
     * Aplica Matriz V6:
     * - CEO: Ve TODO
     * - GG: Ve toda su sucursal
     * - GERENTE: Ve colaboradores directos + propias + bolsas relevantes
     * - COLABORADOR: Ve solo asignadas + iniciativas propias + bolsas colaborador
     *
     * Query params opcionales: estado_ejecucion, prioridad, es_iniciativa
     *
     * @param Slim $app Instancia de Slim
     */
    public function listar(Slim $app) {
        try {
            // Obtener usuario autenticado
            $usuario_autenticado = JWTMiddleware::obtenerUsuarioAutenticado();

            // Extraer filtros opcionales de query params
            $filtros = [];

            $estado_ejecucion = $app->request()->get('estado_ejecucion');
            if ($estado_ejecucion) {
                $filtros['estado_ejecucion'] = $estado_ejecucion;
            }

            $prioridad = $app->request()->get('prioridad');
            if ($prioridad) {
                $filtros['prioridad'] = $prioridad;
            }

            $es_iniciativa = $app->request()->get('es_iniciativa');
            if ($es_iniciativa !== null) {
                $filtros['es_iniciativa'] = filter_var($es_iniciativa, FILTER_VALIDATE_BOOLEAN);
            }

            // Obtener tareas con visibilidad aplicada
            $tareas = $this->tareaRepository->obtenerTareasConFiltros(
                $usuario_autenticado,
                $filtros
            );

            $respuesta = Response::exito(
                ['tareas' => $tareas, 'total' => count($tareas)],
                "Tareas obtenidas exitosamente"
            );
            return Response::enviar($app, $respuesta, 200);

        } catch (\Exception $e) {
            error_log("Error en TareaController::listar: " . $e->getMessage());
            $respuesta = Response::error("Error al obtener las tareas");
            return Response::enviar($app, $respuesta, 500);
        }
    }

    /**
     * GET /:id - Obtener una tarea por ID
     *
     * Verifica que el usuario tenga permisos para ver esta tarea según Matriz V6.
     *
     * @param Slim $app Instancia de Slim
     * @param int $id ID de la tarea
     */
    public function obtenerPorId(Slim $app, $id) {
        try {
            // Obtener usuario autenticado
            $usuario_autenticado = JWTMiddleware::obtenerUsuarioAutenticado();

            // Buscar tarea con filtros de visibilidad
            $tarea = $this->tareaRepository->obtenerPorId($id, $usuario_autenticado);

            if (!$tarea) {
                $respuesta = Response::advertencia(
                    "No se encontró la tarea o no tienes permisos para verla"
                );
                return Response::enviar($app, $respuesta, 404);
            }

            $respuesta = Response::exito(
                ['tarea' => $tarea],
                "Tarea obtenida exitosamente"
            );
            return Response::enviar($app, $respuesta, 200);

        } catch (\Exception $e) {
            error_log("Error en TareaController::obtenerPorId: " . $e->getMessage());
            $respuesta = Response::error("Error al obtener la tarea");
            return Response::enviar($app, $respuesta, 500);
        }
    }

    /**
     * POST /:id/completar - Marcar tarea como completada
     *
     * Solo el usuario asignado puede marcar como completada.
     * Valida que todas las subtareas estén completadas.
     *
     * @param Slim $app Instancia de Slim
     * @param int $id ID de la tarea
     */
    public function marcarCompletada(Slim $app, $id) {
        try {
            // Obtener usuario autenticado
            $usuario_autenticado = JWTMiddleware::obtenerUsuarioAutenticado();

            // Verificar que la tarea existe y el usuario es el asignado
            $tarea = $this->tareaRepository->obtenerPorId($id, $usuario_autenticado);

            if (!$tarea) {
                $respuesta = Response::advertencia("Tarea no encontrada");
                return Response::enviar($app, $respuesta, 404);
            }

            // Verificar que es el asignado
            if ($tarea['id_asignado'] != $usuario_autenticado['id']) {
                $respuesta = Response::advertencia(
                    "Solo el usuario asignado puede marcar la tarea como completada"
                );
                return Response::enviar($app, $respuesta, 403);
            }

            // Verificar que todas las subtareas estén completadas
            if (!empty($tarea['subtareas'])) {
                $validacion = $this->tareaValidator->validarSubtareasCompletadas($tarea['subtareas']);

                if (!$validacion['valido']) {
                    $respuesta = Response::advertencia(
                        "No se puede completar la tarea",
                        ['errores' => $validacion['errores']]
                    );
                    return Response::enviar($app, $respuesta, 400);
                }
            }

            // Marcar como completada
            $exito = $this->tareaRepository->marcarCompletada($id);

            if (!$exito) {
                $respuesta = Response::error("Error al completar la tarea");
                return Response::enviar($app, $respuesta, 500);
            }

            $respuesta = Response::exito(
                [],
                "Tarea marcada como completada. Ahora está pendiente de validación."
            );
            return Response::enviar($app, $respuesta, 200);

        } catch (\Exception $e) {
            error_log("Error en TareaController::marcarCompletada: " . $e->getMessage());
            $respuesta = Response::error("Error al completar la tarea");
            return Response::enviar($app, $respuesta, 500);
        }
    }

    /**
     * DELETE /:id - Eliminar una tarea
     *
     * - Iniciativas: Soft delete (eliminado_en = NOW)
     * - Asignadas: Hard state (estado_ciclo_vida = INACTIVA)
     *
     * Permisos:
     * - Iniciativas: Solo el creador puede eliminar
     * - Asignadas: Solo el creador (jefe que asignó)
     *
     * @param Slim $app Instancia de Slim
     * @param int $id ID de la tarea
     */
    public function eliminar(Slim $app, $id) {
        try {
            // Obtener usuario autenticado
            $usuario_autenticado = JWTMiddleware::obtenerUsuarioAutenticado();

            // Verificar que la tarea existe
            $tarea = $this->tareaRepository->obtenerPorId($id, $usuario_autenticado);

            if (!$tarea) {
                $respuesta = Response::advertencia("Tarea no encontrada");
                return Response::enviar($app, $respuesta, 404);
            }

            // Verificar permisos: Solo el creador puede eliminar
            if ($tarea['id_creador'] != $usuario_autenticado['id']) {
                $respuesta = Response::advertencia(
                    "Solo el creador de la tarea puede eliminarla"
                );
                return Response::enviar($app, $respuesta, 403);
            }

            // Eliminar según tipo
            $es_iniciativa = (bool) $tarea['es_iniciativa'];
            $exito = $this->tareaRepository->eliminar($id, $es_iniciativa);

            if (!$exito) {
                $respuesta = Response::error("Error al eliminar la tarea");
                return Response::enviar($app, $respuesta, 500);
            }

            $mensaje = $es_iniciativa
                ? "Iniciativa eliminada exitosamente"
                : "Tarea marcada como inactiva";

            $respuesta = Response::exito([], $mensaje);
            return Response::enviar($app, $respuesta, 200);

        } catch (\Exception $e) {
            error_log("Error en TareaController::eliminar: " . $e->getMessage());
            $respuesta = Response::error("Error al eliminar la tarea");
            return Response::enviar($app, $respuesta, 500);
        }
    }

    /**
     * PUT /subtareas/:id - Actualizar estado de una subtarea
     *
     * Toggle del estado completada (true/false).
     * Solo usuarios con acceso a la tarea pueden actualizar subtareas.
     *
     * @param Slim $app Instancia de Slim
     * @param int $id_subtarea ID de la subtarea
     */
    public function actualizarSubtarea(Slim $app, $id_subtarea) {
        try {
            // Obtener usuario autenticado
            $usuario_autenticado = JWTMiddleware::obtenerUsuarioAutenticado();

            // Extraer datos del request
            $datos = json_decode($app->request()->getBody(), true);

            if (!isset($datos['completada'])) {
                $respuesta = Response::advertencia("Debe especificar el estado 'completada'");
                return Response::enviar($app, $respuesta, 400);
            }

            $completada = filter_var($datos['completada'], FILTER_VALIDATE_BOOLEAN);

            // Actualizar subtarea
            $exito = $this->tareaRepository->actualizarSubtarea($id_subtarea, $completada);

            if (!$exito) {
                $respuesta = Response::error("Error al actualizar la subtarea");
                return Response::enviar($app, $respuesta, 500);
            }

            $respuesta = Response::exito(
                [],
                "Subtarea actualizada exitosamente"
            );
            return Response::enviar($app, $respuesta, 200);

        } catch (\Exception $e) {
            error_log("Error en TareaController::actualizarSubtarea: " . $e->getMessage());
            $respuesta = Response::error("Error al actualizar la subtarea");
            return Response::enviar($app, $respuesta, 500);
        }
    }
}

<?php
namespace Api\Controllers;

use Api\Core\Response;
use Api\Core\JWTHelper;
use Api\Repositories\TareaRepository;
use Api\Repositories\SucursalRepository;
use Api\Validators\TareaValidator;
use Slim\Slim;

class TareaController {

    private $tareaRepository;
    private $sucursalRepository;
    private $tareaValidator;

    public function __construct() {
        $this->tareaRepository = new TareaRepository();
        $this->sucursalRepository = new SucursalRepository();
        $this->tareaValidator = new TareaValidator();
    }

    /**
     * GET / - Listar tareas (Dashboard)
     */
    public function listar(Slim $app) {
        try {
            $usuario = JWTHelper::obtenerUsuarioDesdeToken();
            if (!$usuario) {
                return Response::enviar($app, Response::error("Sesión no válida o expirada"), 401);
            }

            $filtros = $app->request()->get();

            $tareas = $this->tareaRepository->listarPorUsuario(
                $usuario['id'], 
                $usuario['rol'], 
                $usuario['id_sucursal'],
                $filtros
            );

            return Response::enviar($app, Response::exito([
                'tareas' => $tareas, 
                'total' => count($tareas)
            ], "Listado actualizado"));

        } catch (\Exception $e) {
            error_log("Error TareaController::listar: " . $e->getMessage());
            return Response::enviar($app, Response::error("Error al cargar tareas"), 500);
        }
    }

    /**
     * POST / - Crear Tarea
     */
    public function crear(Slim $app) {
        try {
            $usuario = JWTHelper::obtenerUsuarioDesdeToken();
            if (!$usuario) return Response::enviar($app, Response::error("No autorizado"), 401);

            $datos = json_decode($app->request()->getBody(), true);
            $id_usuario = $usuario['id'];
            $id_sucursal = $usuario['id_sucursal'];
            $es_iniciativa = isset($datos['es_iniciativa']) && $datos['es_iniciativa'] === true;

            $zona_horaria = $this->sucursalRepository->obtenerZonaHoraria($id_sucursal);
            if (!$zona_horaria) $zona_horaria = 'UTC';

            $conteo_iniciativas = 0;
            if ($es_iniciativa) {
                $conteo_iniciativas = $this->tareaRepository->contarIniciativasHoy($id_usuario);
            }

            $validacion = $this->tareaValidator->validarCreacion(
                $datos,
                $id_usuario,
                $zona_horaria,
                $conteo_iniciativas
            );

            if (!$validacion['valido']) {
                return Response::enviar($app, Response::advertencia("Datos inválidos", $validacion['errores']), 400);
            }

            // Limpieza de fechas (Angular envía con 'T')
            $f_inicio = str_replace('T', ' ', $datos['fecha_inicio']);
            $f_fin = str_replace('T', ' ', $datos['fecha_fin']);

            $datos_tarea = [
                'id_sucursal' => $id_sucursal,
                'id_creador' => $id_usuario,
                'id_asignado' => $es_iniciativa ? $id_usuario : ($datos['id_asignado'] ?? null),
                'titulo' => $datos['titulo'],
                'descripcion' => $datos['descripcion'] ?? '',
                'prioridad' => $datos['prioridad'] ?? 'MEDIA',
                'categoria_asignacion' => $datos['categoria_asignacion'] ?? 'ESPECIFICA',
                'fecha_inicio' => $f_inicio,
                'fecha_fin' => $f_fin,
                'es_iniciativa' => $es_iniciativa ? 1 : 0
            ];

            $resultado = $this->tareaRepository->crear($datos_tarea, $datos['subtareas'] ?? []);

            if (!$resultado['exito']) {
                return Response::enviar($app, Response::error($resultado['error']), 500);
            }

            return Response::enviar($app, Response::exito(['id' => $resultado['id_tarea']], "Tarea creada"), 201);

        } catch (\Exception $e) {
            error_log("Error Crear Tarea: " . $e->getMessage());
            return Response::enviar($app, Response::error("Error interno: " . $e->getMessage()), 500);
        }
    }

    /**
     * GET /:id - Detalle
     */
    public function obtenerPorId(Slim $app, $id) {
        try {
            $usuario = JWTHelper::obtenerUsuarioDesdeToken();
            if (!$usuario) return Response::enviar($app, Response::error("No autorizado"), 401);

            $tarea = $this->tareaRepository->obtenerPorId($id);

            if (!$tarea || ($tarea['id_sucursal'] != $usuario['id_sucursal'] && $usuario['rol'] != 'CEO')) {
                return Response::enviar($app, Response::advertencia("Tarea no encontrada"), 404);
            }

            return Response::enviar($app, Response::exito(['tarea' => $tarea]));
        } catch (\Exception $e) {
            return Response::enviar($app, Response::error("Error interno"), 500);
        }
    }

    /**
     * ACCIÓN 1: Solicitar Validación (Colaborador)
     */
    public function solicitarValidacion(Slim $app, $id) {
        try {
            $usuario = JWTHelper::obtenerUsuarioDesdeToken();
            $this->tareaRepository->solicitarValidacion($id);
            return Response::enviar($app, Response::exito([], "Solicitud enviada"));
        } catch (\Exception $e) {
            return Response::enviar($app, Response::error("Error al procesar"), 500);
        }
    }

    /**
     * ACCIÓN 2: Aprobar (Jefes)
     */
    public function validar(Slim $app, $id) {
        $usuario = JWTHelper::obtenerUsuarioDesdeToken();
        if ($usuario['rol'] === 'COLABORADOR') {
            return Response::enviar($app, Response::error("Sin permiso para validar"), 403);
        }
        $this->tareaRepository->validar($id, $usuario['id']);
        return Response::enviar($app, Response::exito([], "Tarea aprobada"));
    }

    /**
     * ACCIÓN 3: Rechazar (Jefes)
     */
    public function rechazar(Slim $app, $id) {
        $usuario = JWTHelper::obtenerUsuarioDesdeToken();
        if ($usuario['rol'] === 'COLABORADOR') {
            return Response::enviar($app, Response::error("Sin permiso para rechazar"), 403);
        }
        $this->tareaRepository->rechazar($id);
        return Response::enviar($app, Response::exito([], "Tarea rechazada"));
    }

    /**
     * ACCIÓN 4: Eliminar
     */
    public function eliminar(Slim $app, $id) {
        try {
            $tarea = $this->tareaRepository->obtenerPorId($id);
            if (!$tarea) return Response::enviar($app, Response::error("No encontrada"), 404);
            
            $es_iniciativa = (bool)$tarea['es_iniciativa'];
            $this->tareaRepository->eliminar($id, $es_iniciativa);
            
            return Response::enviar($app, Response::exito([], "Eliminada correctamente"));
        } catch (\Exception $e) {
            return Response::enviar($app, Response::error("Error al eliminar"), 500);
        }
    }
} // Fin de la clase

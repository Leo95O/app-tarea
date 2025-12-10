<?php
/**
 * Entry Point: Tareas CRUD
 *
 * Rutas protegidas con JWT para gestión de tareas.
 *
 * IMPORTANTE: Todas las rutas requieren autenticación JWT.
 */

// Cargar autoloader de Composer
require_once __DIR__ . '/../../../../vendor/autoload.php';

// Cargar configuración
require_once __DIR__ . '/../../../../config.php';

// Importar clases necesarias
use Api\Controllers\TareaController;
use Api\Core\JWTMiddleware;
use Api\Core\AppHelper;

// Configurar zona horaria
date_default_timezone_set(TIMEZONE_DEFECTO);

// Instanciar Slim
$app = new \Slim\Slim();

// Configurar hooks globales (CORS + Error Handler)
AppHelper::configurarHooks($app);

// Verificar estructura de carpetas
AppHelper::verificarEstructuraCarpetas();

// Instanciar controlador
$controller = new TareaController();

/**
 * POST / - Crear nueva tarea
 *
 * Body (JSON):
 * {
 *   "titulo": "string",
 *   "descripcion": "string (opcional)",
 *   "prioridad": "BAJA|MEDIA|ALTA|CRITICA (opcional, default: MEDIA)",
 *   "categoria_asignacion": "ESPECIFICA|BOLSA_COLABORADOR|BOLSA_GERENTE|BOLSA_AMBOS",
 *   "id_asignado": int (opcional, null para bolsas),
 *   "fecha_inicio": "Y-m-d H:i:s",
 *   "fecha_fin": "Y-m-d H:i:s",
 *   "es_iniciativa": boolean (default: false),
 *   "subtareas": ["Título 1", "Título 2"] (opcional)
 * }
 *
 * Validaciones:
 * - Tipo A (Asignada): Sin límite duración, mínimo 15 min
 * - Tipo B (Iniciativa): Max 10/día, Max 9h, autoasignación
 */
$app->post('/', function() use ($app, $controller) {
    JWTMiddleware::verificar();
    $controller->crear($app);
});

/**
 * GET / - Listar tareas con filtros de visibilidad
 *
 * Query params (opcionales):
 * - estado_ejecucion: PROGRAMADA|EN_PROGRESO|COMPLETADA|VENCIDA
 * - prioridad: BAJA|MEDIA|ALTA|CRITICA
 * - es_iniciativa: true|false
 *
 * Aplica Matriz V6:
 * - CEO: Ve TODO
 * - GG: Ve toda su sucursal
 * - GERENTE: Ve colaboradores + propias + bolsas relevantes
 * - COLABORADOR: Ve solo asignadas + iniciativas propias
 */
$app->get('/', function() use ($app, $controller) {
    JWTMiddleware::verificar();
    $controller->listar($app);
});

/**
 * GET /:id - Obtener una tarea por ID
 *
 * Params:
 * - id: ID de la tarea
 *
 * Verifica permisos según Matriz V6.
 */
$app->get('/:id', function($id) use ($app, $controller) {
    JWTMiddleware::verificar();
    $controller->obtenerPorId($app, $id);
});

/**
 * POST /:id/completar - Marcar tarea como completada
 *
 * Params:
 * - id: ID de la tarea
 *
 * Permisos: Solo el usuario asignado
 * Validación: Todas las subtareas deben estar completadas
 */
$app->post('/:id/completar', function($id) use ($app, $controller) {
    JWTMiddleware::verificar();
    $controller->marcarCompletada($app, $id);
});

/**
 * DELETE /:id - Eliminar una tarea
 *
 * Params:
 * - id: ID de la tarea
 *
 * Comportamiento:
 * - Iniciativas: Soft delete (eliminado_en = NOW)
 * - Asignadas: Hard state (estado_ciclo_vida = INACTIVA)
 *
 * Permisos: Solo el creador puede eliminar
 */
$app->delete('/:id', function($id) use ($app, $controller) {
    JWTMiddleware::verificar();
    $controller->eliminar($app, $id);
});

/**
 * PUT /subtareas/:id - Actualizar estado de una subtarea
 *
 * Params:
 * - id: ID de la subtarea
 *
 * Body (JSON):
 * {
 *   "completada": boolean
 * }
 */
$app->put('/subtareas/:id', function($id) use ($app, $controller) {
    JWTMiddleware::verificar();
    $controller->actualizarSubtarea($app, $id);
});

// Manejar 404
$app->notFound(function() use ($app) {
    $app->halt(404, json_encode([
        'tipo' => 3,
        'mensajes' => ['Ruta no encontrada en el módulo de tareas'],
        'data' => []
    ]));
});

// Ejecutar aplicación
$app->run();

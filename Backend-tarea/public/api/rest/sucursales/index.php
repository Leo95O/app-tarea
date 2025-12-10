<?php
/**
 * Entry Point: Módulo de Sucursales
 *
 * Endpoints disponibles (SOLO CEO):
 * - POST   /sucursales                -> Crear sucursal + GG (Atómica)
 * - GET    /sucursales                -> Listar sucursales
 * - GET    /sucursales/:id            -> Obtener sucursal por ID
 * - PUT    /sucursales/:id            -> Actualizar sucursal
 * - DELETE /sucursales/:id            -> Desactivar sucursal (Soft Delete)
 * - POST   /sucursales/:id/reactivar  -> Reactivar sucursal
 *
 * Base URL: http://localhost/app-tarea/Backend-tarea/public/api/rest/sucursales/
 *
 * IMPORTANTE: Todas las rutas requieren autenticación JWT y rol CEO
 */

// Cargar configuración y autoloader
require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../config.php';

use Api\Core\AppHelper;
use Api\Core\JWTMiddleware;
use Api\Controllers\SucursalController;
use Slim\Slim;

// Configurar zona horaria
date_default_timezone_set(TIMEZONE_DEFECTO);

// Crear instancia de Slim
$app = new Slim();

// Configurar hooks globales (CORS + Error Handler)
AppHelper::configurarHooks($app);

// Verificar estructura de carpetas
AppHelper::verificarEstructuraCarpetas();

// Instanciar controlador
$sucursalController = new SucursalController();

// ============================================================================
// RUTAS PROTEGIDAS (Requieren JWT + Rol CEO)
// ============================================================================

/**
 * POST /
 *
 * Crea una sucursal con su Gerente General (Transacción Atómica)
 *
 * Headers:
 * Authorization: Bearer <TOKEN>
 * Content-Type: application/json
 *
 * Request Body:
 * {
 *   "sucursal": {
 *     "nombre": "Sucursal Centro Lima",
 *     "direccion": "Av. Arequipa 1234",
 *     "telefono": "+51 987654321",
 *     "zona_horaria": "America/Lima"
 *   },
 *   "gerente_general": {
 *     "nombre_completo": "Juan Pérez García",
 *     "email": "juan.perez@restaurant.com",
 *     "password": "Gerente123!"
 *   }
 * }
 *
 * Response (201 Created):
 * {
 *   "tipo": 1,
 *   "mensajes": ["Sucursal creada exitosamente con su Gerente General"],
 *   "data": {
 *     "id_sucursal": 1,
 *     "id_gerente_general": 3
 *   }
 * }
 */
$app->post('/', function() use ($app, $sucursalController) {
    JWTMiddleware::verificar();
    $sucursalController->crearAtomica($app);
});

/**
 * GET /
 *
 * Lista todas las sucursales con información agregada
 *
 * Headers:
 * Authorization: Bearer <TOKEN>
 *
 * Query Params (opcionales):
 * - solo_activas=true  -> Filtra solo las activas
 *
 * Response (200 OK):
 * {
 *   "tipo": 1,
 *   "mensajes": ["Sucursales obtenidas exitosamente"],
 *   "data": {
 *     "sucursales": [
 *       {
 *         "id": 1,
 *         "nombre": "Sucursal Centro Lima",
 *         "direccion": "Av. Arequipa 1234",
 *         "telefono": "+51 987654321",
 *         "zona_horaria": "America/Lima",
 *         "estado": "ACTIVA",
 *         "total_usuarios": 5,
 *         "gerente_general": "Juan Pérez (juan.perez@restaurant.com)",
 *         "creado_en": "2025-12-10 14:30:00"
 *       }
 *     ],
 *     "total": 1
 *   }
 * }
 */
$app->get('/', function() use ($app, $sucursalController) {
    JWTMiddleware::verificar();
    $sucursalController->listar($app);
});

/**
 * GET /:id
 *
 * Obtiene una sucursal por ID
 *
 * Headers:
 * Authorization: Bearer <TOKEN>
 *
 * Response (200 OK):
 * {
 *   "tipo": 1,
 *   "mensajes": ["Sucursal obtenida exitosamente"],
 *   "data": {
 *     "sucursal": { ... }
 *   }
 * }
 */
$app->get('/:id', function($id) use ($app, $sucursalController) {
    JWTMiddleware::verificar();
    $sucursalController->obtenerPorId($app, $id);
});

/**
 * PUT /:id
 *
 * Actualiza los datos de una sucursal
 *
 * Headers:
 * Authorization: Bearer <TOKEN>
 * Content-Type: application/json
 *
 * Request Body (Campos opcionales):
 * {
 *   "nombre": "Nuevo nombre",
 *   "direccion": "Nueva dirección",
 *   "telefono": "+51 999888777",
 *   "zona_horaria": "America/Bogota"
 * }
 *
 * Response (200 OK):
 * {
 *   "tipo": 1,
 *   "mensajes": ["Sucursal actualizada exitosamente"],
 *   "data": { "id": 1 }
 * }
 */
$app->put('/:id', function($id) use ($app, $sucursalController) {
    JWTMiddleware::verificar();
    $sucursalController->actualizar($app, $id);
});

/**
 * DELETE /:id
 *
 * Desactiva una sucursal (Soft Delete)
 *
 * Headers:
 * Authorization: Bearer <TOKEN>
 *
 * Response (200 OK):
 * {
 *   "tipo": 1,
 *   "mensajes": ["Sucursal desactivada exitosamente. Los datos históricos se han preservado."],
 *   "data": { "id": 1 }
 * }
 */
$app->delete('/:id', function($id) use ($app, $sucursalController) {
    JWTMiddleware::verificar();
    $sucursalController->desactivar($app, $id);
});

/**
 * POST /:id/reactivar
 *
 * Reactiva una sucursal previamente desactivada
 *
 * Headers:
 * Authorization: Bearer <TOKEN>
 *
 * Response (200 OK):
 * {
 *   "tipo": 1,
 *   "mensajes": ["Sucursal reactivada exitosamente"],
 *   "data": { "id": 1 }
 * }
 */
$app->post('/:id/reactivar', function($id) use ($app, $sucursalController) {
    JWTMiddleware::verificar();
    $sucursalController->reactivar($app, $id);
});

// Ejecutar aplicación
$app->run();

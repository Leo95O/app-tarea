<?php
/**
 * Entry Point: Módulo Tareas (Actualizado V6)
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../config.php';

use Api\Controllers\TareaController;
use Api\Core\AppHelper;
use Slim\Slim;

date_default_timezone_set('UTC');
$app = new Slim();
AppHelper::configurarHooks($app);

// RUTAS BÁSICAS
$app->get('/', function() use ($app) { (new TareaController())->listar($app); });
$app->post('/', function() use ($app) { (new TareaController())->crear($app); });
$app->get('/:id', function($id) use ($app) { (new TareaController())->obtenerPorId($app, $id); });

// FLUJO DE VALIDACIÓN (NUEVO)
// 1. Colaborador solicita revisión
$app->post('/:id/solicitar-validacion', function($id) use ($app) {
    (new TareaController())->solicitarValidacion($app, $id);
});

// 2. Jefe aprueba
$app->post('/:id/validar', function($id) use ($app) {
    (new TareaController())->validar($app, $id);
});

// 3. Jefe rechaza
$app->post('/:id/rechazar', function($id) use ($app) {
    (new TareaController())->rechazar($app, $id);
});

// DELETE
$app->delete('/:id', function($id) use ($app) {
    (new TareaController())->eliminar($app, $id);
});

// CORS y 404
$app->options('/(:id)(/:action)', function() use ($app) { $app->response()->status(200); });
$app->notFound(function() use ($app) {
    $app->halt(404, json_encode(['tipo'=>3, 'mensajes'=>['Ruta no encontrada'], 'data'=>[]]));
});

$app->run();
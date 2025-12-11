<?php
/**
 * Entry Point: Autenticación
 * Ubicación: public/api/rest/auth/index.php
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../config.php';

use Api\Core\AppHelper;
use Api\Controllers\AuthController;
use Slim\Slim;

date_default_timezone_set('UTC');

$app = new Slim();
// Configurar CORS y Manejo de Errores
AppHelper::configurarHooks($app);

// --- RUTAS ---

/**
 * POST /
 * Iniciar Sesión
 */
$app->post('/', function() use ($app) {
    // CORRECCIÓN: Instanciamos DENTRO de la ruta.
    // Si falla la BD aquí, el AppHelper atrapará el error y responderá bonito.
    $controller = new AuthController(); 
    $controller->login($app);
});

/**
 * GET /verificar
 */
$app->get('/verificar', function() use ($app) {
    $controller = new AuthController();
    $controller->verificarToken($app);
});

/**
 * OPTIONS /
 * Vital para el Preflight de CORS (Angular pregunta "¿Puedo entrar?" aquí)
 */
$app->options('/', function() use ($app) {
    // Solo devolvemos 200 OK. Los headers CORS los pone el AppHelper automáticamente.
    $app->response()->status(200);
});

$app->notFound(function() use ($app) {
    $app->halt(404, json_encode(['tipo'=>3, 'mensajes'=>['Ruta Auth no encontrada'], 'data'=>[]]));
});

$app->run();
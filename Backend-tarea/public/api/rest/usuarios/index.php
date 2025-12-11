<?php
/**
 * Entry Point: Módulo Usuarios
 * Ubicación: public/api/rest/usuarios/index.php
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../config.php';

use Api\Controllers\UsuarioController;
use Api\Core\AppHelper;
use Slim\Slim;

date_default_timezone_set('UTC');
$app = new Slim();
AppHelper::configurarHooks($app); // Aquí se inyectan las cabeceras CORS generales

// --- RUTAS ---

// 1. GET /autocomplete (La que usas)
$app->get('/autocomplete', function() use ($app) {
    $controller = new UsuarioController();
    $controller->autocomplete($app);
});

// 2. OPTIONS /autocomplete (CRÍTICO: Esto arregla el error CORS)
// Angular lanza una petición "preflight" antes del GET. 
// Si esta ruta no existe, el navegador bloquea todo.
$app->options('/autocomplete', function() use ($app) {
    $app->response()->status(200);
    // AppHelper se encarga de poner los headers Access-Control-Allow-* aquí
});

// 3. Manejo de 404
$app->notFound(function() use ($app) {
    $app->halt(404, json_encode([
        'tipo' => 3, 
        'mensajes' => ['Ruta Usuarios no encontrada'], 
        'data' => []
    ]));
});

$app->run();
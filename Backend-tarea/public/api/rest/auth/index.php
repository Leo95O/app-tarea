<?php
/**
 * Entry Point: Módulo de Autenticación
 *
 * Endpoints disponibles:
 * - POST /auth/login         -> Iniciar sesión
 * - GET  /auth/verificar     -> Verificar token JWT
 *
 * Base URL: http://localhost/backend-tarea/public/api/rest/auth/
 */

// Cargar configuración y autoloader
require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../config.php';

use Api\Core\AppHelper;
use Api\Controllers\AuthController;
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
$authController = new AuthController();

// ============================================================================
// RUTAS PÚBLICAS (No requieren autenticación)
// ============================================================================

/**
 * POST /login
 *
 * Inicia sesión y retorna token JWT
 *
 * Request Body:
 * {
 *   "email": "ceo@restaurant.com",
 *   "password": "Admin123!"
 * }
 *
 * Response (Éxito - 200):
 * {
 *   "tipo": 1,
 *   "mensajes": ["Inicio de sesión exitoso"],
 *   "data": {
 *     "token": "eyJ0eXAiOiJKV1Q...",
 *     "usuario": { ... },
 *     "expira_en_segundos": 32400
 *   }
 * }
 *
 * Response (Error - 401):
 * {
 *   "tipo": 2,
 *   "mensajes": ["Credenciales incorrectas"],
 *   "data": {}
 * }
 */
$app->post('/login', function() use ($app, $authController) {
    $authController->login($app);
});

/**
 * GET /verificar
 *
 * Verifica si un token JWT es válido
 *
 * Headers:
 * Authorization: Bearer <TOKEN>
 *
 * Response (Éxito - 200):
 * {
 *   "tipo": 1,
 *   "mensajes": ["Token válido"],
 *   "data": {
 *     "usuario": { ... },
 *     "token_valido": true
 *   }
 * }
 *
 * Response (Error - 401):
 * {
 *   "tipo": 2,
 *   "mensajes": ["Token inválido o expirado"],
 *   "data": {}
 * }
 */
$app->get('/verificar', function() use ($app, $authController) {
    $authController->verificarToken($app);
});

// Ejecutar aplicación
$app->run();

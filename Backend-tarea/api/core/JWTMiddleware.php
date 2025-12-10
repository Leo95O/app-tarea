<?php
namespace Api\Core;

use Api\Core\Response;
use Api\Core\JWTHelper;
use Slim\Slim;

/**
 * Middleware de Autenticación JWT
 *
 * Protege rutas verificando la presencia y validez del token JWT.
 * Si el token es inválido, retorna error 401 y detiene la ejecución.
 *
 * USO:
 * $app->get('/ruta-protegida', 'JWTMiddleware::verificar', function() use ($app) {
 *     // Código protegido
 * });
 */
class JWTMiddleware {

    /**
     * Verifica que exista un token JWT válido en el header
     *
     * @param Slim $app Instancia de Slim
     */
    public static function verificar() {
        $app = Slim::getInstance();

        try {
            // Extraer token del header Authorization
            $token = JWTHelper::extraerTokenDeHeader();

            if (!$token) {
                $respuesta = Response::advertencia("Token de autenticación no proporcionado");
                Response::enviar($app, $respuesta, 401);
                $app->stop(); // Detener ejecución
            }

            // Verificar validez del token
            $decoded = JWTHelper::verificarToken($token);

            if (!$decoded) {
                $respuesta = Response::advertencia("Token inválido o expirado");
                Response::enviar($app, $respuesta, 401);
                $app->stop();
            }

            // Token válido - Guardar datos del usuario en el entorno de Slim
            $app->environment['usuario_autenticado'] = (array) $decoded->data;

        } catch (\Exception $e) {
            error_log("Error en JWTMiddleware: " . $e->getMessage());
            $respuesta = Response::error("Error al verificar autenticación");
            Response::enviar($app, $respuesta, 500);
            $app->stop();
        }
    }

    /**
     * Obtiene los datos del usuario autenticado desde el entorno de Slim
     *
     * @param Slim $app Instancia de Slim
     * @return array|null Datos del usuario o null si no está autenticado
     */
    public static function obtenerUsuarioAutenticado(Slim $app) {
        return $app->environment['usuario_autenticado'] ?? null;
    }

    /**
     * Verifica que el usuario autenticado tenga un rol específico
     *
     * @param Slim $app Instancia de Slim
     * @param array $roles_permitidos Array de roles permitidos (ej: ['CEO', 'GG'])
     * @return bool True si tiene permiso
     */
    public static function verificarRol(Slim $app, $roles_permitidos) {
        $usuario = self::obtenerUsuarioAutenticado($app);

        if (!$usuario || !isset($usuario['rol'])) {
            $respuesta = Response::advertencia("No tiene permisos para realizar esta acción");
            Response::enviar($app, $respuesta, 403);
            $app->stop();
            return false;
        }

        if (!in_array($usuario['rol'], $roles_permitidos)) {
            $respuesta = Response::advertencia("No tiene permisos suficientes para esta operación");
            Response::enviar($app, $respuesta, 403);
            $app->stop();
            return false;
        }

        return true;
    }
}

<?php
namespace Api\Middlewares; 

use Api\Core\Response;
use Api\Core\JWTHelper; 
use Slim\Slim;

class JWTMiddleware {

    public static function verificar() {
        $app = Slim::getInstance();

        try {
            $token = JWTHelper::extraerTokenDeHeader();

            
            if (!$token) {
                Response::enviar($app, Response::advertencia("Token requerido"), 401);
                $app->stop();
            }

            $decoded = JWTHelper::verificarToken($token);

            if (!$decoded) {
                Response::enviar($app, Response::advertencia("Token inválido"), 401);
                $app->stop();
            }

            // Inyectar usuario en el entorno
            $app->environment['usuario_autenticado'] = (array) $decoded->data;

        } catch (\Exception $e) {
            Response::enviar($app, Response::error("Error de autenticación"), 500);
            $app->stop();
        }
    }

    public static function obtenerUsuarioAutenticado() {
        $app = Slim::getInstance();
        return $app->environment['usuario_autenticado'] ?? null;
    }
}
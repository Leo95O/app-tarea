<?php
namespace Api\Core;

use Slim\Slim;

/**
 * Helper de Inicialización Global
 */
class AppHelper {

    public static function configurarHooks(Slim $app) {
        
        $origen = defined('CORS_ORIGIN') ? CORS_ORIGIN : '*';

        // Hook CORS
        $app->hook('slim.before.dispatch', function() use ($app, $origen) {
            $app->response()->header('Access-Control-Allow-Origin', $origen);
            $app->response()->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $app->response()->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key');
            $app->response()->header('Access-Control-Max-Age', '3600');
        });

        // Preflight OPTIONS
        $app->options('/(:x+)', function() use ($app) {
            $app->response()->status(200);
        });

        // Global Error Handler
        $app->error(function(\Exception $e) use ($app) {
            self::manejarErrorGlobal($app, $e);
        });
    }

    private static function manejarErrorGlobal(Slim $app, \Exception $excepcion) {
        $timestamp = date('Y-m-d H:i:s');
        $logMsg = sprintf(
            "[%s] FATAL: %s en %s:%d\nStack: %s\n%s\n",
            $timestamp, $excepcion->getMessage(), $excepcion->getFile(), 
            $excepcion->getLine(), $excepcion->getTraceAsString(), str_repeat('-', 60)
        );

        // Definir ruta de logs segura
        $ruta_logs = defined('RUTA_LOGS') ? RUTA_LOGS : __DIR__ . '/../../logs/';
        if (is_dir($ruta_logs)) {
            error_log($logMsg, 3, $ruta_logs . 'error.log');
        } else {
            // Fallback si la carpeta no existe
            error_log($logMsg); 
        }

        $debug = defined('MODO_DEBUG') && MODO_DEBUG ? [
            'file' => $excepcion->getFile(),
            'line' => $excepcion->getLine(),
            'trace' => $excepcion->getMessage()
        ] : [];

        $respuesta = Response::error("Error interno del servidor.", $debug);
        Response::enviar($app, $respuesta, 500);
    }

    public static function verificarEstructuraCarpetas() {
        $ruta_logs = defined('RUTA_LOGS') ? RUTA_LOGS : __DIR__ . '/../../logs/';
        if (!is_dir($ruta_logs)) {
            @mkdir($ruta_logs, 0755, true);
        }
    }
}
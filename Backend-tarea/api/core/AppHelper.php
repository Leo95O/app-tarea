<?php
namespace Api\Core;

use Slim\Slim;

/**
 * Helper de Inicialización de la Aplicación
 *
 * Configura comportamientos globales:
 * - Cabeceras CORS para consumo desde Angular
 * - Manejo centralizado de errores fatales
 * - Logging de excepciones
 */
class AppHelper {

    /**
     * Configura Hooks de Slim (CORS + Error Handling)
     *
     * @param Slim $app Instancia de Slim Framework
     */
    public static function configurarHooks(Slim $app) {

        // Hook: Inyectar cabeceras CORS en cada respuesta
        $app->hook('slim.before.dispatch', function() use ($app) {
            $app->response()->header('Access-Control-Allow-Origin', CORS_ORIGIN);
            $app->response()->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $app->response()->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            $app->response()->header('Access-Control-Max-Age', '3600');
        });

        // Manejo de preflight OPTIONS (CORS)
        $app->options('/:x+', function() use ($app) {
            $app->response()->status(200);
        });

        // Manejador Global de Errores
        $app->error(function(\Exception $excepcion) use ($app) {
            self::manejarErrorGlobal($app, $excepcion);
        });
    }

    /**
     * Manejador de Errores Fatales
     *
     * Captura excepciones no controladas, las registra en log y retorna
     * JSON Tipo 3 al cliente sin exponer detalles internos.
     *
     * @param Slim $app Instancia de Slim
     * @param \Exception $excepcion Excepción capturada
     */
    private static function manejarErrorGlobal(Slim $app, \Exception $excepcion) {

        // Construir mensaje de log detallado
        $timestamp = date('Y-m-d H:i:s');
        $archivo = $excepcion->getFile();
        $linea = $excepcion->getLine();
        $mensaje = $excepcion->getMessage();
        $traza = $excepcion->getTraceAsString();

        $logMensaje = sprintf(
            "[%s] ERROR FATAL\nArchivo: %s:%d\nMensaje: %s\nStack Trace:\n%s\n%s\n",
            $timestamp,
            $archivo,
            $linea,
            $mensaje,
            $traza,
            str_repeat('-', 80)
        );

        // Escribir en archivo de log
        $archivoLog = RUTA_LOGS . 'error.log';
        error_log($logMensaje, 3, $archivoLog);

        // Responder al cliente con JSON Tipo 3
        $datosDebug = MODO_DEBUG ? [
            'archivo' => $archivo,
            'linea' => $linea,
            'mensaje_tecnico' => $mensaje
        ] : [];

        $respuesta = Response::error(
            "Ha ocurrido un error inesperado. Por favor, contacte al administrador.",
            $datosDebug
        );

        Response::enviar($app, $respuesta, 500);
    }

    /**
     * Verifica que las carpetas críticas existen
     *
     * Crea las carpetas de logs y uploads si no existen.
     */
    public static function verificarEstructuraCarpetas() {
        $carpetas = [
            RUTA_LOGS,
            RUTA_UPLOADS
        ];

        foreach ($carpetas as $carpeta) {
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0755, true);
            }
        }
    }
}

<?php
namespace Api\Core;

/**
 * Helper de Respuestas JSON Estandarizadas (Contrato V4)
 */
class Response {

    const TIPO_EXITO = 1;
    const TIPO_ADVERTENCIA = 2;
    const TIPO_ERROR = 3;

    public static function exito($datos = [], $mensaje = "Operación exitosa") {
        return self::construirRespuesta(self::TIPO_EXITO, $mensaje, $datos);
    }

    public static function advertencia($mensaje, $datos = []) {
        return self::construirRespuesta(self::TIPO_ADVERTENCIA, $mensaje, $datos);
    }

    public static function error($mensaje = "Error interno del servidor", $datos = []) {
        // En producción, limpiar datos de debug
        if (defined('MODO_DEBUG') && !MODO_DEBUG) {
            $datos = [];
        }
        return self::construirRespuesta(self::TIPO_ERROR, $mensaje, $datos);
    }

    private static function construirRespuesta($tipo, $mensajes, $datos) {
        // Garantizar que mensajes siempre sea un array
        if (!is_array($mensajes)) {
            $mensajes = [$mensajes];
        }
        
        return [
            'tipo' => $tipo,
            'mensajes' => $mensajes,
            'data' => $datos
        ];
    }

    public static function enviar($app, $respuesta, $codigoHttp = 200) {
        $app->response()->status($codigoHttp);
        $app->response()->header('Content-Type', 'application/json; charset=utf-8');
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return; 
    }
}
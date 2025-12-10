<?php
namespace Api\Core;

/**
 * Helper de Respuestas JSON Estandarizadas
 *
 * Garantiza el cumplimiento del Contrato JSON V4:
 * {
 *   "tipo": 1,           // 1=Éxito, 2=Advertencia, 3=Error
 *   "mensajes": [],      // Array de strings descriptivos
 *   "data": {}           // Objeto con datos de respuesta
 * }
 */
class Response {

    // Constantes de Tipos de Respuesta
    const TIPO_EXITO = 1;
    const TIPO_ADVERTENCIA = 2;
    const TIPO_ERROR = 3;

    /**
     * Respuesta de Éxito (Tipo 1)
     *
     * Indica operación exitosa. El cliente debe procesar el campo "data".
     *
     * @param array|object $datos Información a retornar
     * @param string $mensaje Mensaje descriptivo
     * @return array Estructura JSON estandarizada
     */
    public static function exito($datos = [], $mensaje = "Operación exitosa") {
        return self::construirRespuesta(self::TIPO_EXITO, [$mensaje], $datos);
    }

    /**
     * Respuesta de Advertencia (Tipo 2)
     *
     * Indica violación de regla de negocio (no es un error del sistema).
     * El cliente debe mostrar los mensajes al usuario.
     *
     * @param array $mensajes Lista de advertencias
     * @param array|object $datos Información adicional (opcional)
     * @return array Estructura JSON estandarizada
     */
    public static function advertencia($mensajes, $datos = []) {
        // Si recibe un string, lo convierte a array
        if (is_string($mensajes)) {
            $mensajes = [$mensajes];
        }
        return self::construirRespuesta(self::TIPO_ADVERTENCIA, $mensajes, $datos);
    }

    /**
     * Respuesta de Error Crítico (Tipo 3)
     *
     * Indica fallo interno del sistema (BD, excepción no controlada).
     * El cliente debe mostrar mensaje genérico y registrar el error.
     *
     * @param string $mensaje Descripción del error
     * @param array|object $datos Información de debug (solo en modo desarrollo)
     * @return array Estructura JSON estandarizada
     */
    public static function error($mensaje = "Error interno del servidor", $datos = []) {
        // En producción, ocultar detalles técnicos
        if (!MODO_DEBUG) {
            $datos = [];
        }
        return self::construirRespuesta(self::TIPO_ERROR, [$mensaje], $datos);
    }

    /**
     * Constructor de Respuesta (Privado)
     *
     * Construye la estructura JSON estándar.
     *
     * @param int $tipo Tipo de respuesta (1, 2 o 3)
     * @param array $mensajes Lista de mensajes
     * @param array|object $datos Información adicional
     * @return array Estructura completa
     */
    private static function construirRespuesta($tipo, $mensajes, $datos) {
        return [
            'tipo' => $tipo,
            'mensajes' => $mensajes,
            'data' => $datos
        ];
    }

    /**
     * Envía respuesta JSON al cliente
     *
     * Configura headers y emite JSON con codificación UTF-8.
     *
     * @param \Slim\Slim $app Instancia de Slim
     * @param array $respuesta Estructura de respuesta
     * @param int $codigoHttp Código de estado HTTP (200, 400, 500, etc.)
     */
    public static function enviar($app, $respuesta, $codigoHttp = 200) {
        $app->response()->status($codigoHttp);
        $app->response()->header('Content-Type', 'application/json; charset=utf-8');
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

<?php
namespace Api\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

/**
 * Helper para manejo de JSON Web Tokens (JWT)
 *
 * Encapsula la lógica de generación y verificación de tokens
 * usando la librería Firebase JWT.
 */
class JWTHelper {

    /**
     * Genera un token JWT para un usuario
     *
     * @param array $datos_usuario Datos del usuario a incluir en el token
     * @return string Token JWT firmado
     */
    public static function generarToken($datos_usuario) {
        $tiempo_emision = time();
        $tiempo_expiracion = $tiempo_emision + JWT_DURACION_SEGUNDOS;

        $payload = [
            'iat' => $tiempo_emision,                    // Tiempo de emisión (issued at)
            'exp' => $tiempo_expiracion,                 // Tiempo de expiración
            'data' => [
                'id' => $datos_usuario['id'],
                'email' => $datos_usuario['email'],
                'rol' => $datos_usuario['rol'],
                'id_sucursal' => $datos_usuario['id_sucursal'],
                'nombre_completo' => $datos_usuario['nombre_completo']
            ]
        ];

        return JWT::encode($payload, JWT_SECRET, 'HS256');
    }

    /**
     * Verifica y decodifica un token JWT
     *
     * @param string $token Token a verificar
     * @return object|null Datos decodificados del token o null si es inválido
     */
    public static function verificarToken($token) {
        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
            return $decoded;

        } catch (Exception $e) {
            error_log("Error al verificar token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extrae el token del header Authorization
     *
     * Formato esperado: "Authorization: Bearer <TOKEN>"
     *
     * @return string|null Token extraído o null si no existe
     */
    public static function extraerTokenDeHeader() {
        $headers = getallheaders();

        // Buscar el header Authorization (insensible a mayúsculas)
        foreach ($headers as $nombre => $valor) {
            if (strtolower($nombre) === 'authorization') {
                // Formato: "Bearer <TOKEN>"
                if (preg_match('/Bearer\s+(.*)$/i', $valor, $matches)) {
                    return $matches[1];
                }
            }
        }

        return null;
    }

    /**
     * Obtiene los datos del usuario desde el token en el header
     *
     * @return array|null Datos del usuario o null si el token es inválido
     */
    public static function obtenerUsuarioDesdeToken() {
        $token = self::extraerTokenDeHeader();

        if (!$token) {
            return null;
        }

        $decoded = self::verificarToken($token);

        if (!$decoded || !isset($decoded->data)) {
            return null;
        }

        return (array) $decoded->data;
    }
}

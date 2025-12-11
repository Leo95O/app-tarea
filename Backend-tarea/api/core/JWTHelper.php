<?php
namespace Api\Core;

use Firebase\JWT\JWT;
use Exception;
// ❌ ELIMINADO: use Firebase\JWT\Key; (No existe en v5)

/**
 * Helper para manejo de JWT (Versión Compatible PHP 7.1 / JWT v5.5)
 */
class JWTHelper {

    public static function generarToken($datos_usuario) {
        $secret = defined('JWT_SECRET') ? JWT_SECRET : 'secret_dev_fallback';
        $duracion = defined('JWT_DURACION_SEGUNDOS') ? JWT_DURACION_SEGUNDOS : 32400;

        $tiempo_emision = time();
        $tiempo_expiracion = $tiempo_emision + $duracion;

        $payload = [
            'iat' => $tiempo_emision,
            'exp' => $tiempo_expiracion,
            'data' => [
                'id' => $datos_usuario['id'],
                'email' => $datos_usuario['email'],
                'rol' => $datos_usuario['rol'],
                'id_sucursal' => $datos_usuario['id_sucursal'],
                'nombre_completo' => $datos_usuario['nombre_completo']
            ]
        ];

        // ✅ SINTAXIS V5: Algoritmo como 3er parámetro string (sin objeto Key)
        return JWT::encode($payload, $secret, 'HS256');
    }

    public static function verificarToken($token) {
        $secret = defined('JWT_SECRET') ? JWT_SECRET : 'secret_dev_fallback';
        
        try {
            // ✅ SINTAXIS V5: El tercer parámetro es un ARRAY de algoritmos
            // JWT::decode(string $jwt, string|resource $key, array $allowed_algs)
            $decoded = JWT::decode($token, $secret, ['HS256']);
            return $decoded;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function extraerTokenDeHeader() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    public static function obtenerUsuarioDesdeToken() {
        $token = self::extraerTokenDeHeader();
        if (!$token) return null;

        $decoded = self::verificarToken($token);
        if (!$decoded || !isset($decoded->data)) return null;

        return (array) $decoded->data;
    }
}
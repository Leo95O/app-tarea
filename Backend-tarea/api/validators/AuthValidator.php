<?php
namespace Api\Validators;

/**
 * Validador de Autenticación
 *
 * Valida formato y presencia de credenciales.
 * No valida contra la base de datos (esa responsabilidad es del Repository).
 */
class AuthValidator {

    /**
     * Valida las credenciales de login
     *
     * @param string $email Email del usuario
     * @param string $password Contraseña en texto plano
     * @return array ['valido' => bool, 'errores' => array]
     */
    public static function validarCredenciales($email, $password) {
        $errores = [];

        // Validar email
        if (empty($email)) {
            $errores[] = "El email es obligatorio";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El formato del email no es válido";
        }

        // Validar contraseña
        if (empty($password)) {
            $errores[] = "La contraseña es obligatoria";
        } elseif (strlen($password) < 6) {
            $errores[] = "La contraseña debe tener al menos 6 caracteres";
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Valida los datos para crear un nuevo usuario
     *
     * @param array $datos Datos del usuario a validar
     * @return array ['valido' => bool, 'errores' => array]
     */
    public static function validarCreacionUsuario($datos) {
        $errores = [];

        // Validar nombre completo
        if (empty($datos['nombre_completo'])) {
            $errores[] = "El nombre completo es obligatorio";
        } elseif (strlen($datos['nombre_completo']) < 3) {
            $errores[] = "El nombre completo debe tener al menos 3 caracteres";
        }

        // Validar email
        if (empty($datos['email'])) {
            $errores[] = "El email es obligatorio";
        } elseif (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El formato del email no es válido";
        }

        // Validar contraseña
        if (empty($datos['password'])) {
            $errores[] = "La contraseña es obligatoria";
        } elseif (strlen($datos['password']) < 8) {
            $errores[] = "La contraseña debe tener al menos 8 caracteres";
        }

        // Validar rol
        $roles_validos = ['CEO', 'GG', 'GERENTE', 'COLABORADOR'];
        if (empty($datos['rol'])) {
            $errores[] = "El rol es obligatorio";
        } elseif (!in_array($datos['rol'], $roles_validos)) {
            $errores[] = "El rol especificado no es válido";
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}

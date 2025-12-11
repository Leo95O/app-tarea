<?php
namespace Api\Validators;

/**
 * Validador de Autenticación
 * Responsabilidad: Integridad de datos antes de procesar lógica.
 */
class AuthValidator {

    /**
     * Valida los datos del Login (Sincronizado con AuthController).
     * * @param array $datos Array con keys 'email' y 'password'
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validarLogin($datos) {
        $errores = [];
        
        $email = $datos['email'] ?? '';
        $password = $datos['password'] ?? '';

        // 1. Validar Email
        if (empty($email)) {
            $errores[] = "El correo electrónico es obligatorio";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El formato del correo no es válido";
        }

        // 2. Validar Contraseña
        if (empty($password)) {
            $errores[] = "La contraseña es obligatoria";
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Valida la creación de nuevos usuarios (Para el módulo de Usuarios).
     */
    public function validarCreacionUsuario($datos) {
        $errores = [];

        // Nombre
        if (empty($datos['nombre_completo'])) {
            $errores[] = "El nombre es obligatorio";
        } elseif (strlen($datos['nombre_completo']) < 3) {
            $errores[] = "El nombre es muy corto";
        }

        // Email
        if (empty($datos['email']) || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = "Email inválido o vacío";
        }

        // Password (reglas más estrictas para creación)
        if (empty($datos['password'])) {
            $errores[] = "La contraseña es obligatoria";
        } elseif (strlen($datos['password']) < 6) {
            $errores[] = "La contraseña debe tener al menos 6 caracteres";
        }

        // Rol
        $roles_validos = ['CEO', 'GG', 'GERENTE', 'COLABORADOR'];
        if (empty($datos['rol']) || !in_array($datos['rol'], $roles_validos)) {
            $errores[] = "Rol no válido";
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}
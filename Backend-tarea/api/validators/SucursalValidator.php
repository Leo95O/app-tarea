<?php
namespace Api\Validators;

/**
 * Validador de Sucursales
 *
 * Valida datos de sucursales y gerentes generales para creación atómica.
 */
class SucursalValidator {

    /**
     * Valida los datos para crear una sucursal con su Gerente General
     *
     * Regla de negocio: No se permite crear sucursales vacías,
     * siempre debe incluirse el GG en la misma transacción.
     *
     * @param array $datos_sucursal Datos de la sucursal
     * @param array $datos_gerente Datos del Gerente General
     * @return array ['valido' => bool, 'errores' => array]
     */
    public static function validarCreacionAtomica($datos_sucursal, $datos_gerente) {
        $errores = [];

        // Validar datos de sucursal
        $errores_sucursal = self::validarDatosSucursal($datos_sucursal);
        $errores = array_merge($errores, $errores_sucursal);

        // Validar datos del Gerente General
        $errores_gerente = self::validarDatosGerenteGeneral($datos_gerente);
        $errores = array_merge($errores, $errores_gerente);

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Valida los datos de una sucursal
     *
     * @param array $datos Datos de la sucursal
     * @return array Lista de errores (vacío si es válido)
     */
    private static function validarDatosSucursal($datos) {
        $errores = [];

        // Validar nombre
        if (empty($datos['nombre'])) {
            $errores[] = "El nombre de la sucursal es obligatorio";
        } elseif (strlen($datos['nombre']) < 3) {
            $errores[] = "El nombre de la sucursal debe tener al menos 3 caracteres";
        }

        // Validar dirección
        if (empty($datos['direccion'])) {
            $errores[] = "La dirección de la sucursal es obligatoria";
        }

        // Validar zona horaria
        if (empty($datos['zona_horaria'])) {
            $errores[] = "La zona horaria es obligatoria";
        } else {
            // Verificar que la zona horaria sea válida
            $zonas_validas = timezone_identifiers_list();
            if (!in_array($datos['zona_horaria'], $zonas_validas)) {
                $errores[] = "La zona horaria especificada no es válida";
            }
        }

        // Validar teléfono (opcional, pero si se proporciona debe tener formato)
        if (!empty($datos['telefono'])) {
            if (strlen($datos['telefono']) < 7) {
                $errores[] = "El teléfono debe tener al menos 7 caracteres";
            }
        }

        return $errores;
    }

    /**
     * Valida los datos del Gerente General
     *
     * @param array $datos Datos del GG
     * @return array Lista de errores (vacío si es válido)
     */
    private static function validarDatosGerenteGeneral($datos) {
        $errores = [];

        // Validar nombre completo
        if (empty($datos['nombre_completo'])) {
            $errores[] = "El nombre del Gerente General es obligatorio";
        } elseif (strlen($datos['nombre_completo']) < 3) {
            $errores[] = "El nombre del Gerente General debe tener al menos 3 caracteres";
        }

        // Validar email
        if (empty($datos['email'])) {
            $errores[] = "El email del Gerente General es obligatorio";
        } elseif (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El formato del email del Gerente General no es válido";
        }

        // Validar contraseña
        if (empty($datos['password'])) {
            $errores[] = "La contraseña del Gerente General es obligatoria";
        } elseif (strlen($datos['password']) < 8) {
            $errores[] = "La contraseña del Gerente General debe tener al menos 8 caracteres";
        }

        return $errores;
    }

    /**
     * Valida los datos para actualizar una sucursal
     *
     * @param array $datos Datos a actualizar
     * @return array ['valido' => bool, 'errores' => array]
     */
    public static function validarActualizacion($datos) {
        $errores = [];

        // Validar nombre (si se proporciona)
        if (isset($datos['nombre'])) {
            if (empty($datos['nombre'])) {
                $errores[] = "El nombre no puede estar vacío";
            } elseif (strlen($datos['nombre']) < 3) {
                $errores[] = "El nombre debe tener al menos 3 caracteres";
            }
        }

        // Validar dirección (si se proporciona)
        if (isset($datos['direccion'])) {
            if (empty($datos['direccion'])) {
                $errores[] = "La dirección no puede estar vacía";
            }
        }

        // Validar zona horaria (si se proporciona)
        if (isset($datos['zona_horaria'])) {
            $zonas_validas = timezone_identifiers_list();
            if (!in_array($datos['zona_horaria'], $zonas_validas)) {
                $errores[] = "La zona horaria especificada no es válida";
            }
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}

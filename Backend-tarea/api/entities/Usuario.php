<?php
namespace Api\Entities;

/**
 * Entidad Usuario (DTO - Data Transfer Object)
 *
 * Objeto anémico que representa un usuario del sistema.
 * No contiene lógica de negocio ni acceso a base de datos.
 *
 * Jerarquía de Roles:
 * - CEO: Alcance global, autovalidación
 * - GG (Gerente General): Alcance sucursal
 * - GERENTE: Alcance de equipo
 * - COLABORADOR: Alcance personal
 */
class Usuario {

    public $id;
    public $id_sucursal;
    public $id_gerente_directo;
    public $rol;
    public $nombre_completo;
    public $email;
    public $password_hash;
    public $activo;
    public $creado_en;

    /**
     * Constructor del Usuario
     *
     * @param array $datos Array asociativo con los datos del usuario
     */
    public function __construct($datos = []) {
        $this->id = $datos['id'] ?? null;
        $this->id_sucursal = $datos['id_sucursal'] ?? null;
        $this->id_gerente_directo = $datos['id_gerente_directo'] ?? null;
        $this->rol = $datos['rol'] ?? null;
        $this->nombre_completo = $datos['nombre_completo'] ?? '';
        $this->email = $datos['email'] ?? '';
        $this->password_hash = $datos['password_hash'] ?? '';
        $this->activo = $datos['activo'] ?? true;
        $this->creado_en = $datos['creado_en'] ?? null;
    }

    /**
     * Convierte el objeto a array (útil para respuestas JSON)
     *
     * @param bool $incluir_password Si se debe incluir el hash (false por defecto)
     * @return array Representación del usuario sin datos sensibles
     */
    public function aArray($incluir_password = false) {
        $datos = [
            'id' => $this->id,
            'id_sucursal' => $this->id_sucursal,
            'id_gerente_directo' => $this->id_gerente_directo,
            'rol' => $this->rol,
            'nombre_completo' => $this->nombre_completo,
            'email' => $this->email,
            'activo' => $this->activo,
            'creado_en' => $this->creado_en
        ];

        if ($incluir_password) {
            $datos['password_hash'] = $this->password_hash;
        }

        return $datos;
    }

    /**
     * Verifica si el usuario es CEO (alcance global)
     *
     * @return bool True si es CEO
     */
    public function esCEO() {
        return $this->rol === 'CEO';
    }

    /**
     * Verifica si el usuario es Gerente General
     *
     * @return bool True si es GG
     */
    public function esGerenteGeneral() {
        return $this->rol === 'GG';
    }

    /**
     * Verifica si el usuario es Gerente
     *
     * @return bool True si es Gerente
     */
    public function esGerente() {
        return $this->rol === 'GERENTE';
    }

    /**
     * Verifica si el usuario es Colaborador
     *
     * @return bool True si es Colaborador
     */
    public function esColaborador() {
        return $this->rol === 'COLABORADOR';
    }

    /**
     * Verifica si el usuario tiene permisos de jefatura (CEO, GG o Gerente)
     *
     * @return bool True si es jefe
     */
    public function esJefe() {
        return in_array($this->rol, ['CEO', 'GG', 'GERENTE']);
    }
}

<?php
namespace Api\Entities;

/**
 * Entidad Sucursal (DTO - Data Transfer Object)
 *
 * Objeto anémico que representa una sucursal del restaurante.
 * Cada sucursal es un contexto aislado (Multi-Tenant).
 */
class Sucursal {

    public $id;
    public $nombre;
    public $direccion;
    public $telefono;
    public $zona_horaria;
    public $estado;
    public $creado_en;

    /**
     * Constructor de Sucursal
     *
     * @param array $datos Array asociativo con los datos de la sucursal
     */
    public function __construct($datos = []) {
        $this->id = $datos['id'] ?? null;
        $this->nombre = $datos['nombre'] ?? '';
        $this->direccion = $datos['direccion'] ?? '';
        $this->telefono = $datos['telefono'] ?? '';
        $this->zona_horaria = $datos['zona_horaria'] ?? 'America/Lima';
        $this->estado = $datos['estado'] ?? 'ACTIVA';
        $this->creado_en = $datos['creado_en'] ?? null;
    }

    /**
     * Convierte el objeto a array (útil para respuestas JSON)
     *
     * @return array Representación de la sucursal
     */
    public function aArray() {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'zona_horaria' => $this->zona_horaria,
            'estado' => $this->estado,
            'creado_en' => $this->creado_en
        ];
    }

    /**
     * Verifica si la sucursal está activa
     *
     * @return bool True si está activa
     */
    public function estaActiva() {
        return $this->estado === 'ACTIVA';
    }
}

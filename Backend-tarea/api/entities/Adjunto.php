<?php
namespace Api\Entities;

/**
 * Entidad Adjunto (DTO - Data Transfer Object)
 *
 * Representa un archivo adjunto a una tarea.
 */
class Adjunto {

    public $id;
    public $id_tarea;
    public $id_subido_por;
    public $ruta_archivo;
    public $tipo_archivo;
    public $creado_en;

    // Relaciones
    public $subido_por_nombre;

    /**
     * Constructor de Adjunto
     *
     * @param array $datos Array asociativo con los datos del adjunto
     */
    public function __construct($datos = []) {
        $this->id = $datos['id'] ?? null;
        $this->id_tarea = $datos['id_tarea'] ?? null;
        $this->id_subido_por = $datos['id_subido_por'] ?? null;
        $this->ruta_archivo = $datos['ruta_archivo'] ?? '';
        $this->tipo_archivo = $datos['tipo_archivo'] ?? '';
        $this->creado_en = $datos['creado_en'] ?? null;
        $this->subido_por_nombre = $datos['subido_por_nombre'] ?? null;
    }

    /**
     * Convierte el objeto a array (útil para respuestas JSON)
     *
     * @return array Representación del adjunto
     */
    public function aArray() {
        return [
            'id' => $this->id,
            'id_tarea' => $this->id_tarea,
            'id_subido_por' => $this->id_subido_por,
            'ruta_archivo' => $this->ruta_archivo,
            'tipo_archivo' => $this->tipo_archivo,
            'creado_en' => $this->creado_en,
            'subido_por_nombre' => $this->subido_por_nombre
        ];
    }

    /**
     * Obtiene la URL completa del archivo
     *
     * @param string $base_url URL base del servidor
     * @return string URL completa
     */
    public function obtenerUrlCompleta($base_url = '') {
        return $base_url . '/' . $this->ruta_archivo;
    }
}

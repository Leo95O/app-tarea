<?php
namespace Api\Entities;

/**
 * Entidad Subtarea (DTO - Data Transfer Object)
 *
 * Representa una subtarea dentro de una tarea principal.
 */
class Subtarea {

    public $id;
    public $id_tarea;
    public $titulo;
    public $completada;
    public $id_creador;
    public $creado_en;

    // Relaciones
    public $creador_nombre;

    /**
     * Constructor de Subtarea
     *
     * @param array $datos Array asociativo con los datos de la subtarea
     */
    public function __construct($datos = []) {
        $this->id = $datos['id'] ?? null;
        $this->id_tarea = $datos['id_tarea'] ?? null;
        $this->titulo = $datos['titulo'] ?? '';
        $this->completada = $datos['completada'] ?? false;
        $this->id_creador = $datos['id_creador'] ?? null;
        $this->creado_en = $datos['creado_en'] ?? null;
        $this->creador_nombre = $datos['creador_nombre'] ?? null;
    }

    /**
     * Convierte el objeto a array (útil para respuestas JSON)
     *
     * @return array Representación de la subtarea
     */
    public function aArray() {
        return [
            'id' => $this->id,
            'id_tarea' => $this->id_tarea,
            'titulo' => $this->titulo,
            'completada' => (bool) $this->completada,
            'id_creador' => $this->id_creador,
            'creado_en' => $this->creado_en,
            'creador_nombre' => $this->creador_nombre
        ];
    }

    /**
     * Verifica si la subtarea está completada
     *
     * @return bool True si está completada
     */
    public function estaCompletada() {
        return (bool) $this->completada;
    }
}

<?php
namespace Api\Entities;

/**
 * Entidad Tarea (DTO)
 * Estándar V4: Objeto anémico (Solo datos, sin lógica de negocio ni SQL).
 */
class Tarea {
    // Identificadores
    public $id;
    public $id_sucursal;
    public $id_creador;
    public $id_asignado;
    public $id_validador;

    // Configuración
    public $titulo;
    public $descripcion;
    public $prioridad;
    public $categoria_asignacion;

    // Tiempos (UTC)
    public $fecha_inicio;
    public $fecha_fin_original;
    public $fecha_fin_actual;
    public $fecha_validacion;

    // Estados
    public $estado_ejecucion;      // PROGRAMADA, EN_PROGRESO, COMPLETADA, VENCIDA
    public $estado_ciclo_vida;     // ABIERTA, VALIDADA, FINALIZADA_VENCIDA, INACTIVA
    public $estado_validacion;     // SIN_VALIDAR, POR_VALIDAR, VALIDADA

    // Contadores
    public $contador_extensiones;
    public $contador_rechazos;

    // Flags
    public $es_iniciativa;
    public $eliminado_en;

    // Metadatos
    public $creado_en;
    public $actualizado_en;

    // Relaciones (Arrays vacíos por defecto)
    public $subtareas = [];
    public $adjuntos = [];
    public $creador_nombre;
    public $asignado_nombre;
    public $validador_nombre;

    public function __construct($datos = []) {
        $this->id = $datos['id'] ?? null;
        $this->id_sucursal = $datos['id_sucursal'] ?? null;
        $this->id_creador = $datos['id_creador'] ?? null;
        $this->id_asignado = $datos['id_asignado'] ?? null;
        $this->id_validador = $datos['id_validador'] ?? null;
        $this->titulo = $datos['titulo'] ?? '';
        $this->descripcion = $datos['descripcion'] ?? '';
        $this->prioridad = $datos['prioridad'] ?? 'MEDIA';
        $this->categoria_asignacion = $datos['categoria_asignacion'] ?? 'ESPECIFICA';
        $this->fecha_inicio = $datos['fecha_inicio'] ?? null;
        $this->fecha_fin_original = $datos['fecha_fin_original'] ?? null;
        $this->fecha_fin_actual = $datos['fecha_fin_actual'] ?? null;
        $this->fecha_validacion = $datos['fecha_validacion'] ?? null;
        $this->estado_ejecucion = $datos['estado_ejecucion'] ?? 'PROGRAMADA';
        $this->estado_ciclo_vida = $datos['estado_ciclo_vida'] ?? 'ABIERTA';
        $this->estado_validacion = $datos['estado_validacion'] ?? 'SIN_VALIDAR';
        $this->contador_extensiones = $datos['contador_extensiones'] ?? 0;
        $this->contador_rechazos = $datos['contador_rechazos'] ?? 0;
        $this->es_iniciativa = $datos['es_iniciativa'] ?? false;
        $this->eliminado_en = $datos['eliminado_en'] ?? null;
        $this->creado_en = $datos['creado_en'] ?? null;
        $this->actualizado_en = $datos['actualizado_en'] ?? null;
        
        // Carga de relaciones opcionales
        if (isset($datos['subtareas'])) $this->subtareas = $datos['subtareas'];
        if (isset($datos['adjuntos'])) $this->adjuntos = $datos['adjuntos'];
        $this->creador_nombre = $datos['creador_nombre'] ?? null;
        $this->asignado_nombre = $datos['asignado_nombre'] ?? null;
        $this->validador_nombre = $datos['validador_nombre'] ?? null;
    }

    public function aArray() {
        return get_object_vars($this);
    }
}
<?php
namespace Api\Entities;

/**
 * Entidad Tarea (DTO - Data Transfer Object)
 *
 * Objeto anémico que representa una tarea del sistema.
 * Contiene toda la información de estados, contadores y metadatos.
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

    // Tiempos (guardados en UTC, convertidos por zona horaria)
    public $fecha_inicio;
    public $fecha_fin_original;
    public $fecha_fin_actual;
    public $fecha_validacion;

    // Estados (Máquina de Estados)
    public $estado_ejecucion;      // PROGRAMADA, EN_PROGRESO, COMPLETADA, VENCIDA
    public $estado_ciclo_vida;     // ABIERTA, VALIDADA, FINALIZADA_VENCIDA, INACTIVA
    public $estado_validacion;     // SIN_VALIDAR, POR_VALIDAR, VALIDADA

    // Contadores (para KPIs)
    public $contador_extensiones;
    public $contador_rechazos;

    // Indicadores
    public $es_iniciativa;
    public $eliminado_en;

    // Metadatos
    public $creado_en;
    public $actualizado_en;

    // Relaciones (no persisted, cargados por queries)
    public $subtareas;
    public $adjuntos;
    public $creador_nombre;
    public $asignado_nombre;
    public $validador_nombre;

    /**
     * Constructor de Tarea
     *
     * @param array $datos Array asociativo con los datos de la tarea
     */
    public function __construct($datos = []) {
        // Identificadores
        $this->id = $datos['id'] ?? null;
        $this->id_sucursal = $datos['id_sucursal'] ?? null;
        $this->id_creador = $datos['id_creador'] ?? null;
        $this->id_asignado = $datos['id_asignado'] ?? null;
        $this->id_validador = $datos['id_validador'] ?? null;

        // Configuración
        $this->titulo = $datos['titulo'] ?? '';
        $this->descripcion = $datos['descripcion'] ?? '';
        $this->prioridad = $datos['prioridad'] ?? 'MEDIA';
        $this->categoria_asignacion = $datos['categoria_asignacion'] ?? 'ESPECIFICA';

        // Tiempos
        $this->fecha_inicio = $datos['fecha_inicio'] ?? null;
        $this->fecha_fin_original = $datos['fecha_fin_original'] ?? null;
        $this->fecha_fin_actual = $datos['fecha_fin_actual'] ?? null;
        $this->fecha_validacion = $datos['fecha_validacion'] ?? null;

        // Estados
        $this->estado_ejecucion = $datos['estado_ejecucion'] ?? 'PROGRAMADA';
        $this->estado_ciclo_vida = $datos['estado_ciclo_vida'] ?? 'ABIERTA';
        $this->estado_validacion = $datos['estado_validacion'] ?? 'SIN_VALIDAR';

        // Contadores
        $this->contador_extensiones = $datos['contador_extensiones'] ?? 0;
        $this->contador_rechazos = $datos['contador_rechazos'] ?? 0;

        // Indicadores
        $this->es_iniciativa = $datos['es_iniciativa'] ?? false;
        $this->eliminado_en = $datos['eliminado_en'] ?? null;

        // Metadatos
        $this->creado_en = $datos['creado_en'] ?? null;
        $this->actualizado_en = $datos['actualizado_en'] ?? null;

        // Relaciones
        $this->subtareas = $datos['subtareas'] ?? [];
        $this->adjuntos = $datos['adjuntos'] ?? [];
        $this->creador_nombre = $datos['creador_nombre'] ?? null;
        $this->asignado_nombre = $datos['asignado_nombre'] ?? null;
        $this->validador_nombre = $datos['validador_nombre'] ?? null;
    }

    /**
     * Convierte el objeto a array (útil para respuestas JSON)
     *
     * @param bool $incluir_relaciones Si se deben incluir subtareas y adjuntos
     * @return array Representación de la tarea
     */
    public function aArray($incluir_relaciones = true) {
        $datos = [
            'id' => $this->id,
            'id_sucursal' => $this->id_sucursal,
            'id_creador' => $this->id_creador,
            'id_asignado' => $this->id_asignado,
            'id_validador' => $this->id_validador,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'prioridad' => $this->prioridad,
            'categoria_asignacion' => $this->categoria_asignacion,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin_original' => $this->fecha_fin_original,
            'fecha_fin_actual' => $this->fecha_fin_actual,
            'fecha_validacion' => $this->fecha_validacion,
            'estado_ejecucion' => $this->estado_ejecucion,
            'estado_ciclo_vida' => $this->estado_ciclo_vida,
            'estado_validacion' => $this->estado_validacion,
            'contador_extensiones' => $this->contador_extensiones,
            'contador_rechazos' => $this->contador_rechazos,
            'es_iniciativa' => (bool) $this->es_iniciativa,
            'eliminado_en' => $this->eliminado_en,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            'creador_nombre' => $this->creador_nombre,
            'asignado_nombre' => $this->asignado_nombre,
            'validador_nombre' => $this->validador_nombre
        ];

        if ($incluir_relaciones) {
            $datos['subtareas'] = $this->subtareas;
            $datos['adjuntos'] = $this->adjuntos;
        }

        return $datos;
    }

    /**
     * Verifica si la tarea está vencida
     *
     * @return bool True si está vencida
     */
    public function estaVencida() {
        return $this->estado_ejecucion === 'VENCIDA';
    }

    /**
     * Verifica si la tarea está completada (por el usuario)
     *
     * @return bool True si está completada
     */
    public function estaCompletada() {
        return $this->estado_ejecucion === 'COMPLETADA';
    }

    /**
     * Verifica si la tarea está validada (por un jefe)
     *
     * @return bool True si está validada
     */
    public function estaValidada() {
        return $this->estado_ciclo_vida === 'VALIDADA';
    }

    /**
     * Verifica si es una tarea de iniciativa
     *
     * @return bool True si es iniciativa
     */
    public function esIniciativa() {
        return (bool) $this->es_iniciativa;
    }

    /**
     * Verifica si está en bolsa (sin asignar)
     *
     * @return bool True si está en bolsa
     */
    public function estaEnBolsa() {
        return $this->id_asignado === null && $this->categoria_asignacion !== 'ESPECIFICA';
    }
}

<?php
namespace Api\Validators;

use DateTime;
use PDO;

/**
 * Validador de Tareas
 *
 * Implementa todas las reglas de negocio V6:
 * - Tipo A (Asignada): Sin límite de duración
 * - Tipo B (Iniciativa): Max 10/día, Max 9 horas, autoasignación
 * - Validaciones de fechas, subtareas, etc.
 */
class TareaValidator {

    private $conexion;

    /**
     * Constructor
     *
     * @param PDO $conexion Conexión PDO para validaciones que requieren BD
     */
    public function __construct($conexion = null) {
        $this->conexion = $conexion;
    }

    /**
     * Valida los datos para crear una tarea
     *
     * Aplica reglas diferentes según si es iniciativa o asignada.
     *
     * @param array $datos Datos de la tarea
     * @param int $id_usuario_creador ID del usuario que crea la tarea
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validarCreacion($datos, $id_usuario_creador) {
        $errores = [];
        $es_iniciativa = isset($datos['es_iniciativa']) && $datos['es_iniciativa'] === true;

        // Validaciones comunes
        $errores = array_merge($errores, $this->validarDatosBasicos($datos));
        $errores = array_merge($errores, $this->validarFechas($datos, $es_iniciativa));

        // Validaciones específicas por tipo
        if ($es_iniciativa) {
            $errores = array_merge($errores, $this->validarIniciativa($datos, $id_usuario_creador));
        } else {
            $errores = array_merge($errores, $this->validarTareaAsignada($datos));
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Valida datos básicos de la tarea
     *
     * @param array $datos Datos de la tarea
     * @return array Lista de errores
     */
    private function validarDatosBasicos($datos) {
        $errores = [];

        // Validar título
        if (empty($datos['titulo'])) {
            $errores[] = "El título de la tarea es obligatorio";
        } elseif (strlen($datos['titulo']) < 3) {
            $errores[] = "El título debe tener al menos 3 caracteres";
        } elseif (strlen($datos['titulo']) > 200) {
            $errores[] = "El título no puede exceder 200 caracteres";
        }

        // Validar prioridad
        $prioridades_validas = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];
        if (isset($datos['prioridad']) && !in_array($datos['prioridad'], $prioridades_validas)) {
            $errores[] = "La prioridad especificada no es válida";
        }

        return $errores;
    }

    /**
     * Valida las fechas de la tarea
     *
     * Regla: Fecha fin >= Fecha inicio + 15 minutos
     * Para iniciativas: Duración máxima 9 horas
     *
     * @param array $datos Datos de la tarea
     * @param bool $es_iniciativa Si es tarea de iniciativa
     * @return array Lista de errores
     */
    private function validarFechas($datos, $es_iniciativa) {
        $errores = [];

        if (empty($datos['fecha_inicio']) || empty($datos['fecha_fin'])) {
            $errores[] = "Las fechas de inicio y fin son obligatorias";
            return $errores;
        }

        try {
            $fecha_inicio = new DateTime($datos['fecha_inicio']);
            $fecha_fin = new DateTime($datos['fecha_fin']);
            $ahora = new DateTime();

            // La fecha de inicio no puede ser en el pasado
            if ($fecha_inicio < $ahora) {
                $errores[] = "La fecha de inicio no puede ser en el pasado";
            }

            // Diferencia mínima: 15 minutos
            $diferencia_segundos = $fecha_fin->getTimestamp() - $fecha_inicio->getTimestamp();
            $diferencia_minutos = $diferencia_segundos / 60;

            if ($diferencia_minutos < 15) {
                $errores[] = "La tarea debe tener una duración mínima de 15 minutos";
            }

            // Para iniciativas: Duración máxima 9 horas
            if ($es_iniciativa) {
                $diferencia_horas = $diferencia_segundos / 3600;
                if ($diferencia_horas > 9) {
                    $errores[] = "Las tareas de iniciativa no pueden superar las 9 horas de duración";
                }
            }

        } catch (\Exception $e) {
            $errores[] = "Formato de fecha inválido. Use formato: Y-m-d H:i:s";
        }

        return $errores;
    }

    /**
     * Valida una tarea de iniciativa (Tipo B)
     *
     * Reglas:
     * - Máximo 10 tareas de iniciativa por día
     * - Duración máxima 9 horas
     * - Autoasignación forzosa
     *
     * @param array $datos Datos de la tarea
     * @param int $id_usuario ID del usuario creador
     * @return array Lista de errores
     */
    private function validarIniciativa($datos, $id_usuario) {
        $errores = [];

        // Forzar autoasignación
        if (isset($datos['id_asignado']) && $datos['id_asignado'] != $id_usuario) {
            $errores[] = "Las tareas de iniciativa deben ser autoasignadas";
        }

        // No se puede asignar a bolsa
        if (isset($datos['categoria_asignacion']) && $datos['categoria_asignacion'] !== 'ESPECIFICA') {
            $errores[] = "Las tareas de iniciativa no pueden asignarse a bolsas de trabajo";
        }

        // Verificar límite de 10 tareas por día
        if ($this->conexion) {
            $limite_alcanzado = $this->verificarLimiteIniciativasDiarias($id_usuario);
            if ($limite_alcanzado) {
                $errores[] = "Has alcanzado el límite de 10 tareas de iniciativa por día";
            }
        }

        return $errores;
    }

    /**
     * Valida una tarea asignada (Tipo A)
     *
     * @param array $datos Datos de la tarea
     * @return array Lista de errores
     */
    private function validarTareaAsignada($datos) {
        $errores = [];

        // Si no se especifica asignado y no es bolsa, es error
        if (empty($datos['id_asignado']) &&
            (!isset($datos['categoria_asignacion']) || $datos['categoria_asignacion'] === 'ESPECIFICA')) {
            $errores[] = "Debe especificar un asignado o asignar la tarea a una bolsa de trabajo";
        }

        // Si es bolsa, validar categoría
        if (isset($datos['categoria_asignacion']) && $datos['categoria_asignacion'] !== 'ESPECIFICA') {
            $categorias_validas = ['BOLSA_COLABORADOR', 'BOLSA_GERENTE', 'BOLSA_AMBOS'];
            if (!in_array($datos['categoria_asignacion'], $categorias_validas)) {
                $errores[] = "Categoría de asignación no válida";
            }
        }

        return $errores;
    }

    /**
     * Verifica si el usuario ha alcanzado el límite de 10 iniciativas por día
     *
     * @param int $id_usuario ID del usuario
     * @return bool True si alcanzó el límite
     */
    private function verificarLimiteIniciativasDiarias($id_usuario) {
        if (!$this->conexion) {
            return false;
        }

        try {
            $sql = "SELECT COUNT(*) as total
                    FROM tareas
                    WHERE id_creador = :id_usuario
                    AND es_iniciativa = 1
                    AND DATE(creado_en) = CURDATE()
                    AND eliminado_en IS NULL";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute(['id_usuario' => $id_usuario]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return $resultado['total'] >= 10;

        } catch (\Exception $e) {
            error_log("Error en verificarLimiteIniciativasDiarias: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valida que todas las subtareas estén completadas antes de solicitar validación
     *
     * @param array $subtareas Lista de subtareas
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validarSubtareasCompletadas($subtareas) {
        $errores = [];

        if (empty($subtareas)) {
            $errores[] = "La tarea no tiene subtareas definidas";
            return ['valido' => false, 'errores' => $errores];
        }

        $total = count($subtareas);
        $completadas = 0;

        foreach ($subtareas as $subtarea) {
            if (isset($subtarea['completada']) && $subtarea['completada']) {
                $completadas++;
            }
        }

        if ($completadas < $total) {
            $errores[] = "Debes completar todas las subtareas antes de solicitar validación ({$completadas}/{$total})";
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Valida los datos para actualizar una tarea
     *
     * @param array $datos Datos a actualizar
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validarActualizacion($datos) {
        $errores = [];

        // Validar título (si se proporciona)
        if (isset($datos['titulo'])) {
            if (empty($datos['titulo'])) {
                $errores[] = "El título no puede estar vacío";
            } elseif (strlen($datos['titulo']) < 3) {
                $errores[] = "El título debe tener al menos 3 caracteres";
            }
        }

        // Validar prioridad (si se proporciona)
        if (isset($datos['prioridad'])) {
            $prioridades_validas = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];
            if (!in_array($datos['prioridad'], $prioridades_validas)) {
                $errores[] = "La prioridad especificada no es válida";
            }
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}

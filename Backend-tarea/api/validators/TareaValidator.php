<?php
namespace Api\Validators;

use DateTime;
use DateTimeZone;
use Exception;

class TareaValidator {

    public function validarCreacion($datos, $id_usuario_creador, $zona_horaria, $conteo_iniciativas_hoy = 0) {
        $errores = [];
        $es_iniciativa = isset($datos['es_iniciativa']) && $datos['es_iniciativa'] === true;

        $errores = array_merge($errores, $this->validarDatosBasicos($datos));
        $errores = array_merge($errores, $this->validarFechas($datos, $es_iniciativa, $zona_horaria));

        if ($es_iniciativa) {
            $errores = array_merge($errores, $this->validarIniciativa($datos, $id_usuario_creador, $conteo_iniciativas_hoy));
        } else {
            $errores = array_merge($errores, $this->validarTareaAsignada($datos));
        }

        return ['valido' => empty($errores), 'errores' => $errores];
    }

    private function validarDatosBasicos($datos) {
        $errores = [];
        if (empty($datos['titulo'])) $errores[] = "El título es obligatorio";
        $prioridades = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];
        if (isset($datos['prioridad']) && !in_array($datos['prioridad'], $prioridades)) {
            $errores[] = "Prioridad no válida";
        }
        return $errores;
    }

    private function validarFechas($datos, $es_iniciativa, $zona_horaria) {
        $errores = [];
        if (empty($datos['fecha_inicio']) || empty($datos['fecha_fin'])) {
            $errores[] = "Fechas obligatorias";
            return $errores;
        }

        try {
            $tz = new DateTimeZone($zona_horaria);
            $ini = new DateTime($datos['fecha_inicio'], $tz);
            $fin = new DateTime($datos['fecha_fin'], $tz);
            
            // Regla: Mínimo 15 minutos
            if (($fin->getTimestamp() - $ini->getTimestamp()) < 900) {
                $errores[] = "Duración mínima 15 minutos";
            }
            // Regla: Iniciativa max 9 horas
            if ($es_iniciativa && ($fin->getTimestamp() - $ini->getTimestamp()) > 32400) {
                $errores[] = "Iniciativa no puede exceder 9 horas";
            }

        } catch (Exception $e) {
            $errores[] = "Error en fechas o zona horaria ($zona_horaria)";
        }
        return $errores;
    }

    private function validarIniciativa($datos, $id_usuario, $conteo_hoy) {
        $errores = [];
        if (isset($datos['id_asignado']) && $datos['id_asignado'] != $id_usuario) {
            $errores[] = "Las iniciativas deben ser autoasignadas";
        }
        if ($conteo_hoy >= 10) $errores[] = "Límite de 10 iniciativas diario alcanzado";
        return $errores;
    }

    private function validarTareaAsignada($datos) {
        $errores = [];
        $es_bolsa = isset($datos['categoria_asignacion']) && $datos['categoria_asignacion'] !== 'ESPECIFICA';
        if (empty($datos['id_asignado']) && !$es_bolsa) $errores[] = "Debe asignar usuario o elegir bolsa";
        return $errores;
    }
}
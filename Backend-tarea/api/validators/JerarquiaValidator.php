<?php
namespace Api\Validators;

class JerarquiaValidator {

    /**
     * Valida si el usuario creador tiene rango suficiente para crear el rol objetivo.
     * * Regla V6:
     * - CEO crea a TODOS.
     * - GG crea a GERENTE y COLABORADOR.
     * - GERENTE y COLABORADOR no crean a nadie.
     */
    public function puedeCrearUsuario($rol_creador, $rol_nuevo) {
        $permisos = [
            'CEO' => ['GG', 'GERENTE', 'COLABORADOR'],
            'GG'  => ['GERENTE', 'COLABORADOR'],
            'GERENTE' => [],
            'COLABORADOR' => []
        ];

        return in_array($rol_nuevo, $permisos[$rol_creador] ?? []);
    }

    /**
     * Valida si un rol puede asignar tareas a otro rol.
     * Usado en TareaValidator indirectamente o validación de flujo.
     */
    public function puedeAsignarTarea($rol_jefe, $rol_subordinado) {
        // Mapa de poder: Mayor número = Mayor rango
        $niveles = [
            'CEO' => 4,
            'GG' => 3,
            'GERENTE' => 2,
            'COLABORADOR' => 1
        ];

        // Regla: Solo se asigna hacia abajo o a uno mismo (autoasignación se valida antes)
        // Se permite asignar a alguien del mismo nivel en el caso de CEO/GG si es operativo,
        // pero la regla general es jerárquica.
        if (!isset($niveles[$rol_jefe]) || !isset($niveles[$rol_subordinado])) {
            return false;
        }

        return $niveles[$rol_jefe] >= $niveles[$rol_subordinado];
    }
}
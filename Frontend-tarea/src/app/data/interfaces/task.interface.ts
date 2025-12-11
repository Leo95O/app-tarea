export type TaskPriority = 'BAJA' | 'MEDIA' | 'ALTA' | 'CRITICA';
export type TaskExecutionStatus = 'PROGRAMADA' | 'EN_PROGRESO' | 'COMPLETADA' | 'VENCIDA';
export type TaskLifeCycleStatus = 'ABIERTA' | 'VALIDADA' | 'FINALIZADA_VENCIDA' | 'INACTIVA';

export interface Subtask {
  id?: number;
  titulo: string;
  completada: boolean;
  creado_en?: string;
}

export interface Task {
  id: number;
  id_sucursal: number;
  id_creador: number;
  id_asignado: number | null; // Null si está en una bolsa
  
  titulo: string;
  descripcion: string;
  prioridad: TaskPriority;
  categoria_asignacion: 'ESPECIFICA' | 'BOLSA_COLABORADOR' | 'BOLSA_GERENTE' | 'BOLSA_AMBOS';
  
  fecha_inicio: string; 
  fecha_fin_actual: string; // Usamos la fecha final real (post extensiones)
  
  estado_ejecucion: TaskExecutionStatus;
  estado_ciclo_vida: TaskLifeCycleStatus;
  
  es_iniciativa: boolean;
  
  // Datos opcionales (JOINs del backend)
  subtareas?: Subtask[];
  creador_nombre?: string;
  asignado_nombre?: string;
  zona_horaria?: string;
}

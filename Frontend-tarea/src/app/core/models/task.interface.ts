export interface Task {
  id: number;
  id_sucursal: number;
  id_creador: number;
  id_asignado: number | null;
  titulo: string;
  descripcion: string;
  prioridad: 'BAJA' | 'MEDIA' | 'ALTA' | 'CRITICA';
  estado_ejecucion: 'PROGRAMADA' | 'EN_PROGRESO' | 'COMPLETADA' | 'VENCIDA';
  estado_validacion: 'SIN_VALIDAR' | 'POR_VALIDAR' | 'VALIDADA';
  fecha_inicio: string;
  fecha_fin_original: string;
  fecha_fin_actual: string;
  nombre_creador?: string;
  nombre_asignado?: string;
  es_iniciativa: number | boolean; // Importante
}

// Estructura exacta de la respuesta del Backend
export interface TaskResponse {
  tipo: number;
  mensajes: string[];
  data: {
    tareas: Task[]; // <--- AQUÍ ESTABA EL DETALLE
    total: number;
  }; 
}
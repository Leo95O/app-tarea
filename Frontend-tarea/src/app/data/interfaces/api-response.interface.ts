export interface ApiResponse<T = any> {
  tipo: 1 | 2 | 3;      // 1: Éxito, 2: Advertencia, 3: Error
  mensajes: string[];   // Siempre es un array de textos
  data: T;              // El contenido variable (Usuario, Tarea, etc.)
}
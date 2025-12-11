export type UserRole = 'CEO' | 'GG' | 'GERENTE' | 'COLABORADOR';

export interface User {
  id: number;
  id_sucursal: number | null;
  rol: UserRole;
  nombre_completo: string;
  email: string;
  activo: boolean;
}
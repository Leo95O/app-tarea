import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable, signal } from '@angular/core';
import { environment } from '../../../../environments/environment';
import { Task } from '../../../data/interfaces/task.interface'; // Ajusta la ruta si es necesario
import { map, tap } from 'rxjs';
import { ApiResponse } from '../../../data/interfaces/api-response.interface';

@Injectable({
  providedIn: 'root'
})
export class TaskService {
  private http = inject(HttpClient);
  private readonly API_URL = `${environment.apiUrl}/tareas/`;

  public tasks = signal<Task[]>([]);
  public isLoading = signal<boolean>(false);

  /**
   * Obtener tareas con FILTROS (Dashboard)
   * GET /?id_sucursal=1&estado=VENCIDA
   */
  getAllTasks(filters: { id_sucursal?: number, estado?: string, prioridad?: string } = {}) {
    this.isLoading.set(true);

    let params = new HttpParams();
    if (filters.id_sucursal) params = params.set('id_sucursal', filters.id_sucursal);
    if (filters.estado) params = params.set('estado', filters.estado);
    if (filters.prioridad) params = params.set('prioridad', filters.prioridad);

    return this.http.get<ApiResponse>(this.API_URL, { params }).pipe(
      tap(response => {
        if (response.tipo === 1) {
          this.tasks.set(response.data.tareas || response.data); // Ajuste según venga tu JSON
        } else {
          this.tasks.set([]);
        }
      }),
      map(res => res.data.tareas || res.data),
      tap(() => this.isLoading.set(false))
    );
  }

  /**
   * Obtener una tarea por ID
   * GET /:id
   */
  getTaskById(id: number) {
    return this.http.get<ApiResponse>(`${this.API_URL}${id}`).pipe(
      map(res => res.data)
    );
  }

  createTask(taskData: any) {
    this.isLoading.set(true);
    return this.http.post<ApiResponse>(this.API_URL, taskData).pipe(
      tap(() => {
        this.isLoading.set(false);
        this.tasks.set([]); 
      })
    );
  }

  updateTask(taskId: number, data: any) {
    this.isLoading.set(true);
    return this.http.put<ApiResponse>(`${this.API_URL}${taskId}`, data).pipe(
      tap(() => {
        this.isLoading.set(false);
        this.tasks.set([]);
      })
    );
  }

  deleteTask(taskId: number) {
    this.isLoading.set(true);
    return this.http.delete<ApiResponse>(`${this.API_URL}${taskId}`).pipe(
      tap(() => {
        this.isLoading.set(false);
        this.tasks.set([]);
      })
    );
  }

  // --- FLUJO V6 ---
  requestValidation(taskId: number) {
    this.isLoading.set(true);
    return this.http.post<ApiResponse>(`${this.API_URL}${taskId}/solicitar-validacion`, {}).pipe(
      tap(() => { this.isLoading.set(false); this.tasks.set([]); })
    );
  }

  validateTask(taskId: number) {
    this.isLoading.set(true);
    return this.http.post<ApiResponse>(`${this.API_URL}${taskId}/validar`, {}).pipe(
      tap(() => { this.isLoading.set(false); this.tasks.set([]); })
    );
  }

  rejectTask(taskId: number) {
    this.isLoading.set(true);
    return this.http.post<ApiResponse>(`${this.API_URL}${taskId}/rechazar`, {}).pipe(
      tap(() => { this.isLoading.set(false); this.tasks.set([]); })
    );
  }
}
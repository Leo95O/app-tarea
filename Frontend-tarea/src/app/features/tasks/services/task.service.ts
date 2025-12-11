import { HttpClient } from '@angular/common/http';
import { inject, Injectable, signal } from '@angular/core';
import { environment } from '../../../../environments/environment';
import { Task, TaskResponse } from '../../../core/models/task.interface';
import { map, tap } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class TaskService {
  private http = inject(HttpClient);
  private readonly API_URL = `${environment.apiUrl}/tareas/`;

  // Estado reactivo (Signals)
  public tasks = signal<Task[]>([]);
  public isLoading = signal<boolean>(false);

  getAllTasks() {
    this.isLoading.set(true);
    return this.http.get<TaskResponse>(this.API_URL).pipe(
      tap(response => {
        if (response.tipo === 1) {
          this.tasks.set(response.data.tareas);
        } else {
          this.tasks.set([]);
        }
      }),
      map(res => res.data.tareas),
      tap(() => this.isLoading.set(false))
    );
  }

  createTask(taskData: any) {
    this.isLoading.set(true);
    return this.http.post<any>(this.API_URL, taskData).pipe(
      tap(() => {
        this.isLoading.set(false);
        this.tasks.set([]); // Forzar recarga
      })
    );
  }

  // --- NUEVOS MÉTODOS FLUJO V6 (Reemplazan a completeTask) ---

  /**
   * Colaborador: Solicita revisión
   * POST /tareas/:id/solicitar-validacion
   */
  requestValidation(taskId: number) {
    this.isLoading.set(true);
    return this.http.post<any>(`${this.API_URL}${taskId}/solicitar-validacion`, {}).pipe(
      tap(() => {
        this.isLoading.set(false);
        this.tasks.set([]); 
      })
    );
  }

  /**
   * Jefe: Aprueba la tarea
   * POST /tareas/:id/validar
   */
  validateTask(taskId: number) {
    this.isLoading.set(true);
    return this.http.post<any>(`${this.API_URL}${taskId}/validar`, {}).pipe(
      tap(() => {
        this.isLoading.set(false);
        this.tasks.set([]);
      })
    );
  }

  /**
   * Jefe: Rechaza la tarea
   * POST /tareas/:id/rechazar
   */
  rejectTask(taskId: number) {
    this.isLoading.set(true);
    return this.http.post<any>(`${this.API_URL}${taskId}/rechazar`, {}).pipe(
      tap(() => {
        this.isLoading.set(false);
        this.tasks.set([]);
      })
    );
  }

  /**
   * Eliminar tarea
   * DELETE /tareas/{id}
   */
  deleteTask(taskId: number) {
    this.isLoading.set(true);
    return this.http.delete<any>(`${this.API_URL}${taskId}`).pipe(
      tap(() => {
        this.isLoading.set(false);
        this.tasks.set([]);
      })
    );
  }

  // Actualizar (Opcional por ahora)
  updateTask(taskId: number, data: any) {
    this.isLoading.set(true);
    return this.http.put<any>(`${this.API_URL}${taskId}`, data).pipe(
      tap(() => {
        this.isLoading.set(false);
        this.tasks.set([]);
      })
    );
  }
}
import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { environment } from '../../../environments/environment';
import { map, Observable } from 'rxjs';
import { ApiResponse } from '../../data/interfaces/api-response.interface';
import { User, UserBasic } from '../../data/interfaces/user.interface'; // Asegúrate de tener UserBasic en tu interface

@Injectable({
  providedIn: 'root'
})
export class UserService {
  private http = inject(HttpClient);
  private readonly API_URL = `${environment.apiUrl}/usuarios`;

  /**
   * Buscar usuarios (Autocomplete)
   * GET /autocomplete?q=...
   */
  searchUsers(term: string): Observable<UserBasic[]> {
    return this.http.get<ApiResponse>(`${this.API_URL}/autocomplete`, {
      params: { q: term }
    }).pipe(
      map(response => response.data || [])
    );
  }

  /**
   * Crear Usuario (Staff)
   * POST /
   */
  createUser(userData: any): Observable<any> {
    return this.http.post<ApiResponse>(this.API_URL, userData);
  }

  /**
   * Editar Usuario / Promover
   * PUT /:id
   */
  updateUser(id: number, userData: any): Observable<any> {
    return this.http.put<ApiResponse>(`${this.API_URL}/${id}`, userData);
  }

  /**
   * Desactivar Usuario
   * DELETE /:id
   */
  deactivateUser(id: number): Observable<any> {
    return this.http.delete<ApiResponse>(`${this.API_URL}/${id}`);
  }
}
import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { environment } from '../../../environments/environment';
import { map, Observable } from 'rxjs';
import { ApiResponse } from '../../data/interfaces/api-response.interface';

// Define una interfaz rápida si no la tienes
export interface Sucursal {
  id: number;
  nombre: string;
  direccion: string;
  activo: boolean;
}

@Injectable({
  providedIn: 'root'
})
export class SucursalService {
  private http = inject(HttpClient);
  private readonly API_URL = `${environment.apiUrl}/sucursales`;

  /**
   * Listar sucursales
   * GET /?solo_activas=true
   */
  getAll(soloActivas: boolean = true): Observable<Sucursal[]> {
    let params = new HttpParams();
    if (soloActivas) params = params.set('solo_activas', 'true');

    return this.http.get<ApiResponse>(this.API_URL, { params }).pipe(
      map(res => res.data || [])
    );
  }

  /**
   * Obtener detalle
   * GET /:id
   */
  getById(id: number): Observable<Sucursal> {
    return this.http.get<ApiResponse>(`${this.API_URL}/${id}`).pipe(
      map(res => res.data)
    );
  }

  /**
   * Crear Sucursal + GG (Atómico)
   * POST /
   */
  create(data: { sucursal: any, gerente_general: any }): Observable<any> {
    return this.http.post<ApiResponse>(this.API_URL, data);
  }

  /**
   * Editar
   * PUT /:id
   */
  update(id: number, data: any): Observable<any> {
    return this.http.put<ApiResponse>(`${this.API_URL}/${id}`, data);
  }

  /**
   * Eliminar/Cerrar
   * DELETE /:id
   */
  delete(id: number): Observable<any> {
    return this.http.delete<ApiResponse>(`${this.API_URL}/${id}`);
  }
}
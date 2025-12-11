import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { environment } from '../../../environments/environment';
import { map, Observable } from 'rxjs';

// Interfaz ligera solo para el selector
export interface UserBasic {
  id: number;
  nombre_completo: string;
  rol: string;
  id_sucursal: number;
}

@Injectable({
  providedIn: 'root'
})
export class UserService {
  private http = inject(HttpClient);
  private readonly API_URL = `${environment.apiUrl}/usuarios`;

  /**
   * Busca usuarios por nombre (para el Autocomplete)
   */
  searchUsers(term: string): Observable<UserBasic[]> {
    return this.http.get<any>(`${this.API_URL}/autocomplete`, {
      params: { q: term }
    }).pipe(
      map(response => response.data || [])
    );
  }
}
import { Injectable, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { map, tap } from 'rxjs/operators';
import { Observable } from 'rxjs';

// Importamos tus interfaces
import { ApiResponse } from '../../data/interfaces/api-response.interface';
import { User } from '../../data/interfaces/user.interface';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  // Inyección de dependencias moderna
  private http = inject(HttpClient);
  private router = inject(Router);

  // CONFIGURACIÓN: Ajusta esto si tu carpeta se llama distinto en htdocs
  private readonly API_URL = 'http://localhost/app-tarea/Backend-tarea/public/api/rest/auth/';
  // ESTADO REACTIVO (Signals)
  // Cualquiera en la app puede leer esto: authService.currentUser()
  currentUser = signal<User | null>(this.getUserFromStorage());
  
  // Computed signal implícito: si hay usuario, está logueado
  isLoggedIn = () => !!this.currentUser();

  constructor() { }

  /**
   * Petición de Login al PHP
   */
  login(credentials: { email: string; password: string }): Observable<boolean> {
    return this.http.post<ApiResponse>(this.API_URL, credentials).pipe(
      tap(response => {
        if (response.tipo === 1 && response.data.token) {
          // 1. Guardar Token y Usuario en LocalStorage
          this.saveToken(response.data.token);
          this.saveUser(response.data.usuario); // Asumiendo que PHP devuelve 'usuario' en data
          
          // 2. Actualizar la señal (toda la app se entera al instante)
          this.currentUser.set(response.data.usuario);
        }
      }),
      map(response => response.tipo === 1) // Retorna true si fue éxito
    );
  }

  /**
   * Cerrar sesión
   */
  logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    this.currentUser.set(null);
    this.router.navigate(['/auth/login']);
  }

  // --- MÉTODOS DE SOPORTE ---

  getToken(): string | null {
    return localStorage.getItem('token');
  }

  private saveToken(token: string) {
    localStorage.setItem('token', token);
  }

  private saveUser(user: User) {
    localStorage.setItem('user', JSON.stringify(user));
  }

  private getUserFromStorage(): User | null {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
  }
}
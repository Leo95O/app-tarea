import { Injectable, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { map, tap, catchError } from 'rxjs/operators';
import { Observable, of } from 'rxjs';
import { environment } from '../../../environments/environment'; 
import { ApiResponse } from '../../data/interfaces/api-response.interface';
import { User } from '../../data/interfaces/user.interface';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private http = inject(HttpClient);
  private router = inject(Router);

  // Usamos la URL base del environment para no repetir hardcode
  private readonly API_URL = `${environment.apiUrl}/auth`;

  currentUser = signal<User | null>(this.getUserFromStorage());
  isLoggedIn = () => !!this.currentUser();

  constructor() { }

  /**
   * Login (POST /)
   */
  login(credentials: { email: string; password: string }): Observable<boolean> {
    return this.http.post<ApiResponse>(`${this.API_URL}/`, credentials).pipe(
      tap(response => {
        if (response.tipo === 1 && response.data.token) {
          this.saveToken(response.data.token);
          this.saveUser(response.data.usuario);
          this.currentUser.set(response.data.usuario);
        }
      }),
      map(response => response.tipo === 1)
    );
  }

  /**
   * Verificar Token (GET /verificar)
   * Valida si el token sigue vivo y si el usuario sigue activo en BD.
   */
  verifyToken(): Observable<boolean> {
    const token = this.getToken();
    if (!token) return of(false);

    return this.http.get<ApiResponse>(`${this.API_URL}/verificar`).pipe(
      map(res => {
        if (res.tipo === 1) {
          // Actualizamos datos por si cambiaron rol o nombre
          this.saveUser(res.data); 
          this.currentUser.set(res.data);
          return true;
        }
        return false;
      }),
      catchError(() => {
        this.logout();
        return of(false);
      })
    );
  }

  logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    this.currentUser.set(null);
    this.router.navigate(['/auth/login']);
  }

  getToken(): string | null { return localStorage.getItem('token'); }
  private saveToken(token: string) { localStorage.setItem('token', token); }
  private saveUser(user: User) { localStorage.setItem('user', JSON.stringify(user)); }
  private getUserFromStorage(): User | null {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
  }
}
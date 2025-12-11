import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

export const authGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  // 1. Preguntar al servicio si hay sesión válida
  if (authService.isLoggedIn()) {
    return true; // ¡Pase usted!
  }

  // 2. Si no, patada al login
  router.navigate(['/auth/login']);
  return false;
};
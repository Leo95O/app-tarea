import { ApplicationConfig, provideBrowserGlobalErrorListeners, provideZoneChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideHttpClient, withInterceptors, withFetch } from '@angular/common/http'; // 1. Importar HTTP
import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';   // 2. Importar Animaciones (Para Material)

import { routes } from './app.routes';
import { authInterceptor } from './core/interceptors/auth.interceptor'; // 3. Importar tu interceptor

export const appConfig: ApplicationConfig = {
  providers: [
    // --- Configuración Base de Angular (La que ya tenías) ---
    provideBrowserGlobalErrorListeners(),
    provideZoneChangeDetection({ eventCoalescing: true }),
    provideRouter(routes),

    // --- Configuración de Red (Vital para conectar con PHP) ---
    provideHttpClient(
      withFetch(), // Usa la API Fetch moderna (mejor rendimiento que XMLHttpRequest)
      withInterceptors([authInterceptor]) // Inyecta el Token JWT automáticamente
    ),

    // --- Configuración Visual (Preparando el terreno para Angular Material) ---
    provideAnimationsAsync() 
  ]
};
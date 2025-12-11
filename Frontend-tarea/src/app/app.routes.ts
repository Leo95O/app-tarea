import { Routes } from '@angular/router';
import { LoginComponent } from './features/auth/login/login.component';
import { MainLayoutComponent } from './shared/layout/main-layout/main-layout.component'; // <--- IMPORTANTE: El nuevo Layout
import { TaskListComponent } from './features/tasks/pages/task-list/task-list.component';
import { authGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  // 1. RUTA PÚBLICA: El Login va suelto (Pantalla completa, sin menú lateral)
  { path: 'auth/login', component: LoginComponent },

  // 2. RUTAS PROTEGIDAS: Todo lo que vive dentro del sistema
  {
    path: '', 
    component: MainLayoutComponent, // <--- Aquí definimos el "Marco" o "Esqueleto"
    canActivate: [authGuard],       // <--- Protegemos el marco entero. Si no hay login, nadie entra aquí.
    children: [
      // Redirección por defecto interna: Si entras a 'localhost:4200/', ve a 'tareas'
      { path: '', redirectTo: 'tareas', pathMatch: 'full' },

      // La página de tareas se cargará DENTRO del <router-outlet> del MainLayout
      { path: 'tareas', component: TaskListComponent },
      
      // Aquí agregaremos luego:
      // { path: 'analytics', component: AnalyticsComponent },
      // { path: 'usuarios', component: UserListComponent },
    ]
  },

  // 3. WILDCARD: Cualquier ruta desconocida redirige al inicio
  { path: '**', redirectTo: 'tareas' }
];
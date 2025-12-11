import { Component, inject, signal, ViewChild } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { CommonModule } from '@angular/common';
import { BreakpointObserver, Breakpoints } from '@angular/cdk/layout'; // <--- IMPORTANTE

import { MatSidenav, MatSidenavModule } from '@angular/material/sidenav'; // Importar MatSidenav
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatIconModule } from '@angular/material/icon';
import { MatListModule } from '@angular/material/list';
import { MatButtonModule } from '@angular/material/button';
import { MatMenuModule } from '@angular/material/menu';

import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [
    CommonModule,
    RouterOutlet, 
    RouterLink, 
    RouterLinkActive,
    MatSidenavModule,
    MatToolbarModule,
    MatIconModule,
    MatListModule,
    MatButtonModule,
    MatMenuModule
  ],
  templateUrl: './main-layout.component.html',
  styleUrl: './main-layout.component.scss'
})
export class MainLayoutComponent {
  private breakpointObserver = inject(BreakpointObserver);
  authService = inject(AuthService);
  
  @ViewChild('sidenav') sidenav!: MatSidenav;

  // Señales para control de UI
  isSidebarOpen = signal(true);
  isMobile = signal(false);

  constructor() {
    // Detectar cambios de pantalla (Móvil vs Escritorio)
    this.breakpointObserver.observe([Breakpoints.Handset])
      .subscribe(result => {
        this.isMobile.set(result.matches);
        if (result.matches) {
          this.isSidebarOpen.set(false); // Cerrar menú en móvil al iniciar
        } else {
          this.isSidebarOpen.set(true); // Abrir en escritorio
        }
      });
  }

  toggleSidebar() {
    this.isSidebarOpen.update(val => !val);
  }

  // Cerrar menú automáticamente al hacer clic en una opción (solo en móvil)
  closeOnMobile() {
    if (this.isMobile()) {
      this.isSidebarOpen.set(false);
    }
  }

  logout() {
    this.authService.logout();
  }
}
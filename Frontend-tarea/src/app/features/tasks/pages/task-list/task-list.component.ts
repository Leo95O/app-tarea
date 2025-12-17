import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms'; 
import { MatTableModule } from '@angular/material/table';
import { MatPaginatorModule } from '@angular/material/paginator';
import { MatSortModule } from '@angular/material/sort';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatMenuModule } from '@angular/material/menu';
import { MatDividerModule } from '@angular/material/divider';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatFormFieldModule } from '@angular/material/form-field'; 
import { MatSelectModule } from '@angular/material/select'; 
import { MatInputModule } from '@angular/material/input';
import { TaskService } from '../../services/task.service';
import { AuthService } from '../../../../core/services/auth.service';
import { SucursalService } from '../../../../core/services/sucursal.service'; 
import { TaskCreateDialogComponent } from '../../components/task-create-dialog/task-create-dialog.component';
import { ConfirmationDialogComponent } from '../../../../shared/ui/confirmation-dialog/confirmation-dialog.component';
import { Task } from '../../../../core/models/task.interface';

@Component({
  selector: 'app-task-list',
  standalone: true,
  imports: [
    CommonModule,
    DatePipe,
    FormsModule, 
    MatTableModule,
    MatPaginatorModule,
    MatSortModule,
    MatButtonModule,
    MatIconModule,
    MatChipsModule,
    MatTooltipModule,
    MatMenuModule,
    MatDividerModule,
    MatDialogModule,
    MatSnackBarModule,
    MatFormFieldModule,
    MatSelectModule,
    MatInputModule
  ],
  templateUrl: './task-list.component.html',
  styleUrls: ['./task-list.component.scss']
})
export class TaskListComponent implements OnInit {
  private taskService = inject(TaskService);
  private sucursalService = inject(SucursalService); 
  private dialog = inject(MatDialog);
  private snackBar = inject(MatSnackBar);
  public authService = inject(AuthService);

  displayedColumns: string[] = ['prioridad', 'estado', 'titulo', 'asignado', 'vencimiento', 'acciones'];
  
  tasks = this.taskService.tasks;
  isLoading = this.taskService.isLoading;

  // --- VARIABLES PARA FILTROS ---
  listaSucursales = signal<any[]>([]); // Para el combo del CEO
  filtros = {
    id_sucursal: null as number | null,
    estado: '',
    prioridad: ''
  };

  mostrarFiltros = false; // Toggle para mostrar/ocultar barra

  ngOnInit() {
    this.verificarPermisosYCaragar();
  }

  verificarPermisosYCaragar() {
    // Si es CEO (no tiene sucursal fija), cargamos la lista de sucursales
    if (this.authService.currentUser()?.id_sucursal === null) {
      this.cargarSucursales();
    }
    this.cargarTareas();
  }

  cargarSucursales() {
    this.sucursalService.getAll(true).subscribe({
      next: (data) => this.listaSucursales.set(data),
      error: () => this.showSnack('Error cargando sucursales', 'error')
    });
  }

  cargarTareas() {
    // Convertimos nulls a undefined para limpiar la URL
    const filtrosApi = {
      id_sucursal: this.filtros.id_sucursal || undefined,
      estado: this.filtros.estado || undefined,
      prioridad: this.filtros.prioridad || undefined
    };

    this.taskService.getAllTasks(filtrosApi).subscribe({
      error: (err) => console.error('Error cargando tareas:', err)
    });
  }

  limpiarFiltros() {
    this.filtros = {
      id_sucursal: null,
      estado: '',
      prioridad: ''
    };
    this.cargarTareas();
  }

  toggleFiltros() {
    this.mostrarFiltros = !this.mostrarFiltros;
  }

  openCreateDialog() {
    const dialogRef = this.dialog.open(TaskCreateDialogComponent, {
      width: '900px',
      maxWidth: '95vw',
      disableClose: true,
      autoFocus: false,
      panelClass: 'custom-dialog-container'
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result === true) {
        this.cargarTareas();
      }
    });
  }

  onDelete(task: Task) {
    const dialogRef = this.dialog.open(ConfirmationDialogComponent, {
      width: '400px',
      data: {
        title: '¿Eliminar Tarea?',
        message: `Estás a punto de eliminar "${task.titulo}". Esta acción no se puede deshacer.`
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.taskService.deleteTask(task.id).subscribe({
          next: () => {
            this.showSnack('Tarea eliminada correctamente');
            this.cargarTareas();
          },
          error: (err) => this.showSnack('Error al eliminar: ' + (err.error?.mensajes?.[0] || 'Error desconocido'), 'error')
        });
      }
    });
  }

  // --- ACCIONES DEL FLUJO V6 ---

  onRequestValidation(task: Task) {
    this.taskService.requestValidation(task.id).subscribe({
      next: () => {
        this.showSnack('¡Solicitud enviada a revisión! 📤');
        this.cargarTareas();
      },
      error: (err) => this.showSnack('Error: ' + (err.error?.mensajes?.[0] || 'No se pudo enviar'), 'error')
    });
  }

  onValidate(task: Task) {
    this.taskService.validateTask(task.id).subscribe({
      next: () => {
        this.showSnack('Tarea aprobada y cerrada ✅');
        this.cargarTareas();
      },
      error: (err) => this.showSnack('Error al validar', 'error')
    });
  }

  onReject(task: Task) {
    this.taskService.rejectTask(task.id).subscribe({
      next: () => {
        this.showSnack('Tarea devuelta al colaborador ↩️');
        this.cargarTareas();
      },
      error: (err) => this.showSnack('Error al rechazar', 'error')
    });
  }

  private showSnack(msg: string, type: 'success' | 'error' = 'success') {
    this.snackBar.open(msg, 'Cerrar', {
      duration: 3000,
      panelClass: type === 'error' ? ['bg-red-600', 'text-white'] : ['bg-slate-800', 'text-white']
    });
  }

  getPriorityColor(priority: string): string {
    switch(priority) {
      case 'CRITICA': return 'bg-red-100 text-red-800 border-red-200';
      case 'ALTA': return 'bg-orange-100 text-orange-800 border-orange-200';
      case 'MEDIA': return 'bg-blue-100 text-blue-800 border-blue-200';
      default: return 'bg-slate-100 text-slate-800 border-slate-200';
    }
  }
}
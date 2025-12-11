import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatIconModule } from '@angular/material/icon';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';

import { AuthService } from '../../../../core/services/auth.service';
import { TaskService } from '../../services/task.service';
import { UserService, UserBasic } from '../../../../core/services/user.service';
import { debounceTime, distinctUntilChanged, switchMap, startWith } from 'rxjs/operators';
import { Observable, of } from 'rxjs';

@Component({
  selector: 'app-task-create-dialog',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatInputModule,
    MatSelectModule,
    MatIconModule,
    MatAutocompleteModule,
    MatSlideToggleModule,
    MatDatepickerModule,
    MatNativeDateModule
  ],
  templateUrl: './task-create-dialog.component.html',
  styleUrls: ['./task-create-dialog.component.scss']
})
export class TaskCreateDialogComponent implements OnInit {
  private fb = inject(FormBuilder);
  private dialogRef = inject(MatDialogRef<TaskCreateDialogComponent>);
  private authService = inject(AuthService);
  private taskService = inject(TaskService);
  private userService = inject(UserService);

  form!: FormGroup;
  currentUser = this.authService.currentUser();
  
  // Control de estado
  isSubmitting = signal(false);
  isInitiative = signal(false);
  
  // Autocomplete
  filteredUsers$: Observable<UserBasic[]> = of([]);
  selectedUser: UserBasic | null = null;

  ngOnInit() {
    this.initForm();
    this.setupUserSearch();
    this.checkPermissions();
  }

  private initForm() {
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const format = (d: Date) => d.toISOString().slice(0, 16);

    this.form = this.fb.group({
      titulo: ['', [Validators.required, Validators.minLength(3)]],
      descripcion: [''],
      prioridad: ['MEDIA', Validators.required],
      fecha_inicio: [format(now), Validators.required],
      fecha_fin: [format(tomorrow), Validators.required],
      es_iniciativa_toggle: [false],
      asignado_texto: [''],
      id_asignado: [null]
    });

    this.form.get('es_iniciativa_toggle')?.valueChanges.subscribe(val => {
      this.isInitiative.set(val);
      if (val) {
        this.form.get('asignado_texto')?.disable();
        this.form.get('id_asignado')?.setValue(this.currentUser?.id);
      } else {
        this.form.get('asignado_texto')?.enable();
        this.form.get('id_asignado')?.setValue(null);
      }
    });
  }

  private checkPermissions() {
    if (this.currentUser?.rol === 'COLABORADOR') {
      this.form.get('es_iniciativa_toggle')?.setValue(true);
      this.form.get('es_iniciativa_toggle')?.disable();
    }
  }

  private setupUserSearch() {
    this.filteredUsers$ = this.form.get('asignado_texto')!.valueChanges.pipe(
      startWith(''), // inicia búsqueda vacía al cargar
      debounceTime(300),
      distinctUntilChanged(),
      switchMap(value => {
        const term = typeof value === 'string' ? value : '';
        return this.userService.searchUsers(term);
      })
    );
  }

  displayFn(user: UserBasic): string {
    return user && user.nombre_completo ? user.nombre_completo : '';
  }

  onUserSelected(event: any) {
    const user = event.option.value as UserBasic;
    this.selectedUser = user;
    this.form.patchValue({ 
      id_asignado: user.id,
      asignado_texto: user.nombre_completo
    });
  }

  onSubmit() {
    if (this.form.invalid) return;

    this.isSubmitting.set(true);

    const formData = this.form.getRawValue();
    const payload = {
      titulo: formData.titulo,
      descripcion: formData.descripcion,
      prioridad: formData.prioridad,
      fecha_inicio: formData.fecha_inicio,
      fecha_fin: formData.fecha_fin,
      es_iniciativa: this.isInitiative(),
      id_asignado: this.isInitiative() ? this.currentUser?.id : this.selectedUser?.id
    };

    this.taskService.createTask(payload).subscribe({
      next: () => {
        this.dialogRef.close(true);
      },
      error: (err) => {
        console.error(err);
        this.isSubmitting.set(false);
      }
    });
  }

  onCancel() {
    this.dialogRef.close(false);
  }
}

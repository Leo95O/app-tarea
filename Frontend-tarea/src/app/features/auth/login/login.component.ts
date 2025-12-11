import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { CommonModule } from '@angular/common'; // Necesario para algunas directivas básicas

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [ReactiveFormsModule, CommonModule], // Importar ReactiveFormsModule es vital
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss'
})
export class LoginComponent {
  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private router = inject(Router);

  // Señal para manejar el estado de carga y errores
  isLoading = signal(false);
  errorMessage = signal<string | null>(null);

  // Formulario Tipado Estricto
  loginForm = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(6)]]
  });

  onSubmit() {
    if (this.loginForm.invalid) {
      this.loginForm.markAllAsTouched();
      return;
    }

    this.isLoading.set(true);
    this.errorMessage.set(null);

    const { email, password } = this.loginForm.getRawValue();

    // Llamada al Backend PHP
    this.authService.login({ email, password }).subscribe({
      next: (exito) => {
        this.isLoading.set(false);
        if (exito) {
          // Si el login es correcto, redirigimos (por ahora al home o tareas)
          this.router.navigate(['/tareas']); 
        }
      },
      error: (err) => {
        this.isLoading.set(false);
        console.error('Error en login:', err);
        // Manejo básico de error (El interceptor ya maneja errores globales, 
        // pero aquí capturamos credenciales inválidas específicas)
        this.errorMessage.set('Credenciales incorrectas o error de conexión.');
      }
    });
  }
}
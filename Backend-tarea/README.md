# 🍴 API REST - Gestor de Tareas y Rendimiento para Restaurantes

Sistema Multi-Tenant para gestión jerárquica de tareas con analítica de rendimiento.

---

## 📋 Tabla de Contenidos

- [Características](#características)
- [Requisitos del Sistema](#requisitos-del-sistema)
- [Instalación](#instalación)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Configuración](#configuración)
- [Documentación de la API](#documentación-de-la-api)
- [Arquitectura](#arquitectura)
- [Testing](#testing)

---

## ✨ Características

### 🏢 Multi-Tenant (Sucursales)
- Creación atómica de Sucursal + Gerente General
- Zona horaria independiente por sucursal
- Soft delete (estado INACTIVA)

### 👥 Jerarquía de 4 Niveles
- **CEO**: Alcance global, autovalidación
- **Gerente General (GG)**: Alcance sucursal
- **Gerente**: Alcance de equipo
- **Colaborador**: Alcance personal

### 📋 Gestión de Tareas
- **Tipo A (Asignadas)**: Creadas por superiores, sin límite de duración
- **Tipo B (Iniciativas)**: Creadas por el usuario, máx. 10/día, máx. 9 horas

### 🔄 Workflows Avanzados
- Validación/Rechazo con subtareas correctivas
- Extensiones de tiempo (máx. 3, con justificación)
- Estados automáticos por zona horaria

### 📊 Analítica Incorporada
- Eficiencia Pura (validadas sin rechazos ni extensiones)
- Calidad (rechazos antes de validación)
- Puntualidad (extensiones justificadas vs injustificadas)
- Proactividad (iniciativas exitosas vs abandonadas)

---

## 🛠️ Requisitos del Sistema

- **PHP**: >= 7.1.3
- **MySQL**: >= 5.7
- **Composer**: >= 2.0
- **Extensiones PHP**: PDO, pdo_mysql, json, mbstring

---

## 🚀 Instalación

### 1. Clonar el Repositorio
```bash
cd c:/xampp/htdocs/
git clone <URL_DEL_REPO> app-tarea
cd app-tarea/Backend-tarea
```

### 2. Instalar Dependencias
```bash
composer install
```

### 3. Configurar la Base de Datos

**Crear la base de datos:**
```bash
mysql -u root -p < database.sql
```

**Configurar credenciales:**
```bash
cp config.example.php config.php
# Editar config.php con tus credenciales reales
```

### 4. Verificar Instalación
```bash
php test-conexion.php
```

Deberías ver:
```
========================================
  ✓ TODOS LOS TESTS PASARON
  ✓ FASE 1 COMPLETA
========================================
```

---

## 📁 Estructura del Proyecto

```
Backend-tarea/
├── api/
│   ├── core/                    # Infraestructura base
│   │   ├── DB.php              # Singleton de conexión PDO
│   │   ├── Response.php        # Helper de respuestas JSON
│   │   └── AppHelper.php       # CORS + Error Handler
│   ├── controllers/             # Orquestadores de flujo (Fase 2+)
│   ├── entities/                # DTOs (Objetos de Datos)
│   ├── repositories/            # Acceso a datos SQL
│   ├── validators/              # Reglas de negocio
│   ├── logs/                    # Logs de errores
│   └── uploads/adjuntos/        # Archivos subidos
├── public/api/rest/             # Entry Points públicos
├── vendor/                      # Dependencias de Composer
├── .gitignore
├── composer.json
├── config.php                   # ⚠️ No subir a Git
├── config.example.php           # Plantilla de configuración
├── database.sql                 # Schema de BD
└── README.md                    # Este archivo
```

---

## ⚙️ Configuración

### Variables de Entorno (`config.php`)

```php
// Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'tareas_restaurant');
define('DB_USER', 'root');
define('DB_PASS', '');

// Seguridad
define('JWT_SECRET', 'clave-secreta-aleatoria-aqui');
define('JWT_DURACION_SEGUNDOS', 32400); // 9 horas

// Sistema
define('TIMEZONE_DEFECTO', 'America/Lima');
define('MODO_DEBUG', true); // false en producción
define('CORS_ORIGIN', '*'); // Dominio de Angular en producción
```

---

## 📡 Documentación de la API

### Contrato de Respuesta JSON (V4)

Todas las respuestas siguen esta estructura:

```json
{
  "tipo": 1,
  "mensajes": ["Operación exitosa"],
  "data": {}
}
```

**Tipos de Respuesta:**
- **Tipo 1 (Éxito)**: Operación completada. Procesar `data`.
- **Tipo 2 (Advertencia)**: Regla de negocio violada. Mostrar `mensajes`.
- **Tipo 3 (Error)**: Fallo interno. Mostrar error genérico.

---

### 🔐 Autenticación (Fase 2) ✅ IMPLEMENTADO

**Base URL**: `http://localhost/backend-tarea/public/api/rest/auth/`

---

#### `POST /login` - Iniciar Sesión

Autentica un usuario y retorna un token JWT válido por 9 horas.

**Seguridad Incorporada:**
- ✅ Control de intentos fallidos (3 intentos = bloqueo de 2 minutos)
- ✅ Auditoría completa de eventos
- ✅ Protección contra timing attacks

**URL Completa:** `http://localhost/backend-tarea/public/api/rest/auth/login`

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "ceo@restaurant.com",
  "password": "Admin123!"
}
```

**Response (Éxito - 200 OK):**
```json
{
  "tipo": 1,
  "mensajes": ["Inicio de sesión exitoso"],
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "usuario": {
      "id": 1,
      "id_sucursal": null,
      "id_gerente_directo": null,
      "rol": "CEO",
      "nombre_completo": "Administrador General",
      "email": "ceo@restaurant.com"
    },
    "expira_en_segundos": 32400
  }
}
```

**Response (Error - Credenciales Inválidas - 401 Unauthorized):**
```json
{
  "tipo": 2,
  "mensajes": ["Credenciales incorrectas"],
  "data": {}
}
```

**Response (Error - Cuenta Bloqueada - 429 Too Many Requests):**
```json
{
  "tipo": 2,
  "mensajes": ["Cuenta temporalmente bloqueada por múltiples intentos fallidos. Intente nuevamente en 2 minuto(s)."],
  "data": {}
}
```

**Response (Error - Validación - 400 Bad Request):**
```json
{
  "tipo": 2,
  "mensajes": [
    "El email es obligatorio",
    "La contraseña debe tener al menos 6 caracteres"
  ],
  "data": {}
}
```

---

#### `GET /verificar` - Verificar Token JWT

Valida si un token JWT es válido y no ha expirado.

**URL Completa:** `http://localhost/backend-tarea/public/api/rest/auth/verificar`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response (Éxito - 200 OK):**
```json
{
  "tipo": 1,
  "mensajes": ["Token válido"],
  "data": {
    "usuario": {
      "id": 1,
      "email": "ceo@restaurant.com",
      "rol": "CEO",
      "id_sucursal": null,
      "nombre_completo": "Administrador General"
    },
    "token_valido": true
  }
}
```

**Response (Error - Token Inválido - 401 Unauthorized):**
```json
{
  "tipo": 2,
  "mensajes": ["Token inválido o expirado"],
  "data": {}
}
```

---

#### 📱 Ejemplo de Integración con Angular 20

**Servicio de Autenticación (auth.service.ts):**

```typescript
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

interface ApiResponse {
  tipo: number;
  mensajes: string[];
  data: any;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = 'http://localhost/backend-tarea/public/api/rest/auth';
  private tokenKey = 'auth_token';

  constructor(private http: HttpClient) {}

  // Login
  login(email: string, password: string): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(`${this.apiUrl}/login`, {
      email,
      password
    });
  }

  // Guardar token en localStorage
  guardarToken(token: string): void {
    localStorage.setItem(this.tokenKey, token);
  }

  // Obtener token
  obtenerToken(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  // Verificar token
  verificarToken(): Observable<ApiResponse> {
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${this.obtenerToken()}`
    });
    return this.http.get<ApiResponse>(`${this.apiUrl}/verificar`, { headers });
  }

  // Cerrar sesión
  logout(): void {
    localStorage.removeItem(this.tokenKey);
  }

  // Verificar si está autenticado
  estaAutenticado(): boolean {
    return this.obtenerToken() !== null;
  }
}
```

**Interceptor HTTP (auth.interceptor.ts):**

```typescript
import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler, HttpEvent } from '@angular/common/http';
import { Observable } from 'rxjs';
import { AuthService } from './auth.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  constructor(private authService: AuthService) {}

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    const token = this.authService.obtenerToken();

    if (token) {
      const cloned = req.clone({
        headers: req.headers.set('Authorization', `Bearer ${token}`)
      });
      return next.handle(cloned);
    }

    return next.handle(req);
  }
}
```

**Componente de Login (login.component.ts):**

```typescript
import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from './auth.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html'
})
export class LoginComponent {
  email = '';
  password = '';
  errores: string[] = [];
  cargando = false;

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  onSubmit(): void {
    this.cargando = true;
    this.errores = [];

    this.authService.login(this.email, this.password).subscribe({
      next: (response) => {
        if (response.tipo === 1) {
          // Éxito
          this.authService.guardarToken(response.data.token);
          this.router.navigate(['/dashboard']);
        } else if (response.tipo === 2) {
          // Advertencia (credenciales incorrectas, cuenta bloqueada, etc.)
          this.errores = response.mensajes;
        }
        this.cargando = false;
      },
      error: (error) => {
        // Error 500 del servidor
        this.errores = ['Error de conexión con el servidor'];
        this.cargando = false;
      }
    });
  }
}
```

---

### 🏢 Sucursales (Fase 3) ✅ IMPLEMENTADO

**Base URL**: `http://localhost/app-tarea/Backend-tarea/public/api/rest/sucursales/`

**Permisos**: Todos los endpoints requieren JWT + Rol CEO

**Características:**
- ✅ Creación atómica (Sucursal + Gerente General en una transacción)
- ✅ Soft Delete (preserva integridad histórica)
- ✅ Zona horaria independiente por sucursal
- ✅ Control de email duplicado

---

#### `POST /` - Crear Sucursal (Atómica)

Crea una sucursal y su Gerente General en una única transacción SQL.
Si falla alguna operación, se revierte todo.

**URL Completa:** `http://localhost/app-tarea/Backend-tarea/public/api/rest/sucursales/`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json
```

**Request Body:**
```json
{
  "sucursal": {
    "nombre": "Sucursal Centro Lima",
    "direccion": "Av. Arequipa 1234",
    "telefono": "+51 987654321",
    "zona_horaria": "America/Lima"
  },
  "gerente_general": {
    "nombre_completo": "Juan Pérez García",
    "email": "juan.perez@restaurant.com",
    "password": "Gerente123!"
  }
}
```

**Response (201 Created):**
```json
{
  "tipo": 1,
  "mensajes": ["Sucursal creada exitosamente con su Gerente General"],
  "data": {
    "id_sucursal": 1,
    "id_gerente_general": 3,
    "sucursal": "Sucursal Centro Lima",
    "gerente_general": "Juan Pérez García"
  }
}
```

**Response (Error - Email Duplicado - 400 Bad Request):**
```json
{
  "tipo": 2,
  "mensajes": ["El email del Gerente General ya está registrado en el sistema"],
  "data": {}
}
```

---

#### `GET /` - Listar Sucursales

Lista todas las sucursales con información agregada.

**URL Completa:** `http://localhost/app-tarea/Backend-tarea/public/api/rest/sucursales/`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Query Params (opcionales):**
- `solo_activas=true` - Filtra solo sucursales activas

**Response (200 OK):**
```json
{
  "tipo": 1,
  "mensajes": ["Sucursales obtenidas exitosamente"],
  "data": {
    "sucursales": [
      {
        "id": 1,
        "nombre": "Sucursal Centro Lima",
        "direccion": "Av. Arequipa 1234",
        "telefono": "+51 987654321",
        "zona_horaria": "America/Lima",
        "estado": "ACTIVA",
        "total_usuarios": 5,
        "gerente_general": "Juan Pérez García (juan.perez@restaurant.com)",
        "creado_en": "2025-12-10 14:30:00"
      }
    ],
    "total": 1
  }
}
```

---

#### `GET /:id` - Obtener Sucursal por ID

**URL Completa:** `http://localhost/app-tarea/Backend-tarea/public/api/rest/sucursales/1`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Response (200 OK):**
```json
{
  "tipo": 1,
  "mensajes": ["Sucursal obtenida exitosamente"],
  "data": {
    "sucursal": {
      "id": 1,
      "nombre": "Sucursal Centro Lima",
      "direccion": "Av. Arequipa 1234",
      "telefono": "+51 987654321",
      "zona_horaria": "America/Lima",
      "estado": "ACTIVA",
      "creado_en": "2025-12-10 14:30:00"
    }
  }
}
```

---

#### `PUT /:id` - Actualizar Sucursal

**URL Completa:** `http://localhost/app-tarea/Backend-tarea/public/api/rest/sucursales/1`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json
```

**Request Body (Campos opcionales):**
```json
{
  "nombre": "Sucursal Centro Lima - Renovada",
  "direccion": "Av. Arequipa 1500",
  "telefono": "+51 999888777",
  "zona_horaria": "America/Bogota"
}
```

**Response (200 OK):**
```json
{
  "tipo": 1,
  "mensajes": ["Sucursal actualizada exitosamente"],
  "data": { "id": 1 }
}
```

---

#### `DELETE /:id` - Desactivar Sucursal (Soft Delete)

Cambia el estado a INACTIVA preservando datos históricos.

**URL Completa:** `http://localhost/app-tarea/Backend-tarea/public/api/rest/sucursales/1`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Response (200 OK):**
```json
{
  "tipo": 1,
  "mensajes": ["Sucursal desactivada exitosamente. Los datos históricos se han preservado."],
  "data": { "id": 1 }
}
```

---

#### `POST /:id/reactivar` - Reactivar Sucursal

**URL Completa:** `http://localhost/app-tarea/Backend-tarea/public/api/rest/sucursales/1/reactivar`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Response (200 OK):**
```json
{
  "tipo": 1,
  "mensajes": ["Sucursal reactivada exitosamente"],
  "data": { "id": 1 }
}
```

---

#### 📱 Ejemplo de Integración con Angular 20

**Servicio de Sucursales (sucursal.service.ts):**

```typescript
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

interface ApiResponse {
  tipo: number;
  mensajes: string[];
  data: any;
}

@Injectable({
  providedIn: 'root'
})
export class SucursalService {
  private apiUrl = 'http://localhost/app-tarea/Backend-tarea/public/api/rest/sucursales';

  constructor(private http: HttpClient) {}

  // Crear sucursal con GG
  crearAtomica(sucursal: any, gerenteGeneral: any): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(this.apiUrl, {
      sucursal,
      gerente_general: gerenteGeneral
    });
  }

  // Listar sucursales
  listar(soloActivas = false): Observable<ApiResponse> {
    const params = soloActivas ? { solo_activas: 'true' } : {};
    return this.http.get<ApiResponse>(this.apiUrl, { params });
  }

  // Obtener por ID
  obtenerPorId(id: number): Observable<ApiResponse> {
    return this.http.get<ApiResponse>(`${this.apiUrl}/${id}`);
  }

  // Actualizar
  actualizar(id: number, datos: any): Observable<ApiResponse> {
    return this.http.put<ApiResponse>(`${this.apiUrl}/${id}`, datos);
  }

  // Desactivar
  desactivar(id: number): Observable<ApiResponse> {
    return this.http.delete<ApiResponse>(`${this.apiUrl}/${id}`);
  }

  // Reactivar
  reactivar(id: number): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(`${this.apiUrl}/${id}/reactivar`, {});
  }
}
```

**Componente de Creación (crear-sucursal.component.ts):**

```typescript
import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { SucursalService } from './sucursal.service';

@Component({
  selector: 'app-crear-sucursal',
  templateUrl: './crear-sucursal.component.html'
})
export class CrearSucursalComponent {
  sucursal = {
    nombre: '',
    direccion: '',
    telefono: '',
    zona_horaria: 'America/Lima'
  };

  gerenteGeneral = {
    nombre_completo: '',
    email: '',
    password: ''
  };

  errores: string[] = [];
  cargando = false;

  zonasHorarias = [
    { value: 'America/Lima', label: 'Lima (UTC-5)' },
    { value: 'America/Mexico_City', label: 'Ciudad de México (UTC-6)' },
    { value: 'America/Bogota', label: 'Bogotá (UTC-5)' },
    { value: 'America/Argentina/Buenos_Aires', label: 'Buenos Aires (UTC-3)' }
  ];

  constructor(
    private sucursalService: SucursalService,
    private router: Router
  ) {}

  onSubmit(): void {
    this.cargando = true;
    this.errores = [];

    this.sucursalService.crearAtomica(this.sucursal, this.gerenteGeneral).subscribe({
      next: (response) => {
        if (response.tipo === 1) {
          // Éxito
          this.router.navigate(['/sucursales']);
        } else if (response.tipo === 2) {
          // Advertencia
          this.errores = response.mensajes;
        }
        this.cargando = false;
      },
      error: (error) => {
        this.errores = ['Error al crear la sucursal'];
        this.cargando = false;
      }
    });
  }
}
```

---

### 👥 Usuarios (Fase 4)

#### `GET /api/rest/usuarios` (Filtrado por Jerarquía)

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Response (Éxito):**
```json
{
  "tipo": 1,
  "mensajes": ["Usuarios obtenidos exitosamente"],
  "data": {
    "usuarios": [
      {
        "id": 2,
        "nombre_completo": "Juan Pérez García",
        "email": "juan.perez@restaurant.com",
        "rol": "GG",
        "sucursal": "Sucursal Centro Lima",
        "activo": true
      }
    ]
  }
}
```

---

### 📋 Tareas (Fase 5) ✅ IMPLEMENTADO

**Base URL**: `http://localhost/app-tarea/Backend-tarea/public/api/rest/tareas/`

**Permisos**: Todos los endpoints requieren JWT (visibilidad filtrada por Matriz V6)

**Características:**
- ✅ Matriz de Visibilidad V6 (CEO → GG → GERENTE → COLABORADOR)
- ✅ Tipo A (Asignadas): Sin límite de duración, mínimo 15 minutos
- ✅ Tipo B (Iniciativas): Máx. 10/día, máx. 9 horas, autoasignación forzosa
- ✅ Estados automáticos por zona horaria (PROGRAMADA → EN_PROGRESO → VENCIDA/COMPLETADA)
- ✅ Subtareas con validación de completitud
- ✅ Soft Delete para iniciativas, INACTIVA para asignadas

---

#### `POST /` - Crear Tarea

Crea una tarea de tipo A (asignada) o tipo B (iniciativa).

**URL Completa:** `http://localhost/app-tarea/Backend-tarea/public/api/rest/tareas/`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json
```

**Request Body (Tarea Asignada - Tipo A):**
```json
{
  "titulo": "Revisar caja registradora",
  "descripcion": "Verificar cuadre de caja del turno mañana",
  "prioridad": "ALTA",
  "categoria_asignacion": "ESPECIFICA",
  "id_asignado": 5,
  "fecha_inicio": "2025-12-11 09:00:00",
  "fecha_fin": "2025-12-11 12:00:00",
  "es_iniciativa": false,
  "subtareas": [
    "Contar efectivo",
    "Revisar vouchers",
    "Generar reporte de cierre"
  ]
}
```

**Request Body (Tarea de Iniciativa - Tipo B):**
```json
{
  "titulo": "Organizar despensa",
  "descripcion": "Reorganizar productos por fecha de vencimiento",
  "prioridad": "MEDIA",
  "es_iniciativa": true,
  "fecha_inicio": "2025-12-10 14:00:00",
  "fecha_fin": "2025-12-10 17:00:00",
  "subtareas": [
    "Revisar productos próximos a vencer",
    "Reorganizar estantes"
  ]
}
```

**Request Body (Tarea a Bolsa):**
```json
{
  "titulo": "Limpieza de área común",
  "descripcion": "Limpiar y desinfectar área de comedor",
  "prioridad": "MEDIA",
  "categoria_asignacion": "BOLSA_COLABORADOR",
  "fecha_inicio": "2025-12-11 10:00:00",
  "fecha_fin": "2025-12-11 11:30:00",
  "subtareas": ["Barrer", "Trapear", "Desinfectar mesas"]
}
```

**Categorías de Asignación:**
- `ESPECIFICA`: Asignada a un usuario específico (requiere `id_asignado`)
- `BOLSA_COLABORADOR`: Bolsa de trabajo para colaboradores
- `BOLSA_GERENTE`: Bolsa de trabajo para gerentes
- `BOLSA_AMBOS`: Bolsa de trabajo para gerentes y colaboradores

**Validaciones Tipo A (Asignada):**
- ✅ Duración mínima: 15 minutos
- ✅ Sin límite máximo de duración
- ✅ Debe especificar `id_asignado` O `categoria_asignacion` (bolsa)
- ✅ Fecha inicio no puede ser en el pasado

**Validaciones Tipo B (Iniciativa):**
- ✅ Duración mínima: 15 minutos
- ✅ Duración máxima: 9 horas
- ✅ Máximo 10 iniciativas por día
- ✅ Autoasignación forzosa (el creador es el asignado)
- ✅ No se puede asignar a bolsas

**Response (201 Created):**
```json
{
  "tipo": 1,
  "mensajes": ["Tarea creada exitosamente"],
  "data": {
    "id_tarea": 1
  }
}
```

**Response (Error - Límite de Iniciativas - 400 Bad Request):**
```json
{
  "tipo": 2,
  "mensajes": ["Errores de validación"],
  "data": {
    "errores": [
      "Has alcanzado el límite de 10 tareas de iniciativa por día"
    ]
  }
}
```

**Response (Error - Duración Excedida - 400 Bad Request):**
```json
{
  "tipo": 2,
  "mensajes": ["Errores de validación"],
  "data": {
    "errores": [
      "Las tareas de iniciativa no pueden superar las 9 horas de duración"
    ]
  }
}
```

---

#### `GET /` - Listar Tareas (Matriz V6)

Lista tareas aplicando filtros de visibilidad según el rol del usuario autenticado.

**Matriz de Visibilidad V6:**
- **CEO**: Ve TODAS las tareas de todas las sucursales
- **GG**: Ve TODAS las tareas de su sucursal
- **GERENTE**: Ve:
  - Sus propias tareas (creadas o asignadas)
  - Tareas de sus colaboradores directos
  - Bolsas de trabajo relevantes (BOLSA_GERENTE, BOLSA_AMBOS)
- **COLABORADOR**: Ve:
  - Sus tareas asignadas
  - Sus iniciativas propias
  - Bolsas de trabajo de colaborador (BOLSA_COLABORADOR, BOLSA_AMBOS)

**URL Completa:** `http://localhost/app-tarea/Backend-tarea/public/api/rest/tareas/`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Query Params (opcionales):**
- `estado_ejecucion` - Filtrar por estado: PROGRAMADA | EN_PROGRESO | COMPLETADA | VENCIDA
- `prioridad` - Filtrar por prioridad: BAJA | MEDIA | ALTA | CRITICA
- `es_iniciativa` - Filtrar por tipo: true | false

**Ejemplos de URLs:**
```
GET /tareas/
GET /tareas/?estado_ejecucion=EN_PROGRESO
GET /tareas/?prioridad=ALTA&es_iniciativa=false
GET /tareas/?es_iniciativa=true
```

**Response (200 OK):**
```json
{
  "tipo": 1,
  "mensajes": ["Tareas obtenidas exitosamente"],
  "data": {
    "tareas": [
      {
        "id": 1,
        "id_sucursal": 1,
        "id_creador": 2,
        "id_asignado": 5,
        "id_validador": null,
        "titulo": "Inventario de almacén",
        "descripcion": "Realizar conteo completo del inventario",
        "prioridad": "ALTA",
        "categoria_asignacion": "ESPECIFICA",
        "fecha_inicio": "2025-12-10 08:00:00",
        "fecha_fin_original": "2025-12-10 17:00:00",
        "fecha_fin_actual": "2025-12-10 17:00:00",
        "fecha_validacion": null,
        "estado_ejecucion": "EN_PROGRESO",
        "estado_ciclo_vida": "ABIERTA",
        "estado_validacion": "SIN_VALIDAR",
        "contador_extensiones": 0,
        "contador_rechazos": 0,
        "es_iniciativa": false,
        "eliminado_en": null,
        "creado_en": "2025-12-09 15:30:00",
        "actualizado_en": "2025-12-10 08:05:00",
        "creador_nombre": "Juan Pérez García",
        "asignado_nombre": "Pedro González",
        "validador_nombre": null,
        "subtareas": [
          {
            "id": 1,
            "id_tarea": 1,
            "titulo": "Contar productos secos",
            "completada": true,
            "id_creador": 2,
            "creado_en": "2025-12-09 15:30:00",
            "creador_nombre": "Juan Pérez García"
          },
          {
            "id": 2,
            "id_tarea": 1,
            "titulo": "Contar productos refrigerados",
            "completada": false,
            "id_creador": 2,
            "creado_en": "2025-12-09 15:30:00",
            "creador_nombre": "Juan Pérez García"
          }
        ]
      }
    ],
    "total": 1
  }
}
```

**Estados de Ejecución (Automáticos):**
- `PROGRAMADA`: La tarea aún no ha iniciado (fecha_inicio > ahora)
- `EN_PROGRESO`: La tarea está en curso (ahora entre fecha_inicio y fecha_fin_actual)
- `COMPLETADA`: El usuario marcó la tarea como completada
- `VENCIDA`: La tarea no se completó antes de fecha_fin_actual

**Estados de Ciclo de Vida:**
- `ABIERTA`: Tarea activa en proceso
- `VALIDADA`: Tarea aprobada por un superior
- `FINALIZADA_VENCIDA`: Tarea cerrada por vencimiento
- `INACTIVA`: Tarea eliminada por el creador (solo asignadas)

**Estados de Validación:**
- `SIN_VALIDAR`: Tarea no completada aún
- `POR_VALIDAR`: Tarea completada, esperando validación
- `VALIDADA`: Tarea validada por superior

---

#### `GET /:id` - Obtener Tarea por ID

Obtiene los detalles de una tarea específica. Verifica permisos según Matriz V6.

**URL Completa:** `http://localhost/app-tarea/Backend-tarea/public/api/rest/tareas/1`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Response (200 OK):**
```json
{
  "tipo": 1,
  "mensajes": ["Tarea obtenida exitosamente"],
  "data": {
    "tarea": {
      "id": 1,
      "titulo": "Inventario de almacén",
      "descripcion": "Realizar conteo completo del inventario",
      "prioridad": "ALTA",
      "estado_ejecucion": "EN_PROGRESO",
      "creador_nombre": "Juan Pérez García",
      "asignado_nombre": "Pedro González",
      "subtareas": [...]
    }
  }
}
```

**Response (404 Not Found):**
```json
{
  "tipo": 2,
  "mensajes": ["No se encontró la tarea o no tienes permisos para verla"],
  "data": {}
}
```

---

#### `POST /:id/completar` - Marcar Tarea como Completada

Marca una tarea como completada. Solo el usuario asignado puede completar su tarea.

**Validaciones:**
- ✅ Solo el `id_asignado` puede completar la tarea
- ✅ TODAS las subtareas deben estar completadas antes

**URL Completa:** `http://localhost/app-tarea/Backend-tarea/public/api/rest/tareas/1/completar`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Response (200 OK):**
```json
{
  "tipo": 1,
  "mensajes": ["Tarea marcada como completada. Ahora está pendiente de validación."],
  "data": {}
}
```

**Response (Error - Subtareas Incompletas - 400 Bad Request):**
```json
{
  "tipo": 2,
  "mensajes": ["No se puede completar la tarea"],
  "data": {
    "errores": [
      "Debes completar todas las subtareas antes de solicitar validación (1/3)"
    ]
  }
}
```

**Response (Error - No es el Asignado - 403 Forbidden):**
```json
{
  "tipo": 2,
  "mensajes": ["Solo el usuario asignado puede marcar la tarea como completada"],
  "data": {}
}
```

---

#### `DELETE /:id` - Eliminar Tarea

Elimina una tarea. El comportamiento depende del tipo de tarea:
- **Iniciativas (Tipo B)**: Soft delete (marca `eliminado_en = NOW()`)
- **Asignadas (Tipo A)**: Hard state (marca `estado_ciclo_vida = INACTIVA`)

**Permisos:**
- Solo el creador (`id_creador`) puede eliminar la tarea

**URL Completa:** `http://localhost/app-tarea/Backend-tarea/public/api/rest/tareas/1`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Response (200 OK - Iniciativa):**
```json
{
  "tipo": 1,
  "mensajes": ["Iniciativa eliminada exitosamente"],
  "data": {}
}
```

**Response (200 OK - Asignada):**
```json
{
  "tipo": 1,
  "mensajes": ["Tarea marcada como inactiva"],
  "data": {}
}
```

**Response (403 Forbidden):**
```json
{
  "tipo": 2,
  "mensajes": ["Solo el creador de la tarea puede eliminarla"],
  "data": {}
}
```

---

#### `PUT /subtareas/:id` - Actualizar Estado de Subtarea

Cambia el estado de completitud de una subtarea (toggle true/false).

**URL Completa:** `http://localhost/app-tarea/Backend-tarea/public/api/rest/tareas/subtareas/1`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json
```

**Request Body:**
```json
{
  "completada": true
}
```

**Response (200 OK):**
```json
{
  "tipo": 1,
  "mensajes": ["Subtarea actualizada exitosamente"],
  "data": {}
}
```

---

#### 📱 Ejemplo de Integración con Angular 20

**Servicio de Tareas (tarea.service.ts):**

```typescript
import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

interface ApiResponse {
  tipo: number;
  mensajes: string[];
  data: any;
}

interface FiltrosTarea {
  estado_ejecucion?: string;
  prioridad?: string;
  es_iniciativa?: boolean;
}

@Injectable({
  providedIn: 'root'
})
export class TareaService {
  private apiUrl = 'http://localhost/app-tarea/Backend-tarea/public/api/rest/tareas';

  constructor(private http: HttpClient) {}

  // Crear tarea asignada (Tipo A)
  crearAsignada(tarea: any): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(this.apiUrl, {
      ...tarea,
      es_iniciativa: false
    });
  }

  // Crear tarea de iniciativa (Tipo B)
  crearIniciativa(tarea: any): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(this.apiUrl, {
      ...tarea,
      es_iniciativa: true
    });
  }

  // Listar tareas con filtros
  listar(filtros?: FiltrosTarea): Observable<ApiResponse> {
    let params = new HttpParams();

    if (filtros) {
      if (filtros.estado_ejecucion) {
        params = params.set('estado_ejecucion', filtros.estado_ejecucion);
      }
      if (filtros.prioridad) {
        params = params.set('prioridad', filtros.prioridad);
      }
      if (filtros.es_iniciativa !== undefined) {
        params = params.set('es_iniciativa', filtros.es_iniciativa.toString());
      }
    }

    return this.http.get<ApiResponse>(this.apiUrl, { params });
  }

  // Obtener tarea por ID
  obtenerPorId(id: number): Observable<ApiResponse> {
    return this.http.get<ApiResponse>(`${this.apiUrl}/${id}`);
  }

  // Marcar como completada
  marcarCompletada(id: number): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(`${this.apiUrl}/${id}/completar`, {});
  }

  // Eliminar tarea
  eliminar(id: number): Observable<ApiResponse> {
    return this.http.delete<ApiResponse>(`${this.apiUrl}/${id}`);
  }

  // Actualizar subtarea
  actualizarSubtarea(idSubtarea: number, completada: boolean): Observable<ApiResponse> {
    return this.http.put<ApiResponse>(`${this.apiUrl}/subtareas/${idSubtarea}`, {
      completada
    });
  }
}
```

**Componente de Creación (crear-tarea.component.ts):**

```typescript
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { TareaService } from './tarea.service';
import { AuthService } from '../auth/auth.service';

@Component({
  selector: 'app-crear-tarea',
  templateUrl: './crear-tarea.component.html'
})
export class CrearTareaComponent implements OnInit {
  tarea = {
    titulo: '',
    descripcion: '',
    prioridad: 'MEDIA',
    categoria_asignacion: 'ESPECIFICA',
    id_asignado: null as number | null,
    fecha_inicio: '',
    fecha_fin: '',
    es_iniciativa: false,
    subtareas: [] as string[]
  };

  usuarioActual: any;
  colaboradores: any[] = [];
  prioridades = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];
  categoriasAsignacion = [
    { value: 'ESPECIFICA', label: 'Asignar a usuario específico' },
    { value: 'BOLSA_COLABORADOR', label: 'Bolsa de Colaboradores' },
    { value: 'BOLSA_GERENTE', label: 'Bolsa de Gerentes' },
    { value: 'BOLSA_AMBOS', label: 'Bolsa Mixta (Gerentes + Colaboradores)' }
  ];

  nuevaSubtarea = '';
  errores: string[] = [];
  cargando = false;

  constructor(
    private tareaService: TareaService,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Obtener usuario actual del token
    this.authService.verificarToken().subscribe({
      next: (response) => {
        if (response.tipo === 1) {
          this.usuarioActual = response.data.usuario;
        }
      }
    });

    // Cargar colaboradores para dropdown (si no es iniciativa)
    // this.cargarColaboradores();
  }

  agregarSubtarea(): void {
    if (this.nuevaSubtarea.trim()) {
      this.tarea.subtareas.push(this.nuevaSubtarea.trim());
      this.nuevaSubtarea = '';
    }
  }

  eliminarSubtarea(index: number): void {
    this.tarea.subtareas.splice(index, 1);
  }

  onTipoChange(): void {
    if (this.tarea.es_iniciativa) {
      // Iniciativas: forzar autoasignación
      this.tarea.id_asignado = null;
      this.tarea.categoria_asignacion = 'ESPECIFICA';
    }
  }

  onSubmit(): void {
    this.cargando = true;
    this.errores = [];

    // Preparar datos
    const tareaData = { ...this.tarea };

    // Si es bolsa, limpiar id_asignado
    if (tareaData.categoria_asignacion !== 'ESPECIFICA') {
      tareaData.id_asignado = null;
    }

    // Llamar al servicio apropiado
    const observable = tareaData.es_iniciativa
      ? this.tareaService.crearIniciativa(tareaData)
      : this.tareaService.crearAsignada(tareaData);

    observable.subscribe({
      next: (response) => {
        if (response.tipo === 1) {
          // Éxito
          this.router.navigate(['/tareas']);
        } else if (response.tipo === 2) {
          // Advertencia
          this.errores = response.data.errores || response.mensajes;
        }
        this.cargando = false;
      },
      error: (error) => {
        this.errores = ['Error al crear la tarea'];
        this.cargando = false;
      }
    });
  }
}
```

**Componente de Lista (lista-tareas.component.ts):**

```typescript
import { Component, OnInit } from '@angular/core';
import { TareaService } from './tarea.service';

@Component({
  selector: 'app-lista-tareas',
  templateUrl: './lista-tareas.component.html'
})
export class ListaTareasComponent implements OnInit {
  tareas: any[] = [];
  filtros = {
    estado_ejecucion: '',
    prioridad: '',
    es_iniciativa: undefined as boolean | undefined
  };

  cargando = false;
  errores: string[] = [];

  constructor(private tareaService: TareaService) {}

  ngOnInit(): void {
    this.cargarTareas();
  }

  cargarTareas(): void {
    this.cargando = true;
    this.errores = [];

    this.tareaService.listar(this.filtros).subscribe({
      next: (response) => {
        if (response.tipo === 1) {
          this.tareas = response.data.tareas;
        } else {
          this.errores = response.mensajes;
        }
        this.cargando = false;
      },
      error: (error) => {
        this.errores = ['Error al cargar las tareas'];
        this.cargando = false;
      }
    });
  }

  aplicarFiltros(): void {
    this.cargarTareas();
  }

  marcarCompletada(tarea: any): void {
    if (confirm('¿Marcar esta tarea como completada?')) {
      this.tareaService.marcarCompletada(tarea.id).subscribe({
        next: (response) => {
          if (response.tipo === 1) {
            this.cargarTareas();
          } else if (response.tipo === 2) {
            alert(response.mensajes.join('\n'));
          }
        },
        error: () => {
          alert('Error al completar la tarea');
        }
      });
    }
  }

  eliminarTarea(tarea: any): void {
    const mensaje = tarea.es_iniciativa
      ? '¿Eliminar esta iniciativa?'
      : '¿Marcar esta tarea como inactiva?';

    if (confirm(mensaje)) {
      this.tareaService.eliminar(tarea.id).subscribe({
        next: (response) => {
          if (response.tipo === 1) {
            this.cargarTareas();
          } else {
            alert(response.mensajes.join('\n'));
          }
        },
        error: () => {
          alert('Error al eliminar la tarea');
        }
      });
    }
  }

  toggleSubtarea(subtarea: any): void {
    this.tareaService.actualizarSubtarea(
      subtarea.id,
      !subtarea.completada
    ).subscribe({
      next: (response) => {
        if (response.tipo === 1) {
          subtarea.completada = !subtarea.completada;
        }
      },
      error: () => {
        alert('Error al actualizar la subtarea');
      }
    });
  }

  obtenerClaseEstado(estado: string): string {
    const clases: any = {
      'PROGRAMADA': 'badge-info',
      'EN_PROGRESO': 'badge-primary',
      'COMPLETADA': 'badge-success',
      'VENCIDA': 'badge-danger'
    };
    return clases[estado] || 'badge-secondary';
  }

  obtenerClasePrioridad(prioridad: string): string {
    const clases: any = {
      'BAJA': 'text-secondary',
      'MEDIA': 'text-primary',
      'ALTA': 'text-warning',
      'CRITICA': 'text-danger'
    };
    return clases[prioridad] || 'text-muted';
  }
}
```

---

### 🔄 Flujos de Trabajo (Fase 6)

#### `POST /api/rest/flujo-tareas/:id/validar`

**Request:**
```json
{
  "comentario": "Excelente trabajo, todo en orden"
}
```

#### `POST /api/rest/flujo-tareas/:id/rechazar`

**Request:**
```json
{
  "motivo": "Faltan detalles en el reporte",
  "subtareas_correctivas": [
    {"titulo": "Agregar fotos del inventario"},
    {"titulo": "Incluir firma del supervisor"}
  ]
}
```

#### `POST /api/rest/flujo-tareas/:id/extender`

**Request:**
```json
{
  "nueva_fecha_fin": "2025-12-12 18:00:00",
  "es_justificada": true,
  "razon": "Retraso en entrega de insumos por proveedor"
}
```

---

## 🏗️ Arquitectura

### Patrón Repository (Puro)

```
Cliente Angular 20
    ↓
Entry Point (public/api/rest/*)
    ↓
Controller (Orquestador)
    ↓
Validator (Reglas de Negocio)
    ↓
Repository (SQL Puro)
    ↓
DB (Singleton PDO)
    ↓
MySQL
```

### Principios Aplicados

- **Separación de Capas**: Cada capa tiene responsabilidad única
- **DTOs Anémicos**: Las entidades no tienen lógica, solo datos
- **Repository Pattern**: Abstracción completa de SQL
- **Inyección de Dependencias**: Testeable y mantenible

---

## 🧪 Testing

### Test de Conexión
```bash
php test-conexion.php
```

### Datos de Prueba

Usuario CEO por defecto:
```
Email: ceo@restaurant.com
Password: Admin123!
```

---

## 📞 Soporte

Para consultas técnicas o reportar bugs, contactar al equipo de desarrollo.

---

## 📝 Licencia

Uso interno - Todos los derechos reservados.

---

**Última actualización**: Fase 5 - CRUD de Tareas con Matriz de Visibilidad V6 (Diciembre 2025)

---

## 📝 Changelog

### Fase 5 - CRUD de Tareas ✅ (2025-12-10)
- ✅ Matriz de Visibilidad V6 con filtros automáticos por rol
- ✅ Tipo A (Asignadas): Sin límite de duración, asignación específica o bolsas
- ✅ Tipo B (Iniciativas): Máx. 10/día, máx. 9h, autoasignación forzosa
- ✅ Estados automáticos por zona horaria (PROGRAMADA → EN_PROGRESO → VENCIDA/COMPLETADA)
- ✅ Subtareas con validación de completitud antes de marcar como completada
- ✅ Soft Delete para iniciativas (eliminado_en), INACTIVA para asignadas
- ✅ Endpoints: crear, listar, obtener, completar, eliminar, actualizar subtareas
- ✅ Documentación completa con ejemplos Angular 20
- ✅ Sistema de bolsas de trabajo (BOLSA_COLABORADOR, BOLSA_GERENTE, BOLSA_AMBOS)

### Fase 3 - Gestión de Sucursales ✅ (2025-12-10)
- ✅ Creación atómica (Sucursal + Gerente General en una transacción SQL)
- ✅ CRUD completo con protección por rol CEO
- ✅ Soft Delete (preserva integridad histórica)
- ✅ Zona horaria independiente por sucursal
- ✅ Control de email duplicado del GG
- ✅ Documentación completa con ejemplos Angular 20

### Fase 2 - Sistema de Autenticación ✅ (2025-12-10)
- ✅ Login con JWT (9 horas de duración)
- ✅ Control de intentos fallidos (3 intentos = bloqueo 2 min)
- ✅ Auditoría de eventos de seguridad
- ✅ Middleware JWT para rutas protegidas
- ✅ Endpoint de verificación de token
- ✅ Documentación completa para Angular 20

### Fase 1 - Infraestructura Base ✅ (2025-12-10)
- ✅ Arquitectura de capas (Repository Pattern)
- ✅ Base de datos (9 tablas optimizadas)
- ✅ Sistema de respuestas JSON estandarizado
- ✅ Manejo global de errores y CORS
- ✅ Composer + Slim 2.6 + Firebase JWT

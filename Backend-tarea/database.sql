-- ============================================================================
-- ESQUEMA DE BASE DE DATOS: GESTOR DE TAREAS Y RENDIMIENTO
-- Versión: 1.0 (Optimizada con Índices y Constraints)
-- Sistema Multi-Tenant para Restaurantes
-- ============================================================================

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS tareas_restaurant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tareas_restaurant;

-- ============================================================================
-- 1. TABLA DE SUCURSALES (Multi-Tenant)
-- ============================================================================
CREATE TABLE sucursales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(200),
    telefono VARCHAR(20),
    zona_horaria VARCHAR(50) NOT NULL COMMENT 'Ej: America/Lima - VITAL para cálculo de vencimientos',
    estado ENUM('ACTIVA', 'INACTIVA') DEFAULT 'ACTIVA',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Multi-inquilino: Cada sucursal es un contexto aislado';

-- ============================================================================
-- 2. TABLA DE USUARIOS (Jerárquica)
-- ============================================================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_sucursal INT NULL COMMENT 'NULL solo para el CEO (alcance global)',
    id_gerente_directo INT NULL COMMENT 'Para jerarquía Gerente→Colaboradores',
    rol ENUM('CEO', 'GG', 'GERENTE', 'COLABORADOR') NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    activo BOOLEAN DEFAULT 1,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_sucursal) REFERENCES sucursales(id),
    FOREIGN KEY (id_gerente_directo) REFERENCES usuarios(id) ON DELETE SET NULL,

    INDEX idx_email (email),
    INDEX idx_sucursal_rol (id_sucursal, rol),
    INDEX idx_gerente_directo (id_gerente_directo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='4 roles: CEO > GG > GERENTE > COLABORADOR';

-- ============================================================================
-- 3. TABLA MAESTRA DE TAREAS (Analítica y Operativa)
-- ============================================================================
CREATE TABLE tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_sucursal INT NOT NULL,

    -- Actores
    id_creador INT NOT NULL COMMENT 'Quién creó la tarea',
    id_asignado INT NULL COMMENT 'NULL si está en Bolsa (Sin Asignar)',
    id_validador INT NULL COMMENT 'Quién aprobó la tarea (Para KPIs de liderazgo)',

    -- Configuración
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    prioridad ENUM('BAJA', 'MEDIA', 'ALTA', 'CRITICA') DEFAULT 'MEDIA',

    -- Bolsas de Trabajo (Categoría de Asignación)
    categoria_asignacion ENUM('ESPECIFICA', 'BOLSA_COLABORADOR', 'BOLSA_GERENTE', 'BOLSA_AMBOS') NOT NULL,

    -- Tiempos (Guardados en UTC, convertidos según zona_horaria en App)
    fecha_inicio DATETIME NOT NULL,
    fecha_fin_original DATETIME NOT NULL COMMENT 'Fecha prometida inicial',
    fecha_fin_actual DATETIME NOT NULL COMMENT 'Fecha real tras extensiones',
    fecha_validacion DATETIME NULL COMMENT 'Timestamp de aprobación final',

    -- ESTADOS (Máquina de Estados)
    estado_ejecucion ENUM('PROGRAMADA', 'EN_PROGRESO', 'COMPLETADA', 'VENCIDA') DEFAULT 'PROGRAMADA',
    estado_ciclo_vida ENUM('ABIERTA', 'VALIDADA', 'FINALIZADA_VENCIDA', 'INACTIVA') DEFAULT 'ABIERTA',
    estado_validacion ENUM('SIN_VALIDAR', 'POR_VALIDAR', 'VALIDADA') DEFAULT 'SIN_VALIDAR',

    -- CONTADORES (KPIs para Analítica)
    contador_extensiones TINYINT UNSIGNED DEFAULT 0 COMMENT 'Máximo 3',
    contador_rechazos TINYINT UNSIGNED DEFAULT 0 COMMENT 'Para KPI de Calidad',

    -- Indicadores Analíticos
    es_iniciativa BOOLEAN DEFAULT 0 COMMENT '1 si el usuario la creó para sí mismo',
    eliminado_en DATETIME NULL COMMENT 'Soft Delete para iniciativas abandonadas',

    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Claves Foráneas
    FOREIGN KEY (id_sucursal) REFERENCES sucursales(id),
    FOREIGN KEY (id_creador) REFERENCES usuarios(id),
    FOREIGN KEY (id_asignado) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (id_validador) REFERENCES usuarios(id) ON DELETE SET NULL,

    -- Índices de Rendimiento
    INDEX idx_asignado_estado (id_asignado, estado_ciclo_vida),
    INDEX idx_sucursal_estado (id_sucursal, estado_ciclo_vida),
    INDEX idx_creador (id_creador),
    INDEX idx_validador (id_validador),
    INDEX idx_fecha_fin (fecha_fin_actual),
    INDEX idx_es_iniciativa (es_iniciativa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tabla central con estados y contadores para BI';

-- ============================================================================
-- 4. SUBTAREAS
-- ============================================================================
CREATE TABLE subtareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tarea INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    completada BOOLEAN DEFAULT 0,
    id_creador INT NOT NULL COMMENT 'Para saber si fue corrección del jefe tras rechazo',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_tarea) REFERENCES tareas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_creador) REFERENCES usuarios(id),

    INDEX idx_tarea_completada (id_tarea, completada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 5. EXTENSIONES DE TIEMPO (Auditoría de Retrasos)
-- ============================================================================
CREATE TABLE extensiones_tarea (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tarea INT NOT NULL,
    id_autorizador INT NOT NULL COMMENT 'Jefe que autorizó la extensión',
    fecha_fin_anterior DATETIME NOT NULL,
    fecha_fin_nueva DATETIME NOT NULL,
    es_justificada BOOLEAN NOT NULL COMMENT '1=KPI Verde (exonerado), 0=KPI Rojo (ineficiente)',
    razon VARCHAR(255),
    numero_extension TINYINT NOT NULL COMMENT '1, 2 o 3 (máximo)',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_tarea) REFERENCES tareas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_autorizador) REFERENCES usuarios(id),

    INDEX idx_tarea (id_tarea)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registro de todas las extensiones con flag justificación';

-- ============================================================================
-- 6. ADJUNTOS (Soporta múltiples imágenes por tarea)
-- ============================================================================
CREATE TABLE adjuntos_tarea (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tarea INT NOT NULL,
    id_subido_por INT NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL COMMENT 'Path relativo desde /api/uploads/adjuntos/',
    tipo_archivo VARCHAR(50) COMMENT 'MIME Type: image/jpeg, application/pdf',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_tarea) REFERENCES tareas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_subido_por) REFERENCES usuarios(id),

    INDEX idx_tarea (id_tarea)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 7. HISTORIAL COMPLETO (Logs de Auditoría)
-- ============================================================================
CREATE TABLE historial_tarea (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tarea INT NOT NULL,
    id_usuario INT NOT NULL,
    accion ENUM('CREAR','EDITAR','COMPLETAR','SOLICITAR_VAL','VALIDAR','RECHAZAR','EXTENDER','FINALIZAR_VENCIDA','ELIMINAR'),
    metadatos JSON NULL COMMENT 'Detalles adicionales: {"campo":"titulo", "antes":"A", "despues":"B"}',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_tarea) REFERENCES tareas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),

    INDEX idx_tarea_accion (id_tarea, accion),
    INDEX idx_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Trazabilidad completa de cambios';

-- ============================================================================
-- 8. SEGURIDAD (Control de Intentos de Login)
-- ============================================================================
CREATE TABLE intentos_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_origen VARCHAR(45) NOT NULL COMMENT 'Soporta IPv4 e IPv6',
    fecha_intento DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_email_fecha (email, fecha_intento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Prevención de fuerza bruta (3 intentos/2min)';

-- ============================================================================
-- 9. AUDITORÍA DE ACCESO (Sesiones)
-- ============================================================================
CREATE TABLE auditoria_auth (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL COMMENT 'NULL si es intento fallido',
    evento ENUM('LOGIN_EXITOSO', 'LOGIN_FALLIDO', 'BLOQUEO_CUENTA', 'LOGOUT'),
    detalles VARCHAR(255),
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_usuario_evento (id_usuario, evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registro de eventos de seguridad';

-- ============================================================================
-- DATOS DE PRUEBA (Opcional - Solo para desarrollo)
-- ============================================================================

-- Crear CEO por defecto (Contraseña: Admin123!)
INSERT INTO usuarios (id_sucursal, rol, nombre_completo, email, password_hash, activo)
VALUES (NULL, 'CEO', 'Administrador General', 'ceo@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Nota: Para crear más usuarios de prueba, primero crear sucursales y luego sus usuarios asociados

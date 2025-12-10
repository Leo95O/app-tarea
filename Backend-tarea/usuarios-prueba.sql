-- ============================================================================
-- SCRIPT: USUARIOS DE PRUEBA PARA TESTING
-- ============================================================================
-- Este script crea una jerarquía completa de usuarios para probar el sistema:
-- - 1 CEO (ya existe)
-- - 1 Gerente General (ya existe)
-- - 2 Gerentes
-- - 4 Colaboradores (2 por cada Gerente)
-- ============================================================================

USE tareas_restaurant;

-- ============================================================================
-- GERENTES (Sucursal Miraflores)
-- ============================================================================

-- Gerente 1: Carlos Ramírez (Área Cocina)
INSERT INTO usuarios (id_sucursal, id_gerente_directo, rol, nombre_completo, email, password_hash, activo, creado_en)
VALUES (
    1,  -- Sucursal Miraflores
    3,  -- GG: María López
    'GERENTE',
    'Carlos Ramírez Torres',
    'carlos.ramirez@restaurant.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Password: Gerente123!
    1,
    NOW()
);

-- Gerente 2: Ana Martínez (Área Servicio)
INSERT INTO usuarios (id_sucursal, id_gerente_directo, rol, nombre_completo, email, password_hash, activo, creado_en)
VALUES (
    1,  -- Sucursal Miraflores
    3,  -- GG: María López
    'GERENTE',
    'Ana Martínez Silva',
    'ana.martinez@restaurant.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Password: Gerente123!
    1,
    NOW()
);

-- ============================================================================
-- COLABORADORES DEL GERENTE CARLOS (Cocina)
-- ============================================================================

-- Colaborador 1: Pedro Sánchez (Chef Junior)
INSERT INTO usuarios (id_sucursal, id_gerente_directo, rol, nombre_completo, email, password_hash, activo, creado_en)
VALUES (
    1,  -- Sucursal Miraflores
    4,  -- Gerente: Carlos Ramírez (ID asumido: 4)
    'COLABORADOR',
    'Pedro Sánchez López',
    'pedro.sanchez@restaurant.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Password: Colaborador123!
    1,
    NOW()
);

-- Colaborador 2: Luis García (Ayudante de Cocina)
INSERT INTO usuarios (id_sucursal, id_gerente_directo, rol, nombre_completo, email, password_hash, activo, creado_en)
VALUES (
    1,  -- Sucursal Miraflores
    4,  -- Gerente: Carlos Ramírez
    'COLABORADOR',
    'Luis García Mendoza',
    'luis.garcia@restaurant.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Password: Colaborador123!
    1,
    NOW()
);

-- ============================================================================
-- COLABORADORES DE LA GERENTE ANA (Servicio)
-- ============================================================================

-- Colaborador 3: Rosa Fernández (Mesera)
INSERT INTO usuarios (id_sucursal, id_gerente_directo, rol, nombre_completo, email, password_hash, activo, creado_en)
VALUES (
    1,  -- Sucursal Miraflores
    5,  -- Gerente: Ana Martínez (ID asumido: 5)
    'COLABORADOR',
    'Rosa Fernández Díaz',
    'rosa.fernandez@restaurant.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Password: Colaborador123!
    1,
    NOW()
);

-- Colaborador 4: Miguel Torres (Cajero)
INSERT INTO usuarios (id_sucursal, id_gerente_directo, rol, nombre_completo, email, password_hash, activo, creado_en)
VALUES (
    1,  -- Sucursal Miraflores
    5,  -- Gerente: Ana Martínez
    'COLABORADOR',
    'Miguel Torres Ruiz',
    'miguel.torres@restaurant.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Password: Colaborador123!
    1,
    NOW()
);

-- ============================================================================
-- VERIFICACIÓN: Listar todos los usuarios creados
-- ============================================================================

SELECT
    u.id,
    u.nombre_completo,
    u.email,
    u.rol,
    s.nombre as sucursal,
    CONCAT(g.nombre_completo, ' (', g.rol, ')') as jefe_directo
FROM usuarios u
LEFT JOIN sucursales s ON u.id_sucursal = s.id
LEFT JOIN usuarios g ON u.id_gerente_directo = g.id
ORDER BY
    FIELD(u.rol, 'CEO', 'GG', 'GERENTE', 'COLABORADOR'),
    u.id;

-- ============================================================================
-- RESUMEN DE CREDENCIALES
-- ============================================================================

/*
USUARIOS CREADOS:

CEO:
- Email: JoelOlaya@restaurant.com
- Password: 123456789J

GERENTE GENERAL:
- Email: maria.lopez@restaurant.com
- Password: GerenteGG2025!

GERENTES:
- Email: carlos.ramirez@restaurant.com
- Password: Gerente123!

- Email: ana.martinez@restaurant.com
- Password: Gerente123!

COLABORADORES:
- Email: pedro.sanchez@restaurant.com
- Password: Colaborador123!

- Email: luis.garcia@restaurant.com
- Password: Colaborador123!

- Email: rosa.fernandez@restaurant.com
- Password: Colaborador123!

- Email: miguel.torres@restaurant.com
- Password: Colaborador123!

JERARQUÍA:
CEO (Joel Olaya)
└── GG (María López) - Sucursal Miraflores
    ├── GERENTE (Carlos Ramírez) - Área Cocina
    │   ├── COLABORADOR (Pedro Sánchez) - Chef Junior
    │   └── COLABORADOR (Luis García) - Ayudante
    └── GERENTE (Ana Martínez) - Área Servicio
        ├── COLABORADOR (Rosa Fernández) - Mesera
        └── COLABORADOR (Miguel Torres) - Cajero
*/

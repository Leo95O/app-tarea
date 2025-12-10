<?php
/**
 * PLANTILLA DE CONFIGURACIÓN
 *
 * INSTRUCCIONES:
 * 1. Copiar este archivo como "config.php"
 * 2. Completar con las credenciales reales
 * 3. NUNCA subir config.php al repositorio
 */

// Configuración de Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'tareas_restaurant');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de JWT (JSON Web Token)
define('JWT_SECRET', 'GENERAR_CLAVE_SECRETA_ALEATORIA_AQUI');
define('JWT_DURACION_SEGUNDOS', 32400); // 9 horas

// Zona Horaria por Defecto del Sistema
define('TIMEZONE_DEFECTO', 'America/Lima');

// Configuración de Errores (Desarrollo vs Producción)
define('MODO_DEBUG', true); // Cambiar a false en producción

// Configuración de CORS
define('CORS_ORIGIN', '*'); // En producción, especificar dominio de Angular

// Rutas del Sistema
define('RUTA_LOGS', __DIR__ . '/api/logs/');
define('RUTA_UPLOADS', __DIR__ . '/api/uploads/adjuntos/');

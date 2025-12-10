<?php
/**
 * SCRIPT DE PRUEBA DE INFRAESTRUCTURA
 *
 * Verifica que:
 * 1. La configuración se carga correctamente
 * 2. El autoloader de Composer funciona
 * 3. La conexión a BD está operativa
 * 4. El sistema de respuestas JSON funciona
 *
 * USO: php test-conexion.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Api\Core\DB;
use Api\Core\Response;

echo "========================================\n";
echo "  TEST DE INFRAESTRUCTURA - FASE 1\n";
echo "========================================\n\n";

// Test 1: Configuración
echo "[1/4] Verificando configuración...\n";
try {
    if (!defined('DB_NAME')) {
        throw new Exception("config.php no cargado correctamente");
    }
    echo "  ✓ Configuración cargada\n";
    echo "  ✓ Base de datos: " . DB_NAME . "\n";
    echo "  ✓ Zona horaria: " . TIMEZONE_DEFECTO . "\n\n";
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Autoloader
echo "[2/4] Verificando autoloader de Composer...\n";
try {
    if (!class_exists('Api\Core\DB')) {
        throw new Exception("Autoloader no configurado correctamente");
    }
    echo "  ✓ Autoloader PSR-4 funcional\n";
    echo "  ✓ Clases de Api\Core cargadas\n\n";
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Conexión a BD
echo "[3/4] Probando conexión a base de datos...\n";
try {
    $db = DB::obtenerInstancia();
    $conexion = $db->obtenerConexion();

    // Verificar que la BD existe y tiene tablas
    $stmt = $conexion->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_NUM);

    echo "  ✓ Conexión establecida exitosamente\n";
    echo "  ✓ Base de datos: " . DB_NAME . "\n";
    echo "  ✓ Tablas encontradas: " . count($tablas) . "\n";

    if (count($tablas) > 0) {
        echo "\n  Listado de tablas:\n";
        foreach ($tablas as $tabla) {
            echo "    - " . $tabla[0] . "\n";
        }
    } else {
        echo "  ⚠ ADVERTENCIA: No hay tablas. Ejecuta database.sql primero.\n";
    }
    echo "\n";

} catch (PDOException $e) {
    echo "  ✗ Error de BD: " . $e->getMessage() . "\n";
    echo "  ⚠ Verifica que:\n";
    echo "    - MySQL esté corriendo\n";
    echo "    - Las credenciales en config.php sean correctas\n";
    echo "    - La base de datos 'tareas_restaurant' exista\n\n";
    exit(1);
}

// Test 4: Sistema de Respuestas
echo "[4/4] Probando sistema de respuestas JSON...\n";
try {
    $respuestaExito = Response::exito(['test' => 'ok'], 'Test exitoso');
    $respuestaAdvertencia = Response::advertencia('Esto es una prueba');
    $respuestaError = Response::error('Error de prueba');

    if ($respuestaExito['tipo'] !== 1) {
        throw new Exception("Tipo de respuesta de éxito incorrecto");
    }
    if ($respuestaAdvertencia['tipo'] !== 2) {
        throw new Exception("Tipo de respuesta de advertencia incorrecto");
    }
    if ($respuestaError['tipo'] !== 3) {
        throw new Exception("Tipo de respuesta de error incorrecto");
    }

    echo "  ✓ Response::exito() funcional (Tipo 1)\n";
    echo "  ✓ Response::advertencia() funcional (Tipo 2)\n";
    echo "  ✓ Response::error() funcional (Tipo 3)\n";
    echo "  ✓ Contrato JSON V4 implementado\n\n";

} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Resultado Final
echo "========================================\n";
echo "  ✓ TODOS LOS TESTS PASARON\n";
echo "  ✓ FASE 1 COMPLETA\n";
echo "========================================\n";
echo "\nPróximo paso: Ejecutar composer install\n";
echo "Luego: Importar database.sql en MySQL\n\n";

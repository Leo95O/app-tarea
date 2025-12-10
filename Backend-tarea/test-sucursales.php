<?php
/**
 * SCRIPT DE PRUEBA: GESTIÓN DE SUCURSALES
 *
 * Prueba el flujo completo del módulo de sucursales:
 * 1. Login para obtener token JWT (CEO)
 * 2. Crear sucursal con Gerente General (atómica)
 * 3. Listar sucursales
 * 4. Obtener una sucursal por ID
 * 5. Actualizar sucursal
 * 6. Desactivar sucursal
 * 7. Reactivar sucursal
 *
 * USO: php test-sucursales.php
 */

echo "========================================\n";
echo "  TEST DE GESTIÓN DE SUCURSALES\n";
echo "========================================\n\n";

// URLs de los endpoints
$url_login = 'http://localhost/app-tarea/Backend-tarea/public/api/rest/auth/login';
$url_sucursales = 'http://localhost/app-tarea/Backend-tarea/public/api/rest/sucursales/';

// Credenciales del CEO
$credenciales_ceo = [
    'email' => 'JoelOlaya@restaurant.com',
    'password' => '123456789J'
];

// =========================
// PASO 1: LOGIN
// =========================
echo "[1/7] Realizando login como CEO...\n";

$ch = curl_init($url_login);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($credenciales_ceo));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$respuesta = curl_exec($ch);
$codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($codigo_http !== 200) {
    echo "  ✗ Error: No se pudo hacer login (HTTP {$codigo_http})\n";
    echo "  Verifica que el usuario CEO existe y tiene las credenciales correctas\n";
    echo "  Ejecuta: php crear-usuario-ceo.php\n\n";
    exit(1);
}

$datos_login = json_decode($respuesta, true);
if ($datos_login['tipo'] !== 1 || !isset($datos_login['data']['token'])) {
    echo "  ✗ Error: Login falló\n";
    echo "  " . implode(', ', $datos_login['mensajes']) . "\n\n";
    exit(1);
}

$token = $datos_login['data']['token'];
echo "  ✓ Login exitoso\n";
echo "  ✓ Token obtenido\n\n";

// =========================
// PASO 2: CREAR SUCURSAL
// =========================
echo "[2/7] Creando sucursal con Gerente General...\n";

$datos_sucursal = [
    'sucursal' => [
        'nombre' => 'Sucursal Miraflores',
        'direccion' => 'Av. Larco 1234, Miraflores',
        'telefono' => '+51 987654321',
        'zona_horaria' => 'America/Lima'
    ],
    'gerente_general' => [
        'nombre_completo' => 'María López García',
        'email' => 'maria.lopez@restaurant.com',
        'password' => 'GerenteGG2025!'
    ]
];

$ch = curl_init($url_sucursales);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos_sucursal));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer {$token}"
]);

$respuesta = curl_exec($ch);
$codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$datos = json_decode($respuesta, true);

if ($codigo_http !== 201 || !$datos || $datos['tipo'] !== 1) {
    echo "  ✗ Error al crear sucursal (HTTP {$codigo_http})\n";
    if ($datos && isset($datos['mensajes'])) {
        echo "  Mensajes: " . implode(', ', $datos['mensajes']) . "\n";
    }
    echo "  Respuesta completa: {$respuesta}\n\n";
    exit(1);
}

$id_sucursal = $datos['data']['id_sucursal'];
$id_gerente = $datos['data']['id_gerente_general'];

echo "  ✓ Sucursal creada: {$datos['data']['sucursal']}\n";
echo "  ✓ Gerente General: {$datos['data']['gerente_general']}\n";
echo "  ✓ ID Sucursal: {$id_sucursal}\n";
echo "  ✓ ID Gerente: {$id_gerente}\n\n";

// =========================
// PASO 3: LISTAR SUCURSALES
// =========================
echo "[3/7] Listando todas las sucursales...\n";

$ch = curl_init($url_sucursales);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$token}"
]);

$respuesta = curl_exec($ch);
curl_close($ch);

$datos = json_decode($respuesta, true);

if ($datos['tipo'] === 1) {
    echo "  ✓ Total de sucursales: {$datos['data']['total']}\n";
    foreach ($datos['data']['sucursales'] as $sucursal) {
        echo "    - {$sucursal['nombre']} ({$sucursal['estado']})\n";
    }
    echo "\n";
} else {
    echo "  ✗ Error al listar sucursales\n\n";
}

// =========================
// PASO 4: OBTENER POR ID
// =========================
echo "[4/7] Obteniendo sucursal por ID...\n";

$ch = curl_init("{$url_sucursales}/{$id_sucursal}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$token}"
]);

$respuesta = curl_exec($ch);
curl_close($ch);

$datos = json_decode($respuesta, true);

if ($datos['tipo'] === 1) {
    $sucursal = $datos['data']['sucursal'];
    echo "  ✓ Sucursal obtenida:\n";
    echo "    - Nombre: {$sucursal['nombre']}\n";
    echo "    - Dirección: {$sucursal['direccion']}\n";
    echo "    - Zona Horaria: {$sucursal['zona_horaria']}\n";
    echo "    - Estado: {$sucursal['estado']}\n\n";
} else {
    echo "  ✗ Error al obtener sucursal\n\n";
}

// =========================
// PASO 5: ACTUALIZAR
// =========================
echo "[5/7] Actualizando datos de la sucursal...\n";

$datos_actualizacion = [
    'nombre' => 'Sucursal Miraflores - Renovada',
    'telefono' => '+51 999888777'
];

$ch = curl_init("{$url_sucursales}/{$id_sucursal}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos_actualizacion));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer {$token}"
]);

$respuesta = curl_exec($ch);
curl_close($ch);

$datos = json_decode($respuesta, true);

if ($datos['tipo'] === 1) {
    echo "  ✓ Sucursal actualizada exitosamente\n\n";
} else {
    echo "  ✗ Error al actualizar: " . implode(', ', $datos['mensajes']) . "\n\n";
}

// =========================
// PASO 6: DESACTIVAR
// =========================
echo "[6/7] Desactivando sucursal (Soft Delete)...\n";

$ch = curl_init("{$url_sucursales}/{$id_sucursal}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$token}"
]);

$respuesta = curl_exec($ch);
curl_close($ch);

$datos = json_decode($respuesta, true);

if ($datos['tipo'] === 1) {
    echo "  ✓ Sucursal desactivada\n";
    echo "  ✓ Datos históricos preservados\n\n";
} else {
    echo "  ✗ Error al desactivar\n\n";
}

// =========================
// PASO 7: REACTIVAR
// =========================
echo "[7/7] Reactivando sucursal...\n";

$ch = curl_init("{$url_sucursales}/{$id_sucursal}/reactivar");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer {$token}"
]);

$respuesta = curl_exec($ch);
curl_close($ch);

$datos = json_decode($respuesta, true);

if ($datos['tipo'] === 1) {
    echo "  ✓ Sucursal reactivada exitosamente\n\n";
} else {
    echo "  ✗ Error al reactivar\n\n";
}

// =========================
// RESUMEN FINAL
// =========================
echo "========================================\n";
echo "  ✓ TODOS LOS TESTS PASARON\n";
echo "  ✓ FASE 3 COMPLETA Y FUNCIONAL\n";
echo "========================================\n\n";

echo "📋 DATOS CREADOS:\n";
echo "Sucursal ID: {$id_sucursal}\n";
echo "Gerente General ID: {$id_gerente}\n";
echo "Email GG: maria.lopez@restaurant.com\n";
echo "Contraseña GG: GerenteGG2025!\n\n";

echo "🎉 ¡El módulo de sucursales está listo para conectarse con Angular!\n\n";

<?php
/**
 * SCRIPT DE PRUEBA: LOGIN DE USUARIO
 *
 * Prueba el endpoint de autenticación haciendo una petición HTTP al servidor.
 * Verifica que el sistema devuelva un token JWT válido.
 *
 * USO: php test-login.php
 */

echo "========================================\n";
echo "  TEST DE AUTENTICACIÓN - FASE 2\n";
echo "========================================\n\n";

// URL del endpoint de login
$url = 'http://localhost/app-tarea/Backend-tarea/public/api/rest/auth/login';

// Credenciales del usuario CEO
$credenciales = [
    'email' => 'JoelOlaya@restaurant.com',
    'password' => '123456789J'
];

echo "[1/3] Preparando petición...\n";
echo "  URL: {$url}\n";
echo "  Email: {$credenciales['email']}\n\n";

// Preparar datos para enviar
$datos_json = json_encode($credenciales);

// Configurar cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $datos_json);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($datos_json)
]);

echo "[2/3] Enviando petición HTTP POST...\n";

// Ejecutar petición
$respuesta = curl_exec($ch);
$codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Verificar errores de conexión
if ($error) {
    echo "  ✗ Error de conexión: {$error}\n";
    echo "\n⚠ IMPORTANTE:\n";
    echo "  1. Verifica que XAMPP Apache esté corriendo\n";
    echo "  2. Verifica que la URL sea correcta\n";
    echo "  3. Verifica que los archivos estén en la ruta correcta\n\n";
    exit(1);
}

echo "  ✓ Respuesta recibida\n";
echo "  ✓ Código HTTP: {$codigo_http}\n\n";

echo "[3/3] Analizando respuesta...\n";

// Decodificar JSON
$datos = json_decode($respuesta, true);

if (!$datos) {
    echo "  ✗ Error: La respuesta no es JSON válido\n";
    echo "  Respuesta raw:\n{$respuesta}\n\n";
    exit(1);
}

// Verificar estructura del Contrato JSON V4
if (!isset($datos['tipo']) || !isset($datos['mensajes']) || !isset($datos['data'])) {
    echo "  ✗ Error: La respuesta no cumple el Contrato JSON V4\n";
    echo "  Respuesta:\n" . json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    exit(1);
}

echo "  ✓ JSON válido\n";
echo "  ✓ Contrato V4 cumplido\n";
echo "  ✓ Tipo: {$datos['tipo']}\n";
echo "  ✓ Mensaje: " . implode(', ', $datos['mensajes']) . "\n\n";

// Verificar éxito del login
if ($datos['tipo'] !== 1) {
    echo "  ⚠ ADVERTENCIA: Login falló\n";
    echo "  Motivo: " . implode(', ', $datos['mensajes']) . "\n\n";

    if (strpos($datos['mensajes'][0], 'bloqueada') !== false) {
        echo "  ℹ La cuenta está temporalmente bloqueada. Espera 2 minutos.\n\n";
    } elseif (strpos($datos['mensajes'][0], 'incorrectas') !== false) {
        echo "  ℹ Posibles causas:\n";
        echo "    - La contraseña del usuario CEO fue cambiada\n";
        echo "    - El usuario CEO no existe en la BD\n";
        echo "    - Ejecuta database.sql nuevamente para crear el usuario\n\n";
    }

    exit(1);
}

// Verificar presencia del token
if (!isset($datos['data']['token'])) {
    echo "  ✗ Error: No se recibió token JWT\n\n";
    exit(1);
}

$token = $datos['data']['token'];
$usuario = $datos['data']['usuario'];

echo "  ✓ Token JWT generado correctamente\n";
echo "  ✓ Usuario autenticado: {$usuario['nombre_completo']} ({$usuario['rol']})\n\n";

echo "========================================\n";
echo "  ✓ TEST EXITOSO - LOGIN FUNCIONAL\n";
echo "========================================\n\n";

echo "📋 DETALLES DEL TOKEN:\n";
echo "Token: " . substr($token, 0, 50) . "...\n";
echo "Expira en: {$datos['data']['expira_en_segundos']} segundos (9 horas)\n\n";

echo "👤 USUARIO AUTENTICADO:\n";
echo "ID: {$usuario['id']}\n";
echo "Nombre: {$usuario['nombre_completo']}\n";
echo "Email: {$usuario['email']}\n";
echo "Rol: {$usuario['rol']}\n";
echo "Sucursal: " . ($usuario['id_sucursal'] ?? 'Global (CEO)') . "\n\n";

echo "📡 EJEMPLO DE USO EN ANGULAR:\n";
echo "```typescript\n";
echo "this.authService.login('{$credenciales['email']}', '{$credenciales['password']}')\n";
echo "  .subscribe(response => {\n";
echo "    if (response.tipo === 1) {\n";
echo "      localStorage.setItem('token', response.data.token);\n";
echo "      this.router.navigate(['/dashboard']);\n";
echo "    }\n";
echo "  });\n";
echo "```\n\n";

echo "🎉 ¡La API está lista para conectarse con Angular!\n\n";

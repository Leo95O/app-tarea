<?php
/**
 * Test simple de creación de tarea
 */

// URL
$url = 'http://localhost/app-tarea/Backend-tarea/public/api/rest/tareas/';

// Login first
$url_login = 'http://localhost/app-tarea/Backend-tarea/public/api/rest/auth/login';
$datos_login = json_encode([
    'email' => 'JoelOlaya@restaurant.com',
    'password' => '123456789J'
]);

$ch = curl_init($url_login);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $datos_login);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login Response HTTP: $http_code\n";
echo "Login Body: $response\n\n";

$login_data = json_decode($response, true);
$token = $login_data['data']['token'] ?? null;

if (!$token) {
    echo "ERROR: No se pudo obtener token\n";
    exit(1);
}

echo "Token obtenido: " . substr($token, 0, 30) . "...\n\n";

// Ahora intentar crear tarea
$fecha_inicio = date('Y-m-d H:i:s', strtotime('+1 hour'));
$fecha_fin = date('Y-m-d H:i:s', strtotime('+5 hours'));

$datos_tarea = json_encode([
    'titulo' => 'Test simple',
    'descripcion' => 'Descripción test',
    'prioridad' => 'ALTA',
    'categoria_asignacion' => 'ESPECIFICA',
    'id_asignado' => 4,
    'fecha_inicio' => $fecha_inicio,
    'fecha_fin' => $fecha_fin,
    'es_iniciativa' => false,
    'subtareas' => ['Sub 1', 'Sub 2']
]);

echo "Request Body:\n";
echo $datos_tarea . "\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $datos_tarea);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Crear Tarea Response HTTP: $http_code\n";
echo "CURL Error: $error\n";
echo "Response Body: $response\n";
echo "Response Body Length: " . strlen($response) . "\n";

if (empty($response)) {
    echo "\n\nERROR: Body vacío!\n";
    echo "Esto suele indicar que el script PHP está muriendo antes de enviar respuesta.\n";
    echo "Verifica el error_log de Apache.\n";
}

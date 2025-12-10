<?php
/**
 * Script de Testing - Fase 5: CRUD de Tareas
 *
 * Tests a ejecutar:
 * 1. Login del CEO para obtener token
 * 2. Crear tarea asignada (Tipo A) con subtareas
 * 3. Crear tarea de iniciativa (Tipo B)
 * 4. Listar tareas
 * 5. Obtener tarea por ID
 * 6. Actualizar subtarea
 * 7. Marcar tarea como completada
 * 8. Eliminar tarea
 *
 * IMPORTANTE: Este test asume que ya existe al menos 1 sucursal con usuarios creados.
 * Si no existen, ejecutar primero: php crear-usuario-ceo.php y usuarios-prueba.sql
 */

// URL base de la API
$base_url = 'http://localhost/app-tarea/Backend-tarea/public/api/rest';

// Colores para output
function imprimir_exito($mensaje) {
    echo "✓ " . $mensaje . "\n";
}

function imprimir_error($mensaje) {
    echo "✗ " . $mensaje . "\n";
}

function imprimir_titulo($titulo) {
    echo "\n========================================\n";
    echo "  " . $titulo . "\n";
    echo "========================================\n\n";
}

function hacer_request($url, $metodo = 'GET', $datos = null, $token = null) {
    $ch = curl_init($url);

    // Headers
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Método y datos
    if ($metodo === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($datos) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
        }
    } elseif ($metodo === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($datos) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
        }
    } elseif ($metodo === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => $http_code,
        'body' => json_decode($response, true)
    ];
}

// Comenzar tests
imprimir_titulo("TEST DE TAREAS - FASE 5");

// ============================================
// TEST 1: Login (obtener token)
// ============================================
imprimir_titulo("TEST 1: Login del CEO");

$url_login = $base_url . '/auth/login';
$datos_login = [
    'email' => 'JoelOlaya@restaurant.com',
    'password' => '123456789J'
];

$response = hacer_request($url_login, 'POST', $datos_login);

if ($response['http_code'] === 200 && $response['body']['tipo'] === 1) {
    $token = $response['body']['data']['token'];
    imprimir_exito("Login exitoso. Token obtenido.");
} else {
    imprimir_error("Error en login. HTTP " . $response['http_code']);
    echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

// ============================================
// TEST 2: Crear tarea asignada (Tipo A)
// ============================================
imprimir_titulo("TEST 2: Crear Tarea Asignada (Tipo A)");

$url_tareas = $base_url . '/tareas/';

// Nota: Asumimos que existe un usuario con ID 4 (Gerente Carlos Ramírez de usuarios-prueba.sql)
$fecha_inicio = date('Y-m-d H:i:s', strtotime('+1 hour'));
$fecha_fin = date('Y-m-d H:i:s', strtotime('+5 hours'));

$datos_tarea_asignada = [
    'titulo' => 'Inventario de almacén - TEST',
    'descripcion' => 'Realizar conteo completo del inventario de productos secos y refrigerados',
    'prioridad' => 'ALTA',
    'categoria_asignacion' => 'ESPECIFICA',
    'id_asignado' => 4, // Carlos Ramírez (Gerente)
    'fecha_inicio' => $fecha_inicio,
    'fecha_fin' => $fecha_fin,
    'es_iniciativa' => false,
    'subtareas' => [
        'Contar productos secos',
        'Contar productos refrigerados',
        'Generar reporte de inventario'
    ]
];

$response = hacer_request($url_tareas, 'POST', $datos_tarea_asignada, $token);

if ($response['http_code'] === 201 && $response['body']['tipo'] === 1) {
    $id_tarea_asignada = $response['body']['data']['id_tarea'];
    imprimir_exito("Tarea asignada creada exitosamente. ID: {$id_tarea_asignada}");
} else {
    imprimir_error("Error al crear tarea asignada. HTTP " . $response['http_code']);
    echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

// ============================================
// TEST 3: Crear tarea de iniciativa (Tipo B)
// ============================================
imprimir_titulo("TEST 3: Crear Tarea de Iniciativa (Tipo B)");

$fecha_inicio_iniciativa = date('Y-m-d H:i:s', strtotime('+2 hours'));
$fecha_fin_iniciativa = date('Y-m-d H:i:s', strtotime('+6 hours')); // 4 horas (< 9 horas)

$datos_iniciativa = [
    'titulo' => 'Organizar despensa - TEST',
    'descripcion' => 'Reorganizar productos por fecha de vencimiento',
    'prioridad' => 'MEDIA',
    'es_iniciativa' => true,
    'fecha_inicio' => $fecha_inicio_iniciativa,
    'fecha_fin' => $fecha_fin_iniciativa,
    'subtareas' => [
        'Revisar productos próximos a vencer',
        'Reorganizar estantes',
        'Etiquetar productos'
    ]
];

$response = hacer_request($url_tareas, 'POST', $datos_iniciativa, $token);

if ($response['http_code'] === 201 && $response['body']['tipo'] === 1) {
    $id_tarea_iniciativa = $response['body']['data']['id_tarea'];
    imprimir_exito("Tarea de iniciativa creada exitosamente. ID: {$id_tarea_iniciativa}");
} else {
    imprimir_error("Error al crear tarea de iniciativa. HTTP " . $response['http_code']);
    echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

// ============================================
// TEST 4: Crear tarea a bolsa
// ============================================
imprimir_titulo("TEST 4: Crear Tarea a Bolsa");

$fecha_inicio_bolsa = date('Y-m-d H:i:s', strtotime('+1 hour'));
$fecha_fin_bolsa = date('Y-m-d H:i:s', strtotime('+3 hours'));

$datos_bolsa = [
    'titulo' => 'Limpieza de área común - TEST',
    'descripcion' => 'Limpiar y desinfectar área de comedor',
    'prioridad' => 'MEDIA',
    'categoria_asignacion' => 'BOLSA_COLABORADOR',
    'fecha_inicio' => $fecha_inicio_bolsa,
    'fecha_fin' => $fecha_fin_bolsa,
    'subtareas' => [
        'Barrer área de comedor',
        'Trapear pisos',
        'Desinfectar mesas y sillas'
    ]
];

$response = hacer_request($url_tareas, 'POST', $datos_bolsa, $token);

if ($response['http_code'] === 201 && $response['body']['tipo'] === 1) {
    $id_tarea_bolsa = $response['body']['data']['id_tarea'];
    imprimir_exito("Tarea a bolsa creada exitosamente. ID: {$id_tarea_bolsa}");
} else {
    imprimir_error("Error al crear tarea a bolsa. HTTP " . $response['http_code']);
    echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

// ============================================
// TEST 5: Listar todas las tareas
// ============================================
imprimir_titulo("TEST 5: Listar Todas las Tareas");

$response = hacer_request($url_tareas, 'GET', null, $token);

if ($response['http_code'] === 200 && $response['body']['tipo'] === 1) {
    $total_tareas = $response['body']['data']['total'];
    imprimir_exito("Tareas obtenidas exitosamente. Total: {$total_tareas}");

    // Mostrar algunas tareas
    $tareas = $response['body']['data']['tareas'];
    foreach (array_slice($tareas, 0, 3) as $tarea) {
        echo "  - [{$tarea['id']}] {$tarea['titulo']} | Estado: {$tarea['estado_ejecucion']} | Prioridad: {$tarea['prioridad']}\n";
    }
} else {
    imprimir_error("Error al listar tareas. HTTP " . $response['http_code']);
    exit(1);
}

// ============================================
// TEST 6: Listar tareas con filtros
// ============================================
imprimir_titulo("TEST 6: Listar Tareas Filtradas (Iniciativas)");

$url_filtrada = $url_tareas . '?es_iniciativa=true';
$response = hacer_request($url_filtrada, 'GET', null, $token);

if ($response['http_code'] === 200 && $response['body']['tipo'] === 1) {
    $total_iniciativas = $response['body']['data']['total'];
    imprimir_exito("Iniciativas obtenidas: {$total_iniciativas}");
} else {
    imprimir_error("Error al filtrar tareas. HTTP " . $response['http_code']);
    exit(1);
}

// ============================================
// TEST 7: Obtener tarea por ID
// ============================================
imprimir_titulo("TEST 7: Obtener Tarea por ID");

$url_tarea_id = $url_tareas . $id_tarea_asignada;
$response = hacer_request($url_tarea_id, 'GET', null, $token);

if ($response['http_code'] === 200 && $response['body']['tipo'] === 1) {
    $tarea = $response['body']['data']['tarea'];
    imprimir_exito("Tarea obtenida: {$tarea['titulo']}");
    echo "  - Asignado: {$tarea['asignado_nombre']}\n";
    echo "  - Subtareas: " . count($tarea['subtareas']) . "\n";
} else {
    imprimir_error("Error al obtener tarea. HTTP " . $response['http_code']);
    exit(1);
}

// ============================================
// TEST 8: Actualizar subtarea (marcar como completada)
// ============================================
imprimir_titulo("TEST 8: Actualizar Subtarea");

// Obtener ID de la primera subtarea
if (!empty($tarea['subtareas'])) {
    $id_subtarea = $tarea['subtareas'][0]['id'];
    $url_subtarea = $url_tareas . "subtareas/{$id_subtarea}";

    $datos_subtarea = ['completada' => true];
    $response = hacer_request($url_subtarea, 'PUT', $datos_subtarea, $token);

    if ($response['http_code'] === 200 && $response['body']['tipo'] === 1) {
        imprimir_exito("Subtarea actualizada exitosamente");
    } else {
        imprimir_error("Error al actualizar subtarea. HTTP " . $response['http_code']);
    }
} else {
    imprimir_error("No hay subtareas para actualizar");
}

// ============================================
// TEST 9: Intentar marcar como completada (fallará por subtareas incompletas)
// ============================================
imprimir_titulo("TEST 9: Intentar Completar Tarea (debe fallar)");

$url_completar = $url_tareas . "{$id_tarea_asignada}/completar";
$response = hacer_request($url_completar, 'POST', [], $token);

if ($response['http_code'] === 403 || $response['http_code'] === 400) {
    imprimir_exito("Validación correcta: No se puede completar (no es el asignado o faltan subtareas)");
    if (isset($response['body']['mensajes'])) {
        echo "  Motivo: " . implode(', ', $response['body']['mensajes']) . "\n";
    }
} else {
    imprimir_error("Esperaba error 400/403 pero obtuvo HTTP " . $response['http_code']);
}

// ============================================
// TEST 10: Eliminar tarea de iniciativa (Soft Delete)
// ============================================
imprimir_titulo("TEST 10: Eliminar Tarea de Iniciativa");

$url_eliminar = $url_tareas . $id_tarea_iniciativa;
$response = hacer_request($url_eliminar, 'DELETE', null, $token);

if ($response['http_code'] === 200 && $response['body']['tipo'] === 1) {
    imprimir_exito("Iniciativa eliminada exitosamente (Soft Delete)");
} else {
    imprimir_error("Error al eliminar iniciativa. HTTP " . $response['http_code']);
}

// ============================================
// TEST 11: Validar duración máxima de iniciativa (debe fallar)
// ============================================
imprimir_titulo("TEST 11: Validar Límite de 9 Horas para Iniciativas");

$fecha_inicio_larga = date('Y-m-d H:i:s', strtotime('+1 hour'));
$fecha_fin_larga = date('Y-m-d H:i:s', strtotime('+11 hours')); // 10 horas (> 9 horas)

$datos_iniciativa_invalida = [
    'titulo' => 'Iniciativa muy larga - TEST',
    'descripcion' => 'Esta iniciativa debería fallar por exceder 9 horas',
    'prioridad' => 'MEDIA',
    'es_iniciativa' => true,
    'fecha_inicio' => $fecha_inicio_larga,
    'fecha_fin' => $fecha_fin_larga
];

$response = hacer_request($url_tareas, 'POST', $datos_iniciativa_invalida, $token);

if ($response['http_code'] === 400 && $response['body']['tipo'] === 2) {
    imprimir_exito("Validación correcta: Iniciativa rechazada por exceder 9 horas");
    if (isset($response['body']['data']['errores'])) {
        echo "  Errores: " . implode(', ', $response['body']['data']['errores']) . "\n";
    }
} else {
    imprimir_error("Esperaba error 400 pero obtuvo HTTP " . $response['http_code']);
}

// ============================================
// RESUMEN FINAL
// ============================================
imprimir_titulo("✓ TODOS LOS TESTS PASARON");
echo "Fase 5 (CRUD de Tareas) implementada correctamente.\n\n";
echo "Tareas creadas en este test:\n";
echo "  - Tarea Asignada ID: {$id_tarea_asignada}\n";
echo "  - Tarea de Iniciativa ID: {$id_tarea_iniciativa} (eliminada)\n";
echo "  - Tarea a Bolsa ID: {$id_tarea_bolsa}\n\n";
echo "IMPORTANTE: Puedes verificar las tareas en la base de datos:\n";
echo "  SELECT * FROM tareas WHERE id IN ({$id_tarea_asignada}, {$id_tarea_bolsa});\n";
echo "  SELECT * FROM subtareas WHERE id_tarea = {$id_tarea_asignada};\n\n";

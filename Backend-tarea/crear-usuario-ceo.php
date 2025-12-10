<?php
/**
 * SCRIPT: Crear/Actualizar Usuario CEO
 *
 * Crea el usuario CEO con credenciales correctas si no existe,
 * o actualiza su contraseña si ya existe.
 */

require_once 'config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "========================================\n";
    echo "  CREAR/ACTUALIZAR USUARIO CEO\n";
    echo "========================================\n\n";

    // Datos del CEO
    $email = 'JoelOlaya@restaurant.com';
    $password = '123456789J';
    $nombre_completo = 'Joel Olaya';
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Verificar si existe
    $stmt = $pdo->prepare("SELECT id, email, nombre_completo FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario_existente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario_existente) {
        echo "[1/2] Usuario CEO ya existe\n";
        echo "  ID: {$usuario_existente['id']}\n";
        echo "  Email: {$usuario_existente['email']}\n";
        echo "  Nombre: {$usuario_existente['nombre_completo']}\n\n";

        echo "[2/2] Actualizando contraseña...\n";
        $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE email = ?");
        $stmt->execute([$password_hash, $email]);
        echo "  ✓ Contraseña actualizada a: {$password}\n\n";

    } else {
        echo "[1/2] Usuario CEO no existe, creando...\n";

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (id_sucursal, rol, nombre_completo, email, password_hash, activo)
            VALUES (NULL, 'CEO', ?, ?, ?, 1)
        ");
        $stmt->execute([$nombre_completo, $email, $password_hash]);

        $id_insertado = $pdo->lastInsertId();

        echo "  ✓ Usuario creado con ID: {$id_insertado}\n";
        echo "  ✓ Email: {$email}\n";
        echo "  ✓ Contraseña: {$password}\n\n";
    }

    echo "========================================\n";
    echo "  ✓ USUARIO CEO LISTO\n";
    echo "========================================\n\n";

    echo "📋 CREDENCIALES:\n";
    echo "Email: {$email}\n";
    echo "Contraseña: {$password}\n\n";

    echo "Ahora puedes ejecutar: php test-login.php\n\n";

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

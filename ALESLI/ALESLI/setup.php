<?php
/**
 * setup.php — Instalador web para Florería Alesli
 * Abrí http://localhost/ALESLI/setup.php en tu navegador para instalar la base de datos.
 * BORRÁ este archivo después de instalar.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'floreria_alesli');

$log    = [];
$error  = null;
$done   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Conectar sin seleccionar BD
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $log[] = "✅ Conexión a MySQL exitosa.";

        // 2. Crear base de datos
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `" . DB_NAME . "`");
        $log[] = "✅ Base de datos '" . DB_NAME . "' creada / verificada.";

        // 3. Leer y ejecutar el SQL
        $sql = file_get_contents(__DIR__ . '/db/setup.sql');
        // Separar statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        $created = 0;
        foreach ($statements as $stmt) {
            if ($stmt && !str_starts_with(ltrim($stmt), '--')) {
                $pdo->exec($stmt);
                $created++;
            }
        }
        $log[] = "✅ $created sentencias SQL ejecutadas correctamente.";
        $log[] = "✅ Tablas y datos de prueba creados.";
        $log[] = "─────────────────────────────────────────";
        $log[] = "🎉 Instalación completada. Podés ingresar en:";
        $log[] = "   👉 <a href='/ALESLI/index.php'>/ALESLI/index.php</a>";
        $log[] = "";
        $log[] = "👤 Admin:    admin@alesli.com    / admin123";
        $log[] = "👤 Empleado: florencia@alesli.com / empleado123";
        $done = true;

    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalador — Alesli</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
body { background:#f8f9fa; display:flex; align-items:center; justify-content:center; min-height:100vh; }
.card { max-width:540px; width:100%; border-radius:20px; border:0; box-shadow:0 8px 32px rgba(0,0,0,.1); }
.brand { font-size:2.5rem; }
pre { background:#1a1a2e; color:#a8ff78; border-radius:12px; padding:1rem; font-size:.8rem; }
</style>
</head>
<body>
<div class="card p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="brand">🌸</div>
        <h3 class="fw-bold">Florería Alesli</h3>
        <p class="text-muted">Instalador del sistema — XAMPP</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger rounded-3">
        <strong>❌ Error:</strong> <?= htmlspecialchars($error) ?>
        <hr>
        <small>Verificá que XAMPP esté corriendo y que las credenciales en <code>includes/db.php</code> sean correctas.</small>
    </div>
    <?php endif; ?>

    <?php if ($log): ?>
    <pre><?= implode("\n", $log) ?></pre>
    <?php endif; ?>

    <?php if (!$done): ?>
    <div class="alert alert-warning rounded-3 mb-4" style="font-size:.875rem">
        <strong>⚠️ Antes de continuar:</strong>
        <ul class="mb-0 mt-1">
            <li>XAMPP debe estar corriendo (Apache + MySQL)</li>
            <li>Si ya tenés la BD, se sobreescribirán los datos de prueba</li>
            <li>Credenciales MySQL: <code>root</code> sin contraseña (por defecto en XAMPP)</li>
        </ul>
    </div>
    <form method="POST">
        <button type="submit" class="btn btn-danger w-100 rounded-3 py-2 fw-bold">
            🚀 Instalar base de datos
        </button>
    </form>
    <?php else: ?>
    <a href="/ALESLI/index.php" class="btn btn-success w-100 rounded-3 py-2 fw-bold">
        Ir al sistema →
    </a>
    <p class="text-center text-muted mt-3" style="font-size:.78rem">
        ⚠️ Eliminá <code>setup.php</code> después de instalar por seguridad.
    </p>
    <?php endif; ?>
</div>
</body>
</html>

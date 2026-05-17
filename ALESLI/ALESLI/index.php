<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

startSession();
$user = getUser();
if ($user) {
    header('Location: /ALESLI/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if ($u && password_verify($pass, $u['contrasena'])) {
            $_SESSION['user'] = [
                'id'     => $u['id_usuario'],
                'nombre' => $u['nombre'],
                'email'  => $u['email'],
                'rol'    => $u['rol'],
            ];
            header('Location: /ALESLI/dashboard.php');
            exit;
        }
        $error = 'Email o contraseña incorrectos.';
    } catch (PDOException $e) {
        $error = 'Error de conexión a la base de datos. Verificá que MySQL esté activo en XAMPP.';
    }
}

renderHead('Iniciar sesión');
?>
<div class="login-page">
    <div class="login-deco order-last order-md-first">
        <div class="text-center" style="position:relative">
            <div style="font-size:4rem;margin-bottom:1rem">🌸</div>
            <h2 class="fw-bold">Alesli</h2>
            <p class="mt-2 mb-4">Sistema de Gestión Operativa<br>para Florería</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:.75rem 1.25rem;text-align:center">
                    <div style="font-size:1.4rem;font-weight:800;color:#fff">Pedidos</div>
                    <div style="font-size:.7rem;color:rgba(255,255,255,.6)">Gestión completa</div>
                </div>
                <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:.75rem 1.25rem;text-align:center">
                    <div style="font-size:1.4rem;font-weight:800;color:#fff">Inventario</div>
                    <div style="font-size:.7rem;color:rgba(255,255,255,.6)">Control de stock</div>
                </div>
                <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:.75rem 1.25rem;text-align:center">
                    <div style="font-size:1.4rem;font-weight:800;color:#fff">Cursos</div>
                    <div style="font-size:.7rem;color:rgba(255,255,255,.6)">Inscripciones</div>
                </div>
            </div>
        </div>
    </div>
    <div class="login-panel">
        <div style="max-width:360px;width:100%;margin:auto">
            <div class="mb-4">
                <h3 class="fw-bold mb-1">Bienvenida 🌷</h3>
                <p class="text-muted" style="font-size:.875rem">Ingresá con tus credenciales para continuar</p>
            </div>
            <?php if ($error): ?>
            <div class="alert-error-custom mb-4">⚠️ <?= h($error) ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="on">
                <div class="mb-3">
                    <label class="form-label-sm d-block">Correo electrónico</label>
                    <input type="email" name="email" class="form-control rounded-3"
                           placeholder="admin@alesli.com"
                           value="<?= h($_POST['email'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label-sm d-block">Contraseña</label>
                    <input type="password" name="password" class="form-control rounded-3"
                           placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-rose w-100 justify-content-center py-2">
                    Ingresar →
                </button>
            </form>
            <hr class="my-4">
            <div class="p-3 rounded-3" style="background:#f8f9fa;font-size:.78rem;color:#666">
                <strong>Usuarios de prueba:</strong><br>
                🔴 admin@alesli.com / admin123<br>
                🟡 florencia@alesli.com / empleado123
            </div>
        </div>
    </div>
</div>
<?php renderFoot(); ?>

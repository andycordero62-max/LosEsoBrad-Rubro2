<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

startSession();
$user = getUser();
if ($user) {
    if ($user['rol'] === 'cliente') { header('Location: /cliente.php'); }
    else { header('Location: /dashboard.php'); }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pdo   = getDB();
    $stmt  = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($pass, $u['password_hash'])) {
        $_SESSION['user'] = ['id' => $u['id'], 'nombre' => $u['nombre'], 'email' => $u['email'], 'rol' => $u['rol']];
        header($u['rol'] === 'cliente' ? 'Location: /cliente.php' : 'Location: /dashboard.php');
        exit;
    }
    $error = 'Email o contraseña incorrectos.';
}
renderHead('Iniciar sesión');
?>
<div class="login-page">
    <!-- Decorative panel -->
    <div class="login-deco order-last order-md-first">
        <div class="text-center" style="position:relative">
            <div style="font-size:4rem;margin-bottom:1rem">🌸</div>
            <h2 class="fw-bold">Alesli</h2>
            <p class="mt-2 mb-4">Sistema de gestión de pedidos<br>y entregas para florería</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:.75rem 1.25rem;text-align:center">
                    <div style="font-size:1.5rem;font-weight:800;color:#fff">500+</div>
                    <div style="font-size:.72rem;color:rgba(255,255,255,.6)">Pedidos</div>
                </div>
                <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:.75rem 1.25rem;text-align:center">
                    <div style="font-size:1.5rem;font-weight:800;color:#fff">120+</div>
                    <div style="font-size:.72rem;color:rgba(255,255,255,.6)">Clientes</div>
                </div>
                <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:.75rem 1.25rem;text-align:center">
                    <div style="font-size:1.5rem;font-weight:800;color:#fff">99%</div>
                    <div style="font-size:.72rem;color:rgba(255,255,255,.6)">Entregas</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Login form -->
    <div class="login-panel">
        <div style="max-width:360px;width:100%;margin:auto">
            <div class="mb-4">
                <h3 class="fw-bold mb-1">Bienvenido 👋</h3>
                <p class="text-muted" style="font-size:.875rem">Ingresá con tus credenciales para continuar</p>
            </div>
            <?php if ($error): ?>
            <div class="alert-error-custom mb-4">⚠️ <?= h($error) ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="on">
                <div class="mb-3">
                    <label class="form-label-sm d-block">Correo electrónico</label>
                    <input type="email" name="email" class="form-control" placeholder="admin@alesli.com"
                           value="<?= h($_POST['email'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label-sm d-block">Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-rose w-100 justify-content-center py-2">
                    Ingresar →
                </button>
            </form>
            <hr class="my-4">
            <div class="p-3 rounded-3" style="background:#f8f9fa;font-size:.78rem;color:#666">
                <strong>Usuarios de prueba:</strong><br>
                🔴 admin@alesli.com / admin123<br>
                🟡 florencia@alesli.com / empleado123<br>
                🟢 maria@gmail.com / cliente123
            </div>
        </div>
    </div>
</div>
<?php renderFoot(); ?>

<?php
function renderHead(string $title = 'Alesli'): void { ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title) ?> — Alesli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php }

function renderSidebar(array $user, string $active = ''): void {
    $inicial = mb_strtoupper(mb_substr($user['nombre'], 0, 1));
    $rol = ucfirst($user['rol']);
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🌸</div>
        <h5>Alesli</h5>
        <small>Gestión de pedidos</small>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Principal</div>
        <a href="/dashboard.php" class="nav-link <?= $active==='dashboard' ? 'active' : '' ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        <a href="/pedidos.php" class="nav-link <?= $active==='pedidos' ? 'active' : '' ?>">
            <span class="icon">📦</span> Pedidos
        </a>
        <a href="/nuevo-pedido.php" class="nav-link <?= $active==='nuevo-pedido' ? 'active' : '' ?>">
            <span class="icon">➕</span> Nuevo Pedido
        </a>
        <a href="/catalogo.php" class="nav-link <?= $active==='catalogo' ? 'active' : '' ?>">
            <span class="icon">🌺</span> Catálogo
        </a>
        <?php if ($user['rol'] === 'admin'): ?>
        <div class="nav-label mt-2">Administración</div>
        <a href="/admin.php" class="nav-link <?= $active==='admin' ? 'active' : '' ?>">
            <span class="icon">👥</span> Usuarios
        </a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="avatar"><?= h($inicial) ?></div>
            <div>
                <div class="name"><?= h($user['nombre']) ?></div>
                <div class="role"><?= h($rol) ?></div>
            </div>
            <a href="/logout.php" title="Salir">⏻</a>
        </div>
    </div>
</aside>
<?php }

function renderFoot(): void { ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php }

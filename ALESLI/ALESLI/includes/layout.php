<?php
function renderHead(string $title = 'Alesli', bool $charts = false): void { ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title) ?> — Florería Alesli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/ALESLI/css/style.css">
    <?php if ($charts): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <?php endif; ?>
</head>
<body>
<?php }

function renderSidebar(array $user, string $active = ''): void {
    $inicial = mb_strtoupper(mb_substr($user['nombre'], 0, 1));
    $rol     = ucfirst($user['rol']);
    $isAdmin = $user['rol'] === 'admin';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🌸</div>
        <h5>Alesli</h5>
        <small>Sistema de Gestión</small>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Principal</div>
        <a href="/ALESLI/dashboard.php"    class="nav-link <?= $active==='dashboard'    ? 'active' : '' ?>"><span class="icon">📊</span> Dashboard</a>
        <a href="/ALESLI/pedidos.php"      class="nav-link <?= $active==='pedidos'      ? 'active' : '' ?>"><span class="icon">📋</span> Pedidos</a>
        <a href="/ALESLI/nuevo-pedido.php" class="nav-link <?= $active==='nuevo-pedido' ? 'active' : '' ?>"><span class="icon">➕</span> Nuevo Pedido</a>
        <a href="/ALESLI/catalogo.php"     class="nav-link <?= $active==='catalogo'     ? 'active' : '' ?>"><span class="icon">🌺</span> Catálogo</a>

        <div class="nav-label mt-2">Gestión</div>
        <a href="/ALESLI/clientes.php"     class="nav-link <?= $active==='clientes'     ? 'active' : '' ?>"><span class="icon">👤</span> Clientes</a>
        <a href="/ALESLI/inventario.php"   class="nav-link <?= $active==='inventario'   ? 'active' : '' ?>"><span class="icon">📦</span> Inventario</a>
        <a href="/ALESLI/contabilidad.php" class="nav-link <?= $active==='contabilidad' ? 'active' : '' ?>"><span class="icon">💰</span> Contabilidad</a>
        <a href="/ALESLI/cursos.php"       class="nav-link <?= $active==='cursos'       ? 'active' : '' ?>"><span class="icon">🎓</span> Cursos</a>

        <?php if ($isAdmin): ?>
        <div class="nav-label mt-2">Administración</div>
        <a href="/ALESLI/admin.php"        class="nav-link <?= $active==='admin'        ? 'active' : '' ?>"><span class="icon">👥</span> Usuarios</a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="avatar"><?= h($inicial) ?></div>
            <div>
                <div class="name"><?= h($user['nombre']) ?></div>
                <div class="role"><?= h($rol) ?></div>
            </div>
            <a href="/ALESLI/logout.php" title="Cerrar sesión">⏻</a>
        </div>
    </div>
</aside>
<?php }

function renderFoot(string $extraJs = ''): void { ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($extraJs) echo $extraJs; ?>
</body>
</html>
<?php }

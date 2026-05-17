<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = requireRole('admin', 'empleado');
$pdo  = getDB();
$hoy  = date('Y-m-d');

$total    = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE fecha_entrega = '$hoy'")->fetchColumn();
$pend     = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente' AND fecha_entrega = '$hoy'")->fetchColumn();
$entregados = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'entregado' AND fecha_entrega = '$hoy'")->fetchColumn();
$totalAll = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();

$recientes = $pdo->query("SELECT * FROM pedidos ORDER BY fecha_registro DESC LIMIT 8")->fetchAll();

renderHead('Dashboard');
renderSidebar($user, 'dashboard');
?>
<div class="main-content">
    <div class="topbar">
        <h1>📊 Dashboard</h1>
        <span style="font-size:.8rem;color:#888"><?= date('l d \d\e F Y') ?></span>
    </div>
    <div class="page-body">

        <div class="hero-banner">
            <div style="position:relative">
                <h2 class="fw-bold mb-1">¡Hola, <?= h(explode(' ', $user['nombre'])[0]) ?>! 🌸</h2>
                <p style="color:rgba(255,255,255,.75);margin:0">Resumen de actividad del día de hoy</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fef3c7">📦</div>
                    <div class="stat-val"><?= $total ?></div>
                    <div class="stat-label">Pedidos hoy</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fee2e2">⏳</div>
                    <div class="stat-val"><?= $pend ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#d1fae5">✅</div>
                    <div class="stat-val"><?= $entregados ?></div>
                    <div class="stat-label">Entregados hoy</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#ede9fe">📋</div>
                    <div class="stat-val"><?= $totalAll ?></div>
                    <div class="stat-label">Total general</div>
                </div>
            </div>
        </div>

        <!-- Recent orders -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0">Pedidos recientes</h5>
            <a href="/pedidos.php" class="btn-rose" style="font-size:.78rem;padding:.4rem .9rem">Ver todos →</a>
        </div>
        <div class="table-card">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>#</th><th>Cliente</th><th>Producto</th><th>Entrega</th><th>Estado</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recientes as $p): ?>
                <tr>
                    <td class="text-muted">#<?= $p['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= h($p['nombre_cliente']) ?></div>
                        <div style="font-size:.75rem;color:#888"><?= h($p['telefono']) ?></div>
                    </td>
                    <td><?= h($p['producto']) ?></td>
                    <td><?= date('d/m/Y', strtotime($p['fecha_entrega'])) ?><?= $p['hora_entrega'] ? ' ' . h($p['hora_entrega']) : '' ?></td>
                    <td><span class="badge-<?= h($p['estado']) ?>"><?= ucfirst(h($p['estado'])) ?></span></td>
                    <td><a href="/pedido.php?id=<?= $p['id'] ?>" style="font-size:.8rem;color:var(--rose-600)">Ver →</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recientes)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No hay pedidos aún</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php renderFoot(); ?>

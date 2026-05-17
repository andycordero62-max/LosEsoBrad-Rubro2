<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = requireRole('admin', 'empleado');
$pdo  = getDB();
$hoy  = date('Y-m-d');
$mes  = date('Y-m');

// ── Pedidos ──
$totalHoy    = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE fecha_entrega = ?");
$totalHoy->execute([$hoy]); $totalHoy = (int)$totalHoy->fetchColumn();

$pendHoy     = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente' AND fecha_entrega = ?");
$pendHoy->execute([$hoy]); $pendHoy = (int)$pendHoy->fetchColumn();

$entregadosHoy = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE estado = 'entregado' AND fecha_entrega = ?");
$entregadosHoy->execute([$hoy]); $entregadosHoy = (int)$entregadosHoy->fetchColumn();

$totalPedidos = (int)$pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();

// ── Finanzas del mes ──
$ingresosMes = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM transacciones WHERE tipo='ingreso' AND DATE_FORMAT(fecha,'%Y-%m')=?");
$ingresosMes->execute([$mes]); $ingresosMes = (float)$ingresosMes->fetchColumn();

$egresosMes  = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM transacciones WHERE tipo='egreso'  AND DATE_FORMAT(fecha,'%Y-%m')=?");
$egresosMes->execute([$mes]); $egresosMes = (float)$egresosMes->fetchColumn();

$balanceMes  = $ingresosMes - $egresosMes;

// ── Alertas de stock bajo ──
$stockBajo = $pdo->query("SELECT * FROM materiales WHERE stock_actual <= stock_minimo ORDER BY stock_actual ASC LIMIT 5")->fetchAll();

// ── Pedidos recientes ──
$recientes = $pdo->query("
    SELECT p.*, c.nombre AS cliente_nombre
    FROM pedidos p
    LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
    ORDER BY p.fecha_registro DESC LIMIT 8
")->fetchAll();

// ── Datos para gráfico de ingresos/egresos últimos 7 días ──
$chartData = $pdo->query("
    SELECT DATE(fecha) as dia,
           SUM(CASE WHEN tipo='ingreso' THEN monto ELSE 0 END) as ingresos,
           SUM(CASE WHEN tipo='egreso'  THEN monto ELSE 0 END) as egresos
    FROM transacciones
    WHERE fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(fecha)
    ORDER BY dia
")->fetchAll();

$chartLabels  = json_encode(array_column($chartData, 'dia'));
$chartIngreso = json_encode(array_map('floatval', array_column($chartData, 'ingresos')));
$chartEgreso  = json_encode(array_map('floatval', array_column($chartData, 'egresos')));

// ── Próximos cursos ──
$proxCursos = $pdo->query("
    SELECT c.*, COUNT(i.id_inscripcion) AS inscritos
    FROM cursos c
    LEFT JOIN inscripciones i ON i.id_curso = c.id_curso
    WHERE c.fecha_inicio >= CURDATE() AND c.activo=1
    GROUP BY c.id_curso
    ORDER BY c.fecha_inicio LIMIT 3
")->fetchAll();

renderHead('Dashboard', true);
renderSidebar($user, 'dashboard');
?>
<div class="main-content">
    <div class="topbar">
        <h1>📊 Dashboard</h1>
        <span style="font-size:.8rem;color:#888"><?= date('d/m/Y') ?></span>
    </div>
    <div class="page-body">

        <!-- Banner -->
        <div class="hero-banner">
            <h2 class="fw-bold mb-1">¡Hola, <?= h(explode(' ', $user['nombre'])[0]) ?>! 🌸</h2>
            <p style="color:rgba(255,255,255,.75);margin:0">Resumen del día — <?= date('d \d\e F') ?></p>
        </div>

        <!-- Alertas de stock -->
        <?php if (!empty($stockBajo)): ?>
        <div class="stock-alert-bar mb-3">
            ⚠️ <strong><?= count($stockBajo) ?> material(es) con stock bajo:</strong>
            <?= implode(', ', array_map(fn($m) => h($m['nombre']) . ' (' . $m['stock_actual'] . ')', $stockBajo)) ?>
            — <a href="/ALESLI/inventario.php" style="color:#92400e;font-weight:700">Ver inventario →</a>
        </div>
        <?php endif; ?>

        <!-- Stats pedidos del día -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fef3c7">📦</div>
                    <div class="stat-val"><?= $totalHoy ?></div>
                    <div class="stat-label">Pedidos hoy</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fee2e2">⏳</div>
                    <div class="stat-val"><?= $pendHoy ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#d1fae5">✅</div>
                    <div class="stat-val"><?= $entregadosHoy ?></div>
                    <div class="stat-label">Entregados</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#ede9fe">📋</div>
                    <div class="stat-val"><?= $totalPedidos ?></div>
                    <div class="stat-label">Total general</div>
                </div>
            </div>
        </div>

        <!-- Stats finanzas del mes -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#d1fae5">💰</div>
                    <div class="stat-val" style="font-size:1.4rem">Bs. <?= number_format($ingresosMes, 2) ?></div>
                    <div class="stat-label">Ingresos del mes</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fee2e2">💸</div>
                    <div class="stat-val" style="font-size:1.4rem">Bs. <?= number_format($egresosMes, 2) ?></div>
                    <div class="stat-label">Egresos del mes</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:<?= $balanceMes >= 0 ? '#d1fae5' : '#fee2e2' ?>">
                        <?= $balanceMes >= 0 ? '📈' : '📉' ?>
                    </div>
                    <div class="stat-val" style="font-size:1.4rem;color:<?= $balanceMes >= 0 ? '#065f46' : '#991b1b' ?>">
                        Bs. <?= number_format(abs($balanceMes), 2) ?>
                    </div>
                    <div class="stat-label">Balance del mes <?= $balanceMes < 0 ? '(negativo)' : '' ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Gráfico ingresos/egresos -->
            <div class="col-md-8">
                <div class="chart-card">
                    <h6>📈 Ingresos vs Egresos — Últimos 7 días</h6>
                    <canvas id="chartFinanzas" height="90"></canvas>
                </div>
            </div>
            <!-- Próximos cursos -->
            <div class="col-md-4">
                <div class="chart-card h-100">
                    <h6>🎓 Próximos Cursos</h6>
                    <?php if (empty($proxCursos)): ?>
                    <p class="text-muted" style="font-size:.85rem">No hay cursos próximos.</p>
                    <?php endif; ?>
                    <?php foreach ($proxCursos as $c): ?>
                    <div class="mb-3 p-2 rounded-3" style="background:#f8f9fa;border:1px solid #eee">
                        <div class="fw-semibold" style="font-size:.875rem"><?= h($c['nombre']) ?></div>
                        <div style="font-size:.75rem;color:#888">
                            📅 <?= date('d/m/Y', strtotime($c['fecha_inicio'])) ?> &bull;
                            👥 <?= $c['inscritos'] ?>/<?= $c['cupo_maximo'] ?>
                        </div>
                        <div style="font-size:.8rem;color:var(--rose-600);font-weight:700">Bs. <?= number_format($c['precio'], 2) ?></div>
                    </div>
                    <?php endforeach; ?>
                    <a href="/ALESLI/cursos.php" style="font-size:.8rem;color:var(--rose-600)">Ver todos los cursos →</a>
                </div>
            </div>
        </div>

        <!-- Pedidos recientes -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0">Pedidos recientes</h5>
            <a href="/ALESLI/pedidos.php" class="btn-rose" style="font-size:.78rem;padding:.4rem .9rem">Ver todos →</a>
        </div>
        <div class="table-card">
            <table class="table mb-0">
                <thead>
                    <tr><th>#</th><th>Cliente</th><th>Producto</th><th>Entrega</th><th>Estado</th><th>Monto</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($recientes as $p): ?>
                <tr>
                    <td class="text-muted">#<?= $p['id_pedido'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= h($p['nombre_cliente']) ?></div>
                        <div style="font-size:.72rem;color:#888"><?= h($p['telefono'] ?? '') ?></div>
                    </td>
                    <td><?= h($p['producto']) ?></td>
                    <td><?= date('d/m/Y', strtotime($p['fecha_entrega'])) ?><?= $p['hora_entrega'] ? ' ' . h(substr($p['hora_entrega'],0,5)) : '' ?></td>
                    <td><span class="badge-<?= h($p['estado']) ?>"><?= ucfirst(h($p['estado'])) ?></span></td>
                    <td style="font-size:.82rem"><?= $p['monto'] ? 'Bs. '.number_format($p['monto'],2) : '—' ?></td>
                    <td><a href="/ALESLI/pedido.php?id=<?= $p['id_pedido'] ?>" style="font-size:.8rem;color:var(--rose-600)">Ver →</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recientes)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay pedidos aún</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
const ctx = document.getElementById('chartFinanzas').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [
            { label: 'Ingresos', data: <?= $chartIngreso ?>, backgroundColor: 'rgba(16,185,129,.7)', borderRadius: 6 },
            { label: 'Egresos',  data: <?= $chartEgreso  ?>, backgroundColor: 'rgba(239,68,68,.65)', borderRadius: 6 }
        ]
    },
    options: {
        responsive:true, plugins:{ legend:{ position:'top' } },
        scales:{ y:{ beginAtZero:true, ticks:{ callback:v=>'Bs.'+v } } }
    }
});
</script>
<?php renderFoot(); ?>

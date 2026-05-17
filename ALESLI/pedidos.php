<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = requireRole('admin', 'empleado');
$pdo  = getDB();

$estado = $_GET['estado'] ?? '';
$fecha  = $_GET['fecha']  ?? '';

$where = [];
$params = [];
if ($estado) { $where[] = "estado = ?"; $params[] = $estado; }
if ($fecha)  { $where[] = "fecha_entrega = ?"; $params[] = $fecha; }

$sql = "SELECT * FROM pedidos" . ($where ? " WHERE " . implode(" AND ", $where) : "") . " ORDER BY fecha_entrega ASC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

renderHead('Pedidos');
renderSidebar($user, 'pedidos');
?>
<div class="main-content">
    <div class="topbar">
        <h1>📦 Pedidos</h1>
        <a href="/nuevo-pedido.php" class="btn-rose"><span>➕</span> Nuevo pedido</a>
    </div>
    <div class="page-body">
        <!-- Filters -->
        <form method="GET" class="d-flex gap-2 flex-wrap mb-4 align-items-end">
            <div>
                <label class="form-label-sm d-block">Estado</label>
                <select name="estado" class="form-select" style="min-width:150px">
                    <option value="">Todos</option>
                    <option value="pendiente"  <?= $estado==='pendiente'  ? 'selected' : '' ?>>Pendiente</option>
                    <option value="entregado"  <?= $estado==='entregado'  ? 'selected' : '' ?>>Entregado</option>
                    <option value="cancelado"  <?= $estado==='cancelado'  ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
            <div>
                <label class="form-label-sm d-block">Fecha de entrega</label>
                <input type="date" name="fecha" value="<?= h($fecha) ?>" class="form-control">
            </div>
            <button type="submit" class="btn-rose">Filtrar</button>
            <?php if ($estado || $fecha): ?>
            <a href="/pedidos.php" class="btn btn-outline-secondary rounded-3" style="font-size:.875rem">✕ Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="table-card">
            <table class="table mb-0">
                <thead>
                    <tr><th>#</th><th>Cliente</th><th>Producto</th><th>Entrega</th><th>Dirección</th><th>Estado</th><th>Pago</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($pedidos as $p): ?>
                <tr>
                    <td class="text-muted fw-semibold">#<?= $p['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= h($p['nombre_cliente']) ?></div>
                        <div style="font-size:.75rem;color:#888"><?= h($p['telefono']) ?></div>
                    </td>
                    <td><?= h($p['producto']) ?></td>
                    <td>
                        <div><?= date('d/m/Y', strtotime($p['fecha_entrega'])) ?></div>
                        <?php if ($p['hora_entrega']): ?><div style="font-size:.75rem;color:#888"><?= h($p['hora_entrega']) ?></div><?php endif; ?>
                    </td>
                    <td style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($p['direccion_entrega']) ?></td>
                    <td><span class="badge-<?= h($p['estado']) ?>"><?= ucfirst(h($p['estado'])) ?></span></td>
                    <td style="font-size:.8rem;color:#888"><?= h($p['medio_pago'] ?? '—') ?></td>
                    <td><a href="/pedido.php?id=<?= $p['id'] ?>" class="btn-rose" style="font-size:.75rem;padding:.3rem .75rem">Ver</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pedidos)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">No hay pedidos con esos filtros</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3 text-muted" style="font-size:.8rem"><?= count($pedidos) ?> pedido<?= count($pedidos)!==1?'s':'' ?> encontrado<?= count($pedidos)!==1?'s':'' ?></div>
    </div>
</div>
<?php renderFoot(); ?>

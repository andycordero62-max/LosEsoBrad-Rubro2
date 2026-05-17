<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = requireRole('admin', 'empleado');
$pdo  = getDB();

$estado = $_GET['estado'] ?? '';
$fecha  = $_GET['fecha']  ?? '';
$q      = trim($_GET['q'] ?? '');

$where  = [];
$params = [];
if ($estado) { $where[] = "p.estado = ?";         $params[] = $estado; }
if ($fecha)  { $where[] = "p.fecha_entrega = ?";   $params[] = $fecha;  }
if ($q)      { $where[] = "(p.nombre_cliente LIKE ? OR p.producto LIKE ? OR p.telefono LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }

$sql  = "SELECT p.*, c.nombre AS cliente_nombre FROM pedidos p LEFT JOIN clientes c ON c.id_cliente = p.id_cliente" .
        ($where ? " WHERE " . implode(" AND ", $where) : "") .
        " ORDER BY p.fecha_entrega ASC, p.id_pedido DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

renderHead('Pedidos');
renderSidebar($user, 'pedidos');
?>
<div class="main-content">
    <div class="topbar">
        <h1>📦 Pedidos</h1>
        <a href="/ALESLI/nuevo-pedido.php" class="btn-rose"><span>➕</span> Nuevo pedido</a>
    </div>
    <div class="page-body">
        <!-- Filtros -->
        <form method="GET" class="d-flex gap-2 flex-wrap mb-4 align-items-end">
            <div>
                <label class="form-label-sm d-block">Buscar</label>
                <input type="text" name="q" value="<?= h($q) ?>" class="form-control" placeholder="Cliente, producto…" style="min-width:180px">
            </div>
            <div>
                <label class="form-label-sm d-block">Estado</label>
                <select name="estado" class="form-select" style="min-width:140px">
                    <option value="">Todos</option>
                    <?php foreach (['pendiente','preparando','enviado','entregado','cancelado'] as $st): ?>
                    <option value="<?= $st ?>" <?= $estado===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label-sm d-block">Fecha de entrega</label>
                <input type="date" name="fecha" value="<?= h($fecha) ?>" class="form-control">
            </div>
            <button type="submit" class="btn-rose">Filtrar</button>
            <?php if ($estado || $fecha || $q): ?>
            <a href="/ALESLI/pedidos.php" class="btn btn-outline-secondary rounded-3" style="font-size:.875rem">✕ Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="table-card">
            <table class="table mb-0">
                <thead>
                    <tr><th>#</th><th>Cliente</th><th>Producto</th><th>Entrega</th><th>Dirección</th><th>Estado</th><th>Monto</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($pedidos as $p): ?>
                <tr>
                    <td class="text-muted fw-semibold">#<?= $p['id_pedido'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= h($p['nombre_cliente']) ?></div>
                        <div style="font-size:.72rem;color:#888"><?= h($p['telefono'] ?? '') ?></div>
                    </td>
                    <td><?= h($p['producto']) ?></td>
                    <td>
                        <div><?= date('d/m/Y', strtotime($p['fecha_entrega'])) ?></div>
                        <?php if ($p['hora_entrega']): ?><div style="font-size:.72rem;color:#888"><?= h(substr($p['hora_entrega'],0,5)) ?></div><?php endif; ?>
                    </td>
                    <td style="max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:.82rem"><?= h($p['direccion_entrega']) ?></td>
                    <td><span class="badge-<?= h($p['estado']) ?>"><?= ucfirst(h($p['estado'])) ?></span></td>
                    <td style="font-size:.82rem"><?= $p['monto'] ? 'Bs. '.number_format($p['monto'],2) : '—' ?></td>
                    <td><a href="/ALESLI/pedido.php?id=<?= $p['id_pedido'] ?>" class="btn-rose" style="font-size:.75rem;padding:.3rem .75rem">Ver</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pedidos)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">No hay pedidos con esos filtros.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-2 text-muted" style="font-size:.78rem"><?= count($pedidos) ?> pedido<?= count($pedidos)!==1?'s':'' ?></div>
    </div>
</div>
<?php renderFoot(); ?>

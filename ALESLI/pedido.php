<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = requireRole('admin', 'empleado');
$pdo  = getDB();
$id   = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT p.*, c.imagen_url FROM pedidos p LEFT JOIN catalogo_arreglos c ON c.id = p.id_catalogo_arreglo WHERE p.id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { header('Location: /pedidos.php'); exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'entregar') {
        $foto  = trim($_POST['foto_url'] ?? '');
        $notas = trim($_POST['notas'] ?? '');
        $upd   = $pdo->prepare("UPDATE pedidos SET estado='entregado', foto_evidencia_url=?, notas=? WHERE id=?");
        $upd->execute([$foto ?: null, $notas ?: null, $id]);
        $msg = 'Pedido marcado como entregado.';
    } elseif ($action === 'cancelar') {
        $pdo->prepare("UPDATE pedidos SET estado='cancelado' WHERE id=?")->execute([$id]);
        $msg = 'Pedido cancelado.';
    } elseif ($action === 'pendiente') {
        $pdo->prepare("UPDATE pedidos SET estado='pendiente' WHERE id=?")->execute([$id]);
        $msg = 'Pedido vuelto a pendiente.';
    }
    // Reload
    $stmt->execute([$id]);
    $p = $stmt->fetch();
}

renderHead("Pedido #$id");
renderSidebar($user, 'pedidos');
?>
<div class="main-content">
    <div class="topbar">
        <h1>📋 Pedido #<?= $id ?></h1>
        <a href="/pedidos.php" style="color:#888;font-size:.875rem;text-decoration:none">← Volver a pedidos</a>
    </div>
    <div class="page-body">
        <?php if ($msg): ?>
        <div class="alert-success-custom mb-4">✅ <?= h($msg) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left: image + status -->
            <div class="col-md-4">
                <?php if ($p['imagen_url']): ?>
                <img src="<?= h($p['imagen_url']) ?>" alt="" class="rounded-4 w-100 mb-3" style="height:220px;object-fit:cover">
                <?php else: ?>
                <div class="rounded-4 mb-3 d-flex align-items-center justify-content-center" style="height:180px;background:linear-gradient(135deg,#fecdd3,#f9a8d4);font-size:3rem">🌸</div>
                <?php endif; ?>

                <div class="form-card">
                    <h6 class="fw-bold mb-3">Estado del pedido</h6>
                    <div class="mb-3">
                        <span class="badge-<?= h($p['estado']) ?>" style="font-size:.85rem;padding:.4rem .9rem"><?= ucfirst(h($p['estado'])) ?></span>
                    </div>

                    <?php if ($p['estado'] === 'pendiente'): ?>
                    <button class="btn-rose w-100 justify-content-center mb-2" data-bs-toggle="modal" data-bs-target="#modalEntregar">
                        ✅ Marcar como entregado
                    </button>
                    <form method="POST">
                        <input type="hidden" name="action" value="cancelar">
                        <button type="submit" class="btn btn-outline-danger w-100 rounded-3" style="font-size:.875rem">Cancelar pedido</button>
                    </form>
                    <?php elseif ($p['estado'] !== 'pendiente'): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="pendiente">
                        <button type="submit" class="btn btn-outline-secondary w-100 rounded-3" style="font-size:.875rem">↩ Volver a pendiente</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: details -->
            <div class="col-md-8">
                <div class="form-card mb-3">
                    <h6 class="fw-bold mb-3">🌺 <?= h($p['producto']) ?></h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="form-label-sm">Cliente</div>
                            <div class="fw-semibold"><?= h($p['nombre_cliente']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="form-label-sm">Teléfono</div>
                            <div><?= h($p['telefono']) ?></div>
                        </div>
                        <div class="col-12">
                            <div class="form-label-sm">Dirección de entrega</div>
                            <div><?= h($p['direccion_entrega']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="form-label-sm">Fecha de entrega</div>
                            <div><?= date('d/m/Y', strtotime($p['fecha_entrega'])) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="form-label-sm">Hora</div>
                            <div><?= $p['hora_entrega'] ? h($p['hora_entrega']) : '—' ?></div>
                        </div>
                        <div class="col-6">
                            <div class="form-label-sm">Medio de pago</div>
                            <div><?= h($p['medio_pago'] ?? '—') ?></div>
                        </div>
                        <div class="col-6">
                            <div class="form-label-sm">Registro</div>
                            <div style="font-size:.8rem"><?= date('d/m/Y H:i', strtotime($p['fecha_registro'])) ?></div>
                        </div>
                        <?php if ($p['mensaje_personal']): ?>
                        <div class="col-12">
                            <div class="form-label-sm">Mensaje personal</div>
                            <div class="p-3 rounded-3" style="background:#fff1f2;color:#be123c;font-style:italic">"<?= h($p['mensaje_personal']) ?>"</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($p['notas']): ?>
                        <div class="col-12">
                            <div class="form-label-sm">Notas</div>
                            <div><?= h($p['notas']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($p['foto_evidencia_url']): ?>
                        <div class="col-12">
                            <div class="form-label-sm">Foto de evidencia</div>
                            <img src="<?= h($p['foto_evidencia_url']) ?>" alt="Evidencia" class="rounded-3" style="max-height:180px;max-width:100%">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal entregar -->
<div class="modal fade" id="modalEntregar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Confirmar entrega</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="entregar">
                    <div class="mb-3">
                        <label class="form-label-sm d-block">URL foto de evidencia (opcional)</label>
                        <input type="url" name="foto_url" class="form-control" placeholder="https://...">
                    </div>
                    <div>
                        <label class="form-label-sm d-block">Notas (opcional)</label>
                        <textarea name="notas" class="form-control" rows="2" placeholder="Recibido por el destinatario..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose">✅ Confirmar entrega</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php renderFoot(); ?>

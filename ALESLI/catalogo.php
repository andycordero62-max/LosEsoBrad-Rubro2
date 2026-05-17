<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = requireRole('admin', 'empleado');
$pdo  = getDB();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['rol'] === 'admin') {
    $action = $_POST['action'] ?? '';
    if ($action === 'crear') {
        $stmt = $pdo->prepare("INSERT INTO catalogo_arreglos (nombre, descripcion, precio, imagen_url, disponible) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([
            trim($_POST['nombre']), trim($_POST['descripcion'] ?? ''),
            (float)$_POST['precio'], trim($_POST['imagen_url'] ?? '') ?: null,
        ]);
        $msg = 'Arreglo creado.';
    } elseif ($action === 'toggle') {
        $cid = (int)$_POST['id'];
        $pdo->prepare("UPDATE catalogo_arreglos SET disponible = 1 - disponible WHERE id = ?")->execute([$cid]);
        $msg = 'Disponibilidad actualizada.';
    } elseif ($action === 'eliminar') {
        $cid = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM catalogo_arreglos WHERE id = ?")->execute([$cid]);
        $msg = 'Arreglo eliminado.';
    }
}

$items = $pdo->query("SELECT * FROM catalogo_arreglos ORDER BY nombre")->fetchAll();

renderHead('Catálogo');
renderSidebar($user, 'catalogo');
?>
<div class="main-content">
    <div class="topbar">
        <h1>🌺 Catálogo de Arreglos</h1>
        <?php if ($user['rol'] === 'admin'): ?>
        <button class="btn-rose" data-bs-toggle="modal" data-bs-target="#modalCrear">➕ Nuevo arreglo</button>
        <?php endif; ?>
    </div>
    <div class="page-body">
        <?php if ($msg): ?>
        <div class="alert-success-custom mb-4">✅ <?= h($msg) ?></div>
        <?php endif; ?>

        <div class="catalog-grid">
            <?php foreach ($items as $c): ?>
            <div class="catalog-card" style="<?= !$c['disponible'] ? 'opacity:.55' : '' ?>">
                <?php if ($c['imagen_url']): ?>
                <img src="<?= h($c['imagen_url']) ?>" alt="<?= h($c['nombre']) ?>">
                <?php else: ?>
                <div class="img-placeholder">🌸</div>
                <?php endif; ?>
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="card-title"><?= h($c['nombre']) ?></div>
                        <span class="badge-<?= $c['disponible'] ? 'entregado' : 'cancelado' ?>" style="white-space:nowrap;font-size:.65rem">
                            <?= $c['disponible'] ? 'Disponible' : 'No disponible' ?>
                        </span>
                    </div>
                    <?php if ($c['descripcion']): ?>
                    <div class="card-desc"><?= h($c['descripcion']) ?></div>
                    <?php endif; ?>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <span class="price">$<?= number_format($c['precio'], 0) ?></span>
                        <?php if ($user['rol'] === 'admin'): ?>
                        <div class="d-flex gap-1">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary rounded-3" style="font-size:.72rem;padding:.2rem .5rem">
                                    <?= $c['disponible'] ? 'Ocultar' : 'Mostrar' ?>
                                </button>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este arreglo?')">
                                <input type="hidden" name="action" value="eliminar">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" style="font-size:.72rem;padding:.2rem .5rem">✕</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
            <div class="col-12 text-center text-muted py-5">No hay arreglos en el catálogo</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal crear -->
<?php if ($user['rol'] === 'admin'): ?>
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Nuevo arreglo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="crear">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ramo Rojo Pasión">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción del arreglo..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Precio <span class="text-danger">*</span></label>
                        <input type="number" name="precio" class="form-control" required min="0" step="100" placeholder="8500">
                    </div>
                    <div>
                        <label class="form-label-sm d-block">URL de imagen (opcional)</label>
                        <input type="url" name="imagen_url" class="form-control" placeholder="https://images.unsplash.com/...">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose">Crear arreglo</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php renderFoot(); ?>

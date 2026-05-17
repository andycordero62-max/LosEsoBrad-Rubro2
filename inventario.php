<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user  = requireRole('admin', 'empleado');
$pdo   = getDB();
$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear_material') {
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) { $error = 'El nombre es obligatorio.'; }
        else {
            $stmt = $pdo->prepare("INSERT INTO materiales (nombre, descripcion, unidad, stock_actual, stock_minimo, precio_costo) VALUES (?,?,?,?,?,?)");
            $stmt->execute([
                $nombre, trim($_POST['descripcion']??'') ?: null,
                $_POST['unidad'] ?? 'unidad',
                (int)($_POST['stock_actual'] ?? 0),
                (int)($_POST['stock_minimo'] ?? 5),
                (float)($_POST['precio_costo'] ?? 0),
            ]);
            $msg = "Material \"$nombre\" creado.";
        }

    } elseif ($action === 'movimiento') {
        $idMat  = (int)$_POST['id_material'];
        $tipo   = $_POST['tipo'];
        $cant   = (int)$_POST['cantidad'];
        $motivo = trim($_POST['motivo'] ?? '');
        if ($cant <= 0) { $error = 'La cantidad debe ser mayor a 0.'; }
        else {
            // Verificar stock suficiente para salida/baja
            if (in_array($tipo, ['salida','baja'])) {
                $s = $pdo->prepare("SELECT stock_actual FROM materiales WHERE id_material=?");
                $s->execute([$idMat]); $row = $s->fetch();
                if ($row['stock_actual'] < $cant) { $error = 'Stock insuficiente.'; }
            }
            if (!$error) {
                $pdo->prepare("INSERT INTO movimientos_inventario (id_material, id_usuario, tipo, cantidad, motivo) VALUES (?,?,?,?,?)")
                    ->execute([$idMat, $user['id'], $tipo, $cant, $motivo ?: null]);
                $delta = in_array($tipo, ['salida','baja']) ? -$cant : $cant;
                $pdo->prepare("UPDATE materiales SET stock_actual = stock_actual + ? WHERE id_material=?")
                    ->execute([$delta, $idMat]);
                $msg = 'Movimiento registrado correctamente.';
            }
        }

    } elseif ($action === 'editar_material' && $user['rol'] === 'admin') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE materiales SET nombre=?, unidad=?, stock_minimo=?, precio_costo=? WHERE id_material=?")
            ->execute([
                trim($_POST['nombre']), $_POST['unidad'], (int)$_POST['stock_minimo'],
                (float)$_POST['precio_costo'], $id
            ]);
        $msg = 'Material actualizado.';

    } elseif ($action === 'eliminar_material' && $user['rol'] === 'admin') {
        $pdo->prepare("DELETE FROM materiales WHERE id_material=?")->execute([(int)$_POST['id']]);
        $msg = 'Material eliminado.';
    }
}

// ── Listado materiales ──
$materiales = $pdo->query("SELECT * FROM materiales ORDER BY nombre")->fetchAll();
$stockBajoCount = count(array_filter($materiales, fn($m) => $m['stock_actual'] <= $m['stock_minimo']));

// ── Historial reciente de movimientos ──
$movimientos = $pdo->query("
    SELECT mi.*, m.nombre AS material_nombre, u.nombre AS usuario_nombre
    FROM movimientos_inventario mi
    LEFT JOIN materiales m ON m.id_material = mi.id_material
    LEFT JOIN usuarios   u ON u.id_usuario  = mi.id_usuario
    ORDER BY mi.fecha DESC LIMIT 15
")->fetchAll();

renderHead('Inventario');
renderSidebar($user, 'inventario');
?>
<div class="main-content">
    <div class="topbar">
        <h1>📦 Inventario de Materiales</h1>
        <?php if ($user['rol'] === 'admin'): ?>
        <button class="btn-rose" data-bs-toggle="modal" data-bs-target="#modalCrear">➕ Nuevo material</button>
        <?php endif; ?>
    </div>
    <div class="page-body">
        <?php if ($msg):   ?><div class="alert-success-custom mb-3">✅ <?= h($msg)   ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error-custom   mb-3">⚠️ <?= h($error) ?></div><?php endif; ?>

        <?php if ($stockBajoCount): ?>
        <div class="stock-alert-bar">
            ⚠️ <strong><?= $stockBajoCount ?> material(es) con stock bajo o agotado.</strong> Revisá y reponelos pronto.
        </div>
        <?php endif; ?>

        <!-- Tabla materiales -->
        <div class="table-card mb-4">
            <div class="d-flex align-items-center justify-content-between px-3 py-2" style="background:#fafafa;border-bottom:1px solid #f0f0f0">
                <span class="fw-bold" style="font-size:.9rem">📋 Materiales</span>
            </div>
            <table class="table mb-0">
                <thead>
                    <tr><th>Material</th><th>Unidad</th><th>Stock actual</th><th>Stock mín.</th><th>Costo unit.</th><th>Estado</th><th>Acción</th></tr>
                </thead>
                <tbody>
                <?php foreach ($materiales as $m):
                    $pct = $m['stock_minimo'] > 0 ? min(100, round($m['stock_actual'] / $m['stock_minimo'] * 100)) : 100;
                    $bajo = $m['stock_actual'] <= $m['stock_minimo'];
                    $color = $bajo ? '#ef4444' : '#10b981';
                ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= h($m['nombre']) ?></div>
                        <?php if ($m['descripcion']): ?><div style="font-size:.72rem;color:#888"><?= h($m['descripcion']) ?></div><?php endif; ?>
                    </td>
                    <td><?= h($m['unidad']) ?></td>
                    <td>
                        <span class="fw-bold" style="color:<?= $color ?>"><?= $m['stock_actual'] ?></span>
                        <div class="stock-bar" style="width:80px">
                            <div class="stock-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                        </div>
                    </td>
                    <td><?= $m['stock_minimo'] ?></td>
                    <td>Bs. <?= number_format($m['precio_costo'],2) ?></td>
                    <td><span class="badge-<?= $bajo ? 'low' : 'ok' ?>-stock"><?= $bajo ? '⚠️ Bajo' : '✅ OK' ?></span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary rounded-3" style="font-size:.72rem"
                                data-bs-toggle="modal" data-bs-target="#modalMov"
                                onclick="selMat(<?= $m['id_material'] ?>, '<?= h(addslashes($m['nombre'])) ?>')">
                                📊 Movimiento
                            </button>
                            <?php if ($user['rol'] === 'admin'): ?>
                            <button class="btn btn-sm btn-outline-secondary rounded-3" style="font-size:.72rem"
                                data-bs-toggle="modal" data-bs-target="#modalEditar"
                                onclick="selEdit(<?= htmlspecialchars(json_encode($m), ENT_QUOTES) ?>)">
                                ✏️
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar material?')">
                                <input type="hidden" name="action" value="eliminar_material">
                                <input type="hidden" name="id" value="<?= $m['id_material'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" style="font-size:.72rem">✕</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($materiales)): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">No hay materiales registrados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Historial de movimientos -->
        <h5 class="fw-bold mb-3">📋 Historial de movimientos recientes</h5>
        <div class="table-card">
            <table class="table mb-0">
                <thead>
                    <tr><th>Fecha</th><th>Material</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th><th>Usuario</th></tr>
                </thead>
                <tbody>
                <?php foreach ($movimientos as $mv): ?>
                <tr>
                    <td style="font-size:.78rem;color:#888"><?= date('d/m/Y H:i', strtotime($mv['fecha'])) ?></td>
                    <td class="fw-semibold"><?= h($mv['material_nombre'] ?? '—') ?></td>
                    <td>
                        <span class="badge-<?= $mv['tipo'] === 'entrada' ? 'entregado' : 'cancelado' ?>">
                            <?= ucfirst(h($mv['tipo'])) ?>
                        </span>
                    </td>
                    <td><?= $mv['tipo']==='entrada' ? '+' : '-' ?><?= $mv['cantidad'] ?></td>
                    <td style="font-size:.82rem"><?= h($mv['motivo'] ?? '—') ?></td>
                    <td style="font-size:.78rem;color:#888"><?= h($mv['usuario_nombre'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($movimientos)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Sin movimientos aún.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal movimiento -->
<div class="modal fade" id="modalMov" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Registrar movimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="movimiento">
                <input type="hidden" name="id_material" id="mov_id">
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:.875rem">Material: <strong id="mov_nombre"></strong></p>
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Tipo de movimiento</label>
                        <select name="tipo" class="form-select" required>
                            <option value="entrada">📥 Entrada (compra / reposición)</option>
                            <option value="salida">📤 Salida (uso en arreglo)</option>
                            <option value="baja">🗑️ Baja (dañado / vencido)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" name="cantidad" class="form-control" min="1" required placeholder="10">
                    </div>
                    <div>
                        <label class="form-label-sm d-block">Motivo / descripción</label>
                        <input type="text" name="motivo" class="form-control" placeholder="Ej: Compra semanal, Pedido #5…">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal editar material -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Editar material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="editar_material">
                <input type="hidden" name="id" id="edit_mat_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Nombre</label>
                        <input type="text" name="nombre" id="edit_mat_nombre" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-sm d-block">Unidad</label>
                            <input type="text" name="unidad" id="edit_mat_unidad" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label-sm d-block">Stock mínimo</label>
                            <input type="number" name="stock_minimo" id="edit_mat_min" class="form-control" min="0">
                        </div>
                    </div>
                    <div>
                        <label class="form-label-sm d-block">Precio costo unitario (Bs.)</label>
                        <input type="number" name="precio_costo" id="edit_mat_costo" class="form-control" step="0.01" min="0">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal crear material -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Nuevo material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="crear_material">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Rosas Rojas">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Descripción</label>
                        <input type="text" name="descripcion" class="form-control" placeholder="Descripción opcional">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-sm d-block">Unidad</label>
                            <input type="text" name="unidad" class="form-control" placeholder="tallo, kg, unidad…" value="unidad">
                        </div>
                        <div class="col-6">
                            <label class="form-label-sm d-block">Stock inicial</label>
                            <input type="number" name="stock_actual" class="form-control" min="0" value="0">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label-sm d-block">Stock mínimo</label>
                            <input type="number" name="stock_minimo" class="form-control" min="0" value="5">
                        </div>
                        <div class="col-6">
                            <label class="form-label-sm d-block">Precio costo (Bs.)</label>
                            <input type="number" name="precio_costo" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selMat(id, nombre) {
    document.getElementById('mov_id').value = id;
    document.getElementById('mov_nombre').textContent = nombre;
}
function selEdit(m) {
    document.getElementById('edit_mat_id').value    = m.id_material;
    document.getElementById('edit_mat_nombre').value = m.nombre;
    document.getElementById('edit_mat_unidad').value = m.unidad || '';
    document.getElementById('edit_mat_min').value    = m.stock_minimo;
    document.getElementById('edit_mat_costo').value  = m.precio_costo;
}
</script>
<?php renderFoot(); ?>

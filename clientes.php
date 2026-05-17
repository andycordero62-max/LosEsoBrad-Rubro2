<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = requireRole('admin', 'empleado');
$pdo  = getDB();
$msg  = '';
$error = '';

// ── Acciones POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) {
            $error = 'El nombre es obligatorio.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO clientes (nombre, telefono, email, direccion, medio_pago_preferido, notas) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $nombre,
                trim($_POST['telefono'] ?? '') ?: null,
                trim($_POST['email']    ?? '') ?: null,
                trim($_POST['direccion']?? '') ?: null,
                $_POST['medio_pago']    ?? null,
                trim($_POST['notas']    ?? '') ?: null,
            ]);
            $msg = "Cliente \"$nombre\" registrado correctamente.";
        }

    } elseif ($action === 'editar') {
        $id = (int)$_POST['id'];
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) {
            $error = 'El nombre es obligatorio.';
        } else {
            $stmt = $pdo->prepare("UPDATE clientes SET nombre=?, telefono=?, email=?, direccion=?, medio_pago_preferido=?, notas=? WHERE id_cliente=?");
            $stmt->execute([
                $nombre,
                trim($_POST['telefono'] ?? '') ?: null,
                trim($_POST['email']    ?? '') ?: null,
                trim($_POST['direccion']?? '') ?: null,
                $_POST['medio_pago']    ?? null,
                trim($_POST['notas']    ?? '') ?: null,
                $id
            ]);
            $msg = 'Cliente actualizado.';
        }

    } elseif ($action === 'eliminar' && $user['rol'] === 'admin') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM clientes WHERE id_cliente=?")->execute([$id]);
        $msg = 'Cliente eliminado.';
    }
}

// ── Búsqueda ──
$q      = trim($_GET['q'] ?? '');
$params = [];
$where  = '';
if ($q) {
    $where    = "WHERE nombre LIKE ? OR telefono LIKE ? OR email LIKE ?";
    $params   = ["%$q%", "%$q%", "%$q%"];
}
$stmt = $pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM pedidos p WHERE p.id_cliente=c.id_cliente) AS total_pedidos FROM clientes c $where ORDER BY c.nombre");
$stmt->execute($params);
$clientes = $stmt->fetchAll();

renderHead('Clientes');
renderSidebar($user, 'clientes');
?>
<div class="main-content">
    <div class="topbar">
        <h1>👤 Clientes</h1>
        <button class="btn-rose" data-bs-toggle="modal" data-bs-target="#modalCrear">➕ Nuevo cliente</button>
    </div>
    <div class="page-body">
        <?php if ($msg):   ?><div class="alert-success-custom mb-3">✅ <?= h($msg)   ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error-custom   mb-3">⚠️ <?= h($error) ?></div><?php endif; ?>

        <!-- Búsqueda -->
        <form method="GET" class="d-flex gap-2 mb-4">
            <input type="text" name="q" value="<?= h($q) ?>" class="form-control rounded-3" placeholder="Buscar por nombre, teléfono o email…" style="max-width:340px">
            <button type="submit" class="btn-rose">Buscar</button>
            <?php if ($q): ?><a href="/ALESLI/clientes.php" class="btn btn-outline-secondary rounded-3">✕ Limpiar</a><?php endif; ?>
        </form>

        <div class="table-card">
            <table class="table mb-0">
                <thead>
                    <tr><th>#</th><th>Nombre</th><th>Teléfono</th><th>Email</th><th>Pago pref.</th><th>Pedidos</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                <?php foreach ($clientes as $c): ?>
                <tr>
                    <td class="text-muted"><?= $c['id_cliente'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= h($c['nombre']) ?></div>
                        <?php if ($c['direccion']): ?>
                        <div style="font-size:.72rem;color:#888"><?= h(mb_substr($c['direccion'],0,50)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= h($c['telefono'] ?? '—') ?></td>
                    <td style="font-size:.82rem"><?= h($c['email'] ?? '—') ?></td>
                    <td><?= h($c['medio_pago_preferido'] ?? '—') ?></td>
                    <td>
                        <span class="badge-<?= $c['total_pedidos'] > 0 ? 'entregado' : 'pendiente' ?>">
                            <?= $c['total_pedidos'] ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-secondary rounded-3" style="font-size:.72rem"
                                data-bs-toggle="modal" data-bs-target="#modalEditar"
                                onclick="cargarEdicion(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)">
                                ✏️ Editar
                            </button>
                            <?php if ($user['rol'] === 'admin'): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar cliente?')">
                                <input type="hidden" name="action" value="eliminar">
                                <input type="hidden" name="id" value="<?= $c['id_cliente'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" style="font-size:.72rem">✕</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($clientes)): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">
                    <?= $q ? 'No se encontraron clientes con esa búsqueda.' : 'No hay clientes registrados.' ?>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-2 text-muted" style="font-size:.78rem"><?= count($clientes) ?> cliente<?= count($clientes)!==1?'s':'' ?></div>
    </div>
</div>

<!-- Modal crear -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Nuevo cliente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="crear">
                <div class="modal-body">
                    <?= modalCampos() ?>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal editar -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Editar cliente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" id="formEditar">
                <input type="hidden" name="action" value="editar">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body" id="edit_body"></div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function cargarEdicion(c) {
    document.getElementById('edit_id').value = c.id_cliente;
    document.getElementById('edit_body').innerHTML = `
        <div class="mb-3">
            <label class="form-label-sm d-block">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control" required value="${esc(c.nombre)}">
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label-sm d-block">Teléfono</label>
                <input type="tel" name="telefono" class="form-control" value="${esc(c.telefono||'')}">
            </div>
            <div class="col-6">
                <label class="form-label-sm d-block">Email</label>
                <input type="email" name="email" class="form-control" value="${esc(c.email||'')}">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label-sm d-block">Dirección</label>
            <input type="text" name="direccion" class="form-control" value="${esc(c.direccion||'')}">
        </div>
        <div class="mb-3">
            <label class="form-label-sm d-block">Medio de pago preferido</label>
            <select name="medio_pago" class="form-select">
                <option value="">— Seleccionar —</option>
                ${['Efectivo','Transferencia','QR','Tarjeta'].map(m=>`<option value="${m}" ${c.medio_pago_preferido===m?'selected':''}>${m}</option>`).join('')}
            </select>
        </div>
        <div>
            <label class="form-label-sm d-block">Notas</label>
            <textarea name="notas" class="form-control" rows="2">${esc(c.notas||'')}</textarea>
        </div>
    `;
}
function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }
</script>
<?php renderFoot(); ?>

<?php
function modalCampos(array $vals = []): string {
    $medios = ['Efectivo','Transferencia','QR','Tarjeta'];
    $opts   = '<option value="">— Seleccionar —</option>';
    foreach ($medios as $m) $opts .= "<option value=\"$m\">$m</option>";
    return '
    <div class="mb-3">
        <label class="form-label-sm d-block">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="nombre" class="form-control" required placeholder="María García">
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label-sm d-block">Teléfono</label>
            <input type="tel" name="telefono" class="form-control" placeholder="78901234">
        </div>
        <div class="col-6">
            <label class="form-label-sm d-block">Email</label>
            <input type="email" name="email" class="form-control" placeholder="maria@email.com">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label-sm d-block">Dirección</label>
        <input type="text" name="direccion" class="form-control" placeholder="Av. 6 de Agosto 234, La Paz">
    </div>
    <div class="mb-3">
        <label class="form-label-sm d-block">Medio de pago preferido</label>
        <select name="medio_pago" class="form-select">'.$opts.'</select>
    </div>
    <div>
        <label class="form-label-sm d-block">Notas</label>
        <textarea name="notas" class="form-control" rows="2" placeholder="Observaciones…"></textarea>
    </div>';
}

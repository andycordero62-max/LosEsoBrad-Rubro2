<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user     = requireRole('admin', 'empleado');
$pdo      = getDB();
$catalogo = $pdo->query("SELECT * FROM catalogo_arreglos WHERE disponible=1 ORDER BY nombre")->fetchAll();
$clientes = $pdo->query("SELECT id_cliente, nombre, telefono, direccion, medio_pago_preferido FROM clientes ORDER BY nombre")->fetchAll();

$success = false;
$error   = '';
$vals    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vals = [
        'nombre_cliente'   => trim($_POST['nombre_cliente']    ?? ''),
        'telefono'         => trim($_POST['telefono']          ?? ''),
        'direccion_entrega'=> trim($_POST['direccion_entrega'] ?? ''),
        'producto'         => trim($_POST['producto']          ?? ''),
        'fecha_entrega'    => $_POST['fecha_entrega']          ?? '',
        'hora_entrega'     => trim($_POST['hora_entrega']      ?? ''),
        'estado'           => $_POST['estado']                 ?? 'pendiente',
        'mensaje_personal' => trim($_POST['mensaje_personal']  ?? ''),
        'medio_pago'       => $_POST['medio_pago']             ?? '',
        'monto'            => (float)($_POST['monto']          ?? 0),
        'notas'            => trim($_POST['notas']             ?? ''),
        'id_catalogo'      => (int)($_POST['id_catalogo']      ?? 0),
        'id_cliente'       => (int)($_POST['id_cliente']       ?? 0),
    ];

    if (!$vals['nombre_cliente'] || !$vals['direccion_entrega'] || !$vals['producto'] || !$vals['fecha_entrega']) {
        $error = 'Completá todos los campos obligatorios.';
    } else {
        if ($vals['id_catalogo']) {
            $cat = $pdo->prepare("SELECT nombre, precio FROM catalogo_arreglos WHERE id_catalogo_arreglo=?");
            $cat->execute([$vals['id_catalogo']]); $catRow = $cat->fetch();
            if ($catRow) { $vals['producto'] = $catRow['nombre']; if (!$vals['monto']) $vals['monto'] = $catRow['precio']; }
        }
        $stmt = $pdo->prepare("INSERT INTO pedidos (id_cliente, id_usuario, id_catalogo_arreglo, nombre_cliente, telefono, direccion_entrega, producto, fecha_entrega, hora_entrega, estado, mensaje_personalizado, medio_pago, monto, notas) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $vals['id_cliente'] ?: null, $user['id'], $vals['id_catalogo'] ?: null,
            $vals['nombre_cliente'], $vals['telefono'] ?: null, $vals['direccion_entrega'],
            $vals['producto'], $vals['fecha_entrega'], $vals['hora_entrega'] ?: null,
            $vals['estado'], $vals['mensaje_personal'] ?: null, $vals['medio_pago'] ?: null,
            $vals['monto'] ?: null, $vals['notas'] ?: null,
        ]);
        $success = true;
        $vals    = [];
    }
}

// Pasar clientes como JSON para autocompletar
$clientesJson = json_encode(array_values($clientes), JSON_UNESCAPED_UNICODE);

renderHead('Nuevo Pedido');
renderSidebar($user, 'nuevo-pedido');
?>
<div class="main-content">
    <div class="topbar">
        <h1>➕ Nuevo Pedido</h1>
        <a href="/ALESLI/pedidos.php" style="color:#888;font-size:.875rem;text-decoration:none">← Volver</a>
    </div>
    <div class="page-body" style="max-width:720px">
        <?php if ($success): ?>
        <div class="alert-success-custom mb-4">✅ Pedido creado correctamente. <a href="/ALESLI/pedidos.php" style="color:var(--rose-700)">Ver todos los pedidos →</a></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert-error-custom mb-4">⚠️ <?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="form-card">
            <!-- Selector de cliente registrado -->
            <div class="mb-3">
                <label class="form-label-sm d-block">Cliente registrado (opcional)</label>
                <select id="sel_cliente" class="form-select" onchange="autoFillCliente(this)">
                    <option value="">— Nuevo / cliente no registrado —</option>
                    <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c['id_cliente'] ?>"><?= h($c['nombre']) ?> <?= $c['telefono'] ? '· '.$c['telefono'] : '' ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Al seleccionar un cliente se autocompletan sus datos.</small>
            </div>
            <input type="hidden" name="id_cliente" id="campo_id_cliente" value="">
            <hr class="my-3">

            <!-- Catálogo -->
            <div class="mb-3">
                <label class="form-label-sm d-block">Arreglo del catálogo (opcional)</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.6rem">
                    <?php foreach ($catalogo as $c): ?>
                    <label style="cursor:pointer">
                        <input type="radio" name="id_catalogo" value="<?= $c['id_catalogo_arreglo'] ?>"
                               style="display:none" onchange="selectCatalog(this, '<?= h(addslashes($c['nombre'])) ?>', <?= $c['precio'] ?>)">
                        <div class="catalog-opt rounded-3 border p-2 text-center" style="transition:.15s">
                            <?php if ($c['foto_url']): ?>
                            <img src="<?= h($c['foto_url']) ?>" style="width:100%;height:60px;object-fit:cover;border-radius:6px;margin-bottom:.3rem" alt="">
                            <?php else: ?>
                            <div style="height:60px;background:#fecdd3;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:.3rem">🌸</div>
                            <?php endif; ?>
                            <div style="font-size:.68rem;font-weight:600;line-height:1.2"><?= h($c['nombre']) ?></div>
                            <div style="font-size:.65rem;color:#888">Bs.<?= number_format($c['precio'],0) ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <hr class="my-3">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label-sm d-block">Nombre del cliente <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_cliente" id="campo_nombre" class="form-control" required value="<?= h($vals['nombre_cliente']??'') ?>" placeholder="María García">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm d-block">Teléfono</label>
                    <input type="tel" name="telefono" id="campo_tel" class="form-control" value="<?= h($vals['telefono']??'') ?>" placeholder="78901234">
                </div>
                <div class="col-12">
                    <label class="form-label-sm d-block">Dirección de entrega <span class="text-danger">*</span></label>
                    <input type="text" name="direccion_entrega" id="campo_dir" class="form-control" required value="<?= h($vals['direccion_entrega']??'') ?>" placeholder="Av. 6 de Agosto 234, La Paz">
                </div>
                <div class="col-12">
                    <label class="form-label-sm d-block">Producto <span class="text-danger">*</span></label>
                    <input type="text" name="producto" id="campo_producto" class="form-control" required value="<?= h($vals['producto']??'') ?>" placeholder="Nombre del arreglo">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm d-block">Fecha de entrega <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_entrega" class="form-control" required value="<?= h($vals['fecha_entrega'] ?? date('Y-m-d', strtotime('+1 day'))) ?>" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm d-block">Hora</label>
                    <input type="time" name="hora_entrega" class="form-control" value="<?= h($vals['hora_entrega']??'') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm d-block">Estado inicial</label>
                    <select name="estado" class="form-select">
                        <?php foreach (['pendiente','preparando'] as $st): ?>
                        <option value="<?= $st ?>" <?= ($vals['estado']??'pendiente')===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm d-block">Medio de pago</label>
                    <select name="medio_pago" id="campo_pago" class="form-select">
                        <option value="">— Seleccionar —</option>
                        <?php foreach (['Efectivo','Transferencia','QR','Tarjeta'] as $mp): ?>
                        <option value="<?= $mp ?>" <?= ($vals['medio_pago']??'')===$mp?'selected':'' ?>><?= $mp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm d-block">Monto (Bs.)</label>
                    <input type="number" name="monto" id="campo_monto" class="form-control" step="0.01" min="0" value="<?= h($vals['monto']??'') ?>" placeholder="0.00">
                </div>
                <div class="col-12">
                    <label class="form-label-sm d-block">Mensaje para la tarjeta</label>
                    <textarea name="mensaje_personal" class="form-control" rows="2" placeholder="¡Feliz cumpleaños!…"><?= h($vals['mensaje_personal']??'') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label-sm d-block">Notas internas</label>
                    <textarea name="notas" class="form-control" rows="2" placeholder="Instrucciones especiales…"><?= h($vals['notas']??'') ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn-rose px-4 py-2">Crear pedido →</button>
                <a href="/ALESLI/pedidos.php" class="btn btn-outline-secondary rounded-3" style="font-size:.875rem">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
const clientes = <?= $clientesJson ?>;

function autoFillCliente(sel) {
    const id = parseInt(sel.value);
    const c = clientes.find(x => x.id_cliente === id);
    document.getElementById('campo_id_cliente').value = id || '';
    if (c) {
        document.getElementById('campo_nombre').value = c.nombre || '';
        document.getElementById('campo_tel').value    = c.telefono || '';
        document.getElementById('campo_dir').value    = c.direccion || '';
        if (c.medio_pago_preferido) {
            const sel2 = document.getElementById('campo_pago');
            for (let opt of sel2.options) { if (opt.value === c.medio_pago_preferido) { opt.selected = true; break; } }
        }
    } else {
        document.getElementById('campo_nombre').value = '';
        document.getElementById('campo_tel').value    = '';
        document.getElementById('campo_dir').value    = '';
    }
}

function selectCatalog(radio, nombre, precio) {
    document.querySelectorAll('.catalog-opt').forEach(el => {
        el.style.borderColor = '#e5e7eb'; el.style.background = '#fff';
    });
    const card = radio.closest('label').querySelector('.catalog-opt');
    card.style.borderColor = '#e11d48'; card.style.background = '#fff1f2';
    document.getElementById('campo_producto').value = nombre;
    if (!document.getElementById('campo_monto').value) {
        document.getElementById('campo_monto').value = precio.toFixed(2);
    }
}
</script>
<?php renderFoot(); ?>

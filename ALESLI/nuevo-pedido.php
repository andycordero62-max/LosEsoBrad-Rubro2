<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = requireRole('admin', 'empleado');
$pdo  = getDB();
$catalogo = $pdo->query("SELECT * FROM catalogo_arreglos WHERE disponible=1 ORDER BY nombre")->fetchAll();

$success = false;
$error   = '';
$vals    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vals = [
        'nombre_cliente'   => trim($_POST['nombre_cliente'] ?? ''),
        'telefono'         => trim($_POST['telefono'] ?? ''),
        'direccion_entrega'=> trim($_POST['direccion_entrega'] ?? ''),
        'producto'         => trim($_POST['producto'] ?? ''),
        'fecha_entrega'    => $_POST['fecha_entrega'] ?? '',
        'hora_entrega'     => trim($_POST['hora_entrega'] ?? ''),
        'estado'           => $_POST['estado'] ?? 'pendiente',
        'mensaje_personal' => trim($_POST['mensaje_personal'] ?? ''),
        'medio_pago'       => $_POST['medio_pago'] ?? '',
        'notas'            => trim($_POST['notas'] ?? ''),
        'id_catalogo'      => (int)($_POST['id_catalogo'] ?? 0),
    ];
    if (!$vals['nombre_cliente'] || !$vals['telefono'] || !$vals['direccion_entrega'] || !$vals['producto'] || !$vals['fecha_entrega']) {
        $error = 'Completá todos los campos obligatorios.';
    } else {
        // Auto-set producto from catalog if selected
        if ($vals['id_catalogo']) {
            $cat = $pdo->prepare("SELECT nombre FROM catalogo_arreglos WHERE id=?");
            $cat->execute([$vals['id_catalogo']]);
            $catRow = $cat->fetch();
            if ($catRow) $vals['producto'] = $catRow['nombre'];
        }
        $stmt = $pdo->prepare("INSERT INTO pedidos (nombre_cliente, telefono, direccion_entrega, producto, fecha_entrega, hora_entrega, estado, mensaje_personal, medio_pago, notas, id_catalogo_arreglo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $vals['nombre_cliente'], $vals['telefono'], $vals['direccion_entrega'],
            $vals['producto'], $vals['fecha_entrega'],
            $vals['hora_entrega'] ?: null, $vals['estado'],
            $vals['mensaje_personal'] ?: null, $vals['medio_pago'] ?: null,
            $vals['notas'] ?: null, $vals['id_catalogo'] ?: null,
        ]);
        $success = true;
        $vals = [];
    }
}

$manana = date('Y-m-d', strtotime('+1 day'));
renderHead('Nuevo Pedido');
renderSidebar($user, 'nuevo-pedido');
?>
<div class="main-content">
    <div class="topbar">
        <h1>➕ Nuevo Pedido</h1>
        <a href="/pedidos.php" style="color:#888;font-size:.875rem;text-decoration:none">← Volver a pedidos</a>
    </div>
    <div class="page-body" style="max-width:700px">
        <?php if ($success): ?>
        <div class="alert-success-custom mb-4">✅ Pedido creado correctamente. <a href="/pedidos.php" style="color:var(--rose-700)">Ver todos los pedidos →</a></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert-error-custom mb-4">⚠️ <?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="form-card">
            <!-- Catalog selector -->
            <div class="mb-4">
                <label class="form-label-sm d-block">Arreglo del catálogo (opcional)</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.75rem">
                    <?php foreach ($catalogo as $c): ?>
                    <label class="catalog-option" for="cat_<?= $c['id'] ?>" style="cursor:pointer">
                        <input type="radio" name="id_catalogo" id="cat_<?= $c['id'] ?>" value="<?= $c['id'] ?>"
                               style="display:none" onchange="selectCatalog(this, '<?= h(addslashes($c['nombre'])) ?>')">
                        <div class="catalog-opt-card rounded-3 border p-2 text-center" style="transition:.15s">
                            <?php if ($c['imagen_url']): ?>
                            <img src="<?= h($c['imagen_url']) ?>" alt="" style="width:100%;height:70px;object-fit:cover;border-radius:8px;margin-bottom:.4rem">
                            <?php else: ?>
                            <div style="height:70px;background:#fecdd3;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:.4rem">🌸</div>
                            <?php endif; ?>
                            <div style="font-size:.72rem;font-weight:600;line-height:1.2"><?= h($c['nombre']) ?></div>
                            <div style="font-size:.68rem;color:#888">$<?= number_format($c['precio'], 0) ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label-sm d-block">Nombre del cliente <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_cliente" class="form-control" required value="<?= h($vals['nombre_cliente'] ?? '') ?>" placeholder="María García">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm d-block">Teléfono <span class="text-danger">*</span></label>
                    <input type="tel" name="telefono" class="form-control" required value="<?= h($vals['telefono'] ?? '') ?>" placeholder="+54 9 11 ...">
                </div>
                <div class="col-12">
                    <label class="form-label-sm d-block">Dirección de entrega <span class="text-danger">*</span></label>
                    <input type="text" name="direccion_entrega" class="form-control" required value="<?= h($vals['direccion_entrega'] ?? '') ?>" placeholder="Av. Corrientes 1234, CABA">
                </div>
                <div class="col-12">
                    <label class="form-label-sm d-block">Producto <span class="text-danger">*</span></label>
                    <input type="text" name="producto" id="campo_producto" class="form-control" required value="<?= h($vals['producto'] ?? '') ?>" placeholder="Nombre del arreglo">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm d-block">Fecha de entrega <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_entrega" class="form-control" required value="<?= h($vals['fecha_entrega'] ?? $manana) ?>" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm d-block">Hora preferida</label>
                    <input type="time" name="hora_entrega" class="form-control" value="<?= h($vals['hora_entrega'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm d-block">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="pendiente" <?= ($vals['estado']??'pendiente')==='pendiente'?'selected':'' ?>>Pendiente</option>
                        <option value="entregado" <?= ($vals['estado']??'')==='entregado'?'selected':'' ?>>Entregado</option>
                        <option value="cancelado" <?= ($vals['estado']??'')==='cancelado'?'selected':'' ?>>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm d-block">Medio de pago</label>
                    <select name="medio_pago" class="form-select">
                        <option value="">— Seleccionar —</option>
                        <?php foreach (['Efectivo','Transferencia','Tarjeta','MercadoPago'] as $mp): ?>
                        <option value="<?= $mp ?>" <?= ($vals['medio_pago']??'')===$mp?'selected':'' ?>><?= $mp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label-sm d-block">Mensaje personal para la tarjeta</label>
                    <textarea name="mensaje_personal" class="form-control" rows="2" placeholder="¡Feliz cumpleaños! Con todo mi cariño..."><?= h($vals['mensaje_personal'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label-sm d-block">Notas internas</label>
                    <textarea name="notas" class="form-control" rows="2" placeholder="Instrucciones especiales, referencias..."><?= h($vals['notas'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn-rose px-4 py-2">Crear pedido →</button>
                <a href="/pedidos.php" class="btn btn-outline-secondary rounded-3" style="font-size:.875rem">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<script>
function selectCatalog(radio, nombre) {
    document.querySelectorAll('.catalog-opt-card').forEach(el => {
        el.style.borderColor = '#e5e7eb';
        el.style.background = '#fff';
    });
    const card = radio.closest('label').querySelector('.catalog-opt-card');
    card.style.borderColor = '#e11d48';
    card.style.background = '#fff1f2';
    document.getElementById('campo_producto').value = nombre;
}
</script>
<?php renderFoot(); ?>

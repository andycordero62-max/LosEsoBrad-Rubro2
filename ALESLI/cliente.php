<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = requireRole('cliente', 'admin', 'empleado');
$pdo  = getDB();

$tab      = $_GET['tab'] ?? 'tienda';
$msg      = '';
$error    = '';
$pedidos  = [];

// Handle new order POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pedir') {
    $telefono   = trim($_POST['telefono'] ?? '');
    $direccion  = trim($_POST['direccion_entrega'] ?? '');
    $fecha      = $_POST['fecha_entrega'] ?? '';
    $hora       = trim($_POST['hora_entrega'] ?? '');
    $mensaje    = trim($_POST['mensaje_personal'] ?? '');
    $pago       = $_POST['medio_pago'] ?? '';
    $id_cat     = (int)($_POST['id_catalogo'] ?? 0);

    if (!$telefono || !$direccion || !$fecha || !$id_cat) {
        $error = 'Completá todos los campos obligatorios.';
        $tab   = 'tienda';
    } else {
        $catRow = $pdo->prepare("SELECT nombre FROM catalogo_arreglos WHERE id=? AND disponible=1");
        $catRow->execute([$id_cat]);
        $cat = $catRow->fetch();
        if (!$cat) { $error = 'Arreglo no válido.'; $tab = 'tienda'; }
        else {
            $stmt = $pdo->prepare("INSERT INTO pedidos (nombre_cliente, telefono, direccion_entrega, producto, fecha_entrega, hora_entrega, estado, mensaje_personal, medio_pago, id_catalogo_arreglo) VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, ?)");
            $stmt->execute([
                $user['nombre'], $telefono, $direccion, $cat['nombre'],
                $fecha, $hora ?: null, $mensaje ?: null, $pago ?: null, $id_cat,
            ]);
            // Save phone in session for easy order lookup
            $_SESSION['cliente_telefono'] = $telefono;
            $msg = "¡Pedido de «{$cat['nombre']}» realizado! Nos contactaremos a la brevedad.";
            $tab = 'pedidos';
        }
    }
}

// Load client's orders
$savedPhone = $_SESSION['cliente_telefono'] ?? '';
$searchPhone = $_GET['telefono'] ?? $savedPhone;
if ($searchPhone) {
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE telefono = ? ORDER BY fecha_registro DESC");
    $stmt->execute([$searchPhone]);
    $pedidos = $stmt->fetchAll();
}

$catalogo = $pdo->query("SELECT * FROM catalogo_arreglos WHERE disponible=1 ORDER BY nombre")->fetchAll();
$manana   = date('Y-m-d', strtotime('+1 day'));

renderHead('Tienda Alesli');
// No sidebar for clients
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alesli — Tienda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body style="background:linear-gradient(135deg,#fff1f2 0%,#ffffff 50%,#fdf2f8 100%);min-height:100vh">

<!-- Header -->
<header class="cliente-header">
    <div class="d-flex align-items-center gap-2">
        <span style="font-size:1.3rem">🌸</span>
        <span style="font-weight:800;font-size:1.1rem;color:#be123c">Alesli</span>
    </div>
    <div class="cliente-tabs">
        <a href="?tab=tienda" class="cliente-tab <?= $tab==='tienda'?'active':'' ?>">
            🛍️ Tienda
        </a>
        <a href="?tab=pedidos" class="cliente-tab <?= $tab==='pedidos'?'active':'' ?>">
            📦 Mis Pedidos
            <?php $pend = count(array_filter($pedidos, fn($p) => $p['estado']==='pendiente')); if ($pend > 0): ?>
            <span style="background:#fbbf24;color:#fff;border-radius:99px;padding:0 6px;font-size:.65rem;font-weight:700"><?= $pend ?></span>
            <?php endif; ?>
        </a>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span style="font-size:.8rem;color:#888">Hola, <strong><?= h(explode(' ', $user['nombre'])[0]) ?></strong></span>
        <a href="/logout.php" style="font-size:.8rem;color:#999;text-decoration:none">Salir ⏻</a>
    </div>
</header>

<main style="max-width:960px;margin:0 auto;padding:2rem 1rem">

    <?php if ($msg): ?>
    <div class="alert-success-custom mb-4">✅ <?= h($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert-error-custom mb-4">⚠️ <?= h($error) ?></div>
    <?php endif; ?>

    <!-- ══ TIENDA TAB ══ -->
    <?php if ($tab === 'tienda'): ?>

    <!-- Hero banner -->
    <div class="hero-banner">
        <div style="position:relative">
            <div style="font-size:.8rem;color:rgba(255,255,255,.7);margin-bottom:.4rem">⭐ Arreglos florales a domicilio</div>
            <h2 class="fw-bold mb-2" style="font-size:1.6rem">¡Hola, <?= h(explode(' ', $user['nombre'])[0]) ?>! 🌸</h2>
            <p style="color:rgba(255,255,255,.75);margin:0;font-size:.9rem">Elegí tu arreglo y hacé tu pedido directo desde acá. Entregamos en el día.</p>
        </div>
    </div>

    <h5 class="fw-bold mb-3">Nuestros arreglos</h5>
    <div class="catalog-grid">
        <?php foreach ($catalogo as $c): ?>
        <div class="catalog-card">
            <?php if ($c['imagen_url']): ?>
            <img src="<?= h($c['imagen_url']) ?>" alt="<?= h($c['nombre']) ?>">
            <?php else: ?>
            <div class="img-placeholder">🌸</div>
            <?php endif; ?>
            <div class="card-body">
                <div class="card-title"><?= h($c['nombre']) ?></div>
                <?php if ($c['descripcion']): ?>
                <div class="card-desc"><?= h($c['descripcion']) ?></div>
                <?php endif; ?>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="price">$<?= number_format($c['precio'], 0) ?></span>
                    <button class="btn-rose" style="font-size:.78rem;padding:.35rem .85rem"
                        data-bs-toggle="modal" data-bs-target="#modalPedir"
                        onclick="setPedirItem(<?= $c['id'] ?>, '<?= h(addslashes($c['nombre'])) ?>', <?= $c['precio'] ?>, '<?= h(addslashes($c['imagen_url'] ?? '')) ?>')">
                        Pedir
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($catalogo)): ?>
        <div class="text-center text-muted py-5">No hay arreglos disponibles en este momento</div>
        <?php endif; ?>
    </div>

    <!-- ══ PEDIDOS TAB ══ -->
    <?php else: ?>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h5 class="fw-bold mb-0">Mis Pedidos</h5>
        <?php if (!empty($pedidos)): ?>
        <span style="font-size:.8rem;color:#888"><?= count($pedidos) ?> pedido<?= count($pedidos)!==1?'s':'' ?></span>
        <?php endif; ?>
    </div>

    <!-- Phone search -->
    <?php if (!$searchPhone): ?>
    <div class="form-card mb-4">
        <p style="font-size:.875rem;font-weight:500;margin-bottom:.75rem">Ingresá tu teléfono para ver tus pedidos</p>
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="tab" value="pedidos">
            <input type="tel" name="telefono" class="form-control" placeholder="+54 9 11 1234-5678" required>
            <button type="submit" class="btn-rose px-4">Buscar</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($searchPhone): ?>
    <div class="mb-3 d-flex align-items-center gap-2" style="font-size:.78rem;color:#888">
        📞 Pedidos de: <strong style="color:#333"><?= h($searchPhone) ?></strong>
        <a href="?tab=pedidos" style="color:var(--rose-600);margin-left:.25rem">Cambiar</a>
    </div>
    <?php endif; ?>

    <?php if ($searchPhone && empty($pedidos)): ?>
    <div class="form-card text-center py-5">
        <div style="font-size:3rem;margin-bottom:.75rem">📦</div>
        <p class="fw-semibold text-muted">No encontramos pedidos</p>
        <p style="font-size:.8rem;color:#aaa">Verificá el número o hacé tu primer pedido desde la tienda</p>
        <a href="?tab=tienda" class="btn-rose d-inline-flex mt-3" style="font-size:.825rem">🛍️ Ir a la tienda</a>
    </div>
    <?php endif; ?>

    <div class="d-flex flex-column gap-3">
        <?php foreach ($pedidos as $p): ?>
        <div class="form-card p-0 overflow-hidden">
            <div class="status-bar <?= h($p['estado']) ?>"></div>
            <div class="p-4">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <span class="fw-bold"><?= h($p['producto']) ?></span>
                            <span class="badge-<?= h($p['estado']) ?>"><?= ucfirst(h($p['estado'])) ?></span>
                        </div>
                        <div style="font-size:.75rem;color:#aaa">Pedido #<?= $p['id'] ?></div>
                    </div>
                    <?php if ($p['estado']==='entregado'): ?>
                    <span style="font-size:1.2rem">✅</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-3" style="font-size:.8rem;color:#777">
                    <span>📅 <?= date('d/m/Y', strtotime($p['fecha_entrega'])) ?><?= $p['hora_entrega'] ? ' · ' . h($p['hora_entrega']) : '' ?></span>
                    <span>📍 <?= h($p['direccion_entrega']) ?></span>
                    <?php if ($p['medio_pago']): ?><span>💳 <?= h($p['medio_pago']) ?></span><?php endif; ?>
                </div>
                <?php if ($p['mensaje_personal']): ?>
                <div class="mt-3 p-3 rounded-3" style="background:#fff1f2;color:#be123c;font-style:italic;font-size:.85rem">
                    💬 "<?= h($p['mensaje_personal']) ?>"
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- CTA to shop -->
    <a href="?tab=tienda" style="display:flex;align-items:center;justify-content:space-between;border:2px dashed #fecdd3;border-radius:16px;padding:1.1rem 1.4rem;text-decoration:none;color:var(--rose-600);font-size:.875rem;font-weight:600;margin-top:1rem;background:rgba(255,241,242,.5);transition:.15s" onmouseover="this.style.borderColor='#fb7185'" onmouseout="this.style.borderColor='#fecdd3'">
        <span>🛍️ Hacer un nuevo pedido</span>
        <span>→</span>
    </a>

    <?php endif; ?>
</main>

<!-- Modal pedir -->
<div class="modal fade" id="modalPedir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0" style="overflow:hidden">
            <!-- Product hero -->
            <div id="modal-img-container" style="position:relative;height:180px;background:linear-gradient(135deg,#fecdd3,#f9a8d4)">
                <img id="modal-img" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none">
                <div id="modal-img-placeholder" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem">🌸</div>
                <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.55),transparent)"></div>
                <div style="position:absolute;bottom:1rem;left:1.25rem;right:1.25rem">
                    <div id="modal-nombre" style="color:#fff;font-weight:800;font-size:1.2rem"></div>
                    <div id="modal-precio" style="color:#fff;font-size:1.1rem;font-weight:700;margin-top:.1rem"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    style="position:absolute;top:.75rem;right:.75rem;background-color:rgba(255,255,255,.25)"></button>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="pedir">
                <input type="hidden" name="id_catalogo" id="modal-id">
                <div class="modal-body">
                    <h6 class="fw-bold mb-3">Datos de entrega</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label-sm d-block">Nombre</label>
                            <input type="text" class="form-control" value="<?= h($user['nombre']) ?>" disabled style="background:#f8f9fa">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm d-block">Teléfono <span class="text-danger">*</span></label>
                            <input type="tel" name="telefono" class="form-control" required placeholder="+54 9 11..."
                                   value="<?= h($searchPhone) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm d-block">Medio de pago</label>
                            <select name="medio_pago" class="form-select">
                                <option value="">— Seleccionar —</option>
                                <?php foreach (['Efectivo','Transferencia','Tarjeta','MercadoPago'] as $mp): ?>
                                <option value="<?= $mp ?>"><?= $mp ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label-sm d-block">Dirección de entrega <span class="text-danger">*</span></label>
                            <input type="text" name="direccion_entrega" class="form-control" required placeholder="Av. Corrientes 1234, CABA">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm d-block">Fecha de entrega <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_entrega" class="form-control" required value="<?= $manana ?>" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm d-block">Hora preferida</label>
                            <input type="time" name="hora_entrega" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label-sm d-block">Mensaje para la tarjeta</label>
                            <textarea name="mensaje_personal" class="form-control" rows="2" placeholder="¡Feliz cumpleaños! Con todo mi cariño..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose px-4">✅ Confirmar pedido</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setPedirItem(id, nombre, precio, imgUrl) {
    document.getElementById('modal-id').value = id;
    document.getElementById('modal-nombre').textContent = nombre;
    document.getElementById('modal-precio').textContent = '$' + precio.toLocaleString('es-AR');
    const img = document.getElementById('modal-img');
    const placeholder = document.getElementById('modal-img-placeholder');
    if (imgUrl) {
        img.src = imgUrl;
        img.style.display = 'block';
        placeholder.style.display = 'none';
    } else {
        img.style.display = 'none';
        placeholder.style.display = 'flex';
    }
}
</script>
</body>
</html>

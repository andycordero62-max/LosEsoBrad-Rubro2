<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = requireRole('admin', 'empleado');
$pdo  = getDB();
$id   = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.*, ca.foto_url AS cat_imagen, c.telefono AS cli_telefono, c.email AS cli_email
    FROM pedidos p
    LEFT JOIN catalogo_arreglos ca ON ca.id_catalogo_arreglo = p.id_catalogo_arreglo
    LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
    WHERE p.id_pedido = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { header('Location: /ALESLI/pedidos.php'); exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'cambiar_estado') {
        $nuevoEstado = $_POST['nuevo_estado'] ?? '';
        $estados     = ['pendiente','preparando','enviado','entregado','cancelado'];
        if (in_array($nuevoEstado, $estados)) {
            $foto  = trim($_POST['foto_url'] ?? '');
            $notas = trim($_POST['notas'] ?? '');
            $pdo->prepare("UPDATE pedidos SET estado=?, foto_evidencia_url=?, notas=? WHERE id_pedido=?")
                ->execute([$nuevoEstado, $foto ?: null, $notas ?: null, $id]);
            // Si se marca como entregado, registrar transacción de ingreso automáticamente
            if ($nuevoEstado === 'entregado' && $p['monto'] > 0) {
                $ya = $pdo->prepare("SELECT COUNT(*) FROM transacciones WHERE id_pedido=? AND tipo='ingreso'");
                $ya->execute([$id]);
                if ($ya->fetchColumn() == 0) {
                    $pdo->prepare("INSERT INTO transacciones (id_pedido, id_usuario, tipo, monto, categoria, descripcion, medio_pago) VALUES (?,?,?,?,?,?,?)")
                        ->execute([$id, $user['id'], 'ingreso', $p['monto'], 'Venta', "Pedido #{$id} — {$p['producto']}", $p['medio_pago']]);
                }
            }
            $msg = 'Estado actualizado a: ' . ucfirst($nuevoEstado);
        }

    } elseif ($action === 'editar_monto') {
        $monto = (float)$_POST['monto'];
        $pdo->prepare("UPDATE pedidos SET monto=?, medio_pago=? WHERE id_pedido=?")
            ->execute([$monto, $_POST['medio_pago'] ?? null, $id]);
        $msg = 'Monto actualizado.';
    }

    // Recargar pedido
    $stmt->execute([$id]); $p = $stmt->fetch();
}

$estados = [
    'pendiente'  => ['color'=>'#fef3c7','label'=>'Pendiente',  'next'=>'preparando'],
    'preparando' => ['color'=>'#dbeafe','label'=>'Preparando', 'next'=>'enviado'],
    'enviado'    => ['color'=>'#ede9fe','label'=>'Enviado',    'next'=>'entregado'],
    'entregado'  => ['color'=>'#d1fae5','label'=>'Entregado',  'next'=>null],
    'cancelado'  => ['color'=>'#fee2e2','label'=>'Cancelado',  'next'=>null],
];
$estadoInfo = $estados[$p['estado']] ?? $estados['pendiente'];

renderHead("Pedido #{$id}");
renderSidebar($user, 'pedidos');
?>
<div class="main-content">
    <div class="topbar">
        <h1>📋 Pedido #<?= $id ?></h1>
        <a href="/ALESLI/pedidos.php" style="color:#888;font-size:.875rem;text-decoration:none">← Volver a pedidos</a>
    </div>
    <div class="page-body">
        <?php if ($msg): ?><div class="alert-success-custom mb-3">✅ <?= h($msg) ?></div><?php endif; ?>

        <!-- Progreso de estados -->
        <div class="form-card mb-4">
            <h6 class="fw-bold mb-3">Progreso del pedido</h6>
            <div class="d-flex align-items-center gap-1 flex-wrap">
                <?php foreach ($estados as $key => $info): if ($key === 'cancelado') continue; ?>
                <div class="d-flex align-items-center gap-1">
                    <div class="px-3 py-1 rounded-3 fw-semibold" style="font-size:.78rem;background:<?= $p['estado']===$key ? $info['color'] : '#f0f0f0' ?>;color:<?= $p['estado']===$key ? '#333' : '#aaa' ?>">
                        <?= $info['label'] ?>
                    </div>
                    <?php if ($key !== 'entregado'): ?><span style="color:#ddd">→</span><?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if ($p['estado']==='cancelado'): ?>
                <div class="px-3 py-1 rounded-3 fw-semibold" style="font-size:.78rem;background:#fee2e2;color:#991b1b">Cancelado</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Panel izquierdo -->
            <div class="col-md-4">
                <?php if ($p['cat_imagen']): ?>
                <img src="<?= h($p['cat_imagen']) ?>" alt="" class="rounded-4 w-100 mb-3" style="height:200px;object-fit:cover">
                <?php else: ?>
                <div class="rounded-4 mb-3 d-flex align-items-center justify-content-center" style="height:160px;background:linear-gradient(135deg,#fecdd3,#f9a8d4);font-size:3rem">🌸</div>
                <?php endif; ?>

                <!-- Cambiar estado -->
                <div class="form-card mb-3">
                    <h6 class="fw-bold mb-3">Gestionar estado</h6>
                    <div class="mb-3">
                        <span class="badge-<?= h($p['estado']) ?>" style="font-size:.9rem;padding:.4rem .9rem"><?= ucfirst(h($p['estado'])) ?></span>
                    </div>

                    <?php if ($estadoInfo['next']): ?>
                    <button class="btn-rose w-100 justify-content-center mb-2"
                        data-bs-toggle="modal" data-bs-target="#modalCambioEstado"
                        onclick="setNuevoEstado('<?= $estadoInfo['next'] ?>', '<?= ucfirst($estadoInfo['next']) ?>')">
                        ✅ Marcar como "<?= ucfirst($estadoInfo['next']) ?>"
                    </button>
                    <?php endif; ?>

                    <?php if (!in_array($p['estado'], ['cancelado'])): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="cambiar_estado">
                        <input type="hidden" name="nuevo_estado" value="cancelado">
                        <button type="submit" class="btn btn-outline-danger w-100 rounded-3" style="font-size:.855rem"
                            onclick="return confirm('¿Cancelar este pedido?')">Cancelar pedido</button>
                    </form>
                    <?php elseif ($p['estado'] === 'cancelado'): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="cambiar_estado">
                        <input type="hidden" name="nuevo_estado" value="pendiente">
                        <button type="submit" class="btn btn-outline-secondary w-100 rounded-3" style="font-size:.855rem">↩ Restaurar a pendiente</button>
                    </form>
                    <?php endif; ?>
                </div>

                <!-- Monto y pago -->
                <div class="form-card">
                    <h6 class="fw-bold mb-3">💰 Pago</h6>
                    <form method="POST">
                        <input type="hidden" name="action" value="editar_monto">
                        <div class="mb-2">
                            <label class="form-label-sm d-block">Monto (Bs.)</label>
                            <input type="number" name="monto" class="form-control" step="0.01" min="0" value="<?= h($p['monto'] ?? 0) ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label-sm d-block">Medio de pago</label>
                            <select name="medio_pago" class="form-select">
                                <option value="">—</option>
                                <?php foreach (['Efectivo','Transferencia','QR','Tarjeta'] as $mp): ?>
                                <option value="<?= $mp ?>" <?= ($p['medio_pago']??'')===$mp?'selected':'' ?>><?= $mp ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary w-100 rounded-3" style="font-size:.855rem">Actualizar pago</button>
                    </form>
                </div>
            </div>

            <!-- Panel derecho -->
            <div class="col-md-8">
                <div class="form-card">
                    <h6 class="fw-bold mb-3">🌺 <?= h($p['producto']) ?></h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="form-label-sm">Cliente</div>
                            <div class="fw-semibold"><?= h($p['nombre_cliente']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="form-label-sm">Teléfono</div>
                            <div><?= h($p['telefono'] ?? $p['cli_telefono'] ?? '—') ?></div>
                        </div>
                        <div class="col-12">
                            <div class="form-label-sm">Dirección de entrega</div>
                            <div><?= h($p['direccion_entrega']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="form-label-sm">Fecha de entrega</div>
                            <div class="fw-semibold"><?= date('d/m/Y', strtotime($p['fecha_entrega'])) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="form-label-sm">Hora</div>
                            <div><?= $p['hora_entrega'] ? h(substr($p['hora_entrega'],0,5)) : '—' ?></div>
                        </div>
                        <div class="col-6">
                            <div class="form-label-sm">Medio de pago</div>
                            <div><?= h($p['medio_pago'] ?? '—') ?></div>
                        </div>
                        <div class="col-6">
                            <div class="form-label-sm">Monto</div>
                            <div class="fw-bold" style="color:var(--rose-600)"><?= $p['monto'] ? 'Bs. '.number_format($p['monto'],2) : '—' ?></div>
                        </div>
                        <div class="col-6">
                            <div class="form-label-sm">Registrado</div>
                            <div style="font-size:.8rem"><?= date('d/m/Y H:i', strtotime($p['fecha_registro'])) ?></div>
                        </div>
                        <?php if ($p['mensaje_personalizado']): ?>
                        <div class="col-12">
                            <div class="form-label-sm">Mensaje personal</div>
                            <div class="p-3 rounded-3" style="background:#fff1f2;color:#be123c;font-style:italic">
                                "<?= h($p['mensaje_personalizado']) ?>"
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($p['notas']): ?>
                        <div class="col-12">
                            <div class="form-label-sm">Notas</div>
                            <div class="p-2 rounded-3" style="background:#f8f9fa"><?= h($p['notas']) ?></div>
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

<!-- Modal cambio de estado -->
<div class="modal fade" id="modalCambioEstado" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Actualizar estado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="cambiar_estado">
                    <input type="hidden" name="nuevo_estado" id="modal_estado_val">
                    <p>Cambiar estado a: <strong id="modal_estado_label"></strong></p>
                    <div class="mb-3">
                        <label class="form-label-sm d-block">URL foto de evidencia (opcional)</label>
                        <input type="url" name="foto_url" class="form-control" placeholder="https://…">
                    </div>
                    <div>
                        <label class="form-label-sm d-block">Notas (opcional)</label>
                        <textarea name="notas" class="form-control" rows="2"></textarea>
                    </div>
                    <div id="nota_ingreso" class="alert-warn-custom mt-2" style="display:none">
                        💡 Al marcar como "Entregado" se registrará automáticamente el ingreso en Contabilidad.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function setNuevoEstado(val, label) {
    document.getElementById('modal_estado_val').value = val;
    document.getElementById('modal_estado_label').textContent = label;
    document.getElementById('nota_ingreso').style.display = val === 'entregado' ? 'block' : 'none';
}
</script>
<?php renderFoot(); ?>

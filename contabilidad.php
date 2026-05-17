<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user  = requireRole('admin', 'empleado');
$pdo   = getDB();
$msg   = '';
$error = '';

// ── Acción: registrar transacción ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'crear') {
        $monto = (float)($_POST['monto'] ?? 0);
        if ($monto <= 0) { $error = 'El monto debe ser mayor a 0.'; }
        else {
            $stmt = $pdo->prepare("INSERT INTO transacciones (id_usuario, tipo, monto, categoria, descripcion, medio_pago) VALUES (?,?,?,?,?,?)");
            $stmt->execute([
                $user['id'],
                $_POST['tipo'],
                $monto,
                $_POST['categoria'] ?? null,
                trim($_POST['descripcion'] ?? '') ?: null,
                $_POST['medio_pago'] ?? null,
            ]);
            $msg = 'Transacción registrada.';
        }
    } elseif ($action === 'eliminar' && $user['rol'] === 'admin') {
        $pdo->prepare("DELETE FROM transacciones WHERE id_transaccion=?")->execute([(int)$_POST['id']]);
        $msg = 'Transacción eliminada.';
    }
}

// ── Filtros ──
$mes  = $_GET['mes']  ?? date('Y-m');
$tipo = $_GET['tipo'] ?? '';

$where  = ["DATE_FORMAT(fecha,'%Y-%m') = ?"];
$params = [$mes];
if ($tipo) { $where[] = "tipo = ?"; $params[] = $tipo; }

$sql  = "SELECT t.*, u.nombre AS usuario_nombre FROM transacciones t LEFT JOIN usuarios u ON u.id_usuario=t.id_usuario WHERE " . implode(' AND ', $where) . " ORDER BY t.fecha DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$transacciones = $stmt->fetchAll();

// ── Totales del mes ──
$stmt2 = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tipo='ingreso' THEN monto ELSE 0 END),0) AS ingresos, COALESCE(SUM(CASE WHEN tipo='egreso' THEN monto ELSE 0 END),0) AS egresos FROM transacciones WHERE DATE_FORMAT(fecha,'%Y-%m')=?");
$stmt2->execute([$mes]); $totales = $stmt2->fetch();
$balance = $totales['ingresos'] - $totales['egresos'];

// ── Datos para gráfico de torta (categorías) ──
$catData = $pdo->prepare("SELECT categoria, SUM(monto) AS total FROM transacciones WHERE DATE_FORMAT(fecha,'%Y-%m')=? GROUP BY categoria");
$catData->execute([$mes]); $catData = $catData->fetchAll();

$chartLabels = json_encode(array_column($catData, 'categoria'));
$chartVals   = json_encode(array_map('floatval', array_column($catData, 'total')));

// ── Gráfico línea: ingresos/egresos diarios del mes ──
$lineData = $pdo->prepare("
    SELECT DATE(fecha) as dia,
           SUM(CASE WHEN tipo='ingreso' THEN monto ELSE 0 END) as ingresos,
           SUM(CASE WHEN tipo='egreso'  THEN monto ELSE 0 END) as egresos
    FROM transacciones WHERE DATE_FORMAT(fecha,'%Y-%m')=?
    GROUP BY DATE(fecha) ORDER BY dia
");
$lineData->execute([$mes]); $lineData = $lineData->fetchAll();
$lineDias      = json_encode(array_column($lineData, 'dia'));
$lineIngresos  = json_encode(array_map('floatval', array_column($lineData, 'ingresos')));
$lineEgresos   = json_encode(array_map('floatval', array_column($lineData, 'egresos')));

renderHead('Contabilidad', true);
renderSidebar($user, 'contabilidad');
?>
<div class="main-content">
    <div class="topbar">
        <h1>💰 Contabilidad</h1>
        <button class="btn-rose" data-bs-toggle="modal" data-bs-target="#modalCrear">➕ Nueva transacción</button>
    </div>
    <div class="page-body">
        <?php if ($msg):   ?><div class="alert-success-custom mb-3">✅ <?= h($msg)   ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error-custom   mb-3">⚠️ <?= h($error) ?></div><?php endif; ?>

        <!-- Resumen del mes -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#d1fae5">💰</div>
                    <div class="stat-val" style="font-size:1.5rem;color:#065f46">Bs. <?= number_format($totales['ingresos'],2) ?></div>
                    <div class="stat-label">Ingresos</div>
                    <div class="stat-sub"><?= h($mes) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fee2e2">💸</div>
                    <div class="stat-val" style="font-size:1.5rem;color:#991b1b">Bs. <?= number_format($totales['egresos'],2) ?></div>
                    <div class="stat-label">Egresos</div>
                    <div class="stat-sub"><?= h($mes) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:<?= $balance >= 0 ? '#d1fae5' : '#fee2e2' ?>"><?= $balance >= 0 ? '📈' : '📉' ?></div>
                    <div class="stat-val" style="font-size:1.5rem;color:<?= $balance >= 0 ? '#065f46' : '#991b1b' ?>">
                        <?= $balance < 0 ? '-' : '' ?>Bs. <?= number_format(abs($balance),2) ?>
                    </div>
                    <div class="stat-label">Balance neto</div>
                    <div class="stat-sub"><?= h($mes) ?></div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row g-4 mb-4">
            <div class="col-md-7">
                <div class="chart-card">
                    <h6>📈 Ingresos vs Egresos diarios</h6>
                    <canvas id="chartLine" height="100"></canvas>
                </div>
            </div>
            <div class="col-md-5">
                <div class="chart-card">
                    <h6>🍩 Por categoría</h6>
                    <canvas id="chartPie" height="150"></canvas>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <form method="GET" class="d-flex gap-2 flex-wrap mb-3 align-items-end">
            <div>
                <label class="form-label-sm d-block">Mes</label>
                <input type="month" name="mes" value="<?= h($mes) ?>" class="form-control">
            </div>
            <div>
                <label class="form-label-sm d-block">Tipo</label>
                <select name="tipo" class="form-select" style="min-width:130px">
                    <option value="">Todos</option>
                    <option value="ingreso" <?= $tipo==='ingreso'?'selected':'' ?>>Ingreso</option>
                    <option value="egreso"  <?= $tipo==='egreso' ?'selected':'' ?>>Egreso</option>
                </select>
            </div>
            <button type="submit" class="btn-rose">Filtrar</button>
        </form>

        <!-- Tabla -->
        <div class="table-card">
            <table class="table mb-0">
                <thead>
                    <tr><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Descripción</th><th>Medio de pago</th><th>Monto</th><th>Usuario</th>
                    <?php if ($user['rol']==='admin'): ?><th></th><?php endif; ?></tr>
                </thead>
                <tbody>
                <?php foreach ($transacciones as $t): ?>
                <tr>
                    <td style="font-size:.78rem;color:#888"><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                    <td><span class="badge-<?= $t['tipo'] ?>"><?= ucfirst(h($t['tipo'])) ?></span></td>
                    <td style="font-size:.82rem"><?= h($t['categoria'] ?? '—') ?></td>
                    <td style="font-size:.82rem"><?= h($t['descripcion'] ?? '—') ?></td>
                    <td style="font-size:.8rem;color:#888"><?= h($t['medio_pago'] ?? '—') ?></td>
                    <td class="fw-bold" style="color:<?= $t['tipo']==='ingreso'?'#065f46':'#991b1b' ?>">
                        <?= $t['tipo']==='ingreso'?'+':'-' ?>Bs. <?= number_format($t['monto'],2) ?>
                    </td>
                    <td style="font-size:.78rem;color:#888"><?= h($t['usuario_nombre']??'—') ?></td>
                    <?php if ($user['rol']==='admin'): ?>
                    <td>
                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar transacción?')">
                            <input type="hidden" name="action" value="eliminar">
                            <input type="hidden" name="id" value="<?= $t['id_transaccion'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" style="font-size:.7rem">✕</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($transacciones)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">No hay transacciones para este período.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal nueva transacción -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Nueva transacción</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="crear">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Tipo <span class="text-danger">*</span></label>
                        <select name="tipo" class="form-select" required>
                            <option value="ingreso">💰 Ingreso</option>
                            <option value="egreso">💸 Egreso</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Categoría</label>
                        <select name="categoria" class="form-select">
                            <option value="">— Seleccionar —</option>
                            <option value="Venta">Venta</option>
                            <option value="Curso">Curso</option>
                            <option value="Compra">Compra de materiales</option>
                            <option value="Servicio">Servicio / Transporte</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Monto (Bs.) <span class="text-danger">*</span></label>
                        <input type="number" name="monto" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Descripción</label>
                        <input type="text" name="descripcion" class="form-control" placeholder="Ej: Pago pedido #5, compra de rosas…">
                    </div>
                    <div>
                        <label class="form-label-sm d-block">Medio de pago</label>
                        <select name="medio_pago" class="form-select">
                            <option value="">— Seleccionar —</option>
                            <option>Efectivo</option><option>Transferencia</option><option>QR</option><option>Tarjeta</option>
                        </select>
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

<script>
// Gráfico de líneas
new Chart(document.getElementById('chartLine').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= $lineDias ?>,
        datasets: [
            { label:'Ingresos', data:<?= $lineIngresos ?>, borderColor:'#10b981', backgroundColor:'rgba(16,185,129,.1)', tension:.3, fill:true },
            { label:'Egresos',  data:<?= $lineEgresos  ?>, borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,.08)',  tension:.3, fill:true }
        ]
    },
    options:{ responsive:true, scales:{ y:{ beginAtZero:true, ticks:{ callback:v=>'Bs.'+v } } } }
});

// Gráfico de torta
new Chart(document.getElementById('chartPie').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?= $chartLabels ?>,
        datasets:[{ data:<?= $chartVals ?>, backgroundColor:['#e11d48','#ec4899','#f97316','#eab308','#10b981','#6366f1'], hoverOffset:4 }]
    },
    options:{ responsive:true, plugins:{ legend:{ position:'bottom' } } }
});
</script>
<?php renderFoot(); ?>

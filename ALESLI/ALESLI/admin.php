<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user  = requireRole('admin');
$pdo   = getDB();
$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'crear') {
        $nombre = trim($_POST['nombre']   ?? '');
        $email  = trim($_POST['email']    ?? '');
        $pass   = $_POST['password']      ?? '';
        $rol    = $_POST['rol']           ?? 'empleado';
        if (!$nombre || !$email || !$pass) {
            $error = 'Todos los campos son obligatorios.';
        } elseif (strlen($pass) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            try {
                $pdo->prepare("INSERT INTO usuarios (nombre, email, contrasena, rol) VALUES (?, ?, ?, ?)")
                    ->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT), $rol]);
                $msg = "Usuario $nombre creado.";
            } catch (PDOException $e) { $error = 'El email ya está registrado.'; }
        }

    } elseif ($action === 'eliminar') {
        $uid = (int)$_POST['id'];
        if ($uid === (int)$user['id']) { $error = 'No podés eliminarte a vos mismo.'; }
        else {
            $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([$uid]);
            $msg = 'Usuario eliminado.';
        }

    } elseif ($action === 'cambiar_pass') {
        $uid  = (int)$_POST['id'];
        $pass = $_POST['password'] ?? '';
        if (strlen($pass) < 6) { $error = 'Mínimo 6 caracteres.'; }
        else {
            $pdo->prepare("UPDATE usuarios SET contrasena=? WHERE id_usuario=?")->execute([password_hash($pass, PASSWORD_DEFAULT), $uid]);
            $msg = 'Contraseña actualizada.';
        }
    }
}

$usuarios = $pdo->query("SELECT id_usuario, nombre, email, rol, creado_en FROM usuarios ORDER BY id_usuario")->fetchAll();
$conteos  = [];
foreach ($pdo->query("SELECT rol, COUNT(*) as n FROM usuarios GROUP BY rol")->fetchAll() as $r) $conteos[$r['rol']] = $r['n'];

renderHead('Administración');
renderSidebar($user, 'admin');
?>
<div class="main-content">
    <div class="topbar">
        <h1>👥 Usuarios del sistema</h1>
        <button class="btn-rose" data-bs-toggle="modal" data-bs-target="#modalCrear">➕ Nuevo usuario</button>
    </div>
    <div class="page-body">
        <?php if ($msg):   ?><div class="alert-success-custom mb-4">✅ <?= h($msg)   ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error-custom   mb-4">⚠️ <?= h($error) ?></div><?php endif; ?>

        <div class="row g-3 mb-4">
            <?php foreach ([['admin','🔴','Admins'],['empleado','🟡','Empleados']] as [$r,$ico,$label]): ?>
            <div class="col-6">
                <div class="stat-card text-center">
                    <div style="font-size:1.5rem"><?= $ico ?></div>
                    <div class="stat-val"><?= $conteos[$r] ?? 0 ?></div>
                    <div class="stat-label"><?= $label ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="table-card">
            <table class="table mb-0">
                <thead><tr><th>#</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Registro</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td class="text-muted"><?= $u['id_usuario'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#e11d48,#ec4899);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.75rem;flex-shrink:0">
                                <?= h(mb_strtoupper(mb_substr($u['nombre'],0,1))) ?>
                            </div>
                            <span class="fw-semibold"><?= h($u['nombre']) ?></span>
                        </div>
                    </td>
                    <td style="font-size:.85rem;color:#555"><?= h($u['email']) ?></td>
                    <td>
                        <span class="badge-<?= $u['rol']==='admin'?'cancelado':'pendiente' ?>" style="font-size:.7rem">
                            <?= ucfirst(h($u['rol'])) ?>
                        </span>
                    </td>
                    <td style="font-size:.78rem;color:#888"><?= date('d/m/Y', strtotime($u['creado_en'])) ?></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <button class="btn btn-sm btn-outline-secondary rounded-3" style="font-size:.72rem"
                                data-bs-toggle="modal" data-bs-target="#modalPass"
                                onclick="setPassUid(<?= $u['id_usuario'] ?>, '<?= h(addslashes($u['nombre'])) ?>')">
                                🔑 Contraseña
                            </button>
                            <?php if ($u['id_usuario'] !== (int)$user['id']): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar a <?= h(addslashes($u['nombre'])) ?>?')">
                                <input type="hidden" name="action" value="eliminar">
                                <input type="hidden" name="id" value="<?= $u['id_usuario'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" style="font-size:.72rem">✕</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal crear -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Nuevo usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="crear">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label-sm d-block">Nombre completo</label><input type="text" name="nombre" class="form-control" required placeholder="María García"></div>
                    <div class="mb-3"><label class="form-label-sm d-block">Email</label><input type="email" name="email" class="form-control" required placeholder="maria@alesli.com"></div>
                    <div class="mb-3"><label class="form-label-sm d-block">Contraseña (mín. 6 caracteres)</label><input type="password" name="password" class="form-control" required minlength="6" placeholder="••••••••"></div>
                    <div><label class="form-label-sm d-block">Rol</label>
                        <select name="rol" class="form-select">
                            <option value="empleado">Empleado</option>
                            <option value="admin">Admin</option>
                        </select>
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

<!-- Modal contraseña -->
<div class="modal fade" id="modalPass" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Cambiar contraseña</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="cambiar_pass">
                <input type="hidden" name="id" id="pass_uid">
                <div class="modal-body">
                    <p class="text-muted" style="font-size:.875rem">Usuario: <strong id="pass_nombre"></strong></p>
                    <div><label class="form-label-sm d-block">Nueva contraseña</label><input type="password" name="password" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function setPassUid(id, nombre) {
    document.getElementById('pass_uid').value = id;
    document.getElementById('pass_nombre').textContent = nombre;
}
</script>
<?php renderFoot(); ?>

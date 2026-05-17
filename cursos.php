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

    if ($action === 'crear_curso' && $user['rol'] === 'admin') {
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) { $error = 'El nombre del curso es obligatorio.'; }
        else {
            $pdo->prepare("INSERT INTO cursos (nombre, descripcion, precio, fecha_inicio, fecha_fin, cupo_maximo) VALUES (?,?,?,?,?,?)")
                ->execute([$nombre, trim($_POST['descripcion']??'') ?: null, (float)$_POST['precio'], $_POST['fecha_inicio']??null, $_POST['fecha_fin']??null, (int)$_POST['cupo_maximo']]);
            $msg = "Curso \"$nombre\" creado.";
        }

    } elseif ($action === 'toggle_curso' && $user['rol'] === 'admin') {
        $pdo->prepare("UPDATE cursos SET activo = 1 - activo WHERE id_curso=?")->execute([(int)$_POST['id']]);
        $msg = 'Estado del curso actualizado.';

    } elseif ($action === 'crear_alumno') {
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) { $error = 'El nombre del alumno es obligatorio.'; }
        else {
            $pdo->prepare("INSERT INTO alumnos (nombre, telefono, email) VALUES (?,?,?)")
                ->execute([$nombre, trim($_POST['telefono']??'') ?: null, trim($_POST['email']??'') ?: null]);
            $msg = "Alumno \"$nombre\" registrado.";
        }

    } elseif ($action === 'inscribir') {
        $idAlumno = (int)$_POST['id_alumno'];
        $idCurso  = (int)$_POST['id_curso'];
        // Verificar cupo
        $cupo = $pdo->prepare("SELECT c.cupo_maximo, COUNT(i.id_inscripcion) AS inscritos FROM cursos c LEFT JOIN inscripciones i ON i.id_curso=c.id_curso WHERE c.id_curso=? GROUP BY c.id_curso");
        $cupo->execute([$idCurso]); $c = $cupo->fetch();
        if ($c && $c['inscritos'] >= $c['cupo_maximo']) {
            $error = 'El curso está lleno.';
        } else {
            // Verificar si ya está inscrito
            $dup = $pdo->prepare("SELECT id_inscripcion FROM inscripciones WHERE id_alumno=? AND id_curso=?");
            $dup->execute([$idAlumno, $idCurso]);
            if ($dup->fetch()) { $error = 'El alumno ya está inscrito en este curso.'; }
            else {
                $pdo->prepare("INSERT INTO inscripciones (id_alumno, id_curso, monto_pagado, estado_pago) VALUES (?,?,?,?)")
                    ->execute([$idAlumno, $idCurso, (float)$_POST['monto_pagado'], $_POST['estado_pago']]);
                $msg = 'Inscripción registrada correctamente.';
            }
        }

    } elseif ($action === 'cancelar_inscripcion') {
        $pdo->prepare("DELETE FROM inscripciones WHERE id_inscripcion=?")->execute([(int)$_POST['id']]);
        $msg = 'Inscripción cancelada.';
    }
}

// ── Datos ──
$cursos  = $pdo->query("
    SELECT c.*, COUNT(i.id_inscripcion) AS inscritos
    FROM cursos c LEFT JOIN inscripciones i ON i.id_curso=c.id_curso
    GROUP BY c.id_curso ORDER BY c.fecha_inicio DESC
")->fetchAll();

$alumnos = $pdo->query("SELECT * FROM alumnos ORDER BY nombre")->fetchAll();

// Inscripciones del curso seleccionado
$cursoVer = (int)($_GET['curso'] ?? 0);
$cursoDet = null;
$inscripciones = [];
if ($cursoVer) {
    $s = $pdo->prepare("SELECT * FROM cursos WHERE id_curso=?");
    $s->execute([$cursoVer]); $cursoDet = $s->fetch();
    $inscripciones = $pdo->prepare("
        SELECT i.*, a.nombre AS alumno_nombre, a.telefono AS alumno_tel, a.email AS alumno_email
        FROM inscripciones i
        LEFT JOIN alumnos a ON a.id_alumno=i.id_alumno
        WHERE i.id_curso=? ORDER BY i.fecha_inscripcion
    ");
    $inscripciones->execute([$cursoVer]); $inscripciones = $inscripciones->fetchAll();
}

renderHead('Cursos');
renderSidebar($user, 'cursos');
?>
<div class="main-content">
    <div class="topbar">
        <h1>🎓 Cursos</h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary rounded-3" style="font-size:.855rem" data-bs-toggle="modal" data-bs-target="#modalAlumno">👤 Nuevo alumno</button>
            <?php if ($user['rol'] === 'admin'): ?>
            <button class="btn-rose" data-bs-toggle="modal" data-bs-target="#modalCurso">➕ Nuevo curso</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="page-body">
        <?php if ($msg):   ?><div class="alert-success-custom mb-3">✅ <?= h($msg)   ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error-custom   mb-3">⚠️ <?= h($error) ?></div><?php endif; ?>

        <div class="row g-4">
            <!-- Lista de cursos -->
            <div class="col-md-6">
                <h5 class="fw-bold mb-3">📋 Cursos disponibles</h5>
                <?php foreach ($cursos as $c): ?>
                <div class="form-card mb-3 <?= !$c['activo'] ? 'opacity-50' : '' ?>" style="border-left:4px solid var(--rose-500)">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <div class="fw-bold"><?= h($c['nombre']) ?></div>
                            <?php if ($c['descripcion']): ?><div style="font-size:.8rem;color:#666"><?= h(mb_substr($c['descripcion'],0,80)) ?></div><?php endif; ?>
                        </div>
                        <span class="badge-<?= $c['activo'] ? 'entregado' : 'cancelado' ?>"><?= $c['activo'] ? 'Activo':'Inactivo' ?></span>
                    </div>
                    <div class="d-flex flex-wrap gap-3" style="font-size:.78rem;color:#888">
                        <?php if ($c['fecha_inicio']): ?><span>📅 <?= date('d/m/Y', strtotime($c['fecha_inicio'])) ?> → <?= $c['fecha_fin'] ? date('d/m/Y', strtotime($c['fecha_fin'])) : '?' ?></span><?php endif; ?>
                        <span>💰 Bs. <?= number_format($c['precio'],2) ?></span>
                        <span>👥 <?= $c['inscritos'] ?> / <?= $c['cupo_maximo'] ?></span>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <a href="?curso=<?= $c['id_curso'] ?>" class="btn btn-sm btn-outline-primary rounded-3" style="font-size:.75rem">👥 Ver inscritos</a>
                        <button class="btn btn-sm rounded-3" style="font-size:.75rem" data-bs-toggle="modal" data-bs-target="#modalInscribir"
                            onclick="selCurso(<?= $c['id_curso'] ?>, '<?= h(addslashes($c['nombre'])) ?>')">
                            ➕ Inscribir alumno
                        </button>
                        <?php if ($user['rol']==='admin'): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle_curso">
                            <input type="hidden" name="id" value="<?= $c['id_curso'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-3" style="font-size:.75rem">
                                <?= $c['activo'] ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($cursos)): ?>
                <p class="text-muted">No hay cursos registrados.</p>
                <?php endif; ?>
            </div>

            <!-- Detalle inscripciones -->
            <div class="col-md-6">
                <?php if ($cursoDet): ?>
                <h5 class="fw-bold mb-3">👥 Inscritos: <?= h($cursoDet['nombre']) ?></h5>
                <?php if (empty($inscripciones)): ?>
                <p class="text-muted">No hay alumnos inscritos en este curso.</p>
                <?php else: ?>
                <div class="table-card">
                    <table class="table mb-0">
                        <thead><tr><th>Alumno</th><th>Contacto</th><th>Monto</th><th>Pago</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($inscripciones as $i): ?>
                        <tr>
                            <td class="fw-semibold" style="font-size:.855rem"><?= h($i['alumno_nombre']) ?></td>
                            <td style="font-size:.75rem;color:#888"><?= h($i['alumno_tel']??'—') ?></td>
                            <td>Bs. <?= number_format($i['monto_pagado'] ?? 0, 2) ?></td>
                            <td><span class="badge-<?= $i['estado_pago']==='completado'?'entregado':'pendiente' ?>"><?= ucfirst($i['estado_pago']) ?></span></td>
                            <td>
                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Cancelar inscripción?')">
                                    <input type="hidden" name="action" value="cancelar_inscripcion">
                                    <input type="hidden" name="id" value="<?= $i['id_inscripcion'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" style="font-size:.7rem">✕</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div style="background:#f8f9fa;border-radius:16px;padding:2rem;text-align:center;color:#888">
                    <div style="font-size:2rem">🎓</div>
                    <p class="mt-2">Seleccioná un curso para ver sus inscripciones.</p>
                </div>

                <h5 class="fw-bold mt-4 mb-3">👤 Alumnos registrados</h5>
                <div class="table-card">
                    <table class="table mb-0">
                        <thead><tr><th>Nombre</th><th>Teléfono</th><th>Email</th></tr></thead>
                        <tbody>
                        <?php foreach ($alumnos as $a): ?>
                        <tr>
                            <td class="fw-semibold" style="font-size:.855rem"><?= h($a['nombre']) ?></td>
                            <td style="font-size:.78rem;color:#888"><?= h($a['telefono']??'—') ?></td>
                            <td style="font-size:.78rem;color:#888"><?= h($a['email']??'—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($alumnos)): ?><tr><td colspan="3" class="text-center text-muted py-3">Sin alumnos aún.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal nuevo curso -->
<div class="modal fade" id="modalCurso" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Nuevo curso</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="crear_curso">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label-sm d-block">Nombre <span class="text-danger">*</span></label><input type="text" name="nombre" class="form-control" required placeholder="Arreglos Básico"></div>
                    <div class="mb-3"><label class="form-label-sm d-block">Descripción</label><textarea name="descripcion" class="form-control" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label-sm d-block">Precio (Bs.)</label><input type="number" name="precio" class="form-control" step="0.01" min="0" value="0"></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label-sm d-block">Fecha inicio</label><input type="date" name="fecha_inicio" class="form-control"></div>
                        <div class="col-6"><label class="form-label-sm d-block">Fecha fin</label><input type="date" name="fecha_fin" class="form-control"></div>
                    </div>
                    <div><label class="form-label-sm d-block">Cupo máximo</label><input type="number" name="cupo_maximo" class="form-control" min="1" value="10"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal nuevo alumno -->
<div class="modal fade" id="modalAlumno" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Nuevo alumno</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="crear_alumno">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label-sm d-block">Nombre <span class="text-danger">*</span></label><input type="text" name="nombre" class="form-control" required placeholder="Patricia Flores"></div>
                    <div class="mb-3"><label class="form-label-sm d-block">Teléfono</label><input type="tel" name="telefono" class="form-control" placeholder="72345678"></div>
                    <div><label class="form-label-sm d-block">Email</label><input type="email" name="email" class="form-control" placeholder="alumna@email.com"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal inscribir alumno -->
<div class="modal fade" id="modalInscribir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Inscribir alumno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="inscribir">
                <input type="hidden" name="id_curso" id="insc_curso_id">
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:.875rem">Curso: <strong id="insc_curso_nombre"></strong></p>
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Alumno <span class="text-danger">*</span></label>
                        <select name="id_alumno" class="form-select" required>
                            <option value="">— Seleccionar alumno —</option>
                            <?php foreach ($alumnos as $a): ?>
                            <option value="<?= $a['id_alumno'] ?>"><?= h($a['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Si no aparece, registrá el alumno primero.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm d-block">Monto pagado (Bs.)</label>
                        <input type="number" name="monto_pagado" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div>
                        <label class="form-label-sm d-block">Estado de pago</label>
                        <select name="estado_pago" class="form-select">
                            <option value="pendiente">Pendiente</option>
                            <option value="completado">Completado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-rose">Inscribir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selCurso(id, nombre) {
    document.getElementById('insc_curso_id').value = id;
    document.getElementById('insc_curso_nombre').textContent = nombre;
}
</script>
<?php renderFoot(); ?>

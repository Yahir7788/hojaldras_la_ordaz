<?php
/**
 * Administración de usuarios (solo gerente).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

$pdo = db();
$usuarioActual = usuario_actual();

$busqueda = trim((string) get('q', ''));
$rolFiltro = (int) get('rol', 0);
$estadoFiltro = (string) get('estado', 'activos');
if (!in_array($estadoFiltro, ['activos', 'inactivos', 'todos'], true)) {
    $estadoFiltro = 'activos';
}

$condiciones = [];
$parametros = [];

if ($estadoFiltro === 'activos') {
    $condiciones[] = 'u.activo = 1';
} elseif ($estadoFiltro === 'inactivos') {
    $condiciones[] = 'u.activo = 0';
}

if ($rolFiltro > 0) {
    $condiciones[] = 'u.id_rol = :rol';
    $parametros[':rol'] = $rolFiltro;
}

if ($busqueda !== '') {
    $condiciones[] = '(u.nombre_completo LIKE :busqueda OR u.usuario LIKE :busqueda OR u.correo LIKE :busqueda)';
    $parametros[':busqueda'] = '%' . $busqueda . '%';
}

$where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

$stmt = $pdo->prepare(
    "SELECT u.*, r.nombre AS rol
     FROM usuarios u
     INNER JOIN roles r ON r.id_rol = u.id_rol
     {$where}
     ORDER BY u.activo DESC, r.id_rol ASC, u.nombre_completo ASC
     LIMIT 300"
);
$stmt->execute($parametros);
$usuarios = $stmt->fetchAll();

$roles = $pdo->query('SELECT * FROM roles ORDER BY id_rol')->fetchAll();

$conteos = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(CASE WHEN u.activo = 1 THEN 1 ELSE 0 END) AS activos,
            SUM(CASE WHEN u.activo = 0 THEN 1 ELSE 0 END) AS inactivos,
            SUM(CASE WHEN u.activo = 1 AND r.nombre = 'gerente' THEN 1 ELSE 0 END) AS gerentes
     FROM usuarios u
     INNER JOIN roles r ON r.id_rol = u.id_rol"
)->fetch();

$gerentesActivos = contar_gerentes_activos($pdo);

$pageTitle = 'Usuarios del sistema';
$pageSubtitle = 'Cuentas, roles y accesos del personal de Hojaldras “La Ordaz”.';
$pageIcon = 'bi-people';
$pageActions = '<a class="btn btn-gold" href="' . e(base_url('usuarios/registrar.php')) . '"><i class="bi bi-person-plus me-1"></i>Nuevo usuario</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-coffee">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-people"></i></span></div>
            <p class="stat-value"><?= (int) $conteos['total'] ?></p>
            <div class="stat-label">Usuarios registrados</div>
            <div class="stat-hint"><i class="bi bi-journal-text"></i> Cuentas del sistema</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-success">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-person-check"></i></span></div>
            <p class="stat-value"><?= (int) $conteos['activos'] ?></p>
            <div class="stat-label">Cuentas activas</div>
            <div class="stat-hint"><i class="bi bi-check2-circle"></i> Pueden iniciar sesión</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-gold">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-shield-check"></i></span></div>
            <p class="stat-value"><?= (int) $conteos['gerentes'] ?></p>
            <div class="stat-label">Gerentes activos</div>
            <div class="stat-hint"><i class="bi bi-lock"></i> Acceso completo</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-<?= (int) $conteos['inactivos'] > 0 ? 'warning' : 'success' ?>">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-person-dash"></i></span></div>
            <p class="stat-value"><?= (int) $conteos['inactivos'] ?></p>
            <div class="stat-label">Cuentas inactivas</div>
            <div class="stat-hint"><i class="bi bi-slash-circle"></i> Sin acceso al sistema</div>
        </div>
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-body">
        <form method="get" action="<?= e(base_url('usuarios/index.php')) ?>" class="filter-bar">
            <div class="form-group form-group-wide">
                <label class="form-label" for="q">Buscar usuario</label>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" id="q" name="q" value="<?= e($busqueda) ?>"
                           placeholder="Nombre, usuario o correo…">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="rol">Rol</label>
                <select class="form-select" id="rol" name="rol">
                    <option value="0">Todos los roles</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= (int) $rol['id_rol'] ?>" <?= $rolFiltro === (int) $rol['id_rol'] ? 'selected' : '' ?>>
                            <?= e(etiqueta_rol($rol['nombre'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="estado">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="activos" <?= $estadoFiltro === 'activos' ? 'selected' : '' ?>>Activos</option>
                    <option value="inactivos" <?= $estadoFiltro === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                    <option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-coffee"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                <a href="<?= e(base_url('usuarios/index.php')) ?>" class="btn btn-soft" title="Limpiar filtros"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2 class="panel-title"><i class="bi bi-person-lines-fill"></i> Cuentas registradas</h2>
            <p class="panel-subtitle"><?= count($usuarios) ?> resultado(s) · Los cambios de rol y estado surten efecto de inmediato.</p>
        </div>
        <span class="chip"><i class="bi bi-shield-lock"></i> Contraseñas protegidas con hash</span>
    </div>
    <div class="panel-body panel-body-flush">
        <?php if (!$usuarios): ?>
            <div class="table-empty">
                <i class="bi bi-people"></i>
                <strong>No se encontraron usuarios</strong>
                <span>Cambia los filtros o registra una nueva cuenta.</span>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-app align-middle">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Último acceso</th>
                            <th>Fecha de alta</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php
                            $esCuentaActual = (int) $usuario['id_usuario'] === (int) $usuarioActual['id_usuario'];
                            $estaActivo = (int) $usuario['activo'] === 1;
                            $esGerenteActivo = $usuario['rol'] === 'gerente' && $estaActivo;
                            $esUltimoGerente = $esGerenteActivo && $gerentesActivos <= 1;
                            $claseRol = match ($usuario['rol']) {
                                'gerente'  => 'gold',
                                'cocinero' => 'warning',
                                default    => 'info',
                            };
                            ?>
                            <tr class="<?= $estaActivo ? '' : 'opacity-75' ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-sm"><?= e(iniciales($usuario['nombre_completo'])) ?></span>
                                        <div>
                                            <span class="cell-title">
                                                <?= e($usuario['nombre_completo']) ?>
                                                <?php if ($esCuentaActual): ?>
                                                    <span class="badge-soft bg-neutral-soft ms-1"><i class="bi bi-person-check"></i>Tu cuenta</span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="cell-sub">@<?= e($usuario['usuario']) ?><?= $usuario['correo'] ? ' · ' . e($usuario['correo']) : '' ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-soft bg-<?= e($claseRol) ?>-soft">
                                        <i class="bi <?= e(icono_rol($usuario['rol'])) ?>"></i><?= e(etiqueta_rol($usuario['rol'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($estaActivo): ?>
                                        <span class="badge-soft bg-success-soft"><i class="bi bi-check-circle"></i>Activo</span>
                                    <?php else: ?>
                                        <span class="badge-soft bg-neutral-soft"><i class="bi bi-slash-circle"></i>Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted-app small-70"><?= e($usuario['ultimo_acceso'] ? formatear_fecha_hora($usuario['ultimo_acceso']) : 'Sin acceso') ?></td>
                                <td class="text-muted-app small-70"><?= e(formatear_fecha($usuario['fecha_creacion'])) ?></td>
                                <td class="cell-actions">
                                    <a class="btn btn-soft btn-action" href="<?= e(base_url('usuarios/editar.php?id=' . (int) $usuario['id_usuario'])) ?>"
                                       data-bs-toggle="tooltip" title="Editar usuario"><i class="bi bi-pencil"></i></a>
                                    <a class="btn btn-soft btn-action" href="<?= e(base_url('usuarios/restablecer.php?id=' . (int) $usuario['id_usuario'])) ?>"
                                       data-bs-toggle="tooltip" title="Restablecer contraseña"><i class="bi bi-key"></i></a>

                                    <?php if ($esCuentaActual): ?>
                                        <span class="badge-soft bg-neutral-soft" title="No puedes desactivar tu propia cuenta">
                                            <i class="bi bi-shield-check"></i>Sesión actual
                                        </span>
                                    <?php elseif ($esUltimoGerente): ?>
                                        <span class="badge-soft bg-warning-soft" title="Debe existir al menos un gerente activo">
                                            <i class="bi bi-lock"></i>Último gerente
                                        </span>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(base_url('usuarios/desactivar.php')) ?>" class="d-inline"
                                              data-confirm="<?= $estaActivo
                                                  ? 'La cuenta de «' . e($usuario['nombre_completo']) . '» quedará desactivada y no podrá iniciar sesión. ¿Deseas continuar?'
                                                  : 'La cuenta de «' . e($usuario['nombre_completo']) . '» volverá a estar activa. ¿Deseas continuar?' ?>"
                                              data-confirm-title="<?= $estaActivo ? 'Desactivar usuario' : 'Reactivar usuario' ?>"
                                              data-confirm-button="<?= $estaActivo ? 'btn-danger' : 'btn-success' ?>"
                                              data-confirm-accept="<?= $estaActivo ? 'Sí, desactivar' : 'Sí, reactivar' ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $usuario['id_usuario'] ?>">
                                            <input type="hidden" name="accion" value="<?= $estaActivo ? 'desactivar' : 'reactivar' ?>">
                                            <button type="submit" class="btn <?= $estaActivo ? 'btn-danger-soft' : 'btn-success-soft' ?> btn-action"
                                                    data-bs-toggle="tooltip" title="<?= $estaActivo ? 'Desactivar' : 'Reactivar' ?>">
                                                <i class="bi <?= $estaActivo ? 'bi-person-dash' : 'bi-person-check' ?>"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="panel mt-4">
    <div class="panel-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <span class="badge-soft bg-gold-soft"><i class="bi bi-shield-check"></i>Gerente</span>
                    <span class="small text-muted-app">Acceso completo, reportes, usuarios y ajustes.</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <span class="badge-soft bg-warning-soft"><i class="bi bi-fire"></i>Cocinero</span>
                    <span class="small text-muted-app">Consulta almacén, registra salidas y mermas.</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <span class="badge-soft bg-info-soft"><i class="bi bi-shop"></i>Ventas</span>
                    <span class="small text-muted-app">Controla producto terminado y pizzas.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

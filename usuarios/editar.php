<?php
/**
 * Editar una cuenta de usuario (solo gerente).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

$pdo = db();
$usuarioActual = usuario_actual();
$idUsuario = (int) get('id', post('id', 0));

$stmt = $pdo->prepare(
    'SELECT u.*, r.nombre AS rol
     FROM usuarios u
     INNER JOIN roles r ON r.id_rol = u.id_rol
     WHERE u.id_usuario = :id'
);
$stmt->execute([':id' => $idUsuario]);
$usuarioEditar = $stmt->fetch();

if (!$usuarioEditar) {
    flash('danger', 'El usuario solicitado no existe.');
    redirigir('usuarios/index.php');
}

$roles = $pdo->query('SELECT * FROM roles ORDER BY id_rol')->fetchAll();
$esCuentaActual = (int) $usuarioEditar['id_usuario'] === (int) $usuarioActual['id_usuario'];

$errores = [];
$valores = [
    'nombre_completo' => $usuarioEditar['nombre_completo'],
    'usuario'         => $usuarioEditar['usuario'],
    'correo'          => $usuarioEditar['correo'] ?? '',
    'id_rol'          => (string) $usuarioEditar['id_rol'],
    'activo'          => (int) $usuarioEditar['activo'] === 1 ? '1' : '0',
];

if (es_post()) {
    verificar_csrf();

    foreach (['nombre_completo', 'usuario', 'correo', 'id_rol'] as $campo) {
        $valores[$campo] = trim((string) post($campo));
    }
    $valores['usuario'] = mb_strtolower($valores['usuario'], 'UTF-8');
    $valores['activo'] = $esCuentaActual ? '1' : (post('activo', '0') === '1' ? '1' : '0');

    $nombre = texto_requerido($valores['nombre_completo'], 3, 100);
    $correo = $valores['correo'] !== '' ? texto_requerido($valores['correo'], 5, 120) : '';
    $idRol = (int) $valores['id_rol'];

    $rolValido = false;
    foreach ($roles as $rol) {
        if ((int) $rol['id_rol'] === $idRol) {
            $rolValido = true;
            break;
        }
    }

    if ($nombre === null) {
        $errores[] = 'El nombre completo debe tener entre 3 y 100 caracteres.';
    }
    if (!preg_match('/^[a-z0-9._-]{3,40}$/', $valores['usuario'])) {
        $errores[] = 'El usuario debe tener entre 3 y 40 caracteres y solo puede incluir letras minúsculas, números, punto, guion o guion bajo.';
    }
    if ($correo === null) {
        $errores[] = 'El correo no es válido o supera los 120 caracteres.';
    } elseif ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }
    if (!$rolValido) {
        $errores[] = 'Selecciona un rol válido.';
    }

    if ($esCuentaActual) {
        if ($idRol !== (int) $usuarioEditar['id_rol']) {
            $errores[] = 'No puedes cambiar tu propio rol. Otro gerente debe realizar ese cambio.';
        }
        $idRol = (int) $usuarioEditar['id_rol'];
    }

    $eraGerenteActivo = $usuarioEditar['rol'] === 'gerente' && (int) $usuarioEditar['activo'] === 1;
    $seguiraGerenteActivo = $idRol === (int) $usuarioEditar['id_rol'] && $valores['activo'] === '1'
        && $usuarioEditar['rol'] === 'gerente';

    if ($eraGerenteActivo && !$seguiraGerenteActivo && !$esCuentaActual) {
        if (contar_gerentes_activos($pdo, $idUsuario) <= 0) {
            $errores[] = 'No puedes quitar el rol ni desactivar al último gerente activo del sistema.';
        }
    }

    if (!$errores) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario AND id_usuario <> :id');
        $stmt->execute([':usuario' => $valores['usuario'], ':id' => $idUsuario]);
        if ((int) $stmt->fetchColumn() > 0) {
            $errores[] = 'Ya existe otra cuenta con ese nombre de usuario.';
        }

        if ($correo !== '') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = :correo AND id_usuario <> :id');
            $stmt->execute([':correo' => $correo, ':id' => $idUsuario]);
            if ((int) $stmt->fetchColumn() > 0) {
                $errores[] = 'Ya existe otra cuenta con ese correo electrónico.';
            }
        }
    }

    if (!$errores) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE usuarios
                 SET nombre_completo = :nombre, usuario = :usuario, correo = :correo,
                     id_rol = :rol, activo = :activo
                 WHERE id_usuario = :id'
            );
            $stmt->execute([
                ':nombre'  => $nombre,
                ':usuario' => $valores['usuario'],
                ':correo'  => $correo ?: null,
                ':rol'     => $idRol,
                ':activo'  => (int) $valores['activo'],
                ':id'      => $idUsuario,
            ]);

            flash('success', 'La cuenta de «' . $nombre . '» se actualizó correctamente.');
            redirigir('usuarios/index.php');
        } catch (PDOException $e) {
            error_log('[La Ordaz] Edición de usuario: ' . $e->getMessage());
            $errores[] = $e->getCode() === '23000'
                ? 'El usuario o el correo ya pertenecen a otra cuenta.'
                : 'No fue posible actualizar la cuenta. Inténtalo nuevamente.';
        }
    }
}

$pageTitle = 'Editar usuario';
$pageSubtitle = 'Actualiza los datos, el rol y el estado de la cuenta.';
$pageIcon = 'bi-person-gear';
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('usuarios/index.php')) . '"><i class="bi bi-arrow-left me-1"></i>Volver a usuarios</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-person-gear"></i> Datos de la cuenta</h2>
                    <p class="panel-subtitle">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                </div>
            </div>
            <div class="panel-body">
                <?php if ($errores): ?>
                    <div class="alert alert-danger">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-octagon-fill mt-1"></i>
                            <div>
                                <strong>Revisa la información:</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    <?php foreach ($errores as $error): ?>
                                        <li><?= e($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($esCuentaActual): ?>
                    <div class="alert alert-info d-flex align-items-start gap-2 py-2 px-3 small">
                        <i class="bi bi-info-circle-fill mt-1"></i>
                        <span>Estás editando tu propia cuenta. No puedes cambiar tu rol ni desactivarla para evitar perder el acceso administrativo.</span>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('usuarios/editar.php?id=' . $idUsuario)) ?>" data-submit-once autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $idUsuario ?>">

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-person-vcard"></i> Identidad y acceso</div>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="nombre_completo" class="form-label">Nombre completo <span class="req">*</span></label>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" maxlength="100"
                                       value="<?= e($valores['nombre_completo']) ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label for="usuario" class="form-label">Nombre de usuario <span class="req">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input type="text" class="form-control" id="usuario" name="usuario" maxlength="40"
                                           value="<?= e($valores['usuario']) ?>" pattern="[a-z0-9._-]{3,40}"
                                           title="Letras minúsculas, números, punto, guion o guion bajo" required>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label for="correo" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control" id="correo" name="correo" maxlength="120"
                                       value="<?= e($valores['correo']) ?>">
                            </div>
                            <div class="col-md-5">
                                <label for="id_rol" class="form-label">Rol <span class="req">*</span></label>
                                <select class="form-select" id="id_rol" name="id_rol" <?= $esCuentaActual ? 'disabled' : '' ?> required>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?= (int) $rol['id_rol'] ?>" <?= (string) $valores['id_rol'] === (string) $rol['id_rol'] ? 'selected' : '' ?>>
                                            <?= e(etiqueta_rol($rol['nombre'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($esCuentaActual): ?>
                                    <input type="hidden" name="id_rol" value="<?= (int) $usuarioEditar['id_rol'] ?>">
                                    <div class="form-text">Tu propio rol no puede modificarse.</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1"
                                           <?= $valores['activo'] === '1' ? 'checked' : '' ?> <?= $esCuentaActual ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="activo">Cuenta activa</label>
                                </div>
                                <?php if ($esCuentaActual): ?>
                                    <input type="hidden" name="activo" value="1">
                                    <div class="form-text">Tu cuenta debe permanecer activa mientras tengas la sesión iniciada.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="<?= e(base_url('usuarios/restablecer.php?id=' . $idUsuario)) ?>" class="btn btn-outline-coffee me-auto">
                            <i class="bi bi-key me-1"></i>Restablecer contraseña
                        </a>
                        <a href="<?= e(base_url('usuarios/index.php')) ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-gold"><i class="bi bi-check2 me-1"></i>Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-info-circle"></i> Resumen de la cuenta</h2>
            </div>
            <div class="panel-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="avatar"><?= e(iniciales($usuarioEditar['nombre_completo'])) ?></span>
                    <div>
                        <div class="fw-700 text-coffee"><?= e($usuarioEditar['nombre_completo']) ?></div>
                        <div class="small text-muted-app">@<?= e($usuarioEditar['usuario']) ?></div>
                    </div>
                </div>
                <div class="text-muted-app small-70">Rol actual</div>
                <div class="mb-2">
                    <span class="badge-soft bg-gold-soft"><i class="bi <?= e(icono_rol($usuarioEditar['rol'])) ?>"></i><?= e(etiqueta_rol($usuarioEditar['rol'])) ?></span>
                </div>
                <div class="text-muted-app small-70">Último acceso</div>
                <div class="small mb-2"><?= e($usuarioEditar['ultimo_acceso'] ? formatear_fecha_hora($usuarioEditar['ultimo_acceso']) : 'Sin acceso registrado') ?></div>
                <div class="text-muted-app small-70">Fecha de alta</div>
                <div class="small"><?= e(formatear_fecha_hora($usuarioEditar['fecha_creacion'])) ?></div>
                <div class="divider"></div>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    Los cambios de rol y estado se aplican en la siguiente petición del usuario afectado.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

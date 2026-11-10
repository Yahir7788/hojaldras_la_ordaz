<?php
/**
 * Registrar un nuevo usuario (solo gerente).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

$pdo = db();
$roles = $pdo->query('SELECT * FROM roles ORDER BY id_rol')->fetchAll();

$errores = [];
$valores = [
    'nombre_completo' => '',
    'usuario'         => '',
    'correo'          => '',
    'id_rol'          => '2',
    'activo'          => '1',
];

if (es_post()) {
    verificar_csrf();

    foreach ($valores as $campo => $_) {
        $valores[$campo] = trim((string) post($campo));
    }
    $valores['activo'] = post('activo', '0') === '1' ? '1' : '0';
    $valores['usuario'] = mb_strtolower($valores['usuario'], 'UTF-8');

    $contrasena = (string) post('contrasena');
    $confirmacion = (string) post('contrasena_confirmacion');

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

    $errorContrasena = validar_contrasena($contrasena);
    if ($errorContrasena !== null) {
        $errores[] = $errorContrasena;
    } elseif ($contrasena !== $confirmacion) {
        $errores[] = 'La confirmación de contraseña no coincide.';
    }

    if (!$errores) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario');
        $stmt->execute([':usuario' => $valores['usuario']]);
        if ((int) $stmt->fetchColumn() > 0) {
            $errores[] = 'Ya existe una cuenta con ese nombre de usuario.';
        }

        if ($correo !== '') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = :correo');
            $stmt->execute([':correo' => $correo]);
            if ((int) $stmt->fetchColumn() > 0) {
                $errores[] = 'Ya existe una cuenta con ese correo electrónico.';
            }
        }
    }

    if (!$errores) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nombre_completo, usuario, correo, contrasena, id_rol, activo)
                 VALUES (:nombre, :usuario, :correo, :contrasena, :rol, :activo)'
            );
            $stmt->execute([
                ':nombre'     => $nombre,
                ':usuario'    => $valores['usuario'],
                ':correo'     => $correo ?: null,
                ':contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
                ':rol'        => $idRol,
                ':activo'     => (int) $valores['activo'],
            ]);

            flash('success', 'La cuenta de «' . $nombre . '» se creó correctamente.');
            redirigir('usuarios/index.php');
        } catch (PDOException $e) {
            error_log('[La Ordaz] Alta de usuario: ' . $e->getMessage());
            $errores[] = $e->getCode() === '23000'
                ? 'El usuario o el correo ya están registrados.'
                : 'No fue posible crear la cuenta. Inténtalo nuevamente.';
        }
    }
}

$pageTitle = 'Nuevo usuario';
$pageSubtitle = 'Crea una cuenta y asigna el rol que tendrá dentro del sistema.';
$pageIcon = 'bi-person-plus';
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('usuarios/index.php')) . '"><i class="bi bi-arrow-left me-1"></i>Volver a usuarios</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-person-plus"></i> Datos de la cuenta</h2>
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

                <form method="post" action="<?= e(base_url('usuarios/registrar.php')) ?>" data-submit-once autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-person-vcard"></i> Identidad y acceso</div>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="nombre_completo" class="form-label">Nombre completo <span class="req">*</span></label>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" maxlength="100"
                                       value="<?= e($valores['nombre_completo']) ?>" placeholder="Ej. Laura Ordaz" required>
                            </div>
                            <div class="col-md-5">
                                <label for="usuario" class="form-label">Nombre de usuario <span class="req">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input type="text" class="form-control" id="usuario" name="usuario" maxlength="40"
                                           value="<?= e($valores['usuario']) ?>" placeholder="laura.ordaz"
                                           pattern="[a-z0-9._-]{3,40}" title="Letras minúsculas, números, punto, guion o guion bajo" required>
                                </div>
                                <div class="form-text">Se usará para iniciar sesión.</div>
                            </div>
                            <div class="col-md-7">
                                <label for="correo" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control" id="correo" name="correo" maxlength="120"
                                       value="<?= e($valores['correo']) ?>" placeholder="nombre@laordaz.local">
                            </div>
                            <div class="col-md-5">
                                <label for="id_rol" class="form-label">Rol <span class="req">*</span></label>
                                <select class="form-select" id="id_rol" name="id_rol" required>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?= (int) $rol['id_rol'] ?>" <?= (string) $valores['id_rol'] === (string) $rol['id_rol'] ? 'selected' : '' ?>>
                                            <?= e(etiqueta_rol($rol['nombre'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-shield-lock"></i> Contraseña</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="contrasena" class="form-label">Contraseña <span class="req">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="contrasena" name="contrasena"
                                           minlength="8" maxlength="72" autocomplete="new-password" required>
                                    <button class="btn btn-outline-coffee" type="button" tabindex="-1"
                                            data-toggle-password="contrasena" aria-label="Mostrar u ocultar contraseña">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Mínimo 8 caracteres, con al menos una letra y un número.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="contrasena_confirmacion" class="form-label">Confirmar contraseña <span class="req">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="contrasena_confirmacion" name="contrasena_confirmacion"
                                           minlength="8" maxlength="72" autocomplete="new-password" required>
                                    <button class="btn btn-outline-coffee" type="button" tabindex="-1"
                                            data-toggle-password="contrasena_confirmacion" aria-label="Mostrar u ocultar contraseña">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1"
                                           <?= $valores['activo'] === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="activo">Cuenta activa desde su creación</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <span class="required-note me-auto"><span class="req">*</span> Campos obligatorios</span>
                        <a href="<?= e(base_url('usuarios/index.php')) ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-gold"><i class="bi bi-check2 me-1"></i>Crear usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-diagram-3"></i> Roles disponibles</h2>
            </div>
            <div class="panel-body">
                <div class="d-flex gap-2 mb-3">
                    <span class="badge-soft bg-gold-soft"><i class="bi bi-shield-check"></i>Gerente</span>
                    <span class="small text-muted-app">Acceso completo: almacén, reportes, usuarios y ajustes.</span>
                </div>
                <div class="d-flex gap-2 mb-3">
                    <span class="badge-soft bg-warning-soft"><i class="bi bi-fire"></i>Cocinero</span>
                    <span class="small text-muted-app">Consulta existencias, registra salidas y mermas, revisa alertas.</span>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge-soft bg-info-soft"><i class="bi bi-shop"></i>Ventas</span>
                    <span class="small text-muted-app">Controla producción, ventas y disponibilidad de producto terminado.</span>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-shield-lock"></i> Seguridad</h2>
            </div>
            <div class="panel-body small text-muted-app">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">Las contraseñas se guardan con <strong>password_hash()</strong>.</li>
                    <li class="mb-1">Nunca se muestra la contraseña almacenada.</li>
                    <li class="mb-1">El gerente puede restablecer contraseñas.</li>
                    <li class="mb-0">Debe existir al menos un gerente activo.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

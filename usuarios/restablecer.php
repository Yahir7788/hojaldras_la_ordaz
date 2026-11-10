<?php
/**
 * Restablecer la contraseña de un usuario (solo gerente).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

$pdo = db();
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

$errores = [];

if (es_post()) {
    verificar_csrf();

    $contrasena = (string) post('contrasena');
    $confirmacion = (string) post('contrasena_confirmacion');

    $errorContrasena = validar_contrasena($contrasena);
    if ($errorContrasena !== null) {
        $errores[] = $errorContrasena;
    } elseif ($contrasena !== $confirmacion) {
        $errores[] = 'La confirmación de contraseña no coincide.';
    }

    if (!$errores) {
        try {
            $stmt = $pdo->prepare('UPDATE usuarios SET contrasena = :contrasena WHERE id_usuario = :id');
            $stmt->execute([
                ':contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
                ':id'         => $idUsuario,
            ]);

            flash('success', 'La contraseña de «' . $usuarioEditar['nombre_completo'] . '» se restableció correctamente. Compártela de forma segura.');
            redirigir('usuarios/index.php');
        } catch (Throwable $e) {
            error_log('[La Ordaz] Restablecer contraseña: ' . $e->getMessage());
            $errores[] = 'No fue posible restablecer la contraseña. Inténtalo nuevamente.';
        }
    }
}

$pageTitle = 'Restablecer contraseña';
$pageSubtitle = 'Asigna una nueva contraseña temporal a la cuenta seleccionada.';
$pageIcon = 'bi-key';
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('usuarios/index.php')) . '"><i class="bi bi-arrow-left me-1"></i>Volver a usuarios</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-key"></i> Nueva contraseña</h2>
                    <p class="panel-subtitle">La contraseña anterior dejará de funcionar inmediatamente.</p>
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

                <form method="post" action="<?= e(base_url('usuarios/restablecer.php?id=' . $idUsuario)) ?>" data-submit-once autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $idUsuario ?>">

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-shield-lock"></i> Credenciales de la cuenta</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="contrasena" class="form-label">Nueva contraseña <span class="req">*</span></label>
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
                                <button type="button" class="btn btn-soft btn-sm" id="generarClave">
                                    <i class="bi bi-magic me-1"></i>Generar contraseña segura
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <span class="required-note me-auto"><span class="req">*</span> Campos obligatorios</span>
                        <a href="<?= e(base_url('usuarios/index.php')) ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-gold"><i class="bi bi-key me-1"></i>Restablecer contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="panel mb-3">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-person-circle"></i> Cuenta seleccionada</h2>
            </div>
            <div class="panel-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="avatar"><?= e(iniciales($usuarioEditar['nombre_completo'])) ?></span>
                    <div>
                        <div class="fw-700 text-coffee"><?= e($usuarioEditar['nombre_completo']) ?></div>
                        <div class="small text-muted-app">@<?= e($usuarioEditar['usuario']) ?></div>
                    </div>
                </div>
                <div class="text-muted-app small-70">Rol</div>
                <div class="mb-2">
                    <span class="badge-soft bg-gold-soft"><i class="bi <?= e(icono_rol($usuarioEditar['rol'])) ?>"></i><?= e(etiqueta_rol($usuarioEditar['rol'])) ?></span>
                </div>
                <div class="text-muted-app small-70">Estado</div>
                <div class="mb-2">
                    <?php if ((int) $usuarioEditar['activo'] === 1): ?>
                        <span class="badge-soft bg-success-soft"><i class="bi bi-check-circle"></i>Activo</span>
                    <?php else: ?>
                        <span class="badge-soft bg-neutral-soft"><i class="bi bi-slash-circle"></i>Inactivo</span>
                    <?php endif; ?>
                </div>
                <div class="divider"></div>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Comparte la nueva contraseña por un medio seguro. El usuario puede cambiarla después desde su cuenta.
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = '<script>
    const generar = document.getElementById("generarClave");
    const clave = document.getElementById("contrasena");
    const confirmar = document.getElementById("contrasena_confirmacion");

    function generarClave() {
        const letras = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
        const numeros = "23456789";
        const simbolos = "!@#$%&*";
        const todos = letras + numeros + simbolos;
        const largo = 12;
        const valores = new Uint32Array(largo);
        window.crypto.getRandomValues(valores);
        let resultado = "";
        for (let i = 0; i < largo; i++) {
            resultado += todos[valores[i] % todos.length];
        }
        // Garantizar al menos una letra y un número.
        resultado = letras[valores[0] % letras.length] + numeros[valores[1] % numeros.length] + resultado.slice(2);
        clave.value = resultado;
        confirmar.value = resultado;
        clave.type = "text";
        confirmar.type = "text";
    }
    if (generar) generar.addEventListener("click", generarClave);
</script>';
require __DIR__ . '/../includes/footer.php';

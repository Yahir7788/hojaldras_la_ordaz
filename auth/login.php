<?php
/**
 * Inicio de sesión.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';

if (usuario_actual() !== null) {
    redirigir('dashboard/index.php');
}

$error = '';
$usuarioIngresado = '';
$aviso = '';

if (isset($_GET['expirada'])) {
    $aviso = 'Tu sesión expiró por inactividad. Vuelve a iniciar sesión.';
} elseif (isset($_GET['inactivo'])) {
    $aviso = 'Tu cuenta fue desactivada. Contacta al gerente del negocio.';
} elseif (isset($_GET['salida'])) {
    $aviso = 'Sesión cerrada correctamente. ¡Hasta pronto!';
}

if (es_post()) {
    verificar_csrf();

    $usuarioIngresado = trim((string) post('usuario'));
    $contrasena = (string) post('contrasena');
    $trampa = trim((string) post('sitio_web'));

    // Control simple de intentos fallidos (5 intentos / 5 minutos).
    $intentos = $_SESSION['login_intentos'] ?? ['cantidad' => 0, 'ultimo' => 0];
    $bloqueado = $intentos['cantidad'] >= 5 && (time() - (int) $intentos['ultimo']) < 300;

    if ($trampa !== '') {
        $error = 'No fue posible iniciar sesión con los datos proporcionados.';
    } elseif ($bloqueado) {
        $restantes = 300 - (time() - (int) $intentos['ultimo']);
        $error = 'Demasiados intentos fallidos. Espera ' . max(1, (int) ceil($restantes / 60)) . ' minuto(s) antes de volver a intentar.';
    } elseif ($usuarioIngresado === '' || $contrasena === '') {
        $error = 'Ingresa tu usuario y contraseña.';
    } else {
        $stmt = db()->prepare(
            'SELECT u.*, r.nombre AS rol
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE u.usuario = :usuario
             LIMIT 1'
        );
        $stmt->execute([':usuario' => $usuarioIngresado]);
        $cuenta = $stmt->fetch();

        if (!$cuenta || !password_verify($contrasena, $cuenta['contrasena'])) {
            $intentos['cantidad'] = (int) ($intentos['cantidad'] ?? 0) + 1;
            $intentos['ultimo'] = time();
            $_SESSION['login_intentos'] = $intentos;
            $error = 'Usuario o contraseña incorrectos. Verifica tus datos.';
        } elseif ((int) $cuenta['activo'] !== 1) {
            $error = 'La cuenta está desactivada. Contacta al gerente del negocio.';
        } else {
            if (password_needs_rehash($cuenta['contrasena'], PASSWORD_DEFAULT)) {
                $nuevoHash = password_hash($contrasena, PASSWORD_DEFAULT);
                $actualizar = db()->prepare('UPDATE usuarios SET contrasena = :hash WHERE id_usuario = :id');
                $actualizar->execute([':hash' => $nuevoHash, ':id' => (int) $cuenta['id_usuario']]);
            }

            session_regenerate_id(true);
            unset($_SESSION['login_intentos'], $_SESSION['url_destino']);

            $_SESSION['usuario'] = [
                'id_usuario'      => (int) $cuenta['id_usuario'],
                'nombre_completo' => $cuenta['nombre_completo'],
                'usuario'         => $cuenta['usuario'],
                'correo'          => $cuenta['correo'],
                'id_rol'          => (int) $cuenta['id_rol'],
                'rol'             => $cuenta['rol'],
            ];
            $_SESSION['ultima_actividad'] = time();

            $marcar = db()->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id');
            $marcar->execute([':id' => (int) $cuenta['id_usuario']]);

            redirigir('dashboard/index.php');
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2e1a10">
    <title>Iniciar sesión · <?= e(APP_NOMBRE) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(base_url('assets/img/logo.svg')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/estilos.css')) ?>">
</head>
<body>
<div class="login-page">
    <section class="login-brand">
        <div class="login-brand-top">
            <img src="<?= e(base_url('assets/img/logo.svg')) ?>" alt="Logo Hojaldras La Ordaz">
            <div>
                <strong>HOJALDRAS</strong>
                <span>“LA ORDAZ”</span>
                <small>Almacén e inventarios</small>
            </div>
        </div>

        <div class="login-hero">
            <h1>El sabor de lo artesanal,<br><em>con control total.</em></h1>
            <p>
                Administra las materias primas de tus hojaldres, volovanes y pizzas.
                Conoce existencias reales, registra entradas y salidas, controla mermas
                y recibe alertas antes de quedarte sin ingredientes.
            </p>
            <div class="login-features">
                <span class="login-feature"><i class="bi bi-box-seam"></i> Inventario en tiempo real</span>
                <span class="login-feature"><i class="bi bi-bell"></i> Alertas automáticas</span>
                <span class="login-feature"><i class="bi bi-file-earmark-bar-graph"></i> Reportes</span>
                <span class="login-feature"><i class="bi bi-shield-check"></i> Acceso por roles</span>
            </div>
        </div>

        <div class="login-brand-footer">
            Sistema de Gestión de Almacén e Inventarios · Hojaldras “La Ordaz”
        </div>
    </section>

    <section class="login-panel">
        <div class="login-card">
            <div class="login-card-head">
                <img class="login-mobile-logo" src="<?= e(base_url('assets/img/logo.svg')) ?>" alt="Logo Hojaldras La Ordaz">
                <h2>Bienvenido de nuevo</h2>
                <p>Ingresa tus credenciales para acceder al sistema.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger d-flex align-items-start gap-2 py-2 px-3 small" role="alert">
                    <i class="bi bi-exclamation-octagon-fill mt-1"></i>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($aviso !== ''): ?>
                <div class="alert alert-success d-flex align-items-start gap-2 py-2 px-3 small" role="alert">
                    <i class="bi bi-check-circle-fill mt-1"></i>
                    <span><?= e($aviso) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url('auth/login.php')) ?>" class="login-form" autocomplete="on" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="usuario" name="usuario"
                               value="<?= e($usuarioIngresado) ?>" placeholder="Tu nombre de usuario"
                               autocomplete="username" maxlength="40" required autofocus>
                    </div>
                </div>

                <div class="mb-1">
                    <label for="contrasena" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="contrasena" name="contrasena"
                               placeholder="Tu contraseña" autocomplete="current-password" required>
                        <button class="btn btn-outline-coffee" type="button" tabindex="-1"
                                data-toggle-password="contrasena" aria-label="Mostrar u ocultar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="login-extra">
                    <span><i class="bi bi-shield-lock me-1"></i>Conexión segura</span>
                    <span class="small">¿Olvidaste tu contraseña? Contacta al gerente.</span>
                </div>

                <div class="d-none" aria-hidden="true">
                    <label for="sitio_web">No rellenar</label>
                    <input type="text" id="sitio_web" name="sitio_web" tabindex="-1" autocomplete="off">
                </div>

                <button type="submit" class="btn btn-coffee w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión
                </button>
            </form>

            <div class="login-demo">
                <div class="login-demo-title"><i class="bi bi-key"></i> Usuarios de prueba</div>
                <div class="login-demo-row">
                    <span>Gerente</span>
                    <span><code>gerente</code> · <code>Gerente123!</code>
                        <a href="#" class="ms-1" data-demo-user="gerente" data-demo-pass="Gerente123!" title="Usar estas credenciales"><i class="bi bi-box-arrow-in-right"></i></a>
                    </span>
                </div>
                <div class="login-demo-row">
                    <span>Cocinero</span>
                    <span><code>cocinero</code> · <code>Cocinero123!</code>
                        <a href="#" class="ms-1" data-demo-user="cocinero" data-demo-pass="Cocinero123!" title="Usar estas credenciales"><i class="bi bi-box-arrow-in-right"></i></a>
                    </span>
                </div>
                <div class="login-demo-row">
                    <span>Ventas</span>
                    <span><code>ventas</code> · <code>Ventas123!</code>
                        <a href="#" class="ms-1" data-demo-user="ventas" data-demo-pass="Ventas123!" title="Usar estas credenciales"><i class="bi bi-box-arrow-in-right"></i></a>
                    </span>
                </div>
            </div>

            <div class="login-footer">
                © <?= date('Y') ?> Hojaldras “La Ordaz” · Sistema de Gestión de Almacén e Inventarios
            </div>
        </div>
    </section>
</div>
<script src="<?= e(base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>

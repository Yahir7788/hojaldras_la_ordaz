<?php
/**
 * Protección de sesión y permisos por rol.
 * Todas las páginas privadas deben incluir este archivo.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/alertas.php';

// ---------------------------------------------------------------------------
// Expiración por inactividad (30 minutos)
// ---------------------------------------------------------------------------
if (usuario_actual() !== null && isset($_SESSION['ultima_actividad'])) {
    if (time() - (int) $_SESSION['ultima_actividad'] > 1800) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        header('Location: ' . base_url('auth/login.php?expirada=1'));
        exit;
    }
}
$_SESSION['ultima_actividad'] = time();

// ---------------------------------------------------------------------------
// Sesión obligatoria
// ---------------------------------------------------------------------------
if (usuario_actual() === null) {
    $_SESSION['url_destino'] = $_SERVER['REQUEST_URI'] ?? null;
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

// ---------------------------------------------------------------------------
// Refrescar usuario en cada petición: si fue desactivado o cambió de rol,
// el cambio surte efecto de inmediato.
// ---------------------------------------------------------------------------
$stmt = db()->prepare(
    'SELECT u.id_usuario, u.nombre_completo, u.usuario, u.correo, u.id_rol, u.activo,
            r.nombre AS rol
     FROM usuarios u
     INNER JOIN roles r ON r.id_rol = u.id_rol
     WHERE u.id_usuario = :id'
);
$stmt->execute([':id' => (int) $_SESSION['usuario']['id_usuario']]);
$usuarioDb = $stmt->fetch();

if (!$usuarioDb || (int) $usuarioDb['activo'] !== 1) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . base_url('auth/login.php?inactivo=1'));
    exit;
}

$_SESSION['usuario'] = [
    'id_usuario'     => (int) $usuarioDb['id_usuario'],
    'nombre_completo' => $usuarioDb['nombre_completo'],
    'usuario'        => $usuarioDb['usuario'],
    'correo'         => $usuarioDb['correo'],
    'id_rol'         => (int) $usuarioDb['id_rol'],
    'rol'            => $usuarioDb['rol'],
];

/**
 * Restringe el acceso a los roles indicados.
 */
function requerir_roles(array $roles): void
{
    if (!usuario_actual() || !in_array(usuario_actual()['rol'], $roles, true)) {
        flash('danger', 'No tienes permisos para acceder a ese módulo.');
        redirigir('dashboard/index.php');
    }
}

/**
 * Indica si el usuario actual puede consultar alertas de almacén.
 */
function puede_ver_alertas(): bool
{
    return tiene_rol('gerente', 'cocinero');
}

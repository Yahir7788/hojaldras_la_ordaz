<?php
/**
 * Activar o desactivar una cuenta de usuario (solo gerente).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

requerir_post();
verificar_csrf();

$pdo = db();
$usuarioActual = usuario_actual();
$idUsuario = (int) post('id', 0);
$accion = (string) post('accion', '');

if ($idUsuario <= 0 || !in_array($accion, ['desactivar', 'reactivar'], true)) {
    flash('danger', 'La solicitud no es válida.');
    redirigir('usuarios/index.php');
}

$stmt = $pdo->prepare(
    'SELECT u.*, r.nombre AS rol
     FROM usuarios u
     INNER JOIN roles r ON r.id_rol = u.id_rol
     WHERE u.id_usuario = :id'
);
$stmt->execute([':id' => $idUsuario]);
$usuario = $stmt->fetch();

if (!$usuario) {
    flash('danger', 'El usuario solicitado no existe.');
    redirigir('usuarios/index.php');
}

if ($idUsuario === (int) $usuarioActual['id_usuario']) {
    flash('danger', 'No puedes desactivar tu propia cuenta. Otro gerente debe hacerlo si es necesario.');
    redirigir('usuarios/index.php');
}

if ($accion === 'desactivar') {
    if ((int) $usuario['activo'] !== 1) {
        flash('info', 'La cuenta de «' . $usuario['nombre_completo'] . '» ya está inactiva.');
        redirigir('usuarios/index.php');
    }

    if ($usuario['rol'] === 'gerente' && contar_gerentes_activos($pdo, $idUsuario) <= 0) {
        flash('danger', 'No puedes desactivar al último gerente activo del sistema.');
        redirigir('usuarios/index.php');
    }

    $stmt = $pdo->prepare('UPDATE usuarios SET activo = 0 WHERE id_usuario = :id');
    $stmt->execute([':id' => $idUsuario]);
    flash('success', 'La cuenta de «' . $usuario['nombre_completo'] . '» quedó desactivada.');
} else {
    if ((int) $usuario['activo'] === 1) {
        flash('info', 'La cuenta de «' . $usuario['nombre_completo'] . '» ya está activa.');
        redirigir('usuarios/index.php');
    }

    $stmt = $pdo->prepare('UPDATE usuarios SET activo = 1 WHERE id_usuario = :id');
    $stmt->execute([':id' => $idUsuario]);
    flash('success', 'La cuenta de «' . $usuario['nombre_completo'] . '» se reactivó.');
}

redirigir('usuarios/index.php');

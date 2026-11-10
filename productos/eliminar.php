<?php
/**
 * Activar o desactivar un producto terminado (solo gerente).
 * La desactivación es lógica para conservar el historial de movimientos.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

requerir_post();
verificar_csrf();

$pdo = db();
$idProducto = (int) post('id', 0);
$accion = (string) post('accion', '');

if ($idProducto <= 0 || !in_array($accion, ['desactivar', 'reactivar'], true)) {
    flash('danger', 'La solicitud no es válida.');
    redirigir('productos/index.php');
}

$stmt = $pdo->prepare('SELECT * FROM productos WHERE id_producto = :id');
$stmt->execute([':id' => $idProducto]);
$producto = $stmt->fetch();

if (!$producto) {
    flash('danger', 'El producto solicitado no existe.');
    redirigir('productos/index.php');
}

if ($accion === 'desactivar') {
    if ((int) $producto['stock_actual'] > 0) {
        flash('danger', 'No puedes desactivar «' . $producto['nombre'] . '» porque aún tiene ' . (int) $producto['stock_actual'] . ' unidades disponibles. Registra una venta o un ajuste primero.');
        redirigir('productos/index.php');
    }

    $stmt = $pdo->prepare('UPDATE productos SET activo = 0 WHERE id_producto = :id');
    $stmt->execute([':id' => $idProducto]);
    flash('success', 'El producto «' . $producto['nombre'] . '» quedó inactivo. Su historial se conserva.');
} else {
    $stmt = $pdo->prepare('UPDATE productos SET activo = 1 WHERE id_producto = :id');
    $stmt->execute([':id' => $idProducto]);
    flash('success', 'El producto «' . $producto['nombre'] . '» se reactivó para venta.');
}

redirigir('productos/index.php');

<?php
/**
 * Eliminación lógica y reactivación de insumos.
 * Se conserva el historial; nunca se borran movimientos.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

requerir_post();
verificar_csrf();

$pdo = db();
$id = (int) post('id', 0);
$accion = (string) post('accion', 'eliminar');

$stmt = $pdo->prepare('SELECT * FROM insumos WHERE id_insumo = :id');
$stmt->execute([':id' => $id]);
$insumo = $stmt->fetch();

if (!$insumo) {
    flash('danger', 'El insumo solicitado no existe.');
    redirigir('insumos/index.php');
}

if ($accion === 'reactivar') {
    $stmt = $pdo->prepare('UPDATE insumos SET activo = 1 WHERE id_insumo = :id');
    $stmt->execute([':id' => $id]);
    flash('success', 'El insumo «' . $insumo['nombre'] . '» fue reactivado.');
    redirigir('insumos/index.php');
}

if ($accion !== 'eliminar') {
    flash('danger', 'Acción no válida.');
    redirigir('insumos/index.php');
}

if ((float) $insumo['cantidad_actual'] > 0) {
    flash('danger', 'No puedes eliminar «' . $insumo['nombre'] . '» porque todavía tiene ' . formatear_cantidad($insumo['cantidad_actual']) . ' ' . $insumo['unidad_medida'] . ' en existencia. Registra una merma o un ajuste primero.');
    redirigir('insumos/index.php');
}

$stmt = $pdo->prepare('UPDATE insumos SET activo = 0 WHERE id_insumo = :id');
$stmt->execute([':id' => $id]);

flash('success', 'El insumo «' . $insumo['nombre'] . '» fue eliminado del catálogo activo. Su historial se conserva.');
redirigir('insumos/index.php');

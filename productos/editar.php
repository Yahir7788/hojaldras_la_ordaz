<?php
/**
 * Editar los datos de un producto terminado (solo gerente).
 * La existencia se modifica únicamente mediante movimientos auditados.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

$pdo = db();
$idProducto = (int) get('id', post('id', 0));

$stmt = $pdo->prepare('SELECT * FROM productos WHERE id_producto = :id');
$stmt->execute([':id' => $idProducto]);
$producto = $stmt->fetch();

if (!$producto) {
    flash('danger', 'El producto solicitado no existe.');
    redirigir('productos/index.php');
}

$errores = [];
$valores = [
    'nombre'       => $producto['nombre'],
    'descripcion'  => $producto['descripcion'] ?? '',
    'precio'       => (string) $producto['precio'],
    'stock_minimo' => (string) $producto['stock_minimo'],
    'activo'       => (int) $producto['activo'] === 1 ? '1' : '0',
];

if (es_post()) {
    verificar_csrf();

    foreach (['nombre', 'descripcion', 'precio', 'stock_minimo'] as $campo) {
        $valores[$campo] = trim((string) post($campo));
    }
    $valores['activo'] = post('activo', '0') === '1' ? '1' : '0';

    $nombre = texto_requerido($valores['nombre'], 2, 80);
    $descripcion = $valores['descripcion'] !== '' ? texto_requerido($valores['descripcion'], 2, 255) : '';
    $precio = cantidad_no_negativa($valores['precio']);
    $stockMinimo = ctype_digit($valores['stock_minimo']) ? (int) $valores['stock_minimo'] : null;

    if ($nombre === null) {
        $errores[] = 'El nombre debe tener entre 2 y 80 caracteres.';
    }
    if ($descripcion === null) {
        $errores[] = 'La descripción no puede superar los 255 caracteres.';
    }
    if ($precio === null) {
        $errores[] = 'El precio debe ser un número mayor o igual a cero.';
    }
    if ($stockMinimo === null) {
        $errores[] = 'El stock mínimo debe ser un número entero mayor o igual a cero.';
    }
    if ($valores['activo'] === '0' && (int) $producto['stock_actual'] > 0) {
        $errores[] = 'No puedes desactivar un producto con existencia disponible. Registra primero una venta o un ajuste que deje el stock en cero.';
    }

    if (!$errores) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE productos
                 SET nombre = :nombre, descripcion = :descripcion, precio = :precio,
                     stock_minimo = :minimo, activo = :activo
                 WHERE id_producto = :id'
            );
            $stmt->execute([
                ':nombre'      => $nombre,
                ':descripcion' => $descripcion ?: null,
                ':precio'      => $precio,
                ':minimo'      => $stockMinimo,
                ':activo'      => (int) $valores['activo'],
                ':id'          => $idProducto,
            ]);

            flash('success', 'El producto «' . $nombre . '» se actualizó correctamente.');
            redirigir('productos/index.php');
        } catch (PDOException $e) {
            error_log('[La Ordaz] Edición de producto: ' . $e->getMessage());
            $errores[] = $e->getCode() === '23000'
                ? 'Ya existe otro producto con ese nombre.'
                : 'No fue posible actualizar el producto. Inténtalo nuevamente.';
        }
    }
}

$pageTitle = 'Editar producto';
$pageSubtitle = 'Actualiza precios, mínimos y datos del producto terminado.';
$pageIcon = 'bi-pencil-square';
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('productos/index.php')) . '"><i class="bi bi-arrow-left me-1"></i>Volver al control</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-pencil-square"></i> Datos del producto</h2>
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

                <form method="post" action="<?= e(base_url('productos/editar.php?id=' . $idProducto)) ?>" data-submit-once>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $idProducto ?>">

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-info-circle"></i> Información del producto</div>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="nombre" class="form-label">Nombre <span class="req">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" maxlength="80"
                                       value="<?= e($valores['nombre']) ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label for="precio" class="form-label">Precio de venta <span class="req">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="precio" name="precio"
                                           min="0" step="0.01" value="<?= e($valores['precio']) ?>" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" maxlength="255"><?= e($valores['descripcion']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-box-seam"></i> Existencias y disponibilidad</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Existencia actual</label>
                                <input type="text" class="form-control" value="<?= (int) $producto['stock_actual'] ?> unidades" disabled>
                                <div class="form-text">Se modifica con producción, venta o ajuste.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="stock_minimo" class="form-label">Stock mínimo <span class="req">*</span></label>
                                <input type="number" class="form-control" id="stock_minimo" name="stock_minimo"
                                       min="0" step="1" value="<?= e($valores['stock_minimo']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="activo"
                                           name="activo" value="1" <?= $valores['activo'] === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="activo">Producto activo para venta</label>
                                </div>
                                <div class="form-text">No se puede desactivar con existencia disponible.</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="<?= e(base_url('productos/movimiento.php?accion=ajuste&producto=' . $idProducto)) ?>" class="btn btn-outline-coffee me-auto">
                            <i class="bi bi-sliders me-1"></i>Ajustar existencia
                        </a>
                        <a href="<?= e(base_url('productos/index.php')) ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-gold"><i class="bi bi-check2 me-1"></i>Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-info-circle"></i> Resumen</h2>
            </div>
            <div class="panel-body">
                <div class="text-muted-app small-70">Producto</div>
                <div class="fw-700 text-coffee mb-2"><?= e($producto['nombre']) ?></div>
                <div class="text-muted-app small-70">Existencia actual</div>
                <div class="fs-2 fw-700 text-coffee"><?= (int) $producto['stock_actual'] ?> <span class="fs-6">unidades</span></div>
                <div class="text-muted-app small-70">Registrado</div>
                <div class="small"><?= e(formatear_fecha_hora($producto['fecha_registro'])) ?></div>
                <div class="divider"></div>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    La existencia no se edita directamente: usa producción, venta o ajuste para conservar el historial.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

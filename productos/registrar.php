<?php
/**
 * Registrar un producto terminado (solo gerente).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

$pdo = db();

$errores = [];
$valores = [
    'nombre'       => '',
    'descripcion'  => '',
    'precio'       => '',
    'stock_actual' => '0',
    'stock_minimo' => '',
];

if (es_post()) {
    verificar_csrf();

    foreach ($valores as $campo => $_) {
        $valores[$campo] = trim((string) post($campo));
    }

    $nombre = texto_requerido($valores['nombre'], 2, 80);
    $descripcion = $valores['descripcion'] !== '' ? texto_requerido($valores['descripcion'], 2, 255) : '';
    $precio = cantidad_no_negativa($valores['precio']);
    $stockInicial = ctype_digit($valores['stock_actual']) ? (int) $valores['stock_actual'] : null;
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
    if ($stockInicial === null) {
        $errores[] = 'La existencia inicial debe ser un número entero mayor o igual a cero.';
    }
    if ($stockMinimo === null) {
        $errores[] = 'El stock mínimo debe ser un número entero mayor o igual a cero.';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO productos (nombre, descripcion, precio, stock_actual, stock_minimo, activo)
                 VALUES (:nombre, :descripcion, :precio, :stock, :minimo, 1)'
            );
            $stmt->execute([
                ':nombre'      => $nombre,
                ':descripcion' => $descripcion ?: null,
                ':precio'      => $precio,
                ':stock'       => $stockInicial,
                ':minimo'      => $stockMinimo,
            ]);
            $idProducto = (int) $pdo->lastInsertId();

            if ($stockInicial > 0) {
                $stmtMov = $pdo->prepare(
                    'INSERT INTO movimientos_producto
                        (id_producto, tipo, cantidad, stock_anterior, stock_nuevo, id_usuario, observaciones)
                     VALUES (:producto, :tipo, :cantidad, 0, :nuevo, :usuario, :observaciones)'
                );
                $stmtMov->execute([
                    ':producto'      => $idProducto,
                    ':tipo'          => 'Ajuste',
                    ':cantidad'      => $stockInicial,
                    ':nuevo'         => $stockInicial,
                    ':usuario'       => (int) usuario_actual()['id_usuario'],
                    ':observaciones' => 'Existencia inicial registrada al crear el producto.',
                ]);
            }

            $pdo->commit();
            flash('success', 'El producto «' . $nombre . '» se registró correctamente.');
            redirigir('productos/index.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[La Ordaz] Alta de producto: ' . $e->getMessage());
            $errores[] = $e->getCode() === '23000'
                ? 'Ya existe un producto con ese nombre.'
                : 'No fue posible registrar el producto. Inténtalo nuevamente.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[La Ordaz] Alta de producto: ' . $e->getMessage());
            $errores[] = 'Ocurrió un error al guardar el producto.';
        }
    }
}

$pageTitle = 'Nuevo producto terminado';
$pageSubtitle = 'Agrega un producto de venta al catálogo del mostrador.';
$pageIcon = 'bi-basket2';
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('productos/index.php')) . '"><i class="bi bi-arrow-left me-1"></i>Volver al control</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel form-card">
    <div class="panel-header">
        <div>
            <h2 class="panel-title"><i class="bi bi-basket2"></i> Datos del producto</h2>
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

        <form method="post" action="<?= e(base_url('productos/registrar.php')) ?>" data-submit-once>
            <?= csrf_field() ?>

            <div class="form-section">
                <div class="form-section-title"><i class="bi bi-info-circle"></i> Información del producto</div>
                <div class="row g-3">
                    <div class="col-md-7">
                        <label for="nombre" class="form-label">Nombre <span class="req">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" maxlength="80"
                               value="<?= e($valores['nombre']) ?>" placeholder="Ej. Volován de jamón" required>
                    </div>
                    <div class="col-md-5">
                        <label for="precio" class="form-label">Precio de venta <span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="precio" name="precio"
                                   min="0" step="0.01" value="<?= e($valores['precio']) ?>" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" maxlength="255"
                                  placeholder="Presentación, sabor o características del producto…"><?= e($valores['descripcion']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title"><i class="bi bi-box-seam"></i> Existencias</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="stock_actual" class="form-label">Existencia inicial <span class="req">*</span></label>
                        <input type="number" class="form-control" id="stock_actual" name="stock_actual"
                               min="0" step="1" value="<?= e($valores['stock_actual']) ?>" required>
                        <div class="form-text">Unidades listas para venta al momento del alta. Puedes dejarla en 0.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="stock_minimo" class="form-label">Stock mínimo <span class="req">*</span></label>
                        <input type="number" class="form-control" id="stock_minimo" name="stock_minimo"
                               min="0" step="1" value="<?= e($valores['stock_minimo']) ?>" placeholder="Ej. 5" required>
                        <div class="form-text">Nivel que activa la alerta de producción.</div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <span class="required-note me-auto"><span class="req">*</span> Campos obligatorios</span>
                <a href="<?= e(base_url('productos/index.php')) ?>" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-gold"><i class="bi bi-check2 me-1"></i>Guardar producto</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

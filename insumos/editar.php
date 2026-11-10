<?php
/**
 * Editar un insumo (solo gerente). El stock no se modifica aquí:
 * para eso existe el ajuste de inventario con auditoría.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

$pdo = db();
$id = (int) get('id', post('id', 0));

$stmt = $pdo->prepare(
    'SELECT i.*, c.nombre AS categoria, c.perecedero
     FROM insumos i
     INNER JOIN categorias c ON c.id_categoria = i.id_categoria
     WHERE i.id_insumo = :id'
);
$stmt->execute([':id' => $id]);
$insumo = $stmt->fetch();

if (!$insumo) {
    flash('danger', 'El insumo solicitado no existe.');
    redirigir('insumos/index.php');
}

$categorias = obtener_categorias($pdo);
$unidades = unidades_medida();
$errores = [];

$valores = [
    'nombre'        => $insumo['nombre'],
    'descripcion'   => $insumo['descripcion'] ?? '',
    'id_categoria'  => (string) $insumo['id_categoria'],
    'unidad_medida' => $insumo['unidad_medida'],
    'stock_minimo'  => (string) $insumo['stock_minimo'],
];

if (es_post()) {
    verificar_csrf();

    foreach ($valores as $campo => $_) {
        $valores[$campo] = trim((string) post($campo));
    }

    $nombre = texto_requerido($valores['nombre'], 2, 100);
    $descripcion = $valores['descripcion'] !== '' ? texto_requerido($valores['descripcion'], 2, 255) : '';
    $idCategoria = (int) $valores['id_categoria'];
    $unidad = opcion_valida($valores['unidad_medida'], $unidades);
    $minimo = cantidad_no_negativa($valores['stock_minimo']);

    $categoria = null;
    foreach ($categorias as $cat) {
        if ((int) $cat['id_categoria'] === $idCategoria) {
            $categoria = $cat;
            break;
        }
    }

    if ($nombre === null) {
        $errores[] = 'El nombre debe tener entre 2 y 100 caracteres.';
    }
    if ($descripcion === null) {
        $errores[] = 'La descripción no puede superar los 255 caracteres.';
    }
    if ($categoria === null) {
        $errores[] = 'Selecciona una categoría válida.';
    }
    if ($unidad === null) {
        $errores[] = 'Selecciona una unidad de medida válida.';
    }
    if ($minimo === null) {
        $errores[] = 'El stock mínimo debe ser un número mayor o igual a cero.';
    }

    if (!$errores) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE insumos
                 SET nombre = :nombre, descripcion = :descripcion, id_categoria = :categoria,
                     unidad_medida = :unidad, stock_minimo = :minimo
                 WHERE id_insumo = :id'
            );
            $stmt->execute([
                ':nombre'      => $nombre,
                ':descripcion' => $descripcion ?: null,
                ':categoria'   => $idCategoria,
                ':unidad'      => $unidad,
                ':minimo'      => $minimo,
                ':id'          => $id,
            ]);

            flash('success', 'El insumo «' . $nombre . '» se actualizó correctamente.');
            redirigir('insumos/index.php');
        } catch (PDOException $e) {
            error_log('[La Ordaz] Edición de insumo: ' . $e->getMessage());
            if ($e->getCode() === '23000') {
                $errores[] = 'Ya existe otro insumo con ese nombre.';
            } else {
                $errores[] = 'No fue posible actualizar el insumo.';
            }
        }
    }
}

$pageTitle = 'Editar insumo';
$pageSubtitle = 'Actualiza los datos del catálogo. El stock se controla mediante movimientos.';
$pageIcon = 'bi-pencil-square';
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('insumos/index.php')) . '"><i class="bi bi-arrow-left me-1"></i>Volver al catálogo</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-pencil-square"></i> Datos del insumo</h2>
                    <p class="panel-subtitle">Los cambios se reflejan de inmediato en el catálogo y las alertas.</p>
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

                <form method="post" action="<?= e(base_url('insumos/editar.php?id=' . $id)) ?>" data-submit-once>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-info-circle"></i> Información general</div>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="nombre" class="form-label">Nombre del insumo <span class="req">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100"
                                       value="<?= e($valores['nombre']) ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label for="id_categoria" class="form-label">Categoría <span class="req">*</span></label>
                                <select class="form-select" id="id_categoria" name="id_categoria" required>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= (int) $categoria['id_categoria'] ?>"
                                                data-perecedero="<?= (int) $categoria['perecedero'] ?>"
                                                <?= (string) $valores['id_categoria'] === (string) $categoria['id_categoria'] ? 'selected' : '' ?>>
                                            <?= e($categoria['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" maxlength="255"><?= e($valores['descripcion']) ?></textarea>
                            </div>
                            <div class="col-md-5">
                                <label for="unidad_medida" class="form-label">Unidad de medida <span class="req">*</span></label>
                                <select class="form-select" id="unidad_medida" name="unidad_medida" required>
                                    <?php foreach ($unidades as $clave => $etiqueta): ?>
                                        <option value="<?= e($clave) ?>" <?= $valores['unidad_medida'] === $clave ? 'selected' : '' ?>>
                                            <?= e($etiqueta) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($insumo['unidad_medida'] !== $valores['unidad_medida']): ?>
                                    <div class="form-text text-danger">Cambiar la unidad no convierte las cantidades existentes.</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="stock_minimo" class="form-label">Stock mínimo <span class="req">*</span></label>
                                <input type="number" class="form-control" id="stock_minimo" name="stock_minimo"
                                       min="0" step="0.001" value="<?= e($valores['stock_minimo']) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="<?= e(base_url('insumos/index.php')) ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-gold"><i class="bi bi-check2 me-1"></i>Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-speedometer2"></i> Estado actual</h2>
            </div>
            <div class="panel-body">
                <?php [$clase, $texto, $icono] = estado_stock($insumo['cantidad_actual'], $insumo['stock_minimo']); ?>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="small-70 text-muted-app">Existencia actual</div>
                        <div class="fs-3 fw-700 text-coffee"><?= e(formatear_cantidad($insumo['cantidad_actual'])) ?> <small class="fs-6"><?= e($insumo['unidad_medida']) ?></small></div>
                    </div>
                    <span class="badge-soft bg-<?= e($clase) ?>-soft"><i class="bi <?= e($icono) ?>"></i><?= e($texto) ?></span>
                </div>
                <div class="divider"></div>
                <div class="d-grid gap-2">
                    <a href="<?= e(base_url('insumos/ajustar.php?id=' . $id)) ?>" class="btn btn-outline-coffee">
                        <i class="bi bi-sliders me-1"></i>Ajustar inventario
                    </a>
                    <a href="<?= e(base_url('entradas/registrar.php?insumo=' . $id)) ?>" class="btn btn-soft">
                        <i class="bi bi-box-arrow-in-down me-1"></i>Registrar entrada
                    </a>
                </div>
                <div class="alert alert-warning small mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    El stock no se edita directamente. Cada cambio queda registrado en el historial de movimientos.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

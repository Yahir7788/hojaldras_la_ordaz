<?php
/**
 * Registrar un nuevo insumo (solo gerente).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

$pdo = db();
$categorias = obtener_categorias($pdo);
$unidades = unidades_medida();

$errores = [];
$valores = [
    'nombre'          => '',
    'descripcion'     => '',
    'id_categoria'    => '',
    'unidad_medida'   => 'kg',
    'stock_minimo'    => '',
    'cantidad_inicial' => '0',
    'fecha_caducidad' => '',
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
    $inicial = cantidad_no_negativa($valores['cantidad_inicial'] === '' ? '0' : $valores['cantidad_inicial']);

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
    if ($inicial === null) {
        $errores[] = 'La cantidad inicial debe ser un número mayor o igual a cero.';
    }

    $caducidad = null;
    if ($valores['fecha_caducidad'] !== '') {
        if (!validar_fecha($valores['fecha_caducidad'])) {
            $errores[] = 'La fecha de caducidad no es válida.';
        } else {
            $caducidad = $valores['fecha_caducidad'];
        }
    }
    if ($categoria !== null && (int) $categoria['perecedero'] === 1 && $inicial !== null && $inicial > 0 && $caducidad === null) {
        $errores[] = 'Los insumos perecederos con existencia inicial requieren fecha de caducidad.';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO insumos (nombre, descripcion, id_categoria, unidad_medida, cantidad_actual, stock_minimo)
                 VALUES (:nombre, :descripcion, :categoria, :unidad, :cantidad, :minimo)'
            );
            $stmt->execute([
                ':nombre'      => $nombre,
                ':descripcion' => $descripcion ?: null,
                ':categoria'   => $idCategoria,
                ':unidad'      => $unidad,
                ':cantidad'    => $inicial,
                ':minimo'      => $minimo,
            ]);
            $idInsumo = (int) $pdo->lastInsertId();

            if ($inicial > 0) {
                $numeroLote = 'INICIAL-' . date('Ymd-His') . '-' . $idInsumo;
                $stmtLote = $pdo->prepare(
                    'INSERT INTO lotes (id_insumo, id_entrada, numero_lote, cantidad_inicial, cantidad_actual, fecha_caducidad)
                     VALUES (:insumo, NULL, :lote, :cantidad, :cantidad_actual, :caducidad)'
                );
                $stmtLote->execute([
                    ':insumo'         => $idInsumo,
                    ':lote'           => $numeroLote,
                    ':cantidad'       => $inicial,
                    ':cantidad_actual' => $inicial,
                    ':caducidad'      => $caducidad,
                ]);

                registrar_movimiento(
                    $pdo,
                    $idInsumo,
                    'Ajuste',
                    $inicial,
                    0,
                    $inicial,
                    null,
                    'ajustes',
                    (int) usuario_actual()['id_usuario'],
                    'Stock inicial registrado al crear el insumo.'
                );
            }

            $pdo->commit();
            flash('success', 'El insumo «' . $nombre . '» se registró correctamente.');
            redirigir('insumos/index.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[La Ordaz] Alta de insumo: ' . $e->getMessage());
            if ($e->getCode() === '23000') {
                $errores[] = 'Ya existe un insumo con ese nombre.';
            } else {
                $errores[] = 'No fue posible registrar el insumo. Inténtalo nuevamente.';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[La Ordaz] Alta de insumo: ' . $e->getMessage());
            $errores[] = 'Ocurrió un error al guardar el insumo.';
        }
    }
}

$pageTitle = 'Nuevo insumo';
$pageSubtitle = 'Registra una materia prima o material de empaque en el catálogo.';
$pageIcon = 'bi-plus-square';
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('insumos/index.php')) . '"><i class="bi bi-arrow-left me-1"></i>Volver al catálogo</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel form-card">
    <div class="panel-header">
        <div>
            <h2 class="panel-title"><i class="bi bi-box-seam"></i> Datos del insumo</h2>
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

        <form method="post" action="<?= e(base_url('insumos/registrar.php')) ?>" data-submit-once>
            <?= csrf_field() ?>

            <div class="form-section">
                <div class="form-section-title"><i class="bi bi-info-circle"></i> Información general</div>
                <div class="row g-3">
                    <div class="col-md-7">
                        <label for="nombre" class="form-label">Nombre del insumo <span class="req">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100"
                               value="<?= e($valores['nombre']) ?>" placeholder="Ej. Harina de trigo" required>
                    </div>
                    <div class="col-md-5">
                        <label for="id_categoria" class="form-label">Categoría <span class="req">*</span></label>
                        <select class="form-select" id="id_categoria" name="id_categoria" required>
                            <option value="">Selecciona una categoría</option>
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
                        <textarea class="form-control" id="descripcion" name="descripcion" maxlength="255"
                                  placeholder="Uso, presentación o características del insumo…"><?= e($valores['descripcion']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label for="unidad_medida" class="form-label">Unidad de medida <span class="req">*</span></label>
                        <select class="form-select" id="unidad_medida" name="unidad_medida" required>
                            <?php foreach ($unidades as $clave => $etiqueta): ?>
                                <option value="<?= e($clave) ?>" <?= $valores['unidad_medida'] === $clave ? 'selected' : '' ?>>
                                    <?= e($etiqueta) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="stock_minimo" class="form-label">Stock mínimo <span class="req">*</span></label>
                        <input type="number" class="form-control" id="stock_minimo" name="stock_minimo"
                               min="0" step="0.001" value="<?= e($valores['stock_minimo']) ?>"
                               placeholder="Ej. 10" required>
                        <div class="form-text">Nivel que activa la alerta de reabastecimiento.</div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title"><i class="bi bi-box-arrow-in-down"></i> Existencia inicial</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="cantidad_inicial" class="form-label">Cantidad disponible</label>
                        <input type="number" class="form-control" id="cantidad_inicial" name="cantidad_inicial"
                               min="0" step="0.001" value="<?= e($valores['cantidad_inicial']) ?>" placeholder="0">
                        <div class="form-text">Puedes dejarla en 0 y registrar una entrada después.</div>
                    </div>
                    <div class="col-md-4" id="grupoCaducidad" hidden>
                        <label for="fecha_caducidad" class="form-label">Caducidad del stock inicial</label>
                        <input type="date" class="form-control" id="fecha_caducidad" name="fecha_caducidad"
                               value="<?= e($valores['fecha_caducidad']) ?>">
                        <div class="form-text">Obligatoria para insumos perecederos con existencia.</div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <span class="required-note me-auto"><span class="req">*</span> Campos obligatorios</span>
                <a href="<?= e(base_url('insumos/index.php')) ?>" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-gold"><i class="bi bi-check2 me-1"></i>Guardar insumo</button>
            </div>
        </form>
    </div>
</div>

<?php
$pageScripts = '<script>
    const categoria = document.getElementById("id_categoria");
    const grupoCaducidad = document.getElementById("grupoCaducidad");
    const fechaCaducidad = document.getElementById("fecha_caducidad");
    function actualizarCaducidad() {
        const opcion = categoria.options[categoria.selectedIndex];
        const perecedero = opcion && opcion.dataset.perecedero === "1";
        grupoCaducidad.hidden = !perecedero;
        fechaCaducidad.required = perecedero;
    }
    if (categoria) {
        categoria.addEventListener("change", actualizarCaducidad);
        actualizarCaducidad();
    }
</script>';
require __DIR__ . '/../includes/footer.php';

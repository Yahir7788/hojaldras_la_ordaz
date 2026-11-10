<?php
/**
 * Ajuste de inventario (solo gerente). Todo ajuste queda auditado.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

$pdo = db();
$insumos = obtener_insumos_activos($pdo);
$idSeleccionado = (int) get('id', post('id_insumo', 0));

$errores = [];
$valores = [
    'id_insumo'       => $idSeleccionado > 0 ? (string) $idSeleccionado : '',
    'tipo'            => 'sumar',
    'cantidad'        => '',
    'fecha_caducidad' => '',
    'motivo'          => '',
];

if (es_post()) {
    verificar_csrf();

    foreach ($valores as $campo => $_) {
        $valores[$campo] = trim((string) post($campo));
    }

    $idInsumo = (int) $valores['id_insumo'];
    $tipo = in_array($valores['tipo'], ['fijar', 'sumar', 'restar'], true) ? $valores['tipo'] : null;
    $cantidad = cantidad_no_negativa($valores['cantidad']);
    $motivo = texto_requerido($valores['motivo'], 5, 500);

    if ($idInsumo <= 0) {
        $errores[] = 'Selecciona un insumo.';
    }
    if ($tipo === null) {
        $errores[] = 'Selecciona un tipo de ajuste válido.';
    }
    if ($cantidad === null || ($tipo !== 'fijar' && $cantidad <= 0)) {
        $errores[] = 'La cantidad debe ser un número mayor que cero.';
    }
    if ($motivo === null) {
        $errores[] = 'Describe el motivo del ajuste (mínimo 5 caracteres).';
    }
    $caducidad = null;
    if ($valores['fecha_caducidad'] !== '') {
        if (!validar_fecha($valores['fecha_caducidad'])) {
            $errores[] = 'La fecha de caducidad no es válida.';
        } else {
            $caducidad = $valores['fecha_caducidad'];
        }
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();
            $insumo = bloquear_insumo($pdo, $idInsumo);

            if (!$insumo || (int) $insumo['activo'] !== 1) {
                throw new RuntimeException('El insumo seleccionado no está disponible.');
            }

            $actual = (float) $insumo['cantidad_actual'];
            $nueva = match ($tipo) {
                'fijar'  => $cantidad,
                'sumar'  => $actual + $cantidad,
                'restar' => $actual - $cantidad,
            };
            $nueva = round($nueva, 3);

            if ($nueva < 0) {
                throw new RuntimeException('El ajuste dejaría el inventario en negativo. La existencia actual es ' . formatear_cantidad($actual) . ' ' . $insumo['unidad_medida'] . '.');
            }
            if (abs($nueva - $actual) < 0.0005) {
                throw new RuntimeException('El ajuste no modifica la existencia actual.');
            }

            $diferencia = round(abs($nueva - $actual), 3);

            if ($nueva > $actual) {
                if ((int) $insumo['perecedero'] === 1 && $caducidad === null) {
                    throw new RuntimeException('Los insumos perecederos requieren fecha de caducidad para el lote del ajuste.');
                }
                crear_lote_ajuste($pdo, $idInsumo, $diferencia, (int) usuario_actual()['id_usuario'], $caducidad);
            } else {
                consumir_lotes($pdo, $idInsumo, $diferencia);
            }

            $stmt = $pdo->prepare('UPDATE insumos SET cantidad_actual = :cantidad WHERE id_insumo = :id');
            $stmt->execute([':cantidad' => $nueva, ':id' => $idInsumo]);

            registrar_movimiento(
                $pdo,
                $idInsumo,
                'Ajuste',
                $diferencia,
                $actual,
                $nueva,
                null,
                'ajustes',
                (int) usuario_actual()['id_usuario'],
                $motivo
            );

            $pdo->commit();
            flash('success', 'Ajuste registrado: ' . $insumo['nombre'] . ' pasó de ' . formatear_cantidad($actual) . ' a ' . formatear_cantidad($nueva) . ' ' . $insumo['unidad_medida'] . '.');
            redirigir('insumos/index.php');
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[La Ordaz] Ajuste de inventario: ' . $e->getMessage());
            $errores[] = 'No fue posible registrar el ajuste. Inténtalo nuevamente.';
        }
    }
}

$pageTitle = 'Ajuste de inventario';
$pageSubtitle = 'Corrige existencias con trazabilidad completa. Solo el gerente puede realizarlo.';
$pageIcon = 'bi-sliders';
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('insumos/index.php')) . '"><i class="bi bi-arrow-left me-1"></i>Volver al catálogo</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-sliders"></i> Nuevo ajuste</h2>
                    <p class="panel-subtitle">Fija una existencia, suma o resta unidades. Cada ajuste genera un movimiento auditado.</p>
                </div>
            </div>
            <div class="panel-body">
                <?php if ($errores): ?>
                    <div class="alert alert-danger">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-octagon-fill mt-1"></i>
                            <div>
                                <strong>No se pudo guardar el ajuste:</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    <?php foreach ($errores as $error): ?>
                                        <li><?= e($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('insumos/ajustar.php')) ?>" data-submit-once>
                    <?= csrf_field() ?>

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-box-seam"></i> Insumo y operación</div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="id_insumo" class="form-label">Insumo <span class="req">*</span></label>
                                <select class="form-select" id="id_insumo" name="id_insumo" required>
                                    <option value="">Selecciona un insumo</option>
                                    <?php foreach ($insumos as $insumo): ?>
                                        <option value="<?= (int) $insumo['id_insumo'] ?>"
                                                data-stock="<?= e((string) $insumo['cantidad_actual']) ?>"
                                                data-unidad="<?= e($insumo['unidad_medida']) ?>"
                                                data-minimo="<?= e((string) $insumo['stock_minimo']) ?>"
                                                <?= $valores['id_insumo'] === (string) $insumo['id_insumo'] ? 'selected' : '' ?>>
                                            <?= e($insumo['nombre']) ?> · <?= e(formatear_cantidad($insumo['cantidad_actual'])) ?> <?= e($insumo['unidad_medida']) ?> en existencia
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="tipo" class="form-label">Tipo de ajuste <span class="req">*</span></label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="sumar" <?= $valores['tipo'] === 'sumar' ? 'selected' : '' ?>>Sumar existencia</option>
                                    <option value="restar" <?= $valores['tipo'] === 'restar' ? 'selected' : '' ?>>Restar existencia</option>
                                    <option value="fijar" <?= $valores['tipo'] === 'fijar' ? 'selected' : '' ?>>Fijar nueva existencia</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="cantidad" class="form-label">Cantidad <span class="req">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cantidad" name="cantidad"
                                           min="0" step="0.001" value="<?= e($valores['cantidad']) ?>" required>
                                    <span class="input-group-text" id="unidadAjuste">—</span>
                                </div>
                                <div class="form-text" id="ayudaCantidad">Cantidad que se sumará al stock actual.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha_caducidad" class="form-label">Caducidad del lote</label>
                                <input type="date" class="form-control" id="fecha_caducidad" name="fecha_caducidad"
                                       value="<?= e($valores['fecha_caducidad']) ?>">
                                <div class="form-text">Solo aplica si el ajuste aumenta la existencia.</div>
                            </div>
                            <div class="col-12">
                                <label for="motivo" class="form-label">Motivo del ajuste <span class="req">*</span></label>
                                <textarea class="form-control" id="motivo" name="motivo" maxlength="500" required
                                          placeholder="Ej. Corrección por conteo físico, diferencia detectada en inventario, stock inicial…"><?= e($valores['motivo']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="<?= e(base_url('insumos/index.php')) ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-gold"><i class="bi bi-check2 me-1"></i>Registrar ajuste</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-info-circle"></i> Existencia seleccionada</h2>
            </div>
            <div class="panel-body">
                <div class="text-muted-app small-70">Stock actual</div>
                <div class="fs-2 fw-700 text-coffee" id="stockActual">—</div>
                <div class="small text-muted-app" id="minimoActual">Selecciona un insumo para ver el detalle.</div>
                <div class="divider"></div>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    Los ajustes quedan asociados a tu usuario y al motivo indicado. No pueden eliminarse del historial.
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-lightbulb"></i> ¿Cuándo usar un ajuste?</h2>
            </div>
            <div class="panel-body small text-muted-app">
                <p class="mb-2"><strong class="text-coffee">Conteo físico:</strong> cuando la existencia real difiere de la del sistema.</p>
                <p class="mb-2"><strong class="text-coffee">Corrección de captura:</strong> para reparar un error administrativo.</p>
                <p class="mb-0"><strong class="text-coffee">Stock inicial:</strong> al comenzar a usar el sistema con producto ya disponible.</p>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = '<script>
    const selectInsumo = document.getElementById("id_insumo");
    const tipoAjuste = document.getElementById("tipo");
    const unidadAjuste = document.getElementById("unidadAjuste");
    const ayudaCantidad = document.getElementById("ayudaCantidad");
    const stockActual = document.getElementById("stockActual");
    const minimoActual = document.getElementById("minimoActual");

    function actualizarResumen() {
        const opcion = selectInsumo.options[selectInsumo.selectedIndex];
        if (!opcion || !opcion.value) {
            unidadAjuste.textContent = "—";
            stockActual.textContent = "—";
            minimoActual.textContent = "Selecciona un insumo para ver el detalle.";
            return;
        }
        const stock = parseFloat(opcion.dataset.stock || "0");
        const unidad = opcion.dataset.unidad || "";
        const minimo = parseFloat(opcion.dataset.minimo || "0");
        unidadAjuste.textContent = unidad;
        stockActual.textContent = stock.toLocaleString("es-MX", { maximumFractionDigits: 3 }) + " " + unidad;
        minimoActual.textContent = "Stock mínimo: " + minimo.toLocaleString("es-MX", { maximumFractionDigits: 3 }) + " " + unidad;
        ayudaCantidad.textContent = tipoAjuste.value === "fijar"
            ? "La existencia quedará exactamente en este valor."
            : (tipoAjuste.value === "sumar" ? "Cantidad que se sumará al stock actual." : "Cantidad que se restará del stock actual.");
    }
    selectInsumo.addEventListener("change", actualizarResumen);
    tipoAjuste.addEventListener("change", actualizarResumen);
    actualizarResumen();
</script>';
require __DIR__ . '/../includes/footer.php';

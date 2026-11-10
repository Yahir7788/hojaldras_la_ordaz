<?php
/**
 * Registrar una merma (gerente y cocinero).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente', 'cocinero']);

$pdo = db();
$insumos = $pdo->query(
    'SELECT i.id_insumo, i.nombre, i.unidad_medida, i.cantidad_actual, i.stock_minimo,
            c.nombre AS categoria
     FROM insumos i
     INNER JOIN categorias c ON c.id_categoria = i.id_categoria
     WHERE i.activo = 1 AND i.cantidad_actual > 0
     ORDER BY i.nombre'
)->fetchAll();
$motivos = motivos_merma();
$caducados = lotes_caducados($pdo);

$errores = [];
$valores = [
    'id_insumo'   => (string) get('insumo', ''),
    'cantidad'    => '',
    'motivo'      => '',
    'fecha'       => fecha_hoy(),
    'descripcion' => '',
];

if (es_post()) {
    verificar_csrf();

    foreach ($valores as $campo => $_) {
        $valores[$campo] = trim((string) post($campo));
    }

    $idInsumo = (int) $valores['id_insumo'];
    $cantidad = cantidad_positiva($valores['cantidad']);
    $motivo = opcion_valida($valores['motivo'], $motivos);
    $fecha = $valores['fecha'];
    $descripcion = texto_requerido($valores['descripcion'], 5, 500);

    if ($idInsumo <= 0) {
        $errores[] = 'Selecciona un insumo.';
    }
    if ($cantidad === null) {
        $errores[] = 'La cantidad debe ser mayor que cero.';
    }
    if ($motivo === null) {
        $errores[] = 'Selecciona un motivo válido.';
    }
    if (!validar_fecha($fecha)) {
        $errores[] = 'La fecha no es válida.';
    } elseif ($fecha > fecha_hoy()) {
        $errores[] = 'La fecha de la merma no puede ser futura.';
    }
    if ($descripcion === null) {
        $errores[] = 'Describe la merma con al menos 5 caracteres.';
    }

    if (!$errores && $cantidad !== null && $motivo !== null) {
        try {
            $pdo->beginTransaction();
            $insumo = bloquear_insumo($pdo, $idInsumo);

            if (!$insumo || (int) $insumo['activo'] !== 1) {
                throw new RuntimeException('El insumo seleccionado no está disponible.');
            }

            $stockAnterior = (float) $insumo['cantidad_actual'];
            if ($cantidad > $stockAnterior + 0.0004) {
                throw new RuntimeException('La merma no puede superar las existencias. Disponible: ' . formatear_cantidad($stockAnterior) . ' ' . $insumo['unidad_medida'] . '.');
            }

            $stockNuevo = round($stockAnterior - $cantidad, 3);

            consumir_lotes($pdo, $idInsumo, $cantidad);

            $stmtStock = $pdo->prepare('UPDATE insumos SET cantidad_actual = :cantidad WHERE id_insumo = :id');
            $stmtStock->execute([':cantidad' => $stockNuevo, ':id' => $idInsumo]);

            $stmtMerma = $pdo->prepare(
                'INSERT INTO mermas (id_insumo, cantidad, motivo, fecha, descripcion, id_usuario)
                 VALUES (:insumo, :cantidad, :motivo, :fecha, :descripcion, :usuario)'
            );
            $stmtMerma->execute([
                ':insumo'      => $idInsumo,
                ':cantidad'    => $cantidad,
                ':motivo'      => $motivo,
                ':fecha'       => $fecha,
                ':descripcion' => $descripcion,
                ':usuario'     => (int) usuario_actual()['id_usuario'],
            ]);
            $idMerma = (int) $pdo->lastInsertId();

            registrar_movimiento(
                $pdo,
                $idInsumo,
                'Merma',
                $cantidad,
                $stockAnterior,
                $stockNuevo,
                $idMerma,
                'mermas',
                (int) usuario_actual()['id_usuario'],
                'Merma por ' . mb_strtolower($motivo, 'UTF-8') . ' · ' . $descripcion
            );

            $pdo->commit();
            flash('success', 'Merma registrada: ' . formatear_cantidad($cantidad) . ' ' . $insumo['unidad_medida'] . ' de ' . $insumo['nombre'] . ' (' . $motivo . '). El inventario se redujo a ' . formatear_cantidad($stockNuevo) . '.');
            redirigir('mermas/index.php');
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[La Ordaz] Registro de merma: ' . $e->getMessage());
            $errores[] = 'No fue posible registrar la merma. Inténtalo nuevamente.';
        }
    }
}

$pageTitle = 'Registrar merma';
$pageSubtitle = 'Registra producto perdido y descuéntalo del inventario automáticamente.';
$pageIcon = 'bi-trash3';
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('mermas/index.php')) . '"><i class="bi bi-arrow-left me-1"></i>Ver mermas</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-trash3"></i> Datos de la merma</h2>
                    <p class="panel-subtitle">La cantidad se descontará del inventario al guardar.</p>
                </div>
            </div>
            <div class="panel-body">
                <?php if ($errores): ?>
                    <div class="alert alert-danger">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-octagon-fill mt-1"></i>
                            <div>
                                <strong>No se pudo registrar la merma:</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    <?php foreach ($errores as $error): ?>
                                        <li><?= e($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('mermas/registrar.php')) ?>" data-submit-once>
                    <?= csrf_field() ?>

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-box-seam"></i> Producto perdido</div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="id_insumo" class="form-label">Insumo <span class="req">*</span></label>
                                <select class="form-select" id="id_insumo" name="id_insumo" required>
                                    <option value="">Selecciona el insumo</option>
                                    <?php foreach ($insumos as $insumo): ?>
                                        <option value="<?= (int) $insumo['id_insumo'] ?>"
                                                data-stock="<?= e((string) $insumo['cantidad_actual']) ?>"
                                                data-unidad="<?= e($insumo['unidad_medida']) ?>"
                                                data-categoria="<?= e($insumo['categoria']) ?>"
                                                <?= $valores['id_insumo'] === (string) $insumo['id_insumo'] ? 'selected' : '' ?>>
                                            <?= e($insumo['nombre']) ?> · <?= e(formatear_cantidad($insumo['cantidad_actual'])) ?> <?= e($insumo['unidad_medida']) ?> disponibles
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="cantidad" class="form-label">Cantidad perdida <span class="req">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cantidad" name="cantidad"
                                           min="0.001" step="0.001" value="<?= e($valores['cantidad']) ?>" required>
                                    <span class="input-group-text" id="unidadMerma">—</span>
                                </div>
                                <div class="form-text" id="disponibleTexto">Selecciona un insumo.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="motivo" class="form-label">Motivo <span class="req">*</span></label>
                                <select class="form-select" id="motivo" name="motivo" required>
                                    <option value="">Selecciona el motivo</option>
                                    <?php foreach ($motivos as $clave => $etiqueta): ?>
                                        <option value="<?= e($clave) ?>" <?= $valores['motivo'] === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha" class="form-label">Fecha <span class="req">*</span></label>
                                <input type="date" class="form-control" id="fecha" name="fecha"
                                       max="<?= e(fecha_hoy()) ?>" value="<?= e($valores['fecha']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="descripcion" class="form-label">Descripción <span class="req">*</span></label>
                                <textarea class="form-control" id="descripcion" name="descripcion" maxlength="500" required
                                          placeholder="Explica qué ocurrió con el producto…"><?= e($valores['descripcion']) ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Usuario responsable</label>
                                <input type="text" class="form-control" value="<?= e(usuario_actual()['nombre_completo']) ?>" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <span class="required-note me-auto"><span class="req">*</span> Campos obligatorios</span>
                        <a href="<?= e(base_url('mermas/index.php')) ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-gold"><i class="bi bi-check2 me-1"></i>Registrar merma</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-info-circle"></i> Existencia del insumo</h2>
            </div>
            <div class="panel-body">
                <div class="text-muted-app small-70">Insumo</div>
                <div class="fw-700 text-coffee mb-2" id="insumoVista">—</div>
                <div class="text-muted-app small-70">Disponible</div>
                <div class="fs-2 fw-700 text-coffee" id="stockVista">—</div>
                <div class="small text-muted-app" id="categoriaVista">Selecciona un insumo.</div>
                <div class="divider"></div>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    No se puede registrar una merma mayor a las existencias. La operación es irreversible en el inventario.
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-calendar-x"></i> Lotes caducados</h2>
                    <p class="panel-subtitle">Con existencia disponible.</p>
                </div>
            </div>
            <div class="panel-body panel-body-flush">
                <?php if (!$caducados): ?>
                    <div class="table-empty py-4"><i class="bi bi-check2-circle"></i><strong>Sin lotes caducados</strong><span>No hay producto vencido con existencia.</span></div>
                <?php else: ?>
                    <?php foreach (array_slice($caducados, 0, 4) as $lote): ?>
                        <div class="alert-row">
                            <span class="alert-row-icon"><i class="bi bi-calendar-x"></i></span>
                            <div class="alert-row-main">
                                <strong><?= e($lote['insumo']) ?></strong>
                                <small>Lote <?= e($lote['numero_lote']) ?> · <?= e(formatear_cantidad($lote['cantidad_actual'])) ?> <?= e($lote['unidad_medida']) ?></small>
                            </div>
                            <div class="alert-row-meta">
                                <span class="badge-soft bg-danger-soft"><?= (int) $lote['dias_caducado'] ?> día(s)</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = '<script>
    const insumo = document.getElementById("id_insumo");
    const unidad = document.getElementById("unidadMerma");
    const disponibleTexto = document.getElementById("disponibleTexto");
    const insumoVista = document.getElementById("insumoVista");
    const stockVista = document.getElementById("stockVista");
    const categoriaVista = document.getElementById("categoriaVista");
    const cantidad = document.getElementById("cantidad");

    function actualizarInsumo() {
        const opcion = insumo.options[insumo.selectedIndex];
        if (!opcion || !opcion.value) {
            unidad.textContent = "—";
            disponibleTexto.textContent = "Selecciona un insumo.";
            insumoVista.textContent = "—";
            stockVista.textContent = "—";
            categoriaVista.textContent = "Selecciona un insumo.";
            cantidad.removeAttribute("max");
            return;
        }
        const stock = parseFloat(opcion.dataset.stock || "0");
        const unidadTexto = opcion.dataset.unidad || "";
        unidad.textContent = unidadTexto;
        cantidad.max = stock;
        disponibleTexto.textContent = "Disponible: " + stock.toLocaleString("es-MX", { maximumFractionDigits: 3 }) + " " + unidadTexto;
        insumoVista.textContent = opcion.textContent.split(" · ")[0];
        stockVista.textContent = stock.toLocaleString("es-MX", { maximumFractionDigits: 3 }) + " " + unidadTexto;
        categoriaVista.textContent = opcion.dataset.categoria || "—";
    }
    if (insumo) {
        insumo.addEventListener("change", actualizarInsumo);
        actualizarInsumo();
    }
</script>';
require __DIR__ . '/../includes/footer.php';

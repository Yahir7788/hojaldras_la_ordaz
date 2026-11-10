<?php
/**
 * Registrar una salida de almacén (gerente y cocinero).
 * El inventario disminuye y se consumen los lotes FEFO.
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

$errores = [];
$valores = [
    'id_insumo'     => (string) get('insumo', ''),
    'cantidad'      => '',
    'fecha'         => fecha_hoy(),
    'destino'       => 'Cocina - preparación',
    'observaciones' => '',
];

if (es_post()) {
    verificar_csrf();

    foreach ($valores as $campo => $_) {
        $valores[$campo] = trim((string) post($campo));
    }

    $idInsumo = (int) $valores['id_insumo'];
    $cantidad = cantidad_positiva($valores['cantidad']);
    $fecha = $valores['fecha'];
    $destino = texto_requerido($valores['destino'], 2, 100);
    $observaciones = $valores['observaciones'] !== '' ? texto_requerido($valores['observaciones'], 2, 500) : '';

    if ($idInsumo <= 0) {
        $errores[] = 'Selecciona un insumo.';
    }
    if ($cantidad === null) {
        $errores[] = 'La cantidad solicitada debe ser mayor que cero.';
    }
    if (!validar_fecha($fecha)) {
        $errores[] = 'La fecha no es válida.';
    } elseif ($fecha > fecha_hoy()) {
        $errores[] = 'La fecha de la salida no puede ser futura.';
    }
    if ($destino === null) {
        $errores[] = 'Indica el destino de la salida (máximo 100 caracteres).';
    }
    if ($observaciones === null) {
        $errores[] = 'Las observaciones no pueden superar los 500 caracteres.';
    }

    if (!$errores && $cantidad !== null) {
        try {
            $pdo->beginTransaction();
            $insumo = bloquear_insumo($pdo, $idInsumo);

            if (!$insumo || (int) $insumo['activo'] !== 1) {
                throw new RuntimeException('El insumo seleccionado no está disponible.');
            }

            $stockAnterior = (float) $insumo['cantidad_actual'];
            if ($cantidad > $stockAnterior + 0.0004) {
                throw new RuntimeException('No hay existencias suficientes de ' . $insumo['nombre'] . '. Disponible: ' . formatear_cantidad($stockAnterior) . ' ' . $insumo['unidad_medida'] . '.');
            }

            $stockNuevo = round($stockAnterior - $cantidad, 3);

            consumir_lotes($pdo, $idInsumo, $cantidad);

            $stmtStock = $pdo->prepare('UPDATE insumos SET cantidad_actual = :cantidad WHERE id_insumo = :id');
            $stmtStock->execute([':cantidad' => $stockNuevo, ':id' => $idInsumo]);

            $stmtSalida = $pdo->prepare(
                'INSERT INTO salidas (id_insumo, cantidad, fecha, destino, id_usuario, observaciones)
                 VALUES (:insumo, :cantidad, :fecha, :destino, :usuario, :observaciones)'
            );
            $stmtSalida->execute([
                ':insumo'        => $idInsumo,
                ':cantidad'      => $cantidad,
                ':fecha'         => $fecha,
                ':destino'       => $destino,
                ':usuario'       => (int) usuario_actual()['id_usuario'],
                ':observaciones' => $observaciones ?: null,
            ]);
            $idSalida = (int) $pdo->lastInsertId();

            registrar_movimiento(
                $pdo,
                $idInsumo,
                'Salida',
                $cantidad,
                $stockAnterior,
                $stockNuevo,
                $idSalida,
                'salidas',
                (int) usuario_actual()['id_usuario'],
                'Salida a ' . $destino . ($observaciones ? ' · ' . $observaciones : '')
            );

            $pdo->commit();
            flash('success', 'Salida registrada: ' . formatear_cantidad($cantidad) . ' ' . $insumo['unidad_medida'] . ' de ' . $insumo['nombre'] . ' hacia ' . $destino . '. Quedan ' . formatear_cantidad($stockNuevo) . '.');
            redirigir('salidas/index.php');
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[La Ordaz] Registro de salida: ' . $e->getMessage());
            $errores[] = 'No fue posible registrar la salida. Inténtalo nuevamente.';
        }
    }
}

$pageTitle = 'Registrar salida';
$pageSubtitle = 'Entrega materia prima a cocina y descuenta el inventario.';
$pageIcon = 'bi-box-arrow-up';
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('salidas/index.php')) . '"><i class="bi bi-arrow-left me-1"></i>Ver salidas</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-box-arrow-up"></i> Datos de la salida</h2>
                    <p class="panel-subtitle">El sistema impide retirar más cantidad de la disponible.</p>
                </div>
            </div>
            <div class="panel-body">
                <?php if ($errores): ?>
                    <div class="alert alert-danger">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-octagon-fill mt-1"></i>
                            <div>
                                <strong>No se pudo registrar la salida:</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    <?php foreach ($errores as $error): ?>
                                        <li><?= e($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('salidas/registrar.php')) ?>" data-submit-once>
                    <?= csrf_field() ?>

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-box-seam"></i> Insumo solicitado</div>
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
                                                data-minimo="<?= e((string) $insumo['stock_minimo']) ?>"
                                                <?= $valores['id_insumo'] === (string) $insumo['id_insumo'] ? 'selected' : '' ?>>
                                            <?= e($insumo['nombre']) ?> · <?= e(formatear_cantidad($insumo['cantidad_actual'])) ?> <?= e($insumo['unidad_medida']) ?> disponibles
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!$insumos): ?>
                                    <div class="form-text text-danger">No hay insumos con existencia disponible.</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="cantidad" class="form-label">Cantidad solicitada <span class="req">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cantidad" name="cantidad"
                                           min="0.001" step="0.001" value="<?= e($valores['cantidad']) ?>" required>
                                    <span class="input-group-text" id="unidadSalida">—</span>
                                </div>
                                <div class="form-text" id="disponibleTexto">Selecciona un insumo.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha" class="form-label">Fecha <span class="req">*</span></label>
                                <input type="date" class="form-control" id="fecha" name="fecha"
                                       max="<?= e(fecha_hoy()) ?>" value="<?= e($valores['fecha']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="destino" class="form-label">Destino <span class="req">*</span></label>
                                <input type="text" class="form-control" id="destino" name="destino" maxlength="100"
                                       list="destinosComunes" value="<?= e($valores['destino']) ?>" required>
                                <datalist id="destinosComunes">
                                    <option value="Cocina - preparación"></option>
                                    <option value="Cocina - panadería"></option>
                                    <option value="Cocina - producción"></option>
                                    <option value="Mostrador"></option>
                                </datalist>
                            </div>
                            <div class="col-12">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" maxlength="500"
                                          placeholder="Motivo del consumo, producto a preparar, incidencias…"><?= e($valores['observaciones']) ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Usuario responsable</label>
                                <input type="text" class="form-control" value="<?= e(usuario_actual()['nombre_completo']) ?>" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <span class="required-note me-auto"><span class="req">*</span> Campos obligatorios</span>
                        <a href="<?= e(base_url('salidas/index.php')) ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-gold"><i class="bi bi-check2 me-1"></i>Registrar salida</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-info-circle"></i> Existencia disponible</h2>
            </div>
            <div class="panel-body">
                <div class="text-muted-app small-70">Insumo</div>
                <div class="fw-700 text-coffee mb-2" id="insumoVista">—</div>
                <div class="text-muted-app small-70">Disponible</div>
                <div class="fs-2 fw-700 text-coffee" id="stockVista">—</div>
                <div class="small text-muted-app" id="minimoVista">Selecciona un insumo.</div>
                <div class="divider"></div>
                <div class="alert alert-info small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Se descuentan primero los lotes con caducidad más próxima (FEFO). Verifica el estado del producto antes de entregarlo.
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-shield-check"></i> Reglas de la salida</h2>
            </div>
            <div class="panel-body small text-muted-app">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">No se permiten cantidades menores o iguales a cero.</li>
                    <li class="mb-1">No se puede retirar más de lo disponible.</li>
                    <li class="mb-1">El inventario y los lotes se actualizan en una transacción.</li>
                    <li class="mb-0">Todo movimiento guarda fecha, insumo, cantidad y usuario.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = '<script>
    const insumo = document.getElementById("id_insumo");
    const cantidad = document.getElementById("cantidad");
    const unidad = document.getElementById("unidadSalida");
    const disponibleTexto = document.getElementById("disponibleTexto");
    const insumoVista = document.getElementById("insumoVista");
    const stockVista = document.getElementById("stockVista");
    const minimoVista = document.getElementById("minimoVista");

    function actualizarInsumo() {
        const opcion = insumo.options[insumo.selectedIndex];
        if (!opcion || !opcion.value) {
            unidad.textContent = "—";
            disponibleTexto.textContent = "Selecciona un insumo.";
            insumoVista.textContent = "—";
            stockVista.textContent = "—";
            minimoVista.textContent = "Selecciona un insumo.";
            cantidad.removeAttribute("max");
            return;
        }
        const stock = parseFloat(opcion.dataset.stock || "0");
        const unidadTexto = opcion.dataset.unidad || "";
        const minimo = parseFloat(opcion.dataset.minimo || "0");
        unidad.textContent = unidadTexto;
        cantidad.max = stock;
        disponibleTexto.textContent = "Disponible: " + stock.toLocaleString("es-MX", { maximumFractionDigits: 3 }) + " " + unidadTexto;
        insumoVista.textContent = opcion.textContent.split(" · ")[0];
        stockVista.textContent = stock.toLocaleString("es-MX", { maximumFractionDigits: 3 }) + " " + unidadTexto;
        minimoVista.textContent = "Stock mínimo: " + minimo.toLocaleString("es-MX", { maximumFractionDigits: 3 }) + " " + unidadTexto;
    }
    if (insumo) {
        insumo.addEventListener("change", actualizarInsumo);
        actualizarInsumo();
    }
</script>';
require __DIR__ . '/../includes/footer.php';

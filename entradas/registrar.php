<?php
/**
 * Registrar una entrada de almacén (solo gerente).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

$pdo = db();
$insumos = obtener_insumos_activos($pdo);
$idPreseleccionado = (int) get('insumo', 0);

$errores = [];
$valores = [
    'id_insumo'       => $idPreseleccionado > 0 ? (string) $idPreseleccionado : '',
    'cantidad'        => '',
    'fecha_ingreso'   => fecha_hoy(),
    'fecha_caducidad' => '',
    'numero_lote'     => '',
    'observaciones'   => '',
];

if (es_post()) {
    verificar_csrf();

    foreach ($valores as $campo => $_) {
        $valores[$campo] = trim((string) post($campo));
    }

    $idInsumo = (int) $valores['id_insumo'];
    $cantidad = cantidad_positiva($valores['cantidad']);
    $fechaIngreso = $valores['fecha_ingreso'];
    $numeroLote = texto_requerido($valores['numero_lote'], 1, 60);
    $observaciones = $valores['observaciones'] !== '' ? texto_requerido($valores['observaciones'], 2, 500) : '';

    $stmt = $pdo->prepare(
        'SELECT i.*, c.nombre AS categoria, c.perecedero
         FROM insumos i
         INNER JOIN categorias c ON c.id_categoria = i.id_categoria
         WHERE i.id_insumo = :id AND i.activo = 1'
    );
    $stmt->execute([':id' => $idInsumo]);
    $insumo = $stmt->fetch();

    if (!$insumo) {
        $errores[] = 'Selecciona un insumo activo.';
    }
    if ($cantidad === null) {
        $errores[] = 'La cantidad recibida debe ser mayor que cero.';
    }
    if (!validar_fecha($fechaIngreso)) {
        $errores[] = 'La fecha de ingreso no es válida.';
    } elseif ($fechaIngreso > fecha_hoy()) {
        $errores[] = 'La fecha de ingreso no puede ser futura.';
    }
    if ($numeroLote === null) {
        $errores[] = 'El número de lote es obligatorio (máximo 60 caracteres).';
    }
    if ($observaciones === null) {
        $errores[] = 'Las observaciones no pueden superar los 500 caracteres.';
    }

    $caducidad = null;
    if ($valores['fecha_caducidad'] !== '') {
        if (!validar_fecha($valores['fecha_caducidad'])) {
            $errores[] = 'La fecha de caducidad no es válida.';
        } else {
            $caducidad = $valores['fecha_caducidad'];
        }
    }
    if ($insumo && (int) $insumo['perecedero'] === 1 && $caducidad === null) {
        $errores[] = 'Este insumo es perecedero: la fecha de caducidad es obligatoria.';
    }
    if ($caducidad !== null && validar_fecha($fechaIngreso) && $caducidad < $fechaIngreso) {
        $errores[] = 'La fecha de caducidad no puede ser anterior a la fecha de ingreso.';
    }

    if (!$errores && $insumo && $cantidad !== null) {
        try {
            $pdo->beginTransaction();
            $insumoBloqueado = bloquear_insumo($pdo, $idInsumo);
            if (!$insumoBloqueado || (int) $insumoBloqueado['activo'] !== 1) {
                throw new RuntimeException('El insumo ya no está disponible.');
            }

            $stockAnterior = (float) $insumoBloqueado['cantidad_actual'];
            $stockNuevo = round($stockAnterior + $cantidad, 3);

            $stmtEntrada = $pdo->prepare(
                'INSERT INTO entradas
                    (id_insumo, cantidad, unidad_medida, fecha_ingreso, fecha_caducidad, numero_lote,
                     id_usuario, observaciones)
                 VALUES (:insumo, :cantidad, :unidad, :ingreso, :caducidad, :lote, :usuario, :observaciones)'
            );
            $stmtEntrada->execute([
                ':insumo'        => $idInsumo,
                ':cantidad'      => $cantidad,
                ':unidad'        => $insumoBloqueado['unidad_medida'],
                ':ingreso'       => $fechaIngreso,
                ':caducidad'     => $caducidad,
                ':lote'          => $numeroLote,
                ':usuario'       => (int) usuario_actual()['id_usuario'],
                ':observaciones' => $observaciones ?: null,
            ]);
            $idEntrada = (int) $pdo->lastInsertId();

            $stmtLote = $pdo->prepare(
                'INSERT INTO lotes (id_insumo, id_entrada, numero_lote, cantidad_inicial, cantidad_actual, fecha_caducidad)
                 VALUES (:insumo, :entrada, :lote, :inicial, :actual, :caducidad)'
            );
            $stmtLote->execute([
                ':insumo'    => $idInsumo,
                ':entrada'   => $idEntrada,
                ':lote'      => $numeroLote,
                ':inicial'   => $cantidad,
                ':actual'    => $cantidad,
                ':caducidad' => $caducidad,
            ]);

            $stmtStock = $pdo->prepare('UPDATE insumos SET cantidad_actual = :cantidad WHERE id_insumo = :id');
            $stmtStock->execute([':cantidad' => $stockNuevo, ':id' => $idInsumo]);

            registrar_movimiento(
                $pdo,
                $idInsumo,
                'Entrada',
                $cantidad,
                $stockAnterior,
                $stockNuevo,
                $idEntrada,
                'entradas',
                (int) usuario_actual()['id_usuario'],
                'Entrada de almacén · lote ' . $numeroLote . ($observaciones ? ' · ' . $observaciones : '')
            );

            $pdo->commit();
            flash('success', 'Entrada registrada: ' . formatear_cantidad($cantidad) . ' ' . $insumoBloqueado['unidad_medida'] . ' de ' . $insumoBloqueado['nombre'] . '. El inventario aumentó a ' . formatear_cantidad($stockNuevo) . '.');
            redirigir('entradas/index.php');
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[La Ordaz] Registro de entrada: ' . $e->getMessage());
            $errores[] = 'No fue posible registrar la entrada. Inténtalo nuevamente.';
        }
    }
}

$pageTitle = 'Registrar entrada';
$pageSubtitle = 'Recibe materia prima y aumenta el inventario automáticamente.';
$pageIcon = 'bi-box-arrow-in-down';
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('entradas/index.php')) . '"><i class="bi bi-arrow-left me-1"></i>Ver entradas</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-box-arrow-in-down"></i> Datos de la entrada</h2>
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

                <form method="post" action="<?= e(base_url('entradas/registrar.php')) ?>" data-submit-once>
                    <?= csrf_field() ?>

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-box-seam"></i> Insumo recibido</div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="id_insumo" class="form-label">Insumo <span class="req">*</span></label>
                                <select class="form-select" id="id_insumo" name="id_insumo" required>
                                    <option value="">Selecciona el insumo</option>
                                    <?php foreach ($insumos as $insumo): ?>
                                        <option value="<?= (int) $insumo['id_insumo'] ?>"
                                                data-unidad="<?= e($insumo['unidad_medida']) ?>"
                                                data-categoria="<?= e($insumo['categoria']) ?>"
                                                data-stock="<?= e((string) $insumo['cantidad_actual']) ?>"
                                                <?= $valores['id_insumo'] === (string) $insumo['id_insumo'] ? 'selected' : '' ?>>
                                            <?= e($insumo['nombre']) ?> · <?= e($insumo['categoria']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="cantidad" class="form-label">Cantidad recibida <span class="req">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cantidad" name="cantidad"
                                           min="0.001" step="0.001" value="<?= e($valores['cantidad']) ?>" required>
                                    <span class="input-group-text" id="unidadEntrada">—</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha_ingreso" class="form-label">Fecha de ingreso <span class="req">*</span></label>
                                <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso"
                                       max="<?= e(fecha_hoy()) ?>" value="<?= e($valores['fecha_ingreso']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha_caducidad" class="form-label">Fecha de caducidad</label>
                                <input type="date" class="form-control" id="fecha_caducidad" name="fecha_caducidad"
                                       value="<?= e($valores['fecha_caducidad']) ?>">
                                <div class="form-text">Obligatoria para insumos perecederos.</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-upc-scan"></i> Trazabilidad</div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="numero_lote" class="form-label">Número de lote <span class="req">*</span></label>
                                <input type="text" class="form-control" id="numero_lote" name="numero_lote" maxlength="60"
                                       value="<?= e($valores['numero_lote']) ?>" placeholder="Ej. LOTE-20261109-001" required>
                            </div>
                            <div class="col-md-7">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <input type="text" class="form-control" id="observaciones" name="observaciones" maxlength="500"
                                       value="<?= e($valores['observaciones']) ?>" placeholder="Proveedor, factura, condiciones del producto…">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Usuario responsable</label>
                                <input type="text" class="form-control" value="<?= e(usuario_actual()['nombre_completo']) ?>" disabled>
                                <div class="form-text">Se registra automáticamente con la sesión activa.</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <span class="required-note me-auto"><span class="req">*</span> Campos obligatorios</span>
                        <a href="<?= e(base_url('entradas/index.php')) ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-gold"><i class="bi bi-check2 me-1"></i>Registrar entrada</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-info-circle"></i> Insumo seleccionado</h2>
            </div>
            <div class="panel-body">
                <div class="text-muted-app small-70">Categoría</div>
                <div class="fw-700 text-coffee mb-2" id="categoriaVista">—</div>
                <div class="text-muted-app small-70">Existencia actual</div>
                <div class="fs-3 fw-700 text-coffee" id="stockVista">—</div>
                <div class="divider"></div>
                <div class="alert alert-success small mb-0">
                    <i class="bi bi-arrow-up-circle me-1"></i>
                    Al guardar, la cantidad se sumará automáticamente al inventario y se creará el lote para el control de caducidad.
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-shield-check"></i> Reglas de la entrada</h2>
            </div>
            <div class="panel-body small text-muted-app">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">La cantidad debe ser mayor que cero.</li>
                    <li class="mb-1">Todo producto perecedero exige caducidad.</li>
                    <li class="mb-1">El lote queda ligado a la entrada.</li>
                    <li class="mb-0">La operación se guarda en una transacción segura.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = '<script>
    const insumo = document.getElementById("id_insumo");
    const unidad = document.getElementById("unidadEntrada");
    const categoria = document.getElementById("categoriaVista");
    const stock = document.getElementById("stockVista");
    function actualizarInsumo() {
        const opcion = insumo.options[insumo.selectedIndex];
        if (!opcion || !opcion.value) {
            unidad.textContent = "—";
            categoria.textContent = "—";
            stock.textContent = "—";
            return;
        }
        const unidadTexto = opcion.dataset.unidad || "";
        unidad.textContent = unidadTexto;
        categoria.textContent = opcion.dataset.categoria || "—";
        stock.textContent = parseFloat(opcion.dataset.stock || "0").toLocaleString("es-MX", { maximumFractionDigits: 3 }) + " " + unidadTexto;
    }
    if (insumo) {
        insumo.addEventListener("change", actualizarInsumo);
        actualizarInsumo();
    }
</script>';
require __DIR__ . '/../includes/footer.php';

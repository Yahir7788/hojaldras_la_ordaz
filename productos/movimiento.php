<?php
/**
 * Registrar producción, venta o ajuste de producto terminado.
 * Gerente: los tres tipos. Ventas: producción y venta.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente', 'ventas']);

$pdo = db();
$esGerente = es_gerente();

$acciones = [
    'produccion' => [
        'tipo'      => 'Producción',
        'titulo'    => 'Registrar producción',
        'subtitulo' => 'Suma al inventario las unidades recién horneadas.',
        'icono'     => 'bi-plus-circle',
        'color'     => 'success',
        'etiqueta'  => 'Unidades producidas',
        'ayuda'     => 'Cantidad de producto terminado que se agrega a la venta.',
    ],
    'venta' => [
        'tipo'      => 'Venta',
        'titulo'    => 'Registrar venta',
        'subtitulo' => 'Descuenta del inventario las unidades entregadas al cliente.',
        'icono'     => 'bi-bag-check',
        'color'     => 'info',
        'etiqueta'  => 'Unidades vendidas',
        'ayuda'     => 'No puede superar la existencia disponible.',
    ],
    'ajuste' => [
        'tipo'      => 'Ajuste',
        'titulo'    => 'Ajustar existencia',
        'subtitulo' => 'Corrige el stock con un movimiento auditado.',
        'icono'     => 'bi-sliders',
        'color'     => 'warning',
        'etiqueta'  => 'Nueva existencia',
        'ayuda'     => 'La existencia quedará exactamente en este valor.',
    ],
];

$accion = (string) get('accion', post('accion', 'produccion'));
if (!isset($acciones[$accion])) {
    $accion = 'produccion';
}
if ($accion === 'ajuste' && !$esGerente) {
    flash('danger', 'Solo el gerente puede ajustar la existencia de producto terminado.');
    redirigir('productos/index.php');
}

$idProducto = (int) get('producto', post('producto', 0));

$stmt = $pdo->prepare('SELECT * FROM productos WHERE id_producto = :id');
$stmt->execute([':id' => $idProducto]);
$producto = $stmt->fetch();

if (!$producto) {
    flash('danger', 'El producto solicitado no existe.');
    redirigir('productos/index.php');
}
if ((int) $producto['activo'] !== 1) {
    flash('danger', 'El producto «' . $producto['nombre'] . '» está inactivo. Reactívalo antes de registrar movimientos.');
    redirigir('productos/index.php');
}

$config = $acciones[$accion];
$errores = [];
$valores = [
    'cantidad'      => '',
    'observaciones' => '',
];

if (es_post()) {
    verificar_csrf();

    foreach ($valores as $campo => $_) {
        $valores[$campo] = trim((string) post($campo));
    }

    $cantidad = ctype_digit($valores['cantidad']) ? (int) $valores['cantidad'] : null;
    $observaciones = $valores['observaciones'] !== ''
        ? texto_requerido($valores['observaciones'], 2, 500)
        : '';

    if ($cantidad === null) {
        $errores[] = 'La cantidad debe ser un número entero.';
    } elseif ($accion === 'ajuste' && $cantidad < 0) {
        $errores[] = 'La nueva existencia no puede ser negativa.';
    } elseif ($accion !== 'ajuste' && $cantidad <= 0) {
        $errores[] = 'La cantidad debe ser mayor que cero.';
    }

    if ($observaciones === null) {
        $errores[] = 'Las observaciones no pueden superar los 500 caracteres.';
    }
    if ($accion === 'ajuste' && ($observaciones === '' || $observaciones === null)) {
        $errores[] = 'Describe el motivo del ajuste (mínimo 2 caracteres).';
    }

    if (!$errores && $cantidad !== null) {
        try {
            $pdo->beginTransaction();

            $bloqueo = $pdo->prepare('SELECT * FROM productos WHERE id_producto = :id FOR UPDATE');
            $bloqueo->execute([':id' => $idProducto]);
            $actual = $bloqueo->fetch();

            if (!$actual || (int) $actual['activo'] !== 1) {
                throw new RuntimeException('El producto ya no está disponible para movimientos.');
            }

            $stockAnterior = (int) $actual['stock_actual'];

            if ($accion === 'produccion') {
                $stockNuevo = $stockAnterior + $cantidad;
                $cantidadMovimiento = $cantidad;
                $detalle = 'Producción registrada.';
            } elseif ($accion === 'venta') {
                if ($cantidad > $stockAnterior) {
                    throw new RuntimeException('No hay existencias suficientes de ' . $actual['nombre'] . '. Disponible: ' . $stockAnterior . ' unidades.');
                }
                $stockNuevo = $stockAnterior - $cantidad;
                $cantidadMovimiento = $cantidad;
                $detalle = 'Venta registrada.';
            } else {
                if ($cantidad === $stockAnterior) {
                    throw new RuntimeException('El ajuste no modifica la existencia actual (' . $stockAnterior . ' unidades).');
                }
                $stockNuevo = $cantidad;
                $cantidadMovimiento = abs($stockNuevo - $stockAnterior);
                $detalle = 'Ajuste de existencia: ' . $stockAnterior . ' → ' . $stockNuevo . '.';
            }

            $stmtMov = $pdo->prepare(
                'INSERT INTO movimientos_producto
                    (id_producto, tipo, cantidad, stock_anterior, stock_nuevo, id_usuario, observaciones)
                 VALUES (:producto, :tipo, :cantidad, :anterior, :nuevo, :usuario, :observaciones)'
            );
            $stmtMov->execute([
                ':producto'      => $idProducto,
                ':tipo'          => $config['tipo'],
                ':cantidad'      => $cantidadMovimiento,
                ':anterior'      => $stockAnterior,
                ':nuevo'         => $stockNuevo,
                ':usuario'       => (int) usuario_actual()['id_usuario'],
                ':observaciones' => trim($detalle . ' ' . $observaciones),
            ]);

            $stmtStock = $pdo->prepare('UPDATE productos SET stock_actual = :stock WHERE id_producto = :id');
            $stmtStock->execute([':stock' => $stockNuevo, ':id' => $idProducto]);

            $pdo->commit();

            $mensaje = match ($accion) {
                'produccion' => 'Producción registrada: +' . $cantidad . ' unidades de ' . $actual['nombre'] . '. Existencia actual: ' . $stockNuevo . '.',
                'venta'      => 'Venta registrada: −' . $cantidad . ' unidades de ' . $actual['nombre'] . '. Existencia actual: ' . $stockNuevo . '.',
                default      => 'Ajuste registrado: ' . $actual['nombre'] . ' pasó de ' . $stockAnterior . ' a ' . $stockNuevo . ' unidades.',
            };
            flash('success', $mensaje);
            redirigir('productos/index.php');
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[La Ordaz] Movimiento de producto: ' . $e->getMessage());
            $errores[] = 'No fue posible registrar el movimiento. Inténtalo nuevamente.';
        }
    }
}

$stock = (int) $producto['stock_actual'];
$minimo = (int) $producto['stock_minimo'];
[$claseEstado, $textoEstado, $iconoEstado] = estado_stock($stock, $minimo);

$pageTitle = $config['titulo'];
$pageSubtitle = $config['subtitulo'];
$pageIcon = $config['icono'];
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('productos/index.php')) . '"><i class="bi bi-arrow-left me-1"></i>Volver al control</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="report-tabs mb-3">
    <?php foreach ($acciones as $clave => $opcion): ?>
        <?php if ($clave === 'ajuste' && !$esGerente) { continue; } ?>
        <a class="report-tab <?= $accion === $clave ? 'active' : '' ?>"
           href="<?= e(base_url('productos/movimiento.php?accion=' . $clave . '&producto=' . $idProducto)) ?>">
            <i class="bi <?= e($opcion['icono']) ?>"></i><?= e(ucfirst($clave)) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi <?= e($config['icono']) ?>"></i> <?= e($config['titulo']) ?></h2>
                    <p class="panel-subtitle">Producto: <strong><?= e($producto['nombre']) ?></strong> · <?= e($config['ayuda']) ?></p>
                </div>
            </div>
            <div class="panel-body">
                <?php if ($errores): ?>
                    <div class="alert alert-danger">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-octagon-fill mt-1"></i>
                            <div>
                                <strong>No se pudo registrar el movimiento:</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    <?php foreach ($errores as $error): ?>
                                        <li><?= e($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('productos/movimiento.php?accion=' . $accion . '&producto=' . $idProducto)) ?>" data-submit-once>
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="<?= e($accion) ?>">
                    <input type="hidden" name="producto" value="<?= $idProducto ?>">

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-basket2"></i> Datos del movimiento</div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="cantidad" class="form-label"><?= e($config['etiqueta']) ?> <span class="req">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cantidad" name="cantidad"
                                           min="<?= $accion === 'ajuste' ? '0' : '1' ?>" step="1"
                                           <?= $accion === 'venta' ? 'max="' . $stock . '"' : '' ?>
                                           value="<?= e($valores['cantidad']) ?>" required autofocus>
                                    <span class="input-group-text">unidades</span>
                                </div>
                                <div class="form-text"><?= e($config['ayuda']) ?></div>
                            </div>
                            <div class="col-md-7">
                                <label for="observaciones" class="form-label">
                                    Observaciones <?= $accion === 'ajuste' ? '<span class="req">*</span>' : '' ?>
                                </label>
                                <textarea class="form-control" id="observaciones" name="observaciones" maxlength="500"
                                          rows="3" <?= $accion === 'ajuste' ? 'required' : '' ?>
                                          placeholder="<?= $accion === 'ajuste' ? 'Motivo del ajuste: conteo físico, producto dañado, corrección…' : 'Detalle de la producción o venta (opcional)…' ?>"><?= e($valores['observaciones']) ?></textarea>
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
                        <a href="<?= e(base_url('productos/index.php')) ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-gold">
                            <i class="bi bi-check2 me-1"></i><?= e($config['titulo']) ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-info-circle"></i> Producto seleccionado</h2>
            </div>
            <div class="panel-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted-app small-70">Producto</div>
                        <div class="fw-700 text-coffee"><?= e($producto['nombre']) ?></div>
                    </div>
                    <span class="badge-soft bg-<?= e($claseEstado) ?>-soft"><i class="bi <?= e($iconoEstado) ?>"></i><?= e($textoEstado) ?></span>
                </div>
                <div class="divider"></div>
                <div class="text-muted-app small-70">Existencia actual</div>
                <div class="fs-2 fw-700 text-coffee"><?= $stock ?> <span class="fs-6">unidades</span></div>
                <div class="small text-muted-app">Stock mínimo: <?= $minimo ?> unidades</div>
                <div class="text-muted-app small-70 mt-2">Precio de venta</div>
                <div class="fw-700 text-gold"><?= e(formatear_moneda($producto['precio'])) ?></div>
                <div class="divider"></div>
                <div class="text-muted-app small-70">Resultado estimado</div>
                <div class="fs-4 fw-700" id="resultadoStock">—</div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-shield-check"></i> Reglas del movimiento</h2>
            </div>
            <div class="panel-body small text-muted-app">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">Las cantidades se manejan en unidades enteras.</li>
                    <li class="mb-1">Una venta no puede superar la existencia disponible.</li>
                    <li class="mb-1">Solo el gerente puede ajustar la existencia.</li>
                    <li class="mb-0">Todo movimiento guarda fecha, producto, cantidad y usuario.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = '<script>
    const cantidad = document.getElementById("cantidad");
    const resultado = document.getElementById("resultadoStock");
    const stockActual = ' . $stock . ';
    const accion = ' . json_encode($accion) . ';

    function calcularResultado() {
        if (!cantidad || !resultado) return;
        const valor = parseInt(cantidad.value || "0", 10);
        if (isNaN(valor) || cantidad.value === "") {
            resultado.textContent = "—";
            resultado.className = "fs-4 fw-700";
            return;
        }
        let nuevo = stockActual;
        if (accion === "produccion") nuevo = stockActual + valor;
        if (accion === "venta") nuevo = stockActual - valor;
        if (accion === "ajuste") nuevo = valor;

        resultado.textContent = nuevo + " unidades";
        resultado.className = "fs-4 fw-700 " + (nuevo < 0 ? "text-danger" : (nuevo <= ' . $minimo . ' ? "text-warning" : "text-success"));
    }
    if (cantidad) {
        cantidad.addEventListener("input", calcularResultado);
        calcularResultado();
    }
</script>';
require __DIR__ . '/../includes/footer.php';

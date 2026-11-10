<?php
/**
 * Centro de alertas de inventario (gerente y cocinero).
 * Se calcula en tiempo real con las existencias y los lotes.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente', 'cocinero']);

$pdo = db();
$diasVencimiento = (int) get('dias', 7);
if (!in_array($diasVencimiento, [7, 15, 30], true)) {
    $diasVencimiento = 7;
}

$resumen = resumen_alertas($pdo);
$stockBajo = insumos_con_stock_bajo($pdo);
$porVencer = lotes_por_vencer($pdo, $diasVencimiento);
$caducados = lotes_caducados($pdo);
$esGerente = es_gerente();

$agotados = array_values(array_filter($stockBajo, static fn(array $i): bool => (float) $i['cantidad_actual'] <= 0));
$criticos = array_values(array_filter($stockBajo, static fn(array $i): bool => (float) $i['cantidad_actual'] > 0 && (float) $i['cantidad_actual'] < (float) $i['stock_minimo']));
$enMinimo = array_values(array_filter($stockBajo, static fn(array $i): bool => (float) $i['cantidad_actual'] > 0 && abs((float) $i['cantidad_actual'] - (float) $i['stock_minimo']) < 0.0005));

$pageTitle = 'Alertas de inventario';
$pageSubtitle = 'Existencias críticas, reabastecimiento y control de caducidades.';
$pageIcon = 'bi-exclamation-triangle';
$pageActions = '<a class="btn btn-soft" href="' . e(base_url('alertas/index.php?dias=' . $diasVencimiento)) . '"><i class="bi bi-arrow-clockwise me-1"></i>Actualizar</a>';

require __DIR__ . '/../includes/header.php';
?>

<?php if ($resumen['total'] === 0): ?>
    <div class="alert-banner mb-4" style="background: var(--success-soft); border-color: #cfe7d7; color: #215c3a;">
        <i class="bi bi-check2-circle"></i>
        <div>
            <strong>Inventario saludable</strong>
            No hay alertas activas: las existencias están por encima del mínimo y no hay lotes próximos a caducar o vencidos.
        </div>
    </div>
<?php else: ?>
    <div class="alert-banner mb-4">
        <i class="bi bi-bell-fill"></i>
        <div>
            <strong>Hay <?= (int) $resumen['total'] ?> alerta(s) que requieren atención</strong>
            Revisa el reabastecimiento y las caducidades. Las alertas se recalculan con cada entrada, salida o merma.
        </div>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-danger">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-exclamation-octagon"></i></span></div>
            <p class="stat-value"><?= count($agotados) + count($criticos) ?></p>
            <div class="stat-label">Stock crítico</div>
            <div class="stat-hint"><i class="bi bi-arrow-down-circle"></i> Bajo el mínimo o agotado</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-warning">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-exclamation-triangle"></i></span></div>
            <p class="stat-value"><?= count($enMinimo) ?></p>
            <div class="stat-label">En el mínimo exacto</div>
            <div class="stat-hint"><i class="bi bi-dash-circle"></i> Reabastecer pronto</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-warning">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-clock-history"></i></span></div>
            <p class="stat-value"><?= (int) $resumen['por_vencer'] ?></p>
            <div class="stat-label">Próximos a caducar</div>
            <div class="stat-hint"><i class="bi bi-calendar-event"></i> En los próximos 7 días</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-danger">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-x-octagon"></i></span></div>
            <p class="stat-value"><?= count($caducados) ?></p>
            <div class="stat-label">Lotes caducados</div>
            <div class="stat-hint"><i class="bi bi-trash3"></i> Con existencia disponible</div>
        </div>
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-header">
        <div>
            <h2 class="panel-title"><i class="bi bi-box-seam"></i> Existencias críticas y reabastecimiento</h2>
            <p class="panel-subtitle">Insumos en o por debajo del stock mínimo. La sugerencia duplica el mínimo menos la existencia actual.</p>
        </div>
        <span class="badge-soft bg-danger-soft"><i class="bi bi-arrow-repeat"></i><?= count($stockBajo) ?> por atender</span>
    </div>
    <div class="panel-body panel-body-flush">
        <?php if (!$stockBajo): ?>
            <div class="table-empty"><i class="bi bi-check2-circle"></i><strong>Sin faltantes</strong><span>Todos los insumos activos están por encima del stock mínimo.</span></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-app">
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Categoría</th>
                            <th class="num">Existencia</th>
                            <th class="num">Mínimo</th>
                            <th>Estado</th>
                            <th class="num">Sugerido comprar</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockBajo as $insumo): ?>
                            <?php
                            [$clase, $texto, $icono] = estado_stock($insumo['cantidad_actual'], $insumo['stock_minimo']);
                            $sugerido = max(0, ((float) $insumo['stock_minimo'] * 2) - (float) $insumo['cantidad_actual']);
                            ?>
                            <tr>
                                <td>
                                    <span class="cell-title"><?= e($insumo['nombre']) ?></span>
                                    <span class="cell-sub"><?= e($insumo['descripcion'] ?: 'Sin descripción') ?></span>
                                </td>
                                <td><span class="chip"><i class="bi <?= (int) $insumo['perecedero'] === 1 ? 'bi-snow' : 'bi-box' ?>"></i><?= e($insumo['categoria']) ?></span></td>
                                <td class="num"><strong><?= e(formatear_cantidad($insumo['cantidad_actual'])) ?> <?= e($insumo['unidad_medida']) ?></strong></td>
                                <td class="num text-muted-app"><?= e(formatear_cantidad($insumo['stock_minimo'])) ?> <?= e($insumo['unidad_medida']) ?></td>
                                <td><span class="badge-soft bg-<?= e($clase) ?>-soft"><i class="bi <?= e($icono) ?>"></i><?= e($texto) ?></span></td>
                                <td class="num"><strong class="text-gold"><?= e(formatear_cantidad($sugerido)) ?> <?= e($insumo['unidad_medida']) ?></strong></td>
                                <td class="cell-actions">
                                    <?php if ($esGerente): ?>
                                        <a class="btn btn-success-soft btn-sm" href="<?= e(base_url('entradas/registrar.php?insumo=' . (int) $insumo['id_insumo'])) ?>">
                                            <i class="bi bi-box-arrow-in-down"></i> Reabastecer
                                        </a>
                                    <?php else: ?>
                                        <a class="btn btn-soft btn-sm" href="<?= e(base_url('insumos/index.php?q=' . urlencode($insumo['nombre']))) ?>">
                                            <i class="bi bi-eye"></i> Consultar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-header">
        <div>
            <h2 class="panel-title"><i class="bi bi-clock-history"></i> Insumos próximos a caducar</h2>
            <p class="panel-subtitle">Lotes con existencia que vencen en el periodo seleccionado.</p>
        </div>
        <div class="toolbar-tabs">
            <?php foreach ([7 => '7 días', 15 => '15 días', 30 => '30 días'] as $dias => $etiqueta): ?>
                <a class="toolbar-tab <?= $diasVencimiento === $dias ? 'active' : '' ?>"
                   href="<?= e(base_url('alertas/index.php?dias=' . $dias)) ?>"><?= e($etiqueta) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="panel-body panel-body-flush">
        <?php if (!$porVencer): ?>
            <div class="table-empty"><i class="bi bi-calendar-check"></i><strong>Sin vencimientos próximos</strong><span>No hay lotes que caduquen en los próximos <?= $diasVencimiento ?> días.</span></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-app">
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Lote</th>
                            <th class="num">Cantidad</th>
                            <th>Caducidad</th>
                            <th>Días restantes</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($porVencer as $lote): ?>
                            <?php
                            $dias = dias_para_caducar($lote['fecha_caducidad']);
                            $claseDias = $dias !== null && $dias <= 2 ? 'danger' : 'warning';
                            ?>
                            <tr>
                                <td>
                                    <span class="cell-title"><?= e($lote['insumo']) ?></span>
                                    <span class="cell-sub"><?= e($lote['categoria']) ?></span>
                                </td>
                                <td><span class="chip"><i class="bi bi-upc-scan"></i><?= e($lote['numero_lote']) ?></span></td>
                                <td class="num"><strong><?= e(formatear_cantidad($lote['cantidad_actual'])) ?> <?= e($lote['unidad_medida']) ?></strong></td>
                                <td><?= e(formatear_fecha($lote['fecha_caducidad'])) ?></td>
                                <td><span class="badge-soft bg-<?= e($claseDias) ?>-soft"><i class="bi bi-clock"></i><?= (int) $dias ?> día(s)</span></td>
                                <td class="cell-actions">
                                    <a class="btn btn-danger-soft btn-sm" href="<?= e(base_url('mermas/registrar.php?insumo=' . (int) $lote['id_insumo'])) ?>">
                                        <i class="bi bi-trash3"></i> Registrar merma
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2 class="panel-title"><i class="bi bi-x-octagon"></i> Insumos caducados</h2>
            <p class="panel-subtitle">Lotes vencidos que todavía tienen existencia. Deben retirarse del inventario.</p>
        </div>
        <span class="badge-soft bg-danger-soft"><i class="bi bi-exclamation-octagon"></i><?= count($caducados) ?> lote(s)</span>
    </div>
    <div class="panel-body panel-body-flush">
        <?php if (!$caducados): ?>
            <div class="table-empty"><i class="bi bi-check2-circle"></i><strong>Sin producto caducado</strong><span>Ningún lote vencido conserva existencia.</span></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-app">
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Lote</th>
                            <th class="num">Cantidad</th>
                            <th>Vencio el</th>
                            <th>Días vencido</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($caducados as $lote): ?>
                            <tr>
                                <td>
                                    <span class="cell-title"><?= e($lote['insumo']) ?></span>
                                    <span class="cell-sub"><?= e($lote['categoria']) ?></span>
                                </td>
                                <td><span class="chip"><i class="bi bi-upc-scan"></i><?= e($lote['numero_lote']) ?></span></td>
                                <td class="num"><strong class="text-danger"><?= e(formatear_cantidad($lote['cantidad_actual'])) ?> <?= e($lote['unidad_medida']) ?></strong></td>
                                <td><?= e(formatear_fecha($lote['fecha_caducidad'])) ?></td>
                                <td><span class="badge-soft bg-danger-soft"><i class="bi bi-calendar-x"></i><?= (int) $lote['dias_caducado'] ?> día(s)</span></td>
                                <td class="cell-actions">
                                    <a class="btn btn-danger-soft btn-sm" href="<?= e(base_url('mermas/registrar.php?insumo=' . (int) $lote['id_insumo'])) ?>">
                                        <i class="bi bi-trash3"></i> Dar de baja
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

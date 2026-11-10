<?php
/**
 * Reportes del sistema (solo gerente).
 * Vista con filtros, resumen, impresión y exportación CSV.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);
require_once __DIR__ . '/_reportes.php';

$pdo = db();
$filtros = reportes_filtros_normalizados($_GET);
$reporte = construir_reporte($pdo, $filtros);
$opciones = reportes_opciones($pdo);
$disponibles = reportes_disponibles();
$definicion = $disponibles[$filtros['tipo']];

$parametrosExportacion = [
    'tipo'         => $filtros['tipo'],
    'desde'        => $filtros['desde'],
    'hasta'        => $filtros['hasta'],
    'estado'       => $filtros['estado'],
    'categoria'    => $filtros['categoria'],
    'insumo'       => $filtros['insumo'],
    'producto'     => $filtros['producto'],
    'mov'          => $filtros['mov'],
    'mov_producto' => $filtros['mov_producto'],
    'motivo'       => $filtros['motivo'],
    'dias'         => $filtros['dias'],
];
$urlExportar = base_url('reportes/exportar.php?' . http_build_query($parametrosExportacion));

$pageTitle = 'Reportes';
$pageSubtitle = 'Consulta, imprime y descarga la información del almacén.';
$pageIcon = 'bi-file-earmark-bar-graph';
$pageActions = '<a class="btn btn-outline-coffee" href="' . e($urlExportar) . '"><i class="bi bi-download me-1"></i>Descargar CSV</a>'
    . '<button type="button" class="btn btn-gold" data-print><i class="bi bi-printer me-1"></i>Imprimir</button>';

require __DIR__ . '/../includes/header.php';
?>

<div class="report-tabs mb-3 no-print">
    <?php foreach ($disponibles as $clave => $opcion): ?>
        <a class="report-tab <?= $filtros['tipo'] === $clave ? 'active' : '' ?>"
           href="<?= e(base_url('reportes/index.php?tipo=' . $clave)) ?>">
            <i class="bi <?= e($opcion['icono']) ?>"></i><?= e($opcion['titulo']) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="panel mb-4 no-print">
    <div class="panel-body">
        <form method="get" action="<?= e(base_url('reportes/index.php')) ?>" class="filter-bar">
            <input type="hidden" name="tipo" value="<?= e($filtros['tipo']) ?>">

            <?php if ($definicion['fechas']): ?>
                <div class="form-group">
                    <label class="form-label" for="desde">Desde</label>
                    <input type="date" class="form-control" id="desde" name="desde" value="<?= e($filtros['desde']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="hasta">Hasta</label>
                    <input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($filtros['hasta']) ?>">
                </div>
            <?php endif; ?>

            <?php if (in_array('estado', $definicion['filtros'], true)): ?>
                <div class="form-group">
                    <label class="form-label" for="estado">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="activos" <?= $filtros['estado'] === 'activos' ? 'selected' : '' ?>>Activos</option>
                        <option value="disponibles" <?= $filtros['estado'] === 'disponibles' ? 'selected' : '' ?>>Disponibles</option>
                        <option value="bajo" <?= $filtros['estado'] === 'bajo' ? 'selected' : '' ?>>En mínimo o bajo</option>
                        <option value="agotados" <?= $filtros['estado'] === 'agotados' ? 'selected' : '' ?>>Agotados</option>
                        <option value="inactivos" <?= $filtros['estado'] === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                        <option value="todos" <?= $filtros['estado'] === 'todos' ? 'selected' : '' ?>>Todos</option>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array('mov', $definicion['filtros'], true)): ?>
                <div class="form-group">
                    <label class="form-label" for="mov">Tipo de movimiento</label>
                    <select class="form-select" id="mov" name="mov">
                        <option value="">Todos</option>
                        <?php foreach (['Entrada', 'Salida', 'Merma', 'Ajuste'] as $tipoMovimiento): ?>
                            <option value="<?= e($tipoMovimiento) ?>" <?= $filtros['mov'] === $tipoMovimiento ? 'selected' : '' ?>>
                                <?= e($tipoMovimiento) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array('mov_producto', $definicion['filtros'], true)): ?>
                <div class="form-group">
                    <label class="form-label" for="mov_producto">Tipo de movimiento</label>
                    <select class="form-select" id="mov_producto" name="mov_producto">
                        <option value="">Todos</option>
                        <?php foreach (['Producción', 'Venta', 'Ajuste'] as $tipoProducto): ?>
                            <option value="<?= e($tipoProducto) ?>" <?= $filtros['mov_producto'] === $tipoProducto ? 'selected' : '' ?>>
                                <?= e($tipoProducto) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array('motivo', $definicion['filtros'], true)): ?>
                <div class="form-group">
                    <label class="form-label" for="motivo">Motivo</label>
                    <select class="form-select" id="motivo" name="motivo">
                        <option value="">Todos</option>
                        <?php foreach ($opciones['motivos'] as $clave => $etiqueta): ?>
                            <option value="<?= e($clave) ?>" <?= $filtros['motivo'] === $clave ? 'selected' : '' ?>>
                                <?= e($etiqueta) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array('dias', $definicion['filtros'], true)): ?>
                <div class="form-group">
                    <label class="form-label" for="dias">Vencimiento</label>
                    <select class="form-select" id="dias" name="dias">
                        <option value="0" <?= $filtros['dias'] === '0' ? 'selected' : '' ?>>Solo caducados</option>
                        <option value="7" <?= $filtros['dias'] === '7' ? 'selected' : '' ?>>Próximos 7 días</option>
                        <option value="15" <?= $filtros['dias'] === '15' ? 'selected' : '' ?>>Próximos 15 días</option>
                        <option value="30" <?= $filtros['dias'] === '30' ? 'selected' : '' ?>>Próximos 30 días</option>
                        <option value="60" <?= $filtros['dias'] === '60' ? 'selected' : '' ?>>Próximos 60 días</option>
                        <option value="90" <?= $filtros['dias'] === '90' ? 'selected' : '' ?>>Próximos 90 días</option>
                        <option value="365" <?= $filtros['dias'] === '365' ? 'selected' : '' ?>>Próximo año</option>
                        <option value="todos" <?= $filtros['dias'] === 'todos' ? 'selected' : '' ?>>Todos los lotes</option>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array('categoria', $definicion['filtros'], true)): ?>
                <div class="form-group">
                    <label class="form-label" for="categoria">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="0">Todas</option>
                        <?php foreach ($opciones['categorias'] as $categoria): ?>
                            <option value="<?= (int) $categoria['id_categoria'] ?>" <?= $filtros['categoria'] === (int) $categoria['id_categoria'] ? 'selected' : '' ?>>
                                <?= e($categoria['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array('insumo', $definicion['filtros'], true)): ?>
                <div class="form-group form-group-wide">
                    <label class="form-label" for="insumo">Insumo</label>
                    <select class="form-select" id="insumo" name="insumo">
                        <option value="0">Todos los insumos</option>
                        <?php foreach ($opciones['insumos'] as $insumo): ?>
                            <option value="<?= (int) $insumo['id_insumo'] ?>" <?= $filtros['insumo'] === (int) $insumo['id_insumo'] ? 'selected' : '' ?>>
                                <?= e($insumo['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array('producto', $definicion['filtros'], true)): ?>
                <div class="form-group form-group-wide">
                    <label class="form-label" for="producto">Producto</label>
                    <select class="form-select" id="producto" name="producto">
                        <option value="0">Todos los productos</option>
                        <?php foreach ($opciones['productos'] as $producto): ?>
                            <option value="<?= (int) $producto['id_producto'] ?>" <?= $filtros['producto'] === (int) $producto['id_producto'] ? 'selected' : '' ?>>
                                <?= e($producto['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="filter-actions">
                <button type="submit" class="btn btn-coffee"><i class="bi bi-funnel me-1"></i>Aplicar</button>
                <a href="<?= e(base_url('reportes/index.php?tipo=' . $filtros['tipo'])) ?>" class="btn btn-soft" title="Limpiar filtros">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2 class="panel-title"><i class="bi <?= e($reporte['icono']) ?>"></i> <?= e($reporte['titulo']) ?></h2>
            <p class="panel-subtitle"><?= e($reporte['descripcion']) ?> · <?= e($reporte['periodo']) ?></p>
        </div>
        <span class="chip"><i class="bi bi-table"></i> <?= count($reporte['filas']) ?> fila(s)</span>
    </div>

    <div class="report-print-head">
        <h1><?= e(APP_NOMBRE) ?></h1>
        <p><?= e(APP_LEMA) ?></p>
        <p><strong><?= e($reporte['titulo']) ?></strong> · <?= e($reporte['periodo']) ?></p>
        <p>Generado el <?= e(formatear_fecha_hora(date('Y-m-d H:i:s'))) ?> por <?= e(usuario_actual()['nombre_completo']) ?></p>
    </div>

    <div class="report-summary">
        <?php foreach ($reporte['resumen'] as $item): ?>
            <div class="report-summary-item">
                <span><?= e($item['etiqueta']) ?></span>
                <strong><?= e((string) $item['valor']) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="panel-body panel-body-flush">
        <?php if (!$reporte['filas']): ?>
            <div class="table-empty">
                <i class="bi <?= e($reporte['icono']) ?>"></i>
                <strong>Sin resultados para los filtros seleccionados</strong>
                <span>Ajusta el periodo o los filtros e inténtalo de nuevo.</span>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-app align-middle">
                    <thead>
                        <tr>
                            <?php foreach ($reporte['columnas'] as $columna): ?>
                                <th class="<?= e($columna['clase'] ?? '') ?>"><?= e($columna['etiqueta']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reporte['filas'] as $fila): ?>
                            <tr>
                                <?php foreach ($reporte['columnas'] as $columna): ?>
                                    <?php $valor = $fila[$columna['clave']] ?? ''; ?>
                                    <td class="<?= e($columna['clase'] ?? '') ?>">
                                        <?php if ($columna['tipo'] === 'estado_stock'): ?>
                                            <?php [$claseEstado, $textoEstado, $iconoEstado] = estado_stock((float) ($fila['cantidad_actual'] ?? 0), (float) ($fila['stock_minimo'] ?? 0)); ?>
                                            <span class="badge-soft bg-<?= e($claseEstado) ?>-soft"><i class="bi <?= e($iconoEstado) ?>"></i><?= e($textoEstado) ?></span>
                                        <?php elseif ($columna['tipo'] === 'dias_caducidad'): ?>
                                            <?php
                                            $dias = (int) $valor;
                                            if ($dias < 0) {
                                                $claseDias = 'danger';
                                                $textoDias = 'Caducado hace ' . abs($dias) . ' día' . (abs($dias) === 1 ? '' : 's');
                                                $iconoDias = 'bi-x-octagon';
                                            } elseif ($dias === 0) {
                                                $claseDias = 'danger';
                                                $textoDias = 'Caduca hoy';
                                                $iconoDias = 'bi-exclamation-octagon';
                                            } elseif ($dias <= 7) {
                                                $claseDias = 'warning';
                                                $textoDias = 'Vence en ' . $dias . ' día' . ($dias === 1 ? '' : 's');
                                                $iconoDias = 'bi-clock-history';
                                            } elseif ($dias <= 30) {
                                                $claseDias = 'info';
                                                $textoDias = 'Vence en ' . $dias . ' días';
                                                $iconoDias = 'bi-calendar-event';
                                            } else {
                                                $claseDias = 'success';
                                                $textoDias = 'Vence en ' . $dias . ' días';
                                                $iconoDias = 'bi-check-circle';
                                            }
                                            ?>
                                            <span class="badge-soft bg-<?= e($claseDias) ?>-soft"><i class="bi <?= e($iconoDias) ?>"></i><?= e($textoDias) ?></span>
                                        <?php elseif ($columna['tipo'] === 'movimiento'): ?>
                                            <?php
                                            [$claseMov, $iconoMov] = match ((string) $valor) {
                                                'Entrada' => ['success', 'bi-box-arrow-in-down'],
                                                'Salida'  => ['info', 'bi-box-arrow-up'],
                                                'Merma'   => ['danger', 'bi-trash3'],
                                                default   => ['warning', 'bi-sliders'],
                                            };
                                            ?>
                                            <span class="badge-soft bg-<?= e($claseMov) ?>-soft"><i class="bi <?= e($iconoMov) ?>"></i><?= e($valor) ?></span>
                                        <?php elseif ($columna['tipo'] === 'merma'): ?>
                                            <span class="badge-soft bg-warning-soft"><i class="bi bi-exclamation-triangle"></i><?= e($valor) ?></span>
                                        <?php elseif ($columna['tipo'] === 'movimiento_producto'): ?>
                                            <?php
                                            [$claseProd, $iconoProd] = match ((string) $valor) {
                                                'Producción' => ['success', 'bi-plus-circle'],
                                                'Venta'      => ['info', 'bi-bag-check'],
                                                default      => ['warning', 'bi-sliders'],
                                            };
                                            ?>
                                            <span class="badge-soft bg-<?= e($claseProd) ?>-soft"><i class="bi <?= e($iconoProd) ?>"></i><?= e($valor) ?></span>
                                        <?php else: ?>
                                            <?= e(reporte_valor_texto($valor, $columna['tipo'])) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="panel mt-4 no-print">
    <div class="panel-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span class="small text-muted-app">
                <i class="bi bi-info-circle me-1"></i>
                El CSV conserva los filtros aplicados. La impresión usa el formato horizontal del sistema.
            </span>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-coffee btn-sm" href="<?= e($urlExportar) ?>"><i class="bi bi-download me-1"></i>Descargar CSV</a>
                <button type="button" class="btn btn-gold btn-sm" data-print><i class="bi bi-printer me-1"></i>Imprimir reporte</button>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

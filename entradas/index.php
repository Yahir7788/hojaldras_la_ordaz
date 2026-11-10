<?php
/**
 * Historial de entradas de almacén (solo gerente).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);

$pdo = db();

$desde = trim((string) get('desde', date('Y-m-d', strtotime('-30 days'))));
$hasta = trim((string) get('hasta', date('Y-m-d')));
$insumoFiltro = (int) get('insumo', 0);
$categoriaFiltro = (int) get('categoria', 0);

if (!validar_fecha($desde)) {
    $desde = date('Y-m-d', strtotime('-30 days'));
}
if (!validar_fecha($hasta)) {
    $hasta = date('Y-m-d');
}
if ($desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
}

$condiciones = ['e.fecha_ingreso BETWEEN :desde AND :hasta'];
$parametros = [':desde' => $desde, ':hasta' => $hasta];

if ($insumoFiltro > 0) {
    $condiciones[] = 'e.id_insumo = :insumo';
    $parametros[':insumo'] = $insumoFiltro;
}
if ($categoriaFiltro > 0) {
    $condiciones[] = 'i.id_categoria = :categoria';
    $parametros[':categoria'] = $categoriaFiltro;
}

$where = implode(' AND ', $condiciones);
$stmt = $pdo->prepare(
    "SELECT e.*, i.nombre AS insumo, i.id_categoria, c.nombre AS categoria, u.nombre_completo AS usuario
     FROM entradas e
     INNER JOIN insumos i ON i.id_insumo = e.id_insumo
     INNER JOIN categorias c ON c.id_categoria = i.id_categoria
     INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
     WHERE {$where}
     ORDER BY e.fecha_ingreso DESC, e.id_entrada DESC
     LIMIT 500"
);
$stmt->execute($parametros);
$entradas = $stmt->fetchAll();

$stats = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            COUNT(DISTINCT e.id_insumo) AS insumos,
            SUM(CASE WHEN e.fecha_caducidad IS NOT NULL THEN 1 ELSE 0 END) AS con_caducidad
     FROM entradas e
     INNER JOIN insumos i ON i.id_insumo = e.id_insumo
     WHERE {$where}"
);
$stats->execute($parametros);
$resumen = $stats->fetch() ?: ['total' => 0, 'insumos' => 0, 'con_caducidad' => 0];

$insumos = $pdo->query('SELECT id_insumo, nombre FROM insumos ORDER BY nombre')->fetchAll();
$categorias = obtener_categorias($pdo);

$pageTitle = 'Entradas de almacén';
$pageSubtitle = 'Compras y recepciones que incrementan el inventario.';
$pageIcon = 'bi-box-arrow-in-down';
$pageActions = '<a class="btn btn-gold" href="' . e(base_url('entradas/registrar.php')) . '"><i class="bi bi-plus-lg me-1"></i>Registrar entrada</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4">
        <div class="stat-card tone-coffee">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-box-arrow-in-down"></i></span></div>
            <p class="stat-value"><?= (int) $resumen['total'] ?></p>
            <div class="stat-label">Entradas en el periodo</div>
            <div class="stat-hint"><i class="bi bi-calendar3"></i><?= e(formatear_fecha($desde)) ?> al <?= e(formatear_fecha($hasta)) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="stat-card tone-success">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-box-seam"></i></span></div>
            <p class="stat-value"><?= (int) $resumen['insumos'] ?></p>
            <div class="stat-label">Insumos abastecidos</div>
            <div class="stat-hint"><i class="bi bi-arrow-up-circle"></i> Incrementaron existencias</div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="stat-card tone-info">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-calendar-event"></i></span></div>
            <p class="stat-value"><?= (int) $resumen['con_caducidad'] ?></p>
            <div class="stat-label">Lotes con caducidad</div>
            <div class="stat-hint"><i class="bi bi-clock-history"></i> Productos perecederos</div>
        </div>
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-body">
        <form method="get" action="<?= e(base_url('entradas/index.php')) ?>" class="filter-bar">
            <div class="form-group">
                <label class="form-label" for="desde">Desde</label>
                <input type="date" class="form-control" id="desde" name="desde" value="<?= e($desde) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="hasta">Hasta</label>
                <input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($hasta) ?>">
            </div>
            <div class="form-group form-group-wide">
                <label class="form-label" for="insumo">Insumo</label>
                <select class="form-select" id="insumo" name="insumo">
                    <option value="0">Todos los insumos</option>
                    <?php foreach ($insumos as $insumo): ?>
                        <option value="<?= (int) $insumo['id_insumo'] ?>" <?= $insumoFiltro === (int) $insumo['id_insumo'] ? 'selected' : '' ?>>
                            <?= e($insumo['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="categoria">Categoría</label>
                <select class="form-select" id="categoria" name="categoria">
                    <option value="0">Todas</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= (int) $categoria['id_categoria'] ?>" <?= $categoriaFiltro === (int) $categoria['id_categoria'] ? 'selected' : '' ?>>
                            <?= e($categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-coffee"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                <a href="<?= e(base_url('entradas/index.php')) ?>" class="btn btn-soft" title="Limpiar filtros"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2 class="panel-title"><i class="bi bi-list-ul"></i> Entradas registradas</h2>
            <p class="panel-subtitle"><?= count($entradas) ?> registro(s) · Las entradas no se eliminan para conservar la trazabilidad.</p>
        </div>
    </div>
    <div class="panel-body panel-body-flush">
        <?php if (!$entradas): ?>
            <div class="table-empty"><i class="bi bi-box-arrow-in-down"></i><strong>Sin entradas en el periodo</strong><span>Registra una compra o ajusta el rango de fechas.</span></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-app">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Insumo</th>
                            <th class="num">Cantidad</th>
                            <th>Lote</th>
                            <th>Caducidad</th>
                            <th>Responsable</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entradas as $entrada): ?>
                            <tr>
                                <td>
                                    <span class="cell-title"><?= e(formatear_fecha($entrada['fecha_ingreso'])) ?></span>
                                    <span class="cell-sub">#<?= (int) $entrada['id_entrada'] ?></span>
                                </td>
                                <td>
                                    <span class="cell-title"><?= e($entrada['insumo']) ?></span>
                                    <span class="cell-sub"><?= e($entrada['categoria']) ?></span>
                                </td>
                                <td class="num">
                                    <strong class="text-success">+<?= e(formatear_cantidad($entrada['cantidad'])) ?> <?= e($entrada['unidad_medida']) ?></strong>
                                </td>
                                <td><span class="chip"><i class="bi bi-upc-scan"></i><?= e($entrada['numero_lote']) ?></span></td>
                                <td>
                                    <?php if ($entrada['fecha_caducidad']): ?>
                                        <?php [$clase, $texto, $icono] = estado_vencimiento($entrada['fecha_caducidad']); ?>
                                        <span class="badge-soft bg-<?= e($clase) ?>-soft"><i class="bi <?= e($icono) ?>"></i><?= e(formatear_fecha($entrada['fecha_caducidad'])) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted-app small-70">No aplica</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($entrada['usuario']) ?></td>
                                <td><span class="small-75 text-muted-app"><?= e($entrada['observaciones'] ?: '—') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

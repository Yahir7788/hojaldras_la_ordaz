<?php
/**
 * Historial de salidas de almacén (gerente y cocinero).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente', 'cocinero']);

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

$condiciones = ['s.fecha BETWEEN :desde AND :hasta'];
$parametros = [':desde' => $desde, ':hasta' => $hasta];

if ($insumoFiltro > 0) {
    $condiciones[] = 's.id_insumo = :insumo';
    $parametros[':insumo'] = $insumoFiltro;
}
if ($categoriaFiltro > 0) {
    $condiciones[] = 'i.id_categoria = :categoria';
    $parametros[':categoria'] = $categoriaFiltro;
}

$where = implode(' AND ', $condiciones);
$stmt = $pdo->prepare(
    "SELECT s.*, i.nombre AS insumo, i.unidad_medida, c.nombre AS categoria, u.nombre_completo AS usuario
     FROM salidas s
     INNER JOIN insumos i ON i.id_insumo = s.id_insumo
     INNER JOIN categorias c ON c.id_categoria = i.id_categoria
     INNER JOIN usuarios u ON u.id_usuario = s.id_usuario
     WHERE {$where}
     ORDER BY s.fecha DESC, s.id_salida DESC
     LIMIT 500"
);
$stmt->execute($parametros);
$salidas = $stmt->fetchAll();

$stats = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            COUNT(DISTINCT s.id_insumo) AS insumos,
            COUNT(DISTINCT s.destino) AS destinos
     FROM salidas s
     INNER JOIN insumos i ON i.id_insumo = s.id_insumo
     WHERE {$where}"
);
$stats->execute($parametros);
$resumen = $stats->fetch() ?: ['total' => 0, 'insumos' => 0, 'destinos' => 0];

$insumos = $pdo->query('SELECT id_insumo, nombre FROM insumos ORDER BY nombre')->fetchAll();
$categorias = obtener_categorias($pdo);

$pageTitle = 'Salidas de almacén';
$pageSubtitle = 'Entregas de materia prima al área de cocina y producción.';
$pageIcon = 'bi-box-arrow-up';
$pageActions = '<a class="btn btn-gold" href="' . e(base_url('salidas/registrar.php')) . '"><i class="bi bi-plus-lg me-1"></i>Registrar salida</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4">
        <div class="stat-card tone-coffee">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-box-arrow-up"></i></span></div>
            <p class="stat-value"><?= (int) $resumen['total'] ?></p>
            <div class="stat-label">Salidas en el periodo</div>
            <div class="stat-hint"><i class="bi bi-calendar3"></i><?= e(formatear_fecha($desde)) ?> al <?= e(formatear_fecha($hasta)) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="stat-card tone-info">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-box-seam"></i></span></div>
            <p class="stat-value"><?= (int) $resumen['insumos'] ?></p>
            <div class="stat-label">Insumos utilizados</div>
            <div class="stat-hint"><i class="bi bi-arrow-down-circle"></i> Con consumo registrado</div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="stat-card tone-success">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-signpost-split"></i></span></div>
            <p class="stat-value"><?= (int) $resumen['destinos'] ?></p>
            <div class="stat-label">Destinos atendidos</div>
            <div class="stat-hint"><i class="bi bi-shop"></i> Áreas de producción</div>
        </div>
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-body">
        <form method="get" action="<?= e(base_url('salidas/index.php')) ?>" class="filter-bar">
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
                <a href="<?= e(base_url('salidas/index.php')) ?>" class="btn btn-soft" title="Limpiar filtros"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2 class="panel-title"><i class="bi bi-list-ul"></i> Salidas registradas</h2>
            <p class="panel-subtitle"><?= count($salidas) ?> registro(s) · Cada salida descuenta existencias y consume lotes FEFO.</p>
        </div>
    </div>
    <div class="panel-body panel-body-flush">
        <?php if (!$salidas): ?>
            <div class="table-empty"><i class="bi bi-box-arrow-up"></i><strong>Sin salidas en el periodo</strong><span>Registra una entrega a cocina o ajusta el rango de fechas.</span></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-app">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Insumo</th>
                            <th class="num">Cantidad</th>
                            <th>Destino</th>
                            <th>Responsable</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salidas as $salida): ?>
                            <tr>
                                <td>
                                    <span class="cell-title"><?= e(formatear_fecha($salida['fecha'])) ?></span>
                                    <span class="cell-sub">#<?= (int) $salida['id_salida'] ?></span>
                                </td>
                                <td>
                                    <span class="cell-title"><?= e($salida['insumo']) ?></span>
                                    <span class="cell-sub"><?= e($salida['categoria']) ?></span>
                                </td>
                                <td class="num"><strong class="text-coffee">−<?= e(formatear_cantidad($salida['cantidad'])) ?> <?= e($salida['unidad_medida']) ?></strong></td>
                                <td><span class="chip"><i class="bi bi-signpost"></i><?= e($salida['destino']) ?></span></td>
                                <td><?= e($salida['usuario']) ?></td>
                                <td><span class="small-75 text-muted-app"><?= e($salida['observaciones'] ?: '—') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

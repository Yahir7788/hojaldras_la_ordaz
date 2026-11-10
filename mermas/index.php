<?php
/**
 * Historial de mermas (gerente y cocinero).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente', 'cocinero']);

$pdo = db();

$desde = trim((string) get('desde', date('Y-m-d', strtotime('-30 days'))));
$hasta = trim((string) get('hasta', date('Y-m-d')));
$insumoFiltro = (int) get('insumo', 0);
$categoriaFiltro = (int) get('categoria', 0);
$motivoFiltro = trim((string) get('motivo', ''));

if (!validar_fecha($desde)) {
    $desde = date('Y-m-d', strtotime('-30 days'));
}
if (!validar_fecha($hasta)) {
    $hasta = date('Y-m-d');
}
if ($desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
}

$motivos = motivos_merma();
if ($motivoFiltro !== '' && !array_key_exists($motivoFiltro, $motivos)) {
    $motivoFiltro = '';
}

$condiciones = ['m.fecha BETWEEN :desde AND :hasta'];
$parametros = [':desde' => $desde, ':hasta' => $hasta];

if ($insumoFiltro > 0) {
    $condiciones[] = 'm.id_insumo = :insumo';
    $parametros[':insumo'] = $insumoFiltro;
}
if ($categoriaFiltro > 0) {
    $condiciones[] = 'i.id_categoria = :categoria';
    $parametros[':categoria'] = $categoriaFiltro;
}
if ($motivoFiltro !== '') {
    $condiciones[] = 'm.motivo = :motivo';
    $parametros[':motivo'] = $motivoFiltro;
}

$where = implode(' AND ', $condiciones);
$stmt = $pdo->prepare(
    "SELECT m.*, i.nombre AS insumo, i.unidad_medida, c.nombre AS categoria, u.nombre_completo AS usuario
     FROM mermas m
     INNER JOIN insumos i ON i.id_insumo = m.id_insumo
     INNER JOIN categorias c ON c.id_categoria = i.id_categoria
     INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
     WHERE {$where}
     ORDER BY m.fecha DESC, m.id_merma DESC
     LIMIT 500"
);
$stmt->execute($parametros);
$mermas = $stmt->fetchAll();

$stats = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            COUNT(DISTINCT m.id_insumo) AS insumos,
            COUNT(DISTINCT m.motivo) AS motivos
     FROM mermas m
     INNER JOIN insumos i ON i.id_insumo = m.id_insumo
     WHERE {$where}"
);
$stats->execute($parametros);
$resumen = $stats->fetch() ?: ['total' => 0, 'insumos' => 0, 'motivos' => 0];

$insumos = $pdo->query('SELECT id_insumo, nombre FROM insumos ORDER BY nombre')->fetchAll();
$categorias = obtener_categorias($pdo);

$pageTitle = 'Control de mermas';
$pageSubtitle = 'Pérdidas por caducidad, deterioro, desperdicio o producto dañado.';
$pageIcon = 'bi-trash3';
$pageActions = '<a class="btn btn-gold" href="' . e(base_url('mermas/registrar.php')) . '"><i class="bi bi-plus-lg me-1"></i>Registrar merma</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4">
        <div class="stat-card tone-danger">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-trash3"></i></span></div>
            <p class="stat-value"><?= (int) $resumen['total'] ?></p>
            <div class="stat-label">Mermas en el periodo</div>
            <div class="stat-hint"><i class="bi bi-calendar3"></i><?= e(formatear_fecha($desde)) ?> al <?= e(formatear_fecha($hasta)) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="stat-card tone-warning">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-box-seam"></i></span></div>
            <p class="stat-value"><?= (int) $resumen['insumos'] ?></p>
            <div class="stat-label">Insumos afectados</div>
            <div class="stat-hint"><i class="bi bi-exclamation-triangle"></i> Producto perdido</div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="stat-card tone-info">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-tags"></i></span></div>
            <p class="stat-value"><?= (int) $resumen['motivos'] ?></p>
            <div class="stat-label">Motivos registrados</div>
            <div class="stat-hint"><i class="bi bi-clipboard-data"></i> Análisis de pérdidas</div>
        </div>
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-body">
        <form method="get" action="<?= e(base_url('mermas/index.php')) ?>" class="filter-bar">
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
            <div class="form-group">
                <label class="form-label" for="motivo">Motivo</label>
                <select class="form-select" id="motivo" name="motivo">
                    <option value="">Todos</option>
                    <?php foreach ($motivos as $clave => $etiqueta): ?>
                        <option value="<?= e($clave) ?>" <?= $motivoFiltro === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-coffee"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                <a href="<?= e(base_url('mermas/index.php')) ?>" class="btn btn-soft" title="Limpiar filtros"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2 class="panel-title"><i class="bi bi-list-ul"></i> Mermas registradas</h2>
            <p class="panel-subtitle"><?= count($mermas) ?> registro(s) · Cada merma ya fue descontada del inventario.</p>
        </div>
    </div>
    <div class="panel-body panel-body-flush">
        <?php if (!$mermas): ?>
            <div class="table-empty"><i class="bi bi-trash3"></i><strong>Sin mermas en el periodo</strong><span>No se han registrado pérdidas con los filtros actuales.</span></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-app">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Insumo</th>
                            <th class="num">Cantidad</th>
                            <th>Motivo</th>
                            <th>Descripción</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mermas as $merma): ?>
                            <?php
                            $colorMotivo = match ($merma['motivo']) {
                                'Caducidad'                 => 'danger',
                                'Deterioro'                 => 'warning',
                                'Desperdicio en producción' => 'info',
                                default                     => 'neutral',
                            };
                            ?>
                            <tr>
                                <td>
                                    <span class="cell-title"><?= e(formatear_fecha($merma['fecha'])) ?></span>
                                    <span class="cell-sub">#<?= (int) $merma['id_merma'] ?></span>
                                </td>
                                <td>
                                    <span class="cell-title"><?= e($merma['insumo']) ?></span>
                                    <span class="cell-sub"><?= e($merma['categoria']) ?></span>
                                </td>
                                <td class="num"><strong class="text-danger">−<?= e(formatear_cantidad($merma['cantidad'])) ?> <?= e($merma['unidad_medida']) ?></strong></td>
                                <td><span class="badge-soft bg-<?= e($colorMotivo) ?>-soft"><?= e($merma['motivo']) ?></span></td>
                                <td><span class="small-75 text-muted-app"><?= e($merma['descripcion']) ?></span></td>
                                <td><?= e($merma['usuario']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

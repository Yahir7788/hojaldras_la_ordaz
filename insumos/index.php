<?php
/**
 * Catálogo de insumos.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente', 'cocinero']);

$pdo = db();
$esGerente = es_gerente();

$busqueda = trim((string) get('q'));
$categoriaFiltro = (int) get('categoria', 0);
$estadoFiltro = (string) get('estado', 'activos');
$estadosValidos = ['activos', 'disponibles', 'bajo', 'agotados', 'inactivos', 'todos'];
if (!in_array($estadoFiltro, $estadosValidos, true)) {
    $estadoFiltro = 'activos';
}

$condiciones = [];
$parametros = [];

switch ($estadoFiltro) {
    case 'activos':
        $condiciones[] = 'i.activo = 1';
        break;
    case 'inactivos':
        $condiciones[] = 'i.activo = 0';
        break;
    case 'disponibles':
        $condiciones[] = 'i.activo = 1 AND i.cantidad_actual > i.stock_minimo';
        break;
    case 'bajo':
        $condiciones[] = 'i.activo = 1 AND i.cantidad_actual <= i.stock_minimo';
        break;
    case 'agotados':
        $condiciones[] = 'i.activo = 1 AND i.cantidad_actual <= 0';
        break;
}

if ($busqueda !== '') {
    $condiciones[] = '(i.nombre LIKE :busqueda OR i.descripcion LIKE :busqueda)';
    $parametros[':busqueda'] = '%' . $busqueda . '%';
}
if ($categoriaFiltro > 0) {
    $condiciones[] = 'i.id_categoria = :categoria';
    $parametros[':categoria'] = $categoriaFiltro;
}

$where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

$sql = "SELECT i.*, c.nombre AS categoria, c.perecedero,
               (SELECT MIN(l.fecha_caducidad)
                  FROM lotes l
                 WHERE l.id_insumo = i.id_insumo AND l.cantidad_actual > 0
                   AND l.fecha_caducidad IS NOT NULL) AS proxima_caducidad
        FROM insumos i
        INNER JOIN categorias c ON c.id_categoria = i.id_categoria
        {$where}
        ORDER BY i.activo DESC, i.nombre ASC
        LIMIT 300";
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$insumos = $stmt->fetchAll();

$categorias = obtener_categorias($pdo);

$conteos = $pdo->query(
    "SELECT
        SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS activos,
        SUM(CASE WHEN activo = 1 AND cantidad_actual > stock_minimo THEN 1 ELSE 0 END) AS disponibles,
        SUM(CASE WHEN activo = 1 AND cantidad_actual <= stock_minimo THEN 1 ELSE 0 END) AS bajo,
        SUM(CASE WHEN activo = 1 AND cantidad_actual <= 0 THEN 1 ELSE 0 END) AS agotados,
        SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) AS inactivos
     FROM insumos"
)->fetch();

$pageTitle = 'Catálogo de insumos';
$pageSubtitle = 'Materias primas y materiales registrados en el almacén.';
$pageIcon = 'bi-box-seam';

$pageActions = '';
if ($esGerente) {
    $pageActions = '<a class="btn btn-outline-coffee" href="' . e(base_url('insumos/ajustar.php')) . '">'
        . '<i class="bi bi-sliders me-1"></i>Ajustar stock</a>'
        . '<a class="btn btn-gold" href="' . e(base_url('insumos/registrar.php')) . '">'
        . '<i class="bi bi-plus-lg me-1"></i>Nuevo insumo</a>';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg">
        <div class="stat-card tone-coffee">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-box-seam"></i></span></div>
            <p class="stat-value"><?= (int) $conteos['activos'] ?></p>
            <div class="stat-label">Insumos activos</div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="stat-card tone-success">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-check2-circle"></i></span></div>
            <p class="stat-value"><?= (int) $conteos['disponibles'] ?></p>
            <div class="stat-label">Disponibles</div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="stat-card tone-warning">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-exclamation-triangle"></i></span></div>
            <p class="stat-value"><?= (int) $conteos['bajo'] ?></p>
            <div class="stat-label">En mínimo o bajo</div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="stat-card tone-danger">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-x-octagon"></i></span></div>
            <p class="stat-value"><?= (int) $conteos['agotados'] ?></p>
            <div class="stat-label">Agotados</div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="stat-card tone-info">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-archive"></i></span></div>
            <p class="stat-value"><?= (int) $conteos['inactivos'] ?></p>
            <div class="stat-label">Inactivos</div>
        </div>
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-body">
        <form method="get" action="<?= e(base_url('insumos/index.php')) ?>" class="filter-bar">
            <div class="form-group form-group-wide">
                <label class="form-label" for="q">Buscar insumo</label>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" id="q" name="q" value="<?= e($busqueda) ?>"
                           placeholder="Nombre o descripción…">
                </div>
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
                <label class="form-label" for="estado">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="activos" <?= $estadoFiltro === 'activos' ? 'selected' : '' ?>>Activos</option>
                    <option value="disponibles" <?= $estadoFiltro === 'disponibles' ? 'selected' : '' ?>>Disponibles</option>
                    <option value="bajo" <?= $estadoFiltro === 'bajo' ? 'selected' : '' ?>>Stock bajo</option>
                    <option value="agotados" <?= $estadoFiltro === 'agotados' ? 'selected' : '' ?>>Agotados</option>
                    <option value="inactivos" <?= $estadoFiltro === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                    <option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-coffee"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                <a href="<?= e(base_url('insumos/index.php')) ?>" class="btn btn-soft" title="Limpiar filtros"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2 class="panel-title"><i class="bi bi-list-ul"></i> Insumos registrados</h2>
            <p class="panel-subtitle"><?= count($insumos) ?> resultado(s) · El stock se actualiza con cada movimiento.</p>
        </div>
        <span class="chip"><i class="bi bi-info-circle"></i> Semáforo: verde disponible · amarillo mínimo · rojo crítico</span>
    </div>
    <div class="panel-body panel-body-flush">
        <?php if (!$insumos): ?>
            <div class="table-empty">
                <i class="bi bi-box-seam"></i>
                <strong>No se encontraron insumos</strong>
                <span>Cambia los filtros o registra un nuevo insumo.</span>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-app align-middle">
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Categoría</th>
                            <th class="num">Stock actual</th>
                            <th class="num">Stock mínimo</th>
                            <th>Estado</th>
                            <th>Próxima caducidad</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($insumos as $insumo): ?>
                            <?php
                            [$clase, $texto, $icono] = estado_stock($insumo['cantidad_actual'], $insumo['stock_minimo']);
                            $stock = (float) $insumo['cantidad_actual'];
                            $minimo = (float) $insumo['stock_minimo'];
                            $porcentaje = $minimo > 0 ? min(100, ($stock / ($minimo * 2)) * 100) : ($stock > 0 ? 100 : 0);
                            $claseBarra = $stock <= 0 ? 'is-danger' : ($stock <= $minimo ? 'is-warning' : '');
                            ?>
                            <tr class="<?= (int) $insumo['activo'] === 0 ? 'opacity-75' : '' ?>">
                                <td>
                                    <span class="cell-title"><?= e($insumo['nombre']) ?></span>
                                    <span class="cell-sub"><?= e($insumo['descripcion'] ?: 'Sin descripción') ?></span>
                                    <?php if ((int) $insumo['activo'] === 0): ?>
                                        <span class="badge-soft bg-neutral-soft ms-1"><i class="bi bi-archive"></i>Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="chip"><i class="bi <?= (int) $insumo['perecedero'] === 1 ? 'bi-snow' : 'bi-box' ?>"></i><?= e($insumo['categoria']) ?></span>
                                </td>
                                <td class="num">
                                    <strong><?= e(formatear_cantidad($stock)) ?> <?= e($insumo['unidad_medida']) ?></strong>
                                    <div class="stock-bar <?= e($claseBarra) ?> ms-auto"><span style="width: <?= e((string) $porcentaje) ?>%"></span></div>
                                </td>
                                <td class="num text-muted-app"><?= e(formatear_cantidad($minimo)) ?> <?= e($insumo['unidad_medida']) ?></td>
                                <td>
                                    <span class="badge-soft bg-<?= e($clase) ?>-soft"><i class="bi <?= e($icono) ?>"></i><?= e($texto) ?></span>
                                </td>
                                <td>
                                    <?php if ($insumo['proxima_caducidad']): ?>
                                        <?php [$claseVence, $textoVence, $iconoVence] = estado_vencimiento($insumo['proxima_caducidad']); ?>
                                        <span class="badge-soft bg-<?= e($claseVence) ?>-soft"><i class="bi <?= e($iconoVence) ?>"></i><?= e($textoVence) ?></span>
                                        <span class="cell-sub"><?= e(formatear_fecha($insumo['proxima_caducidad'])) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted-app small-70">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-actions">
                                    <?php if ($esGerente): ?>
                                        <?php if ((int) $insumo['activo'] === 1): ?>
                                            <a class="btn btn-soft btn-action" href="<?= e(base_url('insumos/ajustar.php?id=' . (int) $insumo['id_insumo'])) ?>"
                                               data-bs-toggle="tooltip" title="Ajustar stock"><i class="bi bi-sliders"></i></a>
                                            <a class="btn btn-soft btn-action" href="<?= e(base_url('insumos/editar.php?id=' . (int) $insumo['id_insumo'])) ?>"
                                               data-bs-toggle="tooltip" title="Editar"><i class="bi bi-pencil"></i></a>
                                            <form method="post" action="<?= e(base_url('insumos/eliminar.php')) ?>" class="d-inline"
                                                  data-confirm="El insumo «<?= e($insumo['nombre']) ?>» quedará inactivo. Su historial de movimientos se conservará. ¿Deseas continuar?"
                                                  data-confirm-title="Eliminar insumo">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $insumo['id_insumo'] ?>">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <button type="submit" class="btn btn-danger-soft btn-action" data-bs-toggle="tooltip" title="Eliminar">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="<?= e(base_url('insumos/eliminar.php')) ?>" class="d-inline"
                                                  data-confirm="El insumo «<?= e($insumo['nombre']) ?>» volverá a estar activo. ¿Deseas continuar?"
                                                  data-confirm-title="Reactivar insumo"
                                                  data-confirm-button="btn-success"
                                                  data-confirm-accept="Sí, reactivar">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $insumo['id_insumo'] ?>">
                                                <input type="hidden" name="accion" value="reactivar">
                                                <button type="submit" class="btn btn-success-soft btn-action" data-bs-toggle="tooltip" title="Reactivar">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted-app small-70">Solo consulta</span>
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

<?php require __DIR__ . '/../includes/footer.php'; ?>

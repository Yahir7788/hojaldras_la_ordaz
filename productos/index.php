<?php
/**
 * Control de producto terminado: hojaldres, volovanes y pizzas.
 * Gerente y personal de ventas pueden consultar y mover existencias.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente', 'ventas']);

$pdo = db();
$esGerente = es_gerente();

// Enlaces rápidos del dashboard: ?accion=produccion&producto=ID
$accionRapida = (string) get('accion', '');
$productoRapido = (int) get('producto', 0);
if (in_array($accionRapida, ['produccion', 'venta', 'ajuste'], true) && $productoRapido > 0) {
    redirigir('productos/movimiento.php?accion=' . $accionRapida . '&producto=' . $productoRapido);
}

$busqueda = trim((string) get('q', ''));
$estadoFiltro = (string) get('estado', 'activos');
if (!in_array($estadoFiltro, ['activos', 'disponibles', 'bajo', 'agotados', 'inactivos', 'todos'], true)) {
    $estadoFiltro = 'activos';
}

$condiciones = [];
$parametros = [];

switch ($estadoFiltro) {
    case 'activos':
        $condiciones[] = 'activo = 1';
        break;
    case 'inactivos':
        $condiciones[] = 'activo = 0';
        break;
    case 'disponibles':
        $condiciones[] = 'activo = 1 AND stock_actual > stock_minimo';
        break;
    case 'bajo':
        $condiciones[] = 'activo = 1 AND stock_actual <= stock_minimo';
        break;
    case 'agotados':
        $condiciones[] = 'activo = 1 AND stock_actual <= 0';
        break;
}

if ($busqueda !== '') {
    $condiciones[] = '(nombre LIKE :busqueda OR descripcion LIKE :busqueda)';
    $parametros[':busqueda'] = '%' . $busqueda . '%';
}

$where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

$stmt = $pdo->prepare("SELECT * FROM productos {$where} ORDER BY activo DESC, nombre ASC LIMIT 200");
$stmt->execute($parametros);
$productos = $stmt->fetchAll();

$conteos = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS activos,
            SUM(CASE WHEN activo = 1 AND stock_actual > 0 THEN stock_actual ELSE 0 END) AS unidades,
            SUM(CASE WHEN activo = 1 AND stock_actual <= stock_minimo THEN 1 ELSE 0 END) AS bajos,
            SUM(CASE WHEN activo = 1 THEN stock_actual * precio ELSE 0 END) AS valor,
            SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) AS inactivos
     FROM productos"
)->fetch();

$movimientos = $pdo->query(
    'SELECT mp.*, p.nombre AS producto, u.nombre_completo AS usuario
     FROM movimientos_producto mp
     INNER JOIN productos p ON p.id_producto = mp.id_producto
     INNER JOIN usuarios u ON u.id_usuario = mp.id_usuario
     ORDER BY mp.fecha_movimiento DESC, mp.id_movimiento_producto DESC
     LIMIT 12'
)->fetchAll();

$pageTitle = 'Producto terminado';
$pageSubtitle = 'Disponibilidad de hojaldres, volovanes y pizzas listos para venta.';
$pageIcon = 'bi-basket2';

$acciones = [];
if ($esGerente) {
    $acciones[] = '<a class="btn btn-gold" href="' . e(base_url('productos/registrar.php')) . '"><i class="bi bi-plus-lg me-1"></i>Nuevo producto</a>';
}
$acciones[] = '<a class="btn btn-outline-coffee" href="#historialProducto"><i class="bi bi-clock-history me-1"></i>Historial</a>';
$pageActions = implode('', $acciones);

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-coffee">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-box2-heart"></i></span></div>
            <p class="stat-value"><?= (int) $conteos['activos'] ?></p>
            <div class="stat-label">Productos activos</div>
            <div class="stat-hint"><i class="bi bi-tags"></i> Catálogo de venta</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-success">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-basket"></i></span></div>
            <p class="stat-value"><?= number_format((int) $conteos['unidades']) ?></p>
            <div class="stat-label">Unidades disponibles</div>
            <div class="stat-hint"><i class="bi bi-check2-circle"></i> Listas para mostrador</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-<?= (int) $conteos['bajos'] > 0 ? 'warning' : 'success' ?>">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-exclamation-triangle"></i></span></div>
            <p class="stat-value"><?= (int) $conteos['bajos'] ?></p>
            <div class="stat-label">En mínimo o agotados</div>
            <div class="stat-hint"><i class="bi bi-arrow-repeat"></i> Requieren producción</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-gold">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-cash-coin"></i></span></div>
            <p class="stat-value"><?= e(formatear_moneda($conteos['valor'] ?? 0)) ?></p>
            <div class="stat-label">Valor del inventario</div>
            <div class="stat-hint"><i class="bi bi-graph-up-arrow"></i> Precio de venta actual</div>
        </div>
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-body">
        <form method="get" action="<?= e(base_url('productos/index.php')) ?>" class="filter-bar">
            <div class="form-group form-group-wide">
                <label class="form-label" for="q">Buscar producto</label>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" id="q" name="q" value="<?= e($busqueda) ?>"
                           placeholder="Nombre o descripción…">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="estado">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="activos" <?= $estadoFiltro === 'activos' ? 'selected' : '' ?>>Activos</option>
                    <option value="disponibles" <?= $estadoFiltro === 'disponibles' ? 'selected' : '' ?>>Disponibles</option>
                    <option value="bajo" <?= $estadoFiltro === 'bajo' ? 'selected' : '' ?>>En mínimo o bajo</option>
                    <option value="agotados" <?= $estadoFiltro === 'agotados' ? 'selected' : '' ?>>Agotados</option>
                    <option value="inactivos" <?= $estadoFiltro === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                    <option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-coffee"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                <a href="<?= e(base_url('productos/index.php')) ?>" class="btn btn-soft" title="Limpiar filtros"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<?php if (!$productos): ?>
    <div class="panel">
        <div class="table-empty">
            <i class="bi bi-basket2"></i>
            <strong>No hay productos con esos filtros</strong>
            <span>Cambia la búsqueda o registra un nuevo producto terminado.</span>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <?php foreach ($productos as $producto): ?>
            <?php
            $stock = (int) $producto['stock_actual'];
            $minimo = (int) $producto['stock_minimo'];
            [$claseEstado, $textoEstado, $iconoEstado] = estado_stock($stock, $minimo);
            $emoji = match (mb_strtolower($producto['nombre'], 'UTF-8')) {
                'pizza'    => '🍕',
                'volován'  => '🥐',
                'hojaldre' => '🥮',
                default    => '🥖',
            };
            $activo = (int) $producto['activo'] === 1;
            $porcentaje = min(100, $minimo > 0 ? ($stock / max($minimo * 2, 1)) * 100 : ($stock > 0 ? 100 : 0));
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="product-card <?= $activo ? '' : 'opacity-75' ?>">
                    <div class="product-card-top">
                        <span class="product-emoji"><?= $emoji ?></span>
                        <div class="text-end">
                            <span class="badge-soft bg-<?= e($claseEstado) ?>-soft"><i class="bi <?= e($iconoEstado) ?>"></i><?= e($textoEstado) ?></span>
                            <?php if (!$activo): ?>
                                <div class="mt-1"><span class="badge-soft bg-neutral-soft"><i class="bi bi-archive"></i>Inactivo</span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <h3><?= e($producto['nombre']) ?></h3>
                    <p><?= e($producto['descripcion'] ?: 'Sin descripción registrada.') ?></p>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="product-stock">
                            <strong><?= $stock ?></strong><span>unidades</span>
                        </div>
                        <span class="product-price"><?= e(formatear_moneda($producto['precio'])) ?></span>
                    </div>
                    <div class="stock-bar <?= $stock <= 0 ? 'is-danger' : ($stock <= $minimo ? 'is-warning' : '') ?>">
                        <span style="width: <?= e((string) $porcentaje) ?>%"></span>
                    </div>
                    <div class="small text-muted-app mt-2">
                        Stock mínimo: <strong><?= $minimo ?></strong> unidades
                    </div>
                    <div class="product-actions">
                        <?php if ($activo): ?>
                            <a class="btn btn-success-soft btn-sm" href="<?= e(base_url('productos/movimiento.php?accion=produccion&producto=' . (int) $producto['id_producto'])) ?>">
                                <i class="bi bi-plus-lg"></i> Producción
                            </a>
                            <a class="btn btn-soft btn-sm" href="<?= e(base_url('productos/movimiento.php?accion=venta&producto=' . (int) $producto['id_producto'])) ?>">
                                <i class="bi bi-bag-check"></i> Venta
                            </a>
                            <?php if ($esGerente): ?>
                                <a class="btn btn-outline-coffee btn-sm" href="<?= e(base_url('productos/movimiento.php?accion=ajuste&producto=' . (int) $producto['id_producto'])) ?>">
                                    <i class="bi bi-sliders"></i> Ajuste
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($esGerente): ?>
                        <div class="d-flex gap-2 mt-2">
                            <a class="btn btn-soft btn-sm flex-fill" href="<?= e(base_url('productos/editar.php?id=' . (int) $producto['id_producto'])) ?>">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <form method="post" action="<?= e(base_url('productos/eliminar.php')) ?>" class="flex-fill"
                                  data-confirm="<?= $activo
                                      ? 'El producto «' . e($producto['nombre']) . '» quedará inactivo. Su historial se conservará. ¿Deseas continuar?'
                                      : 'El producto «' . e($producto['nombre']) . '» volverá a estar disponible para venta. ¿Deseas continuar?' ?>"
                                  data-confirm-title="<?= $activo ? 'Desactivar producto' : 'Reactivar producto' ?>"
                                  data-confirm-button="<?= $activo ? 'btn-danger' : 'btn-success' ?>"
                                  data-confirm-accept="<?= $activo ? 'Sí, desactivar' : 'Sí, reactivar' ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $producto['id_producto'] ?>">
                                <input type="hidden" name="accion" value="<?= $activo ? 'desactivar' : 'reactivar' ?>">
                                <button type="submit" class="btn <?= $activo ? 'btn-danger-soft' : 'btn-success-soft' ?> btn-sm w-100">
                                    <i class="bi <?= $activo ? 'bi-archive' : 'bi-arrow-counterclockwise' ?>"></i>
                                    <?= $activo ? 'Desactivar' : 'Reactivar' ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="panel" id="historialProducto">
    <div class="panel-header">
        <div>
            <h2 class="panel-title"><i class="bi bi-clock-history"></i> Historial de movimientos</h2>
            <p class="panel-subtitle">Producción, ventas y ajustes de producto terminado.</p>
        </div>
        <span class="chip"><i class="bi bi-shield-check"></i> Cada movimiento guarda usuario y fecha</span>
    </div>
    <div class="panel-body panel-body-flush">
        <?php if (!$movimientos): ?>
            <div class="table-empty">
                <i class="bi bi-inbox"></i>
                <strong>Sin movimientos registrados</strong>
                <span>Registra la producción del día para comenzar el control.</span>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-app align-middle">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th class="num">Cantidad</th>
                            <th class="num">Stock anterior</th>
                            <th class="num">Stock nuevo</th>
                            <th>Usuario</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $movimiento): ?>
                            <?php
                            $tipoMov = $movimiento['tipo'];
                            [$claseMov, $iconoMov] = match ($tipoMov) {
                                'Producción' => ['success', 'bi-plus-circle'],
                                'Venta'      => ['info', 'bi-bag-check'],
                                default      => ['warning', 'bi-sliders'],
                            };
                            ?>
                            <tr>
                                <td class="text-muted-app small-70"><?= e(formatear_fecha_hora($movimiento['fecha_movimiento'])) ?></td>
                                <td><span class="cell-title"><?= e($movimiento['producto']) ?></span></td>
                                <td><span class="badge-soft bg-<?= e($claseMov) ?>-soft"><i class="bi <?= e($iconoMov) ?>"></i><?= e($tipoMov) ?></span></td>
                                <td class="num">
                                    <strong class="<?= $tipoMov === 'Venta' ? 'text-danger' : ($tipoMov === 'Producción' ? 'text-success' : 'text-coffee') ?>">
                                        <?= $tipoMov === 'Venta' ? '−' : ($tipoMov === 'Producción' ? '+' : '±') ?><?= (int) $movimiento['cantidad'] ?>
                                    </strong>
                                </td>
                                <td class="num text-muted-app"><?= (int) $movimiento['stock_anterior'] ?></td>
                                <td class="num"><strong><?= (int) $movimiento['stock_nuevo'] ?></strong></td>
                                <td><?= e($movimiento['usuario']) ?></td>
                                <td class="small text-muted-app"><?= e($movimiento['observaciones'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

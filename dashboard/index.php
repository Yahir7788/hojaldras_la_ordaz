<?php
/**
 * Dashboard principal.
 * Gerente y cocinero: panorama del almacén.
 * Ventas: panorama del producto terminado.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pdo = db();
$usuario = usuario_actual();
$esVentas = es_ventas();

$saludo = match (true) {
    (int) date('H') < 12 => 'Buenos días',
    (int) date('H') < 19 => 'Buenas tardes',
    default              => 'Buenas noches',
};

if ($esVentas) {
    // -----------------------------------------------------------------------
    // Dashboard de ventas / producto terminado
    // -----------------------------------------------------------------------
    $productos = $pdo->query(
        'SELECT * FROM productos WHERE activo = 1 ORDER BY nombre ASC'
    )->fetchAll();

    $totalProductos = count($productos);
    $unidades = array_sum(array_map(static fn(array $p): int => (int) $p['stock_actual'], $productos));
    $pizzas = 0;
    $bajos = 0;
    foreach ($productos as $producto) {
        if (mb_strtolower($producto['nombre'], 'UTF-8') === 'pizza') {
            $pizzas = (int) $producto['stock_actual'];
        }
        if ((int) $producto['stock_actual'] <= (int) $producto['stock_minimo']) {
            $bajos++;
        }
    }

    $movimientosProducto = $pdo->query(
        'SELECT mp.*, p.nombre AS producto, u.nombre_completo AS usuario
         FROM movimientos_producto mp
         INNER JOIN productos p ON p.id_producto = mp.id_producto
         INNER JOIN usuarios u ON u.id_usuario = mp.id_usuario
         ORDER BY mp.fecha_movimiento DESC, mp.id_movimiento_producto DESC
         LIMIT 8'
    )->fetchAll();

    $pageTitle = 'Dashboard';
    $pageSubtitle = 'Control del producto terminado y disponibilidad para venta.';
    $pageIcon = 'bi-grid-1x2-fill';
    $pageActions = '<a class="btn btn-gold" href="' . e(base_url('productos/index.php')) . '">'
        . '<i class="bi bi-basket2 me-1"></i>Control de productos</a>';

    $chartProductos = [
        'labels' => array_map(static fn(array $p): string => $p['nombre'], $productos),
        'values' => array_map(static fn(array $p): int => (int) $p['stock_actual'], $productos),
        'minimos' => array_map(static fn(array $p): int => (int) $p['stock_minimo'], $productos),
    ];

    require __DIR__ . '/../includes/header.php';
    ?>

    <div class="welcome-card mb-4">
        <div>
            <span class="welcome-badge"><i class="bi bi-shop"></i> Punto de venta</span>
            <h2><?= e($saludo) ?>, <?= e(explode(' ', $usuario['nombre_completo'])[0]) ?>.</h2>
            <p>Consulta la disponibilidad de hojaldres, volovanes y pizzas. Registra la producción del día y las ventas para mantener el stock actualizado.</p>
        </div>
        <span class="welcome-icon"><i class="bi bi-basket2-fill"></i></span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card tone-coffee">
                <div class="stat-top"><span class="stat-icon"><i class="bi bi-box2-heart"></i></span></div>
                <p class="stat-value"><?= $totalProductos ?></p>
                <div class="stat-label">Productos activos</div>
                <div class="stat-hint"><i class="bi bi-tags"></i> Catálogo de venta</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card tone-success">
                <div class="stat-top"><span class="stat-icon"><i class="bi bi-basket"></i></span></div>
                <p class="stat-value"><?= number_format($unidades) ?></p>
                <div class="stat-label">Unidades disponibles</div>
                <div class="stat-hint"><i class="bi bi-check2-circle"></i> Listas para venta</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card tone-gold">
                <div class="stat-top"><span class="stat-icon"><i class="bi bi-record-circle"></i></span></div>
                <p class="stat-value"><?= $pizzas ?></p>
                <div class="stat-label">Pizzas disponibles</div>
                <div class="stat-hint"><a href="<?= e(base_url('productos/index.php')) ?>">Controlar pizzas <i class="bi bi-arrow-right"></i></a></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card tone-<?= $bajos > 0 ? 'warning' : 'success' ?>">
                <div class="stat-top"><span class="stat-icon"><i class="bi bi-exclamation-triangle"></i></span></div>
                <p class="stat-value"><?= $bajos ?></p>
                <div class="stat-label">Productos en mínimo</div>
                <div class="stat-hint"><i class="bi bi-arrow-repeat"></i> Requieren producción</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="panel h-100">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title"><i class="bi bi-bar-chart-line"></i> Disponibilidad de producto terminado</h2>
                        <p class="panel-subtitle">Existencias actuales comparadas con el mínimo definido.</p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="chart-box"><canvas id="chartProductos"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel h-100">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title"><i class="bi bi-clock-history"></i> Últimos movimientos</h2>
                        <p class="panel-subtitle">Producción y ventas recientes.</p>
                    </div>
                </div>
                <div class="panel-body panel-body-flush">
                    <?php if (!$movimientosProducto): ?>
                        <div class="table-empty"><i class="bi bi-inbox"></i><strong>Sin movimientos</strong><span>Aún no se han registrado ventas ni producción.</span></div>
                    <?php else: ?>
                        <?php foreach ($movimientosProducto as $movimiento): ?>
                            <?php
                            $tipoMov = $movimiento['tipo'];
                            $iconoMov = $tipoMov === 'Producción' ? 'bi-plus-circle' : ($tipoMov === 'Venta' ? 'bi-bag-check' : 'bi-sliders');
                            $claseMov = $tipoMov === 'Producción' ? 'entrada' : ($tipoMov === 'Venta' ? 'salida' : 'ajuste');
                            ?>
                            <div class="movement-item">
                                <span class="movement-icon <?= e($claseMov) ?>"><i class="bi <?= e($iconoMov) ?>"></i></span>
                                <div class="movement-main">
                                    <strong><?= e($movimiento['producto']) ?></strong>
                                    <small><?= e($tipoMov) ?> · <?= e($movimiento['usuario']) ?></small>
                                </div>
                                <div class="movement-meta">
                                    <strong><?= $tipoMov === 'Venta' ? '−' : '+' ?><?= (int) $movimiento['cantidad'] ?></strong>
                                    <small><?= e(formatear_fecha_hora($movimiento['fecha_movimiento'])) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="panel-footer">
                    <a href="<?= e(base_url('productos/index.php')) ?>" class="fw-700">Ver historial completo <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($productos as $producto): ?>
            <?php
            $stock = (int) $producto['stock_actual'];
            $minimo = (int) $producto['stock_minimo'];
            [$claseEstado, $textoEstado, $iconoEstado] = estado_stock($stock, $minimo);
            $emoji = match (mb_strtolower($producto['nombre'], 'UTF-8')) {
                'pizza'   => '🍕',
                'volován' => '🥐',
                'hojaldre' => '🥮',
                default   => '🥖',
            };
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="product-card">
                    <div class="product-card-top">
                        <span class="product-emoji"><?= $emoji ?></span>
                        <span class="badge-soft bg-<?= e($claseEstado) ?>-soft"><i class="bi <?= e($iconoEstado) ?>"></i><?= e($textoEstado) ?></span>
                    </div>
                    <h3><?= e($producto['nombre']) ?></h3>
                    <p><?= e($producto['descripcion']) ?></p>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="product-stock">
                            <strong><?= $stock ?></strong><span>unidades</span>
                        </div>
                        <span class="product-price"><?= e(formatear_moneda($producto['precio'])) ?></span>
                    </div>
                    <div class="stock-bar <?= $stock <= 0 ? 'is-danger' : ($stock <= $minimo ? 'is-warning' : '') ?>">
                        <span style="width: <?= e((string) min(100, $minimo > 0 ? ($stock / max($minimo * 2, 1)) * 100 : 100)) ?>%"></span>
                    </div>
                    <div class="product-actions">
                        <a class="btn btn-success-soft btn-sm" href="<?= e(base_url('productos/index.php?accion=produccion&producto=' . (int) $producto['id_producto'])) ?>">
                            <i class="bi bi-plus-lg"></i> Producción
                        </a>
                        <a class="btn btn-soft btn-sm" href="<?= e(base_url('productos/index.php?accion=venta&producto=' . (int) $producto['id_producto'])) ?>">
                            <i class="bi bi-bag-check"></i> Venta
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
    $pageScripts = '<script>
        const datosProductos = ' . json_encode($chartProductos, JSON_UNESCAPED_UNICODE) . ';
        const ctxProductos = document.getElementById("chartProductos");
        if (ctxProductos) {
            new Chart(ctxProductos, {
                type: "bar",
                data: {
                    labels: datosProductos.labels,
                    datasets: [
                        {
                            label: "Disponibles",
                            data: datosProductos.values,
                            backgroundColor: ["#c8963e", "#6f4e37", "#3f6f8f"],
                            borderRadius: 8,
                            maxBarThickness: 58
                        },
                        {
                            label: "Stock mínimo",
                            data: datosProductos.minimos,
                            backgroundColor: "rgba(178, 58, 58, .18)",
                            borderColor: "#b23a3a",
                            borderWidth: 1,
                            borderRadius: 8,
                            maxBarThickness: 58
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, grid: { color: "rgba(139,117,101,.12)" }, ticks: { precision: 0 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    </script>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// ---------------------------------------------------------------------------
// Dashboard de almacén (gerente / cocinero)
// ---------------------------------------------------------------------------
$stats = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN cantidad_actual > 0 THEN 1 ELSE 0 END) AS disponibles,
        SUM(CASE WHEN cantidad_actual <= 0 THEN 1 ELSE 0 END) AS agotados,
        SUM(CASE WHEN cantidad_actual > 0 AND cantidad_actual <= stock_minimo THEN 1 ELSE 0 END) AS stock_bajo
     FROM insumos
     WHERE activo = 1"
)->fetch();

$mermasTotal = (int) $pdo->query('SELECT COUNT(*) FROM mermas')->fetchColumn();
$movimientosHoy = (int) $pdo->query(
    'SELECT COUNT(*) FROM movimientos_inventario WHERE DATE(fecha_movimiento) = CURDATE()'
)->fetchColumn();
$alertas = resumen_alertas($pdo);

// Serie de los últimos 7 días
$dias = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-{$i} days"));
    $dias[$fecha] = ['entradas' => 0, 'salidas' => 0];
}

$stmt = $pdo->prepare(
    "SELECT DATE(fecha_movimiento) AS fecha,
            SUM(CASE WHEN tipo = 'Entrada' THEN 1 ELSE 0 END) AS entradas,
            SUM(CASE WHEN tipo = 'Salida' THEN 1 ELSE 0 END) AS salidas
     FROM movimientos_inventario
     WHERE DATE(fecha_movimiento) BETWEEN :desde AND :hasta
     GROUP BY DATE(fecha_movimiento)"
);
$stmt->execute([':desde' => array_key_first($dias), ':hasta' => array_key_last($dias)]);
foreach ($stmt->fetchAll() as $fila) {
    if (isset($dias[$fila['fecha']])) {
        $dias[$fila['fecha']]['entradas'] = (int) $fila['entradas'];
        $dias[$fila['fecha']]['salidas'] = (int) $fila['salidas'];
    }
}

$chartSerie = [
    'labels'   => array_map(static fn(string $f): string => date('d/m', strtotime($f)), array_keys($dias)),
    'entradas' => array_values(array_map(static fn(array $d): int => $d['entradas'], $dias)),
    'salidas'  => array_values(array_map(static fn(array $d): int => $d['salidas'], $dias)),
];

$consumo = $pdo->query(
    "SELECT i.nombre, i.unidad_medida, ROUND(SUM(s.cantidad), 3) AS total
     FROM salidas s
     INNER JOIN insumos i ON i.id_insumo = s.id_insumo
     WHERE s.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY i.id_insumo, i.nombre, i.unidad_medida
     ORDER BY total DESC
     LIMIT 6"
)->fetchAll();

$chartConsumo = [
    'labels' => array_map(static fn(array $c): string => $c['nombre'] . ' (' . $c['unidad_medida'] . ')', $consumo),
    'values' => array_map(static fn(array $c): float => (float) $c['total'], $consumo),
];

$mermasMotivo = $pdo->query(
    "SELECT motivo, COUNT(*) AS total
     FROM mermas
     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY motivo
     ORDER BY total DESC"
)->fetchAll();

$chartMermas = [
    'labels' => array_map(static fn(array $m): string => $m['motivo'], $mermasMotivo),
    'values' => array_map(static fn(array $m): int => (int) $m['total'], $mermasMotivo),
];

$porCategoria = $pdo->query(
    "SELECT c.nombre, COUNT(i.id_insumo) AS total
     FROM categorias c
     LEFT JOIN insumos i ON i.id_categoria = c.id_categoria AND i.activo = 1
     GROUP BY c.id_categoria, c.nombre
     ORDER BY c.nombre"
)->fetchAll();

$chartCategorias = [
    'labels' => array_map(static fn(array $c): string => $c['nombre'], $porCategoria),
    'values' => array_map(static fn(array $c): int => (int) $c['total'], $porCategoria),
];

$ultimosMovimientos = $pdo->query(
    "SELECT m.*, i.nombre AS insumo, i.unidad_medida, u.nombre_completo AS usuario
     FROM movimientos_inventario m
     INNER JOIN insumos i ON i.id_insumo = m.id_insumo
     INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
     ORDER BY m.fecha_movimiento DESC, m.id_movimiento DESC
     LIMIT 8"
)->fetchAll();

$insumosBajos = insumos_con_stock_bajo($pdo);
$lotesProximos = lotes_por_vencer($pdo, 7);

$pageTitle = 'Dashboard';
$pageSubtitle = 'Resumen general del almacén e inventario de materias primas.';
$pageIcon = 'bi-grid-1x2-fill';

$acciones = [];
if (es_gerente()) {
    $acciones[] = '<a class="btn btn-gold" href="' . e(base_url('entradas/registrar.php')) . '"><i class="bi bi-box-arrow-in-down me-1"></i>Registrar entrada</a>';
    $acciones[] = '<a class="btn btn-outline-coffee" href="' . e(base_url('reportes/index.php')) . '"><i class="bi bi-file-earmark-bar-graph me-1"></i>Reportes</a>';
} else {
    $acciones[] = '<a class="btn btn-gold" href="' . e(base_url('salidas/registrar.php')) . '"><i class="bi bi-box-arrow-up me-1"></i>Registrar salida</a>';
    $acciones[] = '<a class="btn btn-outline-coffee" href="' . e(base_url('alertas/index.php')) . '"><i class="bi bi-exclamation-triangle me-1"></i>Ver alertas</a>';
}
$pageActions = implode('', $acciones);

require __DIR__ . '/../includes/header.php';
?>

<div class="welcome-card mb-4">
    <div>
        <span class="welcome-badge"><i class="bi bi-stars"></i> <?= e(etiqueta_rol($usuario['rol'])) ?></span>
        <h2><?= e($saludo) ?>, <?= e(explode(' ', $usuario['nombre_completo'])[0]) ?>.</h2>
        <p>Este es el estado real del almacén de Hojaldras “La Ordaz”. Revisa las existencias, las alertas y los movimientos más recientes antes de iniciar la producción del día.</p>
    </div>
    <span class="welcome-icon"><i class="bi bi-cup-hot-fill"></i></span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-coffee">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-box-seam"></i></span></div>
            <p class="stat-value"><?= (int) $stats['total'] ?></p>
            <div class="stat-label">Total de insumos</div>
            <div class="stat-hint"><i class="bi bi-journal-text"></i> Registrados y activos</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-success">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-check2-circle"></i></span></div>
            <p class="stat-value"><?= (int) $stats['disponibles'] ?></p>
            <div class="stat-label">Insumos disponibles</div>
            <div class="stat-hint"><i class="bi bi-arrow-up-right-circle"></i> Con existencia mayor a cero</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-<?= ((int) $stats['stock_bajo'] + (int) $stats['agotados']) > 0 ? 'danger' : 'success' ?>">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-exclamation-octagon"></i></span></div>
            <p class="stat-value"><?= (int) $stats['stock_bajo'] + (int) $stats['agotados'] ?></p>
            <div class="stat-label">Stock bajo o agotado</div>
            <div class="stat-hint"><i class="bi bi-x-circle"></i> <?= (int) $stats['agotados'] ?> agotado(s) · <?= (int) $stats['stock_bajo'] ?> en mínimo</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-warning">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-clock-history"></i></span></div>
            <p class="stat-value"><?= (int) $alertas['por_vencer'] ?></p>
            <div class="stat-label">Próximos a caducar</div>
            <div class="stat-hint"><i class="bi bi-calendar-event"></i> En los siguientes 7 días</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-danger">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-x-octagon"></i></span></div>
            <p class="stat-value"><?= (int) $alertas['caducados'] ?></p>
            <div class="stat-label">Insumos caducados</div>
            <div class="stat-hint"><i class="bi bi-trash3"></i> Requieren merma inmediata</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-info">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-arrow-left-right"></i></span></div>
            <p class="stat-value"><?= $movimientosHoy ?></p>
            <div class="stat-label">Movimientos de hoy</div>
            <div class="stat-hint"><i class="bi bi-calendar-check"></i> Entradas, salidas y mermas</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-warning">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-trash3"></i></span></div>
            <p class="stat-value"><?= $mermasTotal ?></p>
            <div class="stat-label">Mermas registradas</div>
            <div class="stat-hint"><i class="bi bi-clipboard-data"></i> Historial acumulado</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card tone-<?= $alertas['total'] > 0 ? 'warning' : 'success' ?>">
            <div class="stat-top"><span class="stat-icon"><i class="bi bi-bell"></i></span></div>
            <p class="stat-value"><?= (int) $alertas['total'] ?></p>
            <div class="stat-label">Alertas activas</div>
            <div class="stat-hint"><a href="<?= e(base_url('alertas/index.php')) ?>">Revisar alertas <i class="bi bi-arrow-right"></i></a></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="panel h-100">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-bar-chart-line"></i> Entradas y salidas</h2>
                    <p class="panel-subtitle">Cantidad de movimientos registrados en los últimos 7 días.</p>
                </div>
                <span class="chip"><i class="bi bi-calendar3"></i> Última semana</span>
            </div>
            <div class="panel-body">
                <div class="chart-box"><canvas id="chartSerie"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="panel h-100">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-pie-chart"></i> Consumo de materias primas</h2>
                    <p class="panel-subtitle">Insumos más usados en 30 días.</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-box"><canvas id="chartConsumo"></canvas></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-5">
        <div class="panel h-100">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-trash3"></i> Mermas por motivo</h2>
                    <p class="panel-subtitle">Últimos 30 días.</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-box chart-box-sm"><canvas id="chartMermas"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="panel h-100">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-diagram-3"></i> Existencias por categoría</h2>
                    <p class="panel-subtitle">Cantidad de insumos activos por categoría.</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-box chart-box-sm"><canvas id="chartCategorias"></canvas></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="panel h-100">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-clock-history"></i> Movimientos recientes</h2>
                    <p class="panel-subtitle">Últimas operaciones registradas en el inventario.</p>
                </div>
                <a href="<?= e(base_url('reportes/index.php?tipo=movimientos')) ?>" class="btn btn-soft btn-sm">Ver historial</a>
            </div>
            <div class="panel-body panel-body-flush">
                <?php if (!$ultimosMovimientos): ?>
                    <div class="table-empty"><i class="bi bi-inbox"></i><strong>Sin movimientos</strong><span>Registra una entrada para comenzar.</span></div>
                <?php else: ?>
                    <?php foreach ($ultimosMovimientos as $movimiento): ?>
                        <?php
                        $tipoMov = $movimiento['tipo'];
                        $configMov = [
                            'Entrada' => ['entrada', 'bi-box-arrow-in-down', '+'],
                            'Salida'  => ['salida', 'bi-box-arrow-up', '−'],
                            'Merma'   => ['merma', 'bi-trash3', '−'],
                            'Ajuste'  => ['ajuste', 'bi-sliders', '±'],
                        ][$tipoMov] ?? ['ajuste', 'bi-arrow-left-right', '±'];
                        ?>
                        <div class="movement-item">
                            <span class="movement-icon <?= e($configMov[0]) ?>"><i class="bi <?= e($configMov[1]) ?>"></i></span>
                            <div class="movement-main">
                                <strong><?= e($movimiento['insumo']) ?></strong>
                                <small><?= e($tipoMov) ?> · <?= e($movimiento['usuario']) ?></small>
                            </div>
                            <div class="movement-meta">
                                <strong><?= $configMov[2] ?><?= e(formatear_cantidad($movimiento['cantidad']) . ' ' . $movimiento['unidad_medida']) ?></strong>
                                <small><?= e(formatear_fecha_hora($movimiento['fecha_movimiento'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="panel mb-3">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-exclamation-triangle"></i> Alertas prioritarias</h2>
                    <p class="panel-subtitle">Reabastecimiento y caducidad.</p>
                </div>
                <a href="<?= e(base_url('alertas/index.php')) ?>" class="btn btn-soft btn-sm">Ver todas</a>
            </div>
            <div class="panel-body panel-body-flush">
                <?php
                $prioritarias = 0;
                foreach (array_slice($insumosBajos, 0, 3) as $bajo):
                    $prioritarias++;
                    ?>
                    <div class="alert-row">
                        <span class="alert-row-icon"><i class="bi bi-box-seam"></i></span>
                        <div class="alert-row-main">
                            <strong><?= e($bajo['nombre']) ?></strong>
                            <small>Stock <?= e(formatear_cantidad($bajo['cantidad_actual']) . ' ' . $bajo['unidad_medida']) ?> · mínimo <?= e(formatear_cantidad($bajo['stock_minimo'])) ?></small>
                        </div>
                        <div class="alert-row-meta">
                            <span class="badge-soft bg-danger-soft"><i class="bi bi-arrow-down-circle"></i>Reabastecer</span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php foreach (array_slice($lotesProximos, 0, 3) as $lote): ?>
                    <?php
                    $prioritarias++;
                    $diasLote = dias_para_caducar($lote['fecha_caducidad']);
                    ?>
                    <div class="alert-row">
                        <span class="alert-row-icon warning"><i class="bi bi-calendar-event"></i></span>
                        <div class="alert-row-main">
                            <strong><?= e($lote['insumo']) ?></strong>
                            <small>Lote <?= e($lote['numero_lote']) ?> · <?= e(formatear_cantidad($lote['cantidad_actual']) . ' ' . $lote['unidad_medida']) ?></small>
                        </div>
                        <div class="alert-row-meta">
                            <span class="badge-soft bg-warning-soft"><i class="bi bi-clock"></i><?= (int) $diasLote ?> día(s)</span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ($prioritarias === 0): ?>
                    <div class="table-empty"><i class="bi bi-check2-circle"></i><strong>Todo en orden</strong><span>No hay alertas de inventario por atender.</span></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-lightning-charge"></i> Acciones rápidas</h2>
                </div>
            </div>
            <div class="panel-body">
                <div class="quick-actions">
                    <?php if (es_gerente()): ?>
                        <a class="quick-action" href="<?= e(base_url('entradas/registrar.php')) ?>"><i class="bi bi-box-arrow-in-down"></i> Nueva entrada</a>
                        <a class="quick-action" href="<?= e(base_url('insumos/registrar.php')) ?>"><i class="bi bi-plus-square"></i> Nuevo insumo</a>
                        <a class="quick-action" href="<?= e(base_url('mermas/registrar.php')) ?>"><i class="bi bi-trash3"></i> Registrar merma</a>
                        <a class="quick-action" href="<?= e(base_url('usuarios/registrar.php')) ?>"><i class="bi bi-person-plus"></i> Nuevo usuario</a>
                    <?php else: ?>
                        <a class="quick-action" href="<?= e(base_url('salidas/registrar.php')) ?>"><i class="bi bi-box-arrow-up"></i> Nueva salida</a>
                        <a class="quick-action" href="<?= e(base_url('mermas/registrar.php')) ?>"><i class="bi bi-trash3"></i> Registrar merma</a>
                        <a class="quick-action" href="<?= e(base_url('insumos/index.php')) ?>"><i class="bi bi-box-seam"></i> Ver insumos</a>
                        <a class="quick-action" href="<?= e(base_url('alertas/index.php')) ?>"><i class="bi bi-bell"></i> Alertas</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = '<script>
    const serie = ' . json_encode($chartSerie, JSON_UNESCAPED_UNICODE) . ';
    const consumo = ' . json_encode($chartConsumo, JSON_UNESCAPED_UNICODE) . ';
    const mermas = ' . json_encode($chartMermas, JSON_UNESCAPED_UNICODE) . ';
    const categorias = ' . json_encode($chartCategorias, JSON_UNESCAPED_UNICODE) . ';

    const ctxSerie = document.getElementById("chartSerie");
    if (ctxSerie) {
        new Chart(ctxSerie, {
            type: "bar",
            data: {
                labels: serie.labels,
                datasets: [
                    { label: "Entradas", data: serie.entradas, backgroundColor: "#2f7d4f", borderRadius: 7, maxBarThickness: 28 },
                    { label: "Salidas", data: serie.salidas, backgroundColor: "#c8963e", borderRadius: 7, maxBarThickness: 28 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: "rgba(139,117,101,.12)" }, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const ctxConsumo = document.getElementById("chartConsumo");
    if (ctxConsumo && consumo.labels.length) {
        new Chart(ctxConsumo, {
            type: "doughnut",
            data: {
                labels: consumo.labels,
                datasets: [{ data: consumo.values, backgroundColor: ["#4b2e1e", "#c8963e", "#6f4e37", "#d9ac58", "#8b7565", "#3f6f8f"], borderWidth: 3, borderColor: "#fff" }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: "62%",
                plugins: { legend: { position: "bottom" } }
            }
        });
    } else if (ctxConsumo) {
        ctxConsumo.parentElement.innerHTML = "<div class=\"table-empty\"><i class=\"bi bi-pie-chart\"></i><strong>Sin consumo registrado</strong><span>Las salidas aparecerán aquí.</span></div>";
    }

    const ctxMermas = document.getElementById("chartMermas");
    if (ctxMermas && mermas.labels.length) {
        new Chart(ctxMermas, {
            type: "polarArea",
            data: {
                labels: mermas.labels,
                datasets: [{ data: mermas.values, backgroundColor: ["rgba(178,58,58,.72)", "rgba(200,150,62,.72)", "rgba(63,111,143,.72)", "rgba(111,78,55,.72)"], borderWidth: 2, borderColor: "#fff" }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { r: { ticks: { display: false }, grid: { color: "rgba(139,117,101,.14)" } } },
                plugins: { legend: { position: "bottom" } }
            }
        });
    } else if (ctxMermas) {
        ctxMermas.parentElement.innerHTML = "<div class=\"table-empty\"><i class=\"bi bi-trash3\"></i><strong>Sin mermas</strong><span>No hay pérdidas en los últimos 30 días.</span></div>";
    }

    const ctxCategorias = document.getElementById("chartCategorias");
    if (ctxCategorias) {
        new Chart(ctxCategorias, {
            type: "bar",
            data: {
                labels: categorias.labels,
                datasets: [{ label: "Insumos", data: categorias.values, backgroundColor: ["#6f4e37", "#c8963e", "#3f6f8f", "#2f7d4f"], borderRadius: 7, maxBarThickness: 44 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, indexAxis: "y",
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: "rgba(139,117,101,.12)" }, ticks: { precision: 0 } },
                    y: { grid: { display: false } }
                }
            }
        });
    }
</script>';
require __DIR__ . '/../includes/footer.php';

<?php
/**
 * Motor de reportes: definiciones, filtros y consultas.
 * Se comparte entre la vista (index.php) y la exportación CSV.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/funciones.php';

/**
 * Catálogo de reportes disponibles.
 */
function reportes_disponibles(): array
{
    return [
        'inventario' => [
            'titulo'      => 'Inventario actual',
            'descripcion' => 'Existencias, stock mínimo y estado de cada insumo.',
            'icono'       => 'bi-box-seam',
            'fechas'      => false,
            'filtros'     => ['estado', 'categoria', 'insumo'],
        ],
        'movimientos' => [
            'titulo'      => 'Movimientos de inventario',
            'descripcion' => 'Historial completo de entradas, salidas, mermas y ajustes.',
            'icono'       => 'bi-arrow-left-right',
            'fechas'      => true,
            'filtros'     => ['mov', 'categoria', 'insumo'],
        ],
        'entradas' => [
            'titulo'      => 'Entradas de almacén',
            'descripcion' => 'Recepciones de materia prima y lotes generados.',
            'icono'       => 'bi-box-arrow-in-down',
            'fechas'      => true,
            'filtros'     => ['categoria', 'insumo'],
        ],
        'salidas' => [
            'titulo'      => 'Salidas de almacén',
            'descripcion' => 'Consumo de insumos entregado a cocina y mostrador.',
            'icono'       => 'bi-box-arrow-up',
            'fechas'      => true,
            'filtros'     => ['categoria', 'insumo'],
        ],
        'mermas' => [
            'titulo'      => 'Mermas y pérdidas',
            'descripcion' => 'Producto caducado, deteriorado o desperdiciado.',
            'icono'       => 'bi-trash3',
            'fechas'      => true,
            'filtros'     => ['motivo', 'categoria', 'insumo'],
        ],
        'stock_bajo' => [
            'titulo'      => 'Stock bajo y agotados',
            'descripcion' => 'Insumos que requieren reabastecimiento inmediato.',
            'icono'       => 'bi-exclamation-triangle',
            'fechas'      => false,
            'filtros'     => ['categoria', 'insumo'],
        ],
        'caducidades' => [
            'titulo'      => 'Control de caducidades',
            'descripcion' => 'Lotes vencidos o próximos a vencer, ordenados por fecha.',
            'icono'       => 'bi-calendar-event',
            'fechas'      => false,
            'filtros'     => ['dias', 'categoria', 'insumo'],
        ],
        'productos' => [
            'titulo'      => 'Producto terminado',
            'descripcion' => 'Producción, ventas y ajustes de hojaldres, volovanes y pizzas.',
            'icono'       => 'bi-basket2',
            'fechas'      => true,
            'filtros'     => ['mov_producto', 'producto'],
        ],
    ];
}

/**
 * Normaliza y valida los filtros recibidos por GET.
 */
function reportes_filtros_normalizados(array $entrada): array
{
    $disponibles = reportes_disponibles();
    $tipo = (string) ($entrada['tipo'] ?? 'inventario');
    if (!isset($disponibles[$tipo])) {
        $tipo = 'inventario';
    }

    $desde = trim((string) ($entrada['desde'] ?? date('Y-m-d', strtotime('-29 days'))));
    $hasta = trim((string) ($entrada['hasta'] ?? fecha_hoy()));
    if (!validar_fecha($desde)) {
        $desde = date('Y-m-d', strtotime('-29 days'));
    }
    if (!validar_fecha($hasta)) {
        $hasta = fecha_hoy();
    }
    if ($desde > $hasta) {
        [$desde, $hasta] = [$hasta, $desde];
    }

    $estados = ['activos', 'disponibles', 'bajo', 'agotados', 'inactivos', 'todos'];
    $estado = (string) ($entrada['estado'] ?? 'activos');
    if (!in_array($estado, $estados, true)) {
        $estado = 'activos';
    }

    $movimientos = ['', 'Entrada', 'Salida', 'Merma', 'Ajuste', 'Producción', 'Venta'];
    $mov = (string) ($entrada['mov'] ?? '');
    if (!in_array($mov, $movimientos, true)) {
        $mov = '';
    }

    $movProducto = ['', 'Producción', 'Venta', 'Ajuste'];
    $movProductoValor = (string) ($entrada['mov_producto'] ?? '');
    if (!in_array($movProductoValor, $movProducto, true)) {
        $movProductoValor = '';
    }

    $motivos = array_keys(motivos_merma());
    $motivo = (string) ($entrada['motivo'] ?? '');
    if (!in_array($motivo, $motivos, true)) {
        $motivo = '';
    }

    $dias = (string) ($entrada['dias'] ?? '30');
    if (!in_array($dias, ['0', '7', '15', '30', '60', '90', '365', 'todos'], true)) {
        $dias = '30';
    }

    return [
        'tipo'          => $tipo,
        'desde'         => $desde,
        'hasta'         => $hasta,
        'estado'        => $estado,
        'categoria'     => max(0, (int) ($entrada['categoria'] ?? 0)),
        'insumo'        => max(0, (int) ($entrada['insumo'] ?? 0)),
        'producto'      => max(0, (int) ($entrada['producto'] ?? 0)),
        'mov'           => $mov,
        'mov_producto'  => $movProductoValor,
        'motivo'        => $motivo,
        'dias'          => $dias,
    ];
}

/**
 * Opciones para los selectores de filtros.
 */
function reportes_opciones(PDO $pdo): array
{
    static $opciones = null;

    if ($opciones !== null) {
        return $opciones;
    }

    $opciones = [
        'categorias' => $pdo->query('SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC')->fetchAll(),
        'insumos'    => $pdo->query('SELECT id_insumo, nombre FROM insumos WHERE activo = 1 ORDER BY nombre ASC')->fetchAll(),
        'productos'  => $pdo->query('SELECT id_producto, nombre FROM productos WHERE activo = 1 ORDER BY nombre ASC')->fetchAll(),
        'motivos'    => motivos_merma(),
    ];

    return $opciones;
}

/**
 * Texto del periodo cubierto por el reporte.
 */
function reporte_periodo(array $filtros): string
{
    $disponibles = reportes_disponibles();
    $tipo = $filtros['tipo'];
    $def = $disponibles[$tipo];

    if ($tipo === 'caducidades') {
        return match ($filtros['dias']) {
            '0'     => 'Lotes caducados al ' . formatear_fecha(fecha_hoy()),
            'todos' => 'Todos los lotes con fecha de caducidad',
            default => 'Lotes que vencen en los próximos ' . $filtros['dias'] . ' días (incluye caducados)',
        };
    }

    if ($def['fechas']) {
        return 'Del ' . formatear_fecha($filtros['desde']) . ' al ' . formatear_fecha($filtros['hasta']);
    }

    return 'Corte al ' . formatear_fecha(fecha_hoy());
}

/**
 * Construye las filas, columnas y el resumen del reporte seleccionado.
 */
function construir_reporte(PDO $pdo, array $filtros): array
{
    $tipo = $filtros['tipo'];
    $def = reportes_disponibles()[$tipo];

    $columnas = [];
    $filas = [];
    $resumen = [];

    if ($tipo === 'inventario') {
        $condiciones = [];
        $parametros = [];

        switch ($filtros['estado']) {
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

        if ($filtros['categoria'] > 0) {
            $condiciones[] = 'i.id_categoria = :categoria';
            $parametros[':categoria'] = $filtros['categoria'];
        }
        if ($filtros['insumo'] > 0) {
            $condiciones[] = 'i.id_insumo = :insumo';
            $parametros[':insumo'] = $filtros['insumo'];
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
        $stmt = $pdo->prepare(
            "SELECT i.nombre AS insumo, c.nombre AS categoria, i.unidad_medida AS unidad,
                    i.cantidad_actual, i.stock_minimo, i.activo,
                    (SELECT MIN(l.fecha_caducidad)
                       FROM lotes l
                      WHERE l.id_insumo = i.id_insumo AND l.cantidad_actual > 0
                        AND l.fecha_caducidad IS NOT NULL) AS proxima_caducidad
             FROM insumos i
             INNER JOIN categorias c ON c.id_categoria = i.id_categoria
             {$where}
             ORDER BY i.activo DESC, i.nombre ASC
             LIMIT 1000"
        );
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $columnas = [
            ['clave' => 'insumo', 'etiqueta' => 'Insumo', 'tipo' => 'texto'],
            ['clave' => 'categoria', 'etiqueta' => 'Categoría', 'tipo' => 'texto'],
            ['clave' => 'unidad', 'etiqueta' => 'Unidad', 'tipo' => 'texto'],
            ['clave' => 'cantidad_actual', 'etiqueta' => 'Stock actual', 'tipo' => 'cantidad', 'clase' => 'num'],
            ['clave' => 'stock_minimo', 'etiqueta' => 'Stock mínimo', 'tipo' => 'cantidad', 'clase' => 'num'],
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'estado_stock'],
            ['clave' => 'proxima_caducidad', 'etiqueta' => 'Próxima caducidad', 'tipo' => 'fecha'],
        ];

        $disponibles = 0;
        $criticos = 0;
        $porVencer = 0;
        foreach ($filas as $fila) {
            $stock = (float) $fila['cantidad_actual'];
            $minimo = (float) $fila['stock_minimo'];
            if ($stock > $minimo) {
                $disponibles++;
            } else {
                $criticos++;
            }
            $dias = $fila['proxima_caducidad'] ? dias_para_caducar($fila['proxima_caducidad']) : null;
            if ($dias !== null && $dias <= 30) {
                $porVencer++;
            }
        }

        $resumen = [
            ['etiqueta' => 'Insumos listados', 'valor' => count($filas)],
            ['etiqueta' => 'Disponibles', 'valor' => $disponibles],
            ['etiqueta' => 'En mínimo o agotados', 'valor' => $criticos],
            ['etiqueta' => 'Con caducidad próxima', 'valor' => $porVencer],
        ];
    }

    if ($tipo === 'movimientos') {
        $condiciones = ['DATE(m.fecha_movimiento) BETWEEN :desde AND :hasta'];
        $parametros = [':desde' => $filtros['desde'], ':hasta' => $filtros['hasta']];

        if ($filtros['mov'] !== '') {
            $condiciones[] = 'm.tipo = :mov';
            $parametros[':mov'] = $filtros['mov'];
        }
        if ($filtros['insumo'] > 0) {
            $condiciones[] = 'm.id_insumo = :insumo';
            $parametros[':insumo'] = $filtros['insumo'];
        }
        if ($filtros['categoria'] > 0) {
            $condiciones[] = 'i.id_categoria = :categoria';
            $parametros[':categoria'] = $filtros['categoria'];
        }

        $where = implode(' AND ', $condiciones);
        $stmt = $pdo->prepare(
            "SELECT m.fecha_movimiento, i.nombre AS insumo, c.nombre AS categoria,
                    m.tipo, m.cantidad, i.unidad_medida AS unidad,
                    m.stock_anterior, m.stock_nuevo, u.nombre_completo AS usuario,
                    m.observaciones
             FROM movimientos_inventario m
             INNER JOIN insumos i ON i.id_insumo = m.id_insumo
             INNER JOIN categorias c ON c.id_categoria = i.id_categoria
             INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
             WHERE {$where}
             ORDER BY m.fecha_movimiento DESC, m.id_movimiento DESC
             LIMIT 2000"
        );
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $columnas = [
            ['clave' => 'fecha_movimiento', 'etiqueta' => 'Fecha y hora', 'tipo' => 'fecha_hora'],
            ['clave' => 'insumo', 'etiqueta' => 'Insumo', 'tipo' => 'texto'],
            ['clave' => 'categoria', 'etiqueta' => 'Categoría', 'tipo' => 'texto'],
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'movimiento'],
            ['clave' => 'cantidad', 'etiqueta' => 'Cantidad', 'tipo' => 'cantidad', 'clase' => 'num'],
            ['clave' => 'unidad', 'etiqueta' => 'Unidad', 'tipo' => 'texto'],
            ['clave' => 'stock_anterior', 'etiqueta' => 'Stock anterior', 'tipo' => 'cantidad', 'clase' => 'num'],
            ['clave' => 'stock_nuevo', 'etiqueta' => 'Stock nuevo', 'tipo' => 'cantidad', 'clase' => 'num'],
            ['clave' => 'usuario', 'etiqueta' => 'Usuario', 'tipo' => 'texto'],
            ['clave' => 'observaciones', 'etiqueta' => 'Observaciones', 'tipo' => 'texto'],
        ];

        $conteos = ['Entrada' => 0, 'Salida' => 0, 'Merma' => 0, 'Ajuste' => 0];
        foreach ($filas as $fila) {
            if (isset($conteos[$fila['tipo']])) {
                $conteos[$fila['tipo']]++;
            }
        }

        $resumen = [
            ['etiqueta' => 'Movimientos', 'valor' => count($filas)],
            ['etiqueta' => 'Entradas', 'valor' => $conteos['Entrada']],
            ['etiqueta' => 'Salidas', 'valor' => $conteos['Salida']],
            ['etiqueta' => 'Mermas', 'valor' => $conteos['Merma']],
            ['etiqueta' => 'Ajustes', 'valor' => $conteos['Ajuste']],
        ];
    }

    if ($tipo === 'entradas') {
        $condiciones = ['e.fecha_ingreso BETWEEN :desde AND :hasta'];
        $parametros = [':desde' => $filtros['desde'], ':hasta' => $filtros['hasta']];

        if ($filtros['insumo'] > 0) {
            $condiciones[] = 'e.id_insumo = :insumo';
            $parametros[':insumo'] = $filtros['insumo'];
        }
        if ($filtros['categoria'] > 0) {
            $condiciones[] = 'i.id_categoria = :categoria';
            $parametros[':categoria'] = $filtros['categoria'];
        }

        $where = implode(' AND ', $condiciones);
        $stmt = $pdo->prepare(
            "SELECT e.fecha_ingreso, i.nombre AS insumo, c.nombre AS categoria,
                    e.cantidad, e.unidad_medida AS unidad, e.numero_lote,
                    e.fecha_caducidad, u.nombre_completo AS usuario, e.observaciones
             FROM entradas e
             INNER JOIN insumos i ON i.id_insumo = e.id_insumo
             INNER JOIN categorias c ON c.id_categoria = i.id_categoria
             INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
             WHERE {$where}
             ORDER BY e.fecha_ingreso DESC, e.id_entrada DESC
             LIMIT 2000"
        );
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $columnas = [
            ['clave' => 'fecha_ingreso', 'etiqueta' => 'Fecha', 'tipo' => 'fecha'],
            ['clave' => 'insumo', 'etiqueta' => 'Insumo', 'tipo' => 'texto'],
            ['clave' => 'categoria', 'etiqueta' => 'Categoría', 'tipo' => 'texto'],
            ['clave' => 'cantidad', 'etiqueta' => 'Cantidad', 'tipo' => 'cantidad', 'clase' => 'num'],
            ['clave' => 'unidad', 'etiqueta' => 'Unidad', 'tipo' => 'texto'],
            ['clave' => 'numero_lote', 'etiqueta' => 'Lote', 'tipo' => 'texto'],
            ['clave' => 'fecha_caducidad', 'etiqueta' => 'Caducidad', 'tipo' => 'fecha'],
            ['clave' => 'usuario', 'etiqueta' => 'Usuario', 'tipo' => 'texto'],
            ['clave' => 'observaciones', 'etiqueta' => 'Observaciones', 'tipo' => 'texto'],
        ];

        $insumos = [];
        $conCaducidad = 0;
        foreach ($filas as $fila) {
            $insumos[$fila['insumo']] = true;
            if ($fila['fecha_caducidad']) {
                $conCaducidad++;
            }
        }

        $resumen = [
            ['etiqueta' => 'Entradas', 'valor' => count($filas)],
            ['etiqueta' => 'Insumos abastecidos', 'valor' => count($insumos)],
            ['etiqueta' => 'Lotes con caducidad', 'valor' => $conCaducidad],
        ];
    }

    if ($tipo === 'salidas') {
        $condiciones = ['s.fecha BETWEEN :desde AND :hasta'];
        $parametros = [':desde' => $filtros['desde'], ':hasta' => $filtros['hasta']];

        if ($filtros['insumo'] > 0) {
            $condiciones[] = 's.id_insumo = :insumo';
            $parametros[':insumo'] = $filtros['insumo'];
        }
        if ($filtros['categoria'] > 0) {
            $condiciones[] = 'i.id_categoria = :categoria';
            $parametros[':categoria'] = $filtros['categoria'];
        }

        $where = implode(' AND ', $condiciones);
        $stmt = $pdo->prepare(
            "SELECT s.fecha, i.nombre AS insumo, c.nombre AS categoria,
                    s.cantidad, i.unidad_medida AS unidad, s.destino,
                    u.nombre_completo AS usuario, s.observaciones
             FROM salidas s
             INNER JOIN insumos i ON i.id_insumo = s.id_insumo
             INNER JOIN categorias c ON c.id_categoria = i.id_categoria
             INNER JOIN usuarios u ON u.id_usuario = s.id_usuario
             WHERE {$where}
             ORDER BY s.fecha DESC, s.id_salida DESC
             LIMIT 2000"
        );
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $columnas = [
            ['clave' => 'fecha', 'etiqueta' => 'Fecha', 'tipo' => 'fecha'],
            ['clave' => 'insumo', 'etiqueta' => 'Insumo', 'tipo' => 'texto'],
            ['clave' => 'categoria', 'etiqueta' => 'Categoría', 'tipo' => 'texto'],
            ['clave' => 'cantidad', 'etiqueta' => 'Cantidad', 'tipo' => 'cantidad', 'clase' => 'num'],
            ['clave' => 'unidad', 'etiqueta' => 'Unidad', 'tipo' => 'texto'],
            ['clave' => 'destino', 'etiqueta' => 'Destino', 'tipo' => 'texto'],
            ['clave' => 'usuario', 'etiqueta' => 'Usuario', 'tipo' => 'texto'],
            ['clave' => 'observaciones', 'etiqueta' => 'Observaciones', 'tipo' => 'texto'],
        ];

        $insumos = [];
        $destinos = [];
        foreach ($filas as $fila) {
            $insumos[$fila['insumo']] = true;
            $destinos[$fila['destino']] = true;
        }

        $resumen = [
            ['etiqueta' => 'Salidas', 'valor' => count($filas)],
            ['etiqueta' => 'Insumos consumidos', 'valor' => count($insumos)],
            ['etiqueta' => 'Destinos', 'valor' => count($destinos)],
        ];
    }

    if ($tipo === 'mermas') {
        $condiciones = ['m.fecha BETWEEN :desde AND :hasta'];
        $parametros = [':desde' => $filtros['desde'], ':hasta' => $filtros['hasta']];

        if ($filtros['motivo'] !== '') {
            $condiciones[] = 'm.motivo = :motivo';
            $parametros[':motivo'] = $filtros['motivo'];
        }
        if ($filtros['insumo'] > 0) {
            $condiciones[] = 'm.id_insumo = :insumo';
            $parametros[':insumo'] = $filtros['insumo'];
        }
        if ($filtros['categoria'] > 0) {
            $condiciones[] = 'i.id_categoria = :categoria';
            $parametros[':categoria'] = $filtros['categoria'];
        }

        $where = implode(' AND ', $condiciones);
        $stmt = $pdo->prepare(
            "SELECT m.fecha, i.nombre AS insumo, c.nombre AS categoria,
                    m.cantidad, i.unidad_medida AS unidad, m.motivo,
                    u.nombre_completo AS usuario, m.descripcion
             FROM mermas m
             INNER JOIN insumos i ON i.id_insumo = m.id_insumo
             INNER JOIN categorias c ON c.id_categoria = i.id_categoria
             INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
             WHERE {$where}
             ORDER BY m.fecha DESC, m.id_merma DESC
             LIMIT 2000"
        );
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $columnas = [
            ['clave' => 'fecha', 'etiqueta' => 'Fecha', 'tipo' => 'fecha'],
            ['clave' => 'insumo', 'etiqueta' => 'Insumo', 'tipo' => 'texto'],
            ['clave' => 'categoria', 'etiqueta' => 'Categoría', 'tipo' => 'texto'],
            ['clave' => 'cantidad', 'etiqueta' => 'Cantidad', 'tipo' => 'cantidad', 'clase' => 'num'],
            ['clave' => 'unidad', 'etiqueta' => 'Unidad', 'tipo' => 'texto'],
            ['clave' => 'motivo', 'etiqueta' => 'Motivo', 'tipo' => 'merma'],
            ['clave' => 'usuario', 'etiqueta' => 'Usuario', 'tipo' => 'texto'],
            ['clave' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'texto'],
        ];

        $insumos = [];
        $motivos = [];
        foreach ($filas as $fila) {
            $insumos[$fila['insumo']] = true;
            $motivos[$fila['motivo']] = true;
        }

        $resumen = [
            ['etiqueta' => 'Mermas', 'valor' => count($filas)],
            ['etiqueta' => 'Insumos afectados', 'valor' => count($insumos)],
            ['etiqueta' => 'Motivos distintos', 'valor' => count($motivos)],
        ];
    }

    if ($tipo === 'stock_bajo') {
        $condiciones = ['i.activo = 1 AND i.cantidad_actual <= i.stock_minimo'];
        $parametros = [];

        if ($filtros['categoria'] > 0) {
            $condiciones[] = 'i.id_categoria = :categoria';
            $parametros[':categoria'] = $filtros['categoria'];
        }
        if ($filtros['insumo'] > 0) {
            $condiciones[] = 'i.id_insumo = :insumo';
            $parametros[':insumo'] = $filtros['insumo'];
        }

        $where = implode(' AND ', $condiciones);
        $stmt = $pdo->prepare(
            "SELECT i.nombre AS insumo, c.nombre AS categoria,
                    i.cantidad_actual, i.stock_minimo, i.unidad_medida AS unidad,
                    GREATEST((i.stock_minimo * 2) - i.cantidad_actual, 0) AS sugerido_reabastecer
             FROM insumos i
             INNER JOIN categorias c ON c.id_categoria = i.id_categoria
             WHERE {$where}
             ORDER BY (i.cantidad_actual <= 0) DESC,
                      (i.cantidad_actual / NULLIF(i.stock_minimo, 0)) ASC,
                      i.nombre ASC
             LIMIT 1000"
        );
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $columnas = [
            ['clave' => 'insumo', 'etiqueta' => 'Insumo', 'tipo' => 'texto'],
            ['clave' => 'categoria', 'etiqueta' => 'Categoría', 'tipo' => 'texto'],
            ['clave' => 'cantidad_actual', 'etiqueta' => 'Stock actual', 'tipo' => 'cantidad', 'clase' => 'num'],
            ['clave' => 'stock_minimo', 'etiqueta' => 'Stock mínimo', 'tipo' => 'cantidad', 'clase' => 'num'],
            ['clave' => 'unidad', 'etiqueta' => 'Unidad', 'tipo' => 'texto'],
            ['clave' => 'sugerido_reabastecer', 'etiqueta' => 'Sugerido a pedir', 'tipo' => 'cantidad', 'clase' => 'num'],
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'estado_stock'],
        ];

        $agotados = 0;
        $enMinimo = 0;
        foreach ($filas as $fila) {
            if ((float) $fila['cantidad_actual'] <= 0) {
                $agotados++;
            } elseif (abs((float) $fila['cantidad_actual'] - (float) $fila['stock_minimo']) < 0.0005) {
                $enMinimo++;
            }
        }

        $resumen = [
            ['etiqueta' => 'Insumos en alerta', 'valor' => count($filas)],
            ['etiqueta' => 'Agotados', 'valor' => $agotados],
            ['etiqueta' => 'Exactamente en mínimo', 'valor' => $enMinimo],
        ];
    }

    if ($tipo === 'caducidades') {
        $condiciones = ['i.activo = 1', 'l.cantidad_actual > 0', 'l.fecha_caducidad IS NOT NULL'];
        $parametros = [];

        if ($filtros['dias'] === '0') {
            $condiciones[] = 'l.fecha_caducidad < CURDATE()';
        } elseif ($filtros['dias'] !== 'todos') {
            $dias = (int) $filtros['dias'];
            $condiciones[] = "l.fecha_caducidad <= DATE_ADD(CURDATE(), INTERVAL {$dias} DAY)";
        }
        if ($filtros['categoria'] > 0) {
            $condiciones[] = 'i.id_categoria = :categoria';
            $parametros[':categoria'] = $filtros['categoria'];
        }
        if ($filtros['insumo'] > 0) {
            $condiciones[] = 'l.id_insumo = :insumo';
            $parametros[':insumo'] = $filtros['insumo'];
        }

        $where = implode(' AND ', $condiciones);
        $stmt = $pdo->prepare(
            "SELECT l.numero_lote, i.nombre AS insumo, c.nombre AS categoria,
                    l.cantidad_actual, i.unidad_medida AS unidad, l.fecha_caducidad,
                    DATEDIFF(l.fecha_caducidad, CURDATE()) AS dias,
                    l.fecha_registro
             FROM lotes l
             INNER JOIN insumos i ON i.id_insumo = l.id_insumo
             INNER JOIN categorias c ON c.id_categoria = i.id_categoria
             WHERE {$where}
             ORDER BY l.fecha_caducidad ASC, i.nombre ASC
             LIMIT 1000"
        );
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $columnas = [
            ['clave' => 'numero_lote', 'etiqueta' => 'Lote', 'tipo' => 'texto'],
            ['clave' => 'insumo', 'etiqueta' => 'Insumo', 'tipo' => 'texto'],
            ['clave' => 'categoria', 'etiqueta' => 'Categoría', 'tipo' => 'texto'],
            ['clave' => 'cantidad_actual', 'etiqueta' => 'Cantidad en lote', 'tipo' => 'cantidad', 'clase' => 'num'],
            ['clave' => 'unidad', 'etiqueta' => 'Unidad', 'tipo' => 'texto'],
            ['clave' => 'fecha_caducidad', 'etiqueta' => 'Caducidad', 'tipo' => 'fecha'],
            ['clave' => 'dias', 'etiqueta' => 'Días restantes', 'tipo' => 'dias_caducidad'],
            ['clave' => 'fecha_registro', 'etiqueta' => 'Registro del lote', 'tipo' => 'fecha_hora'],
        ];

        $caducados = 0;
        $porVencer = 0;
        foreach ($filas as $fila) {
            $dias = (int) $fila['dias'];
            if ($dias < 0) {
                $caducados++;
            } elseif ($dias <= 7) {
                $porVencer++;
            }
        }

        $resumen = [
            ['etiqueta' => 'Lotes listados', 'valor' => count($filas)],
            ['etiqueta' => 'Caducados', 'valor' => $caducados],
            ['etiqueta' => 'Vencen en 7 días', 'valor' => $porVencer],
        ];
    }

    if ($tipo === 'productos') {
        $condiciones = ['DATE(mp.fecha_movimiento) BETWEEN :desde AND :hasta'];
        $parametros = [':desde' => $filtros['desde'], ':hasta' => $filtros['hasta']];

        if ($filtros['mov_producto'] !== '') {
            $condiciones[] = 'mp.tipo = :mov';
            $parametros[':mov'] = $filtros['mov_producto'];
        }
        if ($filtros['producto'] > 0) {
            $condiciones[] = 'mp.id_producto = :producto';
            $parametros[':producto'] = $filtros['producto'];
        }

        $where = implode(' AND ', $condiciones);
        $stmt = $pdo->prepare(
            "SELECT mp.fecha_movimiento, p.nombre AS producto, mp.tipo, mp.cantidad,
                    mp.stock_anterior, mp.stock_nuevo, u.nombre_completo AS usuario,
                    mp.observaciones
             FROM movimientos_producto mp
             INNER JOIN productos p ON p.id_producto = mp.id_producto
             INNER JOIN usuarios u ON u.id_usuario = mp.id_usuario
             WHERE {$where}
             ORDER BY mp.fecha_movimiento DESC, mp.id_movimiento_producto DESC
             LIMIT 2000"
        );
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $columnas = [
            ['clave' => 'fecha_movimiento', 'etiqueta' => 'Fecha y hora', 'tipo' => 'fecha_hora'],
            ['clave' => 'producto', 'etiqueta' => 'Producto', 'tipo' => 'texto'],
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'movimiento_producto'],
            ['clave' => 'cantidad', 'etiqueta' => 'Unidades', 'tipo' => 'entero', 'clase' => 'num'],
            ['clave' => 'stock_anterior', 'etiqueta' => 'Stock anterior', 'tipo' => 'entero', 'clase' => 'num'],
            ['clave' => 'stock_nuevo', 'etiqueta' => 'Stock nuevo', 'tipo' => 'entero', 'clase' => 'num'],
            ['clave' => 'usuario', 'etiqueta' => 'Usuario', 'tipo' => 'texto'],
            ['clave' => 'observaciones', 'etiqueta' => 'Observaciones', 'tipo' => 'texto'],
        ];

        $produccion = 0;
        $ventas = 0;
        $ajustes = 0;
        $unidadesVendidas = 0;
        foreach ($filas as $fila) {
            if ($fila['tipo'] === 'Producción') {
                $produccion++;
            } elseif ($fila['tipo'] === 'Venta') {
                $ventas++;
                $unidadesVendidas += (int) $fila['cantidad'];
            } else {
                $ajustes++;
            }
        }

        $resumen = [
            ['etiqueta' => 'Movimientos', 'valor' => count($filas)],
            ['etiqueta' => 'Producción', 'valor' => $produccion],
            ['etiqueta' => 'Ventas', 'valor' => $ventas],
            ['etiqueta' => 'Ajustes', 'valor' => $ajustes],
            ['etiqueta' => 'Unidades vendidas', 'valor' => $unidadesVendidas],
        ];
    }

    return [
        'tipo'        => $tipo,
        'titulo'      => $def['titulo'],
        'descripcion' => $def['descripcion'],
        'icono'       => $def['icono'],
        'periodo'     => reporte_periodo($filtros),
        'columnas'    => $columnas,
        'filas'       => $filas,
        'resumen'     => $resumen,
    ];
}

/**
 * Formatea un valor de celda para la vista HTML.
 */
function reporte_valor_texto(mixed $valor, string $tipo): string
{
    return match ($tipo) {
        'fecha'      => formatear_fecha((string) $valor),
        'fecha_hora' => formatear_fecha_hora((string) $valor),
        'cantidad'   => formatear_cantidad($valor),
        'entero'     => number_format((int) $valor),
        'moneda'     => formatear_moneda($valor),
        default      => (string) $valor,
    };
}

/**
 * Formatea un valor de celda para exportación CSV.
 */
function reporte_valor_csv(array $fila, array $columna): string
{
    $clave = $columna['clave'];
    $tipo = $columna['tipo'];
    $valor = $fila[$clave] ?? '';

    if ($tipo === 'estado_stock') {
        [, $texto] = estado_stock((float) ($fila['cantidad_actual'] ?? 0), (float) ($fila['stock_minimo'] ?? 0));
        return $texto;
    }

    if ($tipo === 'dias_caducidad') {
        $dias = (int) $valor;
        if ($dias < 0) {
            return 'Caducado hace ' . abs($dias) . ' día(s)';
        }
        if ($dias === 0) {
            return 'Caduca hoy';
        }
        return 'Vence en ' . $dias . ' día(s)';
    }

    return match ($tipo) {
        'fecha'      => formatear_fecha((string) $valor),
        'fecha_hora' => formatear_fecha_hora((string) $valor),
        'cantidad'   => rtrim(rtrim(number_format((float) $valor, 3, '.', ''), '0'), '.'),
        'entero'     => (string) (int) $valor,
        'moneda'     => number_format((float) $valor, 2, '.', ''),
        default      => (string) $valor,
    };
}

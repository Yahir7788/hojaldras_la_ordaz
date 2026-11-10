<?php
/**
 * Resumen y consultas de alertas de inventario.
 */

declare(strict_types=1);

function resumen_alertas(PDO $pdo): array
{
    $sql = "SELECT
        (SELECT COUNT(*) FROM insumos WHERE activo = 1 AND cantidad_actual > 0 AND cantidad_actual <= stock_minimo) AS stock_bajo,
        (SELECT COUNT(*) FROM insumos WHERE activo = 1 AND cantidad_actual <= 0) AS agotados,
        (SELECT COUNT(DISTINCT l.id_insumo)
           FROM lotes l
           INNER JOIN insumos i ON i.id_insumo = l.id_insumo
          WHERE i.activo = 1 AND l.cantidad_actual > 0
            AND l.fecha_caducidad IS NOT NULL
            AND l.fecha_caducidad BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) AS por_vencer,
        (SELECT COUNT(DISTINCT l.id_insumo)
           FROM lotes l
           INNER JOIN insumos i ON i.id_insumo = l.id_insumo
          WHERE i.activo = 1 AND l.cantidad_actual > 0
            AND l.fecha_caducidad IS NOT NULL
            AND l.fecha_caducidad < CURDATE()) AS caducados";

    $fila = $pdo->query($sql)->fetch() ?: [];

    $stockBajo = (int) ($fila['stock_bajo'] ?? 0);
    $agotados = (int) ($fila['agotados'] ?? 0);
    $porVencer = (int) ($fila['por_vencer'] ?? 0);
    $caducados = (int) ($fila['caducados'] ?? 0);

    return [
        'stock_bajo'  => $stockBajo,
        'agotados'    => $agotados,
        'por_vencer'  => $porVencer,
        'caducados'   => $caducados,
        'criticos'    => $stockBajo + $agotados,
        'total'       => $stockBajo + $agotados + $porVencer + $caducados,
    ];
}

function insumos_con_stock_bajo(PDO $pdo): array
{
    return $pdo->query(
        "SELECT i.*, c.nombre AS categoria, c.perecedero,
                GREATEST((i.stock_minimo * 2) - i.cantidad_actual, 0) AS sugerido_reabastecer
         FROM insumos i
         INNER JOIN categorias c ON c.id_categoria = i.id_categoria
         WHERE i.activo = 1 AND i.cantidad_actual <= i.stock_minimo
         ORDER BY (i.cantidad_actual <= 0) DESC, (i.cantidad_actual / NULLIF(i.stock_minimo, 0)) ASC, i.nombre ASC"
    )->fetchAll();
}

function lotes_por_vencer(PDO $pdo, int $dias = 7): array
{
    $dias = max(1, min(365, $dias));
    return $pdo->query(
        "SELECT l.*, i.nombre AS insumo, i.unidad_medida, c.nombre AS categoria
         FROM lotes l
         INNER JOIN insumos i ON i.id_insumo = l.id_insumo
         INNER JOIN categorias c ON c.id_categoria = i.id_categoria
         WHERE i.activo = 1 AND l.cantidad_actual > 0
           AND l.fecha_caducidad IS NOT NULL
           AND l.fecha_caducidad BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$dias} DAY)
         ORDER BY l.fecha_caducidad ASC, i.nombre ASC"
    )->fetchAll();
}

function lotes_caducados(PDO $pdo): array
{
    return $pdo->query(
        "SELECT l.*, i.nombre AS insumo, i.unidad_medida, c.nombre AS categoria,
                DATEDIFF(CURDATE(), l.fecha_caducidad) AS dias_caducado
         FROM lotes l
         INNER JOIN insumos i ON i.id_insumo = l.id_insumo
         INNER JOIN categorias c ON c.id_categoria = i.id_categoria
         WHERE i.activo = 1 AND l.cantidad_actual > 0
           AND l.fecha_caducidad IS NOT NULL
           AND l.fecha_caducidad < CURDATE()
         ORDER BY l.fecha_caducidad ASC, i.nombre ASC"
    )->fetchAll();
}

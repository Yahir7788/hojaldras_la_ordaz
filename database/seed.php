<?php
/**
 * Script de desarrollo: genera datos de ejemplo coherentes con el inventario.
 * Se ejecuta una sola vez sobre una base recién importada.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';

$pdo = db();

$insumos = [
    ['Harina', 'Harina de trigo para hojaldres y pizzas.', 1, 'kg', 10, 25, 18, null, 3, 500, 'Desperdicio en producción', 'Restos de masa no aprovechados.'],
    ['Azúcar', 'Azúcar refinada para masas y rellenos.', 1, 'kg', 5, 8, 4.5, null, 2, 200, 'Producto dañado', 'Bolsa con humedad.'],
    ['Levadura', 'Levadura fresca para fermentación.', 2, 'kg', 1, 2.5, 1.2, 10, 2, 0, null, null],
    ['Sal', 'Sal fina de mesa.', 1, 'kg', 1, 3, 2.5, null, 1, 0, null, null],
    ['Aceite', 'Aceite vegetal para freído y horneado.', 1, 'L', 5, 12, 8, null, 3, 300, 'Desperdicio en producción', 'Aceite desechado tras la jornada.'],
    ['Mantequilla', 'Mantequilla para hojaldre.', 2, 'kg', 2, 5, 2.8, 20, 2, 0, null, null],
    ['Margarina', 'Margarina vegetal para repostería.', 2, 'kg', 2, 4, 1.5, 25, 3, 0, null, null],
    ['Jamón', 'Jamón de pavo para volovanes.', 2, 'kg', 1, 3, 2.2, 8, 2, 0, null, null],
    ['Queso', 'Queso mozzarella para pizzas y volovanes.', 2, 'kg', 2, 4, 3.2, 4, 1, 0, null, null],
    ['Pollo', 'Pollo deshebrado para rellenos.', 2, 'kg', 2, 5, 4, 6, 2, 0, null, null],
    ['Carne al pastor', 'Carne al pastor para pizzas.', 2, 'kg', 2, 4, 3, 20, 2, 0, null, null],
    ['Atún', 'Atún en agua para volovanes.', 2, 'kg', 1, 2, 1.5, -2, 0, 500, 'Caducidad', 'Lote vencido detectado en revisión.'],
    ['Salsas', 'Salsa de tomate y aderezos.', 2, 'L', 1, 3, 2.4, 45, 1, 0, null, null],
    ['Vegetales', 'Cebolla, tomate y chile para preparaciones.', 2, 'kg', 2, 6, 4.5, 2, 2, 300, 'Deterioro', 'Vegetales marchitos.'],
    ['Masa de hojaldre', 'Masa de hojaldre congelada.', 3, 'kg', 4, 12, 7, 60, 3, 400, 'Deterioro', 'Masa expuesta a descongelación.'],
    ['Masa de volován', 'Masa de volován congelada.', 3, 'kg', 3, 8, 2.5, 30, 3, 0, null, null],
    ['Cajas', 'Cajas de cartón para empaque.', 4, 'pza', 30, 100, 75, null, 2, 0, null, null],
    ['Bolsas', 'Bolsas de papel para alimentos.', 4, 'pza', 50, 250, 180, null, 3, 0, null, null],
    ['Papel para alimentos', 'Papel encerado para envolver.', 4, 'pza', 40, 200, 0, null, 3, 12000, 'Producto dañado', 'Papel manchado por humedad.'],
    ['Embutidos', 'Salchicha y embutidos para volovanes.', 2, 'kg', 1, 3, 2.3, 12, 2, 0, null, null],
];

$destinos = [
    1 => 'Cocina - panadería',
    2 => 'Cocina - preparación',
    3 => 'Cocina - producción',
    4 => 'Mostrador',
];

$hoy = new DateTimeImmutable('today');
$entradaFecha = $hoy->modify('-15 days');

$insertarInsumo = $pdo->prepare(
    'INSERT INTO insumos (nombre, descripcion, id_categoria, unidad_medida, cantidad_actual, stock_minimo, fecha_registro)
     VALUES (:nombre, :descripcion, :categoria, :unidad, 0, :minimo, :registro)'
);
$insertarEntrada = $pdo->prepare(
    'INSERT INTO entradas (id_insumo, cantidad, unidad_medida, fecha_ingreso, fecha_caducidad, numero_lote,
                           id_usuario, observaciones, fecha_registro)
     VALUES (:insumo, :cantidad, :unidad, :ingreso, :caducidad, :lote, 1, :observaciones, :registro)'
);
$insertarLote = $pdo->prepare(
    'INSERT INTO lotes (id_insumo, id_entrada, numero_lote, cantidad_inicial, cantidad_actual, fecha_caducidad, fecha_registro)
     VALUES (:insumo, :entrada, :lote, :cantidad_inicial, :cantidad_actual, :caducidad, :registro)'
);
$actualizarStock = $pdo->prepare('UPDATE insumos SET cantidad_actual = :cantidad WHERE id_insumo = :insumo');
$insertarSalida = $pdo->prepare(
    'INSERT INTO salidas (id_insumo, cantidad, fecha, destino, id_usuario, observaciones, fecha_registro)
     VALUES (:insumo, :cantidad, :fecha, :destino, :usuario, :observaciones, :registro)'
);
$insertarMerma = $pdo->prepare(
    'INSERT INTO mermas (id_insumo, cantidad, motivo, fecha, descripcion, id_usuario, fecha_registro)
     VALUES (:insumo, :cantidad, :motivo, :fecha, :descripcion, :usuario, :registro)'
);
$insertarMov = $pdo->prepare(
    'INSERT INTO movimientos_inventario
        (id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, id_referencia, tabla_referencia,
         id_usuario, fecha_movimiento, observaciones)
     VALUES (:insumo, :tipo, :cantidad, :anterior, :nuevo, :referencia, :tabla, :usuario, :fecha, :observaciones)'
);

function miles(float $numero): int
{
    return (int) round($numero * 1000);
}

function partir(int $totalMil, int $partes): array
{
    if ($partes <= 0) {
        return [];
    }
    $pesos = [45, 30, 15, 10];
    $pesos = array_slice($pesos, 0, $partes);
    $sumaPesos = array_sum($pesos);
    $resultado = [];
    $acumulado = 0;

    foreach ($pesos as $indice => $peso) {
        if ($indice === count($pesos) - 1) {
            $parte = $totalMil - $acumulado;
        } else {
            $parte = (int) floor($totalMil * $peso / $sumaPesos);
            $acumulado += $parte;
        }
        if ($parte > 0) {
            $resultado[] = $parte;
        }
    }
    return $resultado;
}

$pdo->beginTransaction();

try {
    foreach ($insumos as $indice => $datos) {
        [$nombre, $descripcion, $categoria, $unidad, $minimo, $entrada, $final, $diasCaducidad, $numSalidas, $mermaMil, $motivo, $descMerma] = $datos;

        $caducidad = $diasCaducidad === null ? null : $hoy->modify(($diasCaducidad >= 0 ? '+' : '') . $diasCaducidad . ' days')->format('Y-m-d');
        $numeroLote = 'LOTE-' . $entradaFecha->format('Ym') . '-' . str_pad((string) ($indice + 1), 3, '0', STR_PAD_LEFT);

        $insertarInsumo->execute([
            ':nombre'      => $nombre,
            ':descripcion' => $descripcion,
            ':categoria'   => $categoria,
            ':unidad'      => $unidad,
            ':minimo'      => $minimo,
            ':registro'    => $entradaFecha->format('Y-m-d 07:30:00'),
        ]);
        $idInsumo = (int) $pdo->lastInsertId();

        $insertarEntrada->execute([
            ':insumo'       => $idInsumo,
            ':cantidad'     => $entrada,
            ':unidad'       => $unidad,
            ':ingreso'      => $entradaFecha->format('Y-m-d'),
            ':caducidad'    => $caducidad,
            ':lote'         => $numeroLote,
            ':observaciones' => 'Compra inicial de materia prima.',
            ':registro'     => $entradaFecha->format('Y-m-d 08:00:00'),
        ]);
        $idEntrada = (int) $pdo->lastInsertId();

        $insertarLote->execute([
            ':insumo'          => $idInsumo,
            ':entrada'         => $idEntrada,
            ':lote'            => $numeroLote,
            ':cantidad_inicial' => $entrada,
            ':cantidad_actual' => $entrada,
            ':caducidad'       => $caducidad,
            ':registro'        => $entradaFecha->format('Y-m-d 08:00:00'),
        ]);

        $actualizarStock->execute([':cantidad' => $entrada, ':insumo' => $idInsumo]);
        $insertarMov->execute([
            ':insumo'       => $idInsumo,
            ':tipo'         => 'Entrada',
            ':cantidad'     => $entrada,
            ':anterior'     => 0,
            ':nuevo'        => $entrada,
            ':referencia'   => $idEntrada,
            ':tabla'        => 'entradas',
            ':usuario'      => 1,
            ':fecha'        => $entradaFecha->format('Y-m-d 08:00:00'),
            ':observaciones' => 'Entrada inicial de ' . $nombre . '.',
        ]);

        $consumidoMil = miles((float) $entrada) - miles((float) $final);
        $salidaMil = $consumidoMil - $mermaMil;
        if ($salidaMil < 0) {
            throw new RuntimeException('Los datos de ejemplo no son coherentes para ' . $nombre);
        }

        $eventos = [];
        $partes = partir($salidaMil, (int) $numSalidas);
        $desplazamientos = [6, 4, 2, 1];
        foreach ($partes as $i => $parteMil) {
            $fecha = $hoy->modify('-' . $desplazamientos[$i] . ' days')->format('Y-m-d')
                . ' ' . ['10:15:00', '12:40:00', '16:05:00', '09:20:00'][$i];
            $eventos[] = [
                'tipo'  => 'Salida',
                'mil'   => $parteMil,
                'fecha' => $fecha,
                'extra' => $destinos[$categoria],
            ];
        }
        if ($mermaMil > 0) {
            $fechaMerma = $hoy->modify('-3 days')->format('Y-m-d') . ' 17:30:00';
            $eventos[] = [
                'tipo'  => 'Merma',
                'mil'   => $mermaMil,
                'fecha' => $fechaMerma,
                'extra' => $motivo,
                'desc'  => $descMerma,
            ];
        }

        usort($eventos, static fn(array $a, array $b): int => strcmp($a['fecha'], $b['fecha']));

        $stockMil = miles((float) $entrada);
        foreach ($eventos as $evento) {
            $cantidad = $evento['mil'] / 1000;
            $anterior = $stockMil / 1000;
            $nuevoMil = $stockMil - $evento['mil'];
            if ($nuevoMil < 0) {
                throw new RuntimeException('Stock negativo en datos de ejemplo para ' . $nombre);
            }
            $nuevo = $nuevoMil / 1000;

            consumir_lotes($pdo, $idInsumo, $cantidad);
            $actualizarStock->execute([':cantidad' => $nuevo, ':insumo' => $idInsumo]);

            if ($evento['tipo'] === 'Salida') {
                $insertarSalida->execute([
                    ':insumo'       => $idInsumo,
                    ':cantidad'     => $cantidad,
                    ':fecha'        => substr($evento['fecha'], 0, 10),
                    ':destino'      => $evento['extra'],
                    ':usuario'      => 2,
                    ':observaciones' => 'Consumo para producción del día.',
                    ':registro'     => $evento['fecha'],
                ]);
                $idRef = (int) $pdo->lastInsertId();
                $tabla = 'salidas';
                $obs = 'Salida a ' . $evento['extra'] . '.';
                $usuario = 2;
            } else {
                $insertarMerma->execute([
                    ':insumo'      => $idInsumo,
                    ':cantidad'    => $cantidad,
                    ':motivo'      => $evento['extra'],
                    ':fecha'       => substr($evento['fecha'], 0, 10),
                    ':descripcion' => $evento['desc'],
                    ':usuario'     => 2,
                    ':registro'    => $evento['fecha'],
                ]);
                $idRef = (int) $pdo->lastInsertId();
                $tabla = 'mermas';
                $obs = 'Merma por ' . strtolower($evento['extra']) . '.';
                $usuario = 2;
            }

            $insertarMov->execute([
                ':insumo'       => $idInsumo,
                ':tipo'         => $evento['tipo'],
                ':cantidad'     => $cantidad,
                ':anterior'     => $anterior,
                ':nuevo'        => $nuevo,
                ':referencia'   => $idRef,
                ':tabla'        => $tabla,
                ':usuario'      => $usuario,
                ':fecha'        => $evento['fecha'],
                ':observaciones' => $obs,
            ]);

            $stockMil = $nuevoMil;
        }

        if ($stockMil !== miles((float) $final)) {
            throw new RuntimeException('El stock final no coincide para ' . $nombre);
        }
    }

    // Movimientos de producto terminado
    $productos = [
        [1, 36, 12, 4, 1],
        [2, 24, 6, 4, 1],
        [3, 15, 6, 3, 1],
    ];
    $insertarMovProd = $pdo->prepare(
        'INSERT INTO movimientos_producto
            (id_producto, tipo, cantidad, stock_anterior, stock_nuevo, id_usuario, fecha_movimiento, observaciones)
         VALUES (:producto, :tipo, :cantidad, :anterior, :nuevo, :usuario, :fecha, :observaciones)'
    );

    foreach ($productos as [$idProducto, $produccion, $ventas, $diasProd, $diasVenta]) {
        $fechaProd = $hoy->modify('-' . $diasProd . ' days')->format('Y-m-d 09:00:00');
        $fechaVenta = $hoy->modify('-' . $diasVenta . ' days')->format('Y-m-d 13:15:00');
        $insertarMovProd->execute([
            ':producto' => $idProducto, ':tipo' => 'Producción', ':cantidad' => $produccion,
            ':anterior' => 0, ':nuevo' => $produccion, ':usuario' => 2,
            ':fecha' => $fechaProd, ':observaciones' => 'Producción de la jornada.',
        ]);
        $insertarMovProd->execute([
            ':producto' => $idProducto, ':tipo' => 'Venta', ':cantidad' => $ventas,
            ':anterior' => $produccion, ':nuevo' => $produccion - $ventas, ':usuario' => 3,
            ':fecha' => $fechaVenta, ':observaciones' => 'Ventas en mostrador.',
        ]);
    }

    $pdo->commit();
    echo "Datos de ejemplo generados correctamente.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

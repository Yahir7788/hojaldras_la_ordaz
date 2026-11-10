<?php
/**
 * Exportación CSV de reportes (solo gerente).
 * Respeta los mismos filtros que la vista y descarga un archivo compatible
 * con Excel en español (UTF-8 con BOM y separador punto y coma).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requerir_roles(['gerente']);
require_once __DIR__ . '/_reportes.php';

$pdo = db();
$filtros = reportes_filtros_normalizados($_GET);
$reporte = construir_reporte($pdo, $filtros);

$nombreArchivo = 'reporte_' . $filtros['tipo'] . '_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$salida = fopen('php://output', 'wb');
if ($salida === false) {
    http_response_code(500);
    exit('No fue posible generar el archivo.');
}

// BOM para que Excel reconozca UTF-8.
fwrite($salida, "\xEF\xBB\xBF");

$escribir = static function (array $fila) use ($salida): void {
    fputcsv($salida, $fila, ';', '"', '\\');
};

// Encabezado institucional del reporte.
$escribir([APP_NOMBRE]);
$escribir([APP_LEMA]);
$escribir([$reporte['titulo'], $reporte['periodo']]);
$escribir(['Generado el', formatear_fecha_hora(date('Y-m-d H:i:s')), 'por', usuario_actual()['nombre_completo']]);
$escribir([]);

// Resumen del reporte.
if ($reporte['resumen']) {
    $escribir(['Resumen']);
    foreach ($reporte['resumen'] as $item) {
        $escribir([$item['etiqueta'], $item['valor']]);
    }
    $escribir([]);
}

// Tabla de datos.
$escribir(array_map(static fn (array $columna): string => $columna['etiqueta'], $reporte['columnas']));

foreach ($reporte['filas'] as $fila) {
    $valores = [];
    foreach ($reporte['columnas'] as $columna) {
        $valores[] = reporte_valor_csv($fila, $columna);
    }
    $escribir($valores);
}

if (!$reporte['filas']) {
    $escribir(['Sin resultados para los filtros seleccionados']);
}

fclose($salida);
exit;

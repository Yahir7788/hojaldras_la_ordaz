<?php
/**
 * Funciones compartidas: escapes, URLs, sesión, CSRF, validaciones,
 * operaciones de inventario y utilidades de presentación.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Salida segura y navegación
// ---------------------------------------------------------------------------
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(string $ruta = ''): string
{
    $ruta = ltrim($ruta, '/');
    return BASE_PATH . ($ruta === '' ? '/' : '/' . $ruta);
}

function redirigir(string $ruta): never
{
    header('Location: ' . base_url($ruta));
    exit;
}

function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function requerir_post(): void
{
    if (!es_post()) {
        http_response_code(405);
        exit('Método no permitido.');
    }
}

function post(string $clave, mixed $defecto = ''): mixed
{
    return $_POST[$clave] ?? $defecto;
}

function get(string $clave, mixed $defecto = ''): mixed
{
    return $_GET[$clave] ?? $defecto;
}

// ---------------------------------------------------------------------------
// Mensajes flash (notificaciones)
// ---------------------------------------------------------------------------
function flash(string $tipo, string $mensaje): void
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function obtener_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($flashes) ? $flashes : [];
}

// ---------------------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verificar_csrf(): void
{
    if (!es_post()) {
        return;
    }

    $enviado = (string) ($_POST['_csrf'] ?? '');
    $guardado = (string) ($_SESSION['csrf_token'] ?? '');

    if ($enviado === '' || $guardado === '' || !hash_equals($guardado, $enviado)) {
        http_response_code(419);
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Sesión expirada</title>'
            . '<style>body{font-family:Segoe UI,Arial,sans-serif;background:#f6efe3;color:#3a2a20;display:flex;'
            . 'min-height:100vh;align-items:center;justify-content:center;margin:0}div{max-width:520px;background:#fff;'
            . 'padding:36px;border-radius:16px;box-shadow:0 12px 40px rgba(59,36,23,.12);border-top:5px solid #b23a3a}'
            . 'a{display:inline-block;margin-top:18px;background:#4b2e1e;color:#fff;padding:10px 18px;border-radius:8px;'
            . 'text-decoration:none}</style></head><body><div><h1>Sesión expirada</h1>'
            . '<p>El formulario ya no es válido o la sesión terminó. Recarga la página e inténtalo de nuevo.</p>'
            . '<a href="' . e(base_url('dashboard/index.php')) . '">Volver al sistema</a></div></body></html>';
        exit;
    }
}

// ---------------------------------------------------------------------------
// Usuario y roles
// ---------------------------------------------------------------------------
function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function tiene_rol(string ...$roles): bool
{
    $usuario = usuario_actual();
    return $usuario !== null && in_array($usuario['rol'] ?? '', $roles, true);
}

function es_gerente(): bool
{
    return tiene_rol('gerente');
}

function es_cocinero(): bool
{
    return tiene_rol('cocinero');
}

function es_ventas(): bool
{
    return tiene_rol('ventas');
}

function etiqueta_rol(string $rol): string
{
    return [
        'gerente'  => 'Gerente',
        'cocinero' => 'Cocinero',
        'ventas'   => 'Personal de ventas',
    ][$rol] ?? ucfirst($rol);
}

function icono_rol(string $rol): string
{
    return [
        'gerente'  => 'bi-shield-check',
        'cocinero' => 'bi-fire',
        'ventas'   => 'bi-shop',
    ][$rol] ?? 'bi-person';
}

function iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre)) ?: [];
    $iniciales = '';
    foreach (array_slice($partes, 0, 2) as $parte) {
        $iniciales .= mb_strtoupper(mb_substr($parte, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return $iniciales !== '' ? $iniciales : 'LO';
}

// ---------------------------------------------------------------------------
// Formatos
// ---------------------------------------------------------------------------
function formatear_cantidad(float|int|string|null $cantidad, int $decimales = 3): string
{
    $numero = (float) $cantidad;
    $texto = number_format($numero, $decimales, '.', ',');
    $texto = rtrim(rtrim($texto, '0'), '.');
    return $texto === '' || $texto === '-0' ? '0' : $texto;
}

function formatear_moneda(float|int|string|null $cantidad): string
{
    return '$' . number_format((float) $cantidad, 2, '.', ',');
}

function formatear_fecha(?string $fecha): string
{
    if ($fecha === null || $fecha === '' || str_starts_with($fecha, '0000-00-00')) {
        return '—';
    }
    $ts = strtotime($fecha);
    return $ts ? date('d/m/Y', $ts) : '—';
}

function formatear_fecha_hora(?string $fecha): string
{
    if ($fecha === null || $fecha === '') {
        return '—';
    }
    $ts = strtotime($fecha);
    return $ts ? date('d/m/Y H:i', $ts) : '—';
}

function fecha_hoy(): string
{
    return date('Y-m-d');
}

function strftime_es(): string
{
    $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto',
              'septiembre', 'octubre', 'noviembre', 'diciembre'];

    return ucfirst($dias[(int) date('w')]) . ', ' . (int) date('d') . ' de '
        . $meses[(int) date('n') - 1] . ' de ' . date('Y');
}

function validar_fecha(string $fecha): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    return $dt !== false && $dt->format('Y-m-d') === $fecha;
}

function dias_para_caducar(?string $fecha): ?int
{
    if ($fecha === null || $fecha === '') {
        return null;
    }
    $hoy = new DateTimeImmutable('today');
    $destino = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    if ($destino === false) {
        return null;
    }
    return (int) $hoy->diff($destino)->format('%r%a');
}

function estado_vencimiento(?string $fecha): array
{
    if ($fecha === null || $fecha === '') {
        return ['secondary', 'Sin caducidad', 'bi-infinity'];
    }
    $dias = dias_para_caducar($fecha);
    if ($dias === null) {
        return ['secondary', 'Sin caducidad', 'bi-infinity'];
    }
    if ($dias < 0) {
        return ['danger', 'Caducado hace ' . abs($dias) . ' día' . (abs($dias) === 1 ? '' : 's'), 'bi-x-octagon'];
    }
    if ($dias === 0) {
        return ['danger', 'Caduca hoy', 'bi-exclamation-octagon'];
    }
    if ($dias <= 7) {
        return ['warning', 'Vence en ' . $dias . ' día' . ($dias === 1 ? '' : 's'), 'bi-clock-history'];
    }
    if ($dias <= 30) {
        return ['info', 'Vence en ' . $dias . ' días', 'bi-calendar-event'];
    }
    return ['success', 'Vigente', 'bi-check-circle'];
}

function estado_stock(float|int|string $cantidad, float|int|string $minimo): array
{
    $cantidad = (float) $cantidad;
    $minimo = (float) $minimo;

    if ($cantidad <= 0) {
        return ['danger', 'Agotado', 'bi-x-octagon'];
    }
    if ($cantidad < $minimo) {
        return ['danger', 'Crítico', 'bi-exclamation-octagon'];
    }
    if (abs($cantidad - $minimo) < 0.0005) {
        return ['warning', 'En mínimo', 'bi-exclamation-triangle'];
    }
    return ['success', 'Disponible', 'bi-check-circle'];
}

function unidades_medida(): array
{
    return [
        'kg'      => 'Kilogramo (kg)',
        'g'       => 'Gramo (g)',
        'L'       => 'Litro (L)',
        'ml'      => 'Mililitro (ml)',
        'pza'     => 'Pieza (pza)',
        'caja'    => 'Caja (caja)',
        'paquete' => 'Paquete (paquete)',
        'bolsa'   => 'Bolsa (bolsa)',
        'rollo'   => 'Rollo (rollo)',
    ];
}

function etiqueta_unidad(string $unidad): string
{
    return unidades_medida()[$unidad] ?? $unidad;
}

function motivos_merma(): array
{
    return [
        'Caducidad'                 => 'Caducidad',
        'Deterioro'                 => 'Deterioro',
        'Desperdicio en producción' => 'Desperdicio en producción',
        'Producto dañado'           => 'Producto dañado',
    ];
}

// ---------------------------------------------------------------------------
// Inventario: movimientos y lotes (siempre dentro de una transacción)
// ---------------------------------------------------------------------------
function registrar_movimiento(
    PDO $pdo,
    int $idInsumo,
    string $tipo,
    float $cantidad,
    float $stockAnterior,
    float $stockNuevo,
    ?int $idReferencia,
    ?string $tablaReferencia,
    int $idUsuario,
    ?string $observaciones = null
): void {
    $sql = 'INSERT INTO movimientos_inventario
                (id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, id_referencia,
                 tabla_referencia, id_usuario, observaciones)
            VALUES (:insumo, :tipo, :cantidad, :anterior, :nuevo, :referencia,
                    :tabla, :usuario, :observaciones)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':insumo'       => $idInsumo,
        ':tipo'         => $tipo,
        ':cantidad'     => $cantidad,
        ':anterior'     => $stockAnterior,
        ':nuevo'        => $stockNuevo,
        ':referencia'   => $idReferencia,
        ':tabla'        => $tablaReferencia,
        ':usuario'      => $idUsuario,
        ':observaciones' => $observaciones,
    ]);
}

/**
 * Descuenta una cantidad de los lotes del insumo aplicando FEFO
 * (primero en caducar, primero en salir). Debe ejecutarse dentro de una
 * transacción y con la fila del insumo bloqueada con SELECT ... FOR UPDATE.
 */
function consumir_lotes(PDO $pdo, int $idInsumo, float $cantidad): void
{
    $restante = round($cantidad, 3);

    $stmt = $pdo->prepare(
        'SELECT id_lote, cantidad_actual
         FROM lotes
         WHERE id_insumo = :insumo AND cantidad_actual > 0
         ORDER BY (fecha_caducidad IS NULL) ASC, fecha_caducidad ASC, id_lote ASC
         FOR UPDATE'
    );
    $stmt->execute([':insumo' => $idInsumo]);
    $lotes = $stmt->fetchAll();

    $actualizar = $pdo->prepare('UPDATE lotes SET cantidad_actual = :cantidad WHERE id_lote = :lote');

    foreach ($lotes as $lote) {
        if ($restante <= 0.0004) {
            break;
        }
        $disponible = round((float) $lote['cantidad_actual'], 3);
        $usar = min($disponible, $restante);
        if ($usar <= 0) {
            continue;
        }
        $actualizar->execute([
            ':cantidad' => round($disponible - $usar, 3),
            ':lote'     => (int) $lote['id_lote'],
        ]);
        $restante = round($restante - $usar, 3);
    }

    if ($restante > 0.0004) {
        throw new RuntimeException('No hay lotes suficientes para respaldar la cantidad solicitada. Revisa el inventario del insumo.');
    }
}

/**
 * Crea un lote para un ajuste positivo de inventario (sin entrada asociada).
 */
function crear_lote_ajuste(
    PDO $pdo,
    int $idInsumo,
    float $cantidad,
    int $idUsuario,
    ?string $fechaCaducidad = null
): int {
    $numero = 'AJUSTE-' . date('Ymd-His') . '-' . random_int(100, 999);
    $stmt = $pdo->prepare(
        'INSERT INTO lotes (id_insumo, id_entrada, numero_lote, cantidad_inicial, cantidad_actual, fecha_caducidad)
         VALUES (:insumo, NULL, :numero, :inicial, :actual, :caducidad)'
    );
    $stmt->execute([
        ':insumo'    => $idInsumo,
        ':numero'    => $numero,
        ':inicial'   => $cantidad,
        ':actual'    => $cantidad,
        ':caducidad' => $fechaCaducidad ?: null,
    ]);
    return (int) $pdo->lastInsertId();
}

/**
 * Obtiene un insumo bloqueado para actualización.
 */
function bloquear_insumo(PDO $pdo, int $idInsumo): ?array
{
    $stmt = $pdo->prepare(
        'SELECT i.*, c.nombre AS categoria, c.perecedero
         FROM insumos i
         INNER JOIN categorias c ON c.id_categoria = i.id_categoria
         WHERE i.id_insumo = :id
         FOR UPDATE'
    );
    $stmt->execute([':id' => $idInsumo]);
    $insumo = $stmt->fetch();
    return $insumo ?: null;
}

// ---------------------------------------------------------------------------
// Validaciones de formularios
// ---------------------------------------------------------------------------
function cantidad_positiva(mixed $valor): ?float
{
    $texto = str_replace(',', '.', trim((string) $valor));
    if ($texto === '' || !is_numeric($texto)) {
        return null;
    }
    $numero = (float) $texto;
    return $numero > 0 ? round($numero, 3) : null;
}

function cantidad_no_negativa(mixed $valor): ?float
{
    $texto = str_replace(',', '.', trim((string) $valor));
    if ($texto === '' || !is_numeric($texto)) {
        return null;
    }
    $numero = (float) $texto;
    return $numero >= 0 ? round($numero, 3) : null;
}

function entero_positivo(mixed $valor): ?int
{
    $texto = trim((string) $valor);
    if ($texto === '' || !ctype_digit($texto)) {
        return null;
    }
    $numero = (int) $texto;
    return $numero > 0 ? $numero : null;
}

function texto_requerido(mixed $valor, int $minimo = 1, int $maximo = 255): ?string
{
    $texto = trim((string) $valor);
    $longitud = mb_strlen($texto, 'UTF-8');
    if ($longitud < $minimo || $longitud > $maximo) {
        return null;
    }
    return $texto;
}

function opcion_valida(mixed $valor, array $opciones): ?string
{
    $texto = (string) $valor;
    return array_key_exists($texto, $opciones) ? $texto : null;
}

// ---------------------------------------------------------------------------
// Utilidades de consulta
// ---------------------------------------------------------------------------
function obtener_insumos_activos(PDO $pdo): array
{
    return $pdo->query(
        'SELECT i.id_insumo, i.nombre, i.unidad_medida, i.cantidad_actual, i.stock_minimo,
                c.nombre AS categoria
         FROM insumos i
         INNER JOIN categorias c ON c.id_categoria = i.id_categoria
         WHERE i.activo = 1
         ORDER BY i.nombre ASC'
    )->fetchAll();
}

function obtener_categorias(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM categorias ORDER BY nombre ASC')->fetchAll();
}

// ---------------------------------------------------------------------------
// Usuarios: validaciones de seguridad
// ---------------------------------------------------------------------------
/**
 * Valida la fortaleza mínima de una contraseña.
 * Devuelve el mensaje de error o null cuando es válida.
 */
function validar_contrasena(string $contrasena): ?string
{
    $longitud = mb_strlen($contrasena, 'UTF-8');

    if ($longitud < 8) {
        return 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($longitud > 72) {
        return 'La contraseña no puede superar los 72 caracteres.';
    }
    if (!preg_match('/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/u', $contrasena)) {
        return 'La contraseña debe incluir al menos una letra.';
    }
    if (!preg_match('/\d/', $contrasena)) {
        return 'La contraseña debe incluir al menos un número.';
    }

    return null;
}

/**
 * Cuenta los gerentes activos. Permite excluir a un usuario para validar
 * que nunca se elimine el último gerente activo del sistema.
 */
function contar_gerentes_activos(PDO $pdo, ?int $excluirId = null): int
{
    $sql = 'SELECT COUNT(*)
            FROM usuarios u
            INNER JOIN roles r ON r.id_rol = u.id_rol
            WHERE r.nombre = :rol AND u.activo = 1';
    $parametros = [':rol' => 'gerente'];

    if ($excluirId !== null) {
        $sql .= ' AND u.id_usuario <> :excluir';
        $parametros[':excluir'] = $excluirId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    return (int) $stmt->fetchColumn();
}

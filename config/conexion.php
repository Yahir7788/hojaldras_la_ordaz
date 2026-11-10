<?php
/**
 * Conexión PDO a MySQL.
 *
 * Los valores pueden sobreescribirse con variables de entorno, lo que facilita
 * usar otra instalación sin editar el código:
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
 *
 * En una instalación estándar de XAMPP los valores por defecto funcionan:
 *   host 127.0.0.1, puerto 3306, usuario root, sin contraseña.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'hojaldras_la_ordaz');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Devuelve la conexión PDO única de la petición.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        // Alinear NOW()/CURDATE() de MySQL con la zona horaria de PHP.
        $pdo->exec("SET time_zone = " . $pdo->quote(date('P')));
    } catch (PDOException $e) {
        error_log('[La Ordaz] Error de conexión: ' . $e->getMessage());

        if (APP_DEBUG) {
            $detalle = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $mensaje = "No fue posible conectar con MySQL.<br><small>{$detalle}</small>";
        } else {
            $mensaje = 'No fue posible conectar con la base de datos. Verifica que MySQL esté activo en XAMPP y que la base de datos <strong>hojaldras_la_ordaz</strong> esté importada.';
        }

        http_response_code(500);
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Error de conexión</title>'
            . '<style>body{font-family:Segoe UI,Arial,sans-serif;background:#f6efe3;color:#3a2a20;display:flex;'
            . 'min-height:100vh;align-items:center;justify-content:center;margin:0}div{max-width:560px;background:#fff;'
            . 'padding:36px;border-radius:16px;box-shadow:0 12px 40px rgba(59,36,23,.12);border-top:5px solid #c8963e}'
            . 'h1{font-size:20px;margin:0 0 12px}p{line-height:1.6;color:#6f5a4b}</style></head><body><div>'
            . '<h1>Hojaldras "La Ordaz"</h1><p>' . $mensaje . '</p></div></body></html>';
        exit;
    }

    return $pdo;
}

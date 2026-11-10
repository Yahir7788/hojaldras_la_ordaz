<?php
/**
 * Configuración general del sistema Hojaldras "La Ordaz".
 * Este archivo se carga en todas las páginas (web y API interna).
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Entorno y errores
// ---------------------------------------------------------------------------
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN));

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

// ---------------------------------------------------------------------------
// Identidad del sistema
// ---------------------------------------------------------------------------
define('APP_NOMBRE', 'HOJALDRAS "LA ORDAZ"');
define('APP_LEMA', 'Sistema de Gestión de Almacén e Inventarios');
define('APP_VERSION', '1.0.0');
define('APP_ZONA_HORARIA', 'America/Mexico_City');

date_default_timezone_set(APP_ZONA_HORARIA);

// ---------------------------------------------------------------------------
// Ruta base (funciona en la raíz del dominio o dentro de /hojaldras_la_ordaz)
// ---------------------------------------------------------------------------
$projectRoot = str_replace('\\', '/', dirname(__DIR__));
$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== ''
    ? str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT']))
    : '';
$basePath = '';
if ($documentRoot !== '') {
    $documentRoot = rtrim($documentRoot, '/');
    if (str_starts_with($projectRoot, $documentRoot)) {
        $basePath = substr($projectRoot, strlen($documentRoot));
    }
}
define('BASE_PATH', rtrim($basePath, '/'));

// ---------------------------------------------------------------------------
// Sesión segura
// ---------------------------------------------------------------------------
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('LAORDAZ_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_PATH === '' ? '/' : BASE_PATH . '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---------------------------------------------------------------------------
// Cabeceras de seguridad básicas
// ---------------------------------------------------------------------------
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

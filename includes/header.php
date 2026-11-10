<?php
/**
 * Cabecera común del panel administrativo.
 *
 * Variables esperadas antes de incluir:
 *   $pageTitle    Título de la página
 *   $pageSubtitle Subtítulo opcional
 *   $pageIcon     Clase de Bootstrap Icons (ej. bi-box-seam)
 *   $pageActions  HTML opcional de botones de acción
 */

if (!function_exists('usuario_actual')) {
    require_once __DIR__ . '/auth.php';
}

$pdo = $pdo ?? db();
$usuario = usuario_actual();
$pageTitle = $pageTitle ?? 'Panel';
$pageSubtitle = $pageSubtitle ?? '';
$pageIcon = $pageIcon ?? 'bi-grid-1x2';
$pageActions = $pageActions ?? '';
$alertasResumen = puede_ver_alertas() ? resumen_alertas($pdo) : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3b2417">
    <title><?= e($pageTitle) ?> · <?= e(APP_NOMBRE) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(base_url('assets/img/logo.svg')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/estilos.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    <div class="app-main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="btn btn-icon topbar-toggle" id="sidebarToggle" type="button"
                        aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
                    <i class="bi bi-list"></i>
                </button>
                <div class="topbar-context">
                    <span class="topbar-kicker"><?= e(APP_NOMBRE) ?></span>
                    <span class="topbar-date"><i class="bi bi-calendar3 me-1"></i><?= e(strftime_es()) ?></span>
                </div>
            </div>

            <div class="topbar-right">
                <?php if ($alertasResumen !== null): ?>
                    <a class="topbar-alert <?= $alertasResumen['total'] > 0 ? 'has-alerts' : '' ?>"
                       href="<?= e(base_url('alertas/index.php')) ?>"
                       title="Alertas de inventario">
                        <i class="bi bi-bell"></i>
                        <?php if ($alertasResumen['total'] > 0): ?>
                            <span class="topbar-alert-badge"><?= (int) $alertasResumen['total'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="user-chip dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <span class="avatar"><?= e(iniciales($usuario['nombre_completo'])) ?></span>
                        <span class="user-chip-text d-none d-sm-flex">
                            <strong><?= e($usuario['nombre_completo']) ?></strong>
                            <small><?= e(etiqueta_rol($usuario['rol'])) ?></small>
                        </span>
                        <i class="bi bi-chevron-down user-chevron"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li class="dropdown-header">
                            <i class="bi <?= e(icono_rol($usuario['rol'])) ?> me-1"></i>
                            <?= e(etiqueta_rol($usuario['rol'])) ?>
                        </li>
                        <li><span class="dropdown-item-text small text-muted">@<?= e($usuario['usuario']) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="<?= e(base_url('auth/logout.php')) ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="content">
            <div class="page-heading">
                <div class="page-heading-text">
                    <span class="page-heading-icon"><i class="bi <?= e($pageIcon) ?>"></i></span>
                    <div>
                        <h1><?= e($pageTitle) ?></h1>
                        <?php if ($pageSubtitle !== ''): ?>
                            <p><?= e($pageSubtitle) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($pageActions !== ''): ?>
                    <div class="page-heading-actions"><?= $pageActions ?></div>
                <?php endif; ?>
            </div>

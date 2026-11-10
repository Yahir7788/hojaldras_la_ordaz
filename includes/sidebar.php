<?php
/**
 * Menú lateral. Requiere $usuario y $alertasResumen definidos en header.php.
 */

$scriptActual = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

$menuActivo = static function (string $carpeta) use ($scriptActual): bool {
    return str_contains($scriptActual, '/' . $carpeta . '/');
};

$alertaTotal = (int) ($alertasResumen['total'] ?? 0);
?>
<aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="<?= e(base_url('dashboard/index.php')) ?>">
        <img src="<?= e(base_url('assets/img/logo.svg')) ?>" alt="Logo Hojaldras La Ordaz" class="sidebar-logo">
        <span class="sidebar-brand-text">
            <strong>HOJALDRAS</strong>
            <span>“LA ORDAZ”</span>
            <small>Almacén e inventarios</small>
        </span>
    </a>

    <div class="sidebar-scroll">
        <nav class="sidebar-nav" aria-label="Menú principal">
            <span class="sidebar-section">Principal</span>
            <a class="sidebar-link <?= $menuActivo('dashboard') ? 'active' : '' ?>"
               href="<?= e(base_url('dashboard/index.php')) ?>">
                <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
            </a>

            <?php if (tiene_rol('gerente', 'cocinero')): ?>
                <span class="sidebar-section">Almacén</span>
                <a class="sidebar-link <?= $menuActivo('insumos') ? 'active' : '' ?>"
                   href="<?= e(base_url('insumos/index.php')) ?>">
                    <i class="bi bi-box-seam"></i><span>Insumos</span>
                </a>
            <?php endif; ?>

            <?php if (tiene_rol('gerente')): ?>
                <a class="sidebar-link <?= $menuActivo('entradas') ? 'active' : '' ?>"
                   href="<?= e(base_url('entradas/index.php')) ?>">
                    <i class="bi bi-box-arrow-in-down"></i><span>Entradas</span>
                </a>
            <?php endif; ?>

            <?php if (tiene_rol('gerente', 'cocinero')): ?>
                <a class="sidebar-link <?= $menuActivo('salidas') ? 'active' : '' ?>"
                   href="<?= e(base_url('salidas/index.php')) ?>">
                    <i class="bi bi-box-arrow-up"></i><span>Salidas</span>
                </a>
                <a class="sidebar-link <?= $menuActivo('mermas') ? 'active' : '' ?>"
                   href="<?= e(base_url('mermas/index.php')) ?>">
                    <i class="bi bi-trash3"></i><span>Mermas</span>
                </a>
                <a class="sidebar-link <?= $menuActivo('alertas') ? 'active' : '' ?>"
                   href="<?= e(base_url('alertas/index.php')) ?>">
                    <i class="bi bi-exclamation-triangle"></i><span>Alertas</span>
                    <?php if ($alertaTotal > 0): ?>
                        <span class="sidebar-badge"><?= $alertaTotal ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <?php if (tiene_rol('gerente', 'ventas')): ?>
                <span class="sidebar-section">Producción</span>
                <a class="sidebar-link <?= $menuActivo('productos') ? 'active' : '' ?>"
                   href="<?= e(base_url('productos/index.php')) ?>">
                    <i class="bi bi-basket2"></i><span>Producto terminado</span>
                </a>
            <?php endif; ?>

            <?php if (tiene_rol('gerente')): ?>
                <span class="sidebar-section">Análisis</span>
                <a class="sidebar-link <?= $menuActivo('reportes') ? 'active' : '' ?>"
                   href="<?= e(base_url('reportes/index.php')) ?>">
                    <i class="bi bi-file-earmark-bar-graph"></i><span>Reportes</span>
                </a>

                <span class="sidebar-section">Administración</span>
                <a class="sidebar-link <?= $menuActivo('usuarios') ? 'active' : '' ?>"
                   href="<?= e(base_url('usuarios/index.php')) ?>">
                    <i class="bi bi-people"></i><span>Usuarios</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <span class="avatar avatar-sm"><?= e(iniciales($usuario['nombre_completo'])) ?></span>
            <div>
                <strong><?= e($usuario['nombre_completo']) ?></strong>
                <small><i class="bi <?= e(icono_rol($usuario['rol'])) ?> me-1"></i><?= e(etiqueta_rol($usuario['rol'])) ?></small>
            </div>
        </div>
        <span class="sidebar-version">Versión <?= e(APP_VERSION) ?></span>
    </div>
</aside>

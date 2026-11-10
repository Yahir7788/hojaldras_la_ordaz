<?php
/**
 * Punto de entrada del sistema.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

if (usuario_actual() !== null) {
    redirigir('dashboard/index.php');
}

redirigir('auth/login.php');

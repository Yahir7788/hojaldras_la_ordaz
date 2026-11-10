/* ==========================================================================
   HOJALDRAS "LA ORDAZ" — Interacciones de la interfaz
   ========================================================================== */
(function () {
    'use strict';

    // -----------------------------------------------------------------------
    // Menú lateral responsive
    // -----------------------------------------------------------------------
    const body = document.body;
    const toggle = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');

    function cerrarMenu() {
        body.classList.remove('sidebar-open');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            const abierto = body.classList.toggle('sidebar-open');
            toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        });
    }
    if (backdrop) backdrop.addEventListener('click', cerrarMenu);

    document.querySelectorAll('.sidebar a').forEach(function (enlace) {
        enlace.addEventListener('click', function () {
            if (window.innerWidth < 992) cerrarMenu();
        });
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') cerrarMenu();
    });

    // -----------------------------------------------------------------------
    // Notificaciones Bootstrap
    // -----------------------------------------------------------------------
    document.querySelectorAll('.app-toast').forEach(function (elemento) {
        if (window.bootstrap && bootstrap.Toast) {
            new bootstrap.Toast(elemento, { delay: 6000 }).show();
        }
    });

    // -----------------------------------------------------------------------
    // Modal de confirmación para operaciones destructivas
    // -----------------------------------------------------------------------
    const modalElemento = document.getElementById('confirmModal');
    const modalTexto = document.getElementById('confirmModalText');
    const modalTitulo = document.getElementById('confirmModalLabel');
    const modalAceptar = document.getElementById('confirmModalAccept');
    let formularioPendiente = null;

    if (modalElemento && window.bootstrap) {
        const modal = new bootstrap.Modal(modalElemento);

        document.querySelectorAll('form[data-confirm]').forEach(function (formulario) {
            formulario.addEventListener('submit', function (evento) {
                if (formulario.dataset.confirmado === '1') {
                    return;
                }
                evento.preventDefault();
                formularioPendiente = formulario;
                modalTexto.textContent = formulario.dataset.confirm || '¿Deseas continuar con esta operación?';
                modalTitulo.textContent = formulario.dataset.confirmTitle || 'Confirmar operación';
                modalAceptar.className = 'btn ' + (formulario.dataset.confirmButton || 'btn-danger');
                modalAceptar.innerHTML = '<i class="bi bi-check2 me-1"></i>' +
                    (formulario.dataset.confirmAccept || 'Sí, continuar');
                modal.show();
            });
        });

        if (modalAceptar) {
            modalAceptar.addEventListener('click', function () {
                if (!formularioPendiente) return;
                formularioPendiente.dataset.confirmado = '1';
                modal.hide();
                const boton = formularioPendiente.querySelector('button[type="submit"]');
                if (boton) {
                    boton.disabled = true;
                    boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando…';
                }
                formularioPendiente.requestSubmit
                    ? formularioPendiente.requestSubmit()
                    : formularioPendiente.submit();
            });
        }

        modalElemento.addEventListener('hidden.bs.modal', function () {
            if (formularioPendiente && formularioPendiente.dataset.confirmado !== '1') {
                formularioPendiente = null;
            }
        });
    }

    // -----------------------------------------------------------------------
    // Evitar doble envío en formularios normales
    // -----------------------------------------------------------------------
    document.querySelectorAll('form[data-submit-once]').forEach(function (formulario) {
        formulario.addEventListener('submit', function () {
            const boton = formulario.querySelector('button[type="submit"]');
            if (boton && !boton.disabled) {
                setTimeout(function () {
                    boton.disabled = true;
                    boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando…';
                }, 10);
            }
        });
    });

    // -----------------------------------------------------------------------
    // Mostrar/ocultar contraseña
    // -----------------------------------------------------------------------
    document.querySelectorAll('[data-toggle-password]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const objetivo = document.getElementById(boton.dataset.togglePassword);
            if (!objetivo) return;
            const mostrar = objetivo.type === 'password';
            objetivo.type = mostrar ? 'text' : 'password';
            const icono = boton.querySelector('i');
            if (icono) icono.className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    // -----------------------------------------------------------------------
    // Rellenar credenciales de demostración
    // -----------------------------------------------------------------------
    document.querySelectorAll('[data-demo-user]').forEach(function (boton) {
        boton.addEventListener('click', function (evento) {
            evento.preventDefault();
            const usuario = document.getElementById('usuario');
            const contrasena = document.getElementById('contrasena');
            if (usuario) usuario.value = boton.dataset.demoUser;
            if (contrasena) contrasena.value = boton.dataset.demoPass;
            if (usuario) usuario.focus();
        });
    });

    // -----------------------------------------------------------------------
    // Botones de impresión
    // -----------------------------------------------------------------------
    document.querySelectorAll('[data-print]').forEach(function (boton) {
        boton.addEventListener('click', function (evento) {
            evento.preventDefault();
            window.print();
        });
    });

    // -----------------------------------------------------------------------
    // Tooltips
    // -----------------------------------------------------------------------
    if (window.bootstrap && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (elemento) {
            new bootstrap.Tooltip(elemento);
        });
    }

    // -----------------------------------------------------------------------
    // Gráficas: valores por defecto
    // -----------------------------------------------------------------------
    if (window.Chart) {
        Chart.defaults.font.family = '"Segoe UI", "Helvetica Neue", Arial, sans-serif';
        Chart.defaults.font.size = 11;
        Chart.defaults.color = '#8b7565';
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.boxWidth = 7;
        Chart.defaults.plugins.legend.labels.padding = 14;
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(46, 26, 16, .94)';
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;
        Chart.defaults.plugins.tooltip.titleFont = { size: 12, weight: '600' };
    }
})();

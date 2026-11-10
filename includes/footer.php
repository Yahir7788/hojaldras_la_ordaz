        </main>

        <footer class="app-footer">
            <span><i class="bi bi-shop me-1"></i><?= e(APP_NOMBRE) ?> · <?= e(APP_LEMA) ?></span>
            <span>© <?= date('Y') ?> · Versión <?= e(APP_VERSION) ?></span>
        </footer>
    </div>
</div>

<!-- Notificaciones -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer" aria-live="polite" aria-atomic="true">
    <?php foreach (obtener_flashes() as $flash): ?>
        <?php
        $tipoFlash = in_array($flash['tipo'], ['success', 'danger', 'warning', 'info'], true) ? $flash['tipo'] : 'info';
        $iconosFlash = [
            'success' => 'bi-check-circle-fill',
            'danger'  => 'bi-x-circle-fill',
            'warning' => 'bi-exclamation-triangle-fill',
            'info'    => 'bi-info-circle-fill',
        ];
        ?>
        <div class="toast app-toast toast-<?= e($tipoFlash) ?>" role="alert" aria-live="assertive" aria-atomic="true"
             data-bs-delay="6000">
            <div class="toast-header">
                <i class="bi <?= e($iconosFlash[$tipoFlash]) ?> me-2"></i>
                <strong class="me-auto"><?= $tipoFlash === 'success' ? 'Operación exitosa' : ($tipoFlash === 'danger' ? 'Atención' : 'Aviso') ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
            <div class="toast-body"><?= e($flash['mensaje']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Confirmación para operaciones importantes -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content confirm-modal">
            <div class="modal-body">
                <span class="confirm-icon"><i class="bi bi-exclamation-triangle"></i></span>
                <h2 id="confirmModalLabel">Confirmar operación</h2>
                <p id="confirmModalText">¿Deseas continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmModalAccept">
                    <i class="bi bi-check2 me-1"></i>Sí, continuar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= e(base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(base_url('assets/vendor/chartjs/chart.umd.min.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
<?= $pageScripts ?? '' ?>
</body>
</html>

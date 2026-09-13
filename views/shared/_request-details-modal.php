<?php

/** SHARED REQUEST DETAILS: one reusable read-only request modal per list page. */

/*
 * STYLE CRITIQUE DE LA MODALE : ce composant partagé enregistre lui-même sa
 * feuille de style. Ainsi, une ancienne instance d'AppAsset encore présente
 * dans OPcache ne peut pas livrer le nouveau HTML sans sa présentation. Yii
 * déduplique automatiquement cette URL lorsque plusieurs composants la demandent.
 */
$listSystemPath = Yii::getAlias('@webroot/css/list-system.css');
$listSystemVersion = is_file($listSystemPath) ? (string) filemtime($listSystemPath) : '1';
$this->registerCssFile(
    Yii::getAlias('@web/css/list-system.css') . '?v=' . rawurlencode($listSystemVersion)
);

$this->registerJs(<<<'JS'
var canRequestModal = document.getElementById('canRequestModal');
if (canRequestModal) {
    canRequestModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) return;
        var fields = ['request-id', 'aircraft', 'registration', 'serial', 'eta', 'etd', 'location', 'status', 'description'];
        fields.forEach(function (field) {
            var target = canRequestModal.querySelector('[data-request-field="' + field + '"]');
            if (target) target.textContent = trigger.getAttribute('data-' + field) || 'N/A';
        });
    });

    /* SHARED ROW INTERACTION: open details without intercepting real actions. */
    document.querySelectorAll('.can-request-summary').forEach(function (summary) {
        var row = summary.closest('tr');
        if (!row) return;

        row.classList.add('can-request-row');
        row.setAttribute('tabindex', '0');
        row.setAttribute('aria-label', 'View details for request ' + (summary.getAttribute('data-request-id') || ''));

        var openDetails = function () {
            bootstrap.Modal.getOrCreateInstance(canRequestModal).show(summary);
        };

        row.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea, label, .action-buttons')) return;
            openDetails();
        });

        row.addEventListener('keydown', function (event) {
            if ((event.key === 'Enter' || event.key === ' ') && event.target === row) {
                event.preventDefault();
                openDetails();
            }
        });
    });
}
JS, yii\web\View::POS_READY);
?>
<div class="modal fade can-request-modal" id="canRequestModal" tabindex="-1" aria-labelledby="canRequestModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="canRequestModalTitle"><i class="bi bi-airplane-engines"></i> Request Details <span data-request-field="request-id"></span></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="can-request-grid">
                    <!-- SHARED REQUEST DETAILS: color identifies each operational data group. -->
                    <?php foreach ([
                        'aircraft' => ['bi-airplane', 'Aircraft'],
                        'registration' => ['bi-card-text', 'Registration'],
                        'serial' => ['bi-upc-scan', 'Serial Number'],
                        'location' => ['bi-geo-alt', 'Maintenance Location'],
                        'status' => ['bi-activity', 'Request Status'],
                    ] as $field => [$icon, $label]): ?>
                        <div class="can-request-item is-<?= $field ?>">
                            <small><i class="bi <?= $icon ?>"></i> <?= $label ?></small>
                            <span class="can-request-value" data-request-field="<?= $field ?>">N/A</span>
                        </div>
                    <?php endforeach; ?>

                    <!-- SHARED MAINTENANCE WINDOW: ETA and ETD remain on one operational row. -->
                    <div class="can-flight-window">
                        <div class="can-flight-date is-eta">
                            <span class="can-flight-icon"><i class="bi bi-box-arrow-in-down-right"></i></span>
                            <span>
                                <small>ETA · Arrival</small>
                                <span class="can-flight-value" data-request-field="eta">N/A</span>
                            </span>
                        </div>
                        <div class="can-flight-route" aria-hidden="true">
                            <span></span><i class="bi bi-airplane"></i><span></span>
                        </div>
                        <div class="can-flight-date is-etd">
                            <span class="can-flight-icon"><i class="bi bi-box-arrow-up-right"></i></span>
                            <span>
                                <small>ETD · Departure</small>
                                <span class="can-flight-value" data-request-field="etd">N/A</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="can-request-description">
                    <small><i class="bi bi-info-circle"></i> Request Information</small>
                    <div data-request-field="description">N/A</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Close</button>
            </div>
        </div>
    </div>
</div>

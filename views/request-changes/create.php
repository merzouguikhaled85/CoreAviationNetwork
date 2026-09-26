<?php

use app\components\UrlIdHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\RequestChange */
/* @var $request app\models\Requests */
/* @var $application app\models\MroRequestApply */
/* @var $currentPo app\models\AoRequestsApplications|null */
/* @var $latestReport app\models\RepairReport|null */

$this->title = 'New change order - Request #' . $request->request_id;

/*
 * CONTEXTE EN LECTURE SEULE : toutes les informations affichées proviennent de
 * la Request et de son affectation actuelles. Cette préparation ne modifie aucun
 * statut, document ou périmètre métier.
 */
$aircraft = $request->getAircraft()->one();
$destinationAirport = $request->getDestinationAirport()->one();
$mro = $application ? $application->getMro() : null;
$aircraftName = $aircraft
    ? trim((string) ($aircraft->manufacturer ?? '') . ' ' . (string) ($aircraft->model ?? ''))
    : 'Aircraft unavailable';
$airportName = $destinationAirport
    ? trim((string) ($destinationAirport->icao ?? '') . ' - ' . (string) ($destinationAirport->airport_name ?? ''), ' -')
    : 'N/A';
$mroName = $mro
    ? ((string) ($mro->company_name ?? '') ?: (string) ($mro->username ?? '') ?: 'MRO partner')
    : 'MRO unavailable';
$etaText = $request->eta && strtotime((string) $request->eta)
    ? date('d M Y H:i', strtotime((string) $request->eta))
    : 'N/A';
$etdText = $request->etd && strtotime((string) $request->etd)
    ? date('d M Y H:i', strtotime((string) $request->etd))
    : 'N/A';
$statusText = ucwords(str_replace('_', ' ', (string) $request->status));
$statusClass = 'status-' . preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower((string) $request->status));
$priorityText = $request->getOperationalPriorityLabel();
$priorityClass = strtolower((string) ($request->operational_priority ?: 'routine'));
$priorityIcons = [
    'aog' => 'bi-exclamation-octagon',
    'urgent' => 'bi-lightning-charge',
    'routine' => 'bi-calendar-check',
];
$formatRequestDate = static function ($value) {
    return !empty($value) && strtotime((string) $value)
        ? date('d M Y H:i', strtotime((string) $value))
        : 'N/A';
};
$requestCreatedAt = $formatRequestDate($request->created_at ?? null);
$requestUpdatedAt = $formatRequestDate($request->updated_at ?? null);
$currentScope = trim((string) ($application->Description ?? '')) ?: 'No approved MRO scope description is available.';
$currentQuote = $application && $application->price !== null
    ? number_format((float) $application->price, 2) . ' ' . strtoupper((string) $application->currency)
    : 'Not available';
$hasCurrentPo = $currentPo && trim((string) $currentPo->po) !== '';
$hasCurrentQuoteDocument = $application && trim((string) $application->attachment) !== '';

$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['position' => \yii\web\View::POS_HEAD]
);
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    ['position' => \yii\web\View::POS_HEAD]
);
$this->registerCssFile(
    Yii::$app->request->baseUrl . '/css/mro-view-answer-refresh.css?v=20260926-4'
);
$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    ['position' => \yii\web\View::POS_END]
);

$this->registerCss(<<<CSS
:root {
    --change-primary: #0d6efd;
    --change-primary-dark: #0b5ed7;
    --change-primary-soft: #eaf2ff;
    --change-bg: #f5f7fa;
    --change-card: #ffffff;
    --change-border: #dbe5f0;
    --change-text: #0f172a;
    --change-muted: #64748b;
}

.change-order-page {
    min-height: calc(100vh - 64px);
    padding: 22px 24px 34px;
    background: var(--change-bg);
}

.change-order-shell {
    width: 100%;
    max-width: 1420px;
    margin: 0 auto;
}

/* ICÔNES : même bibliothèque que requests/update, avec la couleur primary. */
.change-order-page .bi {
    color: var(--change-primary);
}

.change-order-title .bi,
.section-heading h2 .bi {
    margin-right: 8px;
}

.change-btn-primary .bi,
.swal-change-confirm .bi {
    color: #ffffff;
}

.change-order-header,
.request-context-card,
.current-work-card,
.change-order-form-card {
    border: 1px solid var(--change-border);
    background: rgba(255, 255, 255, .98);
    box-shadow: 0 14px 36px rgba(15, 23, 42, .07);
}

.change-order-header {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 22px;
    margin-bottom: 18px;
    padding: 22px 24px;
    overflow: hidden;
    border-radius: 20px;
}

.change-order-header::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: var(--change-primary);
}

.change-order-heading {
    display: flex;
    align-items: center;
    gap: 15px;
}

.change-order-eyebrow {
    margin-bottom: 3px;
    color: var(--change-primary);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .11em;
    text-transform: uppercase;
}

.change-order-title {
    margin: 0;
    color: var(--change-text);
    font-size: clamp(24px, 2vw, 32px);
    font-weight: 850;
    letter-spacing: -.025em;
}

.change-order-subtitle {
    margin: 5px 0 0;
    color: var(--change-muted);
    font-size: 14px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.request-id-badge,
.workflow-status-badge,
.priority-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border-radius: 999px;
    font-weight: 800;
    white-space: nowrap;
}

.request-id-badge {
    padding: 9px 13px;
    color: var(--change-primary-dark);
    background: var(--change-primary-soft);
    border: 1px solid #b6d4fe;
    font-size: 13px;
}

.request-context-card,
.current-work-card,
.change-order-form-card {
    padding: 22px;
    border-radius: 20px;
}

.request-context-card {
    margin-bottom: 18px;
}

.current-work-card {
    margin-bottom: 18px;
}

.section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 17px;
}

.section-heading {
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-heading h2 {
    margin: 0;
    color: var(--change-text);
    font-size: 19px;
    font-weight: 850;
}

.section-heading p {
    margin: 3px 0 0;
    color: var(--change-muted);
    font-size: 13px;
}

.workflow-status-badge {
    padding: 8px 12px;
    color: var(--change-primary-dark);
    background: var(--change-primary-soft);
    border: 1px solid #b6d4fe;
    font-size: 12px;
}

.request-context-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.context-item {
    min-width: 0;
    min-height: 86px;
    padding: 13px 14px;
    border: 1px solid #dce6f1;
    border-radius: 14px;
    background: #ffffff;
}

.context-label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    color: #7b8ba1;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .07em;
    text-transform: uppercase;
}

.context-value {
    color: var(--change-text);
    font-size: 14px;
    font-weight: 800;
    line-height: 1.35;
    overflow-wrap: anywhere;
}

.context-value small {
    display: block;
    margin-top: 3px;
    color: var(--change-muted);
    font-size: 12px;
    font-weight: 600;
}

.priority-badge {
    padding: 5px 9px;
    font-size: 12px;
}

.priority-badge { color: var(--change-primary-dark); background: var(--change-primary-soft); }

.request-description {
    margin: 0 0 12px;
    padding: 15px 16px;
    border: 1px dashed #b9cbe0;
    border-radius: 14px;
    color: #334155;
    background: #f8fbff;
    font-size: 13px;
    line-height: 1.6;
}

.request-description strong {
    display: block;
    margin-bottom: 5px;
    color: var(--change-text);
    font-size: 11px;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.request-description-text {
    color: #334155;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.work-overview-grid {
    display: grid;
    grid-template-columns: 1.5fr repeat(3, minmax(0, .75fr));
    gap: 12px;
}

.work-overview-item {
    min-width: 0;
    padding: 15px 16px;
    border: 1px solid #dce6f1;
    border-radius: 15px;
    background: #ffffff;
}

.work-overview-item.scope-item {
    background: #ffffff;
}

.work-value {
    color: var(--change-text);
    font-size: 14px;
    font-weight: 850;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.work-scope-text {
    margin-top: 7px;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.55;
    white-space: pre-wrap;
}

.work-state {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 9px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 850;
}

.work-state.success,
.work-state.info,
.work-state.muted { color: var(--change-primary-dark); background: var(--change-primary-soft); }

.work-resource-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-top: 14px;
}

.work-resource-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 11px;
    border: 1px solid #cbd5e1;
    border-radius: 11px;
    color: #334155;
    background: #f8fafc;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none !important;
}

.work-resource-link:hover {
    color: var(--change-primary-dark);
    border-color: #86b7fe;
    background: var(--change-primary-soft);
}

.swal-change-popup {
    width: 380px !important;
    border-radius: 18px !important;
    padding: 20px !important;
}

.swal-change-popup .swal2-icon {
    width: 3.5em !important;
    height: 3.5em !important;
    margin: .6em auto .8em !important;
}

.swal-change-title {
    padding: 0 !important;
    font-size: 20px !important;
    font-weight: 800 !important;
    color: var(--change-text) !important;
}

.swal-change-html {
    margin: 10px 0 0 !important;
    font-size: 13px !important;
    line-height: 1.5 !important;
}

.swal-change-confirm,
.swal-change-cancel {
    border-radius: 11px !important;
    padding: 9px 15px !important;
    font-size: 13px !important;
    font-weight: 800 !important;
}

.swal-change-popup .swal2-actions {
    gap: 8px !important;
    margin-top: 16px !important;
}

.change-order-form-card .form-control {
    border: 1px solid #cbd8e6;
    border-radius: 13px;
    padding: 12px 14px;
    color: var(--change-text);
    background: #fbfdff;
    box-shadow: none;
    transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
}

.change-order-form-card .form-control:focus {
    border-color: #60a5fa;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
}

.change-order-form-card .control-label,
.change-order-form-card .form-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 7px;
    color: #1e293b !important;
    font-size: 13px !important;
    font-weight: 800 !important;
    line-height: 1.35;
}

.change-order-form-card .help-block,
.change-order-form-card .invalid-feedback {
    display: block;
    margin: 7px 0 0;
    color: #dc2626 !important;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.4;
}

.change-order-form-card .has-error .form-control,
.change-order-form-card .is-invalid {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, .08);
}

.change-order-form-card .has-error .control-label,
.change-order-form-card .has-error .form-label {
    color: #dc2626 !important;
}

.scope-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.scope-panel {
    padding: 16px;
    border-radius: 16px;
}

.scope-panel-add,
.scope-panel-remove {
    border: 1px solid #b6d4fe;
    background: #f8fbff;
}

.scope-panel .mb-3 { margin-bottom: 0 !important; }
.scope-panel-add .control-label,
.scope-panel-add .form-label,
.scope-panel-remove .control-label,
.scope-panel-remove .form-label { color: #1e293b !important; }

.workflow-note {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    margin: 17px 0 0;
    padding: 14px 16px;
    border: 1px solid #bae6fd;
    border-radius: 14px;
    color: #075985;
    background: #f0f9ff;
    font-size: 13px;
    line-height: 1.5;
}

.form-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid #e2e8f0;
}

.change-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 46px;
    padding: 0 19px;
    border-radius: 13px;
    font-size: 14px;
    font-weight: 850;
    text-decoration: none !important;
    transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
}

.change-btn-primary {
    border: 0;
    color: #ffffff !important;
    background: var(--change-primary);
    box-shadow: 0 11px 24px rgba(37, 99, 235, .24);
}

.change-btn-primary:hover { background: var(--change-primary-dark); transform: translateY(-1px); box-shadow: 0 15px 28px rgba(13, 110, 253, .25); }
.change-btn-secondary { color: #334155 !important; background: #ffffff; border: 1px solid #cbd5e1; }
.change-btn-secondary:hover { color: var(--change-primary) !important; border-color: var(--change-primary); background: var(--change-primary-soft); }

@media (max-width: 1050px) {
    .request-context-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .work-overview-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .work-overview-item.scope-item { grid-column: 1 / -1; }
}

@media (max-width: 720px) {
    .change-order-page { padding: 12px; }
    .change-order-header, .section-header { align-items: stretch; flex-direction: column; }
    .header-actions { justify-content: space-between; }
    .request-context-grid, .work-overview-grid, .scope-grid { grid-template-columns: 1fr; }
    .work-overview-item.scope-item { grid-column: auto; }
    .request-context-card, .current-work-card, .change-order-form-card { padding: 16px; border-radius: 16px; }
    .form-actions { align-items: stretch; flex-direction: column-reverse; }
    .change-btn { width: 100%; }
}
CSS);
?>

<main class="dash-content change-order-page answer-view-page change-order-create-page">
    <div class="change-order-shell">
        <header class="page-header-card">
            <div class="answer-header-copy">
                <div class="answer-title-row">
                    <?= Html::a('<i class="bi bi-arrow-left"></i>', ['requests/open-requests'], [
                        'class' => 'answer-back-link',
                        'aria-label' => 'Back to open requests',
                        'title' => 'Back to open requests',
                    ]) ?>
                    <h1 class="dash-title fw-bold">New Change Order</h1>
                    <span class="answer-priority priority-<?= Html::encode($priorityClass) ?>">
                        <i class="bi <?= Html::encode($priorityIcons[$priorityClass] ?? 'bi-calendar-check') ?>"></i>
                        <?= Html::encode($priorityText) ?>
                    </span>
                    <span class="answer-request-status <?= Html::encode($statusClass) ?>">
                        <i class="bi bi-circle-fill"></i>
                        <?= Html::encode($statusText) ?>
                    </span>
                </div>
                <div class="subtitle-text">Submit a controlled scope amendment to the currently assigned MRO.</div>
            </div>

            <div class="answer-header-side">
                <div class="answer-header-dates">
                    <span>Created: <strong><?= Html::encode($requestCreatedAt) ?></strong></span>
                    <span>Last updated: <strong><?= Html::encode($requestUpdatedAt) ?></strong></span>
                </div>
            </div>
        </header>

        <section class="answer-request-overview" aria-labelledby="current-request-title">
            <div class="answer-overview-head">
                <div>
                    <h2 id="current-request-title"><i class="bi bi-info-circle"></i>Request Overview</h2>
                    <p>Read-only operational context. The Request, assigned MRO and active work remain unchanged.</p>
                </div>
                <span class="answer-request-id"><i class="bi bi-hash"></i><?= Html::encode($request->request_id) ?></span>
            </div>

            <div class="answer-summary-grid">
                <article class="answer-summary-item answer-aircraft-item">
                    <div class="answer-summary-label"><i class="bi bi-airplane"></i>Aircraft</div>
                    <div class="answer-summary-value"><?= Html::encode($aircraftName) ?></div>
                    <div class="answer-aircraft-reference">
                        <span><?= Html::encode($request->aircraft_registration ?: 'N/A') ?></span>
                        <span>MSN <?= Html::encode($request->serial_number ?: 'N/A') ?></span>
                    </div>
                    <div class="answer-aircraft-image" role="img" aria-label="Aircraft maintenance network"></div>
                </article>

                <article class="answer-summary-item">
                    <div class="answer-summary-label"><i class="bi bi-building"></i>Assigned MRO</div>
                    <div class="answer-summary-value"><?= Html::encode($mroName) ?></div>
                </article>

                <article class="answer-summary-item">
                    <div class="answer-summary-label"><i class="bi bi-geo-alt"></i>Maintenance Location</div>
                    <div class="answer-summary-value"><?= Html::encode($request->location ?: 'N/A') ?></div>
                    <div class="answer-summary-secondary"><?= Html::encode($airportName) ?></div>
                </article>

                <article class="answer-summary-item">
                    <div class="answer-summary-label"><i class="bi bi-calendar2-week"></i>ETA / ETD</div>
                    <div class="answer-schedule">
                        <span><small>ETA</small><strong><?= Html::encode($etaText) ?></strong></span>
                        <span><small>ETD</small><strong><?= Html::encode($etdText) ?></strong></span>
                    </div>
                </article>

                <article class="answer-summary-item">
                    <div class="answer-summary-label"><i class="bi bi-broadcast-pin"></i>Operational Priority</div>
                    <span class="answer-priority priority-<?= Html::encode($priorityClass) ?>">
                        <i class="bi <?= Html::encode($priorityIcons[$priorityClass] ?? 'bi-calendar-check') ?>"></i>
                        <?= Html::encode($priorityText) ?>
                    </span>
                    <?php if (!empty($request->response_due_at_utc)): ?>
                        <div class="answer-summary-secondary">Response due <?= Html::encode(date('d M Y H:i', strtotime($request->response_due_at_utc))) ?> UTC</div>
                    <?php endif; ?>
                </article>

                <article class="answer-summary-item">
                    <div class="answer-summary-label"><i class="bi bi-cash-stack"></i>Current Quote</div>
                    <div class="answer-summary-value answer-quote-value"><?= Html::encode($currentQuote) ?></div>
                </article>

                <article class="answer-summary-item">
                    <div class="answer-summary-label"><i class="bi bi-activity"></i>Request Status</div>
                    <span class="answer-request-status <?= Html::encode($statusClass) ?>">
                        <i class="bi bi-circle-fill"></i>
                        <?= Html::encode($statusText) ?>
                    </span>
                </article>
            </div>

            <article class="answer-request-information">
                <div class="answer-summary-label"><i class="bi bi-file-text"></i>Request Informations</div>
                <div class="answer-request-text"><?= nl2br(Html::encode(trim((string) $request->request_details) ?: 'No request details provided.')) ?></div>
            </article>
        </section>

        <section class="current-work-card" aria-labelledby="current-work-title">
            <div class="section-header">
                <div class="section-heading">
                    <div>
                        <h2 id="current-work-title"><i class="bi bi-tools"></i>Current Work Details</h2>
                        <p>Approved commercial and operational baseline currently used by the assigned MRO.</p>
                    </div>
                </div>
                <span class="workflow-status-badge"><i class="bi bi-activity"></i><?= Html::encode($statusText) ?></span>
            </div>

            <div class="work-overview-grid">
                <div class="work-overview-item scope-item">
                    <div class="context-label"><i class="bi bi-list-check"></i>Approved MRO Scope</div>
                    <div class="work-scope-text"><?= Html::encode($currentScope) ?></div>
                </div>
                <div class="work-overview-item">
                    <div class="context-label"><i class="bi bi-cash-coin"></i>Current Quote</div>
                    <div class="work-value"><?= Html::encode($currentQuote) ?></div>
                    <span class="work-state <?= $hasCurrentQuoteDocument ? 'success' : 'muted' ?>">
                        <i class="bi <?= $hasCurrentQuoteDocument ? 'bi-file-earmark-check' : 'bi-file-earmark-minus' ?>"></i>
                        <?= $hasCurrentQuoteDocument ? 'Document available' : 'No document' ?>
                    </span>
                </div>
                <div class="work-overview-item">
                    <div class="context-label"><i class="bi bi-file-earmark-text"></i>Current PO</div>
                    <div class="work-value"><?= $hasCurrentPo ? 'Loaded' : 'Not available' ?></div>
                    <span class="work-state <?= $hasCurrentPo ? 'success' : 'muted' ?>">
                        <i class="bi <?= $hasCurrentPo ? 'bi-check-circle' : 'bi-clock' ?>"></i>
                        <?= $hasCurrentPo ? 'Active PO' : 'Awaiting PO' ?>
                    </span>
                </div>
                <div class="work-overview-item">
                    <div class="context-label"><i class="bi bi-clipboard2-pulse"></i>Work Report</div>
                    <div class="work-value"><?= $latestReport ? 'Report available' : 'Not submitted' ?></div>
                    <span class="work-state <?= $latestReport ? 'info' : 'muted' ?>">
                        <i class="bi <?= $latestReport ? 'bi-file-earmark-medical' : 'bi-hourglass-split' ?>"></i>
                        <?= $latestReport ? 'Latest report recorded' : 'Work still ongoing' ?>
                    </span>
                </div>
            </div>

            <div class="work-resource-actions">
                <?= Html::a(
                    '<i class="bi bi-receipt"></i> View Current Quote',
                    ['/mro-applications/view-answer', 'id' => UrlIdHelper::encode($application->id)],
                    ['class' => 'work-resource-link']
                ) ?>
                <?php if ($hasCurrentPo): ?>
                    <?= Html::a(
                        '<i class="bi bi-file-earmark-check"></i> View Current PO',
                        ['/requests/view-po', 'id' => UrlIdHelper::encode($request->request_id)],
                        ['class' => 'work-resource-link']
                    ) ?>
                <?php endif; ?>
                <?php if ($latestReport): ?>
                    <?= Html::a(
                        '<i class="bi bi-clipboard2-check"></i> View Work Reports',
                        ['/requests/view-reports', 'id' => UrlIdHelper::encode($request->request_id)],
                        ['class' => 'work-resource-link']
                    ) ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="change-order-form-card" aria-labelledby="change-order-form-title">
            <div class="section-header">
                <div class="section-heading">
                    <div>
                        <h2 id="change-order-form-title"><i class="bi bi-pencil-square"></i>Proposed Scope Change</h2>
                        <p>Describe only the amendment. The original approved scope remains active until final approval.</p>
                    </div>
                </div>
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'change-order-create-form',
                'options' => ['class' => 'change-order-form'],
            ]); ?>

            <?= $form->field($model, 'reason')->textarea([
                'rows' => 4,
                'placeholder' => 'Explain the operational or contractual reason for this change…',
            ]) ?>

            <div class="scope-grid">
                <div class="scope-panel scope-panel-add">
                    <?= $form->field($model, 'added_tasks')->textarea([
                        'rows' => 7,
                        'placeholder' => "One task per line\nExample: additional main landing gear inspection",
                    ])->label('<i class="bi bi-plus-circle"></i> Tasks to add', ['encode' => false]) ?>
                </div>
                <div class="scope-panel scope-panel-remove">
                    <?= $form->field($model, 'removed_tasks')->textarea([
                        'rows' => 7,
                        'placeholder' => "One task per line\nExample: visual inspection already covered by the original scope",
                    ])->label('<i class="bi bi-dash-circle"></i> Tasks to remove', ['encode' => false]) ?>
                </div>
            </div>

            <div class="workflow-note">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    <strong>Original scope remains active.</strong><br>
                    The proposed change becomes effective only after MRO approval, revised PO approval and revised quote approval.
                </div>
            </div>

            <div class="form-actions">
                <?= Html::a('<i class="bi bi-x-circle"></i> Cancel', ['requests/open-requests'], ['class' => 'change-btn change-btn-secondary']) ?>
                <?= Html::submitButton(
                    '<i class="bi bi-send-check"></i> Send Change Request to MRO',
                    ['class' => 'change-btn change-btn-primary', 'id' => 'create-change-order-button']
                ) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </section>
    </div>
</main>

<?php
/*
 * CONFIRMATION AVANT ÉCRITURE : beforeSubmit s'exécute après la validation
 * cliente Yii. La soumission native n'est déclenchée qu'après confirmation.
 */
$this->registerJs(<<<'JS'
(function () {
    var form = $('#change-order-create-form');
    var confirmed = false;

    form.on('beforeSubmit', function () {
        if (confirmed) {
            return true;
        }

        if (typeof Swal === 'undefined') {
            return window.confirm('Create this change order and send it to the assigned MRO?');
        }

        Swal.fire({
            title: 'Create this change order?',
            html: 'The scope change will be sent to the assigned MRO for review.<br><strong>The current work and original scope will remain active.</strong>',
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            confirmButtonText: '<i class="bi bi-check-circle"></i> Create Change Order',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            customClass: {
                popup: 'swal-change-popup',
                title: 'swal-change-title',
                htmlContainer: 'swal-change-html',
                confirmButton: 'swal-change-confirm',
                cancelButton: 'swal-change-cancel'
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                confirmed = true;
                form.get(0).submit();
            }
        });

        return false;
    });
})();
JS);
?>

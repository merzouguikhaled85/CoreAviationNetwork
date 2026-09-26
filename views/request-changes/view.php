<?php

use app\components\UrlIdHelper;
use app\models\RequestChange;
use app\models\RequestChangeDocument;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\RequestChange */

$this->title = 'Change Order v' . $model->version . ' - Request #' . $model->request_id;
$role = Yii::$app->session->get('user_type');
$encodedId = UrlIdHelper::encode($model->id);
$request = $model->request;
$application = $model->application;
$aircraft = $request ? $request->getAircraft()->one() : null;
$destinationAirport = $request ? $request->getDestinationAirport()->one() : null;
$mro = $application ? $application->getMro() : null;
$documents = $model->documents;

$aircraftName = $aircraft
    ? trim((string) ($aircraft->manufacturer ?? '') . ' ' . (string) ($aircraft->model ?? ''))
    : 'Aircraft unavailable';
$airportName = $destinationAirport
    ? trim((string) ($destinationAirport->icao ?? '') . ' - ' . (string) ($destinationAirport->airport_name ?? ''), ' -')
    : 'N/A';
$mroName = $mro
    ? ((string) ($mro->company_name ?? '') ?: (string) ($mro->username ?? '') ?: 'MRO partner')
    : 'MRO unavailable';
$operationalStatus = ucwords(str_replace('_', ' ', (string) ($request ? $request->status : $model->original_request_status)));
$requestStatusKey = (string) ($request ? $request->status : $model->original_request_status);
$requestStatusClass = 'request-status-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $requestStatusKey);
$operationalPriority = (string) ($request->operational_priority ?? 'routine');
$operationalPriorityLabel = $request && method_exists($request, 'getOperationalPriorityLabel')
    ? $request->getOperationalPriorityLabel()
    : ucfirst($operationalPriority);
$operationalPriorityIcons = [
    'aog' => 'bi-exclamation-octagon',
    'urgent' => 'bi-lightning-charge',
    'routine' => 'bi-calendar-check',
];
$changeStatusClass = $model->status === RequestChange::STATUS_COMPLETED
    ? 'status-success'
    : (in_array($model->status, [
        RequestChange::STATUS_REJECTED,
        RequestChange::STATUS_PO_REJECTED,
        RequestChange::STATUS_QUOTE_REJECTED,
        RequestChange::STATUS_CANCELLED,
    ], true) ? 'status-danger' : 'status-primary');
$backUrl = $role === 'mro' ? ['/mro-applications/index'] : ['/requests/open-requests'];
$formatDate = static function ($value) {
    return $value && strtotime((string) $value) ? date('d M Y H:i', strtotime((string) $value)) : 'N/A';
};
$changeRequestedAt = $formatDate($model->requested_at);
$changeUpdatedAt = $formatDate($model->updated_at);
$documentCount = count($documents);

$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['position' => \yii\web\View::POS_HEAD]
);
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    ['position' => \yii\web\View::POS_HEAD]
);
$this->registerCssFile(
    Yii::$app->request->baseUrl . '/css/request-change-view-refresh.css?v=20260926-1',
    ['position' => \yii\web\View::POS_HEAD]
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
    --change-danger: #dc2626;
}

.change-view-page {
    min-height: calc(100vh - 64px);
    padding: 22px 24px 34px;
    background: var(--change-bg);
}

.change-view-shell {
    width: 100%;
    max-width: 1420px;
    margin: 0 auto;
}

.change-view-header,
.change-view-card {
    border: 1px solid var(--change-border);
    border-radius: 18px;
    background: var(--change-card);
    box-shadow: 0 12px 30px rgba(15, 23, 42, .06);
}

.change-view-header {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 16px;
    padding: 21px 24px;
    overflow: hidden;
}

.change-view-header::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: var(--change-primary);
}

.change-view-eyebrow {
    margin-bottom: 3px;
    color: var(--change-primary);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.change-view-title {
    margin: 0;
    color: var(--change-text);
    font-size: clamp(24px, 2vw, 31px);
    font-weight: 800;
    letter-spacing: -.02em;
}

.change-view-title .bi,
.section-title .bi {
    margin-right: 8px;
    color: var(--change-primary);
}

.change-view-subtitle {
    margin: 5px 0 0;
    color: var(--change-muted);
    font-size: 13px;
}

.header-actions,
.resource-actions,
.decision-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 9px;
}

.version-badge,
.change-status,
.operational-badge,
.document-state {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    font-weight: 800;
    white-space: nowrap;
}

.version-badge {
    padding: 9px 13px;
    color: var(--change-primary-dark);
    border: 1px solid #b6d4fe;
    background: var(--change-primary-soft);
    font-size: 12px;
}

.change-status {
    padding: 8px 12px;
    font-size: 12px;
}

.status-primary { color: var(--change-primary-dark); border: 1px solid #b6d4fe; background: var(--change-primary-soft); }
.status-success { color: #166534; border: 1px solid #bbf7d0; background: #dcfce7; }
.status-danger { color: #991b1b; border: 1px solid #fecaca; background: #fee2e2; }

.change-view-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 42px;
    padding: 0 16px;
    border-radius: 11px;
    font-size: 13px;
    font-weight: 800;
    text-decoration: none !important;
    cursor: pointer;
}

.button-primary {
    color: #ffffff !important;
    border: 1px solid var(--change-primary);
    background: var(--change-primary);
}

.button-primary:hover { color: #ffffff !important; border-color: var(--change-primary-dark); background: var(--change-primary-dark); }
.button-secondary { color: #334155 !important; border: 1px solid #cbd5e1; background: #ffffff; }
.button-secondary:hover { color: var(--change-primary) !important; border-color: var(--change-primary); background: var(--change-primary-soft); }
.button-danger { color: #ffffff !important; border: 1px solid var(--change-danger); background: var(--change-danger); }
.button-danger:hover { color: #ffffff !important; border-color: #b91c1c; background: #b91c1c; }

.change-view-card {
    margin-bottom: 16px;
    padding: 21px;
}

.section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
}

.section-title {
    margin: 0;
    color: var(--change-text);
    font-size: 18px;
    font-weight: 800;
}

.section-description {
    margin: 4px 0 0;
    color: var(--change-muted);
    font-size: 12px;
}

.operational-badge {
    padding: 7px 11px;
    color: var(--change-primary-dark);
    border: 1px solid #b6d4fe;
    background: var(--change-primary-soft);
    font-size: 11px;
}

.context-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 11px;
}

.context-item,
.change-detail,
.document-panel {
    min-width: 0;
    border: 1px solid #dce6f1;
    border-radius: 13px;
    background: #ffffff;
}

.context-item {
    min-height: 82px;
    padding: 13px 14px;
}

.item-label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 7px;
    color: #64748b;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.item-label .bi { color: var(--change-primary); }

.item-value {
    color: var(--change-text);
    font-size: 13px;
    font-weight: 750;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.item-value small {
    display: block;
    margin-top: 3px;
    color: var(--change-muted);
    font-size: 11px;
    font-weight: 600;
}

.current-description,
.baseline-scope {
    margin-bottom: 11px;
    padding: 14px 15px;
    border: 1px dashed #b9cbe0;
    border-radius: 13px;
    background: #f8fbff;
}

.current-description-text,
.detail-text {
    color: #334155;
    font-size: 13px;
    line-height: 1.55;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.baseline-grid,
.change-detail-grid,
.document-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.baseline-scope { margin: 0; }
.baseline-quote { padding: 14px 15px; border: 1px solid #dce6f1; border-radius: 13px; }

.change-detail {
    padding: 15px;
    background: #f8fbff;
}

.change-detail.reason-detail { grid-column: 1 / -1; }

.empty-value {
    color: #94a3b8;
    font-style: italic;
}

.rejection-message {
    margin-top: 12px;
    padding: 12px 14px;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-radius: 11px;
    background: #fff7f7;
    font-size: 12px;
    line-height: 1.5;
}

.document-panel {
    padding: 15px;
}

.document-panel-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.document-title {
    margin: 0;
    color: var(--change-text);
    font-size: 14px;
    font-weight: 800;
}

.document-title .bi { margin-right: 6px; color: var(--change-primary); }

.document-state {
    padding: 5px 8px;
    color: var(--change-primary-dark);
    background: var(--change-primary-soft);
    font-size: 10px;
}

.quote-amount {
    margin: 7px 0;
    color: var(--change-text);
    font-size: 17px;
    font-weight: 800;
}

.resource-actions { margin-top: 11px; }
.resource-link { color: var(--change-primary-dark); font-size: 12px; font-weight: 800; text-decoration: none !important; }
.resource-link:hover { color: var(--change-primary); }

.history-wrap {
    overflow-x: auto;
    border: 1px solid #dce6f1;
    border-radius: 13px;
}

.history-table {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
    font-size: 12px;
}

.history-table th {
    padding: 11px 13px;
    color: #475569;
    border-bottom: 1px solid #dce6f1;
    background: #f8fafc;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .05em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.history-table td {
    padding: 12px 13px;
    color: #334155;
    border-bottom: 1px solid #edf2f7;
    vertical-align: top;
}

.history-table tr:last-child td { border-bottom: 0; }
.history-rejection { margin-top: 4px; color: var(--change-danger); font-weight: 650; }

.action-card {
    border-color: #b6d4fe;
}

.action-panel {
    padding: 15px;
    border: 1px solid #dce6f1;
    border-radius: 13px;
    background: #f8fbff;
}

.inline-action-form { display: inline-block; margin: 0; }
.reject-form { max-width: 720px; margin-top: 13px; }
.reject-label { display: block; margin-bottom: 6px; color: #1e293b; font-size: 12px; font-weight: 800; }
.reject-input {
    width: 100%;
    margin-bottom: 9px;
    padding: 10px 12px;
    color: var(--change-text);
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    background: #ffffff;
    resize: vertical;
}
.reject-input:focus { border-color: var(--change-primary); outline: 0; box-shadow: 0 0 0 3px rgba(13, 110, 253, .1); }
.reject-input.is-invalid { border-color: var(--change-danger); box-shadow: 0 0 0 3px rgba(220, 38, 38, .08); }
.reject-validation-message {
    display: none;
    margin: -3px 0 9px;
    color: var(--change-danger);
    font-size: 12px;
    font-weight: 700;
}
.reject-validation-message.is-visible { display: block; }
.no-action { margin: 0; color: var(--change-muted); font-size: 13px; }

.swal-decision-popup { width: 380px !important; padding: 20px !important; border-radius: 18px !important; }
.swal-decision-popup .swal2-icon { width: 3.5em !important; height: 3.5em !important; margin: .6em auto .8em !important; }
.swal-decision-title { padding: 0 !important; color: var(--change-text) !important; font-size: 20px !important; font-weight: 800 !important; }
.swal-decision-html { margin: 10px 0 0 !important; color: var(--change-muted) !important; font-size: 13px !important; line-height: 1.5 !important; }
.swal-decision-confirm,
.swal-decision-cancel { padding: 9px 15px !important; border-radius: 10px !important; font-size: 13px !important; font-weight: 800 !important; }
.swal-decision-popup .swal2-actions { gap: 8px !important; margin-top: 16px !important; }

@media (max-width: 1050px) {
    .context-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 720px) {
    .change-view-page { padding: 12px; }
    .change-view-header, .section-header { align-items: stretch; flex-direction: column; }
    .header-actions { justify-content: space-between; }
    .context-grid, .baseline-grid, .change-detail-grid, .document-grid { grid-template-columns: 1fr; }
    .change-detail.reason-detail { grid-column: auto; }
    .change-view-card { padding: 16px; }
    .decision-actions, .decision-actions form, .decision-actions .change-view-button { width: 100%; }
}
CSS);
?>

<main class="dash-content change-view-page">
    <div class="change-view-shell">
        <header class="change-view-header">
            <div class="change-header-copy">
                <div class="change-view-eyebrow">Scope change management</div>
                <div class="change-title-row">
                    <?= Html::a('<i class="bi bi-arrow-left"></i>', $backUrl, [
                        'class' => 'change-back-link',
                        'aria-label' => 'Back',
                        'title' => 'Back',
                    ]) ?>
                    <h1 class="change-view-title"><?= Html::encode($this->title) ?></h1>
                    <span class="version-badge"><i class="bi bi-layers"></i>Version <?= Html::encode($model->version) ?></span>
                    <span class="change-status <?= Html::encode($changeStatusClass) ?>">
                        <i class="bi bi-circle-fill"></i><?= Html::encode($model->getStatusLabel()) ?>
                    </span>
                </div>
                <p class="change-view-subtitle">Review the proposed scope, revised documents and current approval stage.</p>
            </div>
            <div class="change-header-dates" aria-label="Change order timestamps">
                <span>Requested: <strong><?= Html::encode($changeRequestedAt) ?></strong></span>
                <span>Last updated: <strong><?= Html::encode($changeUpdatedAt) ?></strong></span>
            </div>
        </header>

        <section class="change-view-card request-overview-card" aria-labelledby="request-context-title">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="request-context-title"><i class="bi bi-clipboard2-data"></i>Request Overview</h2>
                    <p class="section-description">Read-only operational context. The Request status remains unchanged during this change order.</p>
                </div>
            </div>

            <div class="request-summary-grid">
                <article class="request-summary-item aircraft-summary-item">
                    <div class="item-label"><i class="bi bi-airplane-engines"></i>Aircraft</div>
                    <div class="summary-main-value"><?= Html::encode($aircraftName) ?></div>
                    <div class="aircraft-reference">
                        <span><?= Html::encode($aircraft->registration_number ?? ($request->aircraft_registration ?? 'N/A')) ?></span>
                        <span>MSN <?= Html::encode($aircraft->serial_number ?? 'N/A') ?></span>
                    </div>
                    <div class="aircraft-summary-image" role="img" aria-label="Aircraft maintenance network"></div>
                </article>

                <article class="request-summary-item">
                    <div class="item-label"><i class="bi bi-building"></i>Assigned MRO</div>
                    <div class="summary-main-value"><?= Html::encode($mroName) ?></div>
                </article>

                <article class="request-summary-item">
                    <div class="item-label"><i class="bi bi-geo-alt"></i>Maintenance Location</div>
                    <div class="summary-main-value"><?= Html::encode($request && $request->location ? $request->location : $airportName) ?></div>
                    <div class="summary-secondary-value"><?= Html::encode($airportName) ?></div>
                </article>

                <article class="request-summary-item">
                    <div class="item-label"><i class="bi bi-calendar2-week"></i>ETA / ETD</div>
                    <div class="summary-schedule">
                        <span><small>ETA</small><strong><?= Html::encode($formatDate($request ? $request->eta : null)) ?></strong></span>
                        <span><small>ETD</small><strong><?= Html::encode($formatDate($request ? $request->etd : null)) ?></strong></span>
                    </div>
                </article>

                <article class="request-summary-item">
                    <div class="item-label"><i class="bi bi-broadcast-pin"></i>Operational Priority</div>
                    <div>
                        <span class="priority-badge priority-<?= Html::encode($operationalPriority) ?>">
                            <i class="bi <?= Html::encode($operationalPriorityIcons[$operationalPriority] ?? 'bi-calendar-check') ?>"></i>
                            <?= Html::encode($operationalPriorityLabel) ?>
                        </span>
                    </div>
                </article>

                <article class="request-summary-item">
                    <div class="item-label"><i class="bi bi-activity"></i>Request Status</div>
                    <div>
                        <span class="request-status-badge <?= Html::encode($requestStatusClass) ?>">
                            <i class="bi bi-circle-fill"></i>
                            <?= Html::encode($operationalStatus) ?>
                        </span>
                    </div>
                </article>

                <article class="request-summary-item">
                    <div class="item-label"><i class="bi bi-files"></i>Change Documents</div>
                    <div class="summary-main-value"><?= Html::encode((string) $documentCount) ?></div>
                </article>
            </div>

            <article class="request-information-panel">
                <div class="item-label"><i class="bi bi-info-circle"></i>Request Informations</div>
                <div class="current-description-text"><?= Html::encode($request && trim((string) $request->request_details) !== '' ? trim((string) $request->request_details) : 'No request details provided.') ?></div>
            </article>
        </section>

        <section class="change-view-card" aria-labelledby="baseline-title">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="baseline-title"><i class="bi bi-tools"></i>Current Work Baseline</h2>
                    <p class="section-description">The approved scope and quote remain active until the change order is fully accepted.</p>
                </div>
            </div>
            <div class="baseline-grid">
                <div class="baseline-scope">
                    <div class="item-label"><i class="bi bi-list-check"></i>Approved MRO scope</div>
                    <div class="detail-text"><?= Html::encode($application && trim((string) $application->Description) !== '' ? trim((string) $application->Description) : 'No approved scope description is available.') ?></div>
                </div>
                <div class="baseline-quote">
                    <div class="item-label"><i class="bi bi-cash-coin"></i>Current approved quote</div>
                    <div class="item-value">
                        <?= $application && $application->price !== null
                            ? Html::encode(number_format((float) $application->price, 2) . ' ' . strtoupper((string) $application->currency))
                            : '<span class="empty-value">Not available</span>' ?>
                    </div>
                    <?php if ($application && trim((string) $application->attachment) !== ''): ?>
                        <div class="resource-actions">
                            <?= Html::a(
                                '<i class="bi bi-receipt"></i> View Current Quote',
                                ['/mro-applications/view-answer', 'id' => UrlIdHelper::encode($application->id)],
                                ['class' => 'resource-link']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="change-view-card" aria-labelledby="scope-change-title">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="scope-change-title"><i class="bi bi-pencil-square"></i>Proposed Scope Change</h2>
                    <p class="section-description">Version <?= Html::encode($model->version) ?> submitted to the assigned MRO.</p>
                </div>
                <span class="change-status <?= Html::encode($changeStatusClass) ?>"><i class="bi bi-activity"></i><?= Html::encode($model->getStatusLabel()) ?></span>
            </div>

            <div class="change-detail-grid">
                <div class="change-detail reason-detail">
                    <div class="item-label"><i class="bi bi-chat-left-text"></i>Change reason</div>
                    <div class="detail-text"><?= Html::encode($model->reason) ?></div>
                </div>
                <div class="change-detail">
                    <div class="item-label"><i class="bi bi-plus-circle"></i>Tasks to add</div>
                    <div class="detail-text"><?= $model->added_tasks ? Html::encode($model->added_tasks) : '<span class="empty-value">None</span>' ?></div>
                </div>
                <div class="change-detail">
                    <div class="item-label"><i class="bi bi-dash-circle"></i>Tasks to remove</div>
                    <div class="detail-text"><?= $model->removed_tasks ? Html::encode($model->removed_tasks) : '<span class="empty-value">None</span>' ?></div>
                </div>
            </div>

            <?php foreach ([
                'mro_rejection_reason' => 'Change rejection reason',
                'po_rejection_reason' => 'PO rejection reason',
                'quote_rejection_reason' => 'Quote rejection reason',
            ] as $attribute => $label): ?>
                <?php if ($model->$attribute): ?>
                    <div class="rejection-message">
                        <strong><?= Html::encode($label) ?>:</strong>
                        <?= nl2br(Html::encode($model->$attribute)) ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>

        <section class="change-view-card" aria-labelledby="documents-title">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="documents-title"><i class="bi bi-files"></i>Revised Documents and Terms</h2>
                    <p class="section-description">Latest PO and quote submitted for this change order.</p>
                </div>
            </div>

            <div class="document-grid">
                <article class="document-panel">
                    <div class="document-panel-head">
                        <h3 class="document-title"><i class="bi bi-file-earmark-text"></i>Revised PO</h3>
                        <span class="document-state"><?= $model->revised_po ? 'Available' : 'Not uploaded' ?></span>
                    </div>
                    <div class="detail-text"><?= $model->revised_po ? 'The latest revised PO is available for review.' : '<span class="empty-value">No revised PO has been uploaded.</span>' ?></div>
                    <?php if ($model->revised_po): ?>
                        <div class="resource-actions">
                            <?= Html::a(
                                '<i class="bi bi-box-arrow-up-right"></i> Open Revised PO',
                                Yii::$app->request->baseUrl . '/' . ltrim($model->revised_po, '/'),
                                ['class' => 'resource-link', 'target' => '_blank', 'rel' => 'noopener']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="document-panel">
                    <div class="document-panel-head">
                        <h3 class="document-title"><i class="bi bi-receipt"></i>Revised Quote</h3>
                        <span class="document-state"><?= $model->revised_quote ? 'Available' : 'Not submitted' ?></span>
                    </div>
                    <?php if ($model->quote_description): ?>
                        <div class="detail-text"><?= Html::encode($model->quote_description) ?></div>
                    <?php endif; ?>
                    <?php if ($model->quote_price !== null): ?>
                        <div class="quote-amount"><?= Html::encode(number_format((float) $model->quote_price, 2)) ?> <?= Html::encode($model->quote_currency) ?></div>
                    <?php endif; ?>
                    <?php if (!$model->quote_description && $model->quote_price === null): ?>
                        <div class="detail-text"><span class="empty-value">No revised quote terms submitted.</span></div>
                    <?php endif; ?>
                    <?php if ($model->revised_quote): ?>
                        <div class="resource-actions">
                            <?= Html::a(
                                '<i class="bi bi-box-arrow-up-right"></i> Open Quote Document',
                                Yii::$app->request->baseUrl . '/' . ltrim($model->revised_quote, '/'),
                                ['class' => 'resource-link', 'target' => '_blank', 'rel' => 'noopener']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </article>
            </div>

            <?php if ($documents): ?>
                <div class="section-header" style="margin-top:18px">
                    <div>
                        <h3 class="section-title"><i class="bi bi-clock-history"></i>Version History</h3>
                        <p class="section-description">Immutable history of submitted PO and quote versions.</p>
                    </div>
                </div>
                <div class="history-wrap">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Version</th>
                                <th>Decision</th>
                                <th>Content</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $document): ?>
                                <tr>
                                    <td><?= $document->document_type === RequestChangeDocument::TYPE_PO ? 'PO' : 'Quote' ?></td>
                                    <td>v<?= Html::encode($document->version) ?></td>
                                    <td><?= Html::encode(ucfirst($document->review_status)) ?></td>
                                    <td>
                                        <?php if ($document->file_path): ?>
                                            <?= Html::a(
                                                '<i class="bi bi-box-arrow-up-right"></i> Open',
                                                Yii::$app->request->baseUrl . '/' . ltrim($document->file_path, '/'),
                                                ['class' => 'resource-link', 'target' => '_blank', 'rel' => 'noopener']
                                            ) ?>
                                        <?php endif; ?>
                                        <?php if ($document->document_type === RequestChangeDocument::TYPE_QUOTE && $document->price !== null): ?>
                                            <span><?= Html::encode(number_format((float) $document->price, 2)) ?> <?= Html::encode($document->currency) ?></span>
                                        <?php endif; ?>
                                        <?php if ($document->rejection_reason): ?>
                                            <div class="history-rejection"><?= nl2br(Html::encode($document->rejection_reason)) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= Html::encode($formatDate($document->created_at)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="change-view-card action-card" aria-labelledby="available-actions-title">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="available-actions-title"><i class="bi bi-ui-checks"></i>Available Actions</h2>
                    <p class="section-description">Only actions allowed for your role and the current approval stage are shown.</p>
                </div>
            </div>

            <div class="action-panel">
                <?php if ($role === 'mro' && $model->status === RequestChange::STATUS_PENDING_MRO): ?>
                    <div class="decision-actions">
                        <?= Html::beginForm(['accept', 'id' => $encodedId], 'post', [
                            'class' => 'inline-action-form decision-form',
                            'data-confirm-title' => 'Accept this scope change?',
                            'data-confirm-message' => 'The AO will be asked to upload a revised PO.',
                            'data-confirm-button' => 'Accept Change',
                        ]) ?>
                        <?= Html::submitButton('<i class="bi bi-check-circle"></i> Accept Change', ['class' => 'change-view-button button-primary']) ?>
                        <?= Html::endForm() ?>
                    </div>
                    <?= Html::beginForm(['reject', 'id' => $encodedId], 'post', [
                        'class' => 'reject-form decision-form rejection-decision-form',
                        'data-confirm-title' => 'Reject this scope change?',
                        'data-confirm-message' => 'The original approved scope and current work will remain active.',
                        'data-confirm-button' => 'Reject Change',
                        'data-confirm-danger' => '1',
                    ]) ?>
                    <label class="reject-label" for="change-rejection-reason">Rejection reason</label>
                    <?= Html::textarea('reason', '', ['id' => 'change-rejection-reason', 'class' => 'reject-input', 'rows' => 3, 'placeholder' => 'Explain why the scope change is rejected', 'aria-describedby' => 'change-rejection-error']) ?>
                    <div class="reject-validation-message" id="change-rejection-error" role="alert">Rejection reason is required.</div>
                    <?= Html::submitButton('<i class="bi bi-x-circle"></i> Reject Change', ['class' => 'change-view-button button-danger']) ?>
                    <?= Html::endForm() ?>
                <?php elseif ($role === 'ao' && in_array($model->status, [RequestChange::STATUS_AWAITING_UPDATED_PO, RequestChange::STATUS_PO_REJECTED], true)): ?>
                    <?= Html::a('<i class="bi bi-cloud-arrow-up"></i> Upload Revised PO', ['upload-po', 'id' => $encodedId], ['class' => 'change-view-button button-primary']) ?>
                <?php elseif ($role === 'mro' && $model->status === RequestChange::STATUS_PENDING_PO_REVIEW): ?>
                    <div class="decision-actions">
                        <?= Html::beginForm(['accept-po', 'id' => $encodedId], 'post', [
                            'class' => 'inline-action-form decision-form',
                            'data-confirm-title' => 'Accept this revised PO?',
                            'data-confirm-message' => 'The MRO will proceed with the revised commercial quote.',
                            'data-confirm-button' => 'Accept PO',
                        ]) ?>
                        <?= Html::submitButton('<i class="bi bi-check-circle"></i> Accept PO', ['class' => 'change-view-button button-primary']) ?>
                        <?= Html::endForm() ?>
                    </div>
                    <?= Html::beginForm(['reject-po', 'id' => $encodedId], 'post', [
                        'class' => 'reject-form decision-form rejection-decision-form',
                        'data-confirm-title' => 'Reject this revised PO?',
                        'data-confirm-message' => 'The AO will be asked to upload a corrected PO.',
                        'data-confirm-button' => 'Reject PO',
                        'data-confirm-danger' => '1',
                    ]) ?>
                    <label class="reject-label" for="po-rejection-reason">PO rejection reason</label>
                    <?= Html::textarea('reason', '', ['id' => 'po-rejection-reason', 'class' => 'reject-input', 'rows' => 3, 'placeholder' => 'Explain why the revised PO is rejected', 'aria-describedby' => 'po-rejection-error']) ?>
                    <div class="reject-validation-message" id="po-rejection-error" role="alert">PO rejection reason is required.</div>
                    <?= Html::submitButton('<i class="bi bi-x-circle"></i> Reject PO', ['class' => 'change-view-button button-danger']) ?>
                    <?= Html::endForm() ?>
                <?php elseif ($role === 'mro' && in_array($model->status, [RequestChange::STATUS_AWAITING_UPDATED_QUOTE, RequestChange::STATUS_QUOTE_REJECTED], true)): ?>
                    <?= Html::a('<i class="bi bi-file-earmark-plus"></i> Submit Revised Quote', ['submit-quote', 'id' => $encodedId], ['class' => 'change-view-button button-primary']) ?>
                <?php elseif ($role === 'ao' && $model->status === RequestChange::STATUS_PENDING_QUOTE_REVIEW): ?>
                    <div class="decision-actions">
                        <?= Html::beginForm(['accept-quote', 'id' => $encodedId], 'post', [
                            'class' => 'inline-action-form decision-form',
                            'data-confirm-title' => 'Accept and finalize this quote?',
                            'data-confirm-message' => 'The revised scope and commercial terms will be approved.',
                            'data-confirm-button' => 'Accept and Finalize',
                        ]) ?>
                        <?= Html::submitButton('<i class="bi bi-check-circle"></i> Accept Quote and Finalize', ['class' => 'change-view-button button-primary']) ?>
                        <?= Html::endForm() ?>
                    </div>
                    <?= Html::beginForm(['reject-quote', 'id' => $encodedId], 'post', [
                        'class' => 'reject-form decision-form rejection-decision-form',
                        'data-confirm-title' => 'Reject this revised quote?',
                        'data-confirm-message' => 'The MRO will be asked to submit a corrected quote.',
                        'data-confirm-button' => 'Reject Quote',
                        'data-confirm-danger' => '1',
                    ]) ?>
                    <label class="reject-label" for="quote-rejection-reason">Quote rejection reason</label>
                    <?= Html::textarea('reason', '', ['id' => 'quote-rejection-reason', 'class' => 'reject-input', 'rows' => 3, 'placeholder' => 'Explain why the revised quote is rejected', 'aria-describedby' => 'quote-rejection-error']) ?>
                    <div class="reject-validation-message" id="quote-rejection-error" role="alert">Quote rejection reason is required.</div>
                    <?= Html::submitButton('<i class="bi bi-x-circle"></i> Reject Quote', ['class' => 'change-view-button button-danger']) ?>
                    <?= Html::endForm() ?>
                <?php else: ?>
                    <p class="no-action"><i class="bi bi-info-circle"></i> No action is required from you at this stage.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php
/*
 * DÉCISIONS : la validation visible est en anglais et SweetAlert confirme
 * l'action. Le contrôleur conserve le dernier contrôle côté serveur.
 */
$this->registerJs(<<<'JS'
(function () {
    function swalClasses() {
        return {
            popup: 'swal-decision-popup',
            title: 'swal-decision-title',
            htmlContainer: 'swal-decision-html',
            confirmButton: 'swal-decision-confirm',
            cancelButton: 'swal-decision-cancel'
        };
    }

    document.querySelectorAll('.rejection-decision-form .reject-input').forEach(function (input) {
        input.addEventListener('input', function () {
            var error = input.parentElement.querySelector('.reject-validation-message');
            if (input.value.trim() !== '') {
                input.classList.remove('is-invalid');
                input.removeAttribute('aria-invalid');
                if (error) {
                    error.classList.remove('is-visible');
                }
            }
        });
    });

    document.querySelectorAll('.decision-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmed === '1') {
                return;
            }

            var reasonInput = form.querySelector('.reject-input');
            var reasonError = form.querySelector('.reject-validation-message');

            if (reasonInput && reasonInput.value.trim() === '') {
                event.preventDefault();
                reasonInput.classList.add('is-invalid');
                reasonInput.setAttribute('aria-invalid', 'true');
                if (reasonError) {
                    reasonError.classList.add('is-visible');
                }

                if (typeof Swal === 'undefined') {
                    window.alert(reasonError ? reasonError.textContent : 'Rejection reason is required.');
                    reasonInput.focus();
                    return;
                }

                Swal.fire({
                    title: 'Rejection reason required',
                    text: 'Enter a clear rejection reason in the highlighted field before continuing.',
                    icon: 'error',
                    confirmButtonText: '<i class="bi bi-check-circle"></i> Review Field',
                    confirmButtonColor: '#0d6efd',
                    customClass: swalClasses()
                }).then(function () {
                    reasonInput.focus();
                });
                return;
            }

            event.preventDefault();
            var title = form.dataset.confirmTitle || 'Confirm this action?';
            var message = form.dataset.confirmMessage || 'Please confirm before continuing.';
            var buttonText = form.dataset.confirmButton || 'Confirm';
            var isDanger = form.dataset.confirmDanger === '1';

            if (typeof Swal === 'undefined') {
                if (window.confirm(title + '\n\n' + message)) {
                    form.dataset.confirmed = '1';
                    form.submit();
                }
                return;
            }

            Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                reverseButtons: true,
                focusCancel: true,
                confirmButtonText: '<i class="bi bi-check-circle"></i> ' + buttonText,
                cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
                confirmButtonColor: isDanger ? '#dc2626' : '#0d6efd',
                cancelButtonColor: '#6c757d',
                customClass: swalClasses()
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                form.dataset.confirmed = '1';
                var submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                }
                form.submit();
            });
        });
    });
})();
JS);
?>

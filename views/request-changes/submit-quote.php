<?php

use app\components\UrlIdHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\RequestChange */
/* @var $currencyList array */

$this->title = 'Submit Revised Quote - Request #' . $model->request_id;
$encodedId = UrlIdHelper::encode($model->id);
$request = $model->request;
$application = $model->application;
$aircraft = $request ? $request->getAircraft()->one() : null;
$mro = $application ? $application->getMro() : null;
$aircraftName = $aircraft
    ? trim((string) ($aircraft->manufacturer ?? '') . ' ' . (string) ($aircraft->model ?? ''))
    : 'Aircraft unavailable';
$mroName = $mro
    ? ((string) ($mro->company_name ?? '') ?: (string) ($mro->username ?? '') ?: 'MRO partner')
    : 'MRO unavailable';
$operationalStatus = ucwords(str_replace('_', ' ', (string) ($request ? $request->status : $model->original_request_status)));
$hasPreviousDocument = trim((string) $model->revised_quote) !== '';
$destinationAirport = $request && method_exists($request, 'getDestinationAirport')
    ? $request->getDestinationAirport()->one()
    : null;
$airportName = $destinationAirport
    ? trim((string) ($destinationAirport->icao ?? '') . ' - ' . (string) ($destinationAirport->airport_name ?? ''), ' -')
    : 'N/A';
$etaText = $request && !empty($request->eta) && strtotime((string) $request->eta)
    ? date('d M Y H:i', strtotime((string) $request->eta))
    : 'N/A';
$etdText = $request && !empty($request->etd) && strtotime((string) $request->etd)
    ? date('d M Y H:i', strtotime((string) $request->etd))
    : 'N/A';
$priorityClass = strtolower((string) ($request->operational_priority ?? 'routine'));
$priorityText = $request && method_exists($request, 'getOperationalPriorityLabel')
    ? $request->getOperationalPriorityLabel()
    : ucfirst($priorityClass);
$priorityIcons = [
    'aog' => 'bi-exclamation-octagon',
    'urgent' => 'bi-lightning-charge',
    'routine' => 'bi-calendar-check',
];
$statusClass = 'status-' . preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower((string) ($request->status ?? $model->original_request_status)));
$formatRequestDate = static function ($value) {
    return !empty($value) && strtotime((string) $value)
        ? date('d M Y H:i', strtotime((string) $value))
        : 'N/A';
};
$requestCreatedAt = $formatRequestDate($request->created_at ?? null);
$requestUpdatedAt = $formatRequestDate($request->updated_at ?? null);
$requestDetails = $request->request_details ?? $request->description ?? null;

$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['position' => \yii\web\View::POS_HEAD]
);
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    ['position' => \yii\web\View::POS_HEAD]
);
$this->registerCssFile(
    Yii::$app->request->baseUrl . '/css/mro-view-answer-refresh.css?v=20260926-6'
);
$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    ['position' => \yii\web\View::POS_END]
);

$this->registerCss(<<<CSS
:root {
    --quote-primary: #0d6efd;
    --quote-primary-dark: #0b5ed7;
    --quote-primary-soft: #eaf2ff;
    --quote-bg: #f5f7fa;
    --quote-border: #dbe5f0;
    --quote-text: #0f172a;
    --quote-muted: #64748b;
    --quote-danger: #dc2626;
}

.revised-quote-page {
    min-height: calc(100vh - 64px);
    padding: 22px 24px 34px;
    background: var(--quote-bg);
}

.revised-quote-shell {
    width: 100%;
    max-width: 1220px;
    margin: 0 auto;
}

.quote-page-header,
.quote-card {
    border: 1px solid var(--quote-border);
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .06);
}

.quote-page-header {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 16px;
    padding: 21px 24px;
    overflow: hidden;
}

.quote-page-header::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: var(--quote-primary);
}

.quote-eyebrow {
    margin-bottom: 3px;
    color: var(--quote-primary);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.quote-title {
    margin: 0;
    color: var(--quote-text);
    font-size: clamp(24px, 2vw, 31px);
    font-weight: 800;
    letter-spacing: -.02em;
}

.quote-title .bi,
.quote-section-title .bi {
    margin-right: 8px;
    color: var(--quote-primary);
}

.quote-subtitle {
    margin: 5px 0 0;
    color: var(--quote-muted);
    font-size: 13px;
}

.quote-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 43px;
    padding: 0 17px;
    border-radius: 11px;
    font-size: 13px;
    font-weight: 800;
    text-decoration: none !important;
    cursor: pointer;
}

.quote-button-primary {
    color: #ffffff !important;
    border: 1px solid var(--quote-primary);
    background: var(--quote-primary);
}

.quote-button-primary:hover { color: #ffffff !important; border-color: var(--quote-primary-dark); background: var(--quote-primary-dark); }
.quote-button-secondary { color: #334155 !important; border: 1px solid #cbd5e1; background: #ffffff; }
.quote-button-secondary:hover { color: var(--quote-primary) !important; border-color: var(--quote-primary); background: var(--quote-primary-soft); }

.quote-card {
    margin-bottom: 16px;
    padding: 21px;
}

.quote-section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
}

.quote-section-title {
    margin: 0;
    color: var(--quote-text);
    font-size: 18px;
    font-weight: 800;
}

.quote-section-description {
    margin: 4px 0 0;
    color: var(--quote-muted);
    font-size: 12px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 11px;
    color: var(--quote-primary-dark);
    border: 1px solid #b6d4fe;
    border-radius: 999px;
    background: var(--quote-primary-soft);
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
}

.context-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 11px;
}

.context-item,
.scope-summary {
    min-width: 0;
    padding: 13px 14px;
    border: 1px solid #dce6f1;
    border-radius: 13px;
    background: #ffffff;
}

.context-label {
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

.context-label .bi { color: var(--quote-primary); }
.context-value { color: var(--quote-text); font-size: 13px; font-weight: 750; line-height: 1.45; overflow-wrap: anywhere; }

.scope-summary {
    margin-top: 11px;
    border-style: dashed;
    background: #f8fbff;
}

.scope-text {
    color: #334155;
    font-size: 13px;
    line-height: 1.55;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.previous-rejection {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin-bottom: 16px;
    padding: 12px 14px;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-radius: 11px;
    background: #fff7f7;
    font-size: 12px;
    line-height: 1.5;
}

.previous-rejection .bi { margin-top: 1px; color: var(--quote-danger); }

.quote-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.quote-field-full { grid-column: 1 / -1; }

.quote-form .form-group { margin-bottom: 0; }
.quote-form .control-label,
.quote-form .form-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 7px;
    color: #1e293b !important;
    font-size: 13px !important;
    font-weight: 800 !important;
}

.quote-form .control-label .bi,
.quote-form .form-label .bi { color: var(--quote-primary); }
.required-mark { color: var(--quote-danger); }

.quote-form .form-control,
.quote-form .quote-select {
    width: 100%;
    min-height: 44px;
    padding: 11px 13px;
    color: var(--quote-text);
    border: 1px solid #cbd8e6;
    border-radius: 11px;
    background: #fbfdff;
    box-shadow: none;
    transition: border-color .2s ease, box-shadow .2s ease;
}

.quote-form textarea.form-control { min-height: 135px; resize: vertical; }
.quote-form .form-control:focus,
.quote-form .quote-select:focus { border-color: var(--quote-primary); outline: 0; background: #ffffff; box-shadow: 0 0 0 3px rgba(13, 110, 253, .1); }

.quote-form .help-block,
.quote-form .invalid-feedback {
    display: block;
    margin: 6px 0 0;
    color: var(--quote-danger) !important;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.4;
}

.quote-form .has-error .form-control,
.quote-form .has-error .quote-select,
.quote-form .is-invalid { border-color: var(--quote-danger) !important; box-shadow: 0 0 0 3px rgba(220, 38, 38, .08); }
.quote-form .has-error .control-label,
.quote-form .has-error .form-label { color: var(--quote-danger) !important; }

.file-help {
    margin-top: 6px;
    color: var(--quote-muted);
    font-size: 11px;
}

/* TÉLÉVERSEMENT PERSONNALISÉ : le champ Yii reste actif mais n'affiche plus le texte localisé du navigateur. */
.quote-file-input {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

.quote-upload-zone {
    min-height: 150px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 7px;
    color: #475569;
    border: 2px dashed #93c5fd;
    border-radius: 14px;
    background: #f8fbff;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
}

.quote-upload-zone:hover,
.quote-upload-zone:focus,
.quote-upload-zone.is-dragover {
    border-color: var(--quote-primary);
    outline: 0;
    background: var(--quote-primary-soft);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, .08);
}

.quote-upload-zone.has-file {
    border-style: solid;
    border-color: var(--quote-primary);
    background: var(--quote-primary-soft);
}

.quote-file-field.has-error + .quote-upload-zone {
    border-color: var(--quote-danger);
    background: #fff7f7;
}

.quote-upload-icon {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--quote-primary);
    border-radius: 12px;
    background: #dbeafe;
    font-size: 21px;
}

.quote-upload-title {
    color: var(--quote-text);
    font-size: 14px;
    font-weight: 800;
}

.quote-upload-text {
    color: var(--quote-muted);
    font-size: 12px;
}

.quote-upload-action {
    margin-top: 3px;
    padding: 7px 12px;
    color: var(--quote-primary-dark);
    border: 1px solid #b6d4fe;
    border-radius: 9px;
    background: #ffffff;
    font-size: 12px;
    font-weight: 800;
}

.quote-selected-file {
    display: none;
    width: 100%;
    max-width: 620px;
    margin-top: 7px;
    padding: 9px 10px;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    color: #334155;
    border: 1px solid #b6d4fe;
    border-radius: 9px;
    background: #ffffff;
    font-size: 12px;
    font-weight: 700;
}

.quote-upload-zone.has-file .quote-selected-file { display: flex; }
.quote-selected-name { min-width: 0; overflow-wrap: anywhere; text-align: left; }
.quote-remove-file {
    flex: 0 0 auto;
    padding: 4px 8px;
    color: var(--quote-danger);
    border: 1px solid #fecaca;
    border-radius: 7px;
    background: #ffffff;
    font-size: 11px;
    font-weight: 800;
}

.existing-document {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 9px;
    padding: 10px 12px;
    border: 1px solid #b6d4fe;
    border-radius: 10px;
    background: var(--quote-primary-soft);
    color: #334155;
    font-size: 12px;
    font-weight: 700;
}

.existing-document a { color: var(--quote-primary-dark); font-weight: 800; text-decoration: none; }

.quote-note {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin-top: 16px;
    padding: 12px 14px;
    color: #075985;
    border: 1px solid #bae6fd;
    border-radius: 11px;
    background: #f0f9ff;
    font-size: 12px;
    line-height: 1.5;
}

.quote-note .bi { color: var(--quote-primary); }

.quote-form-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 9px;
    margin-top: 18px;
    padding-top: 17px;
    border-top: 1px solid #e2e8f0;
}

.swal-quote-popup { width: 380px !important; padding: 20px !important; border-radius: 18px !important; }
.swal-quote-popup .swal2-icon { width: 3.5em !important; height: 3.5em !important; margin: .6em auto .8em !important; }
.swal-quote-title { padding: 0 !important; color: var(--quote-text) !important; font-size: 20px !important; font-weight: 800 !important; }
.swal-quote-html { margin: 10px 0 0 !important; color: var(--quote-muted) !important; font-size: 13px !important; line-height: 1.5 !important; }
.swal-quote-confirm,
.swal-quote-cancel { padding: 9px 15px !important; border-radius: 10px !important; font-size: 13px !important; font-weight: 800 !important; }
.swal-quote-popup .swal2-actions { gap: 8px !important; margin-top: 16px !important; }

@media (max-width: 900px) {
    .context-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 680px) {
    .revised-quote-page { padding: 12px; }
    .quote-page-header, .quote-section-header { align-items: stretch; flex-direction: column; }
    .context-grid, .quote-form-grid { grid-template-columns: 1fr; }
    .quote-field-full { grid-column: auto; }
    .quote-card { padding: 16px; }
    .quote-form-actions { align-items: stretch; flex-direction: column-reverse; }
    .quote-form-actions .quote-button { width: 100%; }
}
CSS);
?>

<main class="dash-content revised-quote-page answer-view-page revised-quote-shared-page">
    <div class="revised-quote-shell">
        <header class="page-header-card">
            <div class="answer-header-copy">
                <div class="answer-title-row">
                    <?= Html::a('<i class="bi bi-arrow-left"></i>', ['view', 'id' => $encodedId], [
                        'class' => 'answer-back-link',
                        'aria-label' => 'Back to Change Order',
                        'title' => 'Back to Change Order',
                    ]) ?>
                    <h1 class="dash-title fw-bold"><?= Html::encode($this->title) ?></h1>
                    <span class="answer-priority priority-<?= Html::encode($priorityClass) ?>">
                        <i class="bi <?= Html::encode($priorityIcons[$priorityClass] ?? 'bi-calendar-check') ?>"></i>
                        <?= Html::encode($priorityText) ?>
                    </span>
                    <span class="answer-request-status <?= Html::encode($statusClass) ?>">
                        <i class="bi bi-circle-fill"></i>
                        <?= Html::encode($operationalStatus) ?>
                    </span>
                </div>
                <div class="subtitle-text">Prepare the commercial proposal corresponding to the approved revised PO.</div>
            </div>

            <div class="answer-header-side">
                <div class="answer-header-dates">
                    <span>Created: <strong><?= Html::encode($requestCreatedAt) ?></strong></span>
                    <span>Last updated: <strong><?= Html::encode($requestUpdatedAt) ?></strong></span>
                </div>
            </div>
        </header>

        <section class="answer-request-overview" aria-labelledby="quote-context-title">
            <div class="answer-overview-head">
                <div>
                    <h2 id="quote-context-title"><i class="bi bi-info-circle"></i>Request Overview</h2>
                    <p>Read-only operational context for the approved revised PO and scope change.</p>
                </div>
                <span class="answer-request-id"><i class="bi bi-hash"></i><?= Html::encode($model->request_id) ?></span>
            </div>

            <div class="answer-summary-grid">
                <article class="answer-summary-item answer-aircraft-item">
                    <div class="answer-summary-label"><i class="bi bi-airplane"></i>Aircraft</div>
                    <div class="answer-summary-value"><?= Html::encode($aircraftName) ?></div>
                    <div class="answer-aircraft-reference">
                        <span><?= Html::encode($request->aircraft_registration ?? 'N/A') ?></span>
                        <span>MSN <?= Html::encode($request->serial_number ?? 'N/A') ?></span>
                    </div>
                    <div class="answer-aircraft-image" role="img" aria-label="Aircraft maintenance network"></div>
                </article>

                <article class="answer-summary-item">
                    <div class="answer-summary-label"><i class="bi bi-building"></i>Assigned MRO</div>
                    <div class="answer-summary-value"><?= Html::encode($mroName) ?></div>
                </article>

                <article class="answer-summary-item">
                    <div class="answer-summary-label"><i class="bi bi-geo-alt"></i>Maintenance Location</div>
                    <div class="answer-summary-value"><?= Html::encode($request->location ?? 'N/A') ?></div>
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
                </article>

                <article class="answer-summary-item">
                    <div class="answer-summary-label"><i class="bi bi-layers"></i>Change Order</div>
                    <div class="answer-summary-value answer-quote-value">Version <?= Html::encode($model->version) ?></div>
                </article>

                <article class="answer-summary-item">
                    <div class="answer-summary-label"><i class="bi bi-activity"></i>Request Status</div>
                    <span class="answer-request-status <?= Html::encode($statusClass) ?>">
                        <i class="bi bi-circle-fill"></i>
                        <?= Html::encode($operationalStatus) ?>
                    </span>
                </article>
            </div>

            <article class="answer-request-information">
                <div class="answer-summary-label"><i class="bi bi-file-text"></i>Request Informations</div>
                <div class="answer-request-text"><?= nl2br(Html::encode(!empty($requestDetails) ? $requestDetails : 'No request details available')) ?></div>
            </article>

            <article class="answer-request-information">
                <div class="answer-summary-label"><i class="bi bi-chat-left-text"></i>Approved Change Reason</div>
                <div class="answer-request-text"><?= nl2br(Html::encode($model->reason)) ?></div>
            </article>
        </section>

        <section class="quote-card" aria-labelledby="quote-form-title">
            <div class="quote-section-header">
                <div>
                    <h2 class="quote-section-title" id="quote-form-title"><i class="bi bi-file-earmark-plus"></i>Revised Quote Details</h2>
                    <p class="quote-section-description">Complete all required commercial fields before sending the quote to the AO.</p>
                </div>
            </div>

            <?php if ($model->quote_rejection_reason): ?>
                <div class="previous-rejection">
                    <i class="bi bi-exclamation-triangle"></i>
                    <div>
                        <strong>Previous rejection reason:</strong><br>
                        <?= nl2br(Html::encode($model->quote_rejection_reason)) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php $form = ActiveForm::begin([
                'id' => 'revised-quote-form',
                'options' => ['class' => 'quote-form', 'enctype' => 'multipart/form-data'],
                'enableClientValidation' => true,
            ]); ?>

            <div class="quote-form-grid">
                <div class="quote-field-full">
                    <?= $form->field($model, 'quote_description')->textarea([
                        'rows' => 6,
                        'placeholder' => 'Describe the revised scope, commercial terms, exclusions, lead time and validity…',
                    ])->label('<i class="bi bi-card-text"></i> Revised quote description <span class="required-mark">*</span>', ['encode' => false]) ?>
                </div>

                <div>
                    <?= $form->field($model, 'quote_price')->input('number', [
                        'min' => 0,
                        'step' => '0.01',
                        'placeholder' => '0.00',
                    ])->label('<i class="bi bi-cash-stack"></i> Revised quote amount <span class="required-mark">*</span>', ['encode' => false]) ?>
                </div>

                <div>
                    <?= $form->field($model, 'quote_currency')->dropDownList($currencyList, [
                        'prompt' => 'Select currency',
                        'class' => 'form-control quote-select',
                    ])->label('<i class="bi bi-currency-exchange"></i> Currency <span class="required-mark">*</span>', ['encode' => false]) ?>
                </div>

                <div class="quote-field-full">
                    <label class="control-label" for="revised-quote-document"><i class="bi bi-cloud-arrow-up"></i> Revised quote document</label>
                    <?= $form->field($model, 'revisedQuoteUpload', [
                        'template' => '{input}{error}',
                        'options' => ['class' => 'quote-file-field'],
                    ])->fileInput([
                        'id' => 'revised-quote-document',
                        'accept' => '.pdf,.png,.jpg,.jpeg',
                        'class' => 'quote-file-input',
                    ])->label(false) ?>

                    <div class="quote-upload-zone"
                         id="revised-quote-dropzone"
                         role="button"
                         tabindex="0"
                         aria-controls="revised-quote-document"
                         aria-label="Upload revised quote document">
                        <span class="quote-upload-icon"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                        <span class="quote-upload-title">Drag and drop your revised quote here</span>
                        <span class="quote-upload-text">or browse your device to select one file</span>
                        <span class="quote-upload-action"><i class="bi bi-folder2-open"></i> Browse files</span>
                        <span class="quote-selected-file" aria-live="polite">
                            <span class="quote-selected-name"><i class="bi bi-file-earmark-check"></i> <span id="revised-quote-file-name"></span></span>
                            <button type="button" class="quote-remove-file" id="remove-revised-quote-file"><i class="bi bi-x-circle"></i> Remove</button>
                        </span>
                    </div>
                    <div class="file-help">Accepted formats: PDF, PNG, JPG or JPEG. Maximum size: 10 MB.</div>

                    <?php if ($hasPreviousDocument): ?>
                        <div class="existing-document">
                            <span><i class="bi bi-file-earmark-check"></i> Current quote document will be retained if no replacement is selected.</span>
                            <?= Html::a(
                                '<i class="bi bi-box-arrow-up-right"></i> Open',
                                Yii::$app->request->baseUrl . '/' . ltrim($model->revised_quote, '/'),
                                ['target' => '_blank', 'rel' => 'noopener']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="quote-note">
                <i class="bi bi-info-circle-fill"></i>
                <div>The revised quote will be sent to the AO for approval. The current operational Request status and original work remain unchanged until final acceptance.</div>
            </div>

            <div class="quote-form-actions">
                <?= Html::a(
                    '<i class="bi bi-x-circle"></i> Cancel',
                    ['view', 'id' => $encodedId],
                    ['class' => 'quote-button quote-button-secondary']
                ) ?>
                <?= Html::submitButton(
                    '<i class="bi bi-send-check"></i> Send Quote to AO',
                    ['class' => 'quote-button quote-button-primary', 'id' => 'send-revised-quote']
                ) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </section>
    </div>
</main>

<?php
/*
 * VALIDATION ET CONFIRMATION : Yii valide d'abord les champs. SweetAlert
 * résume ensuite l'action avant l'envoi définitif au serveur.
 */
$this->registerJs(<<<'JS'
(function () {
    var form = $('#revised-quote-form');
    var confirmed = false;
    var fileInput = document.getElementById('revised-quote-document');
    var dropzone = document.getElementById('revised-quote-dropzone');
    var fileName = document.getElementById('revised-quote-file-name');
    var removeFile = document.getElementById('remove-revised-quote-file');

    function swalClasses() {
        return {
            popup: 'swal-quote-popup',
            title: 'swal-quote-title',
            htmlContainer: 'swal-quote-html',
            confirmButton: 'swal-quote-confirm',
            cancelButton: 'swal-quote-cancel'
        };
    }

    function refreshFileState() {
        var file = fileInput && fileInput.files && fileInput.files.length ? fileInput.files[0] : null;

        if (fileName) {
            fileName.textContent = file ? file.name : '';
        }
        if (dropzone) {
            dropzone.classList.toggle('has-file', !!file);
        }
    }

    /* DRAG AND DROP : le fichier alimente le champ Yii natif utilisé au submit. */
    if (fileInput && dropzone) {
        dropzone.addEventListener('click', function (event) {
            if (removeFile && (event.target === removeFile || removeFile.contains(event.target))) {
                return;
            }
            fileInput.click();
        });

        dropzone.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                fileInput.click();
            }
        });

        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'dragend', 'drop'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', function (event) {
            var files = event.dataTransfer && event.dataTransfer.files;
            if (!files || !files.length) {
                return;
            }

            try {
                var transfer = new DataTransfer();
                transfer.items.add(files[0]);
                fileInput.files = transfer.files;
                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (error) {
                fileInput.click();
            }
        });

        fileInput.addEventListener('change', refreshFileState);
    }

    if (removeFile && fileInput) {
        removeFile.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            fileInput.value = '';
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            dropzone.focus();
        });
    }

    refreshFileState();

    form.on('afterValidate', function (event, messages, errorAttributes) {
        if (!errorAttributes || !errorAttributes.length || typeof Swal === 'undefined') {
            return;
        }

        Swal.fire({
            title: 'Check the quote details',
            text: 'Complete the required fields highlighted in red before continuing.',
            icon: 'error',
            confirmButtonText: '<i class="bi bi-check-circle"></i> Review Fields',
            confirmButtonColor: '#0d6efd',
            customClass: swalClasses()
        });
    });

    form.on('beforeSubmit', function () {
        if (confirmed) {
            return true;
        }

        if (typeof Swal === 'undefined') {
            return window.confirm('Send this revised quote to the AO for approval?');
        }

        Swal.fire({
            title: 'Send revised quote?',
            html: 'The commercial proposal will be sent to the AO for review.<br><strong>Please confirm the amount and currency.</strong>',
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            confirmButtonText: '<i class="bi bi-send-check"></i> Send Quote',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            customClass: swalClasses()
        }).then(function (result) {
            if (result.isConfirmed) {
                confirmed = true;
                $('#send-revised-quote')
                    .prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Sending…');
                form.get(0).submit();
            }
        });

        return false;
    });
})();
JS);
?>

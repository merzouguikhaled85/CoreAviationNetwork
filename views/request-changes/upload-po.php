<?php

use app\components\UrlIdHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\RequestChange */

$this->title = 'Upload Revised PO - Request #' . $model->request_id;
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
$hasPreviousPo = trim((string) $model->revised_po) !== '';
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
    Yii::$app->request->baseUrl . '/css/mro-view-answer-refresh.css?v=20260926-5'
);
$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    ['position' => \yii\web\View::POS_END]
);

$this->registerCss(<<<CSS
:root {
    --po-primary: #0d6efd;
    --po-primary-dark: #0b5ed7;
    --po-primary-soft: #eaf2ff;
    --po-bg: #f5f7fa;
    --po-border: #dbe5f0;
    --po-text: #0f172a;
    --po-muted: #64748b;
    --po-danger: #dc2626;
}

.revised-po-page {
    min-height: calc(100vh - 64px);
    padding: 22px 24px 34px;
    background: var(--po-bg);
}

.revised-po-shell {
    width: 100%;
    max-width: 1180px;
    margin: 0 auto;
}

.po-page-header,
.po-card {
    border: 1px solid var(--po-border);
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .06);
}

.po-page-header {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 16px;
    padding: 21px 24px;
    overflow: hidden;
}

.po-page-header::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: var(--po-primary);
}

.po-eyebrow {
    margin-bottom: 3px;
    color: var(--po-primary);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.po-title {
    margin: 0;
    color: var(--po-text);
    font-size: clamp(24px, 2vw, 31px);
    font-weight: 800;
    letter-spacing: -.02em;
}

.po-title .bi,
.po-section-title .bi {
    margin-right: 8px;
    color: var(--po-primary);
}

.po-subtitle {
    margin: 5px 0 0;
    color: var(--po-muted);
    font-size: 13px;
}

.po-button {
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

.po-button-primary { color: #ffffff !important; border: 1px solid var(--po-primary); background: var(--po-primary); }
.po-button-primary:hover { color: #ffffff !important; border-color: var(--po-primary-dark); background: var(--po-primary-dark); }
.po-button-secondary { color: #334155 !important; border: 1px solid #cbd5e1; background: #ffffff; }
.po-button-secondary:hover { color: var(--po-primary) !important; border-color: var(--po-primary); background: var(--po-primary-soft); }

.po-card {
    margin-bottom: 16px;
    padding: 21px;
}

.po-section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
}

.po-section-title {
    margin: 0;
    color: var(--po-text);
    font-size: 18px;
    font-weight: 800;
}

.po-section-description {
    margin: 4px 0 0;
    color: var(--po-muted);
    font-size: 12px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 11px;
    color: var(--po-primary-dark);
    border: 1px solid #b6d4fe;
    border-radius: 999px;
    background: var(--po-primary-soft);
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
}

.po-context-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 11px;
}

.po-context-item,
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

.context-label .bi { color: var(--po-primary); }
.context-value { color: var(--po-text); font-size: 13px; font-weight: 750; line-height: 1.45; overflow-wrap: anywhere; }

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

.previous-rejection .bi { margin-top: 1px; color: var(--po-danger); }

.po-form .control-label,
.po-form .form-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 7px;
    color: #1e293b !important;
    font-size: 13px !important;
    font-weight: 800 !important;
}

.po-form .control-label .bi { color: var(--po-primary); }

.po-file-input {
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

.po-upload-zone {
    min-height: 175px;
    padding: 22px;
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

.po-upload-zone:hover,
.po-upload-zone:focus,
.po-upload-zone.is-dragover {
    border-color: var(--po-primary);
    outline: 0;
    background: var(--po-primary-soft);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, .08);
}

.po-upload-zone.has-file {
    border-style: solid;
    border-color: var(--po-primary);
    background: var(--po-primary-soft);
}

.po-file-field.has-error + .po-upload-zone {
    border-color: var(--po-danger);
    background: #fff7f7;
}

.po-upload-icon {
    width: 46px;
    height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--po-primary);
    border-radius: 12px;
    background: #dbeafe;
    font-size: 22px;
}

.po-upload-title { color: var(--po-text); font-size: 15px; font-weight: 800; }
.po-upload-text { color: var(--po-muted); font-size: 12px; }
.po-upload-action {
    margin-top: 3px;
    padding: 7px 12px;
    color: var(--po-primary-dark);
    border: 1px solid #b6d4fe;
    border-radius: 9px;
    background: #ffffff;
    font-size: 12px;
    font-weight: 800;
}

.po-selected-file {
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

.po-upload-zone.has-file .po-selected-file { display: flex; }
.po-selected-name { min-width: 0; overflow-wrap: anywhere; text-align: left; }
.po-remove-file {
    flex: 0 0 auto;
    padding: 4px 8px;
    color: var(--po-danger);
    border: 1px solid #fecaca;
    border-radius: 7px;
    background: #ffffff;
    font-size: 11px;
    font-weight: 800;
}

.po-form .help-block,
.po-form .invalid-feedback {
    display: block;
    margin: 6px 0 8px;
    color: var(--po-danger) !important;
    font-size: 12px;
    font-weight: 700;
}

.file-help { margin-top: 7px; color: var(--po-muted); font-size: 11px; }

.previous-po {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 10px;
    padding: 10px 12px;
    color: #334155;
    border: 1px solid #b6d4fe;
    border-radius: 10px;
    background: var(--po-primary-soft);
    font-size: 12px;
    font-weight: 700;
}

.previous-po a { color: var(--po-primary-dark); font-weight: 800; text-decoration: none; }

.po-note {
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

.po-note .bi { color: var(--po-primary); }

.po-form-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 9px;
    margin-top: 18px;
    padding-top: 17px;
    border-top: 1px solid #e2e8f0;
}

.swal-po-popup { width: 380px !important; padding: 20px !important; border-radius: 18px !important; }
.swal-po-popup .swal2-icon { width: 3.5em !important; height: 3.5em !important; margin: .6em auto .8em !important; }
.swal-po-title { padding: 0 !important; color: var(--po-text) !important; font-size: 20px !important; font-weight: 800 !important; }
.swal-po-html { margin: 10px 0 0 !important; color: var(--po-muted) !important; font-size: 13px !important; line-height: 1.5 !important; }
.swal-po-confirm,
.swal-po-cancel { padding: 9px 15px !important; border-radius: 10px !important; font-size: 13px !important; font-weight: 800 !important; }
.swal-po-popup .swal2-actions { gap: 8px !important; margin-top: 16px !important; }

@media (max-width: 860px) {
    .po-context-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 640px) {
    .revised-po-page { padding: 12px; }
    .po-page-header, .po-section-header { align-items: stretch; flex-direction: column; }
    .po-context-grid { grid-template-columns: 1fr; }
    .po-card { padding: 16px; }
    .po-form-actions { align-items: stretch; flex-direction: column-reverse; }
    .po-form-actions .po-button { width: 100%; }
    .previous-po { align-items: flex-start; flex-direction: column; }
}
CSS);
?>

<main class="dash-content revised-po-page answer-view-page revised-po-shared-page">
    <div class="revised-po-shell">
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
                <div class="subtitle-text">Upload the PO that exactly matches the scope accepted by the MRO.</div>
            </div>

            <div class="answer-header-side">
                <div class="answer-header-dates">
                    <span>Created: <strong><?= Html::encode($requestCreatedAt) ?></strong></span>
                    <span>Last updated: <strong><?= Html::encode($requestUpdatedAt) ?></strong></span>
                </div>
            </div>
        </header>

        <section class="answer-request-overview" aria-labelledby="po-context-title">
            <div class="answer-overview-head">
                <div>
                    <h2 id="po-context-title"><i class="bi bi-info-circle"></i>Request Overview</h2>
                    <p>Read-only operational context for the MRO-approved scope change.</p>
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
                <div class="answer-summary-label"><i class="bi bi-chat-left-text"></i>Accepted Change Reason</div>
                <div class="answer-request-text"><?= nl2br(Html::encode($model->reason)) ?></div>
            </article>
        </section>

        <section class="po-card" aria-labelledby="po-upload-title">
            <div class="po-section-header">
                <div>
                    <h2 class="po-section-title" id="po-upload-title"><i class="bi bi-cloud-arrow-up"></i>Revised PO Document</h2>
                    <p class="po-section-description">Select one clear document before sending it to the assigned MRO for approval.</p>
                </div>
            </div>

            <?php if ($model->po_rejection_reason): ?>
                <div class="previous-rejection">
                    <i class="bi bi-exclamation-triangle"></i>
                    <div>
                        <strong>Previous rejection reason:</strong><br>
                        <?= nl2br(Html::encode($model->po_rejection_reason)) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php $form = ActiveForm::begin([
                'id' => 'revised-po-form',
                'options' => ['class' => 'po-form', 'enctype' => 'multipart/form-data'],
                'enableClientValidation' => true,
            ]); ?>

            <label class="control-label" for="revised-po-document"><i class="bi bi-file-earmark-text"></i> Revised PO <span style="color:#dc2626">*</span></label>
            <?= $form->field($model, 'revisedPoUpload', [
                'template' => '{input}{error}',
                'options' => ['class' => 'po-file-field'],
            ])->fileInput([
                'id' => 'revised-po-document',
                'accept' => '.pdf,.png,.jpg,.jpeg',
                'class' => 'po-file-input',
            ])->label(false) ?>

            <div class="po-upload-zone"
                 id="revised-po-dropzone"
                 role="button"
                 tabindex="0"
                 aria-controls="revised-po-document"
                 aria-label="Upload revised PO document">
                <span class="po-upload-icon"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                <span class="po-upload-title">Drag and drop your revised PO here</span>
                <span class="po-upload-text">or browse your device to select one file</span>
                <span class="po-upload-action"><i class="bi bi-folder2-open"></i> Browse files</span>
                <span class="po-selected-file" aria-live="polite">
                    <span class="po-selected-name"><i class="bi bi-file-earmark-check"></i> <span id="revised-po-file-name"></span></span>
                    <button type="button" class="po-remove-file" id="remove-revised-po-file"><i class="bi bi-x-circle"></i> Remove</button>
                </span>
            </div>

            <div class="file-help">Accepted formats: PDF, PNG, JPG or JPEG. Maximum size: 10 MB.</div>

            <?php if ($hasPreviousPo): ?>
                <div class="previous-po">
                    <span><i class="bi bi-file-earmark-excel"></i> The previously rejected PO remains available for reference.</span>
                    <?= Html::a(
                        '<i class="bi bi-box-arrow-up-right"></i> Open Previous PO',
                        Yii::$app->request->baseUrl . '/' . ltrim($model->revised_po, '/'),
                        ['target' => '_blank', 'rel' => 'noopener']
                    ) ?>
                </div>
            <?php endif; ?>

            <div class="po-note">
                <i class="bi bi-info-circle-fill"></i>
                <div>The revised PO will be sent to the assigned MRO for approval. The operational Request status and current work remain unchanged.</div>
            </div>

            <div class="po-form-actions">
                <?= Html::a(
                    '<i class="bi bi-x-circle"></i> Cancel',
                    ['view', 'id' => $encodedId],
                    ['class' => 'po-button po-button-secondary']
                ) ?>
                <?= Html::submitButton(
                    '<i class="bi bi-send-check"></i> Send PO to MRO',
                    ['class' => 'po-button po-button-primary', 'id' => 'send-revised-po']
                ) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </section>
    </div>
</main>

<?php
/*
 * DRAG-AND-DROP ET VALIDATION : le champ natif reste la source envoyée à Yii.
 * SweetAlert intervient après la validation cliente et avant l'écriture serveur.
 */
$this->registerJs(<<<'JS'
(function () {
    var form = $('#revised-po-form');
    var confirmed = false;
    var fileInput = document.getElementById('revised-po-document');
    var dropzone = document.getElementById('revised-po-dropzone');
    var fileName = document.getElementById('revised-po-file-name');
    var removeFile = document.getElementById('remove-revised-po-file');

    function swalClasses() {
        return {
            popup: 'swal-po-popup',
            title: 'swal-po-title',
            htmlContainer: 'swal-po-html',
            confirmButton: 'swal-po-confirm',
            cancelButton: 'swal-po-cancel'
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
            title: 'Revised PO required',
            text: 'Select a valid PO document in the highlighted upload area before continuing.',
            icon: 'error',
            confirmButtonText: '<i class="bi bi-check-circle"></i> Review Upload',
            confirmButtonColor: '#0d6efd',
            customClass: swalClasses()
        }).then(function () {
            if (dropzone) {
                dropzone.focus();
            }
        });
    });

    form.on('beforeSubmit', function () {
        if (confirmed) {
            return true;
        }

        if (typeof Swal === 'undefined') {
            return window.confirm('Send this revised PO to the assigned MRO for approval?');
        }

        Swal.fire({
            title: 'Send revised PO?',
            html: 'The document will be sent to the assigned MRO for approval.<br><strong>Confirm that it matches the accepted scope change.</strong>',
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            confirmButtonText: '<i class="bi bi-send-check"></i> Send PO',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            customClass: swalClasses()
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            confirmed = true;
            $('#send-revised-po').prop('disabled', true).text('Sending…');
            form.get(0).submit();
        });

        return false;
    });
})();
JS);
?>

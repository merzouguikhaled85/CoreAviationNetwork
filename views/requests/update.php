<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use kartik\select2\Select2;
use app\models\Aircrafts;
use app\models\Requests;
use kartik\datetime\DateTimePicker;
use yii\web\JsExpression;
use yii\web\View;

/** @var yii\web\View $this */
/** @var app\models\Requests $request */

$this->title = 'Update Request';

/*
 * COMPATIBILITÉ DES ANCIENNES DEMANDES : si une ligne créée avant la migration
 * ne fournit pas encore de priorité, le formulaire montre Routine sans changer
 * son statut ni déclencher une sauvegarde anticipée.
 */
if ($request->operational_priority === null || $request->operational_priority === '') {
    $request->operational_priority = Requests::PRIORITY_ROUTINE;
}

/**
 * Register Yii asset.
 */
\yii\web\YiiAsset::register($this);

/**
 * Register Bootstrap Icons.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['position' => View::POS_HEAD]
);

/**
 * Register SweetAlert2 CSS and JS.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    ['position' => View::POS_HEAD]
);

$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
    ['depends' => [\yii\web\JqueryAsset::class]]
);

/**
 * Prepare back URL.
 */
$backUrl = Yii::$app->request->referrer ?: ['index'];

/**
 * Prepare current attachment safely.
 */
$currentAttachment = $request->attachment ?? null;
$currentAttachmentName = !empty($currentAttachment)
    ? basename((string) $currentAttachment)
    : 'No current attachment';

/**
 * Build current attachment URL safely.
 */
$buildFileUrl = static function ($file, $defaultFolder = 'uploads') {
    if (empty($file)) {
        return null;
    }

    $file = trim((string) $file);

    if ($file === '') {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $file)) {
        return $file;
    }

    if (strpos($file, '@web/') === 0) {
        return Yii::getAlias('@web') . '/' . ltrim(substr($file, 5), '/');
    }

    if (strpos($file, '/') !== false) {
        return Yii::$app->request->baseUrl . '/' . ltrim($file, '/');
    }

    return Yii::$app->request->baseUrl . '/' . trim($defaultFolder, '/') . '/' . ltrim($file, '/');
};

$currentAttachmentUrl = !empty($currentAttachment)
    ? $buildFileUrl($currentAttachment, 'uploads')
    : null;

/* AIRCRAFT SELECT DISPLAY: prepare readable labels and reliable option metadata. */
$aircraftList = Aircrafts::find()
    ->with('certificateType')
    ->where(['ao_id' => Yii::$app->session->get('ao_id')])
    ->orderBy(['manufacturer' => SORT_ASC, 'model' => SORT_ASC])
    ->all();
$aircraftSelectData = [];
$aircraftOptionAttributes = [];

foreach ($aircraftList as $aircraftItem) {
    $aircraftManufacturer = trim((string) $aircraftItem->manufacturer);
    $aircraftModel = trim((string) $aircraftItem->model);

    /* AIRCRAFT LABEL NORMALIZATION: avoid repeating the manufacturer when the stored model already contains it. */
    $aircraftName = $aircraftManufacturer !== ''
        && $aircraftModel !== ''
        && stripos($aircraftModel, $aircraftManufacturer) === 0
            ? $aircraftModel
            : trim($aircraftManufacturer . ' ' . $aircraftModel);
    $registration = trim((string) $aircraftItem->registration_number);
    $serialNumber = trim((string) $aircraftItem->serial_number);
    $certificateType = $aircraftItem->certificateType
        ? trim((string) $aircraftItem->certificateType->type)
        : '';

    $aircraftSelectData[$aircraftItem->aircraft_id] = $aircraftName;
    $aircraftOptionAttributes[$aircraftItem->aircraft_id] = [
        'data-aircraft-name' => $aircraftName,
        'data-registration' => $registration,
        'data-serial-number' => $serialNumber,
        'data-certificate-type' => $certificateType,
    ];
}

/* SCHEDULE AIRPORT CONTEXT: resolve the current relation inside the view so initial and validation-error renders are both safe. */
$selectedAirport = $request->destinationAirport;
$selectedAirportCountry = $selectedAirport ? trim((string) $selectedAirport->country_name) : '';

/* UPDATE ADVERTISING ZONE: reuse active campaign rules without changing the request workflow. */
$advertNow = date('Y-m-d H:i:s');
$updateAdverts = \app\models\Advert::find()
    ->where(['status' => 'active'])
    ->andWhere(['<=', 'start_date', $advertNow])
    ->andWhere(['>=', 'end_date', $advertNow])
    ->orderBy(['advert_id' => SORT_DESC])
    ->all();

/**
 * Register page CSS.
 */
$this->registerCss(<<<CSS
html,
body {
    max-width: 100%;
    overflow-x: hidden;
}

.create-page {
    padding: 14px 18px;
    background: #f5f7fb;
    min-height: calc(100vh - 84px);
    max-width: 100%;
    overflow-x: hidden;
}

.create-page .container-fluid {
    max-width: 100%;
    overflow-x: hidden;
    padding-left: 0;
    padding-right: 0;
}

.page-header-card {
    background: linear-gradient(135deg, #ffffff, #eef4ff);
    border-radius: 18px;
    padding: 14px 18px;
    margin-bottom: 12px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5eaf3;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
}

.dash-title {
    margin: 0;
    font-size: 23px;
    font-weight: 800;
    color: #1f2937;
}

.subtitle-text {
    color: #6b7280;
    margin-top: 6px;
    font-size: 14px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-page-action {
    border-radius: 9px;
    padding: 10px 18px;
    font-weight: 800;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    white-space: nowrap;
    text-decoration: none;
    transition: all .2s ease;
}

.btn-back {
    background: #ffffff;
    color: #334155 !important;
    border: 1px solid #cbd5e1;
}

.btn-back:hover {
    background: #f1f5f9;
    color: #0f172a !important;
    transform: translateY(-1px);
    text-decoration: none;
}

.form-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(360px, 430px);
    align-items: start;
    gap: 14px;
}

.form-card,
.update-ad-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 14px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5eaf3;
    max-width: 100%;
}

/* UPDATE PAGE SCROLL: prevent Grid from stretching the guide to the form height. */
.form-card {
    min-width: 0;
    align-self: start;
}

.update-ad-card {
    min-width: 0;
    align-self: start;
    position: sticky;
    top: 104px;
    padding: 0;
    overflow: hidden;
    background: #071a31;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 10px;
    font-size: 17px;
    font-weight: 900;
    color: #0f172a;
}

.form-section {
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 16px;
    padding: 11px;
    margin-bottom: 0;
}

.form-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 8px;
    color: #0f172a;
    font-size: 14px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.form-section-title i {
    color: #f59e0b;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px 12px;
}

/* UPDATE FORM COMPACT GRID: show all sections together on desktop without an inner scrollbar. */
#update-request-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px 12px;
}

.aircraft-section {
    grid-column: 1;
    grid-row: 1 / span 2;
}

.location-section {
    grid-column: 2;
    grid-row: 1;
}

.schedule-section {
    grid-column: 2;
    grid-row: 2;
}

.details-section {
    grid-column: 1 / -1;
}

.details-section .form-grid {
    grid-template-columns: minmax(280px, .8fr) minmax(0, 1.2fr);
    align-items: start;
}

.details-section .form-grid .full-width {
    grid-column: auto;
}

#update-request-form > .field-requests-aircraft_registration,
#update-request-form > .field-requests-serial_number,
#update-request-form > .field-requests-required_certificates,
#update-request-form > .field-requests-status,
#update-request-form > .field-requests-ao_id {
    display: none;
}

.form-grid .full-width {
    grid-column: 1 / -1;
}

.form-group {
    margin-bottom: 0;
}

.control-label,
.form-group label {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #64748b;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 5px;
}

.text-danger {
    color: #dc2626 !important;
}

.form-control,
select.form-control,
textarea.form-control {
    width: 100%;
    min-height: 40px;
    border-radius: 12px !important;
    border: 1px solid #cbd5e1 !important;
    background-color: #ffffff !important;
    color: #0f172a !important;
    font-size: 14px;
    font-weight: 700;
    padding: 7px 11px;
    box-shadow: none !important;
    transition: all .2s ease;
}

textarea.form-control {
    min-height: 102px;
    height: auto;
    resize: vertical;
}

.form-control:focus,
select.form-control:focus,
textarea.form-control:focus {
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.14) !important;
}

.form-control[disabled],
.form-control[readonly] {
    background: #f1f5f9 !important;
    color: #475569 !important;
    cursor: not-allowed;
}

.help-block,
.invalid-feedback {
    margin-top: 7px;
    font-size: 12px;
    font-weight: 700;
    color: #dc2626;
}

.has-error .form-control,
.has-error .select2-selection,
.has-error .custom-file-display {
    border-color: #fca5a5 !important;
}

.has-success .form-control,
.has-success .select2-selection,
.has-success .custom-file-display {
    border-color: #86efac !important;
}

/* Native select chevron */
.chevron-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image:
        linear-gradient(45deg, transparent 50%, #f59e0b 50%),
        linear-gradient(135deg, #f59e0b 50%, transparent 50%);
    background-position:
        calc(100% - 20px) calc(50% + 1px),
        calc(100% - 14px) calc(50% + 1px);
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
    padding-right: 42px !important;
}

/* Select2 design */
.create-page .select2-container {
    width: 100% !important;
}

.create-page .select2-container--krajee-bs5 .select2-selection,
.create-page .select2-container--krajee .select2-selection,
.create-page .select2-container .select2-selection--single {
    min-height: 40px !important;
    border-radius: 12px !important;
    border: 1px solid #cbd5e1 !important;
    background-color: #ffffff !important;
    display: flex !important;
    align-items: center !important;
    box-shadow: none !important;
}

.create-page .select2-container--krajee-bs5 .select2-selection__rendered,
.create-page .select2-container--krajee .select2-selection__rendered,
.create-page .select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 28px !important;
    color: #0f172a !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    padding-left: 12px !important;
    padding-right: 42px !important;
}

.create-page .select2-container--krajee-bs5 .select2-selection__arrow,
.create-page .select2-container--krajee .select2-selection__arrow,
.create-page .select2-container .select2-selection--single .select2-selection__arrow {
    height: 38px !important;
    right: 10px !important;
}

.create-page .select2-container--open .select2-selection {
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.14) !important;
}

.create-page .select2-selection__clear {
    display: none !important;
}

/* RICH SELECT OPTIONS: concise primary value with operational metadata below. */
.aircraft-option,
.airport-option {
    display: grid;
    gap: 3px;
    padding: 3px 2px;
    line-height: 1.3;
}

.aircraft-option-title,
.airport-option-title {
    color: #10233f;
    font-size: 13px;
    font-weight: 600;
}

.aircraft-option-meta,
.airport-option-meta {
    color: #687c98;
    font-size: 11px;
    font-weight: 400;
}

.select2-results__option--highlighted .aircraft-option-title,
.select2-results__option--highlighted .aircraft-option-meta,
.select2-results__option--highlighted .airport-option-title,
.select2-results__option--highlighted .airport-option-meta {
    color: inherit;
}

.aircraft-select-dropdown .select2-results__option,
.airport-select-dropdown .select2-results__option {
    padding: 8px 10px;
}

/* AIRCRAFT FIELD PRESENTATION: keep the selected identity readable and align its metadata. */
.aircraft-section .form-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.aircraft-selection {
    display: inline-flex;
    align-items: center;
    min-width: 0;
    max-width: 100%;
    gap: 8px;
}

.aircraft-selection i,
.aircraft-field-label i,
.aircraft-section .control-label > i {
    flex: 0 0 auto;
    color: #1688d4;
}

.aircraft-selection span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.aircraft-option-title i {
    margin-right: 7px;
    color: #1688d4;
}

/* DateTimePicker cleanup */
.create-page .kv-datetime-remove,
.create-page .kv-date-remove,
.create-page .glyphicon-remove,
.create-page .input-group-addon .glyphicon-remove,
.create-page .input-group-text .glyphicon-remove,
.create-page .input-group-addon[title="Clear field"],
.create-page .input-group-text[title="Clear field"],
.create-page .input-group-addon[title="Clear"],
.create-page .input-group-text[title="Clear"] {
    display: none !important;
}

.create-page .input-group {
    width: 100%;
}

.create-page .input-group .form-control {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
}

.create-page .input-group-addon,
.create-page .input-group-text {
    border-radius: 0 12px 12px 0 !important;
    border: 1px solid #cbd5e1 !important;
    background: #fff7ed !important;
    color: #c2410c !important;
    font-weight: 900;
}

/* Progress steps */
.progress-bar-container {
    display: grid;
    grid-template-columns: repeat(10, minmax(0, 1fr));
    gap: 5px;
    overflow: visible;
    padding: 8px;
    margin-bottom: 10px;
    background: #ffffff;
    border: 1px solid #e5eaf3;
    border-radius: 16px;
}

.progress-bar-step {
    position: relative;
    min-width: 0;
    padding: 7px 5px;
    border-radius: 999px;
    background: #e5e7eb;
    color: #475569;
    font-size: 10px;
    font-weight: 900;
    white-space: normal;
    text-align: center;
}

.progress-bar-step::after {
    content: "\\F285";
    font-family: "bootstrap-icons";
    margin-left: 8px;
    font-size: 10px;
    color: currentColor;
}

.progress-bar-step:last-child::after {
    content: "";
    margin-left: 0;
}

.step-realized {
    background: #dcfce7;
    color: #166534;
}

.step-active {
    background: #fff7ed;
    color: #c2410c;
    box-shadow: 0 6px 14px rgba(245, 158, 11, .18);
}

/* Custom English file input */
.custom-file-wrapper {
    width: 100%;
}

.custom-file-native {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    opacity: 0 !important;
    overflow: hidden !important;
    z-index: -1 !important;
}

.custom-file-display {
    min-height: 46px;
    border-radius: 14px;
    border: 1px dashed #cbd5e1;
    background: #ffffff;
    display: flex;
    align-items: center;
    overflow: hidden;
}

.custom-file-button {
    min-height: 46px;
    padding: 0 16px;
    background: #f59e0b;
    color: #ffffff !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    border-right: 1px solid #d97706;
    cursor: pointer;
    white-space: nowrap;
    margin: 0;
    transition: all .2s ease;
}

.custom-file-button:hover {
    background: #d97706;
}

.custom-file-name {
    padding: 0 14px;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.current-file-box {
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 13px;
}

.current-file-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 12px;
    background: #fff7ed;
    color: #c2410c;
    display: flex;
    align-items: center;
    justify-content: center;
}

.current-file-icon i {
    font-size: 20px;
}

.current-file-text {
    min-width: 0;
}

.current-file-label {
    display: block;
    color: #64748b;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.current-file-name {
    display: block;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.current-file-link {
    color: #c2410c !important;
    text-decoration: none;
}

.current-file-link:hover {
    color: #9a3412 !important;
    text-decoration: underline;
}

/* Actions */
.form-actions {
    grid-column: 1 / -1;
    margin-top: 0;
    padding-top: 10px;
    border-top: 1px solid #e5eaf3;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-form-action {
    min-height: 44px;
    border-radius: 12px;
    padding: 11px 18px;
    font-size: 13px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    border: 1px solid rgba(15, 23, 42, .12);
    transition: all .2s ease;
}

.btn-update {
    background: #f59e0b;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(245, 158, 11, 0.24);
}

.btn-update:hover:not(:disabled) {
    background: #d97706;
    color: #ffffff !important;
    transform: translateY(-1px);
    text-decoration: none;
}

.btn-update:disabled {
    opacity: .55;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-cancel {
    background: #64748b;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(100, 116, 139, 0.18);
}

.btn-cancel:hover {
    background: #475569;
    color: #ffffff !important;
    transform: translateY(-1px);
    text-decoration: none;
}

/* Alerts */
.alert {
    border-radius: 14px;
    border: none;
    font-weight: 800;
    padding: 14px 16px;
    margin-bottom: 18px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Helper card */
.helper-list {
    display: grid;
    gap: 10px;
}

.helper-item {
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 12px 13px;
    transition: border-color .18s ease, background .18s ease, transform .18s ease;
}

.helper-item:hover {
    border-color: #bfd6ef;
    background: #f4f8fd;
    transform: translateX(-2px);
}

.helper-label {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    margin-bottom: 5px;
}

.helper-label i {
    color: #f59e0b;
}

.helper-text {
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.5;
    margin: 0;
}

/* SCHEDULE TIME BASIS: make the local airport/country convention explicit. */
.schedule-time-note {
    grid-column: 1 / -1;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 11px 13px;
    border: 1px solid #b9d9f5;
    border-radius: 11px;
    color: #315777;
    background: #f2f8fe;
    font-size: 12px;
    font-weight: 400;
    line-height: 1.45;
}

.schedule-time-note > i {
    margin-top: 1px;
    color: #1769e0;
    font-size: 16px;
}

.schedule-time-note strong {
    color: #173b5c;
    font-weight: 600;
}

/* UPDATE ADVERTISING ZONE: same visual language as the existing aviation campaign carousels. */
.update-ad-carousel,
.update-ad-slider,
.update-ad-slide,
.update-ad-media-wrap {
    width: 100%;
    height: 100%;
}

.update-ad-carousel {
    position: relative;
    width: 100%;
    aspect-ratio: 4 / 5;
    max-height: calc(100vh - 220px);
    min-height: 430px;
    overflow: hidden;
    background: #071a31;
}

.update-ad-slider {
    display: flex;
    transition: transform .65s cubic-bezier(.22, .61, .36, 1);
    will-change: transform;
}

.update-ad-slide {
    position: relative;
    flex: 0 0 100%;
    min-width: 100%;
    overflow: hidden;
    background: #071a31;
}

.update-ad-media-wrap {
    position: relative;
    overflow: hidden;
}

.update-ad-media {
    position: absolute;
    inset: 0;
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    background: #071a31;
}

.update-ad-shade {
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    background: linear-gradient(180deg, rgba(4, 17, 34, .12), transparent 42%),
        linear-gradient(0deg, rgba(4, 17, 34, .88), transparent 58%);
}

.update-ad-topbar {
    position: absolute;
    z-index: 4;
    top: 15px;
    left: 15px;
    right: 15px;
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.update-ad-sponsored,
.update-ad-counter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 29px;
    padding: 0 10px;
    border: 1px solid rgba(255, 255, 255, .3);
    border-radius: 999px;
    color: #fff;
    background: rgba(7, 26, 49, .58);
    backdrop-filter: blur(9px);
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}

.update-ad-caption {
    position: absolute;
    z-index: 4;
    left: 20px;
    right: 20px;
    bottom: 48px;
    color: #fff;
}

.update-ad-caption span {
    display: block;
    margin-bottom: 5px;
    color: #71d3ff;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.update-ad-caption strong {
    display: block;
    font-size: 23px;
    line-height: 1.12;
}

.update-ad-nav {
    position: absolute;
    z-index: 6;
    top: 50%;
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, .5);
    border-radius: 50%;
    color: #0d3261;
    background: rgba(255, 255, 255, .92);
    transform: translateY(-50%);
    transition: transform .2s ease, background .2s ease;
}

.update-ad-nav:hover {
    background: #fff;
    transform: translateY(-50%) scale(1.06);
}

.update-ad-prev { left: 14px; }
.update-ad-next { right: 14px; }

.update-ad-dots {
    position: absolute;
    z-index: 6;
    left: 50%;
    bottom: 20px;
    display: flex;
    gap: 6px;
    transform: translateX(-50%);
}

.update-ad-dot {
    width: 8px;
    height: 8px;
    padding: 0;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, .45);
    transition: width .2s ease, background .2s ease;
}

.update-ad-dot.is-active {
    width: 24px;
    background: #fff;
}

.update-ad-empty {
    min-height: 430px;
    display: grid;
    place-content: center;
    gap: 10px;
    padding: 24px;
    color: rgba(255, 255, 255, .8);
    text-align: center;
    background: radial-gradient(circle at top, rgba(0, 178, 255, .2), transparent 42%), #071a31;
}

.update-ad-empty i {
    color: #71d3ff;
    font-size: 38px;
}

/* Spinner */
#loading-spinner {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, .35);
    backdrop-filter: blur(3px);
    align-items: center;
    justify-content: center;
}

.spinner-box {
    background: #ffffff;
    border-radius: 18px;
    padding: 20px 24px;
    box-shadow: 0 20px 45px rgba(15,23,42,.25);
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 900;
    color: #0f172a;
}

.spinner-box img {
    width: 34px;
    height: 34px;
}

/* SweetAlert2 warning design */
.swal-update-popup {
    width: 380px !important;
    border-radius: 18px !important;
    padding: 22px !important;
}

.swal-update-title {
    font-size: 20px !important;
    font-weight: 900 !important;
    color: #0f172a !important;
}

.swal-update-html {
    font-size: 14px !important;
    font-weight: 700 !important;
    color: #64748b !important;
}

.swal-update-confirm {
    border-radius: 10px !important;
    padding: 10px 18px !important;
    font-size: 13px !important;
    font-weight: 900 !important;
    background: #f59e0b !important;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(245, 158, 11, 0.25) !important;
}

.swal-update-confirm:hover {
    background: #d97706 !important;
}

.swal-update-cancel {
    border-radius: 10px !important;
    padding: 10px 18px !important;
    font-size: 13px !important;
    font-weight: 900 !important;
    background: #64748b !important;
}

.swal2-actions {
    gap: 12px !important;
}

@media (max-width: 992px) {
    .create-page {
        padding: 14px;
    }

    .page-header-card {
        padding: 18px;
    }

    .dash-title {
        font-size: 23px;
    }

    .form-layout {
        grid-template-columns: 1fr;
    }

    /* UPDATE PAGE RESPONSIVE: advertising returns to document flow on tablet/mobile. */
    .update-ad-card {
        position: static;
        top: auto;
    }

    #update-request-form {
        grid-template-columns: 1fr;
    }

    .aircraft-section,
    .location-section,
    .schedule-section,
    .details-section {
        grid-column: 1;
        grid-row: auto;
    }

    .update-ad-carousel {
        aspect-ratio: 16 / 9;
        min-height: 0;
        max-height: none;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .progress-bar-container {
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .create-page {
        padding: 10px;
    }

    .page-header-card,
    .form-card,
    .update-ad-card {
        border-radius: 14px;
        padding: 16px;
    }

    .header-actions {
        width: 100%;
    }

    .btn-page-action,
    .btn-form-action {
        width: 100%;
    }

    .form-actions {
        justify-content: stretch;
    }

    .custom-file-display {
        flex-direction: column;
        align-items: stretch;
    }

    .custom-file-button {
        border-right: 0;
        border-bottom: 1px solid #d97706;
        width: 100%;
    }

    .current-file-box {
        flex-direction: column;
        align-items: stretch;
    }

    .current-file-name {
        white-space: normal;
    }

    .details-section .form-grid {
        grid-template-columns: 1fr;
    }

    .details-section .form-grid .full-width {
        grid-column: 1;
    }

    .progress-bar-container {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
CSS);
?>

<!-- SHARED FORM SYSTEM: presentation only; request update rules remain unchanged. -->
<main class="dash-content create-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: #f59e0b;">
                        <i class="bi bi-pencil-square"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Update aircraft, airport, schedule and maintenance request details.
                </div>
            </div>

            <div class="header-actions">
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Requests',
                    $backUrl,
                    ['class' => 'btn-page-action btn-back']
                ) ?>
            </div>
        </div>

        <!-- Flash messages -->
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
            </div>
        <?php endif; ?>

        <!-- Update progress -->
        <?php if ($statuss != null): ?>
            <div class="progress-bar-container">
                <span class="progress-bar-step step-realized">Create a Request</span>
                <span class="progress-bar-step step-realized">MRO Quote</span>
                <span class="progress-bar-step step-realized">PO Loaded</span>
                <span class="progress-bar-step step-realized">PO Accepted By MRO</span>
                <span class="progress-bar-step step-realized">Work Started</span>
                <span class="progress-bar-step step-active">Update Request</span>
                <span class="progress-bar-step">MRO Quote</span>
                <span class="progress-bar-step">MRO Report</span>
                <span class="progress-bar-step">AO Feedback</span>
                <span class="progress-bar-step">Request Closed</span>
            </div>
        <?php endif; ?>

        <!-- Loading spinner -->
        <div id="loading-spinner">
            <div class="spinner-box">
                <img src="<?= Yii::getAlias('@web/img/spinner.gif') ?>" alt="Loading..." />
                <span>Updating request...</span>
            </div>
        </div>

        <div class="form-layout">

            <!-- Main update form -->
            <div class="form-card">
                <h2 class="section-title">
                    <i class="bi bi-pencil-square text-warning"></i>
                    Request Information
                </h2>

                <?php $form = ActiveForm::begin([
                    'id' => 'update-request-form',
                    'options' => [
                        'enctype' => 'multipart/form-data',
                    ],
                    'enableClientValidation' => true,
                    'enableAjaxValidation' => false,
                ]); ?>

                <!-- Aircraft information -->
                <div class="form-section aircraft-section">
                    <h2 class="form-section-title">
                        <i class="bi bi-airplane-engines"></i>
                        Aircraft Information
                    </h2>

                    <div class="form-grid">

                        <!-- Aircraft -->
                        <div class="full-width">
                            <?= $form->field($request, 'aircraft_id')->widget(Select2::class, [
                                'data' => $aircraftSelectData,
                                'options' => [
                                    'placeholder' => 'Select an aircraft',
                                    'id' => 'aircraft-dropdown',
                                    'options' => $aircraftOptionAttributes,
                                ],
                                'pluginOptions' => [
                                    'allowClear' => false,
                                    'dropdownCssClass' => 'aircraft-select-dropdown',
                                    'templateResult' => new JsExpression('function(data) {
                                        if (!data.id || !data.element) { return data.text; }
                                        var option = jQuery(data.element);
                                        var title = option.data("aircraft-name") || data.text || "";
                                        var registration = option.data("registration") || "N/A";
                                        var serial = option.data("serial-number") || "N/A";
                                        return jQuery("<div class=\"aircraft-option\"><span class=\"aircraft-option-title\"><i class=\"bi bi-airplane-engines\"></i><span></span></span><span class=\"aircraft-option-meta\"></span></div>")
                                            .find(".aircraft-option-title span").text(title).end()
                                            .find(".aircraft-option-meta").text("Registration: " + registration + " · S/N: " + serial).end();
                                    }'),
                                    'templateSelection' => new JsExpression('function(data) {
                                        if (!data.id || !data.element) { return data.text; }
                                        var option = jQuery(data.element);
                                        var title = option.data("aircraft-name") || data.text || "";
                                        var registration = option.data("registration") || "N/A";
                                        return jQuery("<span class=\"aircraft-selection\"><i class=\"bi bi-airplane-engines\"></i><span></span></span>")
                                            .find("span").text(title + " · " + registration).end();
                                    }'),
                                ],
                            ])->label("<span class='aircraft-field-label'><i class='bi bi-airplane-engines'></i> Aircraft</span> <span class='text-danger'>*</span>", ['encode' => false]) ?>
                        </div>

                        <!-- National Aviation Authority -->
                        <div class="full-width">
                            <?= $form->field($request, 'required_certificates')->textInput([
                                'maxlength' => true,
                                'disabled' => true,
                                'id' => 'required-certificates',
                                'class' => 'form-control',
                            ])->label('<i class="bi bi-shield-check"></i> National Aviation Authority', ['encode' => false]) ?>
                        </div>

                        <!-- Aircraft registration -->
                        <div>
                            <?= $form->field($request, 'aircraft_registration')->textInput([
                                'maxlength' => true,
                                'disabled' => true,
                                'id' => 'aircraft-registration',
                                'class' => 'form-control',
                            ])->label('<i class="bi bi-card-text"></i> Aircraft Registration', ['encode' => false]) ?>
                        </div>

                        <!-- Serial number -->
                        <div>
                            <?= $form->field($request, 'serial_number')->textInput([
                                'maxlength' => true,
                                'disabled' => true,
                                'id' => 'serial-number',
                                'class' => 'form-control',
                            ])->label('<i class="bi bi-upc-scan"></i> Serial Number', ['encode' => false]) ?>
                        </div>

                    </div>
                </div>

                <!-- Maintenance location -->
                <div class="form-section location-section">
                    <h2 class="form-section-title">
                        <i class="bi bi-geo-alt"></i>
                        Maintenance Location
                    </h2>

                    <div class="form-grid">

                        <!-- Destination / ICAO -->
                        <div class="full-width" id="destination-row">
                            <?= $form->field($request, 'destination')->widget(Select2::class, [
                                'data' => $request->destination && $selectedAirportName
                                    ? [$request->destination => $selectedAirportName]
                                    : [],
                                'options' => [
                                    'placeholder' => 'Type to search for an airport',
                                    'id' => 'destination-dropdown',
                                ],
                                'pluginOptions' => [
                                    'allowClear' => false,
                                    'minimumInputLength' => 3,
                                    'dropdownCssClass' => 'airport-select-dropdown',
                                    'ajax' => [
                                        'url' => Url::to(['requests/search-airports']),
                                        'dataType' => 'json',
                                        'delay' => 250,
                                        'data' => new JsExpression('function(params) {
                                            return {
                                                term: params.term
                                            };
                                        }'),
                                        'processResults' => new JsExpression('function(data) {
                                            return {
                                                results: data
                                            };
                                        }'),
                                    ],
                                    'escapeMarkup' => new JsExpression('function(markup) {
                                        return markup;
                                    }'),
                                    'templateResult' => new JsExpression('function(data) {
                                        if (!data.id) { return data.text; }
                                        var title = [data.icao, data.airport].filter(Boolean).join(" · ");
                                        var location = [data.city, data.country].filter(Boolean).join(", ");
                                        return jQuery("<div class=\"airport-option\"><span class=\"airport-option-title\"></span><span class=\"airport-option-meta\"></span></div>")
                                            .find(".airport-option-title").text(title || data.text || "").end()
                                            .find(".airport-option-meta").text(location || "Location not specified").end();
                                    }'),
                                    'templateSelection' => new JsExpression('function(data) {
                                        return data.text || "' . Html::encode($selectedAirportName) . '";
                                    }'),
                                ],
                            ])->label("ICAO (Airport) <span class='text-danger'>*</span>", ['encode' => false]) ?>
                        </div>

                    </div>
                </div>

                <!-- Hidden aircraft values -->
                <?= $form->field($request, 'aircraft_registration')->hiddenInput([
                    'id' => 'aircraftregistration',
                ])->label(false) ?>

                <?= $form->field($request, 'serial_number')->hiddenInput([
                    'id' => 'serialnumber',
                ])->label(false) ?>

                <?= $form->field($request, 'required_certificates')->hiddenInput([
                    'id' => 'requiredcertificates',
                ])->label(false) ?>

                <!--
                    PRIORITÉ OPÉRATIONNELLE : la mise à jour utilise exactement les
                    mêmes valeurs et la même présentation que la création. Changer
                    cette information ne déclenche aucune transition de statut.
                -->
                <div class="form-section operational-priority-section" data-operational-priority>
                    <h2 class="form-section-title">
                        <i class="bi bi-broadcast-pin" aria-hidden="true"></i>
                        Operational Priority
                    </h2>
                    <p class="form-section-subtitle">
                        Indicate how quickly MRO partners should review this request.
                    </p>

                    <?= $form->field($request, 'operational_priority', [
                        'template' => "{input}\n{error}",
                        'options' => ['class' => 'operational-priority-field'],
                    ])->radioList(Requests::getOperationalPriorityOptions(), [
                        'class' => 'operational-priority-list',
                        'item' => static function ($index, $label, $name, $checked, $value) {
                            $icons = [
                                Requests::PRIORITY_AOG => 'bi-exclamation-octagon',
                                Requests::PRIORITY_URGENT => 'bi-lightning-charge',
                                Requests::PRIORITY_ROUTINE => 'bi-calendar-check',
                            ];
                            $descriptions = [
                                Requests::PRIORITY_AOG => 'Aircraft grounded · response time required',
                                Requests::PRIORITY_URGENT => 'Fast review · response time optional',
                                Requests::PRIORITY_ROUTINE => 'Standard planning · no response deadline',
                            ];

                            return Html::tag('label',
                                Html::radio($name, $checked, [
                                    'value' => $value,
                                    'class' => 'operational-priority-input',
                                ])
                                . Html::tag('span', Html::tag('i', '', [
                                    'class' => 'bi ' . $icons[$value],
                                    'aria-hidden' => 'true',
                                ]), ['class' => 'operational-priority-icon'])
                                . Html::tag('span',
                                    Html::tag('span', Html::encode($label), ['class' => 'operational-priority-name'])
                                    . Html::tag('span', Html::encode($descriptions[$value]), ['class' => 'operational-priority-description']),
                                    ['class' => 'operational-priority-copy']
                                ),
                                ['class' => 'operational-priority-card priority-' . $value]
                            );
                        },
                    ])->label(false) ?>

                    <!--
                        DÉLA DE RÉPONSE : les raccourcis et la saisie libre alimentent
                        un seul champ en minutes. Le serveur calcule ensuite l'échéance
                        UTC afin de ne jamais dépendre de l'horloge du navigateur.
                    -->
                    <div class="operational-response-panel" data-response-panel hidden>
                        <div class="operational-response-heading">
                            <i class="bi bi-stopwatch" aria-hidden="true"></i>
                            <div>
                                <span class="operational-response-title">Expected response time</span>
                                <span class="operational-response-rule" data-response-rule></span>
                            </div>
                        </div>
                        <div class="operational-response-controls">
                            <?= $form->field($request, 'response_required_minutes')->input('number', [
                                'id' => 'response-required-minutes',
                                'min' => 15,
                                'max' => 1440,
                                'step' => 15,
                                'placeholder' => 'Custom minutes',
                            ])->label('Response time (minutes)', ['data-response-label' => true]) ?>
                            <div class="operational-response-presets" aria-label="Quick response times">
                                <button type="button" data-response-minutes="30">30 min</button>
                                <button type="button" data-response-minutes="60">1 h</button>
                                <button type="button" data-response-minutes="120">2 h</button>
                                <button type="button" data-response-minutes="240">4 h</button>
                            </div>
                        </div>
                        <div class="operational-deadline-preview" data-deadline-preview></div>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="form-section schedule-section">
                    <h2 class="form-section-title">
                        <i class="bi bi-calendar-event"></i>
                        Schedule
                    </h2>

                    <div class="form-grid">

                        <!-- ETA -->
                        <div>
                            <?= $form->field($request, 'eta')->widget(DateTimePicker::class, [
                                'options' => [
                                    'placeholder' => 'Select date and time...',
                                    'id' => 'eta',
                                    'class' => 'form-control',
                                ],
                                'pluginOptions' => [
                                    'format' => 'yyyy-mm-dd hh:ii:ss',
                                    'autoclose' => true,
                                    'todayHighlight' => true,
                                    'showRemove' => false,
                                ],
                            ])->label('ETA <span class="text-danger">*</span>', ['encode' => false]) ?>
                        </div>

                        <!-- ETD -->
                        <div>
                            <?= $form->field($request, 'etd')->widget(DateTimePicker::class, [
                                'options' => [
                                    'placeholder' => 'Select date and time...',
                                    'id' => 'etd',
                                    'class' => 'form-control',
                                ],
                                'pluginOptions' => [
                                    'format' => 'yyyy-mm-dd hh:ii:ss',
                                    'autoclose' => true,
                                    'todayHighlight' => true,
                                    'showRemove' => false,
                                ],
                            ])->label('ETD <span class="text-danger">*</span>', ['encode' => false]) ?>
                        </div>

                        <!-- SCHEDULE LOCAL TIME: informational only; stored values and validation stay unchanged. -->
                        <div class="schedule-time-note" id="schedule-time-note">
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            <div>
                                <strong>Local maintenance time.</strong>
                                ETA and ETD must be entered using the local time zone applicable to the selected airport and country.
                                <span id="schedule-country"><?= $selectedAirportCountry !== '' ? ' Selected country: ' . Html::encode($selectedAirportCountry) . '.' : '' ?></span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Request details -->
                <div class="form-section details-section">
                    <h2 class="form-section-title">
                        <i class="bi bi-file-earmark-text"></i>
                        Request Details
                    </h2>

                    <div class="form-grid">

                        <!-- Attachment -->
                        <div class="full-width">
                            <label class="control-label">
                                Attachment
                            </label>

                            <div class="custom-file-wrapper">
                                <?= $form->field($request, 'attachment', [
                                    'template' => "{input}\n{error}",
                                    'options' => ['class' => 'custom-file-field'],
                                ])->fileInput([
                                    'accept' => 'image/*,.pdf,.doc,.docx',
                                    'id' => 'attachment-input',
                                    'class' => 'custom-file-native',
                                ]) ?>

                                <div class="custom-file-display">
                                    <label for="attachment-input" class="custom-file-button">
                                        <i class="bi bi-upload"></i>
                                        Choose File
                                    </label>

                                    <span id="attachment-file-name" class="custom-file-name">
                                        No file selected
                                    </span>
                                </div>
                            </div>

                            <?php if (!empty($currentAttachmentUrl)): ?>
                                <div class="current-file-box">
                                    <div class="current-file-icon">
                                        <i class="bi bi-paperclip"></i>
                                    </div>

                                    <div class="current-file-text">
                                        <span class="current-file-label">
                                            Current Attachment
                                        </span>

                                        <?= Html::a(
                                            Html::encode($currentAttachmentName),
                                            $currentAttachmentUrl,
                                            [
                                                'class' => 'current-file-name current-file-link',
                                                'target' => '_blank',
                                                'rel' => 'noopener',
                                            ]
                                        ) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Request details -->
                        <div class="full-width">
                            <?= $form->field($request, 'request_details')->textarea([
                                'rows' => 4,
                                'class' => 'form-control',
                                'placeholder' => 'Describe the requested maintenance work...',
                            ])->label('Request Details <span class="text-danger">*</span>', ['encode' => false]) ?>
                        </div>

                    </div>
                </div>

                <!-- Hidden status -->
                <?= $form->field($request, 'status')->hiddenInput([
                    'value' => $request->status ?: 'created',
                ])->label(false) ?>

                <!-- Hidden AO ID -->
                <?= $form->field($request, 'ao_id')->hiddenInput([
                    'value' => Yii::$app->session->get('ao_id'),
                ])->label(false) ?>

                <!-- Form actions -->
                <div class="form-actions">
                    <?= Html::a(
                        '<i class="bi bi-arrow-left-circle"></i> Back to Requests',
                        ['index'],
                        ['class' => 'btn-form-action btn-cancel']
                    ) ?>

                    <?= Html::submitButton(
                        '<i class="bi bi-check-circle"></i> Update Request',
                        [
                            'class' => 'btn-form-action btn-update',
                            'id' => 'submit-button',
                            'disabled' => true,
                        ]
                    ) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>

            <!-- UPDATE ADVERTISING ZONE: replaces the guide while preserving the form workflow. -->
            <aside class="update-ad-card" aria-label="Sponsored content">
                <?php if (!empty($updateAdverts)): ?>
                    <div class="update-ad-carousel" id="update-ad-carousel">
                        <div class="update-ad-slider" id="update-ad-slider">
                            <?php foreach ($updateAdverts as $index => $advert): ?>
                                <?php
                                $advertUsesUrl = !empty($advert->use_url) && !empty($advert->url);
                                $advertContentUrl = $advertUsesUrl
                                    ? $advert->url
                                    : Yii::getAlias('@web/uploads/') . ltrim((string) $advert->content, '/');
                                $advertType = strtolower(trim((string) $advert->advert_type));
                                $advertTitle = !empty($advert->title) ? $advert->title : 'Aviation partner';
                                ?>
                                <article class="update-ad-slide <?= $index === 0 ? 'is-active' : '' ?>">
                                    <div class="update-ad-media-wrap">
                                        <?php if ($advertType === 'photo'): ?>
                                            <?= Html::img($advertContentUrl, [
                                                'class' => 'update-ad-media',
                                                'alt' => Html::encode($advertTitle),
                                                'loading' => $index === 0 ? 'eager' : 'lazy',
                                            ]) ?>
                                        <?php else: ?>
                                            <video class="update-ad-media" muted loop playsinline preload="metadata" <?= $index === 0 ? 'autoplay' : '' ?>>
                                                <source src="<?= Html::encode($advertContentUrl) ?>" type="video/mp4">
                                            </video>
                                        <?php endif; ?>

                                        <div class="update-ad-shade" aria-hidden="true"></div>
                                        <div class="update-ad-topbar">
                                            <span class="update-ad-sponsored"><i class="bi bi-megaphone"></i>&nbsp; Sponsored</span>
                                            <span class="update-ad-counter"><?= (int) ($index + 1) ?> / <?= count($updateAdverts) ?></span>
                                        </div>
                                        <div class="update-ad-caption">
                                            <span>Core Aviation Network</span>
                                            <strong><?= Html::encode($advertTitle) ?></strong>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($updateAdverts) > 1): ?>
                            <button type="button" class="update-ad-nav update-ad-prev" id="update-ad-prev" aria-label="Previous advertisement">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button type="button" class="update-ad-nav update-ad-next" id="update-ad-next" aria-label="Next advertisement">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                            <div class="update-ad-dots" aria-label="Advertisement navigation">
                                <?php foreach ($updateAdverts as $index => $advert): ?>
                                    <button type="button" class="update-ad-dot <?= $index === 0 ? 'is-active' : '' ?>" data-ad-target="<?= (int) $index ?>" aria-label="Show advertisement <?= (int) ($index + 1) ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="update-ad-empty">
                        <i class="bi bi-airplane-engines"></i>
                        <span>Core Aviation Network</span>
                    </div>
                <?php endif; ?>
            </aside>

        </div>

    </div>
</main>

<?php
/**
 * Register page JavaScript.
 */
$this->registerJs(<<<JS
var aircraftDropdown = document.getElementById("aircraft-dropdown");

/* AIRCRAFT LIVE SYNC: update visible operational data and submitted hidden values from one source. */
function syncAircraftInformation() {
    if (!aircraftDropdown) {
        return;
    }

    var selectedAircraft = aircraftDropdown.options[aircraftDropdown.selectedIndex];
    var aircraftSerialNumber = selectedAircraft
        ? selectedAircraft.getAttribute("data-serial-number") || ""
        : "";
    var aircraftRegistration = selectedAircraft
        ? selectedAircraft.getAttribute("data-registration") || ""
        : "";
    var certificateType = selectedAircraft
        ? selectedAircraft.getAttribute("data-certificate-type") || ""
        : "";

    var fieldValues = {
        "aircraft-registration": aircraftRegistration,
        "serial-number": aircraftSerialNumber,
        "required-certificates": certificateType,
        "aircraftregistration": aircraftRegistration,
        "serialnumber": aircraftSerialNumber,
        "requiredcertificates": certificateType
    };

    Object.keys(fieldValues).forEach(function (fieldId) {
        var field = document.getElementById(fieldId);
        if (field) {
            field.value = fieldValues[fieldId];
        }
    });

    validateUpdateForm();
}

if (aircraftDropdown) {
    aircraftDropdown.addEventListener("change", syncAircraftInformation);

    /* SELECT2 EVENT BRIDGE: Select2 selection must trigger the same metadata refresh. */
    if (typeof jQuery !== "undefined") {
        jQuery(aircraftDropdown).on("select2:select", syncAircraftInformation);
    }
}

var form = document.getElementById("update-request-form");
var submitButton = document.getElementById("submit-button");
var loadingIndicator = document.getElementById("loading-spinner");
var attachmentInput = document.getElementById("attachment-input");
var attachmentFileName = document.getElementById("attachment-file-name");

if (attachmentInput && attachmentFileName) {
    attachmentInput.addEventListener("change", function () {
        attachmentFileName.textContent = this.files && this.files.length > 0
            ? this.files[0].name
            : "No file selected";
    });
}

function parseDateValue(value) {
    if (!value) {
        return null;
    }

    var parsedDate = new Date(value.replace(" ", "T"));

    if (isNaN(parsedDate.getTime())) {
        return null;
    }

    return parsedDate;
}

function setFieldError(input, message) {
    if (!input) {
        return;
    }

    var fieldContainer = input.closest(".form-group") || input.closest("[class*='field-']");

    if (!fieldContainer) {
        fieldContainer = input.parentElement;
    }

    if (!fieldContainer) {
        return;
    }

    fieldContainer.classList.add("has-error");
    fieldContainer.classList.remove("has-success");

    var helpBlock = fieldContainer.querySelector(".help-block");

    if (helpBlock) {
        helpBlock.innerHTML = message;
        helpBlock.style.display = "block";
    }
}

function clearFieldError(input) {
    if (!input) {
        return;
    }

    var fieldContainer = input.closest(".form-group") || input.closest("[class*='field-']");

    if (!fieldContainer) {
        fieldContainer = input.parentElement;
    }

    if (!fieldContainer) {
        return;
    }

    fieldContainer.classList.remove("has-error");
    fieldContainer.classList.add("has-success");

    var helpBlock = fieldContainer.querySelector(".help-block");

    if (helpBlock) {
        helpBlock.innerHTML = "";
        helpBlock.style.display = "none";
    }
}

function getSelect2Value(id) {
    var element = document.getElementById(id);

    if (!element) {
        return "";
    }

    if (typeof jQuery !== "undefined" && jQuery(element).data("select2")) {
        return jQuery(element).val();
    }

    return element.value;
}

function validateUpdateForm() {
    var isValid = true;

    var aircraftInput = document.getElementById("aircraft-dropdown");
    var destinationInput = document.getElementById("destination-dropdown");
    var etaInput = document.getElementById("eta");
    var etdInput = document.getElementById("etd");
    var detailsInput = document.querySelector("[name='Requests[request_details]']");

    var aircraftValue = aircraftInput ? aircraftInput.value : "";
    var destinationValue = getSelect2Value("destination-dropdown");
    var etaValue = etaInput ? etaInput.value.trim() : "";
    var etdValue = etdInput ? etdInput.value.trim() : "";
    var detailsValue = detailsInput ? detailsInput.value.trim() : "";

    if (!aircraftValue) {
        setFieldError(aircraftInput, "Aircraft is required.");
        isValid = false;
    } else {
        clearFieldError(aircraftInput);
    }

    if (!destinationValue) {
        setFieldError(destinationInput, "Maintenance location is required.");
        isValid = false;
    } else {
        clearFieldError(destinationInput);
    }

    if (!etaValue) {
        setFieldError(etaInput, "ETA cannot be blank.");
        isValid = false;
    } else {
        clearFieldError(etaInput);
    }

    if (!etdValue) {
        setFieldError(etdInput, "ETD cannot be blank.");
        isValid = false;
    } else {
        clearFieldError(etdInput);
    }

    if (etaValue && etdValue) {
        var etaDate = parseDateValue(etaValue);
        var etdDate = parseDateValue(etdValue);

        if (etaDate && etdDate && etdDate <= etaDate) {
            setFieldError(etdInput, "ETD must be after ETA.");
            isValid = false;
        }
    }

    if (!detailsValue) {
        setFieldError(detailsInput, "Request details cannot be blank.");
        isValid = false;
    } else {
        clearFieldError(detailsInput);
    }

    /*
     * VALIDATION AOG CÔTÉ NAVIGATEUR : elle empêche l'activation du bouton quand
     * le délai AOG est absent ou hors limites. Le modèle Yii répète volontairement
     * cette règle côté serveur pour garantir l'intégrité métier.
     */
    var selectedPriority = document.querySelector('[name="Requests[operational_priority]"]:checked');
    var priorityValue = selectedPriority ? selectedPriority.value : "routine";
    var responseInput = document.getElementById("response-required-minutes");
    var responseValue = responseInput ? responseInput.value.trim() : "";
    var responseMinutes = Number(responseValue);
    var responseHasValue = responseValue !== "";
    var responseIsInRange = responseHasValue
        && Number.isFinite(responseMinutes)
        && responseMinutes >= 15
        && responseMinutes <= 1440;
    var responseIsValid = (priorityValue !== "aog" || responseHasValue)
        && (!responseHasValue || responseIsInRange);

    if (!responseIsValid) {
        setFieldError(
            responseInput,
            priorityValue === "aog"
                ? "AOG response time must be between 15 minutes and 24 hours."
                : "Response time must be between 15 minutes and 24 hours."
        );
        isValid = false;
    } else if (responseInput) {
        clearFieldError(responseInput);
    }

    if (submitButton) {
        submitButton.disabled = !isValid;
    }

    return isValid;
}

["aircraft-dropdown", "destination-dropdown", "eta", "etd"].forEach(function (id) {
    var element = document.getElementById(id);

    if (element) {
        element.addEventListener("change", validateUpdateForm);
        element.addEventListener("keyup", validateUpdateForm);
        element.addEventListener("blur", validateUpdateForm);
        element.addEventListener("input", validateUpdateForm);
    }
});

var detailsInput = document.querySelector("[name='Requests[request_details]']");

if (detailsInput) {
    detailsInput.addEventListener("input", validateUpdateForm);
    detailsInput.addEventListener("keyup", validateUpdateForm);
    detailsInput.addEventListener("blur", validateUpdateForm);
}

/*
 * SYNCHRONISATION DE LA PRIORITÉ : toute modification des cartes ou du délai
 * relance la validation globale, sans dupliquer le comportement visuel partagé.
 */
var operationalPrioritySection = document.querySelector("[data-operational-priority]");
if (operationalPrioritySection) {
    operationalPrioritySection.addEventListener("can:priority-validity-change", function () {
        validateUpdateForm();
    });
}

if (typeof jQuery !== "undefined") {
    /* AIRPORT COUNTRY CONTEXT: update the Schedule note from the selected AJAX result. */
    jQuery("#destination-dropdown").on("select2:select", function (event) {
        var selectedAirport = event.params && event.params.data ? event.params.data : {};
        var scheduleCountry = document.getElementById("schedule-country");

        if (scheduleCountry) {
            scheduleCountry.textContent = selectedAirport.country
                ? " Selected country: " + selectedAirport.country + "."
                : "";
        }

        validateUpdateForm();
    });

    jQuery("#destination-dropdown").on("change select2:clear", function () {
        validateUpdateForm();
    });

    jQuery("#eta, #etd").on("changeDate change clearDate", function () {
        validateUpdateForm();
    });

    jQuery("#update-request-form").on("afterValidate", function () {
        validateUpdateForm();
    });

    jQuery("#update-request-form").on("beforeSubmit", function (event) {
        var currentForm = jQuery(this);

        if (currentForm.data("update-confirmed") === true) {
            if (submitButton) {
                submitButton.disabled = true;
            }

            if (loadingIndicator) {
                loadingIndicator.style.display = "flex";
            }

            return true;
        }

        event.preventDefault();

        if (!validateUpdateForm()) {
            if (submitButton) {
                submitButton.disabled = true;
            }

            if (loadingIndicator) {
                loadingIndicator.style.display = "none";
            }

            return false;
        }

        Swal.fire({
            title: "Confirm request update?",
            html: "Please confirm that all request information is correct before saving changes.",
            icon: "warning",
            showCancelButton: true,
            // FORM DIALOG ACTIONS: explicit save/review choices replace Yes/No wording.
            confirmButtonText: '<i class="bi bi-save"></i> Update request',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                // SHARED FORM CONFIRMATION: keeps the existing update action and local semantic classes.
                popup: "swal-update-popup can-form-swal",
                title: "swal-update-title",
                htmlContainer: "swal-update-html",
                confirmButton: "swal-update-confirm",
                cancelButton: "swal-update-cancel"
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                currentForm.data("update-confirmed", true);
                currentForm.trigger("submit");
            }
        });

        return false;
    });
}

if (form) {
    form.addEventListener("submit", function (event) {
        if (typeof jQuery !== "undefined" && jQuery(form).data("update-confirmed") === true) {
            return true;
        }

        if (!validateUpdateForm()) {
            event.preventDefault();

            if (submitButton) {
                submitButton.disabled = true;
            }

            if (loadingIndicator) {
                loadingIndicator.style.display = "none";
            }

            return false;
        }
    });
}

/* UPDATE ADVERTISING CAROUSEL: isolated controls preserve every form event and validation rule. */
(function () {
    var carousel = document.getElementById("update-ad-carousel");
    var slider = document.getElementById("update-ad-slider");

    if (!carousel || !slider) {
        return;
    }

    var slides = Array.prototype.slice.call(slider.querySelectorAll(".update-ad-slide"));
    var dots = Array.prototype.slice.call(carousel.querySelectorAll(".update-ad-dot"));
    var previousButton = document.getElementById("update-ad-prev");
    var nextButton = document.getElementById("update-ad-next");
    var currentIndex = 0;
    var timer = null;

    function updateAdvert(index) {
        if (!slides.length) {
            return;
        }

        currentIndex = (index + slides.length) % slides.length;
        slider.style.transform = "translate3d(-" + (currentIndex * 100) + "%, 0, 0)";

        slides.forEach(function (slide, slideIndex) {
            var active = slideIndex === currentIndex;
            var video = slide.querySelector("video");
            slide.classList.toggle("is-active", active);

            if (video) {
                if (active) {
                    var playPromise = video.play();
                    if (playPromise && typeof playPromise.catch === "function") {
                        playPromise.catch(function () {});
                    }
                } else {
                    video.pause();
                }
            }
        });

        dots.forEach(function (dot, dotIndex) {
            dot.classList.toggle("is-active", dotIndex === currentIndex);
        });
    }

    function stopAdvertRotation() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    function startAdvertRotation() {
        stopAdvertRotation();
        if (slides.length > 1 && !document.hidden) {
            timer = window.setInterval(function () {
                updateAdvert(currentIndex + 1);
            }, 7000);
        }
    }

    if (previousButton) {
        previousButton.addEventListener("click", function () {
            updateAdvert(currentIndex - 1);
            startAdvertRotation();
        });
    }

    if (nextButton) {
        nextButton.addEventListener("click", function () {
            updateAdvert(currentIndex + 1);
            startAdvertRotation();
        });
    }

    dots.forEach(function (dot) {
        dot.addEventListener("click", function () {
            updateAdvert(parseInt(dot.getAttribute("data-ad-target"), 10) || 0);
            startAdvertRotation();
        });
    });

    carousel.addEventListener("mouseenter", stopAdvertRotation);
    carousel.addEventListener("mouseleave", startAdvertRotation);
    document.addEventListener("visibilitychange", function () {
        document.hidden ? stopAdvertRotation() : startAdvertRotation();
    });

    updateAdvert(0);
    startAdvertRotation();
}());

validateUpdateForm();
JS);
?>

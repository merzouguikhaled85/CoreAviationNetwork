<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Currency;

$this->title = 'Update MRO Quotation';

/*
 * UI CONTEXT ONLY:
 * Prepare the current request data displayed to the MRO before quote revision.
 * No request or application business value is changed in this view.
 */
$aircraft = $request->getAircraft()->one();
$destinationAirport = $request->getDestinationAirport()->one();
$requestAo = $request->getAO()->one();
$etaText = $request->eta ? date('d M Y H:i', strtotime($request->eta)) : 'N/A';
$etdText = $request->etd ? date('d M Y H:i', strtotime($request->etd)) : 'N/A';
$aircraftText = $aircraft
    ? trim(($aircraft->manufacturer ?: '') . ' ' . ($aircraft->model ?: ''))
    : 'Aircraft deleted';
$airportText = $destinationAirport
    ? trim(($destinationAirport->icao ?: '') . ' - ' . ($destinationAirport->airport_name ?: ''), ' -')
    : 'N/A';
$aoText = $requestAo
    ? ($requestAo->company_name ?: $requestAo->username ?: 'N/A')
    : 'N/A';

/*
 * EXISTING QUOTATION:
 * Build a read-only URL for the previously uploaded quotation. The controller
 * already preserves this document when the MRO does not select a replacement.
 */
$existingQuote = trim((string) ($model->attachment ?? ''));
$existingQuoteUrl = null;

if ($existingQuote !== '') {
    if (preg_match('/^https?:\/\//i', $existingQuote)) {
        $existingQuoteUrl = $existingQuote;
    } elseif (strpos($existingQuote, '/') !== false) {
        $existingQuoteUrl = Yii::getAlias('@web/') . ltrim($existingQuote, '/');
    } else {
        $existingQuoteUrl = Yii::getAlias('@web/uploads/') . $existingQuote;
    }
}

// Bootstrap Icons
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]
);

// SweetAlert2 confirmation
$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    ['position' => \yii\web\View::POS_END]
);

$this->registerCss(<<<CSS
:root {
    --can-primary: #0D3261;
    --can-primary-2: #123F78;
    --can-blue: #2563EB;
    --can-sky: #00B2FF;
    --can-gold: #EED811;
    --can-bg: #F3F7FC;
    --can-card: #FFFFFF;
    --can-border: #D8E4EE;
    --can-border-2: #C7D7E8;
    --can-text: #0F172A;
    --can-muted: #64748B;
    --can-soft: #F8FBFF;
    --can-danger: #DC2626;
    --can-success: #22C55E;
    --can-warning: #F59E0B;

    /* Stepper variables */
    --step-size: 40px;
    --step-icon-size: 16px;
    --step-card-min: 112px;
    --step-line-gap: 11px;
}

/* ==========================================================
   PAGE CONTAINER - PROFESSIONAL AIRCRAFT MAINTENANCE UI
   ========================================================== */
.update-request-wrapper {
    min-height: auto;
    padding: 18px 24px 28px;
    background:
        radial-gradient(circle at 12% 10%, rgba(0,178,255,.16), transparent 34%),
        radial-gradient(circle at 88% 4%, rgba(238,216,17,.16), transparent 30%),
        linear-gradient(145deg, #EFF5FF 0%, #FFFFFF 48%, #F8FBFF 100%);
}

.update-request-card {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    background: rgba(255,255,255,.97);
    border: 1px solid var(--can-border);
    border-radius: 24px;
    box-shadow: 0 20px 50px rgba(15,23,42,.12);
    overflow: hidden;
    position: relative;
    backdrop-filter: blur(18px);
}

.update-request-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 5px;
    background: linear-gradient(135deg, var(--can-primary), var(--can-sky), var(--can-gold));
}

/* ==========================================================
   HEADER
   ========================================================== */
.update-request-header {
    padding: 26px 30px 20px;
    background:
        linear-gradient(135deg, rgba(255,255,255,.98), rgba(246,250,255,.98)),
        repeating-linear-gradient(135deg, rgba(13,50,97,.035) 0 1px, transparent 1px 10px);
    border-bottom: 1px solid rgba(226,232,240,.9);
}

.update-request-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
}

.update-request-title-left {
    display: flex;
    align-items: center;
    gap: 16px;
    min-width: 260px;
}

.update-request-icon {
    width: 58px;
    height: 58px;
    border-radius: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #FFFFFF, #EAF6FF);
    color: var(--can-primary);
    border: 1px solid #D6E7F8;
    box-shadow: 0 14px 28px rgba(13,50,97,.13);
}

.update-request-icon i {
    font-size: 27px;
}

.update-request-title {
    margin: 0 0 5px;
    color: var(--can-text);
    font-size: 26px;
    font-weight: 900;
    letter-spacing: -.035em;
}

.update-request-subtitle {
    color: #94A3B8;
    font-size: 14px;
    line-height: 1.6;
}

.header-badges {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

/* Header actions keep navigation visible without duplicating it below the form. */
.header-tools {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}

.header-back-btn {
    min-height: 38px;
    padding: 8px 14px;
    border: 1px solid #CBD5E1;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    color: #334155;
    background: #FFFFFF;
    font-size: 12px;
    font-weight: 900;
    text-decoration: none;
    transition: .2s ease;
}

.header-back-btn:hover {
    color: #FFFFFF;
    background: var(--can-primary);
    border-color: var(--can-primary);
    transform: translateY(-1px);
}

.header-badge {
    min-height: 34px;
    padding: 7px 12px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1px solid #D8E4EE;
    background: #FFFFFF;
    color: var(--can-primary);
    font-size: 12px;
    font-weight: 900;
    box-shadow: 0 8px 18px rgba(15,23,42,.05);
    white-space: nowrap;
}

.header-badge i {
    color: var(--can-blue);
}

/* ==========================================================
   FLASH MESSAGES
   ========================================================== */
.update-request-flash {
    padding: 18px 30px 0;
}

.update-request-flash .alert {
    border: none;
    border-radius: 16px;
    padding: 13px 16px;
    font-size: 14px;
    font-weight: 700;
    box-shadow: 0 10px 24px rgba(15,23,42,.08);
}

.update-request-body {
    padding: 24px 30px 30px;
}

/* ==========================================================
   OPERATIONAL STRIP
   ========================================================== */
.ops-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.ops-item {
    min-height: 74px;
    border: 1px solid var(--can-border);
    border-radius: 18px;
    background: linear-gradient(135deg, #FFFFFF, #F8FBFF);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 12px 26px rgba(15,23,42,.045);
}

.ops-icon {
    width: 38px;
    height: 38px;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #EAF6FF;
    color: var(--can-primary);
    border: 1px solid #D6E7F8;
    flex: 0 0 auto;
}

.ops-label {
    color: #94A3B8;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .06em;
}

.ops-value {
    color: var(--can-text);
    font-size: 13px;
    font-weight: 900;
    margin-top: 2px;
}

/* ==========================================================
   STEPPER - SAME PROFESSIONAL DESIGN, BUSINESS FLOW PRESERVED
   ========================================================== */
.can-progress-wrapper {
    margin-bottom: 22px;
    padding: 20px 20px 26px;
    background: #FFFFFF;
    border: 1px solid var(--can-border);
    border-radius: 20px;
    box-shadow: 0 12px 30px rgba(15,23,42,.045);
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}

.progress-title {
    margin: 0;
    font-size: 14px;
    font-weight: 900;
    color: var(--can-text);
}

.progress-note {
    color: #94A3B8;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
}

.can-progress-scroll {
    overflow-x: auto;
    overflow-y: visible;
    padding: 4px 4px 2px;
    scrollbar-width: thin;
}

.can-progress-bar {
    display: grid;
    grid-template-columns: repeat(10, minmax(var(--step-card-min), 1fr));
    align-items: start;
    min-width: 1120px;
}

.can-progress-step {
    position: relative;
    min-width: var(--step-card-min);
    text-align: center;
    z-index: 1;
}

/* Line between circles only: it starts after previous circle and stops before current circle */
.can-progress-step::before {
    content: "";
    position: absolute;
    top: calc(var(--step-size) / 2);
    left: calc(-50% + (var(--step-size) / 2) + var(--step-line-gap));
    width: calc(100% - var(--step-size) - (var(--step-line-gap) * 2));
    height: 4px;
    background: #E5EAF0;
    border-radius: 999px;
    z-index: 1;
}

.can-progress-step:first-child::before {
    display: none;
}

.can-progress-step.step-realized::before,
.can-progress-step.step-active::before {
    background: var(--can-success);
}

.step-circle {
    position: relative;
    z-index: 2;
    width: var(--step-size);
    height: var(--step-size);
    margin: 0 auto 11px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #F8FAFC;
    border: 2px solid #CBD5E1;
    color: #94A3B8;
    font-size: var(--step-icon-size);
    box-shadow: 0 4px 12px rgba(15,23,42,.08);
    transition: .22s ease;
}

.step-label {
    display: block;
    max-width: 105px;
    margin: 0 auto;
    color: #8EA0B8;
    font-size: 12px;
    font-weight: 900;
    line-height: 1.25;
    text-align: center;
    white-space: normal;
}

/* ==========================================================
   UPDATED REQUEST SUMMARY - READ-ONLY OPERATIONAL CONTEXT
   ========================================================== */
.request-summary-panel {
    margin-bottom: 22px;
    padding: 22px;
    border: 1px solid var(--can-border);
    border-radius: 20px;
    background: linear-gradient(135deg, #FFFFFF, #F8FBFF);
    box-shadow: 0 12px 30px rgba(15,23,42,.05);
}

.request-summary-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 16px;
}

.request-summary-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.request-summary-title > i {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #7C3AED;
    background: #F3E8FF;
    border: 1px solid #E9D5FF;
    font-size: 18px;
}

.request-summary-title h2 {
    margin: 0;
    color: var(--can-text);
    font-size: 18px;
    font-weight: 900;
}

.request-summary-title p {
    margin: 3px 0 0;
    color: var(--can-muted);
    font-size: 13px;
}

.request-id-pill {
    padding: 7px 12px;
    border-radius: 999px;
    color: #1D4ED8;
    background: #DBEAFE;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
}

.request-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.request-summary-item {
    min-height: 78px;
    padding: 13px 14px;
    border: 1px solid #DDE7F1;
    border-radius: 14px;
    background: #FFFFFF;
    min-width: 0;
}

.request-summary-item.request-dates {
    grid-column: span 2;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.summary-label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    color: #7B8BA1;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.summary-value {
    color: var(--can-text);
    font-size: 13px;
    font-weight: 800;
    line-height: 1.4;
    overflow-wrap: anywhere;
}

.request-information {
    margin-top: 12px;
}

.request-information textarea {
    width: 100%;
    min-height: 92px;
    padding: 12px 14px;
    border: 1px dashed #BFD0E4;
    border-radius: 13px;
    color: #334155;
    background: #FFFFFF;
    font-size: 13px;
    line-height: 1.5;
    resize: vertical;
}

.can-progress-step.step-realized .step-circle {
    background: var(--can-success);
    border-color: var(--can-success);
    color: #FFFFFF;
}

.can-progress-step.step-realized .step-label {
    color: var(--can-success);
}

.can-progress-step.step-active .step-circle {
    background: var(--can-success);
    border-color: var(--can-success);
    color: #FFFFFF;
    box-shadow:
        0 0 0 8px rgba(34,197,94,.14),
        0 0 0 18px rgba(34,197,94,.06),
        0 10px 22px rgba(34,197,94,.28);
}

.can-progress-step.step-active .step-label {
    color: var(--can-success);
    font-weight: 950;
}

.can-progress-step.step-pending .step-circle {
    background: #F8FAFC;
    border-color: #CBD5E1;
    color: #94A3B8;
}

/* ==========================================================
   FORM PANEL
   ========================================================== */
.update-request-form-panel {
    background: #FFFFFF;
    border: 1px solid var(--can-border);
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 16px 34px rgba(15,23,42,.075);
}

.form-section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 22px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(226,232,240,.9);
    flex-wrap: wrap;
}

.form-section-heading {
    display: flex;
    align-items: center;
    gap: 12px;
}

.form-section-title i.section-main-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: #EAF6FF;
    color: var(--can-primary);
    border: 1px solid #D6E7F8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
}

.form-section-title h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 900;
    color: var(--can-text);
}

.form-section-title p {
    margin: 3px 0 0;
    font-size: 13px;
    color: #94A3B8;
}

.form-status-pill {
    min-height: 34px;
    padding: 7px 12px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #ECFDF5;
    color: #15803D;
    border: 1px solid #BBF7D0;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
}

.update-request-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.update-request-grid .full-width {
    grid-column: 1 / -1;
}

.update-request-form-panel .form-group {
    margin-bottom: 0;
}

.update-request-form-panel label {
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 900;
    color: var(--can-text);
}

.update-request-form-panel .text-danger {
    margin-left: 4px;
}

.update-request-form-panel .form-control,
.update-request-form-panel .form-select {
    min-height: 48px;
    border-radius: 14px;
    border: 1px solid #CBD5E1;
    background-color: #F8FAFC;
    color: var(--can-text);
    font-size: 14px;
    font-weight: 600;
    padding: 11px 14px;
    transition: .22s ease;
}

.update-request-form-panel textarea.form-control {
    min-height: 160px;
    resize: vertical;
}

.update-request-form-panel .form-control:focus,
.update-request-form-panel .form-select:focus {
    background-color: #FFFFFF;
    border-color: var(--can-blue);
    box-shadow: 0 0 0 4px rgba(37,99,235,.14);
}

.update-request-form-panel .invalid-feedback,
.update-request-form-panel .help-block {
    margin-top: 7px;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--can-danger);
}

.file-helper {
    margin-top: 8px;
    padding: 13px 14px;
    border-radius: 14px;
    background: linear-gradient(135deg, #F8FAFC, #FFFFFF);
    border: 1px dashed #BFD0E4;
    color: #94A3B8;
    font-size: 12.5px;
    font-weight: 700;
    line-height: 1.5;
}

/* Modern replacement upload; the real file input remains available to Yii. */
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
    min-height: 132px;
    padding: 20px;
    border: 2px dashed #93C5FD;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 7px;
    color: #475569;
    background: #F8FBFF;
    text-align: center;
    cursor: pointer;
    transition: .2s ease;
}

.quote-upload-zone:hover,
.quote-upload-zone:focus,
.quote-upload-zone:focus-within {
    border-color: var(--can-blue);
    background: #EFF6FF;
    outline: none;
}

.quote-upload-zone:focus-visible {
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .18);
}

.quote-upload-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #1D4ED8;
    background: #DBEAFE;
    font-size: 20px;
}

.quote-upload-title {
    color: var(--can-text);
    font-size: 14px;
    font-weight: 900;
}

.quote-upload-file-name {
    max-width: 100%;
    color: var(--can-muted);
    font-size: 12px;
    overflow-wrap: anywhere;
}

.existing-quote-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 12px;
    padding: 13px 14px;
    border: 1px solid #BBF7D0;
    border-radius: 14px;
    background: #F0FDF4;
}

.existing-quote-info {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.existing-quote-info > i {
    color: #16A34A;
    font-size: 22px;
}

.existing-quote-name {
    color: #166534;
    font-size: 12px;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.existing-quote-note {
    color: #64748B;
    font-size: 11px;
}

.existing-quote-link {
    flex: 0 0 auto;
    padding: 8px 12px;
    border-radius: 9px;
    color: #FFFFFF !important;
    background: #16A34A;
    font-size: 12px;
    font-weight: 900;
    text-decoration: none;
}

.existing-quote-link:hover {
    background: #15803D;
}

.field-hint {
    margin-top: 7px;
    color: #94A3B8;
    font-size: 12px;
    font-weight: 600;
}

.update-request-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
    padding-top: 22px;
    border-top: 1px solid rgba(226,232,240,.9);
}

.can-btn {
    height: 48px;
    padding: 0 22px;
    border-radius: 14px;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    font-size: 14px;
    font-weight: 900;
    text-decoration: none !important;
    transition: .22s ease;
}

.can-btn-primary {
    color: #FFFFFF !important;
    background: linear-gradient(135deg, var(--can-blue), var(--can-sky));
    box-shadow: 0 12px 26px rgba(37,99,235,.28);
}

.can-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 18px 34px rgba(37,99,235,.34);
}

.can-btn-secondary {
    color: #334155 !important;
    background: #F8FAFC;
    border: 1px solid #CBD5E1;
}

.can-btn-secondary:hover {
    background: #EEF2F7;
    transform: translateY(-2px);
}

.swal2-popup {
    border-radius: 22px !important;
    padding: 28px !important;
}

.swal2-title {
    font-size: 22px !important;
    font-weight: 900 !important;
    color: var(--can-text) !important;
}

.swal2-html-container {
    font-size: 14px !important;
    color: var(--can-muted) !important;
}

.swal2-confirm,
.swal2-cancel {
    border-radius: 12px !important;
    padding: 10px 22px !important;
    font-weight: 900 !important;
}

/* ==========================================================
   RESPONSIVE
   ========================================================== */
@media (max-width: 1200px) {
    .can-progress-bar {
        grid-template-columns: repeat(10, 112px);
        min-width: 1120px;
    }

    .request-summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 992px) {
    .update-request-wrapper {
        padding: 12px;
    }

    .update-request-header,
    .update-request-body {
        padding-left: 18px;
        padding-right: 18px;
    }

    .update-request-title {
        font-size: 23px;
    }

    .ops-strip {
        grid-template-columns: 1fr;
    }

    .update-request-grid {
        grid-template-columns: 1fr;
    }

    .request-summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .can-progress-wrapper {
        padding: 18px 16px 24px;
    }
}

@media (max-width: 576px) {
    :root {
        --step-size: 36px;
        --step-icon-size: 14px;
        --step-card-min: 100px;
        --step-line-gap: 9px;
    }

    .update-request-card {
        border-radius: 22px;
    }

    .update-request-title-left {
        align-items: flex-start;
    }

    .update-request-icon {
        width: 52px;
        height: 52px;
    }

    .update-request-title {
        font-size: 21px;
    }

    .update-request-subtitle {
        font-size: 13px;
    }

    .header-badges {
        justify-content: flex-start;
    }

    .header-tools {
        width: 100%;
        justify-content: flex-start;
    }

    .header-back-btn {
        order: -1;
        width: 100%;
    }

    .request-summary-grid,
    .request-summary-item.request-dates {
        grid-template-columns: 1fr;
    }

    .request-summary-item.request-dates {
        grid-column: auto;
    }

    .request-summary-header,
    .existing-quote-card {
        align-items: stretch;
        flex-direction: column;
    }

    .existing-quote-link {
        text-align: center;
    }

    .can-progress-bar {
        grid-template-columns: repeat(10, 100px);
        min-width: 1000px;
    }

    .step-label {
        max-width: 92px;
        font-size: 10.5px;
    }

    .update-request-form-panel {
        padding: 18px;
    }

    .update-request-actions {
        flex-direction: column;
    }

    .can-btn {
        width: 100%;
    }
}
CSS);

$this->registerJs(<<<JS
(function () {
    /*
     * FORM UPLOAD INITIALISATION:
     * Yii registers this block at POS_READY, so the DOM is already available.
     * Running it directly keeps the replacement picker active on every visit.
     */
    // UI ONLY: show the selected replacement document name in the custom upload zone.
    const quoteInput = document.querySelector('.quote-file-input');
    const quoteFileName = document.querySelector('.quote-upload-file-name');
    const quoteUploadZone = document.querySelector('.quote-upload-zone');

    // REPLACEMENT PICKER: explicitly open the native selector by mouse or keyboard.
    if (quoteInput && quoteUploadZone) {
        const openReplacementPicker = function (event) {
            event.preventDefault();
            quoteInput.click();
        };

        quoteUploadZone.addEventListener('click', openReplacementPicker);
        quoteUploadZone.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                openReplacementPicker(event);
            }
        });
    }

    if (quoteInput && quoteFileName) {
        quoteInput.addEventListener('change', function () {
            const hasReplacementFile = !!(this.files && this.files.length);
            quoteFileName.textContent = hasReplacementFile
                ? this.files[0].name
                : 'No replacement document selected';

            // VISUAL FILE STATE: does not alter Yii validation or submission rules.
            if (quoteUploadZone) {
                quoteUploadZone.classList.toggle('is-valid', hasReplacementFile);
            }
        });
    }

    // REPLACEMENT DRAG AND DROP: use the same interaction as the creation form.
    if (quoteInput && quoteUploadZone) {
        ['dragenter', 'dragover'].forEach(function (eventName) {
            quoteUploadZone.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                quoteUploadZone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            quoteUploadZone.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                quoteUploadZone.classList.remove('is-dragover');
            });
        });

        quoteUploadZone.addEventListener('drop', function (event) {
            const files = event.dataTransfer && event.dataTransfer.files;

            if (!files || !files.length || typeof DataTransfer === 'undefined') {
                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(files[0]);
            quoteInput.files = transfer.files;
            quoteInput.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    // SweetAlert confirmation before form submit
    const form = document.querySelector('.js-update-request-form');

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            Swal.fire({
                title: 'Submit updated quote?',
                text: 'Please confirm that you want to submit this updated MRO quotation.',
                icon: 'question',
                // SHARED FORM CONFIRMATION: presentation only; submission remains handled below.
                customClass: {
                    popup: 'can-form-swal'
                },
                showCancelButton: true,
                // ACTION LABELS: replace the vague negative choice with a useful review action.
                confirmButtonText: '<i class="bi bi-send-check"></i> Submit update',
                cancelButtonText: '<i class="bi bi-arrow-left"></i> Review',
                reverseButtons: true,
                focusCancel: true,
                buttonsStyling: false
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    }
})();
JS);

/*
|--------------------------------------------------------------------------
| Business progress configuration
|--------------------------------------------------------------------------
| Business flow preserved from your current code:
| 1. Create a Request
| 2. MRO Quote
| 3. PO Loaded
| 4. PO Accepted
| 5. Work Started
| 6. Update Request
| 7. MRO Quote       <-- current active step on this page
| 8. MRO Report
| 9. AO Feedback
| 10. Request Closed
*/
$currentStep = 7;

$steps = [
    1 => [
        'label' => 'Create Request',
        'icon' => 'bi-person-fill',
    ],
    2 => [
        'label' => 'MRO Quote',
        'icon' => 'bi-file-earmark-text-fill',
    ],
    3 => [
        'label' => 'PO Loaded',
        'icon' => 'bi-upload',
    ],
    4 => [
        'label' => 'PO Accepted',
        'icon' => 'bi-check-circle-fill',
    ],
    5 => [
        'label' => 'Work Started',
        'icon' => 'bi-play-circle-fill',
    ],
    6 => [
        'label' => 'Update Request',
        'icon' => 'bi-pencil-square',
    ],
    7 => [
        'label' => 'MRO Quote',
        'icon' => 'bi-file-earmark-check-fill',
    ],
    8 => [
        'label' => 'MRO Report',
        'icon' => 'bi-clipboard-check-fill',
    ],
    9 => [
        'label' => 'AO Feedback',
        'icon' => 'bi-chat-dots-fill',
    ],
    10 => [
        'label' => 'Request Closed',
        'icon' => 'bi-check-lg',
    ],
];

$totalSteps = count($steps);
?>

<!-- SHARED FORM SYSTEM: presentation only; revised quotation rules remain unchanged. -->
<div class="update-request-wrapper can-form-page">
    <div class="update-request-card">

        <!-- Header section -->
        <div class="update-request-header">
            <div class="update-request-title-row">

                <div class="update-request-title-left">
                    <div class="update-request-icon">
                        <i class="bi bi-pencil-square"></i>
                    </div>

                    <div>
                        <h1 class="update-request-title">
                            <?= Html::encode($this->title) ?>
                        </h1>

                        <div class="update-request-subtitle">
                            Update the MRO quotation after the aircraft operator has modified the maintenance request details.
                        </div>
                    </div>
                </div>

                <!-- Header navigation and context badges: Back is kept visible above the form. -->
                <div class="header-tools">
                    <a href="javascript:history.back()" class="header-back-btn">
                        <i class="bi bi-arrow-left"></i>
                        Back
                    </a>

                    <div class="header-badges" aria-label="Aircraft maintenance context">
                        <span class="header-badge">
                            <i class="bi bi-airplane-engines"></i>
                            Aircraft Maintenance
                        </span>
                        <span class="header-badge">
                            <i class="bi bi-shield-check"></i>
                            Controlled Quote
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Flash messages -->
        <?php if (Yii::$app->session->hasFlash('success') || Yii::$app->session->hasFlash('error')): ?>
            <div class="update-request-flash">

                <?php if (Yii::$app->session->hasFlash('success')): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-1"></i>
                        <?= Yii::$app->session->getFlash('success') ?>
                    </div>
                <?php endif; ?>

                <?php if (Yii::$app->session->hasFlash('error')): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <?= Yii::$app->session->getFlash('error') ?>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <div class="update-request-body">

            <!-- Operational summary strip: UI only, no business logic changed -->
            <div class="ops-strip">
                <div class="ops-item">
                    <span class="ops-icon"><i class="bi bi-tools"></i></span>
                    <div>
                        <div class="ops-label">Current action</div>
                        <div class="ops-value">Updated MRO quote submission</div>
                    </div>
                </div>

                <div class="ops-item">
                    <span class="ops-icon"><i class="bi bi-file-earmark-pdf"></i></span>
                    <div>
                        <div class="ops-label">Required document</div>
                        <div class="ops-value">Official quotation attachment</div>
                    </div>
                </div>

                <div class="ops-item">
                    <span class="ops-icon"><i class="bi bi-currency-exchange"></i></span>
                    <div>
                        <div class="ops-label">Commercial data</div>
                        <div class="ops-value">Currency and revised price</div>
                    </div>
                </div>
            </div>

            <!-- Multi-step progress bar -->
            <div class="can-progress-wrapper">
                <div class="progress-header">
                    <h2 class="progress-title">Request progress</h2>
                    <div class="progress-note">
                        Step <?= Html::encode($currentStep) ?> of <?= Html::encode($totalSteps) ?>
                    </div>
                </div>

                <div class="can-progress-scroll">
                    <div class="can-progress-bar" aria-label="Request progress">
                        <?php foreach ($steps as $number => $step): ?>
                            <?php
                            /*
                             * step-realized = already completed
                             * step-active   = current step
                             * step-pending  = next steps
                             */
                            if ($number < $currentStep) {
                                $stepClass = 'step-realized';
                            } elseif ($number === $currentStep) {
                                $stepClass = 'step-active';
                            } else {
                                $stepClass = 'step-pending';
                            }
                            ?>

                            <div class="can-progress-step <?= Html::encode($stepClass) ?>">
                                <span class="step-circle">
                                    <i class="bi <?= Html::encode($step['icon']) ?>"></i>
                                </span>

                                <span class="step-label">
                                    <?= Html::encode($step['label']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!--
                Updated Request summary (read-only).
                This operational context helps the MRO revise the quotation without
                changing any Request value or workflow status.
            -->
            <section class="request-summary-panel" aria-labelledby="updated-request-summary-title">
                <div class="request-summary-header">
                    <div class="request-summary-title">
                        <i class="bi bi-clipboard-data"></i>
                        <div>
                            <h2 id="updated-request-summary-title">Updated Request Summary</h2>
                            <p>Review the operator's current maintenance requirements before revising the quotation.</p>
                        </div>
                    </div>

                    <span class="request-id-pill">Request #<?= Html::encode($request->request_id) ?></span>
                </div>

                <div class="request-summary-grid">
                    <div class="request-summary-item">
                        <div class="summary-label"><i class="bi bi-building"></i> AO / CAMO</div>
                        <div class="summary-value"><?= Html::encode($aoText) ?></div>
                    </div>

                    <div class="request-summary-item">
                        <div class="summary-label"><i class="bi bi-airplane"></i> Aircraft</div>
                        <div class="summary-value"><?= Html::encode($aircraftText ?: 'N/A') ?></div>
                    </div>

                    <div class="request-summary-item">
                        <div class="summary-label"><i class="bi bi-card-text"></i> Registration</div>
                        <div class="summary-value"><?= Html::encode($request->aircraft_registration ?: 'N/A') ?></div>
                    </div>

                    <div class="request-summary-item">
                        <div class="summary-label"><i class="bi bi-upc-scan"></i> Serial Number</div>
                        <div class="summary-value"><?= Html::encode($request->serial_number ?: 'N/A') ?></div>
                    </div>

                    <!-- ETA and ETD intentionally remain together for operational comparison. -->
                    <div class="request-summary-item request-dates">
                        <div>
                            <div class="summary-label"><i class="bi bi-calendar-event"></i> ETA</div>
                            <div class="summary-value"><?= Html::encode($etaText) ?></div>
                        </div>
                        <div>
                            <div class="summary-label"><i class="bi bi-calendar-check"></i> ETD</div>
                            <div class="summary-value"><?= Html::encode($etdText) ?></div>
                        </div>
                    </div>

                    <div class="request-summary-item">
                        <div class="summary-label"><i class="bi bi-geo-alt"></i> Maintenance Location</div>
                        <div class="summary-value"><?= Html::encode($request->location ?: 'N/A') ?></div>
                    </div>

                    <div class="request-summary-item">
                        <div class="summary-label"><i class="bi bi-signpost-2"></i> Maintenance Airport</div>
                        <div class="summary-value"><?= Html::encode($airportText ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="request-information">
                    <div class="summary-label"><i class="bi bi-info-circle"></i> Request Information</div>
                    <?= Html::textarea('request_summary', $request->request_details ?: 'N/A', [
                        'readonly' => true,
                        'aria-label' => 'Updated request information',
                    ]) ?>
                </div>
            </section>

            <!-- Form panel -->
            <div class="update-request-form-panel">

                <div class="form-section-title">
                    <div class="form-section-heading">
                        <i class="bi bi-file-earmark-text section-main-icon"></i>

                        <div>
                            <h2>Updated MRO Quotation</h2>
                            <p>Enter the revised technical and commercial proposal, then attach the controlled quotation.</p>
                        </div>
                    </div>

                    <span class="form-status-pill">
                        <i class="bi bi-check2-circle"></i>
                        Awaiting updated quote
                    </span>
                </div>

                <?php $form = ActiveForm::begin([
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'class' => 'js-update-request-form'
                    ],
                ]); ?>

                <?php
                // Retrieve currencies from the Currency model
                $currencies = Currency::find()->all();

                // Convert currencies array to a dropdown list
                $currencyList = [];

                foreach ($currencies as $currency) {
                    $currencyList[$currency->code] = $currency->name . ' ' . $currency->symbol . ' (' . $currency->code . ')';
                }
                ?>

                <div class="update-request-grid">

                    <div class="full-width">
                        <?= $form->field($model, 'Description')->textarea([
                            'rows' => 6,
                            'placeholder' => 'Describe the updated quotation, maintenance scope, parts, manpower or any special condition...'
                        ])->label('Updated Technical &amp; Commercial Proposal <span class="text-danger">*</span>') ?>

                        <div class="field-hint">
                            Add clear information about scope changes, lead time, parts availability, manpower and operational constraints.
                        </div>
                    </div>

                    <div>
                        <?= $form->field($model, 'currency')->dropDownList(
                            $currencyList,
                            [
                                'prompt' => 'Select Currency',
                                'class' => 'form-select'
                            ]
                        )->label('Currency <span class="text-danger">*</span>') ?>
                    </div>

                    <div>
                        <?= $form->field($model, 'price')->textInput([
                            'type' => 'number',
                            'step' => '0.01',
                            'min' => '0',
                            'placeholder' => '0.00'
                        ])->label('Price <span class="text-danger">*</span>') ?>
                    </div>

                    <div class="full-width">
                        <label class="form-label">
                            Attach Updated Quotation
                        </label>

                        <!-- Existing document remains active until a replacement is submitted. -->
                        <?php if ($existingQuoteUrl !== null): ?>
                            <div class="existing-quote-card">
                                <div class="existing-quote-info">
                                    <i class="bi bi-file-earmark-check"></i>
                                    <div>
                                        <div class="existing-quote-name"><?= Html::encode(basename($existingQuote)) ?></div>
                                        <div class="existing-quote-note">Current quotation — it will be kept unless you select a replacement.</div>
                                    </div>
                                </div>

                                <?= Html::a(
                                    '<i class="bi bi-eye"></i> View / Download',
                                    $existingQuoteUrl,
                                    [
                                        'class' => 'existing-quote-link',
                                        'target' => '_blank',
                                        'rel' => 'noopener',
                                    ]
                                ) ?>
                            </div>
                        <?php endif; ?>

                        <?php $attachmentInputId = Html::getInputId($model, 'attachment'); ?>
                        <?= $form->field($model, 'attachment', [
                            'template' => '{input}{error}',
                            'options' => ['class' => 'quote-file-field'],
                        ])->fileInput([
                            'id' => $attachmentInputId,
                            'class' => 'quote-file-input',
                            'accept' => '.pdf,.png,.jpg',
                        ]) ?>

                        <!-- Replacement picker: explicit button semantics keep mouse and keyboard access reliable. -->
                        <div class="quote-upload-zone"
                             role="button"
                             tabindex="0"
                             aria-controls="<?= Html::encode($attachmentInputId) ?>">
                            <span class="quote-upload-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                            <span class="quote-upload-title">
                                <?= $existingQuoteUrl !== null ? 'Replace quotation document' : 'Select quotation document' ?>
                            </span>
                            <span class="quote-upload-file-name">No replacement document selected</span>
                        </div>

                        <div class="file-helper">
                            <i class="bi bi-paperclip me-1"></i>
                            Accepted formats: PDF, PNG or JPG. Maximum file size: 10 MiB.
                        </div>
                    </div>

                </div>

                <div class="update-request-actions">
                    <?= Html::submitButton(
                        '<i class="bi bi-send-check"></i> Submit Updated Quote',
                        [
                            'class' => 'can-btn can-btn-primary'
                        ]
                    ) ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    </div>
</div>

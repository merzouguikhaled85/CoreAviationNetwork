<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\web\View;

$this->title = 'Submit Certificate of Release to Service';

/*
 * Bootstrap Icons.
 * If Bootstrap Icons are already loaded globally in your layout, you can remove this line.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    ['position' => View::POS_HEAD]
);

/*
 * SweetAlert2.
 */
$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    ['position' => View::POS_END]
);

/*
 * SUBMIT REPORT REQUEST DETAILS 2026: prepare display-only operational data.
 * The controller already supplies the linked request; no business data is changed.
 */
$requestId = $request ? $request->request_id : 'N/A';
$requestAircraft = $request ? $request->aircraft : null;
$requestOperator = $request ? $request->aO : null;

$operatorName = $requestOperator
    ? ($requestOperator->company_name
        ?: trim(($requestOperator->first_name ?: '') . ' ' . ($requestOperator->last_name ?: ''))
        ?: $requestOperator->username)
    : 'N/A';

$aircraftName = $requestAircraft
    ? trim(($requestAircraft->manufacturer ?: '') . ' ' . ($requestAircraft->model ?: ''))
    : 'N/A';
$aircraftName = $aircraftName !== '' ? $aircraftName : 'N/A';

$registration = $request && $request->aircraft_registration
    ? $request->aircraft_registration
    : ($requestAircraft && $requestAircraft->registration_number ? $requestAircraft->registration_number : 'N/A');
$serialNumber = $request && $request->serial_number
    ? $request->serial_number
    : ($requestAircraft && $requestAircraft->serial_number ? $requestAircraft->serial_number : 'N/A');
$maintenanceLocation = $request && $request->location ? $request->location : 'N/A';
$requestStatus = $request && $request->status
    ? ucwords(str_replace('_', ' ', $request->status))
    : 'N/A';
$requestDetails = $request && $request->request_details ? $request->request_details : 'No request details available.';

$formatOperationalDate = static function ($value) {
    if (empty($value)) {
        return 'N/A';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y H:i', $timestamp) : $value;
};

$etaText = $formatOperationalDate($request ? $request->eta : null);
$etdText = $formatOperationalDate($request ? $request->etd : null);

/*
 * Professional SaaS design inspired by View PO.
 * Business logic is kept unchanged.
 */
$this->registerCss(<<<CSS
.crs-page {
    min-height: calc(100vh - 90px);
    padding: 24px 28px 38px;
    background:
        radial-gradient(circle at 10% 10%, rgba(37, 99, 235, 0.08), transparent 30%),
        radial-gradient(circle at 90% 18%, rgba(14, 165, 233, 0.07), transparent 28%),
        linear-gradient(135deg, #f8fafc 0%, #eef4ff 100%);
}

.crs-shell {
    max-width: 1150px;
    margin: 0 auto;
}

/* Header */
.crs-header-card {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 22px 55px rgba(15, 23, 42, 0.08);
    padding: 24px 26px;
    margin-bottom: 22px;
}

.crs-header-card::before {
    content: "";
    position: absolute;
    top: -90px;
    right: -85px;
    width: 245px;
    height: 245px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.16), transparent 70%);
    pointer-events: none;
}

.crs-header-top {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
}

.crs-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 13px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 850;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 13px;
}

.crs-title-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.crs-title-icon {
    width: 44px;
    height: 44px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    font-size: 21px;
    box-shadow: 0 14px 26px rgba(37, 99, 235, 0.24);
}

.crs-title {
    margin: 0;
    color: #020617;
    font-size: 28px;
    font-weight: 850;
    letter-spacing: -0.04em;
}

.crs-subtitle {
    margin: 11px 0 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
}

.crs-request-pill {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    white-space: nowrap;
    padding: 12px 16px;
    border-radius: 9px;
    background: #597bccff;
    color: #ffffff;
    font-size: 13px;
    font-weight: 800;
    box-shadow: 0 16px 30px rgba(15, 23, 42, 0.18);
}

.crs-request-pill i {
    color: #dbeafe;
    font-size: 15px;
}

/* Professional stepper - same previous aviation design, flow unchanged */
.crs-stepper-card {
    --step-circle: 42px;
    --step-icon: 16px;
    --step-gap: 14px;
    --step-width: 132px;
    position: relative;
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 18px 44px rgba(15, 23, 42, 0.06);
    padding: 20px 20px 22px;
    margin-bottom: 24px;
    overflow: hidden;
}

/* SUBMIT REPORT STEPPER 2026: same visual language as the MRO report screens. */
.crs-stepper-card::before {
    content: "";
    position: absolute;
    top: -80px;
    right: -80px;
    width: 190px;
    height: 190px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.10), transparent 70%);
    pointer-events: none;
}

.crs-stepper-head {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 18px;
}

.crs-stepper-title {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin: 0;
    color: #020617;
    font-size: 14px;
    font-weight: 900;
}

.crs-stepper-title i {
    color: #1d4ed8;
    font-size: 16px;
}

.crs-stepper-note {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 11px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 850;
    white-space: nowrap;
}

.crs-stepper-scroll {
    position: relative;
    z-index: 2;
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 8px 2px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.crs-stepper-scroll::-webkit-scrollbar {
    height: 7px;
}

.crs-stepper-scroll::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 999px;
}

.crs-stepper-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

.crs-stepper {
    display: grid;
    grid-template-columns: repeat(8, minmax(var(--step-width), 1fr));
    align-items: start;
    min-width: calc(var(--step-width) * 8);
}

.crs-step {
    position: relative;
    min-width: var(--step-width);
    text-align: center;
}

/*
 * Segment between two steps.
 * The line starts after the previous circle and stops before the current circle.
 * This prevents the line from crossing the icons.
 */
.crs-step:not(:first-child)::before {
    content: "";
    position: absolute;
    top: calc(var(--step-circle) / 2);
    left: calc(-50% + (var(--step-circle) / 2) + var(--step-gap));
    width: calc(100% - var(--step-circle) - (var(--step-gap) * 2));
    height: 4px;
    border-radius: 999px;
    background: #e2e8f0;
    transform: translateY(-50%);
    z-index: 1;
}

.crs-step.crs-step-done::before,
.crs-step.crs-step-active::before {
    background: linear-gradient(90deg, #22c55e, #2563eb);
}

.crs-step-circle {
    position: relative;
    z-index: 3;
    width: var(--step-circle);
    height: var(--step-circle);
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border: 2px solid #cbd5e1;
    color: #94a3b8;
    font-size: var(--step-icon);
    box-shadow: 0 7px 16px rgba(15, 23, 42, 0.08);
    margin: 0 auto 10px;
    transition: all 0.18s ease;
}

.crs-step-label {
    display: block;
    max-width: 108px;
    margin: 0 auto;
    color: #64748b;
    font-size: 11.5px;
    font-weight: 850;
    line-height: 1.25;
    text-align: center;
    white-space: normal;
}

.crs-step.crs-step-done .crs-step-circle {
    background: #22c55e;
    border-color: #22c55e;
    color: #ffffff;
    box-shadow: 0 10px 20px rgba(34, 197, 94, 0.20);
}

.crs-step.crs-step-done .crs-step-label {
    color: #16a34a;
}

.crs-step.crs-step-active .crs-step-circle {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    border-color: #2563eb;
    color: #ffffff;
    box-shadow:
        0 12px 24px rgba(37, 99, 235, 0.28),
        0 0 0 7px rgba(37, 99, 235, 0.10);
}

.crs-step.crs-step-active .crs-step-label {
    color: #1d4ed8;
    font-weight: 950;
}

.crs-step.crs-step-pending .crs-step-circle {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #94a3b8;
}

.crs-step.crs-step-pending .crs-step-label {
    color: #94a3b8;
}

/* SUBMIT REPORT REQUEST DETAILS 2026: concise operational context before upload. */
.crs-request-details-card {
    margin-bottom: 24px;
    padding: 18px 20px 20px;
    border: 1px solid rgba(226, 232, 240, .95);
    border-radius: 22px;
    background: rgba(255, 255, 255, .96);
    box-shadow: 0 18px 44px rgba(15, 23, 42, .06);
}

.crs-request-details-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 14px;
}

.crs-request-details-title {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin: 0;
    color: #0F172A;
    font-size: 15px;
    font-weight: 900;
}

.crs-request-details-title i {
    color: #2563EB;
}

.crs-request-status {
    padding: 6px 10px;
    border-radius: 999px;
    background: #DCFCE7;
    color: #166534;
    font-size: 11px;
    font-weight: 900;
}

.crs-request-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
}

.crs-request-item {
    min-width: 0;
    padding: 11px 12px;
    border: 1px solid #E2E8F0;
    border-radius: 13px;
    background: #F8FAFC;
}

.crs-request-label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
    color: #64748B;
    font-size: 9px;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.crs-request-value {
    overflow: hidden;
    color: #0F172A;
    font-size: 12px;
    font-weight: 850;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.crs-request-dates {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.crs-request-details-text {
    width: 100%;
    min-height: 82px;
    margin-top: 10px;
    padding: 11px 12px;
    resize: vertical;
    border: 1px dashed #CBD5E1;
    border-radius: 13px;
    background: #FFFFFF;
    color: #334155;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.55;
}

/* Form card */
.crs-form-card {
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 22px 55px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.crs-form-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 20px 22px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #ffffff, #f8fafc);
}

.crs-form-title-row {
    display: flex;
    align-items: center;
    gap: 11px;
}

.crs-form-title-icon {
    width: 38px;
    height: 38px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 18px;
    border: 1px solid #bfdbfe;
}

.crs-form-title {
    margin: 0;
    color: #020617;
    font-size: 18px;
    font-weight: 850;
    letter-spacing: -0.02em;
}

.crs-form-caption {
    margin: 5px 0 0;
    color: #64748b;
    font-size: 13px;
}

.crs-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 14px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #334155;
    font-size: 13px;
    font-weight: 800;
    white-space: nowrap;
}

.crs-status-badge i {
    color: #2563eb;
}

.crs-form-body {
    padding: 22px;
}

.crs-js-error {
    display: none;
    margin-bottom: 16px;
    padding: 12px 14px;
    border-radius: 14px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    font-size: 13px;
    font-weight: 750;
}

/* Fields */
.crs-field-card {
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: #ffffff;
    padding: 18px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
    height: 100%;
}

.crs-field-card-light {
    background: #f8fafc;
}

.crs-field-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 9px;
    color: #0f172a;
    font-size: 13px;
    font-weight: 850;
}

.crs-field-label i {
    color: #2563eb;
    font-size: 15px;
}

.crs-form-body label,
.crs-form-body .control-label {
    color: #0f172a;
    font-size: 13px;
    font-weight: 850;
    margin-bottom: 8px;
}

.crs-form-body .form-control {
    min-height: 46px;
    border-radius: 12px;
    border: 1px solid #cbd5e1;
    color: #0f172a;
    font-size: 14px;
    background: #ffffff;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
    transition: all 0.18s ease;
}

.crs-form-body .form-control:hover {
    border-color: #94a3b8;
}

.crs-form-body .form-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 0.18rem rgba(37, 99, 235, 0.14);
}

.crs-readonly {
    background: #f1f5f9 !important;
    color: #1d4ed8 !important;
    font-weight: 850;
}

.crs-form-body textarea.form-control {
    min-height: 230px;
    resize: vertical;
    line-height: 1.65;
}

/* Professional CRS document uploader */
.crs-upload-wrapper {
    position: relative;
}

.crs-upload-field {
    margin: 0;
}

.crs-upload-field .form-group,
.crs-upload-field .mb-3 {
    margin-bottom: 0;
}

.crs-file-native {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
    opacity: 0 !important;
}

.crs-upload-zone {
    position: relative;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 16px;
    width: 100%;
    min-height: 122px;
    margin: 0;
    padding: 18px;
    border: 1.5px dashed #93c5fd;
    border-radius: 20px;
    background:
        linear-gradient(135deg, rgba(239, 246, 255, 0.98), rgba(248, 250, 252, 0.98)),
        radial-gradient(circle at 95% 15%, rgba(37, 99, 235, 0.14), transparent 34%);
    cursor: pointer;
    overflow: hidden;
    transition: all 0.2s ease;
}

.crs-upload-zone::after {
    content: "";
    position: absolute;
    inset: 10px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.74);
    pointer-events: none;
}

.crs-upload-zone:hover,
.crs-upload-zone:focus,
.crs-upload-zone.crs-upload-dragover {
    border-color: #2563eb;
    background:
        linear-gradient(135deg, #eff6ff, #ffffff),
        radial-gradient(circle at 95% 15%, rgba(37, 99, 235, 0.2), transparent 34%);
    box-shadow: 0 18px 38px rgba(37, 99, 235, 0.13);
    transform: translateY(-1px);
    outline: none;
}

.crs-upload-zone.crs-upload-has-file {
    border-style: solid;
    border-color: #60a5fa;
    background: linear-gradient(135deg, #eff6ff, #f8fafc);
}

.crs-upload-icon {
    position: relative;
    z-index: 2;
    width: 58px;
    height: 58px;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    font-size: 27px;
    box-shadow: 0 16px 30px rgba(37, 99, 235, 0.28);
}

.crs-upload-content {
    position: relative;
    z-index: 2;
    min-width: 0;
}

.crs-upload-kicker {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 5px;
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.crs-upload-title {
    margin: 0;
    color: #0f172a;
    font-size: 15px;
    font-weight: 900;
    letter-spacing: -0.01em;
}

.crs-upload-text {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 12px;
    line-height: 1.45;
}

.crs-upload-action {
    position: relative;
    z-index: 2;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 10px 13px;
    border-radius: 12px;
    background: #ffffff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
}

.crs-upload-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.crs-upload-meta span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 11px;
    font-weight: 800;
}

.crs-upload-meta i {
    color: #2563eb;
}

.crs-selected-file {
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 13px;
    padding: 12px 13px;
    border-radius: 16px;
    border: 1px solid #bbf7d0;
    background: #f0fdf4;
}

.crs-selected-file-info {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.crs-selected-file-info > div {
    min-width: 0;
}

.crs-selected-file-icon {
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #dcfce7;
    color: #15803d;
    font-size: 18px;
}

.crs-selected-file-name {
    max-width: 100%;
    color: #14532d;
    font-size: 13px;
    font-weight: 900;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.crs-selected-file-meta {
    margin-top: 2px;
    color: #166534;
    font-size: 11px;
    font-weight: 750;
}

.crs-remove-file {
    border: 0;
    border-radius: 11px;
    padding: 8px 10px;
    background: #ffffff;
    color: #b91c1c;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
    box-shadow: inset 0 0 0 1px #fecaca;
    transition: all 0.18s ease;
}

.crs-remove-file:hover {
    background: #fef2f2;
    color: #991b1b;
}

.crs-form-body .has-error .crs-upload-zone,
.crs-form-body .is-invalid ~ .crs-upload-zone {
    border-color: #dc2626 !important;
    background: linear-gradient(135deg, #fef2f2, #ffffff) !important;
    box-shadow: 0 0 0 0.15rem rgba(220, 38, 38, 0.16) !important;
}

/* SUBMIT REPORT RELEASE CONFIRMATION 2026: emphasize the mandatory CRS attestation. */
.crs-release-box {
    margin-top: 18px;
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr);
    gap: 14px;
    padding: 17px 18px;
    border-radius: 18px;
    background: linear-gradient(135deg, #FFFBEB, #FFFFFF);
    border: 1px solid #FCD34D;
    box-shadow: 0 10px 26px rgba(217, 119, 6, .08);
}

.crs-release-icon {
    width: 46px;
    height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: #FEF3C7;
    color: #B45309;
    font-size: 21px;
}

.crs-release-title {
    margin: 0 0 5px;
    color: #92400E;
    font-size: 13px;
    font-weight: 950;
}

.crs-release-box .form-check,
.crs-release-box .checkbox {
    margin: 0;
}

.crs-release-box .checkbox label,
.crs-release-box .form-check label {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    color: #78350F;
    font-size: 13px;
    line-height: 1.6;
    font-weight: 750;
    cursor: pointer;
}

.crs-release-box input[type="checkbox"] {
    width: 22px;
    height: 22px;
    flex: 0 0 22px;
    margin: 1px 0 0;
    accent-color: #2563EB;
    cursor: pointer;
}

/* SUBMIT REPORT VALIDATION STATE 2026: checked attestation is immediately visible. */
.crs-release-box.is-confirmed {
    border-color: #86EFAC;
    background: linear-gradient(135deg, #F0FDF4, #FFFFFF);
    box-shadow: 0 10px 26px rgba(22, 163, 74, .10);
}

.crs-release-box.is-confirmed .crs-release-icon {
    background: #DCFCE7;
    color: #15803D;
}

.crs-release-box.is-confirmed .crs-release-title,
.crs-release-box.is-confirmed .checkbox label,
.crs-release-box.is-confirmed .form-check label {
    color: #166534;
}

/* Buttons */
.crs-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 22px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.crs-form-readiness {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.crs-readiness-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 9px;
    border-radius: 999px;
    background: #E2E8F0;
    color: #64748B;
    font-size: 10px;
    font-weight: 850;
    transition: all .18s ease;
}

.crs-readiness-item.is-valid {
    background: #DCFCE7;
    color: #15803D;
}

.crs-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 10px 17px;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 850;
    text-decoration: none;
    border: 1px solid transparent;
    transition: all 0.18s ease;
    white-space: nowrap;
}

.crs-btn i {
    font-size: 15px;
}

.crs-btn:hover {
    text-decoration: none;
    transform: translateY(-1px);
}

.crs-btn-submit {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    box-shadow: 0 14px 24px rgba(37, 99, 235, 0.22);
}

.crs-btn-submit:hover {
    color: #ffffff;
    background: linear-gradient(135deg, #1d4ed8, #1e40af);
    box-shadow: 0 18px 30px rgba(37, 99, 235, 0.28);
}

.crs-btn-submit:disabled,
.crs-btn-submit[aria-disabled="true"] {
    border-color: #CBD5E1;
    background: #CBD5E1;
    color: #64748B;
    box-shadow: none;
    opacity: 0.72;
    cursor: not-allowed;
    transform: none;
}

.crs-btn-submit.is-ready {
    box-shadow: 0 12px 24px rgba(37, 99, 235, .24);
}

/* Yii errors */
.crs-form-body .help-block,
.crs-form-body .invalid-feedback {
    color: #dc2626 !important;
    font-size: 13px;
    font-weight: 700;
    margin-top: 8px;
    line-height: 1.4;
}

.crs-form-body .has-error .help-block {
    color: #dc2626 !important;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 8px 10px;
}

.crs-form-body .has-error .form-control,
.crs-form-body .is-invalid {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 0.15rem rgba(220, 38, 38, 0.16) !important;
}

.crs-form-body .has-error label,
.crs-form-body .has-error .control-label {
    color: #dc2626 !important;
}

/* Loading overlay */
.crs-loading-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    background:
        radial-gradient(circle at 20% 20%, rgba(37, 99, 235, 0.15) 0%, transparent 45%),
        radial-gradient(circle at 80% 80%, rgba(14, 165, 233, 0.13) 0%, transparent 45%),
        rgba(15, 23, 42, 0.38);
    backdrop-filter: blur(6px);
}

.crs-loading-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 18px 22px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 1px solid rgba(226, 232, 240, 0.9);
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.35);
}

.crs-spinner-circle {
    width: 42px;
    height: 42px;
    border-radius: 999px;
    border: 4px solid rgba(37, 99, 235, 0.14);
    border-top-color: #2563eb;
    animation: crsSpin 0.9s linear infinite;
}

@keyframes crsSpin {
    to {
        transform: rotate(360deg);
    }
}

.crs-loading-title {
    font-size: 14px;
    font-weight: 850;
    color: #0f172a;
}

.crs-loading-text {
    font-size: 12px;
    color: #64748b;
}

/* SweetAlert2 style like View PO */
.swal2-container {
    backdrop-filter: blur(1px);
}

.swal-crs-popup {
    width: 390px !important;
    padding: 26px 28px 18px !important;
    border-radius: 17px !important;
    background: #ffffff !important;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.30) !important;
}

.swal-crs-icon {
    width: 62px !important;
    height: 62px !important;
    margin: 0 auto 12px !important;
    border: 4px solid #8bb8c8 !important;
    border-radius: 999px !important;
    color: #7aa8ba !important;
    font-size: 34px !important;
    font-weight: 400 !important;
    line-height: 54px !important;
}

.swal-crs-title {
    margin: 0 !important;
    padding: 0 !important;
    color: #111827 !important;
    font-size: 21px !important;
    font-weight: 850 !important;
    letter-spacing: -0.02em !important;
}

.swal-crs-text {
    margin: 18px 0 0 !important;
    padding: 0 !important;
    color: #64748b !important;
    font-size: 14px !important;
    line-height: 1.45 !important;
    font-weight: 500 !important;
}

.swal-crs-actions {
    width: 100% !important;
    margin: 26px 0 0 !important;
    display: flex !important;
    justify-content: center !important;
    gap: 8px !important;
}

.swal-crs-confirm,
.swal-crs-cancel {
    min-width: 115px !important;
    min-height: 39px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 7px !important;
    border-radius: 9px !important;
    border: 0 !important;
    padding: 10px 15px !important;
    font-size: 14px !important;
    font-weight: 850 !important;
    line-height: 1 !important;
    box-shadow: none !important;
    outline: none !important;
    transition: all 0.16s ease !important;
}

.swal-crs-confirm {
    background: #2563eb !important;
    color: #ffffff !important;
    box-shadow: 0 12px 22px rgba(37, 99, 235, 0.28) !important;
}

.swal-crs-confirm:hover {
    background: #1d4ed8 !important;
    color: #ffffff !important;
    transform: translateY(-1px) !important;
}

.swal-crs-cancel {
    background: #e8eef5 !important;
    color: #334155 !important;
}

.swal-crs-cancel:hover {
    background: #dde6ef !important;
    color: #0f172a !important;
    transform: translateY(-1px) !important;
}

.swal-crs-confirm i,
.swal-crs-cancel i {
    font-size: 14px !important;
}

/* Responsive */
@media (max-width: 1100px) {
    .crs-stepper-card {
        padding-left: 18px;
        padding-right: 18px;
    }

    .crs-stepper {
        --step-width: 126px;
    }

    .crs-request-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .crs-page {
        padding: 18px 14px 28px;
    }

    .crs-header-top,
    .crs-form-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .crs-title-row {
        align-items: flex-start;
    }

    .crs-title {
        font-size: 24px;
    }

    .crs-request-pill,
    .crs-status-badge {
        justify-content: center;
    }

    .crs-stepper-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .crs-stepper {
        --step-width: 118px;
        --step-circle: 38px;
        --step-icon: 14px;
    }

    .crs-step-label {
        max-width: 95px;
        font-size: 11px;
    }

    .crs-form-body {
        padding: 16px;
    }

    .crs-upload-zone {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 12px;
    }

    .crs-upload-icon {
        margin: 0 auto;
    }

    .crs-upload-meta {
        justify-content: center;
    }

    .crs-upload-action {
        width: 100%;
    }

    .crs-selected-file {
        align-items: stretch;
        flex-direction: column;
    }

    .crs-remove-file {
        width: 100%;
    }

    .crs-actions {
        align-items: stretch;
        flex-direction: column;
        justify-content: stretch;
    }

    .crs-form-readiness {
        justify-content: center;
    }

    .crs-btn {
        width: 100%;
    }

    .crs-request-details-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .crs-request-grid,
    .crs-request-dates {
        grid-template-columns: 1fr;
    }

    .crs-release-box {
        grid-template-columns: 1fr;
    }

    .swal-crs-popup {
        width: calc(100% - 32px) !important;
    }
}

@media (max-width: 480px) {
    .crs-stepper-card {
        padding: 18px 14px 20px;
    }
}
CSS);
?>

<!-- SHARED FORM SYSTEM: presentation only; CRS validation rules remain unchanged. -->
<main class="dash-content crs-page can-form-page">
    <div class="container-fluid crs-shell">

        <!-- Header -->
        <section class="crs-header-card">
            <div class="crs-header-top">
                <div>
                    <div class="crs-eyebrow">
                        <i class="bi bi-shield-check"></i>
                        Aircraft Maintenance Release
                    </div>

                    <div class="crs-title-row">
                        <span class="crs-title-icon">
                            <i class="bi bi-file-earmark-medical"></i>
                        </span>

                        <h1 class="crs-title"><?= Html::encode($this->title) ?></h1>
                    </div>

                    <p class="crs-subtitle">
                        Upload the CRS document, complete the maintenance report, and confirm the aircraft or component is ready for release to service.
                    </p>
                </div>

                <div class="crs-request-pill">
                    <i class="bi bi-hash"></i>
                    Request ID: <?= Html::encode($requestId) ?>
                </div>
            </div>
        </section>

        <!-- Professional progress stepper -->
        <?php
        /*
         * Business flow kept unchanged:
         * 1. Create a Request
         * 2. MRO Quote
         * 3. PO Loaded
         * 4. PO Accepted By MRO
         * 5. Work Started
         * 6. MRO Report
         * 7. AO Feedback
         * 8. Request Closed
         *
         * This page is the CRS / MRO Report submission screen,
         * therefore the active step remains step 6.
         */
        $currentStep = 6;
        $steps = [
            1 => ['label' => 'Create a Request', 'icon' => 'bi-person-fill'],
            2 => ['label' => 'MRO Quote', 'icon' => 'bi-file-earmark-text-fill'],
            3 => ['label' => 'PO Loaded', 'icon' => 'bi-upload'],
            4 => ['label' => 'PO Accepted By MRO', 'icon' => 'bi-check-circle-fill'],
            5 => ['label' => 'Work Started', 'icon' => 'bi-play-circle-fill'],
            6 => ['label' => 'MRO Report', 'icon' => 'bi-file-earmark-bar-graph-fill'],
            7 => ['label' => 'AO Feedback', 'icon' => 'bi-chat-dots-fill'],
            8 => ['label' => 'Request Closed', 'icon' => 'bi-check-lg'],
        ];
        $totalSteps = count($steps);
        ?>
        <section class="crs-stepper-card">
            <div class="crs-stepper-head">
                <h2 class="crs-stepper-title">
                    <i class="bi bi-diagram-3"></i>
                    Request progress
                </h2>
                <div class="crs-stepper-note">
                    <i class="bi bi-geo-alt-fill"></i>
                    Step <?= Html::encode($currentStep) ?> of <?= Html::encode($totalSteps) ?>
                </div>
            </div>

            <div class="crs-stepper-scroll">
                <div class="crs-stepper" aria-label="Request progress">
                    <?php foreach ($steps as $number => $step): ?>
                        <?php
                        /*
                         * done    = completed business step
                         * active  = current business step
                         * pending = next business step
                         */
                        if ($number < $currentStep) {
                            $stepClass = 'crs-step-done';
                        } elseif ($number === $currentStep) {
                            $stepClass = 'crs-step-active';
                        } else {
                            $stepClass = 'crs-step-pending';
                        }
                        ?>

                        <div class="crs-step <?= Html::encode($stepClass) ?>">
                            <div class="crs-step-circle">
                                <i class="bi <?= Html::encode($step['icon']) ?>"></i>
                            </div>
                            <div class="crs-step-label">
                                <?= Html::encode($step['label']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- SUBMIT REPORT REQUEST DETAILS 2026: read-only context for the MRO release decision. -->
        <section class="crs-request-details-card" aria-label="Current request details">
            <div class="crs-request-details-head">
                <h2 class="crs-request-details-title"><i class="bi bi-airplane-engines"></i> Current Request Details</h2>
                <span class="crs-request-status"><i class="bi bi-circle-fill"></i> <?= Html::encode($requestStatus) ?></span>
            </div>

            <div class="crs-request-grid">
                <div class="crs-request-item"><div class="crs-request-label"><i class="bi bi-building"></i> AO / CAMO</div><div class="crs-request-value" title="<?= Html::encode($operatorName) ?>"><?= Html::encode($operatorName) ?></div></div>
                <div class="crs-request-item"><div class="crs-request-label"><i class="bi bi-airplane"></i> Aircraft</div><div class="crs-request-value" title="<?= Html::encode($aircraftName) ?>"><?= Html::encode($aircraftName) ?></div></div>
                <div class="crs-request-item"><div class="crs-request-label"><i class="bi bi-card-text"></i> Registration</div><div class="crs-request-value"><?= Html::encode($registration) ?></div></div>
                <div class="crs-request-item"><div class="crs-request-label"><i class="bi bi-upc-scan"></i> Serial Number</div><div class="crs-request-value"><?= Html::encode($serialNumber) ?></div></div>
                <div class="crs-request-item"><div class="crs-request-label"><i class="bi bi-geo-alt"></i> Maintenance Location</div><div class="crs-request-value" title="<?= Html::encode($maintenanceLocation) ?>"><?= Html::encode($maintenanceLocation) ?></div></div>
            </div>

            <div class="crs-request-dates">
                <div class="crs-request-item"><div class="crs-request-label"><i class="bi bi-calendar-event"></i> ETA</div><div class="crs-request-value"><?= Html::encode($etaText) ?></div></div>
                <div class="crs-request-item"><div class="crs-request-label"><i class="bi bi-calendar-check"></i> ETD</div><div class="crs-request-value"><?= Html::encode($etdText) ?></div></div>
            </div>

            <?= Html::textarea('request_details_display', $requestDetails, [
                'class' => 'crs-request-details-text',
                'readonly' => true,
                'aria-label' => 'Request information',
            ]) ?>
        </section>

        <!-- CRS form -->
        <section class="crs-form-card">
            <div class="crs-form-toolbar">
                <div>
                    <div class="crs-form-title-row">
                        <span class="crs-form-title-icon">
                            <i class="bi bi-folder2-open"></i>
                        </span>

                        <div>
                            <h2 class="crs-form-title">CRS Submission</h2>
                            <p class="crs-form-caption">
                                Attach the approved CRS document, write the maintenance report, and confirm compliance before submission.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="crs-status-badge">
                    <i class="bi bi-clipboard-check"></i>
                    MRO Report Step
                </div>
            </div>

            <?php $form = ActiveForm::begin([
                'options' => [
                    'enctype' => 'multipart/form-data',
                    'id' => 'repair-report-form'
                ]
            ]); ?>

            <div id="loading-spinner" class="crs-loading-overlay">
                <div class="crs-loading-card">
                    <div class="crs-spinner-circle"></div>
                    <div>
                        <div class="crs-loading-title">Submitting maintenance report...</div>
                        <div class="crs-loading-text">Please wait while the CRS document and maintenance report are being validated.</div>
                    </div>
                </div>
            </div>

            <div class="crs-form-body">

                <div id="crs-js-error" class="crs-js-error"></div>

                <div class="row g-3">
                    <!-- SUBMIT REPORT DEDUPLICATION 2026: Request ID remains in the page header only. -->
                    <div class="col-12">
                        <div class="crs-field-card">
                            <div class="crs-field-label">
                                <i class="bi bi-file-earmark-arrow-up"></i>
                                CRS Document <span class="text-danger">*</span>
                            </div>

                            <div class="crs-upload-wrapper" id="crs-upload-wrapper">
                                <?= $form->field($model, 'CRS_attachment', [
                                    'options' => ['class' => 'crs-upload-field'],
                                    'template' => "{input}
{error}",
                                ])->fileInput([
                                    'accept' => 'application/pdf, image/png, image/jpeg',
                                    'id' => 'repairreport-crs_attachment',
                                    'class' => 'crs-file-native',
                                    'required' => true,
                                    'aria-describedby' => 'crs-upload-help'
                                ])->label(false) ?>

                                <label for="repairreport-crs_attachment"
                                       class="crs-upload-zone"
                                       id="crs-upload-zone"
                                       role="button"
                                       tabindex="0"
                                       aria-label="Upload CRS document">
                                    <span class="crs-upload-icon">
                                        <i class="bi bi-cloud-arrow-up-fill"></i>
                                    </span>

                                    <span class="crs-upload-content">
                                        <span class="crs-upload-kicker">
                                            <i class="bi bi-airplane-engines"></i>
                                            Aircraft maintenance document
                                        </span>
                                        <span class="crs-upload-title">Drag and drop the CRS file here</span>
                                        <span class="crs-upload-text" id="crs-upload-help">
                                            Or browse your device to attach the approved Certificate of Release to Service.
                                        </span>

                                        <span class="crs-upload-meta">
                                            <span><i class="bi bi-file-earmark-pdf"></i> PDF</span>
                                            <span><i class="bi bi-image"></i> PNG / JPG</span>
                                            <span><i class="bi bi-shield-check"></i> Clear readable copy</span>
                                        </span>
                                    </span>

                                    <span class="crs-upload-action">
                                        Browse file
                                        <i class="bi bi-arrow-up-right"></i>
                                    </span>
                                </label>

                                <div class="crs-selected-file" id="crs-selected-file" aria-live="polite">
                                    <div class="crs-selected-file-info">
                                        <span class="crs-selected-file-icon">
                                            <i class="bi bi-file-earmark-check-fill"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="crs-selected-file-name" id="crs-selected-file-name">No file selected</div>
                                            <div class="crs-selected-file-meta" id="crs-selected-file-meta">Ready for submission</div>
                                        </div>
                                    </div>

                                    <button type="button" class="crs-remove-file" id="crs-remove-file">
                                        <i class="bi bi-trash3"></i>
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="crs-field-card">
                        <?= $form->field($model, 'report')->textarea([
                            'rows' => 12,
                            'class' => 'form-control',
                            'id' => 'repairreport-report',
                            'required' => true,
                            'maxlength' => 255,
                            'placeholder' => 'Write the aircraft maintenance report here...'
                        ])->label('<i class="bi bi-chat-left-text"></i> Maintenance Report') ?>
                    </div>
                </div>

                <!-- SUBMIT REPORT RELEASE CONFIRMATION 2026: prominent mandatory attestation. -->
                <div class="crs-release-box">
                    <span class="crs-release-icon"><i class="bi bi-shield-check"></i></span>
                    <div>
                        <h3 class="crs-release-title">Certificate of Release to Service confirmation</h3>
                        <?= $form->field($model, 'CRSed')->checkbox([
                            'id' => 'crsed-checkbox',
                            'required' => true,
                            'label' => 'I certify that the specified work, except as otherwise stated, was carried out in accordance with Part 145 and that the aircraft or aircraft component is ready for release to service.'
                        ]) ?>
                    </div>
                </div>

            </div>

            <div class="crs-actions">
                <!-- SUBMIT REPORT VALIDATION STATE 2026: all three indicators must be green. -->
                <div class="crs-form-readiness" aria-live="polite">
                    <span class="crs-readiness-item" id="crs-ready-file"><i class="bi bi-circle"></i> CRS document</span>
                    <span class="crs-readiness-item" id="crs-ready-report"><i class="bi bi-circle"></i> Maintenance report</span>
                    <span class="crs-readiness-item" id="crs-ready-confirmation"><i class="bi bi-circle"></i> Release confirmation</span>
                </div>

                <?= Html::submitButton(
                    '<i class="bi bi-cloud-arrow-up"></i> Submit CRS Document',
                    [
                        'class' => 'btn crs-btn crs-btn-submit',
                        'id' => 'submit-button',
                        'disabled' => true,
                        'aria-disabled' => 'true',
                    ]
                ) ?>
            </div>

            <?php ActiveForm::end(); ?>

        </section>

    </div>
</main>

<?php
$this->registerJs(<<<JS
(function () {
    var form = document.getElementById("repair-report-form");
    var submitButton = document.getElementById("submit-button");
    var loadingIndicator = document.getElementById("loading-spinner");
    var errorBox = document.getElementById("crs-js-error");
    var fileInput = document.getElementById("repairreport-crs_attachment");
    var uploadZone = document.getElementById("crs-upload-zone");
    var selectedFile = document.getElementById("crs-selected-file");
    var selectedFileName = document.getElementById("crs-selected-file-name");
    var selectedFileMeta = document.getElementById("crs-selected-file-meta");
    var removeFileButton = document.getElementById("crs-remove-file");
    var reportInput = document.getElementById("repairreport-report");
    var checkbox = document.getElementById("crsed-checkbox");
    var releaseBox = document.querySelector(".crs-release-box");
    var readyFile = document.getElementById("crs-ready-file");
    var readyReport = document.getElementById("crs-ready-report");
    var readyConfirmation = document.getElementById("crs-ready-confirmation");
    var allowedMimeTypes = ["application/pdf", "image/png", "image/jpeg"];
    var allowedExtensions = [".pdf", ".png", ".jpg", ".jpeg"];

    if (!form || !submitButton || !loadingIndicator) {
        return;
    }

    function showLoader() {
        submitButton.disabled = true;
        loadingIndicator.style.display = "flex";
    }

    function hideLoader() {
        loadingIndicator.style.display = "none";
        updateFormReadiness();
    }

    function showInlineError(message) {
        if (!errorBox) {
            return;
        }

        errorBox.innerHTML = message;
        errorBox.style.display = "block";
        errorBox.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
    }

    function clearInlineError() {
        if (!errorBox) {
            return;
        }

        errorBox.innerHTML = "";
        errorBox.style.display = "none";
    }

    function formatFileSize(bytes) {
        if (!bytes && bytes !== 0) {
            return "Size unavailable";
        }

        if (bytes < 1024) {
            return bytes + " B";
        }

        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(1).replace(".0", "") + " KB";
        }

        return (bytes / (1024 * 1024)).toFixed(1).replace(".0", "") + " MB";
    }

    function getFileExtension(fileName) {
        var dotIndex = fileName.lastIndexOf(".");

        if (dotIndex === -1) {
            return "";
        }

        return fileName.substring(dotIndex).toLowerCase();
    }

    function isAcceptedCrsFile(file) {
        if (!file) {
            return false;
        }

        var extension = getFileExtension(file.name || "");

        return allowedMimeTypes.indexOf(file.type) !== -1 || allowedExtensions.indexOf(extension) !== -1;
    }

    function hasSelectedFile() {
        return fileInput && ((fileInput.files && fileInput.files.length > 0) || !!fileInput.value);
    }

    /*
     * SUBMIT REPORT VALIDATION STATE 2026: enable submission only when the
     * CRS file, maintenance report and Part 145 confirmation are all valid.
     */
    function setReadinessState(element, isValid) {
        if (!element) {
            return;
        }

        element.classList.toggle("is-valid", isValid);
        var icon = element.querySelector("i");
        if (icon) {
            icon.className = isValid ? "bi bi-check-circle-fill" : "bi bi-circle";
        }
    }

    function updateFormReadiness() {
        var fileIsValid = !!(
            hasSelectedFile()
            && fileInput.files
            && fileInput.files.length > 0
            && isAcceptedCrsFile(fileInput.files[0])
        );
        var reportText = reportInput ? reportInput.value.trim() : "";
        var reportIsValid = reportText.length > 0 && reportText.length <= 255;
        var confirmationIsValid = !!(checkbox && checkbox.checked);
        var formIsReady = fileIsValid && reportIsValid && confirmationIsValid;

        setReadinessState(readyFile, fileIsValid);
        setReadinessState(readyReport, reportIsValid);
        setReadinessState(readyConfirmation, confirmationIsValid);

        if (releaseBox) {
            releaseBox.classList.toggle("is-confirmed", confirmationIsValid);
        }

        submitButton.disabled = !formIsReady;
        submitButton.setAttribute("aria-disabled", formIsReady ? "false" : "true");
        submitButton.classList.toggle("is-ready", formIsReady);
    }

    function resetUploadPreview() {
        if (uploadZone) {
            uploadZone.classList.remove("crs-upload-has-file");
            uploadZone.classList.remove("crs-upload-dragover");
        }

        if (selectedFile) {
            selectedFile.style.display = "none";
        }

        if (selectedFileName) {
            selectedFileName.textContent = "No file selected";
        }

        if (selectedFileMeta) {
            selectedFileMeta.textContent = "Ready for submission";
        }

        updateFormReadiness();
    }

    function updateUploadPreview() {
        if (!fileInput || !fileInput.files || !fileInput.files.length) {
            resetUploadPreview();
            return;
        }

        var file = fileInput.files[0];

        if (!isAcceptedCrsFile(file)) {
            fileInput.value = "";
            resetUploadPreview();
            showInlineError('<i class="bi bi-exclamation-triangle"></i> Unsupported file format. Please upload a PDF, PNG, JPG, or JPEG document.');
            showValidationAlert(
                "Unsupported file format",
                "Please attach a CRS document as PDF, PNG, JPG, or JPEG.",
                "warning"
            );
            return;
        }

        clearInlineError();

        if (uploadZone) {
            uploadZone.classList.add("crs-upload-has-file");
        }

        if (selectedFileName) {
            selectedFileName.textContent = file.name;
        }

        if (selectedFileMeta) {
            selectedFileMeta.textContent = formatFileSize(file.size) + " • CRS document selected";
        }

        if (selectedFile) {
            selectedFile.style.display = "flex";
        }

        updateFormReadiness();
    }

    function assignDroppedFile(file) {
        if (!fileInput || !file) {
            return;
        }

        try {
            if (window.DataTransfer) {
                var dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
            } else {
                fileInput.files = file;
            }

            fileInput.dispatchEvent(new Event("change", { bubbles: true }));
        } catch (error) {
            showValidationAlert(
                "Upload action required",
                "Your browser blocked drag and drop for this field. Please use the Browse file button instead.",
                "info"
            );
        }
    }

    function showValidationAlert(title, message, icon) {
        Swal.fire({
            title: title,
            html: message,
            icon: icon || "warning",
            confirmButtonText: '<i class="bi bi-check2-circle"></i> Got it',
            buttonsStyling: false,
            customClass: {
                // SHARED FORM CONFIRMATION: retain CRS-specific validation and button classes.
                popup: "swal-crs-popup can-form-swal",
                icon: "swal-crs-icon",
                title: "swal-crs-title",
                htmlContainer: "swal-crs-text",
                actions: "swal-crs-actions",
                confirmButton: "swal-crs-confirm"
            }
        });
    }

    function validateCrsForm() {
        clearInlineError();

        if (!hasSelectedFile()) {
            showInlineError('<i class="bi bi-exclamation-triangle"></i> Please upload the CRS document.');
            showValidationAlert(
                "CRS document required",
                "Please upload the Certificate of Release to Service document before submitting the maintenance report.",
                "warning"
            );
            return false;
        }

        if (fileInput.files && fileInput.files.length && !isAcceptedCrsFile(fileInput.files[0])) {
            showInlineError('<i class="bi bi-exclamation-triangle"></i> Unsupported file format. Please upload a PDF, PNG, JPG, or JPEG document.');
            showValidationAlert(
                "Unsupported file format",
                "Please attach a CRS document as PDF, PNG, JPG, or JPEG.",
                "warning"
            );
            return false;
        }

        if (!reportInput || reportInput.value.trim() === "") {
            showInlineError('<i class="bi bi-exclamation-triangle"></i> Please complete the maintenance report.');
            showValidationAlert(
                "Maintenance report required",
                "Please describe the completed maintenance work before submitting the CRS document.",
                "warning"
            );
            return false;
        }

        if (!checkbox || !checkbox.checked) {
            showInlineError('<i class="bi bi-exclamation-triangle"></i> You must confirm the Certificate of Release to Service statement.');
            showValidationAlert(
                "Confirmation required",
                "Please confirm the Certificate of Release to Service statement before submitting.",
                "warning"
            );
            return false;
        }

        return true;
    }

    if (fileInput) {
        fileInput.addEventListener("change", updateUploadPreview);
    }

    if (reportInput) {
        reportInput.addEventListener("input", updateFormReadiness);
    }

    if (checkbox) {
        checkbox.addEventListener("change", updateFormReadiness);
    }

    if (uploadZone && fileInput) {
        uploadZone.addEventListener("keydown", function (event) {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                fileInput.click();
            }
        });

        ["dragenter", "dragover"].forEach(function (eventName) {
            uploadZone.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                uploadZone.classList.add("crs-upload-dragover");
            });
        });

        ["dragleave", "drop"].forEach(function (eventName) {
            uploadZone.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                uploadZone.classList.remove("crs-upload-dragover");
            });
        });

        uploadZone.addEventListener("drop", function (event) {
            var files = event.dataTransfer && event.dataTransfer.files;

            if (files && files.length) {
                assignDroppedFile(files[0]);
            }
        });
    }

    if (removeFileButton && fileInput) {
        removeFileButton.addEventListener("click", function (event) {
            event.preventDefault();
            fileInput.value = "";
            resetUploadPreview();
            clearInlineError();
        });
    }

    submitButton.addEventListener("click", function (event) {
        event.preventDefault();

        if (!validateCrsForm()) {
            hideLoader();
            return;
        }

        Swal.fire({
            title: "Submit CRS?",
            html: "Request #<?= Html::encode($requestId) ?>: You will submit the CRS document and aircraft maintenance report.",
            icon: "question",
            showCancelButton: true,
            // FORM DIALOG ACTIONS: identify the CRS submission instead of using a generic choice.
            confirmButtonText: '<i class="bi bi-cloud-arrow-up"></i> Submit CRS',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review report',
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            customClass: {
                // SHARED FORM CONFIRMATION: retain CRS-specific validation and button classes.
                popup: "swal-crs-popup can-form-swal",
                icon: "swal-crs-icon",
                title: "swal-crs-title",
                htmlContainer: "swal-crs-text",
                actions: "swal-crs-actions",
                confirmButton: "swal-crs-confirm",
                cancelButton: "swal-crs-cancel"
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                showLoader();
                form.submit();
            }
        });
    });

    /* SUBMIT REPORT VALIDATION STATE 2026: also protect keyboard/native submission. */
    form.addEventListener("submit", function (event) {
        if (!validateCrsForm()) {
            event.preventDefault();
            hideLoader();
            return;
        }

        showLoader();
    });

    window.addEventListener("pageshow", function () {
        hideLoader();
    });

    updateFormReadiness();
})();
JS, View::POS_READY);
?>

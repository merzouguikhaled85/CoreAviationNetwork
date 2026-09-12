<?php

use yii\helpers\Html;
use yii\web\View;
use app\components\UrlIdHelper;

$this->title = 'Aircraft Maintenance Report Review';
$this->params['breadcrumbs'][] = ['label' => 'Maintenance Requests', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $request->request_id, 'url' => ['view', 'id' => UrlIdHelper::encode($request->id)]];
$this->params['breadcrumbs'][] = $this->title;

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

$requestId = $request->request_id ?? 'N/A';
$totalReports = is_countable($repairReports) ? count($repairReports) : 0;

/*
 * VIEW REPORTS REQUEST DETAILS 2026: prepare the linked operational request
 * for display only. Report and workflow business logic remain unchanged.
 */
$linkedRequest = $request ? $request->request : null;
$requestAircraft = $linkedRequest ? $linkedRequest->aircraft : null;
$requestOperator = $linkedRequest ? $linkedRequest->aO : null;

$operatorName = $requestOperator
    ? ($requestOperator->company_name
        ?: trim(($requestOperator->first_name ?: '') . ' ' . ($requestOperator->last_name ?: ''))
        ?: $requestOperator->username)
    : 'N/A';
$aircraftName = $requestAircraft
    ? trim(($requestAircraft->manufacturer ?: '') . ' ' . ($requestAircraft->model ?: ''))
    : 'N/A';
$aircraftName = $aircraftName !== '' ? $aircraftName : 'N/A';
$registration = $linkedRequest && $linkedRequest->aircraft_registration
    ? $linkedRequest->aircraft_registration
    : ($requestAircraft && $requestAircraft->registration_number ? $requestAircraft->registration_number : 'N/A');
$serialNumber = $linkedRequest && $linkedRequest->serial_number
    ? $linkedRequest->serial_number
    : ($requestAircraft && $requestAircraft->serial_number ? $requestAircraft->serial_number : 'N/A');
$maintenanceLocation = $linkedRequest && $linkedRequest->location ? $linkedRequest->location : 'N/A';
$requestStatus = $linkedRequest && $linkedRequest->status
    ? ucwords(str_replace('_', ' ', $linkedRequest->status))
    : 'N/A';
$requestDetails = $linkedRequest && $linkedRequest->request_details
    ? $linkedRequest->request_details
    : 'No request details available.';

$formatOperationalDate = static function ($value) {
    if (empty($value)) {
        return 'N/A';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y H:i', $timestamp) : $value;
};

$etaText = $formatOperationalDate($linkedRequest ? $linkedRequest->eta : null);
$etdText = $formatOperationalDate($linkedRequest ? $linkedRequest->etd : null);

$this->registerCss(<<<CSS
.reports-page {
    min-height: calc(100vh - 90px);
    padding: 24px 28px 38px;
    background:
        radial-gradient(circle at 10% 10%, rgba(37, 99, 235, 0.08), transparent 30%),
        radial-gradient(circle at 90% 18%, rgba(14, 165, 233, 0.07), transparent 28%),
        linear-gradient(135deg, #f8fafc 0%, #eef4ff 100%);
}

.reports-shell {
    max-width: 1150px;
    margin: 0 auto;
}

/* Header */
.reports-header-card {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 22px 55px rgba(15, 23, 42, 0.08);
    padding: 24px 26px;
    margin-bottom: 22px;
}

.reports-header-card::before {
    content: "";
    position: absolute;
    top: -90px;
    right: -85px;
    width: 245px;
    height: 245px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.16), transparent 70%);
    pointer-events: none;
}

.reports-header-top {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
}

.reports-eyebrow {
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

.reports-title-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.reports-title-icon {
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

.reports-title {
    margin: 0;
    color: #020617;
    font-size: 28px;
    font-weight: 850;
    letter-spacing: -0.04em;
}

.reports-subtitle {
    margin: 11px 0 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
}

.reports-request-pill {
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

.reports-request-pill i {
    color: #dbeafe;
    font-size: 15px;
}

/* Professional stepper */
.reports-stepper-card {
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

.reports-stepper-card::before {
    content: "";
    position: absolute;
    top: -80px;
    right: -80px;
    width: 190px;
    height: 190px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.10), transparent 70%);
    pointer-events: none;
}

.reports-stepper-head {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 18px;
}

.reports-stepper-title {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 9px;
    color: #0f172a;
    font-size: 14px;
    font-weight: 900;
    letter-spacing: -0.01em;
}

.reports-stepper-title i {
    color: #2563eb;
}

.reports-stepper-note {
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

.reports-stepper-scroll {
    position: relative;
    z-index: 2;
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 8px 2px 8px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}

.reports-stepper-scroll::-webkit-scrollbar {
    height: 7px;
}

.reports-stepper-scroll::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 999px;
}

.reports-stepper-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

.reports-stepper {
    display: grid;
    grid-template-columns: repeat(8, minmax(var(--step-width), 1fr));
    align-items: start;
    min-width: calc(var(--step-width) * 8);
}

.reports-step {
    position: relative;
    min-width: var(--step-width);
    text-align: center;
}

/* Connector line between steps */
.reports-step:not(:first-child)::before {
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

.reports-step.reports-step-done::before,
.reports-step.reports-step-active::before {
    background: linear-gradient(90deg, #22c55e, #2563eb);
}

.reports-step-circle {
    position: relative;
    z-index: 2;
    width: var(--step-circle);
    height: var(--step-circle);
    margin: 0 auto 10px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    color: #94a3b8;
    font-size: var(--step-icon);
    box-shadow: 0 7px 16px rgba(15, 23, 42, 0.08);
    transition: all 0.18s ease;
}

.reports-step-label {
    display: block;
    max-width: 112px;
    margin: 0 auto;
    color: #64748b;
    font-size: 11.5px;
    font-weight: 850;
    line-height: 1.25;
    text-align: center;
    white-space: normal;
}

.reports-step.reports-step-done .reports-step-circle {
    background: #22c55e;
    border-color: #22c55e;
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(34, 197, 94, 0.18);
}

.reports-step.reports-step-done .reports-step-label {
    color: #15803d;
}

.reports-step.reports-step-active .reports-step-circle {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    border-color: #2563eb;
    color: #ffffff;
    box-shadow:
        0 12px 24px rgba(37, 99, 235, 0.28),
        0 0 0 7px rgba(37, 99, 235, 0.10);
}

.reports-step.reports-step-active .reports-step-label {
    color: #1d4ed8;
    font-weight: 900;
}

.reports-step.reports-step-pending .reports-step-circle {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #94a3b8;
}

.reports-step.reports-step-pending .reports-step-label {
    color: #94a3b8;
}

/* VIEW REPORTS REQUEST DETAILS 2026: one non-duplicated operational summary. */
.reports-details-card {
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 18px 44px rgba(15, 23, 42, 0.06);
    padding: 18px 20px;
    margin-bottom: 24px;
}

.reports-details-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 14px;
}

.reports-details-title {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin: 0;
    color: #0F172A;
    font-size: 15px;
    font-weight: 900;
}

.reports-details-title i {
    color: #2563EB;
}

.reports-request-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 999px;
    background: #DCFCE7;
    color: #166534;
    font-size: 11px;
    font-weight: 900;
}

.reports-details-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
}

.reports-detail-item {
    min-width: 0;
    padding: 11px 12px;
    border-radius: 13px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.reports-detail-label {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #64748b;
    font-size: 9px;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 6px;
}

.reports-detail-label i {
    color: #2563eb;
}

.reports-detail-value {
    overflow: hidden;
    color: #0f172a;
    font-size: 12px;
    font-weight: 850;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.reports-details-dates {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.reports-request-info {
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

/* Table card */
.reports-table-card {
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 22px 55px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.reports-table-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 20px 22px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #ffffff, #f8fafc);
}

.reports-table-title-row {
    display: flex;
    align-items: center;
    gap: 11px;
}

.reports-table-title-icon {
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

.reports-table-title {
    margin: 0;
    color: #020617;
    font-size: 18px;
    font-weight: 850;
    letter-spacing: -0.02em;
}

.reports-table-caption {
    margin: 5px 0 0;
    color: #64748b;
    font-size: 13px;
}

.reports-count-badge {
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

.reports-count-badge i {
    color: #2563eb;
}

.reports-table-wrap {
    width: 100%;
    overflow-x: auto;
}

/*
 * MAINTENANCE REPORT CARDS 2026: readable report records replace the dense
 * table presentation. Existing CRS and quotation actions are kept unchanged.
 */
.reports-record-list {
    display: grid;
    gap: 14px;
    padding: 18px;
    background: #F8FAFC;
}

.reports-record {
    overflow: hidden;
    border: 1px solid #DCE6F0;
    border-radius: 18px;
    background: #FFFFFF;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .055);
}

.reports-record-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 15px;
    border-bottom: 1px solid #E8EEF5;
    background: linear-gradient(135deg, #FFFFFF, #F8FBFF);
}

.reports-record-identity,
.reports-record-states {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.reports-record-number {
    color: #0F172A;
    font-size: 13px;
    font-weight: 950;
}

.reports-record-date {
    color: #64748B;
    font-size: 11px;
    font-weight: 750;
}

.reports-record-state {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 8px;
    border-radius: 999px;
    background: #DCFCE7;
    color: #15803D;
    font-size: 10px;
    font-weight: 900;
}

.reports-record-state.is-warning {
    background: #FEF3C7;
    color: #A16207;
}

.reports-record-state.is-info {
    background: #DBEAFE;
    color: #1D4ED8;
}

.reports-record-body {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(320px, .95fr) minmax(175px, .45fr);
    gap: 14px;
    padding: 15px;
}

.reports-record-panel {
    min-width: 0;
    padding: 13px;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    background: #FAFCFE;
}

.reports-record-panel-title {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 9px;
    color: #475569;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.reports-record-panel-title i {
    color: #2563EB;
}

.reports-report-textarea {
    display: block;
    width: 100%;
    min-height: 112px;
    padding: 10px 11px;
    resize: vertical;
    border: 1px dashed #CBD5E1;
    border-radius: 11px;
    background: #FFFFFF;
    color: #334155;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.55;
}

.reports-record-action-panel {
    display: flex;
    flex-direction: column;
}

.reports-record-action-panel .reports-actions {
    margin-top: auto;
}

.reports-table {
    width: 100%;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.reports-table thead th {
    padding: 15px 18px;
    background: #f8fafc;
    color: #334155;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.045em;
    text-transform: uppercase;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

.reports-table thead th i {
    margin-right: 7px;
    color: #64748b;
}

.reports-table tbody td {
    padding: 18px;
    color: #334155;
    font-size: 14px;
    vertical-align: middle;
    border-bottom: 1px solid #eef2f7;
}

.reports-table tbody tr {
    transition: background 0.18s ease;
}

.reports-table tbody tr:hover {
    background: #f8fafc;
}

.reports-report-box {
    max-width: 620px;
    max-height: 86px;
    overflow: hidden;
    color: #475569;
    line-height: 1.6;
    word-break: break-word;
    position: relative;
}

.reports-report-box:hover {
    max-height: none;
}

.reports-report-empty {
    color: #94a3b8;
    font-weight: 700;
}

/* Professional CRS release document card */
.reports-attachment-card,
.reports-attachment-missing {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    min-width: 300px;
    max-width: 440px;
    padding: 13px;
    border-radius: 18px;
    border: 1px solid rgba(191, 219, 254, 0.95);
    background:
        linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(239, 246, 255, 0.86));
    box-shadow: 0 16px 34px rgba(37, 99, 235, 0.10);
}

.reports-attachment-missing {
    justify-content: flex-start;
    border-color: rgba(226, 232, 240, 0.95);
    background: #f8fafc;
    box-shadow: none;
}

.reports-attachment-main {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 0;
}

.reports-attachment-icon,
.reports-attachment-missing-icon {
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
}

.reports-attachment-icon {
    color: #ffffff;
    background: linear-gradient(135deg, #2563eb, #0f766e);
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.22);
}

.reports-attachment-missing-icon {
    color: #64748b;
    background: #e2e8f0;
}

.reports-attachment-copy {
    min-width: 0;
}

.reports-attachment-copy strong {
    display: block;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    letter-spacing: -0.01em;
}

.reports-attachment-copy span {
    display: block;
    max-width: 210px;
    margin-top: 3px;
    color: #475569;
    font-size: 12px;
    font-weight: 750;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.reports-attachment-copy small {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 7px;
    color: #0f766e;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.reports-attachment-copy small i {
    font-size: 12px;
}

.reports-attachment-actions {
    flex: 0 0 auto;
}

.reports-action-state {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 38px;
    padding: 8px 12px;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #64748b;
    font-size: 12px;
    font-weight: 850;
    white-space: nowrap;
}

.reports-action-state i {
    color: #94a3b8;
}

.reports-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* Buttons */
.reports-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 9px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 850;
    text-decoration: none;
    border: 1px solid transparent;
    transition: all 0.18s ease;
    white-space: nowrap;
}

.reports-btn i {
    font-size: 15px;
    line-height: 1;
}

.reports-btn:hover {
    text-decoration: none;
    transform: translateY(-1px);
}

.reports-btn-open {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 12px 22px rgba(15, 23, 42, 0.18);
}

.reports-btn-open i {
    color: #ffffff;
}

.reports-btn-open:hover {
    background: #1d4ed8;
    color: #ffffff;
    border-color: #1d4ed8;
    box-shadow: 0 14px 26px rgba(37, 99, 235, 0.24);
}

.reports-btn-quote {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 12px 22px rgba(37, 99, 235, 0.22);
}

.reports-btn-quote i {
    color: #ffffff;
}

.reports-btn-quote:hover {
    color: #ffffff;
    box-shadow: 0 16px 28px rgba(37, 99, 235, 0.28);
}

.reports-btn-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 10px 17px;
    border-radius: 9px;
    background: #050505ff;
    color: #ffffff;
    font-size: 14px;
    font-weight: 850;
    text-decoration: none;
    box-shadow: 0 14px 24px rgba(15, 23, 42, 0.18);
    transition: all 0.18s ease;
}

.reports-btn-back i {
    color: #ffffff;
    font-size: 15px;
}

.reports-btn-back:hover {
    color: #0a0a0aff;
    text-decoration: none;
    background: #fcfcfcff;
    border: 1px solid #030303ff;
    transform: translateY(-1px);
}

.reports-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 18px 22px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

/* Empty state */
.reports-empty {
    padding: 42px 20px;
    text-align: center;
    color: #64748b;
}

.reports-empty-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 13px;
    border-radius: 18px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 24px;
}

.reports-empty strong {
    display: block;
    margin-bottom: 5px;
    color: #0f172a;
    font-size: 15px;
}

/* SweetAlert2 */
.swal2-container {
    backdrop-filter: blur(1px);
}

.swal-reports-popup {
    width: 390px !important;
    padding: 26px 28px 18px !important;
    border-radius: 17px !important;
    background: #ffffff !important;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.30) !important;
}

.swal-reports-icon {
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

.swal-reports-title {
    margin: 0 !important;
    padding: 0 !important;
    color: #111827 !important;
    font-size: 21px !important;
    font-weight: 850 !important;
    letter-spacing: -0.02em !important;
}

.swal-reports-text {
    margin: 18px 0 0 !important;
    padding: 0 !important;
    color: #64748b !important;
    font-size: 14px !important;
    line-height: 1.45 !important;
    font-weight: 500 !important;
}

.swal-reports-actions {
    width: 100% !important;
    margin: 26px 0 0 !important;
    display: flex !important;
    justify-content: center !important;
    gap: 8px !important;
}

.swal-reports-confirm,
.swal-reports-cancel {
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

.swal-reports-confirm {
    background: #2563eb !important;
    color: #ffffff !important;
    box-shadow: 0 12px 22px rgba(37, 99, 235, 0.28) !important;
}

.swal-reports-confirm:hover {
    background: #1d4ed8 !important;
    color: #ffffff !important;
    transform: translateY(-1px) !important;
}

.swal-reports-cancel {
    background: #e8eef5 !important;
    color: #334155 !important;
}

.swal-reports-cancel:hover {
    background: #dde6ef !important;
    color: #0f172a !important;
    transform: translateY(-1px) !important;
}

/* Responsive */
@media (max-width: 1100px) {
    .reports-stepper-card {
        --step-width: 126px;
        --step-circle: 40px;
        --step-icon: 15px;
    }

    .reports-details-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .reports-record-body {
        grid-template-columns: 1fr 1fr;
    }

    .reports-record-action-panel {
        grid-column: 1 / -1;
    }
}

@media (max-width: 768px) {
    .reports-page {
        padding: 18px 14px 28px;
    }

    .reports-header-top,
    .reports-table-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .reports-title-row {
        align-items: flex-start;
    }

    .reports-title {
        font-size: 24px;
    }

    .reports-request-pill,
    .reports-count-badge {
        justify-content: center;
    }

    .reports-stepper-card {
        --step-width: 118px;
        --step-circle: 38px;
        --step-icon: 14px;
        padding: 18px 14px 20px;
    }

    .reports-stepper-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .reports-step-label {
        max-width: 104px;
        font-size: 11px;
    }

    .reports-details-grid {
        grid-template-columns: 1fr;
    }

    .reports-details-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .reports-details-dates {
        grid-template-columns: 1fr;
    }

    .reports-table thead {
        display: none;
    }

    .reports-record-list {
        padding: 12px;
    }

    .reports-record-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .reports-record-body {
        grid-template-columns: 1fr;
    }

    .reports-record-action-panel {
        grid-column: auto;
    }

    .reports-table,
    .reports-table tbody,
    .reports-table tr,
    .reports-table td {
        display: block;
        width: 100%;
    }

    .reports-table tbody tr {
        padding: 14px;
        border-bottom: 1px solid #e2e8f0;
    }

    .reports-table tbody td {
        border-bottom: 0;
        padding: 9px 4px;
    }

    .reports-table tbody td::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 5px;
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .reports-attachment-card,
    .reports-attachment-missing {
        min-width: 0;
        max-width: none;
        width: 100%;
        align-items: stretch;
        flex-direction: column;
    }

    .reports-attachment-actions,
    .reports-attachment-actions .reports-btn {
        width: 100%;
    }

    .reports-attachment-copy span {
        max-width: 100%;
    }

    .reports-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .reports-btn,
    .reports-btn-back {
        width: 100%;
    }

    .reports-footer {
        justify-content: stretch;
        flex-direction: column;
    }

    .swal-reports-popup {
        width: calc(100% - 32px) !important;
    }
}

@media (max-width: 480px) {
    .reports-stepper-card {
        --step-width: 112px;
        --step-circle: 36px;
        --step-icon: 13px;
    }

    .reports-step-label {
        max-width: 98px;
    }
}
CSS);
?>

<!-- SHARED DETAIL SYSTEM: presentation only; report actions remain unchanged. -->
<main class="dash-content reports-page can-detail-page">
    <div class="container-fluid reports-shell">

        <!-- Header -->
        <section class="reports-header-card">
            <div class="reports-header-top">
                <div>
                    <div class="reports-eyebrow">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        Airworthiness Documentation
                    </div>

                    <div class="reports-title-row">
                        <span class="reports-title-icon">
                            <i class="bi bi-clipboard2-check"></i>
                        </span>

                        <h1 class="reports-title"><?= Html::encode($this->title) ?></h1>
                    </div>

                    <p class="reports-subtitle">
                        Review MRO maintenance findings, access CRS airworthiness release documents, and respond when a revised quotation is required.
                    </p>
                </div>

                <div class="reports-request-pill">
                    <i class="bi bi-hash"></i>
                    Maintenance Request: <?= Html::encode($requestId) ?>
                </div>
            </div>
        </section>

        <!-- Professional progress stepper -->
        <?php
        /*
         * Business workflow preserved:
         * 1. Create a Request
         * 2. MRO Quote
         * 3. PO Loaded
         * 4. PO Accepted By MRO
         * 5. Work Started
         * 6. MRO Report
         * 7. AO Feedback
         * 8. Request Closed
         */
        $currentStep = 6;

        $steps = [
            1 => [
                'label' => 'Create a Request',
                'icon'  => 'bi-person-fill',
            ],
            2 => [
                'label' => 'MRO Quote',
                'icon'  => 'bi-file-earmark-text-fill',
            ],
            3 => [
                'label' => 'PO Loaded',
                'icon'  => 'bi-upload',
            ],
            4 => [
                'label' => 'PO Accepted By MRO',
                'icon'  => 'bi-check-circle-fill',
            ],
            5 => [
                'label' => 'Work Started',
                'icon'  => 'bi-play-circle-fill',
            ],
            6 => [
                'label' => 'MRO Report',
                'icon'  => 'bi-file-earmark-bar-graph',
            ],
            7 => [
                'label' => 'AO Feedback',
                'icon'  => 'bi-star-fill',
            ],
            8 => [
                'label' => 'Request Closed',
                'icon'  => 'bi-check2-circle',
            ],
        ];

        $totalSteps = count($steps);
        ?>
        <section class="reports-stepper-card">
            <div class="reports-stepper-head">
                <h2 class="reports-stepper-title">
                    <i class="bi bi-signpost-split"></i>
                    Request progress
                </h2>

                <div class="reports-stepper-note">
                    <i class="bi bi-shield-check"></i>
                    Step <?= Html::encode($currentStep) ?> of <?= Html::encode($totalSteps) ?>
                </div>
            </div>

            <div class="reports-stepper-scroll" aria-label="Request progress">
                <div class="reports-stepper">
                    <?php foreach ($steps as $number => $step): ?>
                        <?php
                        /*
                         * done    : completed phase
                         * active  : current phase
                         * pending : upcoming phase
                         */
                        if ($number < $currentStep) {
                            $stepClass = 'reports-step-done';
                            $icon = 'bi-check-lg';
                        } elseif ($number === $currentStep) {
                            $stepClass = 'reports-step-active';
                            $icon = $step['icon'];
                        } else {
                            $stepClass = 'reports-step-pending';
                            $icon = $step['icon'];
                        }
                        ?>

                        <div class="reports-step <?= Html::encode($stepClass) ?>">
                            <div class="reports-step-circle">
                                <i class="bi <?= Html::encode($icon) ?>"></i>
                            </div>

                            <div class="reports-step-label">
                                <?= Html::encode($step['label']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- VIEW REPORTS REQUEST DETAILS 2026: Request ID stays in the header; report count stays in the table. -->
        <section class="reports-details-card">
            <div class="reports-details-head">
                <h2 class="reports-details-title"><i class="bi bi-airplane-engines"></i> Current Request Details</h2>
                <span class="reports-request-status"><i class="bi bi-circle-fill"></i> <?= Html::encode($requestStatus) ?></span>
            </div>

            <div class="reports-details-grid">
                <div class="reports-detail-item">
                    <div class="reports-detail-label">
                        <i class="bi bi-building"></i>
                        AO / CAMO
                    </div>
                    <div class="reports-detail-value" title="<?= Html::encode($operatorName) ?>">
                        <?= Html::encode($operatorName) ?>
                    </div>
                </div>

                <div class="reports-detail-item">
                    <div class="reports-detail-label">
                        <i class="bi bi-airplane"></i>
                        Aircraft
                    </div>
                    <div class="reports-detail-value" title="<?= Html::encode($aircraftName) ?>">
                        <?= Html::encode($aircraftName) ?>
                    </div>
                </div>

                <div class="reports-detail-item">
                    <div class="reports-detail-label">
                        <i class="bi bi-card-text"></i>
                        Registration
                    </div>
                    <div class="reports-detail-value">
                        <?= Html::encode($registration) ?>
                    </div>
                </div>

                <div class="reports-detail-item"><div class="reports-detail-label"><i class="bi bi-upc-scan"></i> Serial Number</div><div class="reports-detail-value"><?= Html::encode($serialNumber) ?></div></div>
                <div class="reports-detail-item"><div class="reports-detail-label"><i class="bi bi-geo-alt"></i> Maintenance Location</div><div class="reports-detail-value" title="<?= Html::encode($maintenanceLocation) ?>"><?= Html::encode($maintenanceLocation) ?></div></div>
            </div>

            <div class="reports-details-dates">
                <div class="reports-detail-item"><div class="reports-detail-label"><i class="bi bi-calendar-event"></i> ETA</div><div class="reports-detail-value"><?= Html::encode($etaText) ?></div></div>
                <div class="reports-detail-item"><div class="reports-detail-label"><i class="bi bi-calendar-check"></i> ETD</div><div class="reports-detail-value"><?= Html::encode($etdText) ?></div></div>
            </div>

            <?= Html::textarea('request_details_display', $requestDetails, [
                'class' => 'reports-request-info',
                'readonly' => true,
                'aria-label' => 'Request information',
            ]) ?>
        </section>

        <!-- Reports table -->
        <section class="reports-table-card">
            <div class="reports-table-toolbar">
                <div>
                    <div class="reports-table-title-row">
                        <span class="reports-table-title-icon">
                            <i class="bi bi-folder2-open"></i>
                        </span>

                        <div>
                            <h2 class="reports-table-title">Maintenance Release & Repair Reports</h2>
                            <p class="reports-table-caption">
                                Review maintenance findings and open CRS airworthiness release records.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="reports-count-badge">
                    <i class="bi bi-clipboard-data"></i>
                    <?= Html::encode($totalReports) ?>
                    report<?= $totalReports > 1 ? 's' : '' ?>
                </div>
            </div>

            <!-- MAINTENANCE REPORT CARDS 2026: clearer separation of report, CRS and next action. -->
            <?php if (!empty($repairReports)): ?>
                <div class="reports-record-list">
                    <?php foreach ($repairReports as $report): ?>
                        <?php
                            $reportText = !empty($report->report)
                                ? $report->report
                                : 'No maintenance report description provided.';
                            $crsAttachment = $report->CRS_attachment ?? null;
                            $hasCrsAttachment = !empty($crsAttachment);
                            $crsDisplayName = $hasCrsAttachment
                                ? basename(str_replace('\\', '/', (string) $crsAttachment))
                                : '';
                            $hasRevisionRequest = !empty($report->AO_description);
                            $hasExistingMroQuote = !empty($report->mro_quote);
                            $reportDate = !empty($report->created_at) && strtotime($report->created_at)
                                ? date('d M Y H:i', strtotime($report->created_at))
                                : 'Date unavailable';
                        ?>

                        <article class="reports-record">
                            <header class="reports-record-head">
                                <div class="reports-record-identity">
                                    <span class="reports-record-number"><i class="bi bi-clipboard2-pulse"></i> Report #<?= Html::encode($report->repair_report_id) ?></span>
                                    <span class="reports-record-date"><i class="bi bi-calendar3"></i> <?= Html::encode($reportDate) ?></span>
                                </div>

                                <div class="reports-record-states">
                                    <span class="reports-record-state <?= $hasCrsAttachment ? '' : 'is-warning' ?>">
                                        <i class="bi <?= $hasCrsAttachment ? 'bi-shield-check' : 'bi-exclamation-circle' ?>"></i>
                                        <?= $hasCrsAttachment ? 'CRS attached' : 'CRS missing' ?>
                                    </span>
                                    <?php if ($hasRevisionRequest): ?>
                                        <span class="reports-record-state is-info"><i class="bi bi-chat-left-text"></i> AO revision requested</span>
                                    <?php endif; ?>
                                </div>
                            </header>

                            <div class="reports-record-body">
                                <section class="reports-record-panel">
                                    <div class="reports-record-panel-title"><i class="bi bi-chat-left-text"></i> Maintenance Report</div>
                                    <?= Html::textarea(
                                        'report_display_' . $report->repair_report_id,
                                        $reportText,
                                        [
                                            'class' => 'reports-report-textarea' . (empty($report->report) ? ' reports-report-empty' : ''),
                                            'readonly' => true,
                                            'aria-label' => 'Maintenance report ' . $report->repair_report_id,
                                        ]
                                    ) ?>
                                </section>

                                <section class="reports-record-panel">
                                    <div class="reports-record-panel-title"><i class="bi bi-file-earmark-check"></i> CRS / Release Document</div>
                                    <?php if ($hasCrsAttachment): ?>
                                        <div class="reports-attachment-card">
                                            <div class="reports-attachment-main">
                                                <span class="reports-attachment-icon"><i class="bi bi-file-earmark-check"></i></span>
                                                <div class="reports-attachment-copy">
                                                    <strong>Certificate of Release to Service</strong>
                                                    <span title="<?= Html::encode($crsDisplayName) ?>"><?= Html::encode($crsDisplayName) ?></span>
                                                    <small><i class="bi bi-shield-check"></i> Airworthiness record</small>
                                                </div>
                                            </div>
                                            <div class="reports-attachment-actions">
                                                <?= Html::a(
                                                    '<i class="bi bi-box-arrow-up-right"></i> <span>Open</span>',
                                                    '@web/uploads/' . $crsAttachment,
                                                    [
                                                        'class' => 'reports-btn reports-btn-open js-open-crs',
                                                        'target' => '_blank',
                                                        'rel' => 'noopener noreferrer',
                                                        'title' => 'Open CRS release document',
                                                        'aria-label' => 'Open CRS release document',
                                                        'data-pjax' => '0',
                                                        'data-no-loader' => '1',
                                                    ]
                                                ) ?>
                                                <!-- SHARED DOCUMENT ACTIONS: download uses the same authorized CRS resource. -->
                                                <?= Html::a(
                                                    '<i class="bi bi-download"></i> <span>Download</span>',
                                                    '@web/uploads/' . $crsAttachment,
                                                    [
                                                        'class' => 'reports-btn reports-btn-download',
                                                        'download' => $crsDisplayName,
                                                        'title' => 'Download CRS release document',
                                                        'aria-label' => 'Download CRS release document',
                                                        'data-pjax' => '0',
                                                        'data-no-loader' => '1',
                                                    ]
                                                ) ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="reports-attachment-missing">
                                            <span class="reports-attachment-missing-icon"><i class="bi bi-file-earmark-x"></i></span>
                                            <div class="reports-attachment-copy">
                                                <strong>No CRS document attached</strong>
                                                <span>Awaiting release documentation from the MRO.</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </section>

                                <section class="reports-record-panel reports-record-action-panel">
                                    <div class="reports-record-panel-title"><i class="bi bi-lightning-charge"></i> Next Action</div>
                                    <div class="reports-actions">
                                        <?php if ($hasRevisionRequest && !$hasExistingMroQuote): ?>
                                            <?= Html::a(
                                                '<i class="bi bi-plus-circle"></i> Provide New Quote',
                                                ['provide-new-quote', 'id' => UrlIdHelper::encode($report->repair_report_id)],
                                                [
                                                    'class' => 'reports-btn reports-btn-quote js-new-quote',
                                                    'title' => 'Provide new quote',
                                                    'aria-label' => 'Provide new quote',
                                                    'data-report-id' => $report->repair_report_id,
                                                    'data-request-id' => $requestId,
                                                ]
                                            ) ?>
                                        <?php else: ?>
                                            <span class="reports-action-state"><i class="bi bi-check2-circle"></i> No action required</span>
                                        <?php endif; ?>
                                    </div>
                                </section>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="reports-empty">
                    <div class="reports-empty-icon"><i class="bi bi-inbox"></i></div>
                    <strong>No maintenance reports available.</strong>
                    <div>No MRO maintenance report has been submitted for this aircraft maintenance request yet.</div>
                </div>
            <?php endif; ?>

            <div class="reports-footer">
                <!-- <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Request',
                    ['view', 'id' => $requestId],
                    [
                        'class' => 'reports-btn-back',
                        'title' => 'Back to request details',
                    ]
                ) ?> -->

                <?= Html::a(
                    '<i class="bi bi-list-ul"></i> Back to Maintenance Requests',
                    ['index'],
                    [
                        'class' => 'reports-btn-back',
                        'title' => 'Back to requests',
                    ]
                ) ?>
            </div>
        </section>

    </div>
</main>

<?php
$this->registerJs(<<<JS
function hideReportsGlobalLoader() {
    const loaderSelectors = [
        '#av-global-loader',
        '#global-loader',
        '#preloader',
        '.preloader',
        '.global-loader',
        '.page-loader'
    ];

    loaderSelectors.forEach(function (selector) {
        document.querySelectorAll(selector).forEach(function (loader) {
            loader.classList.add('hidden');
            loader.classList.remove('show', 'active', 'is-active', 'loading');
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';
            loader.style.pointerEvents = 'none';

            window.setTimeout(function () {
                loader.style.display = 'none';
            }, 250);
        });
    });

    document.body.classList.remove('loading', 'is-loading', 'loader-active');
    document.documentElement.classList.remove('loading', 'is-loading', 'loader-active');
}

document.addEventListener('click', function (event) {
    const crsButton = event.target.closest('.js-open-crs');

    if (!crsButton) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const crsUrl = crsButton.getAttribute('href');

    if (!crsUrl) {
        return;
    }

    hideReportsGlobalLoader();

    window.open(crsUrl, '_blank', 'noopener,noreferrer');

    window.setTimeout(hideReportsGlobalLoader, 100);
    window.setTimeout(hideReportsGlobalLoader, 500);
    window.setTimeout(hideReportsGlobalLoader, 1200);
});

document.addEventListener('click', function (event) {
    const quoteButton = event.target.closest('.js-new-quote');

    if (!quoteButton) {
        return;
    }

    event.preventDefault();

    const quoteUrl = quoteButton.getAttribute('href');
    const requestId = quoteButton.getAttribute('data-request-id') || 'N/A';

    Swal.fire({
        title: 'Provide new quote?',
        html: 'Request #' + requestId + ': You will be redirected to the new quote form.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-box-arrow-up-right"></i> Continue',
        cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review report',
        reverseButtons: true,
        focusCancel: true,
        buttonsStyling: false,
        // SHARED DETAIL CONFIRMATION: reuse the common compact dialog presentation.
        customClass: {
            popup: 'swal-reports-popup can-detail-swal',
            icon: 'swal-reports-icon',
            title: 'swal-reports-title',
            htmlContainer: 'swal-reports-text',
            actions: 'swal-reports-actions',
            confirmButton: 'swal-reports-confirm',
            cancelButton: 'swal-reports-cancel'
        }
    }).then(function (result) {
        if (result.isConfirmed) {
            window.location.href = quoteUrl;
        }
    });
});

window.addEventListener('focus', hideReportsGlobalLoader);
window.addEventListener('pageshow', hideReportsGlobalLoader);

document.addEventListener('visibilitychange', function () {
    if (!document.hidden) {
        hideReportsGlobalLoader();
    }
});
JS, View::POS_READY);
?>

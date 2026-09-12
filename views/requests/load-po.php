<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Currency;

$this->title = 'Upload Purchase Order';
$this->params['breadcrumbs'][] = ['label' => 'Requests', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'position' => \yii\web\View::POS_HEAD
]);

/* LOAD PO UX 2026: prepare display-only operational context; workflow data remains unchanged. */
$requestModel = $request ?? $aoRequest->request;
$selectedApplication = $application ?? $aoRequest->application;
$selectedMro = $mro ?? ($selectedApplication ? $selectedApplication->mro : null);
$selectedAircraft = $aircraft ?? ($requestModel ? $requestModel->getAircraft()->one() : null);
$currencyModel = $selectedApplication
    ? Currency::findOne(['code' => $selectedApplication->currency])
    : null;
$currencyLabel = $currencyModel
    ? $currencyModel->symbol . ' ' . $currencyModel->code
    : ($selectedApplication->currency ?? 'N/A');
$aircraftLabel = $selectedAircraft
    ? trim(($selectedAircraft->manufacturer ?: '') . ' ' . ($selectedAircraft->model ?: ''))
    : 'Aircraft unavailable';
$etaLabel = $requestModel && $requestModel->eta ? date('d M Y H:i', strtotime($requestModel->eta)) : 'N/A';
$etdLabel = $requestModel && $requestModel->etd ? date('d M Y H:i', strtotime($requestModel->etd)) : 'N/A';
$quoteLabel = $selectedApplication
    ? number_format((float) $selectedApplication->price, 2, '.', ',') . ' ' . $currencyLabel
    : 'N/A';
$hasMroCertificate = !empty($mroCertificate);
$hasAircraftCertificate = !empty($mroAircraftCertificate);
$hasInsurance = !empty($mroInsurance);

$this->registerCss(<<<CSS
:root {
    --deep-blue: #0D3261;
    --deep-blue-dark: #082546;
    --sky: #00B2FF;
    --gold: #EED811;
    --soft-border: #D8E4EE;
    --soft-bg: #F4F7FC;
    --text-main: #0F172A;
    --text-muted: #64748B;
    --danger: #EF4444;
    --success: #22C55E;
    --warning: #F59E0B;
    --white: #FFFFFF;
}

/* Page wrapper */
.load-po-page {
    min-height: calc(100vh - 70px);
    padding: 22px;
    margin: 0 -12px;
    background:
        radial-gradient(circle at 15% 18%, rgba(0,178,255,.13) 0%, transparent 34%),
        radial-gradient(circle at 88% 12%, rgba(238,216,17,.12) 0%, transparent 30%),
        linear-gradient(145deg, #EFF5FF 0%, #FFFFFF 45%, #F8FBFF 100%);
}

/* Main card */
.load-po-card {
    background: rgba(255,255,255,.98);
    border-radius: 8px;
    border: 1px solid var(--soft-border);
    box-shadow: 0 24px 60px rgba(15,23,42,.14);
    overflow: hidden;
    position: relative;
}

.load-po-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 6px;
    background: linear-gradient(135deg, var(--deep-blue), var(--sky), var(--gold));
    background-size: 220% 220%;
    animation: headerGradient 8s ease infinite;
}

@keyframes headerGradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Header */
.load-po-header {
    padding: 26px 30px 20px;
    background: linear-gradient(135deg, #FFFFFF, #F3F8FF);
    border-bottom: 1px solid rgba(226,232,240,.9);
}

.load-po-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
}

.load-po-title-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.load-po-icon {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    background: linear-gradient(135deg, #E7F4FF, #F7FBFF);
    color: var(--deep-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 14px 28px rgba(13,50,97,.18);
    position: relative;
    overflow: hidden;
}

.load-po-icon::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,.7) 50%, transparent 70%);
    transform: translateX(-100%);
    animation: iconShine 3.4s infinite linear;
}

@keyframes iconShine {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.load-po-icon i {
    font-size: 29px;
    position: relative;
    z-index: 1;
}

.load-po-title {
    margin: 0;
    font-size: 27px;
    font-weight: 900;
    color: var(--text-main);
    letter-spacing: -.03em;
}

.load-po-subtitle {
    margin-top: 5px;
    color: var(--text-muted);
    font-size: 13px;
    line-height: 1.55;
}

.po-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border-radius: 8px;
    background: #DBEAFE;
    color: #1D4ED8;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
    border: 1px solid rgba(29,78,216,.12);
}

/* Body */
.load-po-body {
    padding: 20px 24px 28px;
}

/* LOAD PO UX 2026: compact decision context before the irreversible PO selection. */
.po-context-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 18px;
}

.po-context-item {
    min-width: 0;
    padding: 12px 13px;
    border: 1px solid var(--soft-border);
    border-radius: 8px;
    background: linear-gradient(145deg, #FFFFFF, #F8FBFF);
}

.po-context-label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 5px;
    color: var(--text-muted);
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.po-context-value {
    color: var(--text-main);
    font-size: 13px;
    font-weight: 850;
    line-height: 1.35;
    overflow-wrap: anywhere;
}

.po-context-item.is-highlighted {
    border-color: #BFDBFE;
    background: #EFF6FF;
}

/* Alerts */
.alert {
    border: none;
    border-radius: 8px;
    padding: 13px 16px;
    font-weight: 750;
    box-shadow: 0 8px 18px rgba(15,23,42,.08);
    margin-bottom: 16px;
}

.alert-success {
    background: #DCFCE7;
    color: #166534;
}

.alert-danger {
    background: #FEE2E2;
    color: #991B1B;
}

/* Progress bar */
.progress-bar-container {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 12px;
    margin-bottom: 18px;
    background: #F8FBFF;
    border: 1px solid var(--soft-border);
    border-radius: 8px;
    scrollbar-width: thin;
}

.progress-bar-container::-webkit-scrollbar {
    height: 7px;
}

.progress-bar-container::-webkit-scrollbar-track {
    background: #EAF1FB;
    border-radius: 8px;
}

.progress-bar-container::-webkit-scrollbar-thumb {
    background: #CBD5E1;
    border-radius: 8px;
}

.progress-bar-step {
    position: relative;
    min-width: max-content;
    padding: 9px 14px;
    border-radius: 8px;
    background: #E5E7EB;
    color: #475569;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.progress-bar-step::after {
    content: "\\F285";
    font-family: "bootstrap-icons";
    margin-left: 3px;
    font-size: 10px;
    color: currentColor;
}

.progress-bar-step:last-child::after {
    content: "";
    margin-left: 0;
}

.step-realized {
    background: #DCFCE7;
    color: #166534;
}

.step-active {
    background: #DBEAFE;
    color: #1D4ED8;
    box-shadow: 0 8px 18px rgba(37,99,235,.18);
}

/* Layout */
.po-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) minmax(320px, .7fr);
    gap: 18px;
    align-items: start;
}

.po-form-section,
.po-side-card {
    background: #FFFFFF;
    border: 1px solid var(--soft-border);
    border-radius: 8px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(15,23,42,.07);
}

.po-form-section {
    margin-bottom: 16px;
}

.po-section-title {
    display: flex;
    align-items: center;
    gap: 9px;
    margin: 0 0 14px;
    color: var(--text-main);
    font-size: 15px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .045em;
}

.po-section-subtitle {
    margin: -6px 0 14px;
    color: var(--text-muted);
    font-size: 13px;
    line-height: 1.55;
}

/* Labels */
.load-po-page .control-label {
    color: var(--text-main);
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 7px;
}

.load-po-page .help-block {
    margin-top: 7px;
    color: var(--danger);
    font-size: 12px;
    font-weight: 800;
}

/* Custom file input */
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
    min-height: 48px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    display: flex;
    align-items: center;
    overflow: hidden;
    transition: .2s ease;
}

.custom-file-display.is-valid {
    border-color: var(--success);
    box-shadow: 0 0 0 4px rgba(34,197,94,.10);
}

.custom-file-display.is-invalid {
    border-color: var(--danger);
    box-shadow: 0 0 0 4px rgba(239,68,68,.10);
}

.custom-file-button {
    min-height: 48px;
    padding: 0 17px;
    background: #E2E8F0;
    color: var(--text-main);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 900;
    border-right: 1px solid #CBD5E1;
    cursor: pointer;
    white-space: nowrap;
    margin: 0;
}

.custom-file-button:hover {
    background: #CBD5E1;
}

.custom-file-name {
    padding: 0 14px;
    color: var(--text-main);
    font-weight: 750;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* LOAD PO DRAG DROP 2026: accessible drop surface layered over the existing Yii file input. */
.po-drop-zone.custom-file-display {
    min-height: 158px;
    padding: 22px;
    border: 2px dashed #93C5FD;
    border-radius: 17px;
    background: linear-gradient(145deg, #F8FBFF, #EFF6FF);
    flex-direction: column;
    justify-content: center;
    gap: 7px;
    text-align: center;
    overflow: visible;
    cursor: pointer;
}

.po-drop-zone:hover,
.po-drop-zone:focus-visible,
.po-drop-zone.is-dragover {
    border-color: #2563EB;
    background: #EAF3FF;
    box-shadow: 0 0 0 4px rgba(37,99,235,.10);
    outline: none;
}

.po-drop-zone.is-dragover {
    transform: translateY(-2px);
}

.po-drop-zone .custom-file-button {
    width: 46px;
    min-height: 46px;
    padding: 0;
    border: 1px solid #BFDBFE;
    border-radius: 13px;
    background: #FFFFFF;
    color: #1D4ED8;
    box-shadow: 0 8px 18px rgba(37,99,235,.12);
}

.po-drop-zone .custom-file-button i {
    font-size: 21px;
}

.drop-zone-title {
    color: var(--text-main);
    font-size: 14px;
    font-weight: 900;
}

.drop-zone-text {
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 700;
}

.po-drop-zone .custom-file-name {
    max-width: 100%;
    margin-top: 3px;
    padding: 5px 10px;
    border-radius: 999px;
    background: #FFFFFF;
    color: #334155;
    font-size: 12px;
    box-shadow: 0 3px 10px rgba(15,23,42,.06);
}

.po-drop-zone.is-valid .custom-file-name {
    background: #DCFCE7;
    color: #166534;
}

.po-drop-zone.is-invalid .custom-file-name {
    background: #FEE2E2;
    color: #991B1B;
}

.file-hint {
    margin-top: 8px;
    font-size: 12px;
    color: var(--text-muted);
    font-weight: 650;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Certificate check */
.certificate-check-card {
    background: linear-gradient(135deg, #F8FBFF, #FFFFFF);
    border: 1px solid var(--soft-border);
    border-radius: 8px;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
}

.certificate-check-title {
    display: flex;
    align-items: center;
    gap: 9px;
    color: var(--text-main);
    font-weight: 900;
    font-size: 14px;
}

.certificate-check-text {
    margin-top: 4px;
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 650;
}

.check-certificate-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 9px;
    padding: 10px 16px;
    background: #E7F4FF;
    color: var(--deep-blue) !important;
    border: 1px solid #BFDBFE;
    text-decoration: none;
    font-weight: 900;
    transition: .2s ease;
}

.check-certificate-btn:hover {
    background: var(--deep-blue);
    color: #FFFFFF !important;
    text-decoration: none;
    transform: translateY(-2px);
}

/* LOAD PO UX 2026: high-visibility, keyboard-accessible confirmation checkbox. */
.acknowledgment-box {
    background: #FFFBEB;
    border: 2px solid #FACC15;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 8px 20px rgba(245,158,11,.10);
}

.confirmation-checkbox {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}

.confirmation-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin: 0;
    cursor: pointer;
}

.confirmation-visual {
    width: 26px;
    height: 26px;
    flex: 0 0 26px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #B45309;
    border-radius: 7px;
    background: #FFFFFF;
    color: #FFFFFF;
    transition: .18s ease;
}

.confirmation-visual i {
    font-size: 17px;
    font-weight: 900;
    opacity: 0;
    transform: scale(.65);
    transition: .18s ease;
}

.confirmation-checkbox:focus-visible + .confirmation-label .confirmation-visual {
    outline: 3px solid rgba(37,99,235,.28);
    outline-offset: 3px;
}

.confirmation-checkbox:checked + .confirmation-label .confirmation-visual {
    border-color: #166534;
    background: #16A34A;
    box-shadow: 0 0 0 4px rgba(34,197,94,.14);
}

.confirmation-checkbox:checked + .confirmation-label .confirmation-visual i {
    opacity: 1;
    transform: scale(1);
}

.acknowledgment-text {
    color: #78350F;
    font-size: 13px;
    line-height: 1.5;
}

.acknowledgment-text strong {
    display: block;
    color: #713F12;
    font-size: 14px;
    font-weight: 900;
}

.acknowledgment-text small {
    display: block;
    margin-top: 3px;
    color: #92400E;
    font-size: 12px;
    font-weight: 700;
}

.confirmation-details {
    margin: 12px 0 0 38px;
    padding-top: 10px;
    border-top: 1px solid rgba(180,83,9,.18);
    color: #78350F;
    font-size: 12px;
    line-height: 1.55;
}

.confirmation-details summary {
    width: max-content;
    color: #92400E;
    font-weight: 850;
    cursor: pointer;
}

.confirmation-details p {
    margin: 8px 0 0;
}

/* Side checklist */
.po-side-card {
    position: sticky;
    top: 16px;
}

.side-title {
    display: flex;
    align-items: center;
    gap: 9px;
    margin: 0 0 12px;
    font-size: 15px;
    font-weight: 900;
    color: var(--text-main);
    text-transform: uppercase;
    letter-spacing: .045em;
}

.side-check-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.side-check-list li {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    padding: 11px 0;
    border-top: 1px solid #E2E8F0;
    color: #334155;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.5;
}

.side-check-list li:first-child {
    border-top: none;
}

.side-check-list i {
    color: var(--success);
    margin-top: 2px;
}

/* LOAD PO UX 2026: availability indicators avoid presenting unchecked files as verified. */
.side-check-list li.is-missing i {
    color: var(--danger);
}

.document-state {
    margin-left: auto;
    padding: 3px 8px;
    border-radius: 8px;
    background: #DCFCE7;
    color: #166534;
    font-size: 10px;
    font-weight: 900;
    white-space: nowrap;
}

.is-missing .document-state {
    background: #FEE2E2;
    color: #991B1B;
}

.side-warning {
    margin-top: 14px;
    border-radius: 8px;
    background: #FEF3C7;
    color: #92400E;
    padding: 12px 13px;
    font-size: 12px;
    font-weight: 800;
    line-height: 1.55;
    display: flex;
    gap: 8px;
}

/* Actions */
.form-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.upload-btn,
.back-btn {
    border-radius: 9px;
    padding: 12px 20px;
    font-weight: 900;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    transition: .2s ease;
}

.upload-btn {
    min-width: 190px;
    background: #2563EB;
    color: #FFFFFF !important;
    box-shadow: 0 12px 24px rgba(37,99,235,.26);
}

.upload-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    color: #FFFFFF !important;
    box-shadow: 0 16px 32px rgba(37,99,235,.34);
}

.upload-btn:disabled {
    opacity: .55;
    cursor: not-allowed;
    filter: grayscale(.2);
    transform: none !important;
    box-shadow: none !important;
}

.back-btn {
    background: var(--deep-blue);
    color: #FFFFFF !important;
    box-shadow: 0 10px 22px rgba(13,50,97,.22);
}

.back-btn:hover {
    background: var(--deep-blue-dark);
    color: #FFFFFF !important;
    text-decoration: none;
    transform: translateY(-2px);
}

/* Modal */
.certificate-modal .modal-dialog {
    max-width: 900px;
}

.certificate-modal .modal-content {
    border: none;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 30px 75px rgba(15,23,42,.30);
}

.certificate-modal .modal-header {
    background: linear-gradient(135deg, #0D3261, #1D4ED8);
    color: #FFFFFF;
    border-bottom: none;
    padding: 19px 23px;
}

.certificate-modal .modal-title {
    display: flex;
    align-items: center;
    gap: 9px;
    font-weight: 900;
}

.modal-close-btn {
    background: rgba(255,255,255,.15);
    border: none;
    color: #FFFFFF;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: .2s ease;
}

.modal-close-btn:hover {
    background: rgba(255,255,255,.25);
}

.certificate-modal .modal-body {
    background: #F8FBFF;
    padding: 18px;
    max-height: 70vh;
    overflow-y: auto;
}

.cert-info-card {
    background: #FFFFFF;
    border: 1px solid var(--soft-border);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 14px;
    box-shadow: 0 8px 18px rgba(15,23,42,.06);
}

.cert-info-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px;
    color: var(--text-main);
    font-size: 15px;
    font-weight: 900;
}

.cert-row {
    display: grid;
    grid-template-columns: 210px minmax(0, 1fr);
    gap: 12px;
    padding: 10px 0;
    border-top: 1px solid #E2E8F0;
}

.cert-row:first-of-type {
    border-top: none;
}

.cert-label {
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.cert-value {
    color: var(--text-main);
    font-size: 13px;
    font-weight: 750;
    word-break: break-word;
}

.cert-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 8px;
    background: #E7F4FF;
    color: var(--deep-blue) !important;
    border: 1px solid #BFDBFE;
    text-decoration: none;
    font-weight: 900;
    font-size: 12px;
}

.cert-link:hover {
    background: var(--deep-blue);
    color: #FFFFFF !important;
    text-decoration: none;
}

.cert-empty {
    background: #FEE2E2;
    color: #991B1B;
    border-radius: 8px;
    padding: 12px 14px;
    font-weight: 900;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.insurance-separator {
    border-top: 1px dashed #CBD5E1;
    margin: 12px 0;
}

.certificate-modal .modal-footer {
    background: #FFFFFF;
    border-top: 1px solid var(--soft-border);
    padding: 14px 18px;
}

.close-footer-btn {
    border-radius: 8px;
    padding: 9px 18px;
    background: #E2E8F0;
    color: #334155;
    border: none;
    font-weight: 900;
}

.close-footer-btn:hover {
    background: #CBD5E1;
}

/* SweetAlert */
.swal2-popup.custom-po-popup {
    width: 390px !important;
    max-width: 92vw !important;
    border-radius: 8px !important;
    padding: 18px 20px !important;
    box-shadow: 0 18px 45px rgba(15,23,42,.24) !important;
}

.swal2-popup.custom-po-popup .swal2-icon {
    width: 54px !important;
    height: 54px !important;
    margin: 8px auto 12px !important;
}

.swal2-title.custom-po-title {
    color: #0F172A !important;
    font-size: 20px !important;
    font-weight: 900 !important;
}

.swal2-html-container.custom-po-message {
    color: #64748B !important;
    font-size: 13px !important;
    line-height: 1.45 !important;
}

.swal-po-confirm,
.swal-po-cancel {
    min-width: 125px !important;
    min-height: 40px !important;
    border-radius: 8px !important;
    padding: 9px 16px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    font-weight: 900 !important;
    border: none !important;
}

/* PO UPLOAD CONFIRMATION 2026: keep both actions visually separated and icons aligned. */
.swal2-popup.custom-po-popup .swal2-actions {
    gap: 16px !important;
    margin-top: 16px !important;
}
.swal-po-confirm i,
.swal-po-cancel i {
    flex: 0 0 auto;
    font-size: 14px;
    line-height: 1;
}

.swal-po-confirm {
    background: #2563EB !important;
    color: #FFFFFF !important;
}

.swal-po-cancel {
    background: #E2E8F0 !important;
    color: #334155 !important;
}

@media (max-width: 992px) {
    .po-context-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .po-grid {
        grid-template-columns: 1fr;
    }

    .po-side-card {
        position: static;
    }
}

@media (max-width: 768px) {
    .load-po-page {
        padding: 12px;
    }

    .load-po-header {
        padding: 20px 16px;
    }

    .load-po-body {
        padding: 14px;
    }

    .load-po-title-left {
        align-items: flex-start;
    }

    .load-po-title {
        font-size: 22px;
    }

    .po-context-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .progress-bar-container {
        padding: 10px;
    }

    .custom-file-display {
        flex-direction: column;
        align-items: stretch;
    }

    .custom-file-button {
        border-right: none;
        border-bottom: 1px solid #CBD5E1;
    }

    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .upload-btn,
    .back-btn {
        width: 100%;
    }

    .cert-row {
        grid-template-columns: 1fr;
        gap: 5px;
    }

    .confirmation-details {
        margin-left: 0;
    }
}

@media (max-width: 430px) {
    .po-context-grid {
        grid-template-columns: 1fr;
    }
}
CSS);
?>

<!-- SHARED FORM SYSTEM: presentation only; PO validation rules remain unchanged. -->
<main class="dash-content load-po-page can-form-page">
    <div class="container-fluid">
        <div class="load-po-card">

            <!-- Page header -->
            <div class="load-po-header">
                <div class="load-po-title-row">
                    <div class="load-po-title-left">
                        <div class="load-po-icon">
                            <i class="bi bi-file-earmark-arrow-up"></i>
                        </div>

                        <div>
                            <h1 class="load-po-title">
                                <?= Html::encode($this->title) ?>
                            </h1>
                            <div class="load-po-subtitle">
                                Review the selected MRO, verify compliance documents and upload the final PDF.
                            </div>
                        </div>
                    </div>

                    <div class="po-status-pill">
                        <i class="bi bi-file-earmark-check"></i>
                        Step 3 · PO Upload
                    </div>
                </div>
            </div>

            <!-- Page body -->
            <div class="load-po-body">

                <?php if (Yii::$app->session->hasFlash('success')): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i>
                        <?= Yii::$app->session->getFlash('success') ?>
                    </div>
                <?php endif; ?>

                <?php if (Yii::$app->session->hasFlash('error')): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?= Yii::$app->session->getFlash('error') ?>
                    </div>
                <?php endif; ?>

                <!-- LOAD PO UX 2026: read-only summary of the selected request and MRO quotation. -->
                <section class="po-context-grid" aria-label="Selected request and quotation summary">
                    <div class="po-context-item is-highlighted">
                        <div class="po-context-label"><i class="bi bi-hash"></i> Request</div>
                        <div class="po-context-value">#<?= Html::encode($requestModel->request_id ?? 'N/A') ?></div>
                    </div>
                    <div class="po-context-item">
                        <div class="po-context-label"><i class="bi bi-building"></i> Selected MRO</div>
                        <div class="po-context-value"><?= Html::encode($selectedMro->company_name ?? $selectedMro->username ?? 'N/A') ?></div>
                    </div>
                    <div class="po-context-item">
                        <div class="po-context-label"><i class="bi bi-airplane"></i> Aircraft</div>
                        <div class="po-context-value"><?= Html::encode($aircraftLabel ?: 'N/A') ?></div>
                    </div>
                    <div class="po-context-item">
                        <div class="po-context-label"><i class="bi bi-card-text"></i> Registration</div>
                        <div class="po-context-value"><?= Html::encode($selectedAircraft->registration_number ?? 'N/A') ?></div>
                    </div>
                    <div class="po-context-item">
                        <div class="po-context-label"><i class="bi bi-calendar-range"></i> ETA / ETD</div>
                        <div class="po-context-value"><?= Html::encode($etaLabel) ?><br><?= Html::encode($etdLabel) ?></div>
                    </div>
                    <div class="po-context-item is-highlighted">
                        <div class="po-context-label"><i class="bi bi-cash-stack"></i> Selected Quote</div>
                        <div class="po-context-value"><?= Html::encode($quoteLabel) ?></div>
                    </div>
                </section>

                <!-- Multi-step progress bar -->
                <div class="progress-bar-container">
                    <span class="progress-bar-step step-realized">Create a Request</span>
                    <span class="progress-bar-step step-realized">MRO Quote</span>
                    <span class="progress-bar-step step-active">PO Loaded</span>
                    <span class="progress-bar-step">PO Accepted By MRO</span>
                    <span class="progress-bar-step">Work Started</span>
                    <span class="progress-bar-step">MRO Report</span>
                    <span class="progress-bar-step">AO Feedback</span>
                    <span class="progress-bar-step">Request Closed</span>
                </div>

                <div class="po-grid">

                    <!-- Main form column -->
                    <div>
                        <div class="ao-request-form">
                            <?php $form = ActiveForm::begin([
                                'id' => 'load-po-form',
                                'options' => [
                                    'enctype' => 'multipart/form-data'
                                ],
                            ]); ?>

                            <!-- PO file section -->
                            <div class="po-form-section">
                                <h2 class="po-section-title">
                                    <i class="bi bi-filetype-pdf"></i>
                                    Purchase Order File
                                </h2>

                                <p class="po-section-subtitle">
                                    Select the final PO document in PDF format before continuing.
                                </p>

                                <label class="control-label">
                                    PO File <span class="text-danger">*</span>
                                </label>

                                <div class="custom-file-wrapper">
                                    <?= $form->field($aoRequest, 'po', [
                                        'template' => "{input}\n{error}",
                                        'options' => ['class' => 'custom-file-field'],
                                    ])->fileInput([
                                        'accept' => 'application/pdf,.pdf',
                                        'id' => 'po-input',
                                        'class' => 'custom-file-native',
                                    ]) ?>

                                    <!-- LOAD PO DRAG DROP 2026: click, keyboard and drag/drop use the same native input. -->
                                    <div class="custom-file-display po-drop-zone" id="po-file-display"
                                         role="button" tabindex="0" aria-labelledby="po-drop-title po-file-name">
                                        <label for="po-input" class="custom-file-button">
                                            <i class="bi bi-upload"></i>
                                        </label>
                                        <div class="drop-zone-title" id="po-drop-title">Drop the approved PO here</div>
                                        <div class="drop-zone-text">or click to select a PDF</div>
                                        <span id="po-file-name" class="custom-file-name">
                                            No file chosen
                                        </span>
                                    </div>

                                    <div class="file-hint">
                                        <i class="bi bi-info-circle"></i>
                                        PDF only. Verify the filename before submission.
                                    </div>
                                </div>

                                <?= $form->field($aoRequest, 'request_id')->hiddenInput([
                                    'value' => $aoRequest->request_id
                                ])->label(false) ?>
                            </div>

                            <!-- Certificate check section -->
                            <div class="po-form-section">
                                <h2 class="po-section-title">
                                    <i class="bi bi-shield-check"></i>
                                    Certificate Verification
                                </h2>

                                <div class="certificate-check-card">
                                    <div>
                                        <div class="certificate-check-title">
                                            <i class="bi bi-patch-check"></i>
                                            Review MRO compliance documents
                                        </div>

                                        <div class="certificate-check-text">
                                            NAA certificate, aircraft model approval and insurance.
                                        </div>
                                    </div>

                                    <?= Html::a(
                                        '<i class="bi bi-search"></i> Review Documents',
                                        '#',
                                        [
                                            'class' => 'check-certificate-btn',
                                            'id' => 'check-certificate-link'
                                        ]
                                    ) ?>
                                </div>
                            </div>

                            <!-- Confirmation section -->
                            <div class="po-form-section">
                                <h2 class="po-section-title">
                                    <i class="bi bi-exclamation-circle"></i>
                                    Confirmation
                                </h2>

                                <!-- LOAD PO UX 2026: visible custom checkbox; native input remains accessible and drives validation. -->
                                <div class="acknowledgment-box">
                                    <input type="checkbox" class="confirmation-checkbox" id="acknowledgment-checkbox" aria-describedby="confirmation-help">
                                    <label class="confirmation-label" for="acknowledgment-checkbox">
                                        <span class="confirmation-visual" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                                        <span class="acknowledgment-text">
                                            <strong>I confirm the required compliance checks are complete.</strong>
                                            <small id="confirmation-help">Required before uploading the Purchase Order.</small>
                                        </span>
                                    </label>
                                    <details class="confirmation-details">
                                        <summary>Read full declaration</summary>
                                        <p>By proceeding, I confirm that I have conducted all necessary checks to ensure that the maintenance organisation’s certificates and approvals are appropriate for the scope of work to be undertaken. While this platform may carry out checks from time to time, it does not guarantee the accuracy or applicability of such documentation.</p>
                                    </details>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="form-actions">
                                <?= Html::submitButton(
                                    '<i class="bi bi-cloud-arrow-up-fill"></i> Upload PO',
                                    [
                                        'class' => 'upload-btn',
                                        'id' => 'submit-button',
                                        'disabled' => true,
                                    ]
                                ) ?>

                                <a href="javascript:void(0);" class="back-btn" onclick="window.history.back();">
                                    <i class="bi bi-arrow-left"></i>
                                    Back
                                </a>
                            </div>

                            <?php ActiveForm::end(); ?>
                        </div>
                    </div>

                    <!-- Side checklist column -->
                    <aside class="po-side-card">
                        <h3 class="side-title">
                            <i class="bi bi-list-check"></i>
                            Compliance Documents
                        </h3>

                        <ul class="side-check-list">
                            <li class="<?= $hasMroCertificate ? '' : 'is-missing' ?>">
                                <i class="bi bi-<?= $hasMroCertificate ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
                                Required NAA certificate
                                <span class="document-state"><?= $hasMroCertificate ? 'Available' : 'Missing' ?></span>
                            </li>
                            <li class="<?= $hasAircraftCertificate ? '' : 'is-missing' ?>">
                                <i class="bi bi-<?= $hasAircraftCertificate ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
                                Aircraft model approval
                                <span class="document-state"><?= $hasAircraftCertificate ? 'Available' : 'Missing' ?></span>
                            </li>
                            <li class="<?= $hasInsurance ? '' : 'is-missing' ?>">
                                <i class="bi bi-<?= $hasInsurance ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
                                Insurance documents
                                <span class="document-state"><?= $hasInsurance ? 'Available' : 'Missing' ?></span>
                            </li>
                        </ul>

                        <div class="side-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Uploading the PO confirms selection of this MRO quotation.
                        </div>
                    </aside>

                </div>

            </div>
        </div>
    </div>
</main>

<!-- Certificate Modal -->
<div class="modal fade certificate-modal" id="certificateModal" tabindex="-1" role="dialog" aria-labelledby="certificateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="certificateModalLabel">
                    <i class="bi bi-shield-check"></i>
                    MRO Certificate Information
                </h5>

                <button type="button" class="modal-close-btn" id="modal-close-button" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-body">

                <!-- NAA Certificate -->
                <div class="cert-info-card">
                    <h4 class="cert-info-title">
                        <i class="bi bi-award"></i>
                        NAA Certificate
                    </h4>

                    <?php if ($mroCertificate): ?>
                        <div class="cert-row">
                            <div class="cert-label">Certificate NAA</div>
                            <div class="cert-value">
                                <?= Html::encode($mroCertificate->type) ?>
                            </div>
                        </div>

                        <div class="cert-row">
                            <div class="cert-label">Certificate File</div>
                            <div class="cert-value">
                                <?= $mroCertificate->certificate
                                    ? Html::a(
                                        '<i class="bi bi-box-arrow-up-right"></i> View Certificate',
                                        Yii::getAlias('@web') . '/' . $mroCertificate->certificate,
                                        [
                                            'class' => 'cert-link',
                                            'target' => '_blank'
                                        ]
                                    )
                                    : 'N/A'
                                ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="cert-empty">
                            <i class="bi bi-exclamation-triangle"></i>
                            NAA certificate not found.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Aircraft Model Certificate -->
                <div class="cert-info-card">
                    <h4 class="cert-info-title">
                        <i class="bi bi-airplane-engines"></i>
                        Aircraft Model Certificate
                    </h4>

                    <?php if ($mroAircraftCertificate): ?>
                        <?php $aircraftModel = $mroAircraftCertificate->getAircraftModel()->one(); ?>

                        <div class="cert-row">
                            <div class="cert-label">Manufacturer</div>
                            <div class="cert-value">
                                <?= $aircraftModel ? Html::encode($aircraftModel->manufacturer) : 'N/A' ?>
                            </div>
                        </div>

                        <div class="cert-row">
                            <div class="cert-label">Model</div>
                            <div class="cert-value">
                                <?= $aircraftModel ? Html::encode($aircraftModel->model) : 'N/A' ?>
                            </div>
                        </div>

                        <div class="cert-row">
                            <div class="cert-label">Certificate File</div>
                            <div class="cert-value">
                                <?= $mroAircraftCertificate->certificate
                                    ? Html::a(
                                        '<i class="bi bi-box-arrow-up-right"></i> View Certificate',
                                        Yii::getAlias('@web') . '/' . $mroAircraftCertificate->certificate,
                                        [
                                            'class' => 'cert-link',
                                            'target' => '_blank'
                                        ]
                                    )
                                    : 'N/A'
                                ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="cert-empty">
                            <i class="bi bi-exclamation-triangle"></i>
                            Aircraft model certificate not found.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Insurance Documents -->
                <div class="cert-info-card">
                    <h4 class="cert-info-title">
                        <i class="bi bi-file-earmark-lock"></i>
                        Insurance Documents
                    </h4>

                    <?php if (!empty($mroInsurance)): ?>
                        <?php foreach ($mroInsurance as $index => $insurance): ?>
                            <?php if ($index > 0): ?>
                                <div class="insurance-separator"></div>
                            <?php endif; ?>

                            <div class="cert-row">
                                <div class="cert-label">File Name</div>
                                <div class="cert-value">
                                    <?= Html::encode($insurance->file_name) ?>
                                </div>
                            </div>

                            <div class="cert-row">
                                <div class="cert-label">File Type</div>
                                <div class="cert-value">
                                    <?= Html::encode($insurance->file_type) ?>
                                </div>
                            </div>

                            <div class="cert-row">
                                <div class="cert-label">File Size</div>
                                <div class="cert-value">
                                    <?= Html::encode(round($insurance->file_size / 1024, 2)) ?> KB
                                </div>
                            </div>

                            <div class="cert-row">
                                <div class="cert-label">File</div>
                                <div class="cert-value">
                                    <?= Html::a(
                                        '<i class="bi bi-box-arrow-up-right"></i> View Document',
                                        Yii::getAlias('@web') . '/' . $insurance->file_path,
                                        [
                                            'class' => 'cert-link',
                                            'target' => '_blank'
                                        ]
                                    ) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="cert-empty">
                            <i class="bi bi-exclamation-triangle"></i>
                            No insurance documents found.
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="close-footer-btn" id="modal-close-footer-button">
                    <i class="bi bi-x-circle"></i>
                    Close
                </button>
            </div>

        </div>
    </div>
</div>

<?php
$certificateModalValue = $certificateModal ? '1' : '0';

$js = <<<JS
$(document).ready(function() {
    var poInput = $('#po-input');
    var poFileName = $('#po-file-name');
    var poFileDisplay = $('#po-file-display');
    // LOAD PO DRAG DROP 2026: the drop zone feeds the existing native file input.
    var poDropZone = $('#po-file-display');
    var submitButton = $('#submit-button');
    var acknowledgmentCheckbox = $('#acknowledgment-checkbox');

    function showBootstrapModal(selector) {
        var modalElement = document.querySelector(selector);

        if (typeof bootstrap !== 'undefined' && modalElement) {
            var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
            modalInstance.show();
            return;
        }

        if ($(selector).modal) {
            $(selector).modal('show');
        }
    }

    function hideBootstrapModal(selector) {
        var modalElement = document.querySelector(selector);

        if (typeof bootstrap !== 'undefined' && modalElement) {
            var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
            modalInstance.hide();
            return;
        }

        if ($(selector).modal) {
            $(selector).modal('hide');
        }
    }

    function validatePoForm() {
        var hasFile = poInput[0] && poInput[0].files && poInput[0].files.length > 0;
        var isChecked = acknowledgmentCheckbox.is(':checked');
        var isPdf = false;

        if (hasFile) {
            var file = poInput[0].files[0];
            var fileName = file.name.toLowerCase();
            isPdf = fileName.endsWith('.pdf') || file.type === 'application/pdf';

            poFileName.text(file.name);

            if (isPdf) {
                poFileDisplay.removeClass('is-invalid').addClass('is-valid');
            } else {
                poFileDisplay.removeClass('is-valid').addClass('is-invalid');
            }
        } else {
            poFileName.text('No file chosen');
            poFileDisplay.removeClass('is-valid is-invalid');
        }

        submitButton.prop('disabled', !(hasFile && isPdf && isChecked));
    }

    function showWarning(title, message) {
        Swal.fire({
            title: title,
            html: message,
            icon: 'warning',
            confirmButtonText: '<i class="bi bi-check-circle"></i> Got it',
            buttonsStyling: false,
            customClass: {
                // SHARED FORM CONFIRMATION: retain PO-specific button classes and messages.
                popup: 'custom-po-popup can-form-swal',
                title: 'custom-po-title',
                htmlContainer: 'custom-po-message',
                confirmButton: 'swal-po-confirm'
            }
        });
    }

    poInput.on('change', function() {
        validatePoForm();
    });

    poDropZone.on('click', function(e) {
        if (!$(e.target).closest('.custom-file-button').length) {
            poInput.trigger('click');
        }
    });

    poDropZone.on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            poInput.trigger('click');
        }
    });

    poDropZone.on('dragenter dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        poDropZone.addClass('is-dragover');
    });

    poDropZone.on('dragleave dragend', function(e) {
        e.preventDefault();
        e.stopPropagation();
        poDropZone.removeClass('is-dragover');
    });

    poDropZone.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        poDropZone.removeClass('is-dragover');

        var droppedFiles = e.originalEvent.dataTransfer.files;
        if (droppedFiles && droppedFiles.length) {
            if (droppedFiles.length > 1) {
                showWarning('One PO file only', 'Please drop a single PDF Purchase Order.');
                return;
            }

            poInput[0].files = droppedFiles;
            poInput.trigger('change');
        }
    });

    acknowledgmentCheckbox.on('change', function() {
        validatePoForm();
    });

    $('#load-po-form').on('submit', function(e) {
        var hasFile = poInput[0] && poInput[0].files && poInput[0].files.length > 0;
        var isChecked = acknowledgmentCheckbox.is(':checked');
        var isPdf = false;

        if (hasFile) {
            var file = poInput[0].files[0];
            var fileName = file.name.toLowerCase();
            isPdf = fileName.endsWith('.pdf') || file.type === 'application/pdf';
        }

        if (!hasFile) {
            e.preventDefault();
            showWarning('PO file required', 'Please choose a PDF file before uploading.');
            return false;
        }

        if (!isPdf) {
            e.preventDefault();
            showWarning('Invalid file type', 'Only PDF files are accepted for the PO upload.');
            return false;
        }

        if (!isChecked) {
            e.preventDefault();
            showWarning('Confirmation required', 'You must acknowledge the certification and approval requirement before submitting.');
            return false;
        }

        e.preventDefault();

        Swal.fire({
            title: 'Upload PO file?',
            html:
                '<div style="text-align:center;">' +
                    '<div style="font-weight:800;color:#0F172A;margin-bottom:5px;font-size:13px;">This will load the PO and move the request to the next step.</div>' +
                    '<div style="font-size:13px;color:#64748B;">Please confirm that the selected PDF is correct.</div>' +
                '</div>',
            icon: 'question',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            // FORM DIALOG ACTIONS: name the PO operation and keep a useful review path.
            confirmButtonText: '<i class="bi bi-cloud-arrow-up-fill"></i> Upload PO',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review PO',
            buttonsStyling: false,
            customClass: {
                // SHARED FORM CONFIRMATION: retain PO-specific button classes and messages.
                popup: 'custom-po-popup can-form-swal',
                title: 'custom-po-title',
                htmlContainer: 'custom-po-message',
                confirmButton: 'swal-po-confirm',
                cancelButton: 'swal-po-cancel'
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                submitButton.prop('disabled', true);
                submitButton.html('<i class="bi bi-hourglass-split"></i> Uploading...');
                $('#load-po-form')[0].submit();
            }
        });

        return false;
    });

    $('#check-certificate-link').on('click', function(e) {
        e.preventDefault();
        showBootstrapModal('#certificateModal');
    });

    $('#modal-close-button, #modal-close-footer-button').on('click', function() {
        hideBootstrapModal('#certificateModal');
    });

    var certificateModal = '{$certificateModalValue}';

    if (certificateModal === '1') {
        showBootstrapModal('#certificateModal');
    }

    validatePoForm();
});
JS;

$this->registerJs($js);
?>

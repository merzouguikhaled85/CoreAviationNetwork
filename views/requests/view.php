<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\UrlIdHelper;
use app\models\AoRequestsApplications;
use app\models\Feedback;
use app\models\MroRequestApply;
use app\models\RepairReport;
use app\models\Requests;

/**
 * This view expects the request model in $request.
 * If your controller sends $model instead, this fallback keeps the page working.
 */
$requestModel = $request ?? ($model ?? null);

if ($requestModel === null) {
    throw new \yii\web\NotFoundHttpException('Request not found.');
}

$this->title = 'Request #' . $requestModel->request_id;
$this->params['breadcrumbs'][] = ['label' => 'Requests', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/**
 * Get current user type from session.
 */
$userType = Yii::$app->session->get('user_type');

/**
 * Load related aircraft safely.
 */
$aircraft = $requestModel->getAircraft()->one();
$destinationAirport = $requestModel->getDestinationAirport()->one();
$airportIcaoText = $destinationAirport && !empty($destinationAirport->icao)
    ? $destinationAirport->icao
    : 'N/A';

/**
 * Load AO safely when needed.
 */
$ao = null;

if (method_exists($requestModel, 'getAO')) {
    $ao = $requestModel->getAO()->one();
}

/**
 * MRO ACTION CONTEXT: use the application belonging to the connected MRO.
 * This prevents actions from targeting another MRO application for the same Request.
 */
$mroApplication = null;
$currentMroId = Yii::$app->session->get('mro_id');

if ($userType === 'mro' && $currentMroId) {
    $mroApplication = MroRequestApply::find()
        ->where([
            'request_id' => $requestModel->request_id,
            'mro_id' => $currentMroId,
        ])
        ->orderBy(['id' => SORT_DESC])
        ->one();
} else {
    // Preserve the previous display fallback for non-MRO users.
    $mroApplication = $requestModel->mroApplication ?? null;
}

$mro = $mroApplication ? $mroApplication->getMro() : null;

/**
 * Prepare request status safely.
 */
$status = (string) $requestModel->status;
$statusText = ucwords(str_replace('_', ' ', $status));
$statusClass = 'status-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $status);

/**
 * Prepare dates safely.
 */
$etaText = $requestModel->eta ? date('d M Y H:i', strtotime($requestModel->eta)) : 'N/A';
$etdText = $requestModel->etd ? date('d M Y H:i', strtotime($requestModel->etd)) : 'N/A';

/*
 * HISTORIQUE DE PRIORITÉ : la relation charge uniquement les changements de la
 * demande actuellement autorisée par le contrôleur. Les anciennes demandes sans
 * entrée restent compatibles et recevront un état vide explicite.
 */
$priorityHistory = $requestModel->priorityHistory;

/**
 * Prevent undefined variable errors if these values are not sent by the controller.
 */
$poAttachment = $poAttachment ?? null;
$invoice = $invoice ?? null;
$encodedId = UrlIdHelper::encode($requestModel->request_id);

/*
 * LIEN VERS LA REPONSE MRO OPTIONNEL : UrlIdHelper accepte uniquement un identifiant
 * numerique strictement positif. Une demande sans candidature MRO fournit null ; dans
 * ce cas aucun token n'est genere et la zone de ressources affichera simplement que
 * la page de quotation n'est pas encore disponible, sans interrompre toute la vue.
 */
$encodedInvoice = is_numeric($invoice) && (int) $invoice > 0
    ? UrlIdHelper::encode((int) $invoice)
    : null;
$crs = $crs ?? null;

/**
 * MRO ROW ACTIONS: prepare the same workflow state used by /mro-applications.
 */
$mroActionStatus = $status;
$encodedMroApplicationId = $mroApplication
    ? UrlIdHelper::encode($mroApplication->id)
    : null;
$aoRequestApplication = null;
$hasPo = false;
$lastReport = null;
$existingReport = false;
$existingFeedback = false;

if ($userType === 'mro' && $mroApplication) {
    $aoRequestApplication = AoRequestsApplications::find()
        ->where(['application_id' => $mroApplication->id])
        ->orderBy(['id' => SORT_DESC])
        ->one();
    $hasPo = $aoRequestApplication && !empty($aoRequestApplication->po);

    $lastReport = RepairReport::find()
        ->where(['mro_request_apply_id' => $mroApplication->id])
        ->orderBy(['updated_at' => SORT_DESC, 'repair_report_id' => SORT_DESC])
        ->one();
    $existingReport = $lastReport !== null;

    $existingFeedback = Feedback::find()
        ->where([
            'request_id' => $requestModel->request_id,
            'mro_id' => $mroApplication->mro_id,
        ])
        ->exists();

    // Keep status behavior identical to the MRO applications list.
    if ($mroActionStatus === 'answered' && $hasPo) {
        $mroActionStatus = 'po_loaded';
    }

    if ($existingReport && !in_array($mroActionStatus, ['closed', 'canceled'], true)) {
        $mroActionStatus = 'report_submitted';
    }
}

/**
 * Build file URL safely.
 */
$buildFileUrl = static function ($file, $defaultFolder = null) {
    if (empty($file)) {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $file)) {
        return $file;
    }

    if (strpos($file, '@web/') === 0) {
        return Yii::getAlias('@web') . '/' . ltrim(substr($file, 5), '/');
    }

    if (strpos($file, '/') !== false) {
        return Yii::getAlias('@web/') . ltrim($file, '/');
    }

    if ($defaultFolder !== null) {
        return Yii::getAlias('@web/' . trim($defaultFolder, '/') . '/') . ltrim($file, '/');
    }

    return Yii::getAlias('@web/') . ltrim($file, '/');
};

/**
 * Prepare attachment URLs safely.
 */
$requestAttachmentUrl = $buildFileUrl($requestModel->attachment ?? null);
$poAttachmentUrl = $buildFileUrl($poAttachment, 'uploads');
$crsUrl = $buildFileUrl($crs, 'uploads');
$invoiceUrl = $encodedInvoice !== null
    ? Yii::$app->urlManager->createUrl(['mro-applications/view-answer', 'id' => $encodedInvoice])
    : null;

/*
 * REQUEST RESOURCE DISPLAY 2026: show a safe, readable filename without
 * changing the stored attachment path or the destination of any link.
 */
$getAttachmentName = static function ($url, $fallback) {
    if (empty($url)) {
        return $fallback;
    }

    $path = parse_url((string) $url, PHP_URL_PATH);
    $filename = $path ? basename(str_replace('\\', '/', $path)) : '';

    return $filename !== '' ? urldecode($filename) : $fallback;
};

$requestAttachmentName = $getAttachmentName($requestAttachmentUrl, 'No request document');
$poAttachmentName = $getAttachmentName($poAttachmentUrl, 'No purchase order');
$crsAttachmentName = $getAttachmentName($crsUrl, 'No CRS document');

/**
 * Prepare National Aviation Authority value safely.
 * If a specific NAA field does not exist, it keeps the previous fallback behavior.
 */
$naaText = 'N/A';

if ($aircraft) {
    $possibleNaaFields = [
        'national_aviation_authority',
        'naa',
        'aviation_authority',
        'authority',
    ];

    foreach ($possibleNaaFields as $field) {
        if (
            method_exists($aircraft, 'hasAttribute')
            && $aircraft->hasAttribute($field)
            && !empty($aircraft->{$field})
        ) {
            $naaText = $aircraft->{$field};
            break;
        }
    }

    if ($naaText === 'N/A' && !empty($aircraft->certificateType)) {
        // $naaText = $aircraft->manufacturer;
        $naaText = $aircraft->certificateType ? $aircraft->certificateType->type : 'N/A';

    }
}

/**
 * Prepare back URL.
 */
$backUrl = Yii::$app->request->referrer ?: ['index'];

/**
 * Register Bootstrap Icons.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css'
);

/**
 * Register SweetAlert2.
 */
\yii\web\YiiAsset::register($this);
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

/**
 * Register page CSS.
 */
$this->registerCss(<<<CSS
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .requests-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .requests-page .container-fluid {
        max-width: 100%;
        overflow-x: hidden;
        padding-left: 0;
        padding-right: 0;
    }

    .page-header-card {
        background: linear-gradient(135deg, #ffffff, #eef4ff);
        border-radius: 18px;
        padding: 22px 26px;
        margin-bottom: 22px;
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
        font-size: 28px;
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

    .action-btn {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: solid 1px #8a8b8dff;
        color: #ffffff !important;
        text-decoration: none;
        transition: all .2s ease;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .12);
        font-size: 13px;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, .18);
        color: #ffffff !important;
        text-decoration: none;
    }

    .btn-apply {
        background: #3a6ca5ff;
        width: 100px;
        height: 40px;
        border-radius: 9px;
        padding: 0;
        font-size: 13px;
        font-weight: bold;
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
        background: #fcf9f9ff;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }

    .btn-back:hover {
        background: #000408ff;
        color: #ffffff !important;
        transform: translateY(-1px);
        text-decoration: none;
    }

    .btn-edit {
        background: #f0a51aff;
        color: #ffffff !important;
        border: 1px solid #d97706;
        box-shadow: 0 8px 18px rgba(217, 119, 6, 0.18);
    }

    .btn-edit:hover {
        background: #d97706;
        color: #ffffff !important;
        transform: translateY(-1px);
        text-decoration: none;
    }

    .btn-delete-main {
        background: #ef4444;
        color: #ffffff !important;
        border: 1px solid #dc2626;
        box-shadow: 0 8px 18px rgba(239, 68, 68, 0.20);
    }

    .btn-delete-main:hover {
        background: #dc2626;
        color: #ffffff !important;
        transform: translateY(-1px);
        text-decoration: none;
    }

    .view-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .content-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5eaf3;
        max-width: 100%;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 16px;
        font-size: 17px;
        font-weight: 900;
        color: #0f172a;
    }

    .detail-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .detail-item {
        background: #f8fafc;
        border: 1px solid #e5eaf3;
        border-radius: 14px;
        padding: 14px;
        min-width: 0;
    }

    .detail-item.full-width {
        grid-column: 1 / -1;
    }

    .date-row {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .detail-label {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 7px;
    }

    .detail-value {
        color: #1f2937;
        font-size: 14px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .detail-value a {
        text-decoration: none;
    }

    .request-id-badge,
    .aircraft-badge,
    .registration-badge,
    .serial-badge,
    .airport-code-badge,
    .date-badge,
    .location-badge,
    .mro-badge,
    .ao-badge,
    .naa-badge,
    .empty-badge,
    .status-badge,
    .attachment-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 12px;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
        text-decoration: none;
    }

    .request-id-badge {
        background: #eef2ff;
        color: #4338ca;
    }

    .aircraft-badge {
        background: #e0f2fe;
        color: #0369a1;
    }

    .registration-badge {
        background: #ecfdf5;
        color: #047857;
    }

    .serial-badge {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #e5e7eb;
    }

    /* MRO ROW ACTIONS: compact header buttons copied from the applications list. */
    .mro-header-actions {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
        padding-right: 4px;
        border-right: 1px solid #dbe3ee;
    }

    .mro-action-view { background: #0ea5e9; }
    .mro-action-work { background: #7c3aed; }
    .mro-action-report { background: #2563eb; }
    .mro-action-feedback { background: #059669; }
    .mro-action-contact { background: #475569; }
    .mro-action-danger { background: #dc2626; }

    .mro-header-actions .action-btn:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .25);
        outline-offset: 2px;
    }

    .airport-code-badge {
        background: #f3e8ff;
        color: #7e22ce;
    }

    .date-badge {
        background: #eef2ff;
        color: #4338ca;
    }

    .location-badge {
        background: #fff7ed;
        color: #c2410c;
    }

    .mro-badge,
    .ao-badge {
        background: #f1f5f9;
        color: #334155 !important;
    }

    .mro-badge:hover,
    .ao-badge:hover {
        background: #e2e8f0;
        color: #0f172a !important;
        text-decoration: none;
    }

    .naa-badge {
        background: #fef3c7;
        color: #92400e;
    }

    .attachment-badge {
        background: #f8fafc;
        color: #475569 !important;
        border: 1px solid #e5e7eb;
    }

    .attachment-badge:hover {
        background: #f1f5f9;
        color: #334155 !important;
        text-decoration: none;
    }

    .empty-badge {
        background: #f8fafc;
        color: #94a3b8;
        border: 1px solid #e5e7eb;
    }

    .status-badge {
        background: #e5e7eb;
        color: #374151;
    }

    .status-created {
        background: #e0f2fe;
        color: #0369a1;
    }

    .status-answered {
        background: #dcfce7;
        color: #166534;
    }

    .status-po_loaded {
        background: #fef3c7;
        color: #92400e;
    }

    .status-update_request {
        background: #fae8ff;
        color: #86198f;
    }

    .status-work_accepted,
    .status-work_started {
        background: #ede9fe;
        color: #5b21b6;
    }

    .status-report_submitted {
        background: #ccfbf1;
        color: #115e59;
    }

    .status-closed {
        background: #dcfce7;
        color: #14532d;
    }

    .status-canceled {
        background: #fee2e2;
        color: #991b1b;
    }

    .attachments-box {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    /* REQUEST RESOURCE DISPLAY 2026: lightweight cards; no JavaScript or external asset. */
    .attachments-box .attachment-badge {
        width: 100%;
        min-width: 0;
        min-height: 62px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 11px;
        border: 1px solid #DCE7F2;
        border-radius: 12px;
        background: #FFFFFF;
        color: #0F3A63 !important;
        box-shadow: 0 3px 10px rgba(15, 50, 97, .045);
        transition: border-color .18s ease, background-color .18s ease, transform .18s ease;
    }

    .attachments-box .attachment-badge.is-page {
        border-color: #DDD6FE;
        background: #FAF8FF;
        color: #5B21B6 !important;
    }

    .attachment-resource-icon {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #E0F2FE;
        color: #0369A1;
        font-size: 16px;
    }

    .is-page .attachment-resource-icon {
        background: #EDE9FE;
        color: #6D28D9;
    }

    .attachment-resource-copy {
        min-width: 0;
        display: flex;
        flex: 1;
        flex-direction: column;
        gap: 2px;
    }

    .attachment-resource-title {
        color: inherit;
        font-size: 12px;
        font-weight: 900;
        line-height: 1.2;
    }

    .attachment-resource-meta {
        overflow: hidden;
        color: #64748B;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.3;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .attachment-resource-action {
        flex: 0 0 auto;
        color: #0284C7;
        font-size: 15px;
    }

    .is-page .attachment-resource-action {
        color: #7C3AED;
    }

    .attachments-box a.attachment-badge:hover {
        border-color: #7DD3FC;
        background: #F0F9FF;
        color: #075985 !important;
        text-decoration: none;
        transform: translateY(-1px);
    }

    .attachments-box a.attachment-badge.is-page:hover {
        border-color: #C4B5FD;
        background: #F5F3FF;
        color: #5B21B6 !important;
    }

    .attachment-badge.is-disabled {
        border-style: dashed;
        background: #F8FAFC;
        box-shadow: none;
        opacity: .62;
        cursor: default;
        pointer-events: none;
    }

    .request-info-textarea {
        display: block;
        width: 100%;
        min-height: 96px;
        line-height: 1.65;
        font-weight: 700;
        color: #334155;
        background: #ffffff;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 12px;
        resize: vertical;
        cursor: text;
    }

    .alert {
        border-radius: 14px;
        border: none;
        padding: 14px 18px;
        font-weight: 700;
        box-shadow: 0 5px 16px rgba(15, 23, 42, .08);
        margin-bottom: 18px;
    }

    /* SWEETALERT: compact confirmation shared by Request and MRO actions. */
    .swal2-popup.compact-request-popup {
        width: 320px !important;
        max-width: calc(100vw - 24px) !important;
        border-radius: 14px !important;
        padding: 12px 14px 14px !important;
        box-shadow: 0 16px 38px rgba(15, 23, 42, .22) !important;
    }

    .compact-request-popup .swal2-icon {
        width: 44px !important;
        height: 44px !important;
        margin: 6px auto 9px !important;
    }

    .compact-request-popup .swal2-icon .swal2-icon-content {
        font-size: 27px !important;
    }

    .swal2-title.compact-request-title {
        color: #0f172a !important;
        font-size: 17px !important;
        font-weight: 850 !important;
        line-height: 1.25 !important;
        padding: 0 !important;
        margin: 0 0 6px !important;
    }

    .swal2-html-container.compact-request-message {
        color: #334155 !important;
        font-size: 12.5px !important;
        font-weight: 800 !important;
        line-height: 1.4 !important;
        margin: 0 4px 10px !important;
    }

    .compact-request-actions {
        margin-top: 8px !important;
        gap: 7px !important;
    }

    .compact-swal-confirm,
    .compact-swal-cancel {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        min-width: 96px !important;
        height: 35px !important;
        padding: 7px 11px !important;
        border: 0 !important;
        border-radius: 8px !important;
        font-size: 12px !important;
        font-weight: 800 !important;
        transition: .2s ease !important;
    }

    .compact-swal-confirm {
        background: #2563eb !important;
        color: #ffffff !important;
        box-shadow: 0 6px 14px rgba(37, 99, 235, .22) !important;
    }

    .compact-swal-danger {
        background: #dc2626 !important;
        box-shadow: 0 6px 14px rgba(220, 38, 38, .22) !important;
    }

    .compact-swal-cancel {
        background: #e2e8f0 !important;
        color: #334155 !important;
    }

    .compact-swal-confirm:hover,
    .compact-swal-cancel:hover {
        background: #cbd5e1 !important;
        transform: translateY(-1px);
    }

    .compact-swal-confirm:hover { background: #1d4ed8 !important; }
    .compact-swal-danger:hover { background: #b91c1c !important; }

    @media (max-width: 992px) {
        .requests-page {
            padding: 14px;
        }

        .page-header-card {
            padding: 18px;
        }

        .dash-title {
            font-size: 23px;
        }

        .view-grid {
            grid-template-columns: 1fr;
        }

        .detail-list {
            grid-template-columns: 1fr;
        }

        .date-row {
            grid-template-columns: 1fr;
        }

        /* REQUEST RESOURCE DISPLAY 2026: stack resources on narrow screens. */
        .attachments-box {
            grid-template-columns: 1fr;
        }

    }

    @media (max-width: 576px) {
        .requests-page {
            padding: 10px;
        }

        .page-header-card,
        .content-card {
            border-radius: 14px;
            padding: 16px;
        }

        .header-actions {
            width: 100%;
        }

        .mro-header-actions {
            width: 100%;
            padding: 0 0 10px;
            border-right: 0;
            border-bottom: 1px solid #dbe3ee;
        }

        .btn-page-action {
            width: 100%;
            justify-content: center;
        }

        .request-id-badge,
        .aircraft-badge,
        .registration-badge,
        .serial-badge,
        .airport-code-badge,
        .date-badge,
        .location-badge,
        .mro-badge,
        .ao-badge,
        .naa-badge,
        .empty-badge,
        .status-badge,
        .attachment-badge {
            white-space: normal;
        }
    }
CSS);

/**
 * Register JavaScript.
 * This keeps Yii2 POST behavior for every protected workflow action while
 * replacing the native confirmation dialog with the shared SweetAlert style.
 */
$this->registerJs(<<<JS
if (typeof bootstrap !== 'undefined') {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]'));

    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el);
    });
}

/* MRO ROW ACTIONS: reuse one SweetAlert flow for every protected header action. */
var lastConfirmElement = null;

document.addEventListener('click', function (event) {
    var clickedAction = event.target.closest('a[data-confirm]');
    if (clickedAction) lastConfirmElement = clickedAction;
}, true);

function getSwalData(element, name, fallback) {
    if (!element) return fallback;
    var value = element.getAttribute('data-swal-' + name);
    return value !== null && value !== '' ? value : fallback;
}

function escapeSwalHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

if (typeof yii !== 'undefined') {
    yii.confirm = function (message, okCallback, cancelCallback) {
        var actionElement = lastConfirmElement;

        if (typeof Swal === 'undefined') {
            if (confirm(message)) okCallback();
            else if (cancelCallback) cancelCallback();
            return;
        }

        var alertIcon = getSwalData(actionElement, 'icon', 'question');

        Swal.fire({
            width: 320,
            title: escapeSwalHtml(getSwalData(actionElement, 'title', 'Confirm action?')),
            html: '<div style="font-size:12.5px;font-weight:800;color:#334155;line-height:1.4;text-align:center;">' +
                escapeSwalHtml(getSwalData(actionElement, 'text', message)) + '</div>',
            icon: alertIcon,
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
            allowEscapeKey: true,
            confirmButtonText: getSwalData(
                actionElement,
                'confirm-text',
                '<i class="bi bi-check-circle"></i> Continue'
            ),
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review action',
            buttonsStyling: false,
            // SHARED DETAIL CONFIRMATION: reuse the common compact dialog presentation.
            customClass: {
                popup: 'compact-request-popup can-detail-swal',
                title: 'compact-request-title',
                htmlContainer: 'compact-request-message',
                actions: 'compact-request-actions',
                confirmButton: 'compact-swal-confirm' + (alertIcon === 'warning' ? ' compact-swal-danger' : ''),
                cancelButton: 'compact-swal-cancel'
            }
        }).then(function (result) {
            if (result.isConfirmed) okCallback();
            else if (cancelCallback) cancelCallback();
        });
    };
}
JS, \yii\web\View::POS_READY);
?>

<!-- SHARED DETAIL SYSTEM: presentation only; request actions and permissions remain local. -->
<main class="dash-content requests-page can-detail-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title fw-bold">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-clipboard2-check"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Detailed maintenance request information
                </div>

            </div>

            <div class="header-actions">
                <?php if (!Yii::$app->user->isGuest && $userType === 'mro' && $mroApplication): ?>
                    <!-- MRO ROW ACTIONS: same conditional actions as /mro-applications. -->
                    <div class="mro-header-actions" aria-label="MRO application actions">
                        <?= Html::a(
                            '<i class="bi bi-eye"></i>',
                            ['/mro-applications/view-answer', 'id' => $encodedMroApplicationId],
                            [
                                'class' => 'action-btn mro-action-view',
                                'title' => 'View Answer',
                                'aria-label' => 'View Answer',
                                'data-bs-toggle' => 'tooltip',
                            ]
                        ) ?>

                        <?php if ($mroActionStatus === 'po_loaded' && $hasPo): ?>
                            <?= Html::a(
                                '<i class="bi bi-check-circle"></i>',
                                ['/mro-applications/accept-po', 'id' => $encodedMroApplicationId],
                                [
                                    'class' => 'action-btn mro-action-view',
                                    'title' => 'Accept PO',
                                    'aria-label' => 'Accept PO',
                                    'data-bs-toggle' => 'tooltip',
                                    'data' => [
                                        'confirm' => 'Are you sure you want to accept this PO?',
                                        'method' => 'post',
                                        'swal-title' => 'Accept PO?',
                                        'swal-text' => 'The purchase order for request #' . $requestModel->request_id . ' will be accepted.',
                                        'swal-icon' => 'question',
                                        'swal-confirm-text' => '<i class="bi bi-check-circle"></i> Accept PO',
                                    ],
                                ]
                            ) ?>
                        <?php endif; ?>

                        <?php if ($mroActionStatus === 'work_accepted'): ?>
                            <?= Html::a(
                                '<i class="bi bi-play-circle"></i>',
                                ['/mro-applications/start-work', 'id' => $encodedId],
                                [
                                    'class' => 'action-btn mro-action-work',
                                    'title' => 'Start Working',
                                    'aria-label' => 'Start Working',
                                    'data-bs-toggle' => 'tooltip',
                                    'data' => [
                                        'confirm' => 'Are you sure you want to start work?',
                                        'method' => 'post',
                                        'swal-title' => 'Start work?',
                                        'swal-text' => 'Maintenance work will be started for request #' . $requestModel->request_id . '.',
                                        'swal-icon' => 'question',
                                        'swal-confirm-text' => '<i class="bi bi-play-circle"></i> Start Work',
                                    ],
                                ]
                            ) ?>
                        <?php endif; ?>

                        <?php if ($mroActionStatus === Requests::STATUS_UPDATE_REQUEST): ?>
                            <?= Html::a(
                                '<i class="bi bi-check-square"></i>',
                                ['/mro-applications/accept-update', 'id' => $encodedId],
                                [
                                    'class' => 'action-btn mro-action-view',
                                    'title' => 'Accept Updated Request',
                                    'aria-label' => 'Accept Updated Request',
                                    'data-bs-toggle' => 'tooltip',
                                    'data' => [
                                        'confirm' => 'Are you sure you want to accept this updated request?',
                                        'method' => 'post',
                                        'swal-title' => 'Accept updated request?',
                                        'swal-text' => 'The updated scope for request #' . $requestModel->request_id . ' will be accepted.',
                                        'swal-icon' => 'question',
                                        'swal-confirm-text' => '<i class="bi bi-check-square"></i> Accept',
                                    ],
                                ]
                            ) ?>

                            <?= Html::a(
                                '<i class="bi bi-x-square"></i>',
                                ['/mro-applications/deny-request', 'id' => $encodedId],
                                [
                                    'class' => 'action-btn mro-action-danger',
                                    'title' => 'Deny Updated Request',
                                    'aria-label' => 'Deny Updated Request',
                                    'data-bs-toggle' => 'tooltip',
                                    'data' => [
                                        'confirm' => 'Are you sure you want to deny this updated request?',
                                        'method' => 'post',
                                        'swal-title' => 'Deny updated request?',
                                        'swal-text' => 'The updated scope for request #' . $requestModel->request_id . ' will be denied.',
                                        'swal-icon' => 'warning',
                                        'swal-confirm-text' => '<i class="bi bi-x-square"></i> Deny',
                                    ],
                                ]
                            ) ?>
                        <?php endif; ?>

                        <?php if (
                            in_array($mroActionStatus, ['work_started', 'report_submitted'], true)
                            && (!$existingReport || ($lastReport && (int) $lastReport->quote_approved === 0))
                        ): ?>
                            <?= Html::a(
                                '<i class="bi bi-upload"></i>',
                                ['/mro-applications/submit-report', 'id' => $encodedMroApplicationId],
                                [
                                    'class' => 'action-btn mro-action-report',
                                    'title' => 'Upload CRS',
                                    'aria-label' => 'Upload CRS',
                                    'data-bs-toggle' => 'tooltip',
                                ]
                            ) ?>
                        <?php endif; ?>

                        <?php if ($existingReport): ?>
                            <?= Html::a(
                                '<i class="bi bi-file-text"></i>',
                                ['/mro-applications/view-reports', 'id' => $encodedMroApplicationId],
                                [
                                    'class' => 'action-btn mro-action-report',
                                    'title' => 'View CRSs',
                                    'aria-label' => 'View CRSs',
                                    'data-bs-toggle' => 'tooltip',
                                ]
                            ) ?>
                        <?php endif; ?>

                        <?php if ($mroActionStatus === 'closed' && $existingFeedback): ?>
                            <?= Html::a(
                                '<i class="bi bi-chat-square-text"></i>',
                                ['/mro-applications/view-feedback', 'id' => $encodedId],
                                [
                                    'class' => 'action-btn mro-action-feedback',
                                    'title' => 'View Feedback',
                                    'aria-label' => 'View Feedback',
                                    'data-bs-toggle' => 'tooltip',
                                ]
                            ) ?>
                        <?php endif; ?>

                        <?php if ($mroActionStatus !== 'closed'): ?>
                            <?= Html::a(
                                '<i class="bi bi-envelope"></i>',
                                ['/mro-applications/contact', 'id' => $encodedMroApplicationId],
                                [
                                    'class' => 'action-btn mro-action-contact',
                                    'title' => 'Contact',
                                    'aria-label' => 'Contact',
                                    'data-bs-toggle' => 'tooltip',
                                ]
                            ) ?>

                            <?= Html::a(
                                '<i class="bi bi-calendar-event"></i>',
                                ['/mro-applications/set-appointment', 'app_request_id' => $encodedMroApplicationId],
                                [
                                    'class' => 'action-btn mro-action-contact',
                                    'title' => 'Set Appointment',
                                    'aria-label' => 'Set Appointment',
                                    'data-bs-toggle' => 'tooltip',
                                ]
                            ) ?>
                        <?php endif; ?>

                        <?php if (in_array($mroActionStatus, ['answered', 'created', 'po_loaded'], true)): ?>
                            <?= Html::a(
                                '<i class="bi bi-x-circle"></i>',
                                ['/mro-applications/cancel-application', 'id' => $encodedMroApplicationId],
                                [
                                    'class' => 'action-btn mro-action-danger',
                                    'title' => 'Cancel Application',
                                    'aria-label' => 'Cancel Application',
                                    'data-bs-toggle' => 'tooltip',
                                    'data' => [
                                        'confirm' => 'Are you sure you want to cancel this application?',
                                        'method' => 'post',
                                        'swal-title' => 'Cancel application?',
                                        'swal-text' => 'Your application for request #' . $requestModel->request_id . ' will be removed.',
                                        'swal-icon' => 'warning',
                                        'swal-confirm-text' => '<i class="bi bi-x-circle"></i> Cancel Application',
                                    ],
                                ]
                            ) ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Requests',
                    $backUrl,
                    ['class' => 'btn-page-action btn-back']
                ) ?>

                <?php if (
                    !Yii::$app->user->isGuest
                    && $userType === 'mro'
                    && !$mroApplication
                    && in_array($requestModel->status, ['created', 'answered'], true)
                ): ?>
                    <?= Html::a(
                        '<i class="bi bi-check2-circle"> Apply</i>',
                        ['mro-requests/apply', 'id' => $encodedId],
                        [
                            'class' => 'action-btn btn-apply',
                            
                        ]
                    ) ?>
                <?php endif; ?>

                <?php if (!Yii::$app->user->isGuest && $userType === 'ao' && in_array($requestModel->status, ['answered', 'created', 'po_loaded'], true)): ?>
                    <?= Html::a(
                        '<i class="bi bi-pencil"></i> Update',
                        ['update', 'id' => $encodedId],
                        ['class' => 'btn-page-action btn-edit']
                    ) ?>

                    <?= Html::a(
                        '<i class="bi bi-trash"></i> Delete',
                        ['delete', 'id' => $encodedId],
                        [
                            'class' => 'btn-page-action btn-delete-main',
                            'data' => [
                                'confirm' => 'Request #' . $requestModel->request_id . ' will be permanently deleted.',
                                'method' => 'post',
                                'swal-title' => 'Delete this request?',
                                'swal-text' => 'This action cannot be undone.',
                                'swal-icon' => 'warning',
                                'swal-confirm-text' => '<i class="bi bi-trash3-fill"></i> Delete',
                            ],
                        ]
                    ) ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Flash messages -->
        <?php if (Yii::$app->session->hasFlash('message')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('message')) ?>
            </div>
        <?php endif; ?>

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

        <div class="view-grid">

            <!-- Main request details -->
            <div class="content-card">
                <h2 class="section-title">
                    <i class="bi bi-info-circle text-primary"></i>
                    Request Details
                </h2>

                <div class="detail-list">

                    <!-- Request ID -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-hash"></i>
                            Request ID
                        </div>
                        <div class="detail-value">
                            <span class="request-id-badge">
                                #<?= Html::encode($requestModel->request_id) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-activity"></i>
                            Status
                        </div>
                        <div class="detail-value">
                            <span class="status-badge <?= Html::encode($statusClass ?: 'status-default') ?>">
                                <i class="bi bi-circle-fill"></i>
                                <?= Html::encode($statusText) ?>
                            </span>
                        </div>
                    </div>

                    <!--
                        PRIORITÉ OPÉRATIONNELLE : elle est affichée séparément du
                        statut afin de ne pas laisser penser qu'AOG constitue une
                        étape du workflow. L'échéance demeure explicitement en UTC.
                    -->
                    <?php
                        $operationalPriority = $requestModel->operational_priority ?: Requests::PRIORITY_ROUTINE;
                        $operationalPriorityIcons = [
                            Requests::PRIORITY_AOG => 'bi-exclamation-octagon',
                            Requests::PRIORITY_URGENT => 'bi-lightning-charge',
                            Requests::PRIORITY_ROUTINE => 'bi-calendar-check',
                        ];
                        $responseDueTimestamp = !empty($requestModel->response_due_at_utc)
                            ? strtotime($requestModel->response_due_at_utc . ' UTC')
                            : false;

                        /*
                         * COHÉRENCE AVEC LES LISTES : dans un dossier fermé ou annulé, l'échéance de réponse
                         * demeure disponible en base et dans l'historique, mais le compteur dynamique n'est plus
                         * affiché. L'utilisateur voit toujours la priorité d'origine sans fausse alerte « overdue ».
                         */
                        $responseDeadlineVisible = $responseDueTimestamp !== false
                            && !in_array((string) $requestModel->status, [
                                Requests::STATUS_CLOSED,
                                Requests::STATUS_CANCELLED,
                                'canceled',
                            ], true);
                    ?>
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-broadcast-pin"></i>
                            Operational Priority
                        </div>
                        <div class="detail-value">
                            <div class="operational-priority-display">
                                <span class="operational-priority-badge priority-<?= Html::encode($operationalPriority) ?>">
                                    <i class="bi <?= Html::encode($operationalPriorityIcons[$operationalPriority] ?? 'bi-calendar-check') ?>"></i>
                                    <?= Html::encode($requestModel->getOperationalPriorityLabel()) ?>
                                </span>
                                <?php if ($responseDeadlineVisible): ?>
                                    <small
                                        class="operational-priority-deadline<?= $responseDueTimestamp < time() ? ' is-overdue' : '' ?>"
                                        data-response-deadline-utc="<?= Html::encode(gmdate('c', $responseDueTimestamp)) ?>"
                                        title="Response due <?= Html::encode(gmdate('d M Y H:i', $responseDueTimestamp)) ?> UTC"
                                    >
                                        <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                        <span data-countdown-label>
                                            <?= $responseDueTimestamp < time() ? 'Response overdue' : 'Calculating…' ?>
                                        </span>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- AO information for MRO users -->
                    <?php if (!Yii::$app->user->isGuest && $userType === 'mro'): ?>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="bi bi-building"></i>
                                Aircraft Operator / CAMO
                            </div>
                            <div class="detail-value">
                                <?php if ($ao): ?>
                                    <span class="ao-badge">
                                        <i class="bi bi-building-check"></i>
                                        <?= Html::encode($ao->username ?? 'N/A') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="empty-badge">
                                        <i class="bi bi-person-x"></i>
                                        N/A
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- MRO information for AO users -->
                    <?php if (!Yii::$app->user->isGuest && $userType === 'ao'): ?>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="bi bi-tools"></i>
                                MRO
                            </div>
                            <div class="detail-value">
                                <?php if ($mro): ?>
                                    <?= Html::a(
                                        '<i class="bi bi-tools"></i>' . Html::encode($mro->username),
                                        ['mro-profile/view', 'id' => UrlIdHelper::encode($mro->mro_id)],
                                        ['class' => 'mro-badge']
                                    ) ?>
                                <?php else: ?>
                                    <span class="empty-badge">
                                        <i class="bi bi-person-x"></i>
                                        MRO Deleted
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Aircraft manufacturer -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-airplane"></i>
                            Aircraft
                        </div>
                        <div class="detail-value">
                            <?php if ($aircraft): ?>
                                <span class="aircraft-badge">
                                    <i class="bi bi-airplane"></i>
                                    <?= Html::encode($aircraft->manufacturer ?: 'N/A') ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Aircraft deleted
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Aircraft model -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-airplane-engines"></i>
                            Model
                        </div>
                        <div class="detail-value">
                            <?php if ($aircraft): ?>
                                <span class="aircraft-badge">
                                    <i class="bi bi-airplane-engines"></i>
                                    <?= Html::encode($aircraft->model ?: 'N/A') ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Aircraft deleted
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Aircraft registration -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-card-text"></i>
                            Aircraft Registration
                        </div>
                        <div class="detail-value">
                            <span class="registration-badge">
                                <i class="bi bi-card-heading"></i>
                                <?= Html::encode($requestModel->aircraft_registration ?: 'N/A') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Aircraft serial number -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-upc-scan"></i>
                            Aircraft Serial Number
                        </div>
                        <div class="detail-value">
                            <span class="serial-badge">
                                <i class="bi bi-upc"></i>
                                <?= Html::encode($requestModel->serial_number ?: 'N/A') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Maintenance airport ICAO code -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-signpost-2"></i>
                            Maintenance Airport ICAO
                        </div>
                        <div class="detail-value">
                            <?php if ($airportIcaoText !== 'N/A'): ?>
                                <span class="airport-code-badge">
                                    <i class="bi bi-geo-alt"></i>
                                    <?= Html::encode($airportIcaoText) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-dash-circle"></i>
                                    N/A
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="date-row">
                        <!-- ETA -->
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="bi bi-calendar-event"></i>
                                ETA
                            </div>
                            <div class="detail-value">
                                <span class="date-badge">
                                    <i class="bi bi-calendar-event"></i>
                                    <?= Html::encode($etaText) ?>
                                </span>
                            </div>
                        </div>

                        <!-- ETD -->
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="bi bi-calendar-check"></i>
                                ETD
                            </div>
                            <div class="detail-value">
                                <span class="date-badge">
                                    <i class="bi bi-calendar-check"></i>
                                    <?= Html::encode($etdText) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance location -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-geo-alt"></i>
                            Maintenance Location
                        </div>
                        <div class="detail-value">
                            <span class="location-badge">
                                <i class="bi bi-pin-map"></i>
                                <?= Html::encode($requestModel->location ?: 'N/A') ?>
                            </span>
                        </div>
                    </div>

                    <!-- National Aviation Authority -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-shield-check"></i>
                            National Aviation Authority
                        </div>
                        <div class="detail-value">
                            <?php if ($aircraft): ?>
                                <span class="naa-badge">
                                    <i class="bi bi-patch-check"></i>
                                    <?= Html::encode($naaText) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Aircraft deleted
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- REQUEST RESOURCE DISPLAY 2026: distinguish files from links to application pages. -->
                    <div class="detail-item full-width">
                        <div class="detail-label">
                            <i class="bi bi-paperclip"></i>
                            Documents & Related Pages
                        </div>

                        <div class="attachments-box">
                            <?= $requestAttachmentUrl
                                ? Html::a(
                                    '<span class="attachment-resource-icon"><i class="bi bi-file-earmark-text"></i></span>'
                                    . '<span class="attachment-resource-copy"><span class="attachment-resource-title">Request Attachment</span><span class="attachment-resource-meta">' . Html::encode($requestAttachmentName) . '</span></span>'
                                    . '<span class="attachment-resource-action"><i class="bi bi-box-arrow-up-right"></i></span>',
                                    $requestAttachmentUrl,
                                    ['class' => 'attachment-badge', 'target' => '_blank', 'rel' => 'noopener', 'data-pjax' => '0', 'data-no-loader' => '1', 'title' => 'View request attachment']
                                )
                                : '<span class="attachment-badge is-disabled"><span class="attachment-resource-icon"><i class="bi bi-file-earmark-x"></i></span><span class="attachment-resource-copy"><span class="attachment-resource-title">Request Attachment</span><span class="attachment-resource-meta">Not available</span></span></span>' ?>

                            <?= $poAttachmentUrl
                                ? Html::a(
                                    '<span class="attachment-resource-icon"><i class="bi bi-file-earmark-check"></i></span>'
                                    . '<span class="attachment-resource-copy"><span class="attachment-resource-title">Purchase Order</span><span class="attachment-resource-meta">' . Html::encode($poAttachmentName) . '</span></span>'
                                    . '<span class="attachment-resource-action"><i class="bi bi-box-arrow-up-right"></i></span>',
                                    $poAttachmentUrl,
                                    ['class' => 'attachment-badge', 'target' => '_blank', 'rel' => 'noopener', 'data-pjax' => '0', 'data-no-loader' => '1', 'title' => 'View purchase order']
                                )
                                : '<span class="attachment-badge is-disabled"><span class="attachment-resource-icon"><i class="bi bi-file-earmark-x"></i></span><span class="attachment-resource-copy"><span class="attachment-resource-title">Purchase Order</span><span class="attachment-resource-meta">Not available</span></span></span>' ?>

                            <?= $invoiceUrl
                                ? Html::a(
                                    '<span class="attachment-resource-icon"><i class="bi bi-window-stack"></i></span>'
                                    . '<span class="attachment-resource-copy"><span class="attachment-resource-title">MRO Quotation</span><span class="attachment-resource-meta">Related application page</span></span>'
                                    . '<span class="attachment-resource-action"><i class="bi bi-arrow-right-circle"></i></span>',
                                    $invoiceUrl,
                                    ['class' => 'attachment-badge is-page', 'data-pjax' => '0', 'title' => 'Open MRO quotation page']
                                )
                                : '<span class="attachment-badge is-page is-disabled"><span class="attachment-resource-icon"><i class="bi bi-window"></i></span><span class="attachment-resource-copy"><span class="attachment-resource-title">MRO Quotation</span><span class="attachment-resource-meta">Page not available</span></span></span>' ?>

                            <?= $crsUrl
                                ? Html::a(
                                    '<span class="attachment-resource-icon"><i class="bi bi-file-earmark-medical"></i></span>'
                                    . '<span class="attachment-resource-copy"><span class="attachment-resource-title">CRS Document</span><span class="attachment-resource-meta">' . Html::encode($crsAttachmentName) . '</span></span>'
                                    . '<span class="attachment-resource-action"><i class="bi bi-box-arrow-up-right"></i></span>',
                                    $crsUrl,
                                    ['class' => 'attachment-badge', 'target' => '_blank', 'rel' => 'noopener', 'data-pjax' => '0', 'data-no-loader' => '1', 'title' => 'View CRS document']
                                )
                                : '<span class="attachment-badge is-disabled"><span class="attachment-resource-icon"><i class="bi bi-file-earmark-x"></i></span><span class="attachment-resource-copy"><span class="attachment-resource-title">CRS Document</span><span class="attachment-resource-meta">Not available</span></span></span>' ?>
                        </div>
                    </div>

                    <!-- Request information -->
                    <div class="detail-item full-width">
                        <div class="detail-label">
                            <i class="bi bi-info-circle"></i>
                            Request Informations
                        </div>

                        <?= Html::textarea(
                            'request_details',
                            $requestModel->request_details ?: 'N/A',
                            [
                                'class' => 'request-info-textarea',
                                'rows' => 4,
                                'readonly' => true,
                                'aria-label' => 'Request Informations',
                            ]
                        ) ?>
                    </div>

                </div>

                <!--
                    EMPLACEMENT DE L'HISTORIQUE : sous les informations détaillées,
                    dans la carte principale. Le fragment est replié par défaut et
                    reçoit uniquement les données déjà autorisées de cette demande.
                -->
                <?= $this->render('_priority-history', [
                    'requestModel' => $requestModel,
                    'priorityHistory' => $priorityHistory,
                    'operationalPriorityIcons' => $operationalPriorityIcons,
                ]) ?>
            </div>

        </div>

    </div>
</main>

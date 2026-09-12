<?php

use yii\helpers\Html;
use yii\web\View;
use app\components\UrlIdHelper;

/**
 * This view expects:
 * - $aoRequestsApplications
 *
 * Existing controller:
 * public function actionViewPo1($id)
 * {
 *     $aoRequestsApplications = AoRequestsApplications::find()
 *         ->where(['application_id' => $id])
 *         ->all();
 *
 *     return $this->render('view-po', [
 *         'aoRequestsApplications' => $aoRequestsApplications,
 *     ]);
 * }
 */

$aoRequestsApplications = $aoRequestsApplications ?? [];

if (empty($aoRequestsApplications)) {
    throw new \yii\web\NotFoundHttpException('Purchase Order not found.');
}

/**
 * Get first application safely.
 */
$firstApplication = reset($aoRequestsApplications);

/**
 * Load linked request safely.
 */
$requestModel = null;

if ($firstApplication) {
    if (method_exists($firstApplication, 'canGetProperty') && $firstApplication->canGetProperty('request')) {
        $requestModel = $firstApplication->request;
    } elseif (method_exists($firstApplication, 'getRequest')) {
        $requestModel = $firstApplication->getRequest()->one();
    }
}

/**
 * Load related aircraft safely.
 */
$aircraft = null;

if ($requestModel) {
    if (method_exists($requestModel, 'canGetProperty') && $requestModel->canGetProperty('aircraft')) {
        $aircraft = $requestModel->aircraft;
    } elseif (method_exists($requestModel, 'getAircraft')) {
        $aircraft = $requestModel->getAircraft()->one();
    }
}

/**
 * Page title.
 */
$requestId = $requestModel->request_id ?? ($firstApplication->request_id ?? 'N/A');

$this->title = 'Purchase Orders - Request #' . $requestId;
$this->params['breadcrumbs'][] = ['label' => 'Applied Requests', 'url' => ['/mro-applications/index']];
$this->params['breadcrumbs'][] = $this->title;

/**
 * Register Bootstrap Icons.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['position' => View::POS_HEAD]
);

/* VIEW PO SWEETALERT 2026: compact confirmations for workflow-changing actions. */
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'position' => View::POS_HEAD,
]);

/**
 * Safe attribute getter.
 */
$getSafeAttribute = static function ($model, array $fields, $default = null) {
    if ($model === null) {
        return $default;
    }

    foreach ($fields as $field) {
        try {
            if (
                method_exists($model, 'hasAttribute')
                && $model->hasAttribute($field)
                && $model->{$field} !== null
                && $model->{$field} !== ''
            ) {
                return $model->{$field};
            }

            if (
                method_exists($model, 'canGetProperty')
                && $model->canGetProperty($field)
                && $model->{$field} !== null
                && $model->{$field} !== ''
            ) {
                return $model->{$field};
            }

            if (property_exists($model, $field) && $model->{$field} !== null && $model->{$field} !== '') {
                return $model->{$field};
            }
        } catch (\Throwable $e) {
            continue;
        }
    }

    return $default;
};

/**
 * Build safe file URL.
 */
$buildFileUrl = static function ($file, $defaultFolder = 'uploads') {
    if (empty($file) || is_array($file) || is_object($file)) {
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
        return Yii::getAlias('@web/') . ltrim($file, '/');
    }

    return Yii::getAlias('@web/' . trim($defaultFolder, '/') . '/') . ltrim($file, '/');
};

/**
 * Normalize purchase orders.
 * Supports:
 * - one PO file in $application->po
 * - possible alternative field names
 * - comma-separated PO files
 */
$purchaseOrders = [];

foreach ($aoRequestsApplications as $index => $application) {
    $poFile = $getSafeAttribute($application, [
        'po',
        'PO',
        'purchase_order',
        'purchase_order_file',
        'po_file',
        'file',
        'document',
        'attachment',
    ], null);

    if (empty($poFile)) {
        continue;
    }

    /**
     * Support comma-separated files in one column.
     */
    $poFiles = is_string($poFile) && strpos($poFile, ',') !== false
        ? array_map('trim', explode(',', $poFile))
        : [$poFile];

    foreach ($poFiles as $file) {
        if (empty($file)) {
            continue;
        }

        $createdAt = $getSafeAttribute($application, [
            'created_at',
            'createdAt',
            'date',
            'po_date',
        ], null);

        $status = $getSafeAttribute($application, [
            'status',
            'state',
        ], null);

        $purchaseOrders[] = [
            'application_id' => $application->application_id ?? null,
            'request_id' => $application->request_id ?? null,
            'file' => $file,
            'filename' => basename(str_replace('\\', '/', (string) $file)),
            'url' => $buildFileUrl($file, 'uploads'),
            'label' => 'Purchase Order #' . (count($purchaseOrders) + 1),
            'created_at' => $createdAt,
            'status' => $status,
        ];
    }
}

$purchaseOrderCount = count($purchaseOrders);

/*
 * VIEW PO ACTIONS 2026: resolve the selected MRO application once and prepare
 * signed identifiers. These values affect links only, never workflow state.
 */
$mroRequestApplication = $firstApplication && !empty($firstApplication->application_id)
    ? \app\models\MroRequestApply::findOne($firstApplication->application_id)
    : null;
$encodedApplicationId = $mroRequestApplication
    ? UrlIdHelper::encode($mroRequestApplication->id)
    : null;
$encodedRequestId = $requestModel && !empty($requestModel->request_id)
    ? UrlIdHelper::encode($requestModel->request_id)
    : null;
$hasPo = $purchaseOrderCount > 0;

/**
 * Prepare request details safely.
 */
$status = (string) ($requestModel->status ?? 'N/A');

/* VIEW PO ACTIONS 2026: mirror the effective PO status used by the list. */
if ($status === 'answered' && $hasPo) {
    $status = 'po_loaded';
}
$statusText = $status !== 'N/A' ? ucwords(str_replace('_', ' ', $status)) : 'N/A';
$statusClass = $status !== 'N/A'
    ? 'status-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $status)
    : 'status-default';

$aircraftName = $aircraft
    ? trim(($aircraft->manufacturer ?? '') . ' ' . ($aircraft->model ?? ''))
    : 'Aircraft deleted';

$aircraftRegistration = $requestModel->aircraft_registration
    ?? $aircraft->registration_number
    ?? 'N/A';

$serialNumber = $requestModel->serial_number
    ?? $aircraft->serial_number
    ?? 'N/A';

$etaText = !empty($requestModel->eta)
    ? date('d M Y H:i', strtotime($requestModel->eta))
    : 'N/A';

$etdText = !empty($requestModel->etd)
    ? date('d M Y H:i', strtotime($requestModel->etd))
    : 'N/A';

$locationText = $requestModel->location ?? 'N/A';

$requestDetails = $requestModel->request_details
    ?? $requestModel->description
    ?? null;

/**
 * Safe back URL.
 */
$backUrl = Yii::$app->request->referrer ?: ['/mro-applications/index'];

/**
 * Safe request URL.
 */
$requestViewUrl = $requestModel && !empty($requestModel->request_id)
    ? ['/requests/view', 'id' => $encodedRequestId]
    : ['/mro-applications/index'];

/* VIEW PO ACTIONS 2026: report conditions are identical to the list page. */
$lastReport = $mroRequestApplication
    ? \app\models\RepairReport::find()
        ->where(['mro_request_apply_id' => $mroRequestApplication->id])
        ->orderBy(['updated_at' => SORT_DESC])
        ->one()
    : null;
$existingReport = $lastReport !== null;

/**
 * Active advertising content for the right column.
 */
$now = date('Y-m-d H:i:s');
$adverts = \app\models\Advert::find()
    ->where(['status' => 'active'])
    ->andWhere(['<=', 'start_date', $now])
    ->andWhere(['>=', 'end_date', $now])
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
    border-radius: 8px;
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
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-page-action {
    min-height: 40px;
    border-radius: 8px;
    padding: 0 13px;
    font-weight: 800;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    white-space: nowrap;
    text-decoration: none;
    transition: all .2s ease;
}

/* VIEW PO BUTTON BAR 2026: separate workflow actions from page navigation. */
.header-action-group {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    padding: 5px;
    border: 1px solid #DCE6F1;
    border-radius: 8px;
    background: rgba(255,255,255,.72);
    box-shadow: 0 5px 14px rgba(15,23,42,.05);
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

.btn-view-request {
    background: #0f766e;
    color: #ffffff !important;
    border: 1px solid #0f766e;
    box-shadow: 0 8px 16px rgba(15, 118, 110, .22);
}

.btn-view-request:hover {
    background: #115e59;
    border-color: #115e59;
    color: #ffffff !important;
    transform: translateY(-1px);
    box-shadow: 0 12px 22px rgba(15, 118, 110, .28);
    text-decoration: none;
}

/* VIEW PO ACTIONS 2026: header actions reuse the list workflow with readable labels. */
.btn-view-answer { background: #fff; color: #3730A3 !important; border: 1px solid #C7D2FE; }
.btn-accept-po { background: #15803D; color: #fff !important; border: 1px solid #15803D; }
.btn-start-work { background: #0369A1; color: #fff !important; border: 1px solid #0369A1; }
.btn-report { background: #7C3AED; color: #fff !important; border: 1px solid #7C3AED; }
.btn-contact-mro { background: #fff; color: #0E7490 !important; border: 1px solid #A5F3FC; }
.btn-appointment { background: #fff; color: #92400E !important; border: 1px solid #FDE68A; }
.btn-cancel-application { background: #fff; color: #B91C1C !important; border: 1px solid #FCA5A5; }

.btn-accept-po { order: -1; box-shadow: 0 7px 15px rgba(21,128,61,.18); }

.btn-accept-po:hover,
.btn-start-work:hover,
.btn-report:hover {
    color: #fff !important;
    filter: brightness(.90);
    transform: translateY(-1px);
    text-decoration: none;
}

.btn-view-answer:hover,
.btn-contact-mro:hover,
.btn-appointment:hover {
    background: #F8FAFC;
    color: #0D3261 !important;
    border-color: #93C5FD;
    transform: translateY(-1px);
    text-decoration: none;
}

.btn-cancel-application:hover {
    background: #FEF2F2;
    color: #991B1B !important;
    transform: translateY(-1px);
    text-decoration: none;
}

/* VIEW PO SWEETALERT 2026: smaller dialog, bold decision text and icon buttons. */
.swal2-popup.view-po-swal {
    width: 380px !important;
    max-width: calc(100vw - 28px) !important;
    padding: 18px 20px !important;
    border-radius: 8px !important;
    box-shadow: 0 22px 55px rgba(15,23,42,.24) !important;
}

.view-po-swal .swal2-icon {
    width: 54px !important;
    height: 54px !important;
    margin: 7px auto 12px !important;
}

.view-po-swal-title {
    padding: 0 !important;
    color: #0F172A !important;
    font-size: 20px !important;
    font-weight: 900 !important;
}

.view-po-swal-message {
    margin: 9px 0 2px !important;
    color: #334155 !important;
    font-size: 13px !important;
    font-weight: 800 !important;
    line-height: 1.5 !important;
}

.view-po-swal-confirm,
.view-po-swal-cancel {
    min-height: 38px;
    padding: 0 15px;
    border: 0;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 900;
}

.view-po-swal-confirm {
    background: #15803D;
    color: #FFFFFF;
}

.view-po-swal-confirm.is-danger {
    background: #DC2626;
}

.view-po-swal-cancel {
    margin-right: 8px;
    background: #E2E8F0;
    color: #334155;
}

.view-grid {
    display: grid;
    grid-template-columns: 1.3fr 0.7fr;
    gap: 18px;
}

.content-card {
    background: #ffffff;
    border-radius: 8px;
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
    border-radius: 8px;
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
    min-width: 0;
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

.request-id-badge,
.status-badge,
.aircraft-badge,
.registration-badge,
.serial-badge,
.date-badge,
.location-badge,
.po-count-badge,
.empty-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
    text-decoration: none;
}

.request-id-badge {
    background: #eef2ff;
    color: #4338ca;
}

/* VIEW PO CANONICAL ID 2026: compact signed reference displayed instead of a numeric ID. */
.public-id-badge {
    display: inline-flex;
    max-width: 180px;
    padding: 5px 9px;
    border: 1px solid #C7D2FE;
    border-radius: 8px;
    background: #EEF2FF;
    color: #3730A3;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 11px;
    font-weight: 850;
    overflow-wrap: anywhere;
}

.status-badge {
    background: #e5e7eb;
    color: #374151;
}

.status-default {
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

.date-badge {
    background: #eef2ff;
    color: #4338ca;
}

.location-badge {
    background: #fff7ed;
    color: #c2410c;
}

.po-count-badge {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #bbf7d0;
}

.empty-badge {
    background: #f8fafc;
    color: #94a3b8;
    border: 1px solid #e5e7eb;
}

.po-list {
    display: grid;
    gap: 12px;
}

.po-item {
    background: #ffffff;
    border: 1px solid #e5eaf3;
    border-radius: 8px;
    padding: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    box-shadow: 0 5px 14px rgba(15, 23, 42, 0.05);
}

.po-main {
    min-width: 0;
}

.po-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0f172a;
    font-size: 14px;
    font-weight: 900;
    overflow-wrap: anywhere;
}

.po-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 8px;
}

.po-download {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border-radius: 8px;
    padding: 10px 14px;
    background: #f3a846ff;
    color: #ffffff !important;
    font-size: 13px;
    font-weight: 900;
    text-decoration: none;
    border: 1px solid rgba(14, 165, 233, .2);
    box-shadow: 0 8px 16px rgba(14, 165, 233, .18);
    transition: all .2s ease;
}

.po-download:hover {
    background: #0284c7;
    color: #ffffff !important;
    text-decoration: none;
    transform: translateY(-1px);
}

.request-info-box {
    display: block;
    width: 100%;
    min-height: 110px;
    line-height: 1.65;
    font-weight: 700;
    color: #334155;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    padding: 12px;
    resize: vertical;
}

.request-summary-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 18px;
}

.request-summary-strip .detail-item {
    padding: 12px;
    background: #ffffff;
    box-shadow: 0 5px 14px rgba(15, 23, 42, .045);
}

.request-summary-strip .detail-label {
    margin-bottom: 5px;
    font-size: 10px;
}

.request-summary-strip .detail-value {
    font-size: 13px;
}

.po-table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #e5eaf3;
    border-radius: 8px;
}

.po-document-table {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
    background: #ffffff;
}

.po-document-table th {
    padding: 12px 14px;
    border-bottom: 1px solid #dbe5f0;
    background: #f1f5f9;
    color: #475569;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .045em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.po-document-table td {
    padding: 13px 14px;
    border-bottom: 1px solid #e8eef5;
    color: #334155;
    font-size: 12px;
    font-weight: 700;
    vertical-align: middle;
}

.po-document-table tbody tr:last-child td {
    border-bottom: 0;
}

.po-document-table tbody tr:hover {
    background: #f8fbff;
}

.po-document-name {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 170px;
    max-width: 280px;
}

.po-document-name i {
    flex: 0 0 auto;
    color: #2563eb;
    font-size: 17px;
}

.po-document-name span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.po-document-actions {
    display: flex;
    align-items: center;
    gap: 7px;
    white-space: nowrap;
}

.po-document-action {
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    background: #ffffff;
    color: #334155 !important;
    font-size: 11px;
    font-weight: 900;
    text-decoration: none;
}

.po-document-action:hover {
    border-color: #0ea5e9;
    color: #0369a1 !important;
    text-decoration: none;
}

.po-document-action.download {
    border-color: #0f766e;
    background: #0f766e;
    color: #ffffff !important;
}

.po-document-action.download:hover {
    border-color: #115e59;
    background: #115e59;
    color: #ffffff !important;
}

.request-details-accordion {
    margin-top: 18px;
    overflow: hidden;
    border: 1px solid #dbe5f0;
    border-radius: 8px;
    background: #ffffff;
}

/* VIEW PO REQUEST INFO 2026: static heading keeps request information visible. */
.request-details-heading {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px;
    color: #0F172A;
    font-size: 13px;
    font-weight: 900;
    background: #F8FAFC;
}

.request-details-accordion summary {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 13px 15px;
    cursor: pointer;
    color: #0f172a;
    background: #f8fafc;
    font-size: 13px;
    font-weight: 900;
    list-style: none;
}

.request-details-accordion summary::-webkit-details-marker {
    display: none;
}

.request-details-accordion summary::after {
    content: '+';
    margin-left: auto;
    color: #64748b;
    font-size: 18px;
}

.request-details-accordion[open] summary::after {
    content: '−';
}

.request-details-content {
    padding: 14px;
    border-top: 1px solid #e5eaf3;
}

.quick-actions {
    display: grid;
    gap: 10px;
}

.quick-action-btn {
    width: 100%;
    min-height: 44px;
    border-radius: 8px;
    padding: 11px 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 900;
    color: #ffffff !important;
    text-decoration: none;
    border: 1px solid rgba(15, 23, 42, .12);
    box-shadow: 0 8px 18px rgba(15, 23, 42, .12);
    transition: all .2s ease;
}

.quick-action-btn:hover {
    color: #ffffff !important;
    text-decoration: none;
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(15, 23, 42, .16);
}

.qa-back {
    background: #64748b;
}

.qa-request {
    background: #0ea5e9;
}

.summary-box {
    display: grid;
    gap: 10px;
}

.summary-item {
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 8px;
    padding: 13px 14px;
}

.summary-label {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #64748b;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 6px;
}

.summary-value {
    color: #0f172a;
    font-size: 14px;
    font-weight: 900;
    overflow-wrap: anywhere;
}

.po-ad-zone {
    position: relative;
    align-self: start;
    width: 100%;
    aspect-ratio: 16 / 9;
    min-width: 0;
    min-height: 0;
    max-height: 430px;
    overflow: hidden;
    border: 1px solid #d4e2f1;
    border-radius: 8px;
    background: #071a31;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .10);
}

.po-ad-slider {
    display: flex;
    width: 100%;
    height: 100%;
    min-height: 0;
    transition: transform .65s cubic-bezier(.22, .61, .36, 1);
}

.po-ad-slide {
    position: relative;
    flex: 0 0 100%;
    min-width: 100%;
    height: 100%;
    min-height: 0;
    overflow: hidden;
    background:
        radial-gradient(circle at center, rgba(14, 165, 233, .16), transparent 62%),
        #071a31;
}

/* VIEW PO ADVERTISING 2026: homepage-style blurred backdrop keeps every format filled. */
.po-ad-backdrop {
    position: absolute;
    inset: -22px;
    z-index: 0;
    background-position: center;
    background-size: cover;
    filter: blur(18px) brightness(.48) saturate(1.15);
    transform: scale(1.10);
    opacity: .78;
}

.po-ad-media {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center center;
    background: #071a31;
    z-index: 1;
}

.po-ad-shade {
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    background: linear-gradient(0deg, rgba(4, 17, 34, .90), rgba(4, 17, 34, .06) 70%);
}

.po-ad-sponsored,
.po-ad-counter {
    position: absolute;
    z-index: 4;
    top: 16px;
    min-height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 0 10px;
    border: 1px solid rgba(255, 255, 255, .30);
    border-radius: 8px;
    color: #ffffff;
    background: rgba(7, 26, 49, .58);
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    backdrop-filter: blur(9px);
}

.po-ad-sponsored { left: 16px; }
.po-ad-counter { right: 16px; }

.po-ad-caption {
    position: absolute;
    z-index: 4;
    left: 20px;
    right: 20px;
    bottom: 48px;
    color: #ffffff;
    text-shadow: 0 2px 10px rgba(0, 0, 0, .35);
}

.po-ad-caption small,
.po-ad-caption strong {
    display: block;
}

.po-ad-caption small {
    margin-bottom: 5px;
    color: #71d3ff;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .10em;
    text-transform: uppercase;
}

.po-ad-caption strong {
    font-size: 22px;
    font-weight: 950;
    line-height: 1.12;
}

.po-ad-nav {
    position: absolute;
    z-index: 6;
    top: 50%;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, .55);
    border-radius: 8px;
    color: #0d3261;
    background: rgba(255, 255, 255, .92);
    transform: translateY(-50%);
}

.po-ad-prev { left: 12px; }
.po-ad-next { right: 12px; }

.po-ad-dots {
    position: absolute;
    z-index: 6;
    left: 50%;
    bottom: 20px;
    display: flex;
    gap: 6px;
    transform: translateX(-50%);
}

.po-ad-dot {
    width: 8px;
    height: 8px;
    padding: 0;
    border: 0;
    border-radius: 8px;
    background: rgba(255, 255, 255, .48);
    transition: width .2s ease, background .2s ease;
}

.po-ad-dot.is-active {
    width: 24px;
    background: #ffffff;
}

.po-ad-empty,
.po-ad-error {
    width: 100%;
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 28px;
    color: #0d3261;
    text-align: center;
    background: linear-gradient(180deg, #f8fbff, #ffffff);
}

.po-ad-empty i,
.po-ad-error i {
    font-size: 34px;
}

.po-ad-empty strong,
.po-ad-error strong {
    color: #0f172a;
    font-size: 15px;
    font-weight: 900;
}

.po-ad-empty small,
.po-ad-error small {
    color: #64748b;
    font-size: 12px;
}

.po-ad-error {
    position: absolute;
    inset: 0;
    z-index: 7;
    display: none;
}

.po-ad-slide.media-error .po-ad-error {
    display: flex;
}

@media (max-width: 992px) {
    .requests-page {
        padding: 14px;
    }

    .page-header-card {
        padding: 18px;
    }

    .header-actions,
    .header-action-group {
        width: 100%;
        justify-content: flex-start;
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

    .request-summary-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .po-ad-zone {
        aspect-ratio: 16 / 9;
        max-height: 430px;
    }
}

@media (max-width: 600px) {
    .header-action-group .btn-page-action {
        flex: 1 1 132px;
    }

}

@media (max-width: 576px) {
    .requests-page {
        padding: 10px;
    }

    .page-header-card,
    .content-card {
        border-radius: 8px;
        padding: 16px;
    }

    .header-actions {
        width: 100%;
    }

    .btn-page-action {
        width: 100%;
        justify-content: center;
    }

    .po-item {
        align-items: stretch;
        flex-direction: column;
    }

    .po-download {
        width: 100%;
    }

    .po-ad-zone {
        aspect-ratio: 16 / 9;
        max-height: none;
    }

    .request-summary-strip {
        grid-template-columns: 1fr;
    }

    .po-table-wrap {
        overflow: visible;
        border: 0;
    }

    .po-document-table,
    .po-document-table tbody,
    .po-document-table tr,
    .po-document-table td {
        display: block;
        width: 100%;
    }

    .po-document-table thead {
        display: none;
    }

    .po-document-table tr {
        margin-bottom: 12px;
        overflow: hidden;
        border: 1px solid #e5eaf3;
        border-radius: 8px;
        background: #ffffff;
    }

    .po-document-table td {
        display: grid;
        grid-template-columns: 105px minmax(0, 1fr);
        gap: 10px;
        padding: 10px 12px;
    }

    .po-document-table td::before {
        content: attr(data-label);
        color: #64748b;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .035em;
        text-transform: uppercase;
    }

    .po-document-name {
        min-width: 0;
        max-width: 100%;
    }

    .po-document-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .po-document-action {
        width: 100%;
    }

    .date-row {
        grid-template-columns: 1fr;
    }

    .request-id-badge,
    .status-badge,
    .aircraft-badge,
    .registration-badge,
    .serial-badge,
    .date-badge,
    .location-badge,
    .po-count-badge,
    .empty-badge {
        white-space: normal;
    }
}
CSS);
?>

<!-- SHARED DETAIL SYSTEM: presentation only; PO workflow remains unchanged. -->
<main class="dash-content requests-page can-detail-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title fw-bold">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-file-earmark-check"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Purchase Orders linked to this application and its maintenance request.
                </div>
            </div>

            <div class="header-actions">
                <!-- VIEW PO ACTIONS 2026: same actions and status rules as /mro-applications. -->
                <div class="header-action-group workflow-actions">
                <?php if ($encodedApplicationId): ?>
                    <?= Html::a(
                        '<i class="bi bi-eye"></i> View Answer',
                        ['view-answer', 'id' => $encodedApplicationId],
                        ['class' => 'btn-page-action btn-view-answer']
                    ) ?>

                    <?php if ($status === 'po_loaded' && $hasPo): ?>
                        <?= Html::a(
                            '<i class="bi bi-check-circle"></i> Accept PO',
                            ['accept-po', 'id' => $encodedApplicationId],
                            [
                                'class' => 'btn-page-action btn-accept-po js-view-po-confirm',
                                'data' => [
                                    'confirm-action' => 'accept',
                                    'confirm-title' => 'Accept Purchase Order?',
                                    'confirm-message' => 'This confirms the PO and moves the request to work acceptance.',
                                    'confirm-button' => 'Yes, accept PO',
                                ],
                            ]
                        ) ?>
                    <?php endif; ?>

                    <?php if ($status === 'work_accepted' && $encodedRequestId): ?>
                        <!--
                            CONFIRMATION START WORK : ce lien utilise le même gestionnaire
                            SweetAlert que les autres transitions de cette page. Les données
                            décrivent uniquement la présentation ; la soumission POST sécurisée
                            avec le jeton CSRF reste effectuée par le script commun ci-dessous.
                        -->
                        <?= Html::a(
                            '<i class="bi bi-play-circle"></i> Start Work',
                            ['start-work', 'id' => $encodedRequestId],
                            [
                                'class' => 'btn-page-action btn-start-work js-view-po-confirm',
                                'data' => [
                                    'confirm-action' => 'start',
                                    'confirm-title' => 'Start maintenance work?',
                                    'confirm-message' => 'This confirms that maintenance work is starting and advances the request workflow.',
                                    'confirm-button' => 'Yes, start work',
                                ],
                            ]
                        ) ?>
                    <?php endif; ?>

                    <?php if (
                        in_array($status, ['work_started', 'report_submitted'], true)
                        && (!$existingReport || ($lastReport && (int) $lastReport->quote_approved === 0))
                    ): ?>
                        <?= Html::a(
                            '<i class="bi bi-upload"></i> Upload CRS',
                            ['submit-report', 'id' => $encodedApplicationId],
                            ['class' => 'btn-page-action btn-report']
                        ) ?>
                    <?php endif; ?>

                    <?php if ($existingReport): ?>
                        <?= Html::a(
                            '<i class="bi bi-file-text"></i> View CRS',
                            ['view-reports', 'id' => $encodedApplicationId],
                            ['class' => 'btn-page-action btn-report']
                        ) ?>
                    <?php endif; ?>

                    <?php if ($status !== 'closed'): ?>
                        <?= Html::a(
                            '<i class="bi bi-envelope"></i> Contact',
                            ['contact', 'id' => $encodedApplicationId],
                            ['class' => 'btn-page-action btn-contact-mro']
                        ) ?>
                        <?= Html::a(
                            '<i class="bi bi-calendar-event"></i> Appointment',
                            ['set-appointment', 'app_request_id' => $encodedApplicationId],
                            ['class' => 'btn-page-action btn-appointment']
                        ) ?>
                    <?php endif; ?>

                    <?php if (in_array($status, ['answered', 'created', 'po_loaded'], true)): ?>
                        <?= Html::a(
                            '<i class="bi bi-x-circle"></i> Cancel',
                            ['cancel-application', 'id' => $encodedApplicationId],
                            [
                                'class' => 'btn-page-action btn-cancel-application js-view-po-confirm',
                                'data' => [
                                    'confirm-action' => 'cancel',
                                    'confirm-title' => 'Cancel application?',
                                    'confirm-message' => 'This removes the application from the current request workflow.',
                                    'confirm-button' => 'Yes, cancel',
                                ],
                            ]
                        ) ?>
                    <?php endif; ?>
                <?php endif; ?>
                </div>

                <!-- VIEW PO BUTTON BAR 2026: navigation remains visually secondary. -->
                <div class="header-action-group navigation-actions">
                <?= Html::a(
                    '<i class="bi bi-clipboard2-check"></i> View Request',
                    $requestViewUrl,
                    ['class' => 'btn-page-action btn-view-request']
                ) ?>

                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back',
                    $backUrl,
                    ['class' => 'btn-page-action btn-back']
                ) ?>
                </div>
            </div>
        </div>

        <!-- Compact operational request summary -->
        <section class="request-summary-strip" aria-label="Current request summary">
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-hash"></i> Request ID</div>
                <div class="detail-value"><span class="request-id-badge">#<?= Html::encode($requestId) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-activity"></i> Status</div>
                <div class="detail-value"><span class="status-badge <?= Html::encode($statusClass) ?>"><i class="bi bi-circle-fill"></i> <?= Html::encode($statusText) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-airplane-engines"></i> Aircraft</div>
                <div class="detail-value"><span class="aircraft-badge"><i class="bi bi-airplane"></i> <?= Html::encode($aircraftName ?: 'N/A') ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-card-text"></i> Registration</div>
                <div class="detail-value"><span class="registration-badge"><i class="bi bi-card-heading"></i> <?= Html::encode($aircraftRegistration) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-upc-scan"></i> Serial Number</div>
                <div class="detail-value"><span class="serial-badge"><i class="bi bi-upc"></i> <?= Html::encode($serialNumber) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-geo-alt"></i> Maintenance Location</div>
                <div class="detail-value"><span class="location-badge"><i class="bi bi-pin-map"></i> <?= Html::encode($locationText) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-calendar-event"></i> ETA</div>
                <div class="detail-value"><span class="date-badge"><i class="bi bi-calendar-event"></i> <?= Html::encode($etaText) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-calendar-check"></i> ETD</div>
                <div class="detail-value"><span class="date-badge"><i class="bi bi-calendar-check"></i> <?= Html::encode($etdText) ?></span></div>
            </div>
        </section>

        <div class="view-grid">

            <!-- Purchase Orders and request details -->
            <div class="content-card">
                <h2 class="section-title">
                    <i class="bi bi-file-earmark-check text-primary"></i>
                    Purchase Orders
                </h2>

                <?php if ($purchaseOrderCount > 0): ?>
                    <div class="po-table-wrap">
                        <table class="po-document-table">
                            <thead>
                                <tr>
                                    <th>PO</th>
                                    <th>Application</th>
                                    <th>Uploaded Date</th>
                                    <th>Document</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($purchaseOrders as $index => $po): ?>
                                    <?php
                                    // VIEW PO CANONICAL ID 2026: never expose the numeric application ID in UI.
                                    $poPublicApplicationId = !empty($po['application_id'])
                                        ? UrlIdHelper::encode($po['application_id'])
                                        : 'N/A';
                                    ?>
                                    <tr>
                                        <td data-label="PO"><span class="po-count-badge">PO #<?= Html::encode($index + 1) ?></span></td>
                                        <td data-label="Application"><span class="public-id-badge"><?= Html::encode($poPublicApplicationId) ?></span></td>
                                        <td data-label="Uploaded Date">
                                            <?= !empty($po['created_at'])
                                                ? Html::encode(date('d M Y', strtotime($po['created_at'])))
                                                : 'N/A' ?>
                                        </td>
                                        <td data-label="Document">
                                            <div class="po-document-name" title="<?= Html::encode($po['filename']) ?>">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                                <span><?= Html::encode($po['filename']) ?></span>
                                            </div>
                                        </td>
                                        <td data-label="Actions">
                                            <?php if (!empty($po['url'])): ?>
                                                <div class="po-document-actions">
                                                    <?= Html::a('<i class="bi bi-eye"></i> View', $po['url'], [
                                                        'class' => 'po-document-action',
                                                        'target' => '_blank',
                                                        'rel' => 'noopener noreferrer',
                                                    ]) ?>
                                                    <?= Html::a('<i class="bi bi-download"></i> Download', $po['url'], [
                                                        'class' => 'po-document-action download',
                                                        'download' => $po['filename'],
                                                    ]) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="empty-badge"><i class="bi bi-file-earmark-x"></i> No file</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <span class="empty-badge">
                        <i class="bi bi-file-earmark-x"></i>
                        No Purchase Orders found
                    </span>
                <?php endif; ?>

                <!-- VIEW PO REQUEST INFO 2026: always displayed; no collapsible control. -->
                <section class="request-details-accordion">
                    <div class="request-details-heading"><i class="bi bi-info-circle"></i> Request Informations</div>
                    <div class="request-details-content">
                        <?= Html::textarea(
                            'request_details_display',
                            !empty($requestDetails) ? $requestDetails : 'No request details available',
                            [
                                'class' => 'request-info-box',
                                'rows' => 4,
                                'readonly' => true,
                                'aria-label' => 'Request Informations',
                            ]
                        ) ?>
                    </div>
                </section>
            </div>

            <!-- SHARED ADVERTISING PANEL: common proportions and responsive behaviour. -->
            <aside class="po-ad-zone can-detail-ad" id="po-ad-zone" aria-label="Sponsored content">
                <?php if (!empty($adverts)): ?>
                    <div class="po-ad-slider" id="po-ad-slider">
                        <?php foreach ($adverts as $index => $advert): ?>
                            <?php
                            $isUrl = !empty($advert->use_url) && !empty($advert->url);
                            $contentUrl = $isUrl
                                ? $advert->url
                                : Yii::getAlias('@web/uploads/') . ltrim((string) $advert->content, '/');
                            $advertType = strtolower(trim((string) $advert->advert_type));
                            $advertTitle = !empty($advert->title) ? $advert->title : 'Aviation partner';
                            ?>
                            <article class="po-ad-slide <?= $index === 0 ? 'is-active' : '' ?>">
                                <!-- VIEW PO ADVERTISING 2026: transparent blurred fill inspired by site/index. -->
                                <?php if ($advertType === 'photo'): ?>
                                    <div class="po-ad-backdrop can-detail-ad-backdrop" style="background-image:url('<?= Html::encode($contentUrl) ?>')" aria-hidden="true"></div>
                                <?php endif; ?>
                                <?php if ($advertType === 'photo'): ?>
                                    <?= Html::img($contentUrl, [
                                        'class' => 'po-ad-media',
                                        'alt' => $advertTitle,
                                        'loading' => $index === 0 ? 'eager' : 'lazy',
                                        'onerror' => "this.closest('.po-ad-slide').classList.add('media-error');",
                                    ]) ?>
                                <?php else: ?>
                                    <video class="po-ad-media" muted loop playsinline preload="metadata" <?= $index === 0 ? 'autoplay' : '' ?>
                                           onerror="this.closest('.po-ad-slide').classList.add('media-error');">
                                        <source src="<?= Html::encode($contentUrl) ?>" type="video/mp4">
                                    </video>
                                <?php endif; ?>

                                <div class="po-ad-shade" aria-hidden="true"></div>
                                <span class="po-ad-sponsored"><i class="bi bi-megaphone"></i> Sponsored</span>
                                <span class="po-ad-counter"><?= (int) ($index + 1) ?> / <?= count($adverts) ?></span>
                                <div class="po-ad-caption">
                                    <small>Core Aviation Network</small>
                                    <strong><?= Html::encode($advertTitle) ?></strong>
                                </div>
                                <div class="po-ad-error">
                                    <i class="bi bi-image"></i>
                                    <strong>Advertisement unavailable</strong>
                                    <small>The advertising media could not be loaded.</small>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($adverts) > 1): ?>
                        <button type="button" class="po-ad-nav po-ad-prev" id="po-ad-prev" aria-label="Previous advertisement">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button type="button" class="po-ad-nav po-ad-next" id="po-ad-next" aria-label="Next advertisement">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                        <div class="po-ad-dots" aria-label="Advertisement navigation">
                            <?php foreach ($adverts as $index => $advert): ?>
                                <button type="button" class="po-ad-dot <?= $index === 0 ? 'is-active' : '' ?>"
                                        data-ad-target="<?= (int) $index ?>"
                                        aria-label="Show advertisement <?= (int) ($index + 1) ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="po-ad-empty">
                        <i class="bi bi-megaphone"></i>
                        <strong>Advertising space available</strong>
                        <small>Promote aviation services to qualified operators and MRO professionals.</small>
                    </div>
                <?php endif; ?>
            </aside>

        </div>

    </div>
</main>

<?php
$this->registerJs(<<<JS
(function () {
    var zone = document.getElementById('po-ad-zone');
    var slider = document.getElementById('po-ad-slider');
    if (!zone || !slider) return;

    var slides = Array.prototype.slice.call(slider.querySelectorAll('.po-ad-slide'));
    var dots = Array.prototype.slice.call(zone.querySelectorAll('.po-ad-dot'));
    var previous = document.getElementById('po-ad-prev');
    var next = document.getElementById('po-ad-next');
    var current = 0;
    var timer = null;

    function show(index) {
        if (!slides.length) return;
        current = (index + slides.length) % slides.length;
        slider.style.transform = 'translate3d(-' + (current * 100) + '%, 0, 0)';

        slides.forEach(function (slide, slideIndex) {
            slide.classList.toggle('is-active', slideIndex === current);
            var video = slide.querySelector('video');
            if (!video) return;
            if (slideIndex === current) {
                var promise = video.play();
                if (promise && typeof promise.catch === 'function') promise.catch(function () {});
            } else {
                video.pause();
            }
        });

        dots.forEach(function (dot, dotIndex) {
            dot.classList.toggle('is-active', dotIndex === current);
        });
    }

    function start() {
        if (slides.length < 2) return;
        window.clearInterval(timer);
        timer = window.setInterval(function () { show(current + 1); }, 7000);
    }

    if (previous) previous.addEventListener('click', function () { show(current - 1); start(); });
    if (next) next.addEventListener('click', function () { show(current + 1); start(); });
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            show(parseInt(dot.getAttribute('data-ad-target'), 10) || 0);
            start();
        });
    });

    zone.addEventListener('mouseenter', function () { window.clearInterval(timer); });
    zone.addEventListener('mouseleave', start);
    show(0);
    start();
})();

/*
 * CONFIRMATIONS MÉTIER HARMONISÉES : Accept PO, Start Work et Cancel utilisent
 * une seule présentation SweetAlert. La fonction submitPost conserve la méthode
 * POST et le jeton CSRF attendus par Yii après la validation de l'utilisateur.
 */
(function () {
    var links = document.querySelectorAll('.js-view-po-confirm');
    if (!links.length) return;

    function submitPost(url) {
        var form = document.createElement('form');
        form.method = 'post';
        form.action = url;
        form.style.display = 'none';

        var csrfParam = window.yii && typeof window.yii.getCsrfParam === 'function'
            ? window.yii.getCsrfParam()
            : (document.querySelector('meta[name="csrf-param"]') || {}).content;
        var csrfToken = window.yii && typeof window.yii.getCsrfToken === 'function'
            ? window.yii.getCsrfToken()
            : (document.querySelector('meta[name="csrf-token"]') || {}).content;

        if (csrfParam && csrfToken) {
            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = csrfParam;
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }

        document.body.appendChild(form);
        form.submit();
    }

    links.forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();

            var action = link.dataset.confirmAction;
            var isCancel = action === 'cancel';
            var isStart = action === 'start';

            /*
             * ICÔNES DES ACTIONS : chaque transition conserve une icône explicite.
             * Start Work utilise « play », Accept PO utilise « check » et Cancel
             * utilise « x ». Le libellé d'annulation reste une action de retour.
             */
            var confirmIcon = isCancel
                ? 'bi-x-circle'
                : (isStart ? 'bi-play-circle' : 'bi-check-circle');

            Swal.fire({
                title: link.dataset.confirmTitle,
                html: '<strong>' + link.dataset.confirmMessage + '</strong>',
                icon: isCancel ? 'warning' : (isStart ? 'info' : 'question'),
                showCancelButton: true,
                reverseButtons: true,
                focusCancel: true,
                buttonsStyling: false,
                confirmButtonText: '<i class="bi ' + confirmIcon + '"></i> ' + link.dataset.confirmButton,
                cancelButtonText: '<i class="bi bi-arrow-left"></i> Keep current state',
                // SHARED DETAIL CONFIRMATION: reuse the common compact dialog presentation.
                customClass: {
                    popup: 'view-po-swal can-detail-swal',
                    title: 'view-po-swal-title',
                    htmlContainer: 'view-po-swal-message',
                    confirmButton: 'view-po-swal-confirm' + (isCancel ? ' is-danger' : ''),
                    cancelButton: 'view-po-swal-cancel'
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    submitPost(link.href);
                }
            });
        });
    });
})();
JS, View::POS_END);
?>

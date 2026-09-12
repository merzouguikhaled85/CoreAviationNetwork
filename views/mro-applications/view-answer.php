<?php

use yii\helpers\Html;
use yii\web\View;
use app\components\UrlIdHelper;


/**
 * This view expects the MRO request application model in $mroRequestApplication.
 */

$this->title = 'View Answer';
$this->params['breadcrumbs'][] = ['label' => 'Applied Requests', 'url' => ['/mro-applications/index']];
$this->params['breadcrumbs'][] = $this->title;

/**
 * Get current user type from session.
 */
$userType = Yii::$app->session->get('user_type');

/**
 * Load related request, AO, MRO and aircraft safely.
 */
$requestModel = $mroRequestApplication->request ?? null;
$ao = $requestModel->aO ?? null;
$mro = $mroRequestApplication->mro ?? null;

$aircraft = $requestModel->aircraft ?? null;

if ($requestModel && !$aircraft && method_exists($requestModel, 'getAircraft')) {
    $aircraft = $requestModel->getAircraft()->one();
}

/**
 * Safe attribute getter.
 * This avoids errors when a field does not exist in the model.
 */
$getSafeAttribute = static function ($model, array $fields, $default = null) {
    if ($model === null) {
        return $default;
    }

    foreach ($fields as $field) {
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
            && isset($model->{$field})
            && $model->{$field} !== ''
        ) {
            return $model->{$field};
        }
    }

    return $default;
};

/**
 * Prepare answer values safely.
 */
$requestId = $mroRequestApplication->request_id ?? 'N/A';
$description = $mroRequestApplication->Description ?? null;
$price = $mroRequestApplication->price ?? null;

/**
 * Prepare price currency safely.
 * If your table has one of these fields, it will be used automatically:
 * currency, devise, price_currency, currency_code, quote_currency.
 * Otherwise USD is used by default.
 */
$currencyCode = $getSafeAttribute($mroRequestApplication, [
    'currency',
    'devise',
    'price_currency',
    'currency_code',
    'quote_currency',
], 'USD');

$currencyCode = strtoupper(trim((string) $currencyCode));

$currencySymbols = [
    'USD' => '$',
    'EUR' => '€',
    'TND' => 'TND',
    'GBP' => '£',
    'CAD' => 'C$',
    'AUD' => 'A$',
];

$currencySymbol = $currencySymbols[$currencyCode] ?? $currencyCode;

/**
 * Format price with currency.
 */
$formattedPrice = 'N/A';

if ($price !== null && $price !== '') {
    $priceValue = is_numeric($price)
        ? number_format((float) $price, 2, '.', ' ')
        : $price;

    if (in_array($currencyCode, ['USD', 'GBP', 'CAD', 'AUD'], true)) {
        $formattedPrice = $currencySymbol . ' ' . $priceValue;
    } else {
        $formattedPrice = $priceValue . ' ' . $currencySymbol;
    }
}

/**
 * Prepare linked request details safely.
 */
$requestStatus = $requestModel->status ?? 'N/A';

$requestStatusText = $requestStatus !== 'N/A'
    ? ucwords(str_replace('_', ' ', $requestStatus))
    : 'N/A';

$requestStatusClass = 'status-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $requestStatus);

$aircraftModel = $aircraft->model ?? 'N/A';
$aircraftManufacturer = $aircraft->manufacturer ?? 'N/A';

$aircraftRegistration = $requestModel->aircraft_registration
    ?? $aircraft->registration_number
    ?? 'N/A';

$serialNumber = $requestModel->serial_number
    ?? $aircraft->serial_number
    ?? 'N/A';

$requestLocation = $requestModel->location ?? 'N/A';

$requestEta = !empty($requestModel->eta)
    ? date('d M Y H:i', strtotime($requestModel->eta))
    : 'N/A';

$requestEtd = !empty($requestModel->etd)
    ? date('d M Y H:i', strtotime($requestModel->etd))
    : 'N/A';

$requestDetails = $requestModel->request_details
    ?? $requestModel->description
    ?? null;

/*
 * CLOSED REQUEST ACTIONS: mirror the useful row actions from closed-requests.
 * The current page already represents "View Answer", so only CRS and feedback
 * navigation is added here, using the same records and signed identifiers.
 */
$hasRepairReport = \app\models\RepairReport::find()
    ->where(['mro_request_apply_id' => $mroRequestApplication->id])
    ->exists();

$hasRequestFeedback = $requestModel
    ? \app\models\Feedback::find()
        ->where(['request_id' => $requestModel->request_id])
        ->exists()
    : false;

$encodedApplicationId = UrlIdHelper::encode($mroRequestApplication->id);
$encodedRequestId = $requestModel
    ? UrlIdHelper::encode($requestModel->request_id)
    : null;

/**
 * Secure back URL.
 * This prevents broken back links and avoids 404 errors.
 */
$backUrl = Yii::$app->request->referrer ?: ['/mro-applications/index'];

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
 * Register Bootstrap Icons.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    ['position' => View::POS_HEAD]
);

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

.view-grid {
    display: grid;
    grid-template-columns: 1.3fr 0.7fr;
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
.user-badge,
.price-badge,
.currency-badge,
.aircraft-badge,
.registration-badge,
.serial-badge,
.date-badge,
.location-badge,
.empty-badge,
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
    text-decoration: none;
}

.request-id-badge {
    background: #eef2ff;
    color: #4338ca;
}

.user-badge {
    background: #f1f5f9;
    color: #334155 !important;
}

.user-badge:hover {
    background: #e2e8f0;
    color: #0f172a !important;
    text-decoration: none;
}

.price-badge {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #bbf7d0;
}

.currency-badge {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
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

.empty-badge {
    background: #f8fafc;
    color: #94a3b8;
    border: 1px solid #e5e7eb;
}

.description-box,
.request-info-box {
    display: block;
    width: 100%;
    min-height: 120px;
    line-height: 1.65;
    font-weight: 700;
    color: #334155;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 12px;
    resize: vertical;
}

.header-actions .qa-profile {
    background: #0f766e;
    color: #ffffff !important;
    border: 1px solid #0f766e;
    box-shadow: 0 8px 16px rgba(15, 118, 110, .20);
}

.header-actions .qa-profile:hover {
    background: #115e59;
    border-color: #115e59;
    color: #ffffff !important;
    transform: translateY(-1px);
    text-decoration: none;
}

/* CLOSED REQUEST ACTIONS: header variants of the list-row CRS and feedback buttons. */
.header-actions .qa-reports {
    background: #65a30d;
    color: #ffffff !important;
    border: 1px solid #65a30d;
    box-shadow: 0 8px 16px rgba(101, 163, 13, .20);
}

.header-actions .qa-reports:hover {
    background: #4d7c0f;
    border-color: #4d7c0f;
    color: #ffffff !important;
    transform: translateY(-1px);
    text-decoration: none;
}

.header-actions .qa-feedback {
    background: #d97706;
    color: #ffffff !important;
    border: 1px solid #d97706;
    box-shadow: 0 8px 16px rgba(217, 119, 6, .20);
}

.header-actions .qa-feedback:hover {
    background: #b45309;
    border-color: #b45309;
    color: #ffffff !important;
    transform: translateY(-1px);
    text-decoration: none;
}

.request-summary-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
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

.request-details-only {
    display: block;
}

.request-details-only > *:not(:last-child) {
    display: none;
}

.request-details-only > .detail-item:last-child {
    padding: 0;
    border: 0;
    background: transparent;
}

.request-details-only > .detail-item:last-child > .detail-label {
    display: none;
}

.answer-ad-zone {
    position: relative;
    align-self: start;
    width: 100%;
    aspect-ratio: 16 / 9;
    min-width: 0;
    overflow: hidden;
    border: 1px solid #d4e2f1;
    border-radius: 18px;
    background: #071a31;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .10);
}

.answer-ad-slider {
    display: flex;
    width: 100%;
    height: 100%;
    transition: transform .65s cubic-bezier(.22, .61, .36, 1);
}

.answer-ad-slide {
    position: relative;
    flex: 0 0 100%;
    min-width: 100%;
    height: 100%;
    overflow: hidden;
    background: #071a31;
}

.answer-ad-media {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    background: #071a31;
}

.answer-ad-sponsored,
.answer-ad-counter {
    position: absolute;
    z-index: 4;
    top: 14px;
    min-height: 29px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 0 10px;
    border: 1px solid rgba(255, 255, 255, .32);
    border-radius: 999px;
    color: #ffffff;
    background: rgba(7, 26, 49, .60);
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
}

.answer-ad-sponsored { left: 14px; }
.answer-ad-counter { right: 14px; }

.answer-ad-nav {
    position: absolute;
    z-index: 5;
    top: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, .55);
    border-radius: 50%;
    color: #0d3261;
    background: rgba(255, 255, 255, .92);
    transform: translateY(-50%);
}

.answer-ad-prev { left: 12px; }
.answer-ad-next { right: 12px; }

.answer-ad-dots {
    position: absolute;
    z-index: 5;
    left: 50%;
    bottom: 16px;
    display: flex;
    gap: 6px;
    transform: translateX(-50%);
}

.answer-ad-dot {
    width: 8px;
    height: 8px;
    padding: 0;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, .48);
}

.answer-ad-dot.is-active {
    width: 24px;
    background: #ffffff;
}

.answer-ad-empty,
.answer-ad-error {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 26px;
    color: #0d3261;
    text-align: center;
    background: linear-gradient(180deg, #f8fbff, #ffffff);
}

.answer-ad-empty i,
.answer-ad-error i { font-size: 34px; }

.answer-ad-empty strong,
.answer-ad-error strong {
    color: #0f172a;
    font-size: 15px;
    font-weight: 900;
}

.answer-ad-empty small,
.answer-ad-error small {
    color: #64748b;
    font-size: 12px;
}

.answer-ad-error {
    position: absolute;
    inset: 0;
    z-index: 7;
    display: none;
}

.answer-ad-slide.media-error .answer-ad-error { display: flex; }

.quick-actions {
    display: grid;
    gap: 10px;
}

.quick-action-btn {
    width: 100%;
    min-height: 44px;
    border-radius: 12px;
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

.qa-profile {
    background: #0ea5e9;
}

.summary-box {
    display: grid;
    gap: 10px;
}

.summary-item {
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
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

    .request-summary-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
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

    .btn-page-action {
        width: 100%;
        justify-content: center;
    }

    .request-summary-strip {
        grid-template-columns: 1fr;
    }

    .request-id-badge,
    .user-badge,
    .price-badge,
    .currency-badge,
    .aircraft-badge,
    .registration-badge,
    .serial-badge,
    .date-badge,
    .location-badge,
    .empty-badge,
    .status-badge {
        white-space: normal;
    }
}
CSS);
?>

<!-- SHARED DETAIL SYSTEM: presentation only; MRO application actions remain local. -->
<main class="dash-content requests-page can-detail-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title fw-bold">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-send-check"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    View the submitted answer details, quoted price and current request context.
                </div>
            </div>

            <div class="header-actions">
                <?php if ($userType === 'ao' && $mro): ?>
                    <?php $encryptedMroId = UrlIdHelper::encode($mro->mro_id); ?>
                    <?= Html::a(
                        '<i class="bi bi-tools"></i> View MRO Profile',
                        ['/mro-profile/view', 'id' => $encryptedMroId],
                        ['class' => 'btn-page-action qa-profile']
                    ) ?>
                <?php endif; ?>

                <!-- CLOSED REQUEST ACTIONS: same conditional actions exposed by the list row. -->
                <?php if ($userType === 'mro' && $hasRepairReport): ?>
                    <?= Html::a(
                        '<i class="bi bi-file-text"></i> View CRSs',
                        ['view-reports', 'id' => $encodedApplicationId],
                        [
                            'class' => 'btn-page-action qa-reports',
                            'title' => 'View submitted CRS documents',
                        ]
                    ) ?>
                <?php endif; ?>

                <?php if (
                    $userType === 'mro'
                    && $requestStatus === 'closed'
                    && $hasRequestFeedback
                    && $encodedRequestId !== null
                ): ?>
                    <?= Html::a(
                        '<i class="bi bi-chat-square-text"></i> View Feedback',
                        ['view-feedback', 'id' => $encodedRequestId],
                        [
                            'class' => 'btn-page-action qa-feedback',
                            'title' => 'View aircraft operator feedback',
                        ]
                    ) ?>
                <?php endif; ?>

                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Applied Requests',
                    $backUrl,
                    ['class' => 'btn-page-action btn-back']
                ) ?>
            </div>
        </div>

        <!-- Compact operational request summary -->
        <section class="request-summary-strip" aria-label="Current request summary">
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-hash"></i> Request ID</div>
                <div class="detail-value"><span class="request-id-badge">#<?= Html::encode($requestId) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-activity"></i> Request Status</div>
                <div class="detail-value"><span class="status-badge <?= Html::encode($requestStatusClass) ?>"><i class="bi bi-circle-fill"></i> <?= Html::encode($requestStatusText) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-building"></i> Manufacturer</div>
                <div class="detail-value"><span class="aircraft-badge"><?= Html::encode($aircraftManufacturer) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-airplane-engines"></i> Aircraft Model</div>
                <div class="detail-value"><span class="aircraft-badge"><?= Html::encode($aircraftModel) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-card-text"></i> Registration</div>
                <div class="detail-value"><span class="registration-badge"><?= Html::encode($aircraftRegistration) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-upc-scan"></i> Serial Number</div>
                <div class="detail-value"><span class="serial-badge"><?= Html::encode($serialNumber) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-calendar-event"></i> ETA</div>
                <div class="detail-value"><span class="date-badge"><?= Html::encode($requestEta) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-calendar-check"></i> ETD</div>
                <div class="detail-value"><span class="date-badge"><?= Html::encode($requestEtd) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><i class="bi bi-geo-alt"></i> Maintenance Location</div>
                <div class="detail-value"><span class="location-badge"><i class="bi bi-pin-map"></i> <?= Html::encode($requestLocation) ?></span></div>
            </div>
        </section>

        <div class="view-grid">

            <!-- Main answer details -->
            <div class="content-card">
                <h2 class="section-title">
                    <i class="bi bi-info-circle text-primary"></i>
                    Answer Details
                </h2>

                <div class="detail-list">

                    <!-- Workflow step -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-clipboard-check"></i>
                            Step
                        </div>

                        <div class="detail-value">
                            <span class="status-badge">
                                <i class="bi bi-circle-fill"></i>
                                MRO Quote Step
                            </span>
                        </div>
                    </div>

                    <!-- Username -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-person-lines-fill"></i>
                            <?= $userType === 'ao' ? 'MRO Username' : 'AO Username' ?>
                        </div>

                        <div class="detail-value">
                            <?php if ($userType === 'mro'): ?>

                                <?php if ($ao): ?>
                                    <span class="user-badge">
                                        <i class="bi bi-building-check"></i>
                                        <?= Html::encode($ao->username) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="empty-badge">
                                        <i class="bi bi-exclamation-circle"></i>
                                        AO Not Exists
                                    </span>
                                <?php endif; ?>

                            <?php elseif ($userType === 'ao'): ?>

                                <?php if ($mro): ?>
                                    <?= Html::a(
                                        '<i class="bi bi-tools"></i>' . Html::encode($mro->username),
                                        ['/mro-profile/view', 'id' => $encryptedMroId],
                                        ['class' => 'user-badge']
                                    ) ?>
                                <?php else: ?>
                                    <span class="empty-badge">
                                        <i class="bi bi-exclamation-circle"></i>
                                        MRO Not Exists
                                    </span>
                                <?php endif; ?>

                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-exclamation-circle"></i>
                                    User Not Exists
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Price with currency -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-cash-coin"></i>
                            Price
                        </div>

                        <div class="detail-value">
                            <?php if ($price !== null && $price !== ''): ?>
                                <span class="price-badge">
                                    <i class="bi bi-cash-stack"></i>
                                    <?= Html::encode($formattedPrice) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-dash-circle"></i>
                                    No price available
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Currency -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-currency-exchange"></i>
                            Currency
                        </div>

                        <div class="detail-value">
                            <span class="currency-badge">
                                <i class="bi bi-wallet2"></i>
                                <?= Html::encode($currencyCode) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="detail-item full-width">
                        <div class="detail-label">
                            <i class="bi bi-chat-left-text"></i>
                            Description
                        </div>

                        <?= Html::textarea(
                            'answer_description_display',
                            !empty($description) ? $description : 'No description available',
                            [
                                'class' => 'description-box',
                                'rows' => 6,
                                'readonly' => true,
                                'aria-label' => 'Quotation description',
                            ]
                        ) ?>
                    </div>

                </div>

                <!-- Current request details -->
                <h2 class="section-title" style="margin-top: 24px;">
                    <i class="bi bi-clipboard2-check text-primary"></i>
                    Request Informations
                </h2>

                <div class="detail-list request-details-only">

                    <!-- Linked request ID -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-hash"></i>
                            Request ID
                        </div>

                        <div class="detail-value">
                            <span class="request-id-badge">
                                #<?= Html::encode($requestId) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Request status -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-activity"></i>
                            Request Status
                        </div>

                        <div class="detail-value">
                            <span class="status-badge <?= Html::encode($requestStatusClass) ?>">
                                <i class="bi bi-circle-fill"></i>
                                <?= Html::encode($requestStatusText) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Aircraft manufacturer -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-building"></i>
                            Manufacturer
                        </div>

                        <div class="detail-value">
                            <span class="aircraft-badge">
                                <i class="bi bi-building-check"></i>
                                <?= Html::encode($aircraftManufacturer) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Aircraft model -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-airplane-engines"></i>
                            Aircraft Model
                        </div>

                        <div class="detail-value">
                            <span class="aircraft-badge">
                                <i class="bi bi-airplane"></i>
                                <?= Html::encode($aircraftModel) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Aircraft registration -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-card-text"></i>
                            Registration
                        </div>

                        <div class="detail-value">
                            <span class="registration-badge">
                                <i class="bi bi-card-heading"></i>
                                <?= Html::encode($aircraftRegistration) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Aircraft serial number -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-upc-scan"></i>
                            Serial Number
                        </div>

                        <div class="detail-value">
                            <span class="serial-badge">
                                <i class="bi bi-upc"></i>
                                <?= Html::encode($serialNumber) ?>
                            </span>
                        </div>
                    </div>

                    <!-- ETA -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-calendar-event"></i>
                            ETA
                        </div>

                        <div class="detail-value">
                            <span class="date-badge">
                                <i class="bi bi-calendar-event"></i>
                                <?= Html::encode($requestEta) ?>
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
                                <?= Html::encode($requestEtd) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Maintenance location -->
                    <div class="detail-item full-width">
                        <div class="detail-label">
                            <i class="bi bi-geo-alt"></i>
                            Maintenance Location
                        </div>

                        <div class="detail-value">
                            <span class="location-badge">
                                <i class="bi bi-pin-map"></i>
                                <?= Html::encode($requestLocation) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Request details -->
                    <div class="detail-item full-width">
                        <div class="detail-label">
                            <i class="bi bi-info-circle"></i>
                            Request Informations
                        </div>

                        <?= Html::textarea(
                            'request_details_display',
                            !empty($requestDetails) ? $requestDetails : 'No request details available',
                            [
                                'class' => 'request-info-box',
                                'rows' => 5,
                                'readonly' => true,
                                'aria-label' => 'Request Informations',
                            ]
                        ) ?>
                    </div>

                </div>
            </div>

            <!-- SHARED ADVERTISING PANEL: common proportions and responsive behaviour. -->
            <aside class="answer-ad-zone can-detail-ad" id="answer-ad-zone" aria-label="Sponsored content">
                <?php if (!empty($adverts)): ?>
                    <div class="answer-ad-slider" id="answer-ad-slider">
                        <?php foreach ($adverts as $index => $advert): ?>
                            <?php
                            $isUrl = !empty($advert->use_url) && !empty($advert->url);
                            $contentUrl = $isUrl
                                ? $advert->url
                                : Yii::getAlias('@web/uploads/') . ltrim((string) $advert->content, '/');
                            $advertType = strtolower(trim((string) $advert->advert_type));
                            ?>
                            <article class="answer-ad-slide <?= $index === 0 ? 'is-active' : '' ?>">
                                <?php if ($advertType === 'photo'): ?>
                                    <div class="can-detail-ad-backdrop" style="background-image:url('<?= Html::encode($contentUrl) ?>')" aria-hidden="true"></div>
                                    <?= Html::img($contentUrl, [
                                        'class' => 'answer-ad-media',
                                        'alt' => 'Sponsored aviation content',
                                        'loading' => $index === 0 ? 'eager' : 'lazy',
                                        'onerror' => "this.closest('.answer-ad-slide').classList.add('media-error');",
                                    ]) ?>
                                <?php else: ?>
                                    <video class="answer-ad-media" muted loop playsinline preload="metadata" <?= $index === 0 ? 'autoplay' : '' ?>
                                           onerror="this.closest('.answer-ad-slide').classList.add('media-error');">
                                        <source src="<?= Html::encode($contentUrl) ?>" type="video/mp4">
                                    </video>
                                <?php endif; ?>

                                <span class="answer-ad-sponsored"><i class="bi bi-megaphone"></i> Sponsored</span>
                                <span class="answer-ad-counter"><?= (int) ($index + 1) ?> / <?= count($adverts) ?></span>
                                <div class="answer-ad-error">
                                    <i class="bi bi-image"></i>
                                    <strong>Advertisement unavailable</strong>
                                    <small>The advertising media could not be loaded.</small>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($adverts) > 1): ?>
                        <button type="button" class="answer-ad-nav answer-ad-prev" id="answer-ad-prev" aria-label="Previous advertisement">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button type="button" class="answer-ad-nav answer-ad-next" id="answer-ad-next" aria-label="Next advertisement">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                        <div class="answer-ad-dots" aria-label="Advertisement navigation">
                            <?php foreach ($adverts as $index => $advert): ?>
                                <button type="button" class="answer-ad-dot <?= $index === 0 ? 'is-active' : '' ?>"
                                        data-ad-target="<?= (int) $index ?>"
                                        aria-label="Show advertisement <?= (int) ($index + 1) ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="answer-ad-empty">
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
    var zone = document.getElementById('answer-ad-zone');
    var slider = document.getElementById('answer-ad-slider');
    if (!zone || !slider) return;

    var slides = Array.prototype.slice.call(slider.querySelectorAll('.answer-ad-slide'));
    var dots = Array.prototype.slice.call(zone.querySelectorAll('.answer-ad-dot'));
    var previous = document.getElementById('answer-ad-prev');
    var next = document.getElementById('answer-ad-next');
    var current = 0;
    var timer = null;

    function show(index) {
        if (!slides.length) return;
        current = (index + slides.length) % slides.length;
        slider.style.transform = 'translate3d(-' + (current * 100) + '%, 0, 0)';

        slides.forEach(function (slide, slideIndex) {
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
JS, View::POS_END);
?>

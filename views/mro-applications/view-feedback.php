<?php

use yii\helpers\Html;
use yii\web\View;
use app\components\UrlIdHelper;

/**
 * This view expects:
 * - $feedback
 * - $request
 */

if (!isset($feedback)) {
    throw new \yii\web\NotFoundHttpException('Feedback not found.');
}

/**
 * Prepare linked request safely.
 */
$requestModel = $request ?? null;

if (
    $requestModel === null
    && method_exists($feedback, 'canGetProperty')
    && $feedback->canGetProperty('request')
) {
    $requestModel = $feedback->request;
}

$requestId = $requestModel->request_id
    ?? $feedback->request_id
    ?? 'N/A';

if ($requestModel === null && $requestId !== 'N/A' && class_exists('\app\models\Requests')) {
    $requestModel = \app\models\Requests::findOne($requestId);
}

/**
 * Page title and breadcrumbs.
 */
$this->title = 'View Feedback';
$this->params['breadcrumbs'][] = ['label' => 'Requests', 'url' => ['/requests/index']];

if ($requestId !== 'N/A') {
    $this->params['breadcrumbs'][] = [
        'label' => 'Request #' . $requestId,
        'url' => ['/requests/view', 'id' => UrlIdHelper::encode($requestId)],
    ];
}

$this->params['breadcrumbs'][] = $this->title;

/**
 * Safe attribute getter.
 * This avoids errors when an optional field does not exist.
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
 * Load related aircraft safely.
 */
$aircraft = null;

if ($requestModel !== null) {
    if (
        method_exists($requestModel, 'canGetProperty')
        && $requestModel->canGetProperty('aircraft')
    ) {
        $aircraft = $requestModel->aircraft;
    }

    if (!$aircraft && method_exists($requestModel, 'getAircraft')) {
        $aircraft = $requestModel->getAircraft()->one();
    }
}

/**
 * Prepare feedback date safely.
 */
$feedbackDate = !empty($feedback->timestamp)
    ? Yii::$app->formatter->asDate($feedback->timestamp)
    : 'N/A';

/**
 * Prepare feedback text safely.
 */
$feedbackText = $feedback->feedback_text ?? null;

/**
 * Prepare ratings safely.
 */
$keptScheduleRating = (int) $getSafeAttribute($feedback, ['kept_to_agreed_schedule_rating'], 0);
$keptCostRating = (int) $getSafeAttribute($feedback, ['kept_to_agreed_cost_rating'], 0);
$communicationRating = (int) $getSafeAttribute($feedback, ['overall_communication_rating'], 0);
$overallRating = (int) $getSafeAttribute($feedback, ['rating'], 0);

/**
 * Prepare linked request details safely.
 */
$requestStatus = $requestModel->status ?? 'N/A';

$requestStatusText = $requestStatus !== 'N/A'
    ? ucwords(str_replace('_', ' ', $requestStatus))
    : 'N/A';

$requestStatusClass = $requestStatus !== 'N/A'
    ? 'status-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $requestStatus)
    : 'status-default';

$aircraftManufacturer = $aircraft->manufacturer ?? 'N/A';
$aircraftModel = $aircraft->model ?? 'N/A';

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

/**
 * REQUEST CONTEXT: expose the AO / CAMO company without changing request data.
 */
$aircraftOperator = null;

if ($requestModel !== null && method_exists($requestModel, 'getAO')) {
    $aircraftOperator = $requestModel->getAO()->one();
}

$operatorName = $getSafeAttribute(
    $aircraftOperator,
    ['company_name', 'username'],
    'N/A'
);

/**
 * ADVERTISING: load campaigns that are active for the current date.
 * Empty start/end dates are treated as open-ended campaign dates.
 */
$now = date('Y-m-d H:i:s');
$adverts = \app\models\Advert::find()
    ->where(['status' => 'active'])
    ->andWhere(['or', ['start_date' => null], ['<=', 'start_date', $now]])
    ->andWhere(['or', ['end_date' => null], ['>=', 'end_date', $now]])
    ->orderBy(['advert_id' => SORT_DESC])
    ->all();

/**
 * Prepare safe back URL.
 */
$backUrl = Yii::$app->request->referrer;

if (!$backUrl && $requestId !== 'N/A') {
    $backUrl = ['/requests/view', 'id' => UrlIdHelper::encode($requestId)];
}

if (!$backUrl) {
    $backUrl = ['/requests/index'];
}

/**
 * Prepare request view URL.
 */
$requestViewUrl = $requestId !== 'N/A'
    ? ['/requests/view', 'id' => UrlIdHelper::encode($requestId)]
    : ['/requests/index'];

/**
 * Register Bootstrap Icons.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['position' => View::POS_HEAD]
);

/**
 * Display stars safely.
 */
$displayStars = function ($rating) {
    $rating = (int) $rating;
    $rating = max(0, min(5, $rating));

    $stars = '';

    for ($i = 1; $i <= $rating; $i++) {
        $stars .= '<span>&#x2605;</span>';
    }

    for ($i = $rating + 1; $i <= 5; $i++) {
        $stars .= '<span>&#x2606;</span>';
    }

    return '<div class="stars">' . $stars . '</div>';
};

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

/* HEADER ACTIONS: keep navigation actions visible and visually distinct. */
.btn-request {
    color: #ffffff !important;
    border: 1px solid #0284c7;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    box-shadow: 0 7px 16px rgba(2, 132, 199, .22);
}

.btn-request:hover {
    color: #ffffff !important;
    border-color: #0369a1;
    background: linear-gradient(135deg, #0284c7, #0369a1);
    box-shadow: 0 10px 22px rgba(2, 132, 199, .30);
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
.date-badge,
.aircraft-badge,
.registration-badge,
.serial-badge,
.location-badge,
.empty-badge,
.status-badge,
.rating-badge {
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

.date-badge {
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

.location-badge {
    background: #fff7ed;
    color: #c2410c;
}

.rating-badge {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
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

.empty-badge {
    background: #f8fafc;
    color: #94a3b8;
    border: 1px solid #e5e7eb;
}

.feedback-text-box,
.request-info-box {
    display: block;
    width: 100%;
    min-height: 118px;
    line-height: 1.65;
    font-weight: 700;
    font-family: inherit;
    font-size: 14px;
    color: #334155;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 12px;
    resize: vertical;
    cursor: default;
    overflow: auto;
}

.feedback-text-box:focus,
.request-info-box:focus {
    border-color: #94a3b8;
    outline: none;
    box-shadow: 0 0 0 3px rgba(148, 163, 184, .12);
}

.rating-table-wrapper {
    border-radius: 18px;
    border: 1px solid #e5eaf3;
    background: #ffffff;
    overflow: hidden;
}

.rating-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.rating-table thead th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 900;
    color: #64748b;
    background: #f1f5f9;
    border-bottom: 1px solid #e5eaf3;
    padding: 12px 14px;
}

.rating-table tbody td {
    font-size: 14px;
    color: #0f172a;
    padding: 14px;
    vertical-align: middle;
    border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}

.rating-table tbody tr:last-child td {
    border-bottom: none;
}

.rating-table tbody tr:hover {
    background: #f8fafc;
}

.rating-object {
    font-weight: 800;
}

.rating-overall-row {
    background: #f9fafb;
}

.rating-overall-row .rating-object {
    color: #047857;
    font-weight: 900;
}

.stars {
    font-size: 25px;
    line-height: 1.1;
    white-space: nowrap;
}

.stars span {
    color: #f59e0b;
    margin-right: 2px;
    text-shadow: 0 2px 6px rgba(245, 158, 11, 0.22);
}

.rating-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 70px;
    padding: 6px 10px;
    border-radius: 999px;
    background: #eef2ff;
    color: #4338ca;
    border: 1px solid #c7d2fe;
    font-weight: 900;
    font-size: 13px;
}

.rating-pill.overall {
    background: #ecfdf5;
    color: #047857;
    border-color: #bbf7d0;
}

/* ADVERTISING: responsive 16:9 frame; contain preserves the complete media. */
.feedback-ad-zone {
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

.feedback-ad-slider {
    display: flex;
    width: 100%;
    height: 100%;
    transition: transform .65s cubic-bezier(.22, .61, .36, 1);
}

.feedback-ad-slide {
    position: relative;
    flex: 0 0 100%;
    min-width: 100%;
    height: 100%;
    overflow: hidden;
    background: #071a31;
}

.feedback-ad-media {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    background: #071a31;
}

.feedback-ad-sponsored,
.feedback-ad-counter {
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

.feedback-ad-sponsored { left: 14px; }
.feedback-ad-counter { right: 14px; }

.feedback-ad-nav {
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

.feedback-ad-prev { left: 12px; }
.feedback-ad-next { right: 12px; }

.feedback-ad-dots {
    position: absolute;
    z-index: 5;
    left: 50%;
    bottom: 16px;
    display: flex;
    gap: 6px;
    transform: translateX(-50%);
}

.feedback-ad-dot {
    width: 8px;
    height: 8px;
    padding: 0;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, .48);
}

.feedback-ad-dot.is-active {
    width: 24px;
    background: #ffffff;
}

.feedback-ad-empty,
.feedback-ad-error {
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

.feedback-ad-empty i,
.feedback-ad-error i { font-size: 34px; }

.feedback-ad-empty strong,
.feedback-ad-error strong {
    color: #0f172a;
    font-size: 15px;
    font-weight: 900;
}

.feedback-ad-empty small,
.feedback-ad-error small {
    color: #64748b;
    font-size: 12px;
}

.feedback-ad-error {
    position: absolute;
    inset: 0;
    z-index: 7;
    display: none;
}

.feedback-ad-slide.media-error .feedback-ad-media { display: none; }
.feedback-ad-slide.media-error .feedback-ad-error { display: flex; }

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
}

@media (max-width: 768px) {
    .rating-table thead {
        display: none;
    }

    .rating-table tbody tr {
        display: block;
        border-bottom: 1px solid #e5eaf3;
        padding: 10px 0;
    }

    .rating-table tbody tr:last-child {
        border-bottom: none;
    }

    .rating-table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        border-bottom: none;
        padding: 8px 12px;
    }

    .rating-table tbody td::before {
        content: attr(data-label);
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #64748b;
        flex: 0 0 38%;
    }

    .stars {
        font-size: 21px;
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

    .request-id-badge,
    .date-badge,
    .aircraft-badge,
    .registration-badge,
    .serial-badge,
    .location-badge,
    .empty-badge,
    .status-badge,
    .rating-badge {
        white-space: normal;
    }
}
CSS);
?>

<!-- SHARED DETAIL SYSTEM: presentation only; feedback data remains read-only. -->
<main class="dash-content requests-page can-detail-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title fw-bold">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-star-half"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Detailed evaluation for this maintenance request: schedule, cost, communication and overall rating.
                </div>
            </div>

            <div class="header-actions">
                <!-- HEADER ACTIONS: primary context link moved out of Quick Actions. -->
                <?php if ($requestId !== 'N/A'): ?>
                    <?= Html::a(
                        '<i class="bi bi-clipboard2-check"></i> View Request',
                        $requestViewUrl,
                        ['class' => 'btn-page-action btn-request']
                    ) ?>
                <?php endif; ?>

                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back',
                    $backUrl,
                    ['class' => 'btn-page-action btn-back']
                ) ?>
            </div>
        </div>

        <div class="view-grid">

            <!-- Main feedback details -->
            <div class="content-card">
                <h2 class="section-title">
                    <i class="bi bi-info-circle text-primary"></i>
                    Feedback Details
                </h2>

                <div class="detail-list">

                    <!-- Feedback date -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-calendar-check"></i>
                            Feedback Date
                        </div>

                        <div class="detail-value">
                            <span class="date-badge">
                                <i class="bi bi-calendar-check"></i>
                                <?= Html::encode($feedbackDate) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Overall rating -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-star-fill"></i>
                            Overall Rating
                        </div>

                        <div class="detail-value">
                            <span class="rating-badge">
                                <i class="bi bi-star-fill"></i>
                                <?= Html::encode($overallRating) ?> / 5
                            </span>
                        </div>
                    </div>

                    <!-- Feedback summary -->
                    <div class="detail-item full-width">
                        <div class="detail-label">
                            <i class="bi bi-chat-left-text"></i>
                            Feedback Summary
                        </div>

                        <!-- READ-ONLY TEXT: long feedback remains visible without becoming editable. -->
                        <?= Html::textarea(
                            'feedback_summary_display',
                            !empty($feedbackText) ? $feedbackText : 'No feedback summary available',
                            [
                                'class' => 'feedback-text-box',
                                'rows' => 5,
                                'readonly' => true,
                                'aria-label' => 'Feedback Summary',
                            ]
                        ) ?>
                    </div>

                </div>

                <!-- Rating table -->
                <h2 class="section-title" style="margin-top: 24px;">
                    <i class="bi bi-clipboard-data text-primary"></i>
                    Rating Breakdown
                </h2>

                <div class="rating-table-wrapper">
                    <table class="table rating-table">
                        <thead>
                            <tr>
                                <th>Object</th>
                                <th style="width: 260px;">Stars</th>
                                <th style="width: 140px;">Rating</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td data-label="Object" class="rating-object">
                                    Kept to Agreed Schedule Rating
                                </td>
                                <td data-label="Stars">
                                    <?= $displayStars($keptScheduleRating) ?>
                                </td>
                                <td data-label="Rating">
                                    <span class="rating-pill">
                                        <?= Html::encode($keptScheduleRating) ?> / 5
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td data-label="Object" class="rating-object">
                                    Kept to Agreed Cost Rating
                                </td>
                                <td data-label="Stars">
                                    <?= $displayStars($keptCostRating) ?>
                                </td>
                                <td data-label="Rating">
                                    <span class="rating-pill">
                                        <?= Html::encode($keptCostRating) ?> / 5
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td data-label="Object" class="rating-object">
                                    Overall Communication Rating
                                </td>
                                <td data-label="Stars">
                                    <?= $displayStars($communicationRating) ?>
                                </td>
                                <td data-label="Rating">
                                    <span class="rating-pill">
                                        <?= Html::encode($communicationRating) ?> / 5
                                    </span>
                                </td>
                            </tr>

                            <tr class="rating-overall-row">
                                <td data-label="Object" class="rating-object">
                                    Overall Rating
                                </td>
                                <td data-label="Stars">
                                    <?= $displayStars($overallRating) ?>
                                </td>
                                <td data-label="Rating">
                                    <span class="rating-pill overall">
                                        <?= Html::encode($overallRating) ?> / 5
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Current request details -->
                <h2 class="section-title" style="margin-top: 24px;">
                    <i class="bi bi-clipboard2-check text-primary"></i>
                    Current Request Details
                </h2>

                <div class="detail-list">

                    <!-- REQUEST DETAILS: request identity is displayed only in this section. -->
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

                    <!-- AO / CAMO operator context -->
                    <div class="detail-item full-width">
                        <div class="detail-label">
                            <i class="bi bi-buildings"></i>
                            Aircraft Operator / CAMO
                        </div>

                        <div class="detail-value">
                            <span class="aircraft-badge">
                                <i class="bi bi-building-check"></i>
                                <?= Html::encode($operatorName) ?>
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

                        <!-- READ-ONLY TEXT: preserve multiline request information in a textarea. -->
                        <?= Html::textarea(
                            'request_information_display',
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

            <!-- SHARED ADVERTISING PANEL: replaces Quick Actions and adapts every media format. -->
            <aside class="feedback-ad-zone can-detail-ad" id="feedback-ad-zone" aria-label="Sponsored content">
                <?php if (!empty($adverts)): ?>
                    <div class="feedback-ad-slider" id="feedback-ad-slider">
                        <?php foreach ($adverts as $index => $advert): ?>
                            <?php
                            $storedContent = ltrim(str_replace(chr(92), '/', trim((string) $advert->content)), '/');
                            $isUrl = !empty($advert->use_url) && !empty($advert->url);
                            $contentUrl = $isUrl
                                ? trim((string) $advert->url)
                                : (strpos($storedContent, 'uploads/') === 0
                                    ? Yii::getAlias('@web/') . $storedContent
                                    : Yii::getAlias('@web/uploads/') . $storedContent);
                            $advertType = strtolower(trim((string) $advert->advert_type));
                            ?>
                            <article class="feedback-ad-slide <?= $index === 0 ? 'is-active' : '' ?>">
                                <?php if ($advertType === 'photo'): ?>
                                    <div class="can-detail-ad-backdrop" style="background-image:url('<?= Html::encode($contentUrl) ?>')" aria-hidden="true"></div>
                                    <?= Html::img($contentUrl, [
                                        'class' => 'feedback-ad-media',
                                        'alt' => 'Sponsored aviation content',
                                        'loading' => $index === 0 ? 'eager' : 'lazy',
                                        'onerror' => "this.closest('.feedback-ad-slide').classList.add('media-error');",
                                    ]) ?>
                                <?php else: ?>
                                    <video class="feedback-ad-media" muted loop playsinline preload="metadata" <?= $index === 0 ? 'autoplay' : '' ?>
                                           onerror="this.closest('.feedback-ad-slide').classList.add('media-error');">
                                        <source src="<?= Html::encode($contentUrl) ?>" type="video/mp4">
                                    </video>
                                <?php endif; ?>

                                <span class="feedback-ad-sponsored"><i class="bi bi-megaphone"></i> Sponsored</span>
                                <span class="feedback-ad-counter"><?= (int) ($index + 1) ?> / <?= count($adverts) ?></span>
                                <div class="feedback-ad-error">
                                    <i class="bi bi-image"></i>
                                    <strong>Advertisement unavailable</strong>
                                    <small>The advertising media could not be loaded.</small>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($adverts) > 1): ?>
                        <button type="button" class="feedback-ad-nav feedback-ad-prev" id="feedback-ad-prev" aria-label="Previous advertisement">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button type="button" class="feedback-ad-nav feedback-ad-next" id="feedback-ad-next" aria-label="Next advertisement">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                        <div class="feedback-ad-dots" aria-label="Advertisement navigation">
                            <?php foreach ($adverts as $index => $advert): ?>
                                <button type="button" class="feedback-ad-dot <?= $index === 0 ? 'is-active' : '' ?>"
                                        data-ad-target="<?= (int) $index ?>"
                                        aria-label="Show advertisement <?= (int) ($index + 1) ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="feedback-ad-empty">
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
/* ADVERTISING: carousel navigation and automatic rotation; paused on hover. */
$this->registerJs(<<<JS
(function () {
    var zone = document.getElementById('feedback-ad-zone');
    var slider = document.getElementById('feedback-ad-slider');
    if (!zone || !slider) return;

    var slides = Array.prototype.slice.call(slider.querySelectorAll('.feedback-ad-slide'));
    var dots = Array.prototype.slice.call(zone.querySelectorAll('.feedback-ad-dot'));
    var previous = document.getElementById('feedback-ad-prev');
    var next = document.getElementById('feedback-ad-next');
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

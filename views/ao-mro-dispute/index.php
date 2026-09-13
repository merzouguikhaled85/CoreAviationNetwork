<?php

/** @var yii\web\View $this */
/** @var \yii\data\Pagination|null $pagination */
/** @var array $disputes */

use app\components\UrlIdHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Disputes';

// Current search keyword from GET.
// Keep the same `search` parameter if the controller already supports filtering.
$searchQuery = trim((string) Yii::$app->request->get('search', ''));

// Bootstrap Icons and SweetAlert2 assets.
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');
$this->registerCssFile('https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css');
$this->registerCssFile('https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', [
    'position' => $this::POS_END,
]);

// Custom page design: same CSS/HTML structure used by the request pages.
$this->registerCss(<<<CSS
    /* Prevent horizontal page scroll */
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
        max-width: 100%;
    }

    .header-title-group {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .header-icon {
        width: 52px;
        height: 52px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e0f2fe;
        color: #0369a1;
        font-size: 25px;
        box-shadow: 0 8px 18px rgba(14, 165, 233, 0.18);
        flex: 0 0 auto;
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
        line-height: 1.5;
    }

    .content-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5eaf3;
        max-width: 100%;
        overflow-x: hidden;
    }

    .table-responsive-custom {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        border-radius: 14px;
    }

    .requests-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: auto;
    }

    .requests-table thead th {
        background: #f1f5f9;
        color: #334155;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 800;
        padding: 13px 8px;
        border: none;
        white-space: nowrap;
        vertical-align: middle;
    }

    .requests-table thead th:first-child {
        border-top-left-radius: 12px;
    }

    .requests-table thead th:last-child {
        border-top-right-radius: 12px;
    }

    .requests-table tbody td {
        padding: 13px 8px;
        vertical-align: middle;
        border-top: 1px solid #eef2f7;
        color: #374151;
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 180px;
        background: #ffffff;
    }

    .requests-table tbody tr {
        transition: background .2s ease;
    }

    .requests-table tbody tr:hover td {
        background: #f8fbff;
    }

    .requests-table td.description-cell,
    .requests-table td.response-cell {
        max-width: 260px;
    }

    .request-link {
        font-weight: 800;
        color: #2563eb;
        text-decoration: none;
    }

    .request-link:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }

    .mro-link {
        color: #0f766e;
        font-weight: 700;
        text-decoration: none;
    }

    .date-text {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #374151;
        font-weight: 600;
    }

    .description-text,
    .response-text {
        display: inline-block;
        max-width: 240px;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
        text-transform: capitalize;
    }

    .status-created,
    .status-new,
    .status-open,
    .status-pending {
        background: #e0f2fe;
        color: #0369a1;
    }

    .status-answered,
    .status-accepted,
    .status-approved,
    .status-applied,
    .status-resolved {
        background: #dcfce7;
        color: #166534;
    }

    .status-po_loaded,
    .status-waiting,
    .status-waiting_response,
    .status-in_progress {
        background: #fef3c7;
        color: #92400e;
    }

    .status-update_request,
    .status-reschedule,
    .status-rescheduled {
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

    .status-closed,
    .status-completed,
    .status-confirmed {
        background: #dcfce7;
        color: #14532d;
    }

    .status-canceled,
    .status-cancelled,
    .status-rejected,
    .status-deleted {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-default {
        background: #e5e7eb;
        color: #374151;
    }

    .header-action-btn {
        height: 44px;
        border-radius: 9px;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        white-space: nowrap;
        transition: all .2s ease;
       
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.22);
        text-decoration: none;
    }

    .header-action-btn:hover {
        background: #2e940fff;
        border-color: #1d4ed8;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .action-buttons {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: nowrap;
        justify-content: center;
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
    }

    .btn-po {
        background: #f59e0b;
    }

    .btn-view {
        background: #2563eb;
    }

    .btn-view:hover {
        background: #1d4ed8;
    }

    .btn-delete {
        background: #dc2626;
    }

    .btn-close-dispute {
        background: #64748b;
    }

    .po-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 800;
        color: #92400e;
        text-decoration: none;
    }

    .po-link:hover {
        color: #78350f;
        text-decoration: underline;
    }

    .empty-row td {
        max-width: none;
        white-space: normal;
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 46px 20px;
        color: #6b7280;
        text-align: center;
    }

    .empty-state i {
        font-size: 44px;
        color: #94a3b8;
    }

    .empty-state-title {
        font-size: 18px;
        font-weight: 800;
        color: #334155;
    }

    .empty-state-text {
        font-size: 14px;
        color: #64748b;
    }

    .pagination-container {
        margin-top: 22px;
        display: flex;
        justify-content: center;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 6px;
        margin: 0;
        padding: 0;
        flex-wrap: wrap;
    }

    .pagination li {
        list-style: none;
    }

    .pagination li a,
    .pagination li span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 12px;
        border-radius: 9px;
        border: 1px solid #e5e7eb;
        color: #334155;
        background: #ffffff;
        text-decoration: none;
        font-weight: 700;
    }

    .pagination .active a,
    .pagination .active span {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }

    .pagination li a:hover {
        background: #eff6ff;
        color: #2563eb;
    }

    /* Search and reset filter */
    .search-filter-card {
        background: linear-gradient(135deg, #ffffff, #f8fbff);
        border-radius: 18px;
        padding: 16px 18px;
        margin-bottom: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5eaf3;
        max-width: 100%;
    }

    .search-filter-form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        width: 100%;
    }

    .search-input-wrap {
        position: relative;
        flex: 1 1 360px;
        min-width: 240px;
    }

    .search-input-wrap > i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: 15px;
        z-index: 2;
    }

    .search-input {
        height: 44px;
        border-radius: 12px;
        border: 1px solid #dbeafe;
        background: #ffffff;
        padding: 10px 14px 10px 42px;
        color: #334155;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        transition: all .2s ease;
    }

    .search-input::placeholder {
        color: #94a3b8;
        font-weight: 500;
    }

    .search-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        outline: none;
    }

    .btn-search,
    .btn-reset {
        height: 44px;
        border-radius: 11px;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        white-space: nowrap;
        transition: all .2s ease;
    }

    .btn-search {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff !important;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.22);
    }

    .btn-search:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        transform: translateY(-1px);
    }

    .btn-reset {
        background: #080808ff;
        color: #ebeff5ff !important;
        border: 1px solid #0a0a0aff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        text-decoration: none;
    }

    .btn-reset:hover {
        background: #f1f5f9;
        color: #334155 !important;
        transform: translateY(-1px);
        border: 1px solid #0a0a0aff;
    }

    .search-result-text {
        margin-top: 10px;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    @media (max-width: 1400px) {
        .requests-table thead th {
            font-size: 10px;
            padding: 12px 5px;
        }

        .requests-table tbody td {
            font-size: 12px;
            padding: 12px 5px;
            max-width: 145px;
        }

        .requests-table td.description-cell,
        .requests-table td.response-cell {
            max-width: 210px;
        }

        .status-badge {
            padding: 6px 8px;
            font-size: 10.5px;
        }

        .action-btn {
            width: 30px;
            height: 30px;
            min-width: 30px;
            font-size: 12px;
        }
    }

    @media (max-width: 1200px) {
        .requests-page {
            padding: 18px;
        }

        .content-card {
            padding: 12px;
        }

        .requests-table thead th {
            font-size: 9.5px;
            padding: 11px 4px;
            letter-spacing: .02em;
        }

        .requests-table tbody td {
            font-size: 12.5px;
            padding: 11px 4px;
            max-width: 125px;
        }

        .requests-table td.description-cell,
        .requests-table td.response-cell {
            max-width: 185px;
        }
    }

    /* Tablet and mobile: convert table rows to cards to keep the same request structure */
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

        .content-card {
            padding: 12px;
        }

        .table-responsive-custom {
            overflow-x: hidden;
        }

        .requests-table,
        .requests-table thead,
        .requests-table tbody,
        .requests-table th,
        .requests-table td,
        .requests-table tr {
            display: block;
            width: 100%;
        }

        .requests-table thead {
            display: none;
        }

        .requests-table tbody tr {
            background: #ffffff;
            border: 1px solid #e5eaf3;
            border-radius: 16px;
            margin-bottom: 14px;
            padding: 12px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        }

        .requests-table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border-top: none;
            border-bottom: 1px solid #eef2f7;
            padding: 10px 4px;
            white-space: normal;
            max-width: 100%;
            overflow: visible;
            text-overflow: unset;
            font-size: 13px;
            text-align: right;
        }

        .requests-table tbody td:last-child {
            border-bottom: none;
        }

        .requests-table tbody td::before {
            content: attr(data-label);
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: .04em;
            text-align: left;
            flex: 0 0 44%;
        }

        .requests-table td.description-cell,
        .requests-table td.response-cell {
            max-width: 100%;
        }

        .description-text,
        .response-text {
            max-width: 100%;
        }

        .action-buttons {
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .empty-row td::before {
            display: none;
        }
    }

    @media (max-width: 576px) {
        .requests-page {
            padding: 10px;
        }

        .page-header-card {
            border-radius: 14px;
            padding: 16px;
        }

        .header-title-group {
            align-items: flex-start;
        }

        .header-icon {
            width: 46px;
            height: 46px;
            font-size: 22px;
        }

        .dash-title {
            font-size: 21px;
        }

        .subtitle-text {
            font-size: 13px;
        }

        .content-card {
            border-radius: 14px;
            padding: 10px;
        }

        .requests-table tbody td {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            gap: 4px;
        }

        .requests-table tbody td::before {
            flex: none;
        }

        .action-buttons {
            justify-content: flex-start;
        }

        .search-filter-card {
            padding: 12px;
            border-radius: 14px;
        }

        .search-filter-form {
            gap: 8px;
        }

        .search-input-wrap {
            flex-basis: 100%;
            min-width: 100%;
        }

        .btn-search,
        .btn-reset,
        .header-action-btn {
            width: 100%;
        }
    }
CSS);

// Initialize Bootstrap tooltips safely.
$this->registerJs(<<<JS
if (typeof bootstrap !== 'undefined') {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]'));

    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el);
    });
}
JS, \yii\web\View::POS_READY);

// SweetAlert2 confirmation for POST actions.
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;

$this->registerJs(<<<JS
function postTo(url) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '{$csrfParam}';
    csrfInput.value = '{$csrfToken}';
    form.appendChild(csrfInput);

    document.body.appendChild(form);
    form.submit();
}

document.addEventListener('click', function (event) {
    const deleteButton = event.target.closest('[data-swal="delete"]');

    if (deleteButton) {
        event.preventDefault();

        const disputeId = deleteButton.getAttribute('data-dispute-id') || '';

        Swal.fire({
            icon: 'warning',
            title: '<i class="ri-delete-bin-6-line text-danger me-2"></i> Delete dispute?',
            html: '<div style="font-size:13px;color:#64748b;margin-top:6px;">Dispute ID: <b>' + disputeId + '</b><br><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>This action <b>cannot be undone</b>.</div>',
            showCancelButton: true,
            confirmButtonText: '<i class="ri-delete-bin-2-line"></i> Delete',
            cancelButtonText: '<i class="bi bi-box-arrow-left"></i> Keep dispute',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            focusCancel: true
        }).then(function (result) {
            if (result.isConfirmed) {
                postTo(deleteButton.href);
            }
        });

        return;
    }

    const closeButton = event.target.closest('[data-swal="close"]');

    if (closeButton) {
        event.preventDefault();

        const disputeId = closeButton.getAttribute('data-dispute-id') || '';

        Swal.fire({
            icon: 'question',
            title: '<i class="ri-lock-line text-secondary me-2"></i> Close dispute?',
            html: '<div style="font-size:13px;color:#64748b;margin-top:6px;">Dispute ID: <b>' + disputeId + '</b><br><i class="bi bi-info-circle-fill text-info me-1"></i>The dispute will be marked as <b>closed</b> and no further actions will be allowed.</div>',
            showCancelButton: true,
            confirmButtonText: '<i class="ri-check-line"></i> Close',
            cancelButtonText: '<i class="bi bi-box-arrow-left"></i> Keep dispute open',
            confirmButtonColor: '#0D3261',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            focusCancel: true
        }).then(function (result) {
            if (result.isConfirmed) {
                postTo(closeButton.href);
            }
        });
    }
});
JS, $this::POS_END);

// Format dispute status with the same badge structure.
$statusBadge = function ($status) {
    $rawStatus = trim((string) $status);
    $statusKey = strtolower(str_replace([' ', '-'], '_', $rawStatus));
    $formattedStatus = $rawStatus !== '' ? ucwords(str_replace('_', ' ', $rawStatus)) : 'N/A';
    $class = $statusKey !== '' ? 'status-badge status-' . $statusKey : 'status-badge status-default';

    $icon = match ($statusKey) {
        'open' => 'bi-exclamation-octagon',
        'pending' => 'bi-hourglass-split',
        'resolved' => 'bi-check2-circle',
        'closed' => 'bi-x-circle',
        default => 'bi-info-circle',
    };

    return '<span class="' . Html::encode($class) . '"><i class="bi ' . Html::encode($icon) . '"></i>' .
        Html::encode($formattedStatus) .
        '</span>';
};

// Format dates safely.
$formatDate = function ($date) {
    if (empty($date)) {
        return '<span class="text-muted fw-bold">N/A</span>';
    }

    return '<span class="date-text"><i class="bi bi-calendar-event text-primary"></i>' .
        Html::encode(date('d M Y H:i', strtotime($date))) .
        '</span>';
};

// Optional helper for uploaded PO file size.
$fileSizeLabel = function (?string $relativePath): string {
    if (empty($relativePath)) {
        return '';
    }

    $path = Yii::getAlias('@webroot/uploads/') . ltrim($relativePath, '/');

    if (!is_file($path)) {
        return '';
    }

    $bytes = filesize($path);

    if ($bytes === false) {
        return '';
    }

    $kb = $bytes / 1024;

    if ($kb < 1024) {
        return number_format($kb, 0) . ' KB';
    }

    return number_format($kb / 1024, 2) . ' MB';
};
?>

<!-- SHARED LIST: presentation is centralized; dispute permissions remain unchanged. -->
<main class="dash-content requests-page can-list-page">
    <div class="container-fluid">

        <!-- Page header: same request structure -->
        <div class="page-header-card">
            <div class="header-title-group">
                <div class="header-icon">
                    <i class="bi bi-flag"></i>
                </div>

                <div>
                    <h1 class="dash-title"><?= Html::encode($this->title) ?></h1>
                    <div class="subtitle-text">
                        Open, track, and resolve disputes between AO and MRO with the same request page structure.
                    </div>
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-plus-circle"></i> Open a dispute',
                ['create'],
                ['class' => 'btn btn-sm header-action-btn btn-outline-success']
            ) ?>
        </div>

        <!-- Flash messages -->
        <?php if (Yii::$app->session->hasFlash('message')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <?= Yii::$app->session->getFlash('message') ?>
            </div>
        <?php endif; ?>

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

        <!-- Search and reset filter: same request structure -->
        <div class="search-filter-card">
            <?php $filterUrl = Url::current(['search' => null, 'page' => null]); ?>

            <?= Html::beginForm($filterUrl, 'get', [
                'class' => 'search-filter-form',
                'role' => 'search',
            ]) ?>
                <div class="search-input-wrap">
                    <i class="bi bi-search"></i>
                    <?= Html::textInput('search', $searchQuery, [
                        'class' => 'form-control search-input',
                        'placeholder' => 'Search by dispute ID, description, status, AO, MRO or admin response...',
                        'autocomplete' => 'off',
                    ]) ?>
                </div>

                <?= Html::submitButton(
                    '<i class="bi bi-search"></i> Search',
                    ['class' => 'btn btn-search']
                ) ?>

                <?= Html::a(
                    '<i class="bi bi-arrow-counterclockwise"></i> Reset',
                    $filterUrl,
                    ['class' => 'btn btn-reset']
                ) ?>
            <?= Html::endForm() ?>

            <?php if ($searchQuery !== ''): ?>
                <div class="search-result-text">
                    <i class="bi bi-funnel"></i>
                    Active search: <strong><?= Html::encode($searchQuery) ?></strong>
                </div>
            <?php endif; ?>
        </div>

        <!-- Main content card: same request table/card responsive structure -->
        <div class="content-card">
            <div class="table-responsive-custom">
                <table class="table requests-table align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date Raised</th>
                            <th>Raised By</th>
                            <th>Parties</th>
                            <th>Request Details</th>
                            <th>Description</th>
                            <th>Purchase Order</th>
                            <th>Status</th>
                            <th>Admin Response</th>
                            <th style="min-width: 120px; text-align: center;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($disputes)): ?>
                            <?php foreach ($disputes as $dispute): ?>
                                <?php
                                    // Business logic is preserved. Relations are read the same way as the original file.
                                    $aoUser = $dispute->getAo()->one()->username ?? 'N/A';
                                    $mroUser = $dispute->getMro()->one()->username ?? 'N/A';
                                    $canDelete = ($dispute->created_by == Yii::$app->session->get('user_type'));
                                    $disputeId = $dispute->dispute_id ?? null;
                                    // DISPUTE ENCODED ID 2026: never expose the numeric dispute ID in the View URL.
                                    $encodedDisputeId = $disputeId !== null ? UrlIdHelper::encode($disputeId) : null;
                                    $description = $dispute->description ?? 'N/A';
                                    $adminResponse = $dispute->admin_response ?? '—';
                                    // SHARED REQUEST DETAILS: load the request only for its compact read-only summary.
                                    $requestModel = $dispute->request ?? null;
                                    $requestAircraft = $requestModel ? ($requestModel->aircraft ?? null) : null;
                                ?>

                                <tr>
                                    <td data-label="ID">
                                        <span class="request-link"><?= Html::encode($disputeId) ?></span>
                                    </td>

                                    <td data-label="Date Raised">
                                        <?= $formatDate($dispute->timestamp ?? null) ?>
                                    </td>

                                    <td data-label="Raised By">
                                        <span class="mro-link"><?= Html::encode($dispute->created_by ?: 'N/A') ?></span>
                                    </td>

                                    <td data-label="Parties">
                                        <div class="can-parties">
                                            <span><strong>AO</strong> <?= Html::encode($aoUser) ?></span>
                                            <span><strong>MRO</strong> <?= Html::encode($mroUser) ?></span>
                                        </div>
                                    </td>

                                    <td data-label="Request Details">
                                        <?= $this->render('../shared/_request-summary', [
                                            'requestModel' => $requestModel,
                                            'aircraft' => $requestAircraft,
                                        ]) ?>
                                    </td>

                                    <td data-label="Description" class="description-cell">
                                        <span class="description-text" title="<?= Html::encode($description) ?>">
                                            <?= Html::encode($description) ?>
                                        </span>
                                    </td>

                                    <td data-label="Purchase Order">
                                        <?php if (!empty($dispute->po)): ?>
                                            <?php $size = $fileSizeLabel($dispute->po); ?>
                                            <!-- SHARED PO DISPLAY: availability stays here; download remains in Actions. -->
                                            <span class="can-po-meta" title="Use the orange action button to download the Purchase Order">
                                                <i class="bi bi-file-earmark-check"></i>
                                                <span>PO attached<small><?= Html::encode($size ?: 'Size unavailable') ?></small></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="can-po-meta is-empty"><i class="bi bi-file-earmark-x"></i><span>No PO</span></span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Status">
                                        <?= $statusBadge($dispute->status ?? null) ?>
                                    </td>

                                    <td data-label="Admin Response" class="response-cell">
                                        <span class="response-text" title="<?= Html::encode($adminResponse) ?>">
                                            <?= Html::encode($adminResponse) ?>
                                        </span>
                                    </td>

                                    <td data-label="Actions">
                                        <div class="action-buttons">
                                            <?= Html::a(
                                                '<i class="bi bi-eye"></i>',
                                                ['view', 'id' => $encodedDisputeId],
                                                [
                                                    'class' => 'action-btn btn-view',
                                                    'title' => 'View dispute details',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'aria-label' => 'View dispute details',
                                                ]
                                            ) ?>

                                            <?php if (!empty($dispute->po)): ?>
                                                <?= Html::a(
                                                    '<i class="bi bi-download"></i>',
                                                    Yii::getAlias('@web/uploads/') . ltrim($dispute->po, '/'),
                                                    [
                                                        'class' => 'action-btn btn-po',
                                                        'target' => '_blank',
                                                        'rel' => 'noopener',
                                                        'title' => 'Download PO',
                                                        'data-bs-toggle' => 'tooltip',
                                                        'aria-label' => 'Download PO',
                                                    ]
                                                ) ?>
                                            <?php endif; ?>

                                            <?php if ($canDelete): ?>
                                                <?= Html::a(
                                                    '<i class="bi bi-trash"></i>',
                                                    ['delete', 'id' => $encodedDisputeId],
                                                    [
                                                        'class' => 'action-btn btn-delete',
                                                        'title' => 'Delete',
                                                        'data-bs-toggle' => 'tooltip',
                                                        'aria-label' => 'Delete',
                                                        'data-swal' => 'delete',
                                                        'data-dispute-id' => (string) $disputeId,
                                                        'data-method' => 'post',
                                                    ]
                                                ) ?>
                                            <?php endif; ?>

                                            <?php if (($dispute->status ?? null) != 'resolved' && $canDelete): ?>
                                                <?= Html::a(
                                                    '<i class="bi bi-x-circle"></i>',
                                                    ['close', 'id' => $encodedDisputeId],
                                                    [
                                                        'class' => 'action-btn btn-close-dispute',
                                                        'title' => 'Close',
                                                        'data-bs-toggle' => 'tooltip',
                                                        'aria-label' => 'Close',
                                                        'data-swal' => 'close',
                                                        'data-dispute-id' => (string) $disputeId,
                                                        'data-method' => 'post',
                                                    ]
                                                ) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="10">
                                    <div class="empty-state">
                                        <i class="bi bi-inboxes"></i>
                                        <div class="empty-state-title">No disputes found</div>
                                        <div class="empty-state-text">
                                            <?php if ($searchQuery !== ''): ?>
                                                No disputes found for your current search.
                                            <?php else: ?>
                                                There are currently no disputes to display.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination: displayed only when the controller provides a pagination object -->
            <?php if (isset($pagination) && !empty($pagination)): ?>
                <div class="pagination-container">
                    <?= LinkPager::widget([
                        'pagination' => $pagination,
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?= $this->render('../shared/_request-details-modal') ?>

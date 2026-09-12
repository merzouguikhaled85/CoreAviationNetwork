<?php

/** @var yii\web\View $this */
/** @var array $certificates */
/** @var string|null $title */
/** @var yii\data\Pagination|null $pagination */
/** @var string|null $search */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\YiiAsset;
use yii\widgets\LinkPager;

$title = isset($title) && trim((string) $title) !== ''
    ? (string) $title
    : 'MRO Aircraft Certificates';

$this->title = $title;

// Keep variables safe if the controller does not send them.
$certificates = $certificates ?? [];
$pagination = $pagination ?? null;

// Register YiiAsset to keep Yii2 data-confirm and data-method working correctly.
YiiAsset::register($this);

// Current search keyword from GET or controller variable.
$searchQuery = trim((string) ($search ?? Yii::$app->request->get('search', '')));

// Resolve the real primary key used by MroAircraftCertificate.
// This prevents routes like /mro-aircraft-certificates/view without id.
$getCertificateRouteId = static function ($certificate): ?string {
    if (is_object($certificate) && method_exists($certificate, 'getPrimaryKey')) {
        $primaryKey = $certificate->getPrimaryKey();

        if (is_array($primaryKey)) {
            foreach ($primaryKey as $value) {
                if ($value !== null && $value !== '') {
                    return (string) $value;
                }
            }
        } elseif ($primaryKey !== null && $primaryKey !== '') {
            return (string) $primaryKey;
        }
    }

    // Fallbacks for common column names used in older versions of this table.
    foreach (['id', 'certificate_id', 'mro_aircraft_certificate_id', 'aircraft_certificate_id'] as $attribute) {
        if (is_object($certificate) && isset($certificate->{$attribute}) && $certificate->{$attribute} !== '') {
            return (string) $certificate->{$attribute};
        }
    }

    return null;
};

// Bootstrap Icons: same icon family used by the MRO Airports page.
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

// SweetAlert2 for delete confirmation.
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'position' => \yii\web\View::POS_HEAD,
]);

// Custom page design: same CSS/HTML structure used by the MRO Airports page.
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

    .btn-add-request {
        border-radius: 11px;
        padding: 11px 20px;
        font-weight: 800;
        box-shadow: 0 8px 18px rgba(22, 163, 74, 0.16);
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 8px;
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

    .cert-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
        text-transform: uppercase;
        background: #dcfce7;
        color: #166534;
    }

    .download-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        height: 34px;
        padding: 0 13px;
        border-radius: 9px;
        background: #64748b;
        color: #ffffff !important;
        text-decoration: none;
        font-size: 12px;
        font-weight: 800;
        box-shadow: 0 4px 12px rgba(100, 116, 139, 0.20);
    }

    .download-btn:hover {
        background: #475569;
        color: #ffffff !important;
        text-decoration: none;
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
        text-decoration: none;
    }

    .btn-view {
        background: #0ea5e9;
    }

    .btn-update {
        background: #f59e0b;
    }

    .btn-delete {
        background: #ef4444;
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

    .alert {
        border-radius: 14px;
        border: none;
        padding: 14px 18px;
        font-weight: 600;
        box-shadow: 0 5px 16px rgba(15, 23, 42, .08);
    }

    /* SweetAlert2 compact design */
    .swal2-popup.custom-delete-popup {
        width: 380px !important;
        max-width: 92vw !important;
        border-radius: 18px !important;
        padding: 18px 20px 18px !important;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.24) !important;
    }

    .swal2-popup.custom-delete-popup .swal2-icon {
        width: 52px !important;
        height: 52px !important;
        margin: 8px auto 12px !important;
    }

    .swal2-popup.custom-delete-popup .swal2-icon .swal2-icon-content {
        font-size: 32px !important;
    }

    .swal2-title.custom-delete-title {
        color: #0f172a !important;
        font-size: 20px !important;
        font-weight: 800 !important;
        padding: 0 !important;
        margin: 0 0 8px !important;
    }

    .swal2-html-container.custom-delete-message {
        color: #64748b !important;
        font-size: 13px !important;
        line-height: 1.45 !important;
        margin: 0 8px 14px !important;
    }

    .swal2-actions {
        margin-top: 10px !important;
        gap: 8px !important;
    }

    .swal-delete-confirm,
    .swal-delete-cancel {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 7px !important;
        border-radius: 9px !important;
        padding: 9px 14px !important;
        font-size: 13px !important;
        font-weight: 800 !important;
        border: none !important;
        min-width: 115px !important;
        height: 39px !important;
        transition: 0.2s ease !important;
    }

    .swal-delete-confirm {
        background: #ef4444 !important;
        color: #ffffff !important;
        box-shadow: 0 8px 18px rgba(239, 68, 68, 0.25) !important;
    }

    .swal-delete-confirm:hover {
        background: #dc2626 !important;
        transform: translateY(-1px);
    }

    .swal-delete-cancel {
        background: #e2e8f0 !important;
        color: #334155 !important;
    }

    .swal-delete-cancel:hover {
        background: #cbd5e1 !important;
        transform: translateY(-1px);
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
    }

    /* Tablet and mobile: convert table rows to cards to keep the same MRO Airports structure */
    @media (max-width: 992px) {
        .requests-page {
            padding: 14px;
        }

        .page-header-card {
            padding: 18px;
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

        .action-buttons {
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .empty-row td::before {
            display: none;
        }

        .search-filter-card {
            padding: 14px;
        }

        .search-filter-form {
            align-items: stretch;
        }

        .search-input-wrap {
            flex: 1 1 100%;
        }

        .btn-search,
        .btn-reset,
        .btn-add-request {
            width: 100%;
        }

        .swal2-popup.custom-delete-popup {
            width: 330px !important;
            padding: 16px !important;
        }

        .swal-delete-confirm,
        .swal-delete-cancel {
            min-width: 105px !important;
            height: 38px !important;
            font-size: 12px !important;
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
            gap: 10px;
        }

        .header-icon {
            display: none;
        }

        .content-card,
        .search-filter-card {
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
    }
CSS);

/**
 * Helper function to display a safe aircraft model label.
 */
if (!function_exists('getAircraftCertificateLabel')) {
    function getAircraftCertificateLabel($manufacturer, $model)
    {
        $manufacturer = trim((string) $manufacturer);
        $model = trim((string) $model);

        if ($manufacturer === '' && $model === '') {
            return 'N/A';
        }

        if ($manufacturer === '') {
            return $model;
        }

        if ($model === '') {
            return $manufacturer;
        }

        return $manufacturer . ' - ' . $model;
    }
}

/*
 * SweetAlert2 confirmation for Yii2 data-confirm.
 * Yii2 keeps data-method='post', so delete request is sent as POST.
 */
$this->registerJs(<<<JS
// Initialize Bootstrap tooltips safely.
if (typeof bootstrap !== 'undefined') {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]'));

    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el);
    });
}

function escapeHtmlForSwal(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Replace Yii2 native confirm with SweetAlert2.
if (typeof yii !== 'undefined') {
    yii.confirm = function (message, okCallback, cancelCallback) {

        if (typeof Swal === 'undefined') {
            if (confirm(message)) {
                okCallback();
            } else if (cancelCallback) {
                cancelCallback();
            }

            return;
        }

        Swal.fire({
            width: 380,
            title: 'Delete this aircraft certificate?',
            html:
                '<div style="text-align:center;">' +
                    '<div style="font-weight:700;color:#0F172A;margin-bottom:4px;font-size:13px;">This action cannot be undone.</div>' +
                    '<div style="font-size:13px;">' + escapeHtmlForSwal(message) + '</div>' +
                '</div>',
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
            allowEscapeKey: true,
            confirmButtonText: '<i class="bi bi-trash3-fill"></i> Delete',
            cancelButtonText: '<i class="bi bi-box-arrow-left"></i> Keep certificate',
            buttonsStyling: false,
            customClass: {
                popup: 'custom-delete-popup',
                title: 'custom-delete-title',
                htmlContainer: 'custom-delete-message',
                confirmButton: 'swal-delete-confirm',
                cancelButton: 'swal-delete-cancel'
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                okCallback();
            } else if (cancelCallback) {
                cancelCallback();
            }
        });
    };
}
JS, \yii\web\View::POS_READY);
?>

<main class="dash-content requests-page">
    <div class="container-fluid">

        <!-- Page header: same structure as MRO Certificates -->
        <div class="page-header-card">
            <div class="header-title-group">
                <span class="header-icon">
                    <i class="bi bi-award-fill"></i>
                </span>

                <div>
                    <h1 class="dash-title"><?= Html::encode($this->title) ?></h1>
                    <div class="subtitle-text">
                        Manage your aircraft model approvals and quickly download each certificate.
                    </div>
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-plus-circle"></i> Add Aircraft Certificate',
                ['create'],
                ['class' => 'btn btn-outline-success btn-add-request']
            ) ?>
        </div>

        <!-- Search card: same structure as MRO Certificates -->
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
                        'placeholder' => 'Search by manufacturer, aircraft model or certificate...',
                        'aria-label' => 'Search aircraft certificates',
                    ]) ?>
                </div>

                <?= Html::submitButton(
                    '<i class="bi bi-search"></i> Search',
                    ['class' => 'btn btn-search']
                ) ?>

                <?= Html::a(
                    '<i class="bi bi-arrow-counterclockwise"></i> Reset',
                    $filterUrl,
                    [
                        'class' => 'btn btn-reset',
                        'title' => 'Reset search and show all aircraft certificates',
                    ]
                ) ?>
            <?= Html::endForm() ?>

            <?php if ($searchQuery !== ''): ?>
                <div class="search-result-text">
                    <i class="bi bi-funnel"></i>
                    Active search: <strong><?= Html::encode($searchQuery) ?></strong>
                </div>
            <?php endif; ?>
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

        <?php if (Yii::$app->session->hasFlash('info')): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('info')) ?>
            </div>
        <?php endif; ?>

        <!-- Content card: same table structure as MRO Certificates -->
        <div class="content-card">
            <div class="table-responsive-custom">
                <table class="table requests-table">
                    <thead>
                        <tr>
                            <th>Manufacturer</th>
                            <th>Aircraft Model</th>
                            <th>Certificate</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($certificates)): ?>
                            <?php foreach ($certificates as $certificate): ?>
                                <?php
                                    // Keep values prepared once for display and confirmations.
                                    $certificateId = $getCertificateRouteId($certificate);
                                    $aircraftModel = $certificate->aircraftModel ?? null;
                                    $manufacturer = $aircraftModel->manufacturer ?? 'N/A';
                                    $modelName = $aircraftModel->model ?? 'N/A';
                                    $modelLabel = getAircraftCertificateLabel($manufacturer, $modelName);
                                    $certificateFile = $certificate->certificate ?? null;
                                    $fileUrl = !empty($certificateFile)
                                        ? Url::to('@web/' . ltrim((string) $certificateFile, '/'), true)
                                        : null;
                                ?>
                                <tr>
                                    <td data-label="Manufacturer">
                                        <span class="cert-badge">
                                            <i class="bi bi-building-check"></i>
                                            <?= Html::encode($manufacturer) ?>
                                        </span>
                                    </td>

                                    <td data-label="Aircraft Model">
                                        <span class="cert-badge">
                                            <i class="bi bi-airplane-engines"></i>
                                            <?= Html::encode($modelName) ?>
                                        </span>
                                    </td>

                                    <td data-label="Certificate">
                                        <?php if (!empty($fileUrl)): ?>
                                            <?= Html::a(
                                                '<i class="bi bi-download"></i> Download',
                                                $fileUrl,
                                                [
                                                    'target' => '_blank',
                                                    'class' => 'download-btn',
                                                    'title' => 'Download aircraft certificate',
                                                    'aria-label' => 'Download aircraft certificate',
                                                    'data-bs-toggle' => 'tooltip',
                                                ]
                                            ) ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Actions">
                                        <div class="action-buttons">
                                            <?php if ($certificateId !== null && $certificateId !== ''): ?>
                                                <?= Html::a(
                                                    '<i class="bi bi-eye"></i>',
                                                    ['view', 'id' => $certificateId],
                                                    [
                                                        'class' => 'action-btn btn-view',
                                                        'title' => 'View aircraft certificate #' . $certificateId,
                                                        'aria-label' => 'View aircraft certificate #' . $certificateId,
                                                        'data-bs-toggle' => 'tooltip',
                                                    ]
                                                ) ?>

                                                <?= Html::a(
                                                    '<i class="bi bi-pencil-square"></i>',
                                                    ['update', 'id' => $certificateId],
                                                    [
                                                        'class' => 'action-btn btn-update',
                                                        'title' => 'Update aircraft certificate #' . $certificateId,
                                                        'aria-label' => 'Update aircraft certificate #' . $certificateId,
                                                        'data-bs-toggle' => 'tooltip',
                                                    ]
                                                ) ?>

                                                <?= Html::a(
                                                    '<i class="bi bi-trash"></i>',
                                                    ['delete', 'id' => $certificateId],
                                                    [
                                                        'class' => 'action-btn btn-delete',
                                                        'title' => 'Delete aircraft certificate #' . $certificateId,
                                                        'aria-label' => 'Delete aircraft certificate #' . $certificateId,
                                                        'data-bs-toggle' => 'tooltip',
                                                        'data' => [
                                                            'confirm' => 'Aircraft Certificate ID #' . $certificateId . ' - ' . $modelLabel . ' will be permanently deleted.',
                                                            'method' => 'post',
                                                        ],
                                                    ]
                                                ) ?>
                                            <?php else: ?>
                                                <span class="text-muted" title="Missing certificate route id">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <div class="empty-state-title">
                                            <?php if ($searchQuery !== ''): ?>
                                                No aircraft certificates found for your current search.
                                            <?php else: ?>
                                                No aircraft certificates linked to your MRO for now.
                                            <?php endif; ?>
                                        </div>
                                        <div class="empty-state-text">
                                            <?php if ($searchQuery !== ''): ?>
                                                Try another manufacturer, model or certificate name.
                                            <?php else: ?>
                                                Add an aircraft certificate to complete your MRO approvals profile.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($pagination) && $pagination !== null): ?>
                <div class="pagination-container">
                    <?= LinkPager::widget([
                        'pagination' => $pagination,
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

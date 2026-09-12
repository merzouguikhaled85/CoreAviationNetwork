<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;

$this->title = 'MRO Profiles';

/* Filter values */
$searchValue = $search ?? Yii::$app->request->get('search', '');
$statusValue = $status ?? Yii::$app->request->get('status', '');
$emailVerifiedValue = $emailVerified ?? Yii::$app->request->get('email_verified', '');

/* Bootstrap Icons */
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

/* SweetAlert2 */
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

/* Same design as requests page */
$this->registerCss("
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

    .btn-add-request {
        border-radius: 9px;
        padding: 11px 22px;
        font-weight: 700;
        box-shadow: 0 6px 16px rgba(242, 244, 247, 0.25);
        white-space: nowrap;
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
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 800;
        padding: 12px 5px;
        border: none;
        white-space: nowrap;
    }

    .requests-table tbody td,
    .requests-table tbody th {
        padding: 12px 5px;
        vertical-align: middle;
        border-top: 1px solid #eef2f7;
        color: #374151;
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 135px;
    }

    .requests-table tbody tr {
        transition: background .2s ease;
    }

    .requests-table tbody tr:hover {
        background: #f8fbff;
        transform: none;
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

    .profile-photo,
    .company-photo {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        object-fit: cover;
        border: 2px solid #e5eaf3;
        box-shadow: 0 5px 14px rgba(15, 23, 42, 0.10);
        background: #f8fafc;
    }

    .photo-empty {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: #f1f5f9;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        border: 1px solid #e5e7eb;
    }

    .user-badge,
    .email-badge,
    .status-badge,
    .name-badge,
    .phone-badge,
    .company-badge,
    .verified-badge,
    .insurance-download,
    .empty-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
        text-decoration: none;
    }

    .user-badge {
        background: #e0f2fe;
        color: #0369a1;
    }

    .email-badge {
        background: #eef2ff;
        color: #4338ca;
    }

    /* Email display correction: show full email without ellipsis */
    .email-cell {
        max-width: 260px !important;
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
    }

    .email-badge.email-badge-full {
        max-width: 100%;
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        line-height: 1.35;
        text-align: left;
        align-items: flex-start;
    }

    .email-badge.email-badge-full i {
        flex: 0 0 auto;
        margin-top: 2px;
    }

    .email-badge.email-badge-full .email-text {
        display: inline-block;
        max-width: 100%;
        white-space: normal !important;
        overflow-wrap: anywhere;
        word-break: break-word;
        overflow: visible !important;
        text-overflow: unset !important;
    }

    .name-badge {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #e5e7eb;
    }

    .phone-badge {
        background: #ecfdf5;
        color: #047857;
    }

    .company-badge {
        background: #f1f5f9;
        color: #334155;
    }

    .insurance-download {
        background: #ecfdf5;
        color: #047857 !important;
        border: 1px solid #bbf7d0;
    }

    .insurance-download:hover {
        background: #d1fae5;
        color: #065f46 !important;
        text-decoration: none;
    }

    .empty-badge {
        background: #f8fafc;
        color: #94a3b8;
        border: 1px solid #e5e7eb;
    }

    .status-badge {
        background: #e0f2fe;
        color: #0369a1;
    }

    .status-active,
    .status-approved,
    .status-enabled {
        background: #dcfce7;
        color: #166534;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-banned,
    .status-rejected,
    .status-disabled {
        background: #fee2e2;
        color: #991b1b;
    }

    .verified-yes {
        background: #dcfce7;
        color: #166534;
    }

    .verified-no {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Actions column: display exactly 3 buttons per row */
    .action-cell {
        max-width: 130px !important;
        min-width: 130px !important;
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
    }

    .action-buttons {
        display: grid;
        grid-template-columns: repeat(3, 32px);
        gap: 6px;
        align-items: center;
        justify-content: start;
        min-width: 108px;
        max-width: 108px;
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
        background: #f0a51aff;
    }

    .btn-delete {
        background: #ef4444;
    }

    .btn-toggle-status {
        background: #22c55e;
    }

    .btn-reset-password {
        background: #8b5cf6;
    }

    .empty-state {
        padding: 35px;
        text-align: center;
        color: #64748b;
        font-weight: 600;
    }

    .alert {
        border-radius: 14px;
        border: none;
        padding: 14px 18px;
        font-weight: 600;
        box-shadow: 0 5px 16px rgba(15, 23, 42, .08);
        margin-bottom: 18px;
    }

    /* Search filter card */
    .filter-card {
        background: #ffffff;
        border: 1px solid #e5eaf3;
        border-radius: 18px;
        padding: 16px;
        margin-bottom: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        max-width: 100%;
    }

    .filter-form {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
        width: 100%;
    }

    .filter-group {
        flex: 1;
        min-width: 190px;
    }

    .filter-group-large {
        flex: 1.6;
        min-width: 260px;
    }

    .filter-group label {
        display: block;
        font-size: 12px;
        font-weight: 800;
        color: #334155;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .filter-control {
        width: 100%;
        height: 42px;
        border: 1px solid #dbeafe;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        background: #ffffff;
        outline: none;
        transition: 0.2s ease;
    }

    .filter-control:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12);
    }

    .filter-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-filter-search,
    .btn-filter-reset {
        height: 42px;
        border-radius: 10px;
        padding: 0 16px;
        font-size: 13px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        text-decoration: none !important;
        white-space: nowrap;
    }

    .btn-filter-search {
        background: #2563eb;
        color: #ffffff !important;
        border: 1px solid #2563eb;
    }

    .btn-filter-search:hover {
        background: #1d4ed8;
        color: #ffffff !important;
    }

    .btn-filter-reset {
        background: #f8fafc;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }

    .btn-filter-reset:hover {
        background: #e2e8f0;
        color: #0f172a !important;
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
        padding-left: 0;
        margin: 0;
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
        min-width: 38px;
        height: 38px;
        padding: 6px 12px;
        border-radius: 9%;
        border: 1px solid #dbeafe;
        color: #2563eb;
        background: #ffffff;
        text-decoration: none;
        font-weight: 400;
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
            font-size: 9.5px;
            padding: 11px 4px;
        }

        .requests-table tbody td,
        .requests-table tbody th {
            font-size: 11.5px;
            padding: 11px 4px;
            max-width: 115px;
        }

        .email-cell {
            max-width: 240px !important;
        }

        .action-btn {
            width: 30px;
            height: 30px;
            min-width: 30px;
            font-size: 12px;
        }

        .action-buttons {
            grid-template-columns: repeat(3, 30px);
            min-width: 102px;
            max-width: 102px;
        }

        .profile-photo,
        .company-photo,
        .photo-empty {
            width: 40px;
            height: 40px;
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
            font-size: 9px;
            padding: 10px 3px;
            letter-spacing: .02em;
        }

        .requests-table tbody td,
        .requests-table tbody th {
            font-size: 11px;
            padding: 10px 3px;
            max-width: 100px;
        }

        .email-cell {
            max-width: 220px !important;
        }
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

        .requests-table tbody td,
        .requests-table tbody th {
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

        .requests-table tbody td::before,
        .requests-table tbody th::before {
            content: attr(data-label);
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: .04em;
            text-align: left;
            flex: 0 0 44%;
        }

        .email-cell {
            max-width: 100% !important;
        }

        .email-badge.email-badge-full {
            justify-content: flex-start;
            text-align: right;
        }

        .action-cell {
            max-width: 100% !important;
            min-width: 100% !important;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(3, 32px);
            gap: 6px;
            justify-content: end;
            min-width: 108px;
            max-width: 108px;
        }

        .empty-row td::before {
            display: none;
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

    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-group,
        .filter-group-large {
            min-width: 100%;
        }

        .filter-actions {
            width: 100%;
        }

        .btn-filter-search,
        .btn-filter-reset {
            flex: 1;
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

        .content-card {
            border-radius: 14px;
            padding: 10px;
        }

        .requests-table tbody td,
        .requests-table tbody th {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            gap: 4px;
        }

        .requests-table tbody td::before,
        .requests-table tbody th::before {
            flex: none;
        }

        .email-badge.email-badge-full {
            text-align: left;
        }

        .action-buttons {
            justify-content: flex-start;
        }
    }
");

/*
 * SweetAlert2 confirmation for Yii2 data-confirm.
 * Yii2 keeps data-method='post', so requests are sent as POST.
 */
$this->registerJs(<<<JS
// Initialize Bootstrap tooltips safely.
if (typeof bootstrap !== 'undefined') {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]'));

    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el);
    });
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
            title: 'Confirm action',
            html:
                '<div style="text-align:center;">' +
                    '<div style="font-weight:700;color:#0F172A;margin-bottom:4px;font-size:13px;">Please confirm before continuing.</div>' +
                    '<div style="font-size:13px;">' + message + '</div>' +
                '</div>',
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
            allowEscapeKey: true,
            confirmButtonText: '<i class="bi bi-check-circle-fill"></i> Confirm',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review action',
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

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title fw-bold">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-tools"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Manage MRO profiles, company details, documents, and account status
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-plus-circle"></i> Add MRO Profile',
                ['create'],
                ['class' => 'btn btn-outline-success btn-add-request']
            ) ?>
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

        <!-- Search and filter section -->
        <div class="filter-card">
            <?= Html::beginForm(['index'], 'get', ['class' => 'filter-form']) ?>

                <!-- Global search input -->
                <div class="filter-group filter-group-large">
                    <label for="search">
                        <i class="bi bi-search"></i> Search
                    </label>

                    <?= Html::textInput(
                        'search',
                        $searchValue,
                        [
                            'id' => 'search',
                            'class' => 'filter-control',
                            'placeholder' => 'Search by username, email, name, phone, company...',
                        ]
                    ) ?>
                </div>

                <!-- Status filter -->
                <div class="filter-group">
                    <label for="status">
                        <i class="bi bi-info-circle"></i> Status
                    </label>

                    <?= Html::dropDownList(
                        'status',
                        $statusValue,
                        [
                            '' => 'All',
                            'active' => 'Active',
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'enabled' => 'Enabled',
                            'disabled' => 'Disabled',
                            'banned' => 'Banned',
                            'rejected' => 'Rejected',
                        ],
                        [
                            'id' => 'status',
                            'class' => 'filter-control',
                        ]
                    ) ?>
                </div>

                <!-- Email verified filter -->
                <div class="filter-group">
                    <label for="email_verified">
                        <i class="bi bi-envelope-check"></i> Email Verified
                    </label>

                    <?= Html::dropDownList(
                        'email_verified',
                        $emailVerifiedValue,
                        [
                            '' => 'All',
                            '1' => 'Verified',
                            '0' => 'Not verified',
                        ],
                        [
                            'id' => 'email_verified',
                            'class' => 'filter-control',
                        ]
                    ) ?>
                </div>

                <!-- Search and reset buttons -->
                <div class="filter-actions">
                    <?= Html::submitButton(
                        '<i class="bi bi-search"></i> Search',
                        ['class' => 'btn btn-filter-search']
                    ) ?>

                    <?= Html::a(
                        '<i class="bi bi-arrow-counterclockwise"></i> Reset',
                        ['index'],
                        ['class' => 'btn btn-filter-reset']
                    ) ?>
                </div>

            <?= Html::endForm() ?>
        </div>

        <!-- MRO profiles table card -->
        <div class="content-card">
            <div class="table-responsive-custom">
                <table class="table requests-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Profile Photo</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Contact Number</th>
                            <th>Insurance Document</th>
                            <th>Company Name</th>
                            <th>Company Photo</th>
                            <th>Email Verified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($mroProfiles)): ?>
                            <?php foreach ($mroProfiles as $mroProfile): ?>
                                <?php
                                    $status = strtolower((string) $mroProfile->status);
                                    $statusClass = 'status-badge';

                                    if (in_array($status, ['active', 'approved', 'enabled'], true)) {
                                        $statusClass .= ' status-active';
                                    } elseif ($status === 'pending') {
                                        $statusClass .= ' status-pending';
                                    } elseif (in_array($status, ['banned', 'rejected', 'disabled'], true)) {
                                        $statusClass .= ' status-banned';
                                    }
                                ?>

                                <tr>
                                    <td data-label="ID">
                                        <?= Html::a(
                                            '#' . Html::encode($mroProfile->mro_id),
                                            ['view', 'id' => $mroProfile->mro_id],
                                            ['class' => 'request-link']
                                        ) ?>
                                    </td>

                                    <td data-label="Profile Photo">
                                        <?php if (!empty($mroProfile->profile_photo)): ?>
                                            <?= Html::img('@web/' . $mroProfile->profile_photo, [
                                                'class' => 'profile-photo',
                                                'alt' => 'Profile Photo',
                                            ]) ?>
                                        <?php else: ?>
                                            <span class="photo-empty">
                                                <i class="bi bi-person"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Username">
                                        <?php if (!empty($mroProfile->username)): ?>
                                            <span class="user-badge">
                                                <i class="bi bi-person-circle"></i>
                                                <?= Html::encode($mroProfile->username) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="empty-badge">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Full email display -->
                                    <td data-label="Email" class="email-cell">
                                        <?php if (!empty($mroProfile->email)): ?>
                                            <span class="email-badge email-badge-full">
                                                <i class="bi bi-envelope"></i>
                                                <span class="email-text">
                                                    <?= Html::encode($mroProfile->email) ?>
                                                </span>
                                            </span>
                                        <?php else: ?>
                                            <span class="empty-badge">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Status">
                                        <span class="<?= Html::encode($statusClass) ?>">
                                            <i class="bi bi-info-circle"></i>
                                            <?= Html::encode(ucfirst((string) $mroProfile->status)) ?>
                                        </span>
                                    </td>

                                    <td data-label="First Name">
                                        <?php if (!empty($mroProfile->first_name)): ?>
                                            <span class="name-badge">
                                                <?= Html::encode($mroProfile->first_name) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="empty-badge">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Last Name">
                                        <?php if (!empty($mroProfile->last_name)): ?>
                                            <span class="name-badge">
                                                <?= Html::encode($mroProfile->last_name) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="empty-badge">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Contact Number">
                                        <?php if (!empty($mroProfile->contact_number)): ?>
                                            <span class="phone-badge">
                                                <i class="bi bi-telephone"></i>
                                                <?= Html::encode($mroProfile->contact_number) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="empty-badge">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Insurance Document">
                                        <?php if (!empty($mroProfile->insurance_document)): ?>
                                            <?= Html::a(
                                                '<i class="bi bi-download"></i> Download',
                                                Yii::getAlias('@web/') . $mroProfile->insurance_document,
                                                [
                                                    'class' => 'insurance-download',
                                                    'target' => '_blank',
                                                    'rel' => 'noopener',
                                                    'title' => 'Download Insurance',
                                                    'data-bs-toggle' => 'tooltip',
                                                ]
                                            ) ?>
                                        <?php else: ?>
                                            <span class="empty-badge">
                                                <i class="bi bi-file-earmark-x"></i>
                                                N/A
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Company Name">
                                        <?php if (!empty($mroProfile->company_name)): ?>
                                            <span class="company-badge">
                                                <i class="bi bi-building"></i>
                                                <?= Html::encode($mroProfile->company_name) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="empty-badge">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Company Photo">
                                        <?php if (!empty($mroProfile->company_photo)): ?>
                                            <?= Html::img('@web/' . $mroProfile->company_photo, [
                                                'class' => 'company-photo',
                                                'alt' => 'Company Photo',
                                            ]) ?>
                                        <?php else: ?>
                                            <span class="photo-empty">
                                                <i class="bi bi-building"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Email Verified">
                                        <?php if ($mroProfile->email_verified): ?>
                                            <span class="verified-badge verified-yes">
                                                <i class="bi bi-check-circle"></i>
                                                Yes
                                            </span>
                                        <?php else: ?>
                                            <span class="verified-badge verified-no">
                                                <i class="bi bi-x-circle"></i>
                                                No
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Actions" class="action-cell">
                                        <div class="action-buttons">

                                            <!-- View button -->
                                            <?= Html::a(
                                                '<i class="bi bi-eye"></i>',
                                                ['view', 'id' => $mroProfile->mro_id],
                                                [
                                                    'class' => 'action-btn btn-view',
                                                    'title' => 'View',
                                                    'data-bs-toggle' => 'tooltip',
                                                ]
                                            ) ?>

                                            <!-- Update button -->
                                            <?= Html::a(
                                                '<i class="bi bi-pencil"></i>',
                                                ['update', 'id' => $mroProfile->mro_id],
                                                [
                                                    'class' => 'action-btn btn-update',
                                                    'title' => 'Update',
                                                    'data-bs-toggle' => 'tooltip',
                                                ]
                                            ) ?>

                                            <!-- Delete button -->
                                            <?= Html::a(
                                                '<i class="bi bi-trash"></i>',
                                                ['delete', 'id' => $mroProfile->mro_id],
                                                [
                                                    'class' => 'action-btn btn-delete',
                                                    'title' => 'Delete',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'data' => [
                                                        'confirm' => 'MRO profile #' . $mroProfile->mro_id . ' will be permanently deleted.',
                                                        'method' => 'post',
                                                    ],
                                                ]
                                            ) ?>

                                            <!-- Toggle status button -->
                                            <?= Html::a(
                                                '<i class="bi bi-arrow-repeat"></i>',
                                                ['toggle-status', 'id' => $mroProfile->mro_id],
                                                [
                                                    'class' => 'action-btn btn-toggle-status',
                                                    'title' => 'Toggle Status',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'data' => [
                                                        'confirm' => 'Are you sure you want to change the status of this profile?',
                                                        'method' => 'post',
                                                    ],
                                                ]
                                            ) ?>

                                            <!-- Reset password button -->
                                            <?= Html::a(
                                                '<i class="bi bi-key"></i>',
                                                ['send-password-reset', 'id' => $mroProfile->mro_id],
                                                [
                                                    'class' => 'action-btn btn-reset-password',
                                                    'title' => 'Reset Password',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'data' => [
                                                        'confirm' => 'Are you sure you want to send a password reset email to this user?',
                                                        'method' => 'post',
                                                    ],
                                                ]
                                            ) ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="13">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox" style="font-size: 35px;"></i>
                                        <div>No MRO profiles found.</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                <?= LinkPager::widget([
                    'pagination' => $pagination,
                ]) ?>
            </div>
        </div>

    </div>
</main>

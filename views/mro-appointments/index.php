<?php

/** @var yii\web\View $this */
/** @var \app\models\Appointment[] $appointments */
/** @var \yii\data\Pagination|null $pagination */
/** @var string|null $search */
/** @var array<int,string>|null $aoMap */
/** @var array<int,\app\models\Requests>|null $requestMap */
/** @var array|null $cols */

use yii\helpers\Html;
use yii\helpers\Url;
// Chargement direct et versionné pour contourner l'ancien AppAsset de production.
$requestSyncFile = Yii::getAlias('@webroot/js/request-sync.js');
$requestSyncVersion = is_file($requestSyncFile) ? (string) filemtime($requestSyncFile) : '1';
$this->registerJsFile(
    rtrim(Yii::getAlias('@web'), '/') . '/js/request-sync.js?v=' . $requestSyncVersion,
    ['depends' => [\yii\web\YiiAsset::class]]
);

$this->title = 'My Appointments';

// REQUEST DETAILS: keep the view safe when rendered from another action or test.
$requestMap = $requestMap ?? [];

// Current search keyword from GET.
// The controller must keep using the same `search` parameter to filter appointments before pagination.
$searchQuery = trim((string) ($search ?? Yii::$app->request->get('search', '')));

/* Bootstrap Icons */
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

/* SweetAlert2 */
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'position' => \yii\web\View::POS_HEAD
]);

/* Custom page design: same visual system used by requests page */
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
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 800;
        padding: 13px 8px;
        border: none;
        white-space: nowrap;
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
    }

    .requests-table tbody tr {
        transition: background .2s ease;
    }

    .requests-table tbody tr:hover {
        background: #f8fbff;
        transform: none;
    }

    .requests-table td.location-cell {
        max-width: 240px;
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

    .mro-link:hover {
        text-decoration: underline;
    }

    .date-text {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #374151;
        font-weight: 600;
    }

    /* REQUEST DETAILS: compact operational summary displayed inside an appointment row. */
    .requests-table tbody td.request-details-cell {
        width: 310px;
        min-width: 270px;
        max-width: 350px;
        white-space: normal;
        overflow: visible;
        text-overflow: initial;
    }

    .request-row-summary {
        display: grid;
        gap: 7px;
        min-width: 0;
    }

    .request-aircraft-line,
    .request-location-line,
    .request-description-line {
        display: flex;
        min-width: 0;
        gap: 7px;
    }

    .request-aircraft-line,
    .request-location-line {
        align-items: center;
    }

    .request-aircraft-line {
        color: #0f172a;
        font-weight: 800;
    }

    .request-aircraft-line i {
        color: #0284c7;
    }

    .request-aircraft-line span,
    .request-location-line span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .request-identity-line {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 5px;
    }

    .request-mini-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 7px;
        border-radius: 7px;
        background: #ecfdf5;
        color: #047857;
        font-size: 10.5px;
        font-weight: 800;
        white-space: nowrap;
    }

    .request-mini-chip.serial-chip {
        background: #f1f5f9;
        color: #475569;
    }

    .request-flight-window {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 5px;
    }

    .request-flight-date {
        padding: 5px 7px;
        border-radius: 7px;
        background: #eef2ff;
        color: #4338ca;
        font-size: 10.5px;
        font-weight: 700;
        white-space: nowrap;
    }

    .request-flight-date strong {
        margin-right: 3px;
        font-size: 9px;
        letter-spacing: .04em;
    }

    .request-location-line {
        color: #b45309;
        font-size: 11px;
        font-weight: 700;
    }

    .request-location-line i {
        color: #f59e0b;
        flex: 0 0 auto;
    }

    .request-description-line {
        align-items: flex-start;
        padding-top: 6px;
        border-top: 1px dashed #dbe3ef;
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.35;
    }

    .request-description-line i {
        margin-top: 1px;
        color: #6366f1;
        flex: 0 0 auto;
    }

    .request-description-line span {
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        white-space: normal;
    }

    .request-unavailable {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #94a3b8;
        font-weight: 700;
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

    .status-canceled,
    .status-cancelled {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Appointment status aliases using the same request badge design */
    .status-confirmed {
        background: #dcfce7;
        color: #166534;
    }

    .status-waiting_response,
    .status-waiting-response {
        background: #fef3c7;
        color: #92400e;
    }

    .status-reschedule,
    .status-rescheduled {
        background: #ede9fe;
        color: #5b21b6;
    }

    .status-default {
        background: #e5e7eb;
        color: #374151;
    }

    .action-buttons {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: nowrap;
        justify-content: flex-start;
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

    .btn-view {
        background: #0ea5e9;
    }

    .btn-update {
        background: #f0a51aff;
    }

    .btn-delete {
        background: #ef4444;
    }

    .btn-contact {
        background: #64748b;
    }

    .btn-update-request {
        background: #a855f7;
    }

    .btn-feedback {
        background: #f59e0b;
    }

    .no-actions {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
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

    .swal-delete-confirm.confirm-style {
        background: #2563eb !important;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25) !important;
    }

    .swal-delete-confirm.confirm-style:hover {
        background: #1d4ed8 !important;
    }

    .swal-delete-cancel {
        background: #e2e8f0 !important;
        color: #334155 !important;
    }

    .swal-delete-cancel:hover {
        background: #cbd5e1 !important;
        transform: translateY(-1px);
    }

    /* Medium screens: keep all columns but make table more compact */
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

        .requests-table td.location-cell {
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

        .requests-table td.location-cell {
            max-width: 185px;
        }
    }

    /* Tablet and mobile: convert rows to cards to avoid horizontal scroll */
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

        .requests-table td.location-cell {
            max-width: 100%;
        }

        /* REQUEST DETAILS: use the complete value area inside responsive cards. */
        .requests-table tbody td.request-details-cell {
            width: 100%;
            max-width: 100%;
            align-items: flex-start;
        }

        .request-details-cell .request-row-summary {
            flex: 1 1 auto;
            width: 56%;
        }

        .action-buttons {
            justify-content: flex-end;
            flex-wrap: wrap;
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

        .requests-table tbody td {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            gap: 4px;
        }

        .requests-table tbody td::before {
            flex: none;
        }

        /* REQUEST DETAILS: stack the summary below its label on phones. */
        .request-details-cell .request-row-summary {
            width: 100%;
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
        .btn-reset {
            width: 100%;
        }
    }

    /* REQUEST DETAILS MODAL 2026: compact table cell and reusable responsive modal. */
    .requests-table tbody td.request-details-cell {
        width: 230px;
        min-width: 200px;
        max-width: 260px;
    }

    .request-details-compact {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        min-width: 0;
    }

    .request-aircraft-summary {
        display: grid;
        gap: 2px;
        min-width: 0;
    }

    .request-aircraft-make,
    .request-aircraft-model {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .request-aircraft-make {
        color: #64748b;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .06em;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .request-aircraft-model {
        color: #0f172a;
        font-size: 13px;
        font-weight: 800;
    }

    .request-details-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        flex: 0 0 auto;
        padding: 7px 9px;
        border: 1px solid #bfdbfe;
        border-radius: 9px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
        transition: background .2s ease, border-color .2s ease, color .2s ease, transform .2s ease;
    }

    .request-details-trigger:hover,
    .request-details-trigger:focus {
        border-color: #2563eb;
        background: #2563eb;
        color: #ffffff;
        transform: translateY(-1px);
    }

    .request-details-modal .modal-content {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .24);
        overflow: hidden;
    }

    .request-details-modal .modal-header {
        padding: 18px 20px;
        border-bottom: 1px solid #dbe5f2;
        background: linear-gradient(135deg, #f8fbff, #eef6ff);
    }

    .request-details-modal .modal-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #0f172a;
        font-size: 18px;
        font-weight: 800;
    }

    .request-details-modal .modal-title i {
        color: #0284c7;
    }

    .request-details-modal .modal-body {
        padding: 20px;
        background: #ffffff;
    }

    .request-modal-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .request-modal-item {
        min-width: 0;
        padding: 12px;
        border: 1px solid #dbe5f2;
        border-radius: 12px;
        background: #f8fafc;
    }

    .request-modal-label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 5px;
        color: #64748b;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .request-modal-value {
        color: #0f172a;
        font-size: 13px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .request-modal-description {
        margin-top: 10px;
    }

    .request-modal-description textarea {
        min-height: 105px;
        resize: vertical;
        border-color: #dbe5f2;
        background: #f8fafc;
        color: #334155;
        font-size: 13px;
        line-height: 1.5;
    }

    @media (max-width: 992px) {
        .request-details-cell .request-details-compact {
            flex: 1 1 auto;
            width: 56%;
        }

        .request-modal-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 576px) {
        .request-details-cell .request-details-compact {
            width: 100%;
        }

        .request-modal-grid {
            grid-template-columns: 1fr;
        }
    }
CSS);

/*
 * SweetAlert2 confirmation for Yii2 data-confirm.
 * Important:
 * - Do not use DOMContentLoaded here.
 * - Yii2 keeps data-method='post', so appointment actions are sent as POST.
 */
$this->registerJs(<<<JS
// Initialize Bootstrap tooltips safely
if (typeof bootstrap !== 'undefined') {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]'));

    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el);
    });
}

// Replace Yii2 native confirm with SweetAlert2
if (typeof yii !== 'undefined') {
    yii.confirm = function (message, okCallback, cancelCallback) {
        var lowerMessage = String(message).toLowerCase();
        var isAcceptAction = lowerMessage.indexOf('accept') !== -1 || lowerMessage.indexOf('rescheduled') !== -1;
        var isRemoveAction = lowerMessage.indexOf('remove') !== -1 || lowerMessage.indexOf('cancel') !== -1 || lowerMessage.indexOf('delete') !== -1;

        var alertTitle = isAcceptAction ? 'Accept this appointment?' : 'Remove this appointment?';
        var alertIcon = isAcceptAction ? 'question' : 'warning';
        var confirmText = isAcceptAction
            ? '<i class="bi bi-check-circle-fill"></i> Accept'
            : '<i class="bi bi-trash3-fill"></i> Remove';
        var confirmButtonClass = 'swal-delete-confirm' + (isAcceptAction ? ' confirm-style' : '');

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
            title: alertTitle,
            html:
                '<div style="text-align:center;">' +
                    '<div style="font-weight:700;color:#0F172A;margin-bottom:4px;font-size:13px;">Please confirm this action.</div>' +
                    '<div style="font-size:13px;">' + message + '</div>' +
                '</div>',
            icon: alertIcon,
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
            allowEscapeKey: true,
            confirmButtonText: confirmText,
            cancelButtonText: '<i class="bi bi-box-arrow-left"></i> Keep appointment',
            buttonsStyling: false,
            customClass: {
                popup: 'custom-delete-popup',
                title: 'custom-delete-title',
                htmlContainer: 'custom-delete-message',
                confirmButton: confirmButtonClass,
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


// REQUEST DETAILS: plain date formatter for the compact ETA/ETD chips.
$formatRequestDate = static function ($date): string {
    if (empty($date)) {
        return 'N/A';
    }

    $timestamp = strtotime((string) $date);

    return $timestamp !== false ? date('d M Y H:i', $timestamp) : (string) $date;
};
?>

<!-- SHARED LIST: presentation is centralized; appointment actions remain contextual. -->
<main class="dash-content requests-page can-list-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title fw-bold">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-calendar-check"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>
                <div class="subtitle-text">
                    Manage and track your appointments
                </div>
            </div>
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

        <!-- Search and reset filter -->
        <div class="search-filter-card">
            <?php $filterUrl = Url::current(['search' => null, 'page' => null]); ?>

            <?= Html::beginForm($filterUrl, 'get', [
                'class' => 'search-filter-form',
                'role' => 'search'
            ]) ?>
                <div class="search-input-wrap">
                    <i class="bi bi-search"></i>
                    <?= Html::textInput('search', $searchQuery, [
                        'class' => 'form-control search-input',
                        'placeholder' => 'Search by request, aircraft, registration, location, AO, status or date...',
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

        <?php
        /*
         * SYNCHRONISATION DE LA LISTE MRO : seul le tableau est recharge lorsque
         * le serveur confirme un rendez-vous visible pour le MRO connecte. Les
         * parametres de recherche et de pagination restent dans URL courante.
         * Le curseur serveur conserve les changements reçus pendant un onglet masqué.
         */
        ?>
        <div id="mro-appointments-list"
             data-request-sync-context="mro-appointments"
             data-request-sync-cursor="<?= Html::encode((string) ($syncCursor ?? 0)) ?>"
             data-request-sync-url="<?= Html::encode(Url::to(['/request-sync/changes'])) ?>"
             data-request-sync-fragment-url="<?= Html::encode(Url::current()) ?>">
            <?= $this->render('_appointment-list', [
                'appointments' => $appointments,
                'pagination' => $pagination,
                'aoMap' => $aoMap,
                'requestMap' => $requestMap,
                'cols' => $cols,
            ]) ?>
        </div>
    </div>
</main>

<!-- SHARED REQUEST DETAILS: one consistent modal for all harmonized list pages. -->
<?= $this->render('../shared/_request-details-modal') ?>

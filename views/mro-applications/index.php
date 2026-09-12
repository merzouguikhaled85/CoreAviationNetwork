<?php

use app\models\AoRequestsApplications;
use yii\helpers\Html;
use yii\helpers\Url;
use app\components\UrlIdHelper;

use yii\widgets\LinkPager; // Widget Yii2 utilise pour afficher la pagination

$this->title = $title;

// Mot cle actuel de recherche.
// Le controller envoie $search, et on garde aussi q comme ancien parametre compatible.
$searchQuery = trim((string) ($search ?? Yii::$app->request->get('search', Yii::$app->request->get('q', ''))));

// CLOSED HISTORY: values are supplied only by actionClosedRequests().
$isClosedHistory = $title === 'Closed Requests';
$selectedHistoryPeriod = (string) ($historyPeriod ?? Yii::$app->request->get('period', '3months'));
$availableHistoryYears = $historyYears ?? [];


/* YiiAsset is required for data-confirm and data-method handling. */
\yii\web\YiiAsset::register($this);

/* Bootstrap Icons */
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

/* SweetAlert2 */
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'position' => \yii\web\View::POS_HEAD
]);

/* Custom page design */
$this->registerCss("
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
        border-radius: 8px;
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
        border-radius: 8px;
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
        border-radius: 8px;
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

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 9px;
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

    .status-canceled {
        background: #fee2e2;
        color: #991b1b;
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
        transition: background .2s ease;
        box-shadow: 0 7px 16px rgba(15, 23, 42, .12);
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
        background: #e40fc7ff;
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
        background: #f3d00bff;
    }

    .btn-reports {
        background: #a7df24ff;
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
        max-width: 100%;
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
        border-radius: 11px;
        border: 1px solid #dbeafe;
        color: #2563eb;
        background: #ffffff;
        text-decoration: none;
        font-weight: 800;
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
        border-radius: 8px;
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
        border-radius: 8px;
        border: 1px solid #dbeafe;
        background: #ffffff;
        padding: 10px 14px 10px 42px;
        color: #334155;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        transition: background .2s ease;
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

    /* Closed request history selector */
    .history-filter-wrap {
        position: relative;
        flex: 0 1 210px;
        min-width: 185px;
    }

    .history-filter-wrap > i {
        position: absolute;
        left: 14px;
        top: 50%;
        z-index: 2;
        color: #2563eb;
        pointer-events: none;
        transform: translateY(-50%);
    }

    .history-filter-select {
        width: 100%;
        height: 44px;
        padding: 10px 36px 10px 40px;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        color: #1e3a8a;
        background-color: #eff6ff;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(37, 99, 235, .08);
    }

    .history-filter-select:focus {
        border-color: #2563eb;
        outline: none;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
    }

    .btn-search,
    .btn-reset {
        height: 44px;
        border-radius: 8px;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        white-space: nowrap;
        transition: background .2s ease;
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
        background: #050505ff;
        color: #fbfcfdff !important;
        border: 1px solid #cbd5e1;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        text-decoration: none;
    }

    .btn-reset:hover {
        background: #f8f8f8ff;
        color: #334155 !important;
        transform: translateY(-1px);
        border-color: #94a3b8;
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
        border-radius: 8px !important;
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


    /* Generic SweetAlert2 action design */
    .swal2-popup.custom-action-popup {
        width: 390px !important;
        max-width: 92vw !important;
        border-radius: 8px !important;
        padding: 18px 20px 18px !important;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.24) !important;
    }

    .swal2-popup.custom-action-popup .swal2-icon {
        width: 52px !important;
        height: 52px !important;
        margin: 8px auto 12px !important;
    }

    .swal2-popup.custom-action-popup .swal2-icon .swal2-icon-content {
        font-size: 32px !important;
    }

    .swal2-title.custom-action-title {
        color: #0f172a !important;
        font-size: 20px !important;
        font-weight: 800 !important;
        padding: 0 !important;
        margin: 0 0 8px !important;
    }

    .swal2-html-container.custom-action-message {
        color: #64748b !important;
        font-size: 13px !important;
        line-height: 1.45 !important;
        margin: 0 8px 14px !important;
    }

    .swal-action-confirm,
    .swal-action-cancel {
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

    .swal-action-info {
        background: #2563eb !important;
        color: #ffffff !important;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25) !important;
    }

    .swal-action-success {
        background: #16a34a !important;
        color: #ffffff !important;
        box-shadow: 0 8px 18px rgba(22, 163, 74, 0.25) !important;
    }

    .swal-action-warning {
        background: #f59e0b !important;
        color: #ffffff !important;
        box-shadow: 0 8px 18px rgba(245, 158, 11, 0.25) !important;
    }

    .swal-action-danger {
        background: #ef4444 !important;
        color: #ffffff !important;
        box-shadow: 0 8px 18px rgba(239, 68, 68, 0.25) !important;
    }

    .swal-action-confirm:hover,
    .swal-action-cancel:hover {
        transform: translateY(-1px);
    }

    .swal-action-cancel {
        background: #e2e8f0 !important;
        color: #334155 !important;
    }

    .swal-action-cancel:hover {
        background: #cbd5e1 !important;
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
            border-radius: 8px;
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
            border-radius: 8px;
            padding: 16px;
        }

        .content-card {
            border-radius: 8px;
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
            border-radius: 8px;
        }

        .search-filter-form {
            gap: 8px;
        }

        .search-input-wrap {
            flex-basis: 100%;
            min-width: 100%;
        }

        /* CLOSED HISTORY: keep the period selector readable on mobile. */
        .history-filter-wrap {
            flex-basis: 100%;
            min-width: 100%;
        }

        .btn-search,
        .btn-reset {
            width: 100%;
        }

    }
");

/*
 * SweetAlert2 confirmation for every action using Yii2 data-confirm.
 * Each link can customize the popup with data-swal-* attributes.
 * Yii2 keeps data-method='post' for sensitive actions.
 */
$this->registerJs(<<<JS
(function () {
    // Initialize Bootstrap tooltips safely.
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]'));

        tooltipTriggerList.map(function (el) {
            return new bootstrap.Tooltip(el);
        });
    }

    // Keep legacy `q` filter synchronized with the visible `search` input.
    var mroApplicationSearchForm = document.getElementById('mro-application-search-form');
    if (mroApplicationSearchForm) {
        mroApplicationSearchForm.addEventListener('submit', function () {
            var searchInput = mroApplicationSearchForm.querySelector('input[name="search"]');
            var legacyInput = document.getElementById('legacy-q-filter');

            if (searchInput && legacyInput) {
                legacyInput.value = searchInput.value;
            }
        });
    }

    var lastConfirmElement = null;

    // Capture the clicked link before Yii2 calls yii.confirm().
    document.addEventListener('click', function (event) {
        var clickedAction = event.target.closest('a[data-confirm]');
        if (clickedAction) {
            lastConfirmElement = clickedAction;
        }
    }, true);

    function getSwalData(element, name, fallback) {
        if (!element) {
            return fallback;
        }

        var value = element.getAttribute('data-swal-' + name);
        return value !== null && value !== '' ? value : fallback;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function hideTooltip(element) {
        if (!element || typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
            return;
        }

        var tooltip = bootstrap.Tooltip.getInstance(element);
        if (tooltip) {
            tooltip.hide();
        }
    }

    // Replace Yii2 native confirm with SweetAlert2.
    if (typeof yii !== 'undefined') {
        yii.confirm = function (message, okCallback, cancelCallback) {
            var actionElement = lastConfirmElement;

            if (typeof Swal === 'undefined') {
                if (confirm(message)) {
                    okCallback();
                } else if (cancelCallback) {
                    cancelCallback();
                }

                return;
            }

            hideTooltip(actionElement);

            var icon = getSwalData(actionElement, 'icon', 'question');
            var title = getSwalData(actionElement, 'title', 'Confirm action?');
            var text = getSwalData(actionElement, 'text', message);
            // ACTION BUTTON CONTENT: ensure even fallback labels are meaningful and icon-led.
            var confirmText = getSwalData(actionElement, 'confirm-text', '<i class="bi bi-check-circle"></i> Continue');
            var cancelText = getSwalData(actionElement, 'cancel-text', '<i class="bi bi-arrow-counterclockwise"></i> Review action');
            var confirmClass = getSwalData(actionElement, 'confirm-class', 'swal-action-confirm swal-action-info');

            // Add the Request ID to the SweetAlert message for every action when it is available.
            var requestMatch = String(message || '').match(/request\s*#\s*([A-Za-z0-9_-]+)/i);
            if (requestMatch && !/request\s*#/i.test(String(text || ''))) {
                text = 'Request #' + requestMatch[1] + ': ' + text;
            }

            Swal.fire({
                width: 390,
                title: escapeHtml(title),
                html:
                    '<div style="text-align:center;">' +
                        '<div style="font-size:13px;color:#64748b;line-height:1.45;">' + escapeHtml(text) + '</div>' +
                    '</div>',
                icon: icon,
                showCancelButton: true,
                reverseButtons: true,
                focusCancel: true,
                allowOutsideClick: false,
                allowEscapeKey: true,
                confirmButtonText: confirmText,
                cancelButtonText: cancelText,
                buttonsStyling: false,
                customClass: {
                    popup: 'custom-action-popup',
                    title: 'custom-action-title',
                    htmlContainer: 'custom-action-message',
                    confirmButton: confirmClass,
                    cancelButton: 'swal-action-cancel'
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
})();
JS, \yii\web\View::POS_READY);
?>

<!-- SHARED LIST: presentation is centralized; application workflow remains unchanged. -->
<main class="dash-content requests-page can-list-page">
    <div class="container-fluid">

        <!-- Page header: same request structure -->
        <div class="page-header-card">
            <div class="header-title-group">
                <div class="header-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>

                <div>
                    <h1 class="dash-title"><?= Html::encode($this->title) ?></h1>
                    <div class="subtitle-text">
                        Manage and track your maintenance requests.
                    </div>
                </div>
            </div>

            <?php if ($title === 'New requests'): ?>
                <?= Html::a(
                    '<i class="bi bi-plus-circle"></i> Add Request',
                    ['create'],
                    ['class' => 'btn btn-outline-success btn-add-request']
                ) ?>
            <?php endif; ?>
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
            <?php
            // FILTER FORM: reset Closed Requests to its default three-month period.
            $filterUrl = Url::current([
                'search' => null,
                'q' => null,
                'page' => null,
                'period' => $isClosedHistory ? '3months' : null,
            ]);
            ?>

            <?= Html::beginForm($filterUrl, 'get', [
                'class' => 'search-filter-form',
                'role' => 'search',
                'id' => 'mro-application-search-form'
            ]) ?>
                <div class="search-input-wrap">
                    <i class="bi bi-search"></i>
                    <?= Html::textInput('search', $searchQuery, [
                        'class' => 'form-control search-input',
                        'placeholder' => 'Search by request ID, aircraft, registration, serial number, location or status...',
                        'autocomplete' => 'off',
                    ]) ?>
                    <?= Html::hiddenInput('q', $searchQuery, ['id' => 'legacy-q-filter']) ?>
                </div>

                <?php if ($isClosedHistory): ?>
                    <!-- CLOSED HISTORY: default period plus automatically available years. -->
                    <div class="history-filter-wrap">
                        <i class="bi bi-calendar3"></i>
                        <?= Html::dropDownList(
                            'period',
                            $selectedHistoryPeriod,
                            ['3months' => 'Last 3 months'] + array_combine($availableHistoryYears, $availableHistoryYears),
                            [
                                'class' => 'form-select history-filter-select',
                                'aria-label' => 'Closed request history period',
                                'onchange' => 'this.form.submit()',
                            ]
                        ) ?>
                    </div>
                <?php endif; ?>

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

            <?php if ($searchQuery !== '' || $isClosedHistory): ?>
                <div class="search-result-text">
                    <i class="bi bi-funnel"></i>
                    <?php if ($searchQuery !== ''): ?>
                        Active search: <strong><?= Html::encode($searchQuery) ?></strong>
                    <?php endif; ?>
                    <?php if ($isClosedHistory): ?>
                        <?= $searchQuery !== '' ? ' &middot; ' : '' ?>
                        History: <strong><?= Html::encode(
                            $selectedHistoryPeriod === '3months'
                                ? 'Last 3 months'
                                : $selectedHistoryPeriod
                        ) ?></strong>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php
        /*
         * CONTENEUR SYNCHRONISÉ DES APPLICATIONS : le serveur limite les signaux
         * aux candidatures appartenant au MRO authentifié. L'URL courante préserve
         * la recherche, la pagination et la période choisie pour l'historique fermé.
         * Le curseur initial protège également le retour depuis un onglet masqué.
         */
        ?>
        <div
            id="mro-applications-list"
            data-request-sync-context="mro-applications"
            data-request-sync-cursor="<?= Html::encode((string) ($syncCursor ?? 0)) ?>"
            data-request-sync-url="<?= Html::encode(Url::to(['/request-sync/changes'])) ?>"
            data-request-sync-fragment-url="<?= Html::encode(Url::current()) ?>"
        >
            <?= $this->render('_application-list', [
                'appliedRequests' => $appliedRequests,
                'pagination' => $pagination,
            ]) ?>
        </div>

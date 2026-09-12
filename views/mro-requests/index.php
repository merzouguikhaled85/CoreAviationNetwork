<?php

/** @var yii\web\View $this */
/** @var array $requests */
/** @var string|null $title */
/** @var yii\data\Pagination|null $pagination */
/** @var string|null $search */

use app\models\AoProfile;
use app\models\Aircrafts;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\components\UrlIdHelper;
use app\models\Requests;


$this->title = $title ?? 'New Requests';

// Current search keyword from GET.
// Keep the same `search` parameter if the controller already supports filtering.
$searchQuery = trim((string) ($search ?? Yii::$app->request->get('search', '')));

/*
 * ÉTAT DU FILTRE PRIORITY : la vue accepte uniquement les valeurs exposées par
 * le modèle afin que l'option active corresponde toujours au filtre serveur.
 */
$priorityFilter = strtolower(trim((string) Yii::$app->request->get('priority', '')));
if (!array_key_exists($priorityFilter, Requests::getOperationalPriorityOptions())) {
    $priorityFilter = '';
}

// Bootstrap Icons.
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

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

    .location-text {
        display: inline-block;
        max-width: 220px;
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
    .status-applied {
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
    .status-rejected {
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

    .btn-apply {
        background: #0ea5e9;
    }

    .btn-recommend {
        background: #64748b;
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

        .requests-table td.location-cell {
            max-width: 100%;
        }

        .location-text {
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
        .btn-reset {
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

?>

<!--
    PRÉSENTATION PARTAGÉE DE LA LISTE : la structure visuelle est harmonisée,
    tandis que les règles d'accès et d'éligibilité MRO restent dans le contrôleur.
-->
<main class="dash-content requests-page can-list-page">
    <div class="container-fluid">

        <!-- Page header: same request structure -->
        <div class="page-header-card">
            <div class="header-title-group">
                <div class="header-icon">
                    <i class="bi bi-airplane-engines"></i>
                </div>

                <div>
                    <h1 class="dash-title"><?= Html::encode($this->title) ?></h1>
                    <div class="subtitle-text">
                        Incoming AOG / maintenance requests with operator, aircraft, ETA / ETD,
                        maintenance location and quick actions for your MRO.
                    </div>
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

        <!-- Search and reset filter: same request structure -->
        <div class="search-filter-card">
            <?php $filterUrl = Url::current(['search' => null, 'priority' => null, 'page' => null]); ?>

            <?= Html::beginForm($filterUrl, 'get', [
                'class' => 'search-filter-form',
                'role' => 'search',
            ]) ?>
                <div class="search-input-wrap">
                    <i class="bi bi-search"></i>
                    <?= Html::textInput('search', $searchQuery, [
                        'class' => 'form-control search-input',
                        'placeholder' => 'Search by request ID, operator, aircraft, registration, status or location...',
                        'autocomplete' => 'off',
                    ]) ?>
                </div>

                <!-- FILTRE PRIORITY : combinable avec la recherche texte MRO. -->
                <div class="priority-filter-wrap">
                    <i class="bi bi-broadcast-pin" aria-hidden="true"></i>
                    <?= Html::dropDownList(
                        'priority',
                        $priorityFilter,
                        Requests::getOperationalPriorityOptions(),
                        [
                            'prompt' => 'All priorities',
                            'class' => 'form-select priority-filter-select',
                            'aria-label' => 'Filter by operational priority',
                        ]
                    ) ?>
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

            <?php if ($searchQuery !== '' || $priorityFilter !== ''): ?>
                <div class="search-result-text">
                    <i class="bi bi-funnel"></i>
                    <?php if ($searchQuery !== ''): ?>
                        Search: <strong><?= Html::encode($searchQuery) ?></strong>
                    <?php endif; ?>
                    <?php if ($priorityFilter !== ''): ?>
                        <span class="active-priority-filter">
                            Priority:
                            <strong><?= Html::encode(Requests::getOperationalPriorityOptions()[$priorityFilter]) ?></strong>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php
        /*
         * CONTENEUR SYNCHRONISÉ MRO : le contexte est contrôlé par le serveur et
         * ne retourne que les demandes accessibles au MRO connecté. L'URL courante
         * conserve le filtre de recherche et le numéro de page pendant le rendu AJAX.
         * Le curseur serveur constitue un point de reprise fiable après masquage.
         */
        ?>
        <div
            id="mro-requests-list"
            data-request-sync-context="mro-requests"
            data-request-sync-cursor="<?= Html::encode((string) ($syncCursor ?? 0)) ?>"
            data-request-sync-url="<?= Html::encode(Url::to(['/request-sync/changes'])) ?>"
            data-request-sync-fragment-url="<?= Html::encode(Url::current()) ?>"
        >
            <?= $this->render('_request-list', [
                'requests' => $requests,
                'pagination' => $pagination,
                'searchQuery' => $searchQuery,
            ]) ?>
        </div>
    </div>
</main>

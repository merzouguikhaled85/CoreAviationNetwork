<?php

/** @var yii\web\View $this */
/** @var array $groupedConversations */
/** @var yii\data\Pagination|null $pagination */
/** @var string|null $search */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\components\UrlIdHelper;

$this->title = 'Messages';

// Keep variables safe if the controller does not send them.
$groupedConversations = $groupedConversations ?? [];
$pagination = $pagination ?? null;

// Current search keyword from GET or controller variable.
$searchQuery = trim((string) ($search ?? Yii::$app->request->get('search', '')));

/**
 * Return the first message in a grouped conversation safely.
 */
$getLastMessage = static function ($conversations) {
    if (is_array($conversations)) {
        return $conversations[0] ?? null;
    }

    if ($conversations instanceof \ArrayAccess) {
        return $conversations[0] ?? null;
    }

    if ($conversations instanceof \Traversable) {
        foreach ($conversations as $conversation) {
            return $conversation;
        }
    }

    return null;
};

/**
 * Get a username from a related user model without throwing warnings.
 */
$getUsername = static function ($user): string {
    if (!$user) {
        return 'N/A';
    }

    return (string) ($user->username ?? $user->email ?? $user->name ?? 'N/A');
};

/**
 * LIST DISPLAY: format ETA/ETD safely while keeping the stored value as a fallback.
 */
$formatRequestDate = static function ($value): string {
    if (empty($value)) {
        return 'N/A';
    }

    try {
        return Yii::$app->formatter->asDatetime($value, 'php:d M Y H:i');
    } catch (\Throwable $exception) {
        return (string) $value;
    }
};

// Optional view-level filter. This keeps the search box useful even if the controller only sends grouped conversations.
if ($searchQuery !== '') {
    $needle = mb_strtolower($searchQuery, 'UTF-8');

    $groupedConversations = array_filter($groupedConversations, static function ($conversations) use ($getLastMessage, $getUsername, $needle) {
        $lastMessage = $getLastMessage($conversations);

        if (!$lastMessage) {
            return false;
        }

        $senderName = $getUsername($lastMessage->sender ?? null);
        $receiverName = $getUsername($lastMessage->receiver ?? null);
        // LIST SEARCH: mirror the controller filter for request details shown in the row.
        $request = $lastMessage->request ?? null;
        $aircraft = $request ? $request->aircraft : null;

        $haystack = implode(' ', [
            (string) ($lastMessage->request_id ?? ''),
            (string) ($lastMessage->chat_id ?? ''),
            $senderName,
            $receiverName,
            (string) ($lastMessage->message ?? ''),
            (string) ($lastMessage->timestamp ?? ''),
            (string) ($aircraft->manufacturer ?? ''),
            (string) ($aircraft->model ?? ''),
            (string) ($request->aircraft_registration ?? ''),
            (string) ($request->serial_number ?? ''),
            (string) ($request->eta ?? ''),
            (string) ($request->etd ?? ''),
            (string) ($request->location ?? ''),
            (string) ($request->request_details ?? ''),
        ]);

        return mb_stripos($haystack, $needle, 0, 'UTF-8') !== false;
    });
}

// Bootstrap Icons: same icon family used by MRO Aircraft Certificates.
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

// Custom page design: same CSS/HTML structure used by MRO Aircraft Certificates.
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
        max-width: 220px;
        background: #ffffff;
    }

    .requests-table tbody tr {
        transition: background .2s ease;
    }

    .requests-table tbody tr:hover td {
        background: #f8fbff;
    }

    .message-badge,
    .request-badge,
    .user-badge {
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

    .request-badge {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .user-badge {
        background: #dcfce7;
        color: #166534;
    }

    .sender-me {
        background: #e0f2fe;
        color: #0369a1;
    }

    .sender-other {
        background: #f1f5f9;
        color: #334155;
    }

    .message-preview {
        display: block;
        max-width: 360px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 600;
    }

    .time-text {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        font-weight: 700;
        white-space: nowrap;
    }

    /* REQUEST DETAILS: compact operational summary embedded in each conversation row. */
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
    .request-location-line {
        display: flex;
        align-items: center;
        gap: 7px;
        min-width: 0;
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

    .request-schedule {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 5px;
    }

    .request-date-item {
        padding: 5px 7px;
        border-radius: 7px;
        background: #eef2ff;
        color: #4338ca;
        font-size: 10.5px;
        font-weight: 700;
        white-space: nowrap;
    }

    .request-date-item strong {
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
        display: flex;
        align-items: flex-start;
        gap: 7px;
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

    /* Pagination: same design used by mro-appointments */
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

    .alert {
        border-radius: 14px;
        border: none;
        padding: 14px 18px;
        font-weight: 600;
        box-shadow: 0 5px 16px rgba(15, 23, 42, .08);
    }

    @media (max-width: 1400px) {
        .requests-table thead th {
            font-size: 10px;
            padding: 12px 5px;
        }

        .requests-table tbody td {
            font-size: 12px;
            padding: 12px 5px;
            max-width: 170px;
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
            max-width: 140px;
        }
    }

    /* Tablet and mobile: convert table rows to cards to keep the same structure */
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

        .message-preview {
            max-width: 100%;
            white-space: normal;
            text-align: right;
        }

        /* REQUEST DETAILS: use the available card width on tablet layouts. */
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
        .btn-reset {
            width: 100%;
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

        .message-preview {
            text-align: left;
        }

        /* REQUEST DETAILS: stack the summary below its label on narrow screens. */
        .request-details-cell .request-row-summary {
            width: 100%;
        }

        .action-buttons {
            justify-content: flex-start;
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

<!-- PHASE 6: shared list presentation; conversation access rules remain unchanged. -->
<main class="dash-content requests-page can-list-page">
    <div class="container-fluid">

        <!-- Page header: same structure as MRO Aircraft Certificates -->
        <div class="page-header-card">
            <div class="header-title-group">
                <span class="header-icon">
                    <i class="bi bi-chat-dots-fill"></i>
                </span>

                <div>
                    <h1 class="dash-title">MRO Messages</h1>
                    <div class="subtitle-text">
                        View your latest conversations and continue discussions with operators.
                    </div>
                </div>
            </div>
        </div>

        <!-- Search card: same structure as MRO Aircraft Certificates -->
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
                        'placeholder' => 'Search by request, aircraft, registration, location, user or message...',
                        'aria-label' => 'Search messages',
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
                        'title' => 'Reset search and show all conversations',
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

        <!-- Content card: same table structure as MRO Aircraft Certificates -->
        <div class="content-card">
            <div class="table-responsive-custom">
                <table class="table requests-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Request Details</th>
                            <th>Conversation With</th>
                            <th>Last Sender</th>
                            <th>Last Message</th>
                            <th>Timestamp</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($groupedConversations)): ?>
                            <?php foreach ($groupedConversations as $key => $conversations): ?>
                                <?php
                                    $lastMessage = $getLastMessage($conversations);

                                    if (!$lastMessage) {
                                        continue;
                                    }

                                    $requestId = $lastMessage->request_id ?? 'N/A';
                                    $chatId = $lastMessage->chat_id ?? null;

                                    $senderName = $getUsername($lastMessage->sender ?? null);
                                    $receiverName = $getUsername($lastMessage->receiver ?? null);
                                    $currentUsername = (string) Yii::$app->session->get('username');

                                    $isMe = $senderName !== 'N/A' && $senderName === $currentUsername;
                                    $conversationWith = $isMe ? $receiverName : $senderName;
                                    $lastSender = $isMe ? 'Me' : $senderName;
                                    $messagePreview = (string) ($lastMessage->message ?? '');
                                    $timestamp = $lastMessage->timestamp ?? null;

                                    // REQUEST DETAILS: prepare a safe compact summary without changing business data.
                                    $requestModel = $lastMessage->request ?? null;
                                    $aircraft = $requestModel ? $requestModel->aircraft : null;
                                    $manufacturer = trim((string) ($aircraft->manufacturer ?? ''));
                                    $modelName = trim((string) ($aircraft->model ?? ''));
                                    // REQUEST DETAILS: avoid repeating the manufacturer when it is already in the model name.
                                    $aircraftName = $modelName !== '' && $manufacturer !== '' && stripos($modelName, $manufacturer) === 0
                                        ? $modelName
                                        : trim($manufacturer . ' ' . $modelName);
                                    $aircraftName = $aircraftName !== '' ? $aircraftName : 'Aircraft unavailable';
                                    $registration = $requestModel->aircraft_registration ?? $aircraft->registration_number ?? 'N/A';
                                    $serialNumber = $requestModel->serial_number ?? $aircraft->serial_number ?? 'N/A';
                                    $maintenanceLocation = $requestModel->location ?? 'N/A';
                                    $requestDescription = trim((string) ($requestModel->request_details ?? ''));
                                ?>
                                <tr>
                                    <td data-label="Request ID">
                                        <span class="request-badge">
                                            <i class="bi bi-hash"></i>
                                            <?= Html::encode($requestId) ?>
                                        </span>
                                    </td>

                                    <td data-label="Request Details" class="request-details-cell">
                                        <!-- PHASE 6: the row stays concise; details remain available in place. -->
                                        <?= $this->render('../shared/_request-summary', [
                                            'requestModel' => $requestModel,
                                            'aircraft' => $aircraft,
                                        ]) ?>
                                    </td>

                                    <td data-label="Conversation With">
                                        <span class="user-badge">
                                            <i class="bi bi-person"></i>
                                            <?= Html::encode($conversationWith) ?>
                                        </span>
                                    </td>

                                    <td data-label="Last Sender">
                                        <span class="message-badge <?= $isMe ? 'sender-me' : 'sender-other' ?>">
                                            <i class="bi <?= $isMe ? 'bi-send-check' : 'bi-person-lines-fill' ?>"></i>
                                            <?= Html::encode($lastSender) ?>
                                        </span>
                                    </td>

                                    <td data-label="Last Message">
                                        <span class="message-preview" title="<?= Html::encode($messagePreview) ?>">
                                            <?= Html::encode($messagePreview !== '' ? $messagePreview : 'N/A') ?>
                                        </span>
                                    </td>

                                    <td data-label="Timestamp">
                                        <span class="time-text">
                                            <i class="bi bi-clock"></i>
                                            <?= $timestamp ? Html::encode(Yii::$app->formatter->asDatetime($timestamp)) : 'N/A' ?>
                                        </span>
                                    </td>

                                    <td data-label="Actions">
                                        <div class="action-buttons">
                                            <?php if ($chatId !== null && $chatId !== ''): ?>
                                                <?= Html::a(
                                                    '<i class="bi bi-eye"></i>',
                                                    // SECURITY: conversation links expose only the signed chat token.
                                                    ['conversations/view', 'chat_id' => UrlIdHelper::encode($chatId)],
                                                    [
                                                        'class' => 'action-btn btn-view',
                                                        'title' => 'View conversation for Request #' . $requestId,
                                                        'aria-label' => 'View conversation for Request #' . $requestId,
                                                        'data-bs-toggle' => 'tooltip',
                                                    ]
                                                ) ?>
                                            <?php else: ?>
                                                <span class="text-muted" title="Missing chat id">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <div class="empty-state-title">
                                            <?php if ($searchQuery !== ''): ?>
                                                No conversations found for your current search.
                                            <?php else: ?>
                                                No conversations found.
                                            <?php endif; ?>
                                        </div>
                                        <div class="empty-state-text">
                                            <?php if ($searchQuery !== ''): ?>
                                                Try another request ID, user, message or date.
                                            <?php else: ?>
                                                No messages are available for the moment.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($pagination)): ?>
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

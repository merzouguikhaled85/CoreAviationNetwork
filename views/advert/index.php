<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;

$this->title = 'Adverts';

/* Bootstrap Icons */
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

/* SweetAlert2 */
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

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
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 800;
        padding: 13px 8px;
        border: none;
        white-space: nowrap;
    }

    .requests-table tbody td,
    .requests-table tbody th {
        padding: 13px 8px;
        vertical-align: middle;
        border-top: 1px solid #eef2f7;
        color: #374151;
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 220px;
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

    .admin-badge,
    .type-badge,
    .status-badge,
    .date-badge,
    .empty-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
        text-decoration: none;
    }

    .admin-badge {
        background: #f1f5f9;
        color: #475569;
    }

    .type-badge {
        background: #e0f2fe;
        color: #0369a1;
    }

    .date-badge {
        background: #eef2ff;
        color: #4338ca;
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
    .status-enabled,
    .status-published {
        background: #dcfce7;
        color: #166534;
    }

    .status-pending,
    .status-draft {
        background: #fef3c7;
        color: #92400e;
    }

    .status-inactive,
    .status-disabled,
    .status-rejected,
    .status-expired {
        background: #fee2e2;
        color: #991b1b;
    }

    .advert-media-wrap {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 96px;
        height: 64px;
        border-radius: 14px;
        overflow: hidden;
        background: #f8fafc;
        border: 1px solid #e5eaf3;
        box-shadow: 0 5px 14px rgba(15, 23, 42, 0.10);
        position: relative;
    }

    .media-preview-trigger {
        cursor: pointer;
    }

    .media-preview-trigger::after {
        content: '\F642'; /* bi-zoom-in */
        font-family: bootstrap-icons !important;
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.4);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .media-preview-trigger:hover::after {
        opacity: 1;
    }

    .advert-media-img,
    .advert-media-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .content-name {
        display: inline-block;
        max-width: 160px;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }

    .action-buttons {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
        justify-content: flex-start;
        min-width: 135px;
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

    .btn-toggle-status {
        background: #8b5cf6;
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
            font-size: 10px;
            padding: 12px 5px;
        }

        .requests-table tbody td,
        .requests-table tbody th {
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

        .advert-media-wrap {
            width: 82px;
            height: 56px;
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

        .requests-table tbody td,
        .requests-table tbody th {
            font-size: 12.5px;
            padding: 11px 4px;
            max-width: 125px;
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

        .action-buttons {
            justify-content: flex-end;
            flex-wrap: wrap;
            min-width: unset;
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

        .action-buttons {
            justify-content: flex-start;
        }
    }
");

/*
 * SweetAlert2 confirmation for Yii2 data-confirm.
 * Yii2 keeps data-method='post', so delete/toggle requests are sent as POST.
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
                        <i class="bi bi-megaphone"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Manage adverts, media content, dates, and publication status
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-plus-circle"></i> Create Advert',
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

        <!-- Adverts table card -->
        <div class="content-card">
            <div class="table-responsive-custom">
                <table class="table requests-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Admin Username</th>
                            <th>Advert Type</th>
                            <th>Content</th>
                            <th>Timestamp</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($adverts)): ?>
                            <?php foreach ($adverts as $advert): ?>
                                <?php
                                    $status = strtolower((string) $advert->status);
                                    $statusClass = 'status-badge';

                                    if (in_array($status, ['active', 'approved', 'enabled', 'published'], true)) {
                                        $statusClass .= ' status-active';
                                    } elseif (in_array($status, ['pending', 'draft'], true)) {
                                        $statusClass .= ' status-pending';
                                    } elseif (in_array($status, ['inactive', 'disabled', 'rejected', 'expired'], true)) {
                                        $statusClass .= ' status-inactive';
                                    }

                                    $isUrl = strpos($advert->content, 'http://') === 0 || strpos($advert->content, 'https://') === 0;
                                    $contentUrl = $isUrl ? $advert->content : Yii::getAlias('@web/uploads/') . $advert->content;
                                ?>

                                <tr>
                                    <td data-label="ID">
                                        <?= Html::a(
                                            '#' . Html::encode($advert->advert_id),
                                            ['view', 'id' => $advert->advert_id],
                                            ['class' => 'request-link']
                                        ) ?>
                                    </td>

                                    <td data-label="Admin Username">
                                        <?php if ($advert->admin): ?>
                                            <span class="admin-badge">
                                                <i class="bi bi-person-badge"></i>
                                                <?= Html::encode($advert->admin->username) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="empty-badge">
                                                <i class="bi bi-person-x"></i>
                                                N/A
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Advert Type">
                                        <span class="type-badge">
                                            <?php if ($advert->advert_type == 'photo'): ?>
                                                <i class="bi bi-image"></i>
                                            <?php else: ?>
                                                <i class="bi bi-play-btn"></i>
                                            <?php endif; ?>

                                            <?= Html::encode(ucfirst((string) $advert->advert_type)) ?>
                                        </span>
                                    </td>

                                    <td data-label="Content">
                                        <?php if (!empty($advert->content)): ?>
                                            <div class="advert-media-wrap media-preview-trigger" data-type="<?= Html::encode($advert->advert_type) ?>" data-src="<?= Html::encode($contentUrl) ?>" title="Click to preview">
                                                <?php if ($advert->advert_type == 'photo'): ?>
                                                    <?= Html::img($contentUrl, [
                                                        'class' => 'advert-media-img',
                                                        'alt' => 'Advert photo',
                                                    ]) ?>
                                                <?php else: ?>
                                                    <video class="advert-media-video">
                                                        <source src="<?= Html::encode($contentUrl) ?>" type="video/mp4">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="empty-badge">
                                                <i class="bi bi-file-earmark-x"></i>
                                                N/A
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Timestamp">
                                        <span class="date-badge">
                                            <i class="bi bi-calendar-event"></i>
                                            <?= Html::encode($advert->timestamp) ?>
                                        </span>
                                    </td>

                                    <td data-label="Start Date">
                                        <span class="date-badge">
                                            <i class="bi bi-calendar-check"></i>
                                            <?= Html::encode($advert->start_date) ?>
                                        </span>
                                    </td>

                                    <td data-label="End Date">
                                        <span class="date-badge">
                                            <i class="bi bi-calendar-x"></i>
                                            <?= Html::encode($advert->end_date) ?>
                                        </span>
                                    </td>

                                    <td data-label="Status">
                                        <span class="<?= Html::encode($statusClass) ?>">
                                            <i class="bi bi-info-circle"></i>
                                            <?= Html::encode(ucfirst((string) $advert->status)) ?>
                                        </span>
                                    </td>

                                    <td data-label="Actions">
                                        <div class="action-buttons">

                                            <!-- Toggle status button -->
                                            <?= Html::a(
                                                '<i class="bi bi-arrow-repeat"></i>',
                                                ['toggle-status', 'id' => $advert->advert_id],
                                                [
                                                    'class' => 'action-btn btn-toggle-status',
                                                    'title' => 'Toggle Status',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'data' => [
                                                        'confirm' => 'Are you sure you want to change the status of this advert?',
                                                        'method' => 'post',
                                                    ],
                                                ]
                                            ) ?>

                                            <!-- View button -->
                                            <?= Html::a(
                                                '<i class="bi bi-eye"></i>',
                                                ['view', 'id' => $advert->advert_id],
                                                [
                                                    'class' => 'action-btn btn-view',
                                                    'title' => 'View',
                                                    'data-bs-toggle' => 'tooltip',
                                                ]
                                            ) ?>

                                            <!-- Update button -->
                                            <?= Html::a(
                                                '<i class="bi bi-pencil"></i>',
                                                ['update', 'id' => $advert->advert_id],
                                                [
                                                    'class' => 'action-btn btn-update',
                                                    'title' => 'Update',
                                                    'data-bs-toggle' => 'tooltip',
                                                ]
                                            ) ?>

                                            <!-- Delete button -->
                                            <?= Html::a(
                                                '<i class="bi bi-trash"></i>',
                                                ['delete', 'id' => $advert->advert_id],
                                                [
                                                    'class' => 'action-btn btn-delete',
                                                    'title' => 'Delete',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'data' => [
                                                        'confirm' => 'Advert #' . $advert->advert_id . ' will be permanently deleted.',
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
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox" style="font-size: 35px;"></i>
                                        <div>No adverts found.</div>
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

<?php
$this->registerJs(<<<JS
$(document).on('click', '.media-preview-trigger', function() {
    var type = $(this).data('type');
    var src = $(this).data('src');
    
    if (typeof Swal === 'undefined') return;

    var htmlContent = '';
    if (type === 'photo') {
        htmlContent = '<img src="' + src + '" style="max-width: 100%; max-height: 85vh; object-fit: contain; border-radius: 8px;">';
    } else {
        htmlContent = '<video src="' + src + '" controls autoplay style="max-width: 100%; max-height: 85vh; object-fit: contain; border-radius: 8px;"></video>';
    }

    Swal.fire({
        html: htmlContent,
        width: 'auto',
        showConfirmButton: false,
        showCloseButton: true,
        background: 'transparent',
        backdrop: 'rgba(15, 23, 42, 0.85)',
        customClass: {
            popup: 'p-0 overflow-hidden'
        }
    });
});
JS, \yii\web\View::POS_READY);
?>

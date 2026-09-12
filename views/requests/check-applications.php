<?php

use app\models\Currency;
use yii\helpers\Html;
use app\components\UrlIdHelper;


$this->title = 'My Replies';

$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'position' => \yii\web\View::POS_HEAD
]);

$this->registerCss(<<<CSS
:root {
    --deep-blue: #0D3261;
    --sky: #00B2FF;
    --gold: #EED811;
    --soft-border: #D8E4EE;
    --soft-bg: #F4F7FC;
    --text-main: #0F172A;
    --text-muted: #64748B;
    --success: #22C55E;
    --danger: #EF4444;
    --warning: #F59E0B;
}

.my-replies-page {
    min-height: calc(100vh - 70px);
    padding: 22px;
    margin: 0 -12px;
    background:
        radial-gradient(circle at 15% 20%, rgba(0,178,255,.12) 0%, transparent 35%),
        radial-gradient(circle at 85% 10%, rgba(238,216,17,.12) 0%, transparent 32%),
        linear-gradient(145deg, #EFF5FF 0%, #FFFFFF 45%, #F8FBFF 100%);
}

.my-replies-card {
    background: rgba(255,255,255,.98);
    border-radius: 8px;
    border: 1px solid var(--soft-border);
    box-shadow: 0 22px 55px rgba(15,23,42,.13);
    overflow: hidden;
    position: relative;
}

.my-replies-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 6px;
    background: linear-gradient(135deg, var(--deep-blue), var(--sky), var(--gold));
}

.my-replies-header {
    padding: 24px 28px 20px;
    background: linear-gradient(135deg, #FFFFFF, #F4F8FF);
    border-bottom: 1px solid rgba(226,232,240,.85);
}

.my-replies-title-row {
    display: flex;
    align-items: center;
    gap: 16px;
}

.my-replies-icon {
    width: 58px;
    height: 58px;
    border-radius: 8px;
    background: linear-gradient(135deg, #E7F4FF, #F7FBFF);
    color: var(--deep-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12px 26px rgba(13,50,97,.16);
}

.my-replies-icon i {
    font-size: 28px;
}

.my-replies-title {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    color: var(--text-main);
    letter-spacing: -.02em;
}

.my-replies-subtitle {
    margin-top: 4px;
    color: var(--text-muted);
    font-size: 13px;
}

.my-replies-body {
    padding: 20px 24px 26px;
}

.alert {
    border: none;
    border-radius: 8px;
    padding: 13px 16px;
    font-weight: 700;
    box-shadow: 0 8px 18px rgba(15,23,42,.08);
}

.alert-success {
    background: #DCFCE7;
    color: #166534;
}

.alert-danger {
    background: #FEE2E2;
    color: #991B1B;
}

.table-wrapper {
    border-radius: 8px;
    border: 1px solid var(--soft-border);
    overflow-x: auto;
    background: #FFFFFF;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
}

.replies-table {
    width: 100%;
    min-width: 1050px;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.replies-table thead th {
    background: #EAF1FB;
    color: #475569;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    padding: 15px 14px;
    border: none;
    white-space: nowrap;
}

.replies-table tbody td {
    padding: 15px 14px;
    vertical-align: middle;
    border-top: 1px solid rgba(226,232,240,.85);
    color: var(--text-main);
    font-size: 13px;
    background: #FFFFFF;
}

.replies-table tbody tr {
    transition: .22s ease;
}

.replies-table tbody tr:hover td {
    background: #F1F7FF;
}

.request-id-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 12px;
    border-radius: 8px;
    background: #E0F2FE;
    color: #0369A1;
    font-weight: 800;
    font-size: 12px;
}

.mro-name {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-weight: 800;
    color: var(--deep-blue);
}

.description-box {
    display: block;
    width: 100%;
    max-width: 360px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
    color: #334155;
    font-weight: 600;
}

/* APPLICATION DESCRIPTION 2026: keep long descriptions on one line on mobile too. */
.reply-mobile-value.description-single-line {
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.price-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 8px;
    background: #ECFDF5;
    color: #047857;
    font-weight: 800;
    font-size: 12px;
}

.currency-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 8px;
    background: #FEF3C7;
    color: #92400E;
    font-weight: 800;
    font-size: 12px;
}

.attachment-link,
.attachment-empty {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 13px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
    white-space: nowrap;
}

.attachment-link {
    background: #E7F4FF;
    color: var(--deep-blue);
    border: 1px solid #BFDBFE;
}

.attachment-link:hover {
    background: var(--deep-blue);
    color: #FFFFFF;
    text-decoration: none;
}

.attachment-empty {
    background: #F1F5F9;
    color: #94A3B8;
    border: 1px solid #E2E8F0;
}

.actions-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: nowrap;
}

.action-btn {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #FFFFFF !important;
    text-decoration: none;
    border: none;
    box-shadow: 0 8px 18px rgba(15,23,42,.16);
    transition: .22s ease;
}

.action-btn:hover {
    transform: translateY(-3px);
    color: #FFFFFF !important;
    text-decoration: none;
    box-shadow: 0 12px 26px rgba(15,23,42,.24);
}

.btn-accept {
    background: #22C55E;
}

.btn-view {
    background: #2563EB;
}

.btn-contact {
    background: #0EA5E9;
}

.btn-deny {
    background: #EF4444;
}

.btn-profile {
    background: #6366F1;
}

.empty-state {
    padding: 42px 20px;
    text-align: center;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 42px;
    color: #94A3B8;
    margin-bottom: 10px;
}

.empty-state-title {
    font-size: 19px;
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 4px;
}

/* Mobile cards */
.mobile-replies {
    display: none;
}

.reply-mobile-card {
    background: #FFFFFF;
    border: 1px solid var(--soft-border);
    border-radius: 8px;
    box-shadow: 0 12px 26px rgba(15,23,42,.10);
    margin-bottom: 14px;
    overflow: hidden;
}

.reply-mobile-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 15px;
    border-bottom: 1px solid rgba(226,232,240,.85);
}

.reply-mobile-row:last-child {
    border-bottom: none;
}

.reply-mobile-label {
    font-size: 11px;
    color: var(--text-muted);
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .07em;
    white-space: nowrap;
}

.reply-mobile-value {
    font-size: 13px;
    color: var(--text-main);
    font-weight: 700;
    text-align: right;
    word-break: break-word;
}

.reply-mobile-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}

/* SweetAlert2 shared */
.swal2-popup.custom-action-popup {
    width: 380px !important;
    max-width: 92vw !important;
    border-radius: 8px !important;
    padding: 18px 20px 18px !important;
    box-shadow: 0 18px 45px rgba(15,23,42,.24) !important;
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
    color: #0F172A !important;
    font-size: 20px !important;
    font-weight: 800 !important;
    padding: 0 !important;
    margin: 0 0 8px !important;
}

.swal2-html-container.custom-action-message {
    color: #64748B !important;
    font-size: 13px !important;
    line-height: 1.45 !important;
    margin: 0 8px 14px !important;
}

.swal2-actions {
    margin-top: 10px !important;
    gap: 8px !important;
}

.swal-action-confirm,
.swal-action-cancel {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 7px !important;
    border-radius: 8px !important;
    padding: 9px 14px !important;
    font-size: 13px !important;
    font-weight: 800 !important;
    border: none !important;
    min-width: 115px !important;
    height: 39px !important;
    transition: .2s ease !important;
}

.swal-action-confirm {
    color: #FFFFFF !important;
}

.swal-action-confirm.accept-confirm {
    background: #22C55E !important;
    box-shadow: 0 8px 18px rgba(34,197,94,.25) !important;
}

.swal-action-confirm.accept-confirm:hover {
    background: #16A34A !important;
    transform: translateY(-1px);
}

.swal-action-confirm.deny-confirm {
    background: #EF4444 !important;
    box-shadow: 0 8px 18px rgba(239,68,68,.25) !important;
}

.swal-action-confirm.deny-confirm:hover {
    background: #DC2626 !important;
    transform: translateY(-1px);
}

.swal-action-cancel {
    background: #E2E8F0 !important;
    color: #334155 !important;
}

.swal-action-cancel:hover {
    background: #CBD5E1 !important;
    transform: translateY(-1px);
}

@media (max-width: 992px) {
    .replies-table {
        display: none;
    }

    .mobile-replies {
        display: block;
    }

    .table-wrapper {
        border: none;
        background: transparent;
        box-shadow: none;
    }

    .my-replies-page {
        padding: 12px;
    }

    .my-replies-header {
        padding: 20px 16px;
    }

    .my-replies-body {
        padding: 14px;
    }

    .my-replies-title {
        font-size: 22px;
    }

    .my-replies-icon {
        width: 52px;
        height: 52px;
    }
}

@media (max-width: 576px) {
    .my-replies-title-row {
        align-items: flex-start;
    }

    .reply-mobile-row {
        align-items: flex-start;
    }

    .reply-mobile-value {
        max-width: 62%;
    }

    .action-btn {
        width: 36px;
        height: 36px;
    }

    .swal2-popup.custom-action-popup {
        width: 330px !important;
        padding: 16px !important;
    }

    .swal-action-confirm,
    .swal-action-cancel {
        min-width: 105px !important;
        height: 38px !important;
        font-size: 12px !important;
    }
}
CSS);

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

        if (typeof Swal === 'undefined') {
            if (confirm(message)) {
                okCallback();
            } else if (cancelCallback) {
                cancelCallback();
            }

            return;
        }

        var isAcceptAction = message.toLowerCase().indexOf('accept') !== -1;
        var isDenyAction = message.toLowerCase().indexOf('deny') !== -1;

        var popupTitle = isAcceptAction
            ? 'Accept this reply?'
            : 'Deny this application?';

        var popupIcon = isAcceptAction ? 'question' : 'warning';

        var subtitle = isAcceptAction
            ? 'You are about to accept this MRO reply.'
            : 'This action will reject the MRO reply.';

        var confirmText = isAcceptAction
            ? '<i class="bi bi-check-circle-fill"></i> Accept'
            : '<i class="bi bi-x-circle-fill"></i> Deny';

        var confirmClass = isAcceptAction
            ? 'swal-action-confirm accept-confirm'
            : 'swal-action-confirm deny-confirm';

        Swal.fire({
            width: 380,
            title: popupTitle,
            html:
                '<div style="text-align:center;">' +
                    '<div style="font-weight:700;color:#0F172A;margin-bottom:4px;font-size:13px;">' + subtitle + '</div>' +
                    '<div style="font-size:13px;">' + message + '</div>' +
                '</div>',
            icon: popupIcon,
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
            allowEscapeKey: true,
            confirmButtonText: confirmText,
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review application',
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
JS, \yii\web\View::POS_READY);
?>

<main class="dash-content my-replies-page">
    <div class="container-fluid">
        <div class="my-replies-card">

            <div class="my-replies-header">
                <div class="my-replies-title-row">
                    <div class="my-replies-icon">
                        <i class="bi bi-reply-all"></i>
                    </div>

                    <div>
                        <h1 class="my-replies-title">
                            <?= Html::encode($this->title) ?>
                        </h1>
                        <div class="my-replies-subtitle">
                            Review MRO replies, compare quotes, download attachments and manage actions.
                        </div>
                    </div>
                </div>
            </div>

            <div class="my-replies-body">

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

                <div class="table-wrapper">

                    <table class="table replies-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>MRO</th>
                                <th style="width: 30%;">Description</th>
                                <th>Price</th>
                                <th>Currency</th>
                                <th>Attachment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (!empty($applications)): ?>
                            <?php foreach ($applications as $application): ?>
                                <?php
                                $currency = Currency::findOne(['code' => $application->currency]);
                                $currencyName = $currency !== null ? $currency->name : 'Unknown Currency';
                                $mroUsername = $application->mro->username ?? 'MRO Deleted';
                                ?>

                                <tr>
                                    <td>
                                        <span class="request-id-pill">
                                            <i class="bi bi-hash"></i>
                                            <?= Html::encode($request->request_id ?? '-') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="mro-name">
                                            <i class="bi bi-tools"></i>
                                            <?= Html::encode($mroUsername) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <!-- APPLICATION DESCRIPTION 2026: full text remains available on hover. -->
                                        <div class="description-box" title="<?= Html::encode($application->Description ?: 'No description provided.') ?>">
                                            <?= Html::encode($application->Description ?: 'No description provided.') ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="price-pill">
                                            <i class="bi bi-cash-stack"></i>
                                            <?= Html::encode($application->price ?: '0') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="currency-pill">
                                            <i class="bi bi-currency-exchange"></i>
                                            <?= Html::encode($currencyName) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (!empty($application->attachment)): ?>
                                            <?= Html::a(
                                                '<i class="bi bi-download"></i> Download',
                                                Yii::getAlias('@web/') . $application->attachment,
                                                [
                                                    'class' => 'attachment-link',
                                                    'target' => '_blank'
                                                ]
                                            ) ?>
                                        <?php else: ?>
                                            <span class="attachment-empty">
                                                <i class="bi bi-file-earmark-x"></i>
                                                No Attachment
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="actions-wrapper">
                                            <?php
                                            $encodedApplicationId = UrlIdHelper::encode($application->id);
                                            ?>
                                            <?= Html::a(
                                                '<i class="bi bi-eye"></i>',
                                                ['view-application', 'id' => $encodedApplicationId],
                                                [
                                                    'class' => 'action-btn btn-view',
                                                    'title' => 'View application details',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'data-toggle' => 'tooltip',
                                                    'aria-label' => 'View application details',
                                                ]
                                            ) ?>

                                            <?= Html::a(
                                                '<i class="bi bi-check-circle"></i>',
                                                ['load-po', 'id' => $encodedApplicationId],
                                                [
                                                    'class' => 'action-btn btn-accept',
                                                    'title' => 'Accept',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'data-toggle' => 'tooltip',
                                                    'data' => [
                                                        'confirm' => 'Are you sure you want to accept this MRO reply?',
                                                    ],
                                                ]
                                            ) ?>

                                            <?= Html::a(
                                                '<i class="bi bi-chat-dots"></i>',
                                                ['contact', 'id' => $encodedApplicationId],
                                                [
                                                    'class' => 'action-btn btn-contact',
                                                    'title' => 'Contact',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'data-toggle' => 'tooltip'
                                                ]
                                            ) ?>

                                            <?= Html::a(
                                                '<i class="bi bi-x-circle"></i>',
                                                ['deny', 'id' => $encodedApplicationId],
                                                [
                                                    'class' => 'action-btn btn-deny',
                                                    'title' => 'Deny',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'data-toggle' => 'tooltip',
                                                    'data' => [
                                                        'confirm' => 'Are you sure you want to deny this application?',
                                                        'method' => 'post',
                                                    ],
                                                ]
                                            ) ?>

                                            <?php
                                            $encodedMroId = UrlIdHelper::encode($application->mro_id);
                                            ?>
                                            <?= Html::a(
                                                '<i class="bi bi-person-badge"></i>',
                                                [
                                                    'mro-profile/view',
                                                    'id' => $encodedMroId,
                                                    'fromApplication' => $application->id
                                                ],
                                                [
                                                    'class' => 'action-btn btn-profile',
                                                    'title' => 'View MRO Profile',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'data-toggle' => 'tooltip'
                                                ]
                                            ) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <div class="empty-state-title">No replies found</div>
                                        <div>No MRO replies are available for this request yet.</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="mobile-replies">
                        <?php if (!empty($applications)): ?>
                            <?php foreach ($applications as $application): ?>
                                <?php
                                $currency = Currency::findOne(['code' => $application->currency]);
                                $currencyName = $currency !== null ? $currency->name : 'Unknown Currency';
                                $mroUsername = $application->mro->username ?? 'MRO Deleted';
                                ?>

                                <div class="reply-mobile-card">
                                    <div class="reply-mobile-row">
                                        <div class="reply-mobile-label">Request ID</div>
                                        <div class="reply-mobile-value">
                                            #<?= Html::encode($request->request_id ?? '-') ?>
                                        </div>
                                    </div>

                                    <div class="reply-mobile-row">
                                        <div class="reply-mobile-label">MRO</div>
                                        <div class="reply-mobile-value">
                                            <?= Html::encode($mroUsername) ?>
                                        </div>
                                    </div>

                                    <div class="reply-mobile-row">
                                        <div class="reply-mobile-label">Description</div>
                                        <div class="reply-mobile-value description-single-line" title="<?= Html::encode($application->Description ?: 'No description provided.') ?>">
                                            <?= Html::encode($application->Description ?: 'No description provided.') ?>
                                        </div>
                                    </div>

                                    <div class="reply-mobile-row">
                                        <div class="reply-mobile-label">Price</div>
                                        <div class="reply-mobile-value">
                                            <?= Html::encode($application->price ?: '0') ?>
                                        </div>
                                    </div>

                                    <div class="reply-mobile-row">
                                        <div class="reply-mobile-label">Currency</div>
                                        <div class="reply-mobile-value">
                                            <?= Html::encode($currencyName) ?>
                                        </div>
                                    </div>

                                    <div class="reply-mobile-row">
                                        <div class="reply-mobile-label">Attachment</div>
                                        <div class="reply-mobile-value">
                                            <?php if (!empty($application->attachment)): ?>
                                                <?= Html::a(
                                                    'Download',
                                                    Yii::getAlias('@web/') . $application->attachment,
                                                    [
                                                        'target' => '_blank'
                                                    ]
                                                ) ?>
                                            <?php else: ?>
                                                No Attachment
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="reply-mobile-row">
                                        <div class="reply-mobile-label">Actions</div>
                                        <div class="reply-mobile-actions">
                                            <?php
                                            $encodedApplicationId = UrlIdHelper::encode($application->id);
                                            $encodedMroId = UrlIdHelper::encode($application->mro_id);
                                            ?>
                                            <?= Html::a(
                                                '<i class="bi bi-eye"></i>',
                                                ['view-application', 'id' => $encodedApplicationId],
                                                [
                                                    'class' => 'action-btn btn-view',
                                                    'title' => 'View application details',
                                                    'aria-label' => 'View application details',
                                                ]
                                            ) ?>

                                            <?= Html::a(
                                                '<i class="bi bi-check-circle"></i>',
                                                ['load-po', 'id' => $encodedApplicationId],
                                                [
                                                    'class' => 'action-btn btn-accept',
                                                    'title' => 'Accept',
                                                    'data' => [
                                                        'confirm' => 'Are you sure you want to accept this MRO reply?',
                                                    ],
                                                ]
                                            ) ?>

                                            <?= Html::a(
                                                '<i class="bi bi-chat-dots"></i>',
                                                ['contact', 'id' => $encodedApplicationId],
                                                [
                                                    'class' => 'action-btn btn-contact',
                                                    'title' => 'Contact'
                                                ]
                                            ) ?>

                                            <?= Html::a(
                                                '<i class="bi bi-x-circle"></i>',
                                                ['deny', 'id' => $encodedApplicationId],
                                                [
                                                    'class' => 'action-btn btn-deny',
                                                    'title' => 'Deny',
                                                    'data' => [
                                                        'confirm' => 'Are you sure you want to deny this application?',
                                                        'method' => 'post',
                                                    ],
                                                ]
                                            ) ?>

                                            <?= Html::a(
                                                '<i class="bi bi-person-badge"></i>',
                                                [
                                                    'mro-profile/view',
                                                    'id' => $encodedMroId,
                                                    'fromApplication' => $application->id
                                                ],
                                                [
                                                    'class' => 'action-btn btn-profile',
                                                    'title' => 'View MRO Profile'
                                                ]
                                            ) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <div class="empty-state-title">No replies found</div>
                                <div>No MRO replies are available for this request yet.</div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>

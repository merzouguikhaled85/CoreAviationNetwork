<?php

/** @var yii\web\View $this */
/** @var app\models\MroRequestApply $application */
/** @var app\models\Requests $request */

use app\components\UrlIdHelper;
use app\models\Currency;
use yii\helpers\Html;

$this->title = 'MRO Application #' . $application->id;
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'position' => \yii\web\View::POS_HEAD,
]);

/*
 * DISPLAY CONTEXT ONLY:
 * Prepare related data for the application detail page. No workflow value is
 * updated here; all actions continue to use the existing controller endpoints.
 */
$mro = $application->mro;
$aircraft = $request->getAircraft()->one();
$currency = Currency::findOne(['code' => $application->currency]);
$encodedApplicationId = UrlIdHelper::encode($application->id);
$encodedRequestId = UrlIdHelper::encode($request->request_id);
$encodedMroId = UrlIdHelper::encode($application->mro_id);

$currencyLabel = $currency
    ? $currency->name . ' ' . $currency->symbol . ' (' . $currency->code . ')'
    : ($application->currency ?: 'N/A');
$aircraftLabel = $aircraft
    ? trim(($aircraft->manufacturer ?: '') . ' ' . ($aircraft->model ?: ''))
    : 'Aircraft deleted';
$etaLabel = $request->eta ? date('d M Y H:i', strtotime($request->eta)) : 'N/A';
$etdLabel = $request->etd ? date('d M Y H:i', strtotime($request->etd)) : 'N/A';

/* Resolve old and current attachment storage formats safely. */
$attachment = trim((string) ($application->attachment ?? ''));
$attachmentUrl = null;

if ($attachment !== '') {
    if (preg_match('/^https?:\/\//i', $attachment)) {
        $attachmentUrl = $attachment;
    } elseif (strpos($attachment, '/') !== false) {
        $attachmentUrl = Yii::getAlias('@web/') . ltrim($attachment, '/');
    } else {
        $attachmentUrl = Yii::getAlias('@web/uploads/') . $attachment;
    }
}

$this->registerCss(<<<CSS
.application-view-page {
    min-height: 100vh;
    padding: 24px;
    background: #F4F7FC;
}

.application-shell {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}

.application-header,
.application-card {
    border: 1px solid #D8E4EE;
    border-radius: 8px;
    background: #FFFFFF;
    box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
}

.application-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 21px 24px;
    margin-bottom: 18px;
    background: linear-gradient(135deg, #FFFFFF, #EEF5FF);
}

.application-title-group {
    display: flex;
    align-items: center;
    gap: 13px;
}

.application-title-icon {
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    color: #0D3261;
    background: #E0F2FE;
    font-size: 22px;
}

.application-header h1 {
    margin: 0;
    color: #0F172A;
    font-size: 25px;
    font-weight: 900;
}

.application-subtitle {
    margin-top: 4px;
    color: #64748B;
    font-size: 13px;
}

.application-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}

.application-action {
    min-height: 40px;
    padding: 8px 13px;
    border: 1px solid transparent;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    color: #FFFFFF !important;
    font-size: 12px;
    font-weight: 900;
    text-decoration: none;
    box-shadow: 0 6px 15px rgba(15, 23, 42, .13);
    transition: .2s ease;
}

.application-action:hover {
    color: #FFFFFF !important;
    transform: translateY(-1px);
    box-shadow: 0 9px 20px rgba(15, 23, 42, .2);
}

.action-accept { background: #16A34A; }
.action-contact { background: #0284C7; }
.action-deny { background: #DC2626; }
.action-profile { background: #6366F1; }
.action-back {
    color: #334155 !important;
    background: #FFFFFF;
    border-color: #CBD5E1;
}
.action-back:hover { color: #FFFFFF !important; background: #334155; }

.application-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(310px, 1fr);
    gap: 16px;
}

.application-card {
    padding: 20px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 14px;
    color: #0F172A;
    font-size: 16px;
    font-weight: 900;
}

.section-title i { color: #2563EB; }

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.detail-box {
    min-height: 78px;
    padding: 13px 14px;
    border: 1px solid #DDE7F1;
    border-radius: 8px;
    background: #F8FAFC;
    min-width: 0;
}

.detail-box.full-width { grid-column: 1 / -1; }

.detail-label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    color: #708096;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.detail-value {
    color: #0F172A;
    font-size: 13px;
    font-weight: 800;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.price-value {
    color: #166534;
    font-size: 20px;
}

.readonly-description {
    display: block;
    width: 100%;
    min-height: 150px;
    resize: vertical;
    padding: 13px;
    border: 1px dashed #BFD0E4;
    border-radius: 8px;
    color: #334155;
    background: #FFFFFF;
    font: inherit;
    font-size: 13px;
    line-height: 1.55;
}

.attachment-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.attachment-name {
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
    color: #475569;
    font-size: 12px;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.attachment-name i {
    color: #2563EB;
    font-size: 21px;
}

.attachment-link {
    flex: 0 0 auto;
    padding: 8px 12px;
    border-radius: 8px;
    color: #FFFFFF !important;
    background: #2563EB;
    font-size: 12px;
    font-weight: 900;
    text-decoration: none;
}

.attachment-link:hover { background: #1D4ED8; }

.side-stack {
    display: grid;
    gap: 16px;
    align-content: start;
}

.profile-heading {
    display: flex;
    align-items: center;
    gap: 11px;
    margin-bottom: 14px;
}

.profile-avatar {
    width: 42px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    color: #4338CA;
    background: #E0E7FF;
    font-size: 19px;
}

.profile-name {
    color: #0F172A;
    font-size: 14px;
    font-weight: 900;
}

.profile-company {
    color: #64748B;
    font-size: 12px;
}

/* SweetAlert styles intentionally match the check-applications list. */
.swal2-popup.custom-action-popup {
    width: 380px !important;
    max-width: 92vw !important;
    border-radius: 8px !important;
    padding: 18px 20px !important;
    box-shadow: 0 18px 45px rgba(15, 23, 42, .24) !important;
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
    margin: 0 0 8px !important;
    padding: 0 !important;
    color: #0F172A !important;
    font-size: 20px !important;
    font-weight: 800 !important;
}

.swal2-html-container.custom-action-message {
    margin: 0 8px 14px !important;
    color: #64748B !important;
    font-size: 13px !important;
    line-height: 1.45 !important;
}

.swal2-actions {
    margin-top: 10px !important;
    gap: 8px !important;
}

.swal-action-confirm,
.swal-action-cancel {
    min-width: 115px !important;
    height: 39px !important;
    padding: 9px 14px !important;
    border: none !important;
    border-radius: 8px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 7px !important;
    font-size: 13px !important;
    font-weight: 800 !important;
    transition: .2s ease !important;
}

.swal-action-confirm.accept-confirm {
    color: #FFFFFF !important;
    background: #22C55E !important;
    box-shadow: 0 8px 18px rgba(34, 197, 94, .25) !important;
}

.swal-action-confirm.accept-confirm:hover {
    background: #16A34A !important;
    transform: translateY(-1px);
}

.swal-action-confirm.deny-confirm {
    color: #FFFFFF !important;
    background: #EF4444 !important;
    box-shadow: 0 8px 18px rgba(239, 68, 68, .25) !important;
}

.swal-action-confirm.deny-confirm:hover {
    background: #DC2626 !important;
    transform: translateY(-1px);
}

.swal-action-cancel {
    color: #334155 !important;
    background: #E2E8F0 !important;
}

.swal-action-cancel:hover {
    background: #CBD5E1 !important;
    transform: translateY(-1px);
}

@media (max-width: 1100px) {
    .application-header { align-items: flex-start; flex-direction: column; }
    .application-actions { justify-content: flex-start; }
    .application-grid { grid-template-columns: 1fr; }
}

@media (max-width: 767.98px) {
    .application-view-page { padding: 13px; }
    .application-header,
    .application-card { padding: 15px; }
    .application-header h1 { font-size: 21px; }
    .application-actions { width: 100%; }
    .application-action { flex: 1 1 calc(50% - 8px); }
    .detail-grid { grid-template-columns: 1fr; }
    .detail-box.full-width { grid-column: auto; }
    .attachment-card { align-items: stretch; flex-direction: column; }
    .attachment-link { text-align: center; }
    .swal-action-confirm,
    .swal-action-cancel { min-width: 105px !important; height: 38px !important; font-size: 12px !important; }
}
CSS);

/* Reuse the same Yii confirmation bridge as the check-applications list. */
$this->registerJs(<<<JS
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
        var popupTitle = isAcceptAction ? 'Accept this reply?' : 'Deny this application?';
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
            // SHARED DETAIL CONFIRMATION: reuse the common compact dialog presentation.
            customClass: {
                popup: 'custom-action-popup can-detail-swal',
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

<!-- SHARED DETAIL SYSTEM: presentation only; application actions remain unchanged. -->
<main class="application-view-page can-detail-page">
    <div class="application-shell">
        <header class="application-header">
            <div class="application-title-group">
                <span class="application-title-icon"><i class="bi bi-file-earmark-text"></i></span>
                <div>
                    <h1><?= Html::encode($this->title) ?></h1>
                    <div class="application-subtitle">Review the MRO technical and commercial quotation before making a decision.</div>
                </div>
            </div>

            <!-- Same business actions as the selected row in check-applications. -->
            <div class="application-actions">
                <?= Html::a(
                    '<i class="bi bi-check-circle"></i> Accept',
                    ['load-po', 'id' => $encodedApplicationId],
                    [
                        'class' => 'application-action action-accept',
                        'data' => ['confirm' => 'Are you sure you want to accept this MRO reply?'],
                    ]
                ) ?>
                <?= Html::a(
                    '<i class="bi bi-chat-dots"></i> Contact',
                    ['contact', 'id' => $encodedApplicationId],
                    ['class' => 'application-action action-contact']
                ) ?>
                <?= Html::a(
                    '<i class="bi bi-x-circle"></i> Deny',
                    ['deny', 'id' => $encodedApplicationId],
                    [
                        'class' => 'application-action action-deny',
                        'data' => [
                            'confirm' => 'Are you sure you want to deny this application?',
                            'method' => 'post',
                        ],
                    ]
                ) ?>
                <?= Html::a(
                    '<i class="bi bi-person-badge"></i> MRO Profile',
                    ['mro-profile/view', 'id' => $encodedMroId, 'fromApplication' => $application->id],
                    ['class' => 'application-action action-profile']
                ) ?>
                <?= Html::a(
                    '<i class="bi bi-arrow-left"></i> Back',
                    ['check-applications', 'id' => $encodedRequestId],
                    ['class' => 'application-action action-back']
                ) ?>
            </div>
        </header>

        <div class="application-grid">
            <section class="application-card">
                <h2 class="section-title"><i class="bi bi-receipt"></i> MRO Quotation</h2>

                <div class="detail-grid">
                    <div class="detail-box">
                        <div class="detail-label"><i class="bi bi-hash"></i> Application ID</div>
                        <div class="detail-value">#<?= Html::encode($application->id) ?></div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label"><i class="bi bi-clipboard-check"></i> Request ID</div>
                        <div class="detail-value">#<?= Html::encode($request->request_id) ?></div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label"><i class="bi bi-cash-stack"></i> Quoted Price</div>
                        <div class="detail-value price-value"><?= Html::encode(number_format((float) $application->price, 2, '.', ',')) ?></div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label"><i class="bi bi-currency-exchange"></i> Currency</div>
                        <div class="detail-value"><?= Html::encode($currencyLabel) ?></div>
                    </div>

                    <div class="detail-box full-width">
                        <div class="detail-label"><i class="bi bi-chat-left-text"></i> Technical &amp; Commercial Proposal</div>
                        <textarea class="readonly-description" readonly><?= Html::encode($application->Description ?: 'No description provided.') ?></textarea>
                    </div>

                    <div class="detail-box full-width">
                        <div class="detail-label"><i class="bi bi-paperclip"></i> Official Quotation</div>
                        <?php if ($attachmentUrl !== null): ?>
                            <div class="attachment-card">
                                <div class="attachment-name">
                                    <i class="bi bi-file-earmark-check"></i>
                                    <span><?= Html::encode(basename($attachment)) ?></span>
                                </div>
                                <?= Html::a(
                                    '<i class="bi bi-eye"></i> View / Download',
                                    $attachmentUrl,
                                    ['class' => 'attachment-link', 'target' => '_blank', 'rel' => 'noopener']
                                ) ?>
                            </div>
                        <?php else: ?>
                            <div class="detail-value">No quotation document attached.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <aside class="side-stack">
                <section class="application-card">
                    <h2 class="section-title"><i class="bi bi-building-gear"></i> MRO Information</h2>
                    <div class="profile-heading">
                        <span class="profile-avatar"><i class="bi bi-tools"></i></span>
                        <div>
                            <div class="profile-name"><?= Html::encode($mro->username ?? 'MRO deleted') ?></div>
                            <div class="profile-company"><?= Html::encode($mro->company_name ?? 'N/A') ?></div>
                        </div>
                    </div>
                    <div class="detail-grid">
                        <div class="detail-box">
                            <div class="detail-label">MRO ID</div>
                            <div class="detail-value">#<?= Html::encode($application->mro_id) ?></div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?= Html::encode($mro->email ?? 'N/A') ?></div>
                        </div>
                    </div>
                </section>

                <section class="application-card">
                    <h2 class="section-title"><i class="bi bi-airplane"></i> Request Summary</h2>
                    <div class="detail-grid">
                        <div class="detail-box full-width">
                            <div class="detail-label">Aircraft</div>
                            <div class="detail-value"><?= Html::encode($aircraftLabel ?: 'N/A') ?></div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label">Registration</div>
                            <div class="detail-value"><?= Html::encode($request->aircraft_registration ?: 'N/A') ?></div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label">Serial Number</div>
                            <div class="detail-value"><?= Html::encode($request->serial_number ?: 'N/A') ?></div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label">ETA</div>
                            <div class="detail-value"><?= Html::encode($etaLabel) ?></div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label">ETD</div>
                            <div class="detail-value"><?= Html::encode($etdLabel) ?></div>
                        </div>
                        <div class="detail-box full-width">
                            <div class="detail-label">Maintenance Location</div>
                            <div class="detail-value"><?= Html::encode($request->location ?: 'N/A') ?></div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</main>

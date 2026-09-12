<?php

/** @var yii\web\View $this */
/** @var app\models\Dispute $dispute */
/** @var string|null $headerActions */
/** @var bool|null $showRequestDetails */

use yii\helpers\Html;

$this->title = 'Dispute #' . $dispute->dispute_id;
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

$ao = $dispute->ao;
$mro = $dispute->mro;
$status = strtolower((string) $dispute->status);
$statusLabel = $status !== '' ? ucfirst($status) : 'N/A';
$createdBy = strtolower((string) $dispute->created_by) === 'ao' ? 'Aircraft Operator (AO)' : 'MRO';
$poUrl = !empty($dispute->po)
    ? Yii::getAlias('@web/uploads/') . ltrim($dispute->po, '/')
    : null;
$showRequestDetails = $showRequestDetails ?? false;
$request = $showRequestDetails ? $dispute->request : null;
$requestAircraft = $request ? $request->aircraft : null;
$requestAirport = $request ? $request->destinationAirport : null;

$buildFileUrl = static function ($file) {
    if (empty($file)) {
        return null;
    }

    if (preg_match('/^https?:\/\//i', (string) $file)) {
        return $file;
    }

    if (strpos((string) $file, '@web/') === 0) {
        return Yii::getAlias('@web') . '/' . ltrim(substr((string) $file, 5), '/');
    }

    return Yii::getAlias('@web/') . ltrim((string) $file, '/');
};

$requestAttachmentUrl = $request ? $buildFileUrl($request->attachment ?? null) : null;

$profileName = static function ($profile): string {
    if ($profile === null) {
        return 'N/A';
    }

    $company = trim((string) ($profile->company_name ?? ''));
    $username = trim((string) ($profile->username ?? ''));
    return $company !== '' ? $company : ($username !== '' ? $username : 'N/A');
};

$this->registerCss(<<<CSS
.dispute-view-page {
    padding: 24px;
    min-height: 100vh;
    background: #f5f7fb;
}

.dispute-shell {
    max-width: 1450px;
    margin: 0 auto;
}

.dispute-header,
.dispute-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .07);
}

.dispute-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 22px 26px;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #fff, #eef4ff);
}

.dispute-heading {
    display: flex;
    align-items: center;
    gap: 14px;
}

.dispute-heading-icon {
    width: 50px;
    height: 50px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 50px;
    border-radius: 14px;
    color: #0369a1;
    background: #e0f2fe;
    font-size: 23px;
}

.dispute-header h1 {
    margin: 0;
    color: #172033;
    font-size: 26px;
    font-weight: 800;
}

.dispute-subtitle {
    margin-top: 4px;
    color: #64748b;
    font-size: 13px;
}

.back-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 42px;
    padding: 9px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    color: #334155;
    background: #fff;
    font-size: 13px;
    font-weight: 800;
    text-decoration: none;
    transition: .2s ease;
}

.back-button:hover {
    color: #fff;
    background: #334155;
    border-color: #334155;
    transform: translateY(-1px);
}

.dispute-header-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}

.detail-action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 42px;
    padding: 9px 14px;
    border: 1px solid transparent;
    border-radius: 10px;
    color: #fff !important;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
    box-shadow: 0 5px 14px rgba(15, 23, 42, .14);
    transition: .2s ease;
}

.detail-action-button:hover {
    color: #fff !important;
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(15, 23, 42, .2);
}

.detail-action-reply { background: #0ea5e9; }
.detail-action-reply:hover { background: #0284c7; }
.detail-action-delete { background: #ef4444; }
.detail-action-delete:hover { background: #dc2626; }
.detail-action-ban { background: #f97316; }
.detail-action-ban:hover { background: #ea580c; }
.detail-action-unban { background: #22c55e; }
.detail-action-unban:hover { background: #16a34a; }

.dispute-card {
    padding: 22px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 14px;
    color: #172033;
    font-size: 15px;
    font-weight: 800;
}

.section-title i {
    color: #2563eb;
}

.summary-grid,
.party-grid {
    display: grid;
    gap: 12px;
}

.request-details-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.request-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.request-date-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-column: 1 / -1;
    gap: 12px;
}

.request-wide {
    grid-column: 1 / -1;
}

.request-attachment {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 38px;
    padding: 8px 13px;
    border-radius: 9px;
    color: #fff;
    background: #2563eb;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
}

.request-attachment:hover {
    color: #fff;
    background: #1d4ed8;
}

.request-missing {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px;
    border: 1px solid #fed7aa;
    border-radius: 12px;
    color: #9a3412;
    background: #fff7ed;
    font-size: 13px;
    font-weight: 700;
}

.summary-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.party-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin-top: 18px;
}

.info-box,
.party-card,
.text-panel,
.po-panel {
    border: 1px solid #dbe4f0;
    border-radius: 13px;
    background: #f8fafc;
}

.info-box {
    min-height: 82px;
    padding: 13px 14px;
}

.info-label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 9px;
    color: #64748b;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .07em;
    text-transform: uppercase;
}

.info-value {
    color: #172033;
    font-size: 14px;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 12px;
    border-radius: 999px;
    color: #075985;
    background: #e0f2fe;
    font-size: 12px;
    font-weight: 800;
}

.status-pill.resolved {
    color: #166534;
    background: #dcfce7;
}

.status-pill i {
    font-size: 8px;
}

.party-card {
    padding: 16px;
}

.party-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.party-icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    color: #0369a1;
    background: #e0f2fe;
}

.party-title {
    color: #172033;
    font-weight: 800;
}

.party-id {
    color: #64748b;
    font-size: 11px;
}

.party-meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.party-meta div {
    padding-top: 9px;
    border-top: 1px solid #e2e8f0;
    color: #475569;
    font-size: 12px;
    overflow-wrap: anywhere;
}

.party-meta strong {
    display: block;
    margin-bottom: 3px;
    color: #94a3b8;
    font-size: 9px;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.details-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
    gap: 14px;
    margin-top: 18px;
}

.text-panel,
.po-panel {
    padding: 15px;
}

.readonly-text {
    display: block;
    width: 100%;
    min-height: 132px;
    resize: vertical;
    padding: 13px;
    border: 1px dashed #cbd5e1;
    border-radius: 10px;
    color: #334155;
    background: #fff;
    font: inherit;
    font-size: 13px;
    line-height: 1.55;
}

.readonly-text.response {
    min-height: 100px;
}

.po-panel {
    display: flex;
    flex-direction: column;
}

.po-state {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px;
    border: 1px dashed #cbd5e1;
    border-radius: 10px;
    color: #475569;
    background: #fff;
    font-size: 12px;
    overflow-wrap: anywhere;
}

.po-state i {
    color: #2563eb;
    font-size: 20px;
}

.po-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    margin-top: 12px;
    min-height: 40px;
    border-radius: 9px;
    color: #fff;
    background: #2563eb;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
}

.po-button:hover {
    color: #fff;
    background: #1d4ed8;
}

.admin-panel {
    margin-top: 14px;
}

@media (max-width: 991.98px) {
    .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .details-grid { grid-template-columns: 1fr; }
}

@media (max-width: 767.98px) {
    .dispute-view-page { padding: 14px; }
    .dispute-header { padding: 18px; align-items: stretch; }
    .dispute-heading { align-items: flex-start; }
    .back-button { width: 100%; }
    .dispute-header-actions { width: 100%; justify-content: stretch; }
    .dispute-header-actions .detail-action-button { flex: 1 1 calc(50% - 8px); }
    .summary-grid,
    .party-grid,
    .party-meta,
    .request-grid,
    .request-date-grid { grid-template-columns: 1fr; }
    .request-date-grid { grid-column: auto; }
    .dispute-card { padding: 15px; }
    .dispute-header h1 { font-size: 22px; }
}
CSS);
?>

<!-- SHARED DETAIL SYSTEM: presentation only; dispute permissions and actions remain local. -->
<main class="dispute-view-page can-detail-page">
    <div class="dispute-shell">
        <header class="dispute-header">
            <div class="dispute-heading">
                <span class="dispute-heading-icon"><i class="bi bi-flag"></i></span>
                <div>
                    <h1><?= Html::encode($this->title) ?></h1>
                    <div class="dispute-subtitle">Complete dispute information and related documents.</div>
                </div>
            </div>

            <div class="dispute-header-actions">
                <?= $headerActions ?? '' ?>
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Disputes',
                    ['index'],
                    ['class' => 'back-button']
                ) ?>
            </div>
        </header>

        <section class="dispute-card">
            <h2 class="section-title"><i class="bi bi-info-circle"></i> Dispute Summary</h2>

            <div class="summary-grid">
                <div class="info-box">
                    <div class="info-label"><i class="bi bi-hash"></i> Dispute ID</div>
                    <div class="info-value">#<?= Html::encode($dispute->dispute_id) ?></div>
                </div>
                <div class="info-box">
                    <div class="info-label"><i class="bi bi-clipboard-check"></i> Request ID</div>
                    <div class="info-value">#<?= Html::encode($dispute->request_id ?: 'N/A') ?></div>
                </div>
                <div class="info-box">
                    <div class="info-label"><i class="bi bi-activity"></i> Status</div>
                    <span class="status-pill <?= $status === 'resolved' ? 'resolved' : '' ?>">
                        <i class="bi bi-circle-fill"></i><?= Html::encode($statusLabel) ?>
                    </span>
                </div>
                <div class="info-box">
                    <div class="info-label"><i class="bi bi-calendar-event"></i> Date Raised</div>
                    <div class="info-value">
                        <?= !empty($dispute->timestamp)
                            ? Html::encode(date('d M Y H:i', strtotime($dispute->timestamp)))
                            : 'N/A' ?>
                    </div>
                </div>
            </div>

            <?php if ($showRequestDetails): ?>
                <section class="request-details-section">
                    <h2 class="section-title"><i class="bi bi-clipboard-check"></i> Request Details</h2>

                    <?php if ($request !== null): ?>
                        <div class="request-grid">
                            <div class="info-box">
                                <div class="info-label"><i class="bi bi-activity"></i> Request Status</div>
                                <div class="info-value">
                                    <?= Html::encode(ucwords(str_replace('_', ' ', (string) ($request->status ?: 'N/A')))) ?>
                                </div>
                            </div>

                            <div class="info-box">
                                <div class="info-label"><i class="bi bi-airplane"></i> Aircraft</div>
                                <div class="info-value">
                                    <?= Html::encode($requestAircraft
                                        ? trim(($requestAircraft->manufacturer ?: '') . ' ' . ($requestAircraft->model ?: ''))
                                        : 'Aircraft deleted') ?>
                                </div>
                            </div>

                            <div class="info-box">
                                <div class="info-label"><i class="bi bi-card-text"></i> Aircraft Registration</div>
                                <div class="info-value"><?= Html::encode($request->aircraft_registration ?: 'N/A') ?></div>
                            </div>

                            <div class="info-box">
                                <div class="info-label"><i class="bi bi-upc-scan"></i> Aircraft Serial Number</div>
                                <div class="info-value"><?= Html::encode($request->serial_number ?: 'N/A') ?></div>
                            </div>

                            <div class="request-date-grid">
                                <div class="info-box">
                                    <div class="info-label"><i class="bi bi-calendar-event"></i> ETA</div>
                                    <div class="info-value">
                                        <?= !empty($request->eta)
                                            ? Html::encode(date('d M Y H:i', strtotime($request->eta)))
                                            : 'N/A' ?>
                                    </div>
                                </div>

                                <div class="info-box">
                                    <div class="info-label"><i class="bi bi-calendar-check"></i> ETD</div>
                                    <div class="info-value">
                                        <?= !empty($request->etd)
                                            ? Html::encode(date('d M Y H:i', strtotime($request->etd)))
                                            : 'N/A' ?>
                                    </div>
                                </div>
                            </div>

                            <div class="info-box">
                                <div class="info-label"><i class="bi bi-geo-alt"></i> Maintenance Location</div>
                                <div class="info-value"><?= Html::encode($request->location ?: 'N/A') ?></div>
                            </div>

                            <div class="info-box">
                                <div class="info-label"><i class="bi bi-signpost-2"></i> Maintenance Airport</div>
                                <div class="info-value">
                                    <?= Html::encode($requestAirport
                                        ? trim(($requestAirport->icao ?: '') . ' - ' . ($requestAirport->airport_name ?: ''), ' -')
                                        : 'N/A') ?>
                                </div>
                            </div>

                            <div class="text-panel request-wide">
                                <h3 class="section-title"><i class="bi bi-info-circle"></i> Request Information</h3>
                                <textarea class="readonly-text response" readonly><?= Html::encode($request->request_details ?: 'N/A') ?></textarea>
                            </div>

                            <div class="info-box request-wide">
                                <div class="info-label"><i class="bi bi-paperclip"></i> Request Attachment</div>
                                <?php if ($requestAttachmentUrl !== null): ?>
                                    <?= Html::a(
                                        '<i class="bi bi-eye"></i> View / Download Attachment',
                                        $requestAttachmentUrl,
                                        ['class' => 'request-attachment', 'target' => '_blank', 'rel' => 'noopener']
                                    ) ?>
                                <?php else: ?>
                                    <div class="info-value">No request attachment.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="request-missing">
                            <i class="bi bi-exclamation-triangle"></i>
                            The linked request is no longer available.
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <div class="party-grid">
                <article class="party-card">
                    <div class="party-header">
                        <span class="party-icon"><i class="bi bi-building"></i></span>
                        <div>
                            <div class="party-title">Aircraft Operator (AO)</div>
                            <div class="party-id">AO #<?= Html::encode($dispute->ao_id) ?></div>
                        </div>
                    </div>
                    <div class="party-meta">
                        <div><strong>Company / Username</strong><?= Html::encode($profileName($ao)) ?></div>
                        <div><strong>Email</strong><?= Html::encode($ao->email ?? 'N/A') ?></div>
                    </div>
                </article>

                <article class="party-card">
                    <div class="party-header">
                        <span class="party-icon"><i class="bi bi-tools"></i></span>
                        <div>
                            <div class="party-title">Maintenance Organization (MRO)</div>
                            <div class="party-id">MRO #<?= Html::encode($dispute->mro_id) ?></div>
                        </div>
                    </div>
                    <div class="party-meta">
                        <div><strong>Company / Username</strong><?= Html::encode($profileName($mro)) ?></div>
                        <div><strong>Email</strong><?= Html::encode($mro->email ?? 'N/A') ?></div>
                    </div>
                </article>
            </div>

            <div class="details-grid">
                <div class="text-panel">
                    <h2 class="section-title"><i class="bi bi-chat-left-text"></i> Dispute Description</h2>
                    <textarea class="readonly-text" readonly><?= Html::encode($dispute->description ?: 'N/A') ?></textarea>
                </div>

                <aside class="po-panel">
                    <h2 class="section-title"><i class="bi bi-paperclip"></i> Purchase Order</h2>
                    <?php if ($poUrl !== null): ?>
                        <div class="po-state">
                            <i class="bi bi-file-earmark-check"></i>
                            <span><?= Html::encode(basename((string) $dispute->po)) ?></span>
                        </div>
                        <?= Html::a(
                            '<i class="bi bi-eye"></i> View / Download PO',
                            $poUrl,
                            ['class' => 'po-button', 'target' => '_blank', 'rel' => 'noopener']
                        ) ?>
                    <?php else: ?>
                        <div class="po-state">
                            <i class="bi bi-file-earmark-x"></i>
                            <span>No purchase order attached.</span>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>

            <div class="text-panel admin-panel">
                <h2 class="section-title"><i class="bi bi-shield-check"></i> Admin Response</h2>
                <textarea class="readonly-text response" readonly><?= Html::encode($dispute->admin_response ?: 'No admin response yet.') ?></textarea>
            </div>

            <div class="info-box" style="margin-top: 14px; min-height: auto;">
                <div class="info-label"><i class="bi bi-person-check"></i> Raised By</div>
                <div class="info-value"><?= Html::encode($createdBy) ?></div>
            </div>
        </section>
    </div>
</main>

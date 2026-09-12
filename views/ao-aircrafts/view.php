<?php

use yii\helpers\Html;

/**
 * Aircraft view page.
 * This view expects the aircraft model in $aircraft.
 */

$this->title = 'Aircraft #' . $aircraft->aircraft_id;
$this->params['breadcrumbs'][] = ['label' => 'Aircrafts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/**
 * Register Bootstrap Icons.
 * Icons are used in cards, badges, header and action buttons.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    ['position' => \yii\web\View::POS_HEAD]
);

/**
 * Fetch related certificate type safely.
 */
$certificateType = $aircraft->getCertificateType()->one();
$certificateLabel = $certificateType ? $certificateType->type : 'N/A';

/**
 * Fetch current owner label safely.
 * This keeps the same logic from your existing view.
 */
$ownerLabel = Yii::$app->user->identity->username ?? 'N/A';

/**
 * Prepare safe display values.
 */
$manufacturer = $aircraft->manufacturer ?: 'N/A';
$model = $aircraft->model ?: 'N/A';
$serialNumber = $aircraft->serial_number ?: 'N/A';
$registrationNumber = $aircraft->registration_number ?: 'N/A';

/**
 * Register page CSS.
 * This CSS reuses the same visual language as the request view page.
 */
$this->registerCss(<<<CSS
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

    .header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-page-action {
        border-radius: 9px;
        padding: 10px 18px;
        font-weight: 800;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
        text-decoration: none;
        transition: all .2s ease;
    }

    .btn-back {
        background: #ffffff;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }

    .btn-back:hover {
        background: #f1f5f9;
        color: #0f172a !important;
        transform: translateY(-1px);
        text-decoration: none;
    }

    .btn-edit {
        background: #f0a51aff;
        color: #ffffff !important;
        border: 1px solid #d97706;
        box-shadow: 0 8px 18px rgba(217, 119, 6, 0.18);
    }

    .btn-edit:hover {
        background: #d97706;
        color: #ffffff !important;
        transform: translateY(-1px);
        text-decoration: none;
    }

    .view-grid {
        display: grid;
        grid-template-columns: 1.3fr 0.7fr;
        gap: 18px;
    }

    .content-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5eaf3;
        max-width: 100%;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 16px;
        font-size: 17px;
        font-weight: 900;
        color: #0f172a;
    }

    .detail-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .detail-item {
        background: #f8fafc;
        border: 1px solid #e5eaf3;
        border-radius: 14px;
        padding: 14px;
        min-width: 0;
    }

    .detail-label {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 7px;
    }

    .detail-value {
        color: #1f2937;
        font-size: 14px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .aircraft-id-badge,
    .manufacturer-badge,
    .aircraft-badge,
    .registration-badge,
    .serial-badge,
    .owner-badge,
    .certificate-badge,
    .empty-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
        text-decoration: none;
    }

    .aircraft-id-badge {
        background: #eef2ff;
        color: #4338ca;
    }

    .manufacturer-badge {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #e5e7eb;
    }

    .aircraft-badge {
        background: #e0f2fe;
        color: #0369a1;
    }

    .registration-badge {
        background: #ecfdf5;
        color: #047857;
    }

    .serial-badge {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #e5e7eb;
    }

    .owner-badge {
        background: #f1f5f9;
        color: #334155;
    }

    .certificate-badge {
        background: #fff7ed;
        color: #c2410c;
    }

    .empty-badge {
        background: #f8fafc;
        color: #94a3b8;
        border: 1px solid #e5e7eb;
    }

    .quick-actions {
        display: grid;
        gap: 10px;
    }

    .quick-action-btn {
        width: 100%;
        min-height: 44px;
        border-radius: 12px;
        padding: 11px 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 900;
        color: #ffffff !important;
        text-decoration: none;
        border: 1px solid rgba(15, 23, 42, .12);
        box-shadow: 0 8px 18px rgba(15, 23, 42, .12);
        transition: all .2s ease;
    }

    .quick-action-btn:hover {
        color: #ffffff !important;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, .16);
    }

    .qa-update {
        background: #f0a51aff;
    }

    .qa-back {
        background: #64748b;
    }

    .qa-aircraft {
        background: #0ea5e9;
    }

    .summary-box {
        display: grid;
        gap: 10px;
    }

    .summary-item {
        background: #f8fafc;
        border: 1px solid #e5eaf3;
        border-radius: 14px;
        padding: 13px 14px;
    }

    .summary-label {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 6px;
    }

    .summary-value {
        color: #0f172a;
        font-size: 14px;
        font-weight: 900;
        overflow-wrap: anywhere;
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

        .view-grid {
            grid-template-columns: 1fr;
        }

        .detail-list {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 576px) {
        .requests-page {
            padding: 10px;
        }

        .page-header-card,
        .content-card {
            border-radius: 14px;
            padding: 16px;
        }

        .header-actions {
            width: 100%;
        }

        .btn-page-action {
            width: 100%;
            justify-content: center;
        }

        .aircraft-id-badge,
        .manufacturer-badge,
        .aircraft-badge,
        .registration-badge,
        .serial-badge,
        .owner-badge,
        .certificate-badge,
        .empty-badge {
            white-space: normal;
        }
    }
CSS);
?>

<main class="dash-content requests-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title fw-bold">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-airplane-engines"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Detailed view of this aircraft profile
                </div>
            </div>

            <div class="header-actions">
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Aircrafts',
                    ['index'],
                    ['class' => 'btn-page-action btn-back']
                ) ?>

                <?= Html::a(
                    '<i class="bi bi-pencil"></i> Update',
                    ['update', 'id' => $aircraft->aircraft_id],
                    ['class' => 'btn-page-action btn-edit']
                ) ?>
            </div>
        </div>

        <div class="view-grid">

            <!-- Main aircraft details -->
            <div class="content-card">
                <h2 class="section-title">
                    <i class="bi bi-info-circle text-primary"></i>
                    Aircraft Details
                </h2>

                <div class="detail-list">

                    <!-- Aircraft ID -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-hash"></i>
                            Aircraft ID
                        </div>
                        <div class="detail-value">
                            <span class="aircraft-id-badge">
                                #<?= Html::encode($aircraft->aircraft_id) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Manufacturer -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-building"></i>
                            Manufacturer
                        </div>
                        <div class="detail-value">
                            <?php if ($manufacturer !== 'N/A'): ?>
                                <span class="manufacturer-badge">
                                    <i class="bi bi-building-check"></i>
                                    <?= Html::encode($manufacturer) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    N/A
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Aircraft model -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-airplane"></i>
                            Model
                        </div>
                        <div class="detail-value">
                            <?php if ($model !== 'N/A'): ?>
                                <span class="aircraft-badge">
                                    <i class="bi bi-airplane-engines"></i>
                                    <?= Html::encode($model) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    N/A
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Serial number -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-upc-scan"></i>
                            Serial Number
                        </div>
                        <div class="detail-value">
                            <?php if ($serialNumber !== 'N/A'): ?>
                                <span class="serial-badge">
                                    <i class="bi bi-upc"></i>
                                    <?= Html::encode($serialNumber) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    N/A
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Registration number -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-card-heading"></i>
                            Registration Number
                        </div>
                        <div class="detail-value">
                            <?php if ($registrationNumber !== 'N/A'): ?>
                                <span class="registration-badge">
                                    <i class="bi bi-card-text"></i>
                                    <?= Html::encode($registrationNumber) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    N/A
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Owner -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-person-badge"></i>
                            Owner
                        </div>
                        <div class="detail-value">
                            <?php if ($ownerLabel !== 'N/A'): ?>
                                <span class="owner-badge">
                                    <i class="bi bi-person-check"></i>
                                    <?= Html::encode($ownerLabel) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-person-x"></i>
                                    N/A
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- NAA Certificate -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-award"></i>
                            NAA Certificate
                        </div>
                        <div class="detail-value">
                            <?php if ($certificateType): ?>
                                <span class="certificate-badge">
                                    <i class="bi bi-patch-check"></i>
                                    <?= Html::encode($certificateLabel) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-file-earmark-x"></i>
                                    N/A
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Quick actions and aircraft summary -->
            <div class="content-card">
                <h2 class="section-title">
                    <i class="bi bi-lightning-charge text-warning"></i>
                    Quick Actions
                </h2>

                <div class="quick-actions">
                    <?= Html::a(
                        '<i class="bi bi-pencil-square"></i> Update Aircraft',
                        ['update', 'id' => $aircraft->aircraft_id],
                        ['class' => 'quick-action-btn qa-update']
                    ) ?>

                    <?= Html::a(
                        '<i class="bi bi-arrow-left-circle"></i> Back to Aircrafts',
                        ['index'],
                        ['class' => 'quick-action-btn qa-back']
                    ) ?>
                </div>

                <h2 class="section-title" style="margin-top: 22px;">
                    <i class="bi bi-airplane text-primary"></i>
                    Aircraft Summary
                </h2>

                <div class="summary-box">

                    <!-- Summary registration -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-card-text"></i>
                            Registration
                        </div>
                        <div class="summary-value">
                            <?= Html::encode($registrationNumber) ?>
                        </div>
                    </div>

                    <!-- Summary model -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-airplane-engines"></i>
                            Model
                        </div>
                        <div class="summary-value">
                            <?= Html::encode($model) ?>
                        </div>
                    </div>

                    <!-- Summary certificate -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-award"></i>
                            Certificate
                        </div>
                        <div class="summary-value">
                            <?= Html::encode($certificateLabel) ?>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>
</main>
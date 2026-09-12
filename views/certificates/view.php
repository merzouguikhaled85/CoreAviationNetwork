<?php

use yii\helpers\Html;
use yii\web\View;

/** @var yii\web\View $this */
/** @var app\models\Certificates $model */

/**
 * Page title and breadcrumbs.
 */
$this->title = 'View Certificate: ' . $model->certificate_id;
$this->params['breadcrumbs'][] = ['label' => 'Certificates', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/**
 * Register Yii asset.
 */
\yii\web\YiiAsset::register($this);

/**
 * Register Bootstrap Icons.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['position' => View::POS_HEAD]
);

/**
 * Prepare certificate data safely.
 */
$certificateId = $model->certificate_id ?? 'N/A';
$mroName = $model->mro->username ?? 'N/A';
$certificateType = $model->type ?? 'N/A';
$certificateFile = $model->certificate ?? null;

$fileUrl = !empty($certificateFile)
    ? Yii::$app->request->baseUrl . '/' . ltrim((string) $certificateFile, '/')
    : null;

$fileName = !empty($certificateFile)
    ? basename((string) $certificateFile)
    : 'N/A';

/**
 * Prepare back URL.
 */
$backUrl = Yii::$app->request->referrer ?: ['index'];

/**
 * Register page CSS.
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

.detail-item.full-width {
    grid-column: 1 / -1;
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

.certificate-id-badge,
.mro-badge,
.type-badge,
.file-badge,
.document-badge,
.empty-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 12px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
    text-decoration: none;
}

.certificate-id-badge {
    background: #eef2ff;
    color: #4338ca;
}

.mro-badge {
    background: #f1f5f9;
    color: #334155;
}

.type-badge {
    background: #e0f2fe;
    color: #0369a1;
}

.file-badge {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e5e7eb;
}

.document-badge {
    background: #ecfdf5;
    color: #047857 !important;
    border: 1px solid #bbf7d0;
}

.document-badge:hover {
    background: #d1fae5;
    color: #065f46 !important;
    text-decoration: none;
}

.empty-badge {
    background: #f8fafc;
    color: #94a3b8;
    border: 1px solid #e5e7eb;
}

.path-box {
    line-height: 1.65;
    font-weight: 700;
    color: #334155;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 12px;
    overflow-wrap: anywhere;
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

.qa-view {
    background: #0ea5e9;
}

.qa-back {
    background: #64748b;
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

    .btn-page-action,
    .quick-action-btn {
        width: 100%;
        justify-content: center;
    }

    .certificate-id-badge,
    .mro-badge,
    .type-badge,
    .file-badge,
    .document-badge,
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
                        <i class="bi bi-patch-check"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Detailed information about this certificate and its attached document.
                </div>
            </div>

            <div class="header-actions">
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back',
                    $backUrl,
                    ['class' => 'btn-page-action btn-back']
                ) ?>
            </div>
        </div>

        <div class="view-grid">

            <!-- Main certificate details -->
            <div class="content-card">
                <h2 class="section-title">
                    <i class="bi bi-info-circle text-primary"></i>
                    Certificate Details
                </h2>

                <div class="detail-list">

                    <!-- Certificate ID -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-hash"></i>
                            Certificate ID
                        </div>

                        <div class="detail-value">
                            <span class="certificate-id-badge">
                                #<?= Html::encode($certificateId) ?>
                            </span>
                        </div>
                    </div>

                    <!-- MRO name -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-building"></i>
                            MRO Name
                        </div>

                        <div class="detail-value">
                            <span class="mro-badge">
                                <i class="bi bi-building-check"></i>
                                <?= Html::encode($mroName) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Certificate type -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-tags"></i>
                            Type
                        </div>

                        <div class="detail-value">
                            <span class="type-badge">
                                <i class="bi bi-patch-check"></i>
                                <?= Html::encode($certificateType) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Certificate file name -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-file-earmark-text"></i>
                            Certificate File
                        </div>

                        <div class="detail-value">
                            <span class="file-badge">
                                <i class="bi bi-file-earmark"></i>
                                <?= Html::encode($fileName) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Download document -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-cloud-arrow-down"></i>
                            Document
                        </div>

                        <div class="detail-value">
                            <?php if (!empty($fileUrl)): ?>
                                <?= Html::a(
                                    '<i class="bi bi-download"></i> Download Certificate',
                                    $fileUrl,
                                    [
                                        'class' => 'document-badge',
                                        'target' => '_blank',
                                        'rel' => 'noopener',
                                        'title' => 'Download ' . $fileName,
                                    ]
                                ) ?>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-file-earmark-x"></i>
                                    No certificate file
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Certificate path -->
                    <!-- <div class="detail-item full-width">
                        <div class="detail-label">
                            <i class="bi bi-folder2-open"></i>
                            Certificate Path
                        </div>

                        <div class="path-box">
                            <?= Html::encode($certificateFile ?: 'N/A') ?>
                        </div>
                    </div> -->

                </div>
            </div>

            <!-- Quick actions and summary -->
            <div class="content-card">
                <h2 class="section-title">
                    <i class="bi bi-lightning-charge text-warning"></i>
                    Quick Actions
                </h2>

                <div class="quick-actions">

                    <?php if (!empty($fileUrl)): ?>
                        <?= Html::a(
                            '<i class="bi bi-download"></i> Download Certificate',
                            $fileUrl,
                            [
                                'class' => 'quick-action-btn qa-view',
                                'target' => '_blank',
                                'rel' => 'noopener',
                                'title' => 'Download ' . $fileName,
                            ]
                        ) ?>
                    <?php endif; ?>

                    <?= Html::a(
                        '<i class="bi bi-arrow-left-circle"></i> Back to Certificates',
                        $backUrl,
                        ['class' => 'quick-action-btn qa-back']
                    ) ?>

                </div>

                <h2 class="section-title" style="margin-top: 22px;">
                    <i class="bi bi-card-checklist text-primary"></i>
                    Summary
                </h2>

                <div class="summary-box">

                    <!-- Summary certificate ID -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-hash"></i>
                            Certificate
                        </div>
                        <div class="summary-value">
                            #<?= Html::encode($certificateId) ?>
                        </div>
                    </div>

                    <!-- Summary MRO -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-building"></i>
                            MRO
                        </div>
                        <div class="summary-value">
                            <?= Html::encode($mroName) ?>
                        </div>
                    </div>

                    <!-- Summary type -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-tags"></i>
                            Type
                        </div>
                        <div class="summary-value">
                            <?= Html::encode($certificateType) ?>
                        </div>
                    </div>

                    <!-- Summary file -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-file-earmark-text"></i>
                            File
                        </div>
                        <div class="summary-value">
                            <?= Html::encode($fileName) ?>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>
</main>
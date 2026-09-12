<?php

use yii\helpers\Html;
use yii\web\View;

/** @var yii\web\View $this */
/** @var app\models\Advert $advert */

/**
 * Page title and breadcrumbs.
 */
$this->title = 'View Advert: ' . ($advert->advert_id ?? 'N/A');
$this->params['breadcrumbs'][] = ['label' => 'Adverts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/**
 * Register Yii asset.
 */
\yii\web\YiiAsset::register($this);

/**
 * Prepare advert data safely.
 */
$advertId = $advert->advert_id ?? 'N/A';
$adminUsername = $advert->admin ? ($advert->admin->username ?? 'N/A') : 'N/A';
$advertType = $advert->advert_type ?? 'N/A';
$advertContent = $advert->content ?? null;
$timestamp = $advert->timestamp ?? 'N/A';
$startDate = $advert->start_date ?? 'N/A';
$endDate = $advert->end_date ?? 'N/A';
$status = $advert->status ?? 'N/A';

/**
 * Prepare advert status display safely.
 */
$statusText = $status !== 'N/A'
    ? ucwords(str_replace('_', ' ', (string) $status))
    : 'N/A';

$statusClass = $status !== 'N/A'
    ? 'status-' . preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower((string) $status))
    : 'status-default';

/**
 * Build a safe content URL.
 */
$buildContentUrl = static function ($content) {
    if (empty($content)) {
        return null;
    }

    $content = trim((string) $content);

    if ($content === '') {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $content)) {
        return $content;
    }

    if (strpos($content, '@web/') === 0) {
        return Yii::getAlias('@web') . '/' . ltrim(substr($content, 5), '/');
    }

    if (strpos($content, '/') !== false) {
        return Yii::getAlias('@web/') . ltrim($content, '/');
    }

    return Yii::getAlias('@web/uploads/') . ltrim($content, '/');
};

$contentUrl = $buildContentUrl($advertContent);

$contentFileName = !empty($advertContent)
    ? basename((string) $advertContent)
    : 'N/A';

$isPhoto = strtolower((string) $advertType) === 'photo';

/**
 * Prepare back URL.
 */
$backUrl = Yii::$app->request->referrer ?: ['index'];

/**
 * Register Bootstrap Icons.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['position' => View::POS_HEAD]
);

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

.advert-id-badge,
.admin-badge,
.type-badge,
.date-badge,
.file-badge,
.status-badge,
.empty-badge,
.media-link-badge {
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

.advert-id-badge {
    background: #eef2ff;
    color: #4338ca;
}

.admin-badge {
    background: #f1f5f9;
    color: #334155;
}

.type-badge {
    background: #e0f2fe;
    color: #0369a1;
}

.date-badge {
    background: #fff7ed;
    color: #c2410c;
}

.file-badge {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e5e7eb;
}

.status-badge {
    background: #e5e7eb;
    color: #374151;
}

.status-default {
    background: #e5e7eb;
    color: #374151;
}

.status-active,
.status-approved,
.status-published {
    background: #dcfce7;
    color: #166534;
}

.status-inactive,
.status-disabled {
    background: #f1f5f9;
    color: #475569;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-rejected,
.status-expired,
.status-canceled {
    background: #fee2e2;
    color: #991b1b;
}

.empty-badge {
    background: #f8fafc;
    color: #94a3b8;
    border: 1px solid #e5e7eb;
}

.media-link-badge {
    background: #ecfdf5;
    color: #047857 !important;
    border: 1px solid #bbf7d0;
}

.media-link-badge:hover {
    background: #d1fae5;
    color: #065f46 !important;
    text-decoration: none;
}

.media-preview-box {
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
    padding: 14px;
    overflow: hidden;
}

.advert-preview-image {
    width: 100%;
    max-width: 420px;
    height: auto;
    max-height: 280px;
    object-fit: contain;
    display: block;
    border-radius: 14px;
    border: 1px solid #e5eaf3;
    background: #f8fafc;
}

.advert-preview-video {
    width: 100%;
    max-width: 520px;
    max-height: 320px;
    display: block;
    border-radius: 14px;
    border: 1px solid #e5eaf3;
    background: #0f172a;
}

.media-file-info {
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
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

    .advert-id-badge,
    .admin-badge,
    .type-badge,
    .date-badge,
    .file-badge,
    .status-badge,
    .empty-badge,
    .media-link-badge {
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
                        <i class="bi bi-megaphone"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Detailed information about this advert, including media preview, dates and status.
                </div>
            </div>

            <div class="header-actions">
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Adverts',
                    $backUrl,
                    ['class' => 'btn-page-action btn-back']
                ) ?>
            </div>
        </div>

        <div class="view-grid">

            <!-- Main advert details -->
            <div class="content-card">
                <h2 class="section-title">
                    <i class="bi bi-info-circle text-primary"></i>
                    Advert Details
                </h2>

                <div class="detail-list">

                    <!-- Advert ID -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-hash"></i>
                            Advert ID
                        </div>

                        <div class="detail-value">
                            <span class="advert-id-badge">
                                #<?= Html::encode($advertId) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Admin username -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-person-badge"></i>
                            Admin Username
                        </div>

                        <div class="detail-value">
                            <?php if (!empty($adminUsername) && $adminUsername !== 'N/A'): ?>
                                <span class="admin-badge">
                                    <i class="bi bi-person-check"></i>
                                    <?= Html::encode($adminUsername) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-person-x"></i>
                                    N/A
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Advert type -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-tags"></i>
                            Advert Type
                        </div>

                        <div class="detail-value">
                            <span class="type-badge">
                                <i class="bi <?= $isPhoto ? 'bi-image' : 'bi-camera-video' ?>"></i>
                                <?= Html::encode($advertType) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-activity"></i>
                            Status
                        </div>

                        <div class="detail-value">
                            <span class="status-badge <?= Html::encode($statusClass) ?>">
                                <i class="bi bi-circle-fill"></i>
                                <?= Html::encode($statusText) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Timestamp -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-clock-history"></i>
                            Timestamp
                        </div>

                        <div class="detail-value">
                            <span class="date-badge">
                                <i class="bi bi-clock"></i>
                                <?= Html::encode($timestamp) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Start date -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-calendar-event"></i>
                            Start Date
                        </div>

                        <div class="detail-value">
                            <span class="date-badge">
                                <i class="bi bi-calendar-event"></i>
                                <?= Html::encode($startDate) ?>
                            </span>
                        </div>
                    </div>

                    <!-- End date -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-calendar-check"></i>
                            End Date
                        </div>

                        <div class="detail-value">
                            <span class="date-badge">
                                <i class="bi bi-calendar-check"></i>
                                <?= Html::encode($endDate) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Content file name -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-file-earmark-text"></i>
                            Content File
                        </div>

                        <div class="detail-value">
                            <span class="file-badge">
                                <i class="bi bi-file-earmark"></i>
                                <?= Html::encode($contentFileName) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Media preview -->
                    <div class="detail-item full-width">
                        <div class="detail-label">
                            <i class="bi bi-play-btn"></i>
                            Content Preview
                        </div>

                        <div class="media-preview-box">
                            <?php if (!empty($contentUrl)): ?>

                                <?php if ($isPhoto): ?>
                                    <?= Html::img($contentUrl, [
                                        'class' => 'advert-preview-image',
                                        'alt' => 'Advert content',
                                    ]) ?>
                                <?php else: ?>
                                    <video class="advert-preview-video" controls>
                                        <source src="<?= Html::encode($contentUrl) ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php endif; ?>

                                <div class="media-file-info">
                                    <?= Html::a(
                                        '<i class="bi bi-box-arrow-up-right"></i> Open Content',
                                        $contentUrl,
                                        [
                                            'class' => 'media-link-badge',
                                            'target' => '_blank',
                                            'rel' => 'noopener',
                                        ]
                                    ) ?>
                                </div>

                            <?php else: ?>

                                <span class="empty-badge">
                                    <i class="bi bi-file-earmark-x"></i>
                                    No content available
                                </span>

                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Quick actions and summary -->
            <div class="content-card">
                <h2 class="section-title">
                    <i class="bi bi-lightning-charge text-warning"></i>
                    Quick Actions
                </h2>

                <div class="quick-actions">

                    <?php if (!empty($contentUrl)): ?>
                        <?= Html::a(
                            '<i class="bi bi-box-arrow-up-right"></i> Open Content',
                            $contentUrl,
                            [
                                'class' => 'quick-action-btn qa-view',
                                'target' => '_blank',
                                'rel' => 'noopener',
                            ]
                        ) ?>
                    <?php endif; ?>

                    <?= Html::a(
                        '<i class="bi bi-arrow-left-circle"></i> Back to Adverts',
                        $backUrl,
                        ['class' => 'quick-action-btn qa-back']
                    ) ?>

                </div>

                <h2 class="section-title" style="margin-top: 22px;">
                    <i class="bi bi-card-checklist text-primary"></i>
                    Summary
                </h2>

                <div class="summary-box">

                    <!-- Summary advert ID -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-hash"></i>
                            Advert
                        </div>
                        <div class="summary-value">
                            #<?= Html::encode($advertId) ?>
                        </div>
                    </div>

                    <!-- Summary admin username -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-person-badge"></i>
                            Admin
                        </div>
                        <div class="summary-value">
                            <?= Html::encode($adminUsername) ?>
                        </div>
                    </div>

                    <!-- Summary advert type -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-tags"></i>
                            Type
                        </div>
                        <div class="summary-value">
                            <?= Html::encode($advertType) ?>
                        </div>
                    </div>

                    <!-- Summary status -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-activity"></i>
                            Status
                        </div>
                        <div class="summary-value">
                            <?= Html::encode($statusText) ?>
                        </div>
                    </div>

                    <!-- Summary start date -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-calendar-event"></i>
                            Start Date
                        </div>
                        <div class="summary-value">
                            <?= Html::encode($startDate) ?>
                        </div>
                    </div>

                    <!-- Summary end date -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-calendar-check"></i>
                            End Date
                        </div>
                        <div class="summary-value">
                            <?= Html::encode($endDate) ?>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>
</main>
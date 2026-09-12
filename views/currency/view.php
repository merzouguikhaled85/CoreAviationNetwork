<?php

use yii\helpers\Html;
use yii\web\View;

/** @var yii\web\View $this */
/** @var app\models\Currency $model */

/**
 * Page title.
 */
$this->title = 'View Currency';

/**
 * Register Yii asset.
 */
\yii\web\YiiAsset::register($this);

/**
 * Prepare currency data safely.
 */
$currencyId = $model->id ?? 'N/A';
$currencyName = $model->name ?? 'N/A';
$currencyCode = $model->code ?? 'N/A';
$currencySymbol = $model->symbol ?? 'N/A';

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

.currency-id-badge,
.currency-name-badge,
.currency-code-badge,
.currency-symbol-badge,
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

.currency-id-badge {
    background: #eef2ff;
    color: #4338ca;
}

.currency-name-badge {
    background: #e0f2fe;
    color: #0369a1;
}

.currency-code-badge {
    background: #ecfdf5;
    color: #047857;
}

.currency-symbol-badge {
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

    .currency-id-badge,
    .currency-name-badge,
    .currency-code-badge,
    .currency-symbol-badge,
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
                        <i class="bi bi-currency-exchange"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Detailed information about this currency record.
                </div>
            </div>

            <div class="header-actions">
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Currency List',
                    $backUrl,
                    ['class' => 'btn-page-action btn-back']
                ) ?>
            </div>
        </div>

        <div class="view-grid">

            <!-- Main currency details -->
            <div class="content-card">
                <h2 class="section-title">
                    <i class="bi bi-info-circle text-primary"></i>
                    Currency Details
                </h2>

                <div class="detail-list">

                    <!-- Currency ID -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-hash"></i>
                            Currency ID
                        </div>

                        <div class="detail-value">
                            <?php if (!empty($currencyId) && $currencyId !== 'N/A'): ?>
                                <span class="currency-id-badge">
                                    <i class="bi bi-hash"></i>
                                    <?= Html::encode($currencyId) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-dash-circle"></i>
                                    N/A
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Currency name -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-wallet2"></i>
                            Name
                        </div>

                        <div class="detail-value">
                            <?php if (!empty($currencyName) && $currencyName !== 'N/A'): ?>
                                <span class="currency-name-badge">
                                    <i class="bi bi-wallet2"></i>
                                    <?= Html::encode($currencyName) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-dash-circle"></i>
                                    N/A
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Currency code -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-upc-scan"></i>
                            Code
                        </div>

                        <div class="detail-value">
                            <?php if (!empty($currencyCode) && $currencyCode !== 'N/A'): ?>
                                <span class="currency-code-badge">
                                    <i class="bi bi-code-square"></i>
                                    <?= Html::encode($currencyCode) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-dash-circle"></i>
                                    N/A
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Currency symbol -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-cash-coin"></i>
                            Symbol
                        </div>

                        <div class="detail-value">
                            <?php if (!empty($currencySymbol) && $currencySymbol !== 'N/A'): ?>
                                <span class="currency-symbol-badge">
                                    <i class="bi bi-cash-coin"></i>
                                    <?= Html::encode($currencySymbol) ?>
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-dash-circle"></i>
                                    N/A
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
                    <?= Html::a(
                        '<i class="bi bi-arrow-left-circle"></i> Back to Currency List',
                        $backUrl,
                        ['class' => 'quick-action-btn qa-back']
                    ) ?>
                </div>

                <h2 class="section-title" style="margin-top: 22px;">
                    <i class="bi bi-card-checklist text-primary"></i>
                    Summary
                </h2>

                <div class="summary-box">

                    <!-- Summary currency ID -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-hash"></i>
                            Currency ID
                        </div>

                        <div class="summary-value">
                            <?= Html::encode($currencyId) ?>
                        </div>
                    </div>

                    <!-- Summary currency name -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-wallet2"></i>
                            Name
                        </div>

                        <div class="summary-value">
                            <?= Html::encode($currencyName) ?>
                        </div>
                    </div>

                    <!-- Summary currency code -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-upc-scan"></i>
                            Code
                        </div>

                        <div class="summary-value">
                            <?= Html::encode($currencyCode) ?>
                        </div>
                    </div>

                    <!-- Summary currency symbol -->
                    <div class="summary-item">
                        <div class="summary-label">
                            <i class="bi bi-cash-coin"></i>
                            Symbol
                        </div>

                        <div class="summary-value">
                            <?= Html::encode($currencySymbol) ?>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>
</main>
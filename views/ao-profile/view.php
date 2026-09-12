<?php

use yii\helpers\Html;
use yii\web\View;

/** @var yii\web\View $this */
/** @var app\models\AoProfile $aoProfile */

/* =========================================================
   Page title and breadcrumbs.
   This view keeps the same AO profile data and only improves
   the visual presentation to match the MRO profile view style.
   ========================================================= */
$this->title = 'AO Profile: ' . ($aoProfile->username ?? $aoProfile->ao_id ?? 'AO');
$this->params['breadcrumbs'][] = ['label' => 'AO Profiles', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/* =========================================================
   Safe values and reusable helpers.
   ========================================================= */
$backUrl = Yii::$app->request->referrer ?: ['index'];

$displayValue = static function ($value, $fallback = 'Not provided') {
    if ($value === null) {
        return $fallback;
    }

    $value = trim((string) $value);
    return $value !== '' ? $value : $fallback;
};

$buildFileUrl = static function ($file) {
    if (empty($file)) {
        return null;
    }

    $file = trim((string) $file);

    if ($file === '') {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $file)) {
        return $file;
    }

    if (strpos($file, '@web/') === 0) {
        return Yii::getAlias('@web') . '/' . ltrim(substr($file, 5), '/');
    }

    return Yii::$app->request->baseUrl . '/' . ltrim($file, '/');
};

$getInitials = static function ($name) {
    $name = trim((string) $name);

    if ($name === '') {
        return 'AO';
    }

    $parts = preg_split('/\s+/', $name);
    $first = isset($parts[0]) ? mb_substr($parts[0], 0, 1) : '';
    $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '';

    $initials = mb_strtoupper($first . $second);
    return $initials !== '' ? $initials : 'AO';
};

$aoId = $aoProfile->ao_id ?? 'N/A';
$username = $displayValue($aoProfile->username ?? null, 'AO Profile');
$email = $displayValue($aoProfile->email ?? null);
$firstName = $displayValue($aoProfile->first_name ?? null);
$lastName = $displayValue($aoProfile->last_name ?? null);
$contactNumber = $displayValue($aoProfile->contact_number ?? null);
$companyName = $displayValue($aoProfile->company_name ?? null, 'Aircraft Operator');
$address = $displayValue($aoProfile->address ?? null);
$zipCode = $displayValue($aoProfile->zip_code ?? null);

$fullNameRaw = trim((string) ($aoProfile->first_name ?? '') . ' ' . (string) ($aoProfile->last_name ?? ''));
$fullName = $fullNameRaw !== '' ? $fullNameRaw : $username;

$profilePhotoUrl = $buildFileUrl($aoProfile->profile_photo ?? null);

$countryName = $displayValue(
    $aoProfile->country->country_name
        ?? $aoProfile->country_name
        ?? $aoProfile->country_id
        ?? null
);

$cityName = $displayValue(
    $aoProfile->city->city_name
        ?? $aoProfile->city_name
        ?? $aoProfile->city_id
        ?? null
);

$status = $aoProfile->status ?? 'N/A';
$statusText = $displayValue($status, 'N/A');
$statusText = $statusText !== 'N/A' ? ucfirst((string) $statusText) : 'N/A';
$statusSlug = strtolower((string) $status);
$statusClass = 'status-default';
$statusIcon = 'bi-circle-fill';

if ($statusSlug === 'active') {
    $statusClass = 'status-active';
    $statusIcon = 'bi-check-circle-fill';
} elseif (in_array($statusSlug, ['inactive', 'disabled', 'blocked', 'suspended'], true)) {
    $statusClass = 'status-inactive';
    $statusIcon = 'bi-x-circle-fill';
}

$emailVerified = !empty($aoProfile->email_verified);
$emailVerifiedText = $emailVerified ? 'Verified' : 'Not verified';
$emailVerifiedClass = $emailVerified ? 'verified-yes' : 'verified-no';
$emailVerifiedIcon = $emailVerified ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';

/* =========================================================
   Assets.
   ========================================================= */
\yii\web\YiiAsset::register($this);

$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['position' => View::POS_HEAD]
);

$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    ['position' => View::POS_HEAD]
);

$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
    ['depends' => [\yii\web\JqueryAsset::class]]
);

/* =========================================================
   Page CSS.
   Same visual direction as the MRO profile view:
   clean light background, soft cards, amber action style,
   aviation-style icons and responsive layout.
   ========================================================= */
$this->registerCss(<<<CSS
html,
body {
    max-width: 100%;
    overflow-x: hidden;
}

.view-profile-page {
    padding: 24px;
    background: #f5f7fb;
    min-height: 100vh;
    max-width: 100%;
    overflow-x: hidden;
}

.view-profile-page .container-fluid {
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
    font-weight: 900;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 10px;
}

.title-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 13px;
    background: #fff7ed;
    color: #c2410c;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.subtitle-text {
    color: #6b7280;
    margin-top: 7px;
    font-size: 14px;
    font-weight: 700;
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
    font-weight: 900;
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

.profile-hero-card,
.info-card,
.helper-card {
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5eaf3;
    max-width: 100%;
}

.profile-hero-card {
    padding: 22px;
    margin-bottom: 18px;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 18px;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.profile-hero-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 5px;
    background: linear-gradient(90deg, #f59e0b, #0ea5e9, #0f172a);
}

.profile-avatar,
.profile-avatar-placeholder {
    width: 112px;
    height: 112px;
    border-radius: 24px;
    border: 4px solid #ffffff;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.16);
}

.profile-avatar {
    object-fit: cover;
    cursor: zoom-in;
    transition: transform .2s ease, box-shadow .2s ease;
}

.profile-avatar:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.20);
}

.profile-avatar-placeholder {
    background: linear-gradient(135deg, #fff7ed, #ffedd5);
    color: #c2410c;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: 950;
}

.hero-kicker {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #c2410c;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 900;
    margin-bottom: 9px;
}

.hero-title {
    margin: 0;
    color: #0f172a;
    font-size: 25px;
    font-weight: 950;
    letter-spacing: -0.02em;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.hero-subtitle {
    margin-top: 6px;
    color: #64748b;
    font-size: 14px;
    font-weight: 750;
    overflow-wrap: anywhere;
}

.hero-links {
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 9px;
    flex-wrap: wrap;
}

.hero-chip {
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 12px;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    color: #334155 !important;
    font-size: 12px;
    font-weight: 900;
    text-decoration: none;
}

.hero-chip:hover {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #c2410c !important;
    text-decoration: none;
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(105px, 1fr));
    gap: 10px;
}

.stat-card {
    min-width: 105px;
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 16px;
    padding: 14px;
    text-align: center;
}

.stat-value {
    color: #0f172a;
    font-size: 20px;
    font-weight: 950;
    line-height: 1.15;
    overflow-wrap: anywhere;
}

.stat-label {
    margin-top: 6px;
    color: #64748b;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.status-pill,
.verified-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 11px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 950;
    white-space: nowrap;
}

.status-default {
    background: #e5e7eb;
    color: #374151;
    border: 1px solid #d1d5db;
}

.status-active {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.status-inactive {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.verified-yes {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.verified-no {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.view-layout {
    display: grid;
    grid-template-columns: 1.25fr 0.75fr;
    gap: 18px;
    align-items: start;
}

.view-main {
    display: grid;
    gap: 18px;
}

.info-card,
.helper-card {
    padding: 20px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 18px;
    font-size: 17px;
    font-weight: 950;
    color: #0f172a;
}

.section-title i {
    color: #f59e0b;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.info-item {
    min-width: 0;
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 14px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 12px;
    background: #fff7ed;
    color: #c2410c;
    display: flex;
    align-items: center;
    justify-content: center;
}

.info-label {
    display: block;
    color: #64748b;
    font-size: 11px;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 4px;
}

.info-value {
    display: block;
    color: #0f172a;
    font-size: 14px;
    font-weight: 850;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.info-link {
    color: #c2410c !important;
    text-decoration: none;
}

.info-link:hover {
    color: #9a3412 !important;
    text-decoration: underline;
}

.profile-media-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 16px;
}

.profile-media-photo {
    width: 100%;
    max-width: 260px;
    max-height: 210px;
    object-fit: cover;
    border-radius: 14px;
    cursor: zoom-in;
    background: #ffffff;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12);
    transition: transform .2s ease, box-shadow .2s ease;
}

.profile-media-photo:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.20);
}

.empty-photo {
    width: 100%;
    min-height: 140px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #e5eaf3;
    color: #64748b;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 7px;
    font-size: 13px;
    font-weight: 850;
}

.empty-photo i {
    color: #f59e0b;
    font-size: 28px;
}

.profile-status-box {
    background: linear-gradient(135deg, #fff7ed, #ffffff);
    border: 1px solid #fed7aa;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 14px;
}

.status-box-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.status-box-row:last-child {
    margin-bottom: 0;
}

.helper-list {
    display: grid;
    gap: 12px;
}

.helper-item {
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 14px;
}

.helper-label {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #0f172a;
    font-size: 13px;
    font-weight: 950;
    margin-bottom: 5px;
}

.helper-label i {
    color: #f59e0b;
}

.helper-text {
    color: #64748b;
    font-size: 13px;
    font-weight: 750;
    line-height: 1.5;
    margin: 0;
}

.swal-image-popup {
    width: min(680px, 92vw) !important;
    border-radius: 18px !important;
    padding: 22px !important;
}

.swal-image-title {
    font-size: 20px !important;
    font-weight: 950 !important;
    color: #0f172a !important;
}

.swal-image-confirm {
    border-radius: 10px !important;
    padding: 10px 18px !important;
    font-size: 13px !important;
    font-weight: 900 !important;
    background: #f59e0b !important;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(245, 158, 11, 0.25) !important;
}

@media (max-width: 1100px) {
    .profile-hero-card {
        grid-template-columns: auto minmax(0, 1fr);
    }

    .hero-stats {
        grid-column: 1 / -1;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 992px) {
    .view-profile-page {
        padding: 14px;
    }

    .page-header-card {
        padding: 18px;
    }

    .dash-title {
        font-size: 23px;
    }

    .profile-hero-card,
    .view-layout,
    .info-grid {
        grid-template-columns: 1fr;
    }

    .hero-stats {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .view-profile-page {
        padding: 10px;
    }

    .page-header-card,
    .profile-hero-card,
    .info-card,
    .helper-card {
        border-radius: 14px;
        padding: 16px;
    }

    .header-actions,
    .btn-page-action {
        width: 100%;
    }

    .btn-page-action {
        justify-content: center;
    }

    .profile-avatar,
    .profile-avatar-placeholder {
        width: 92px;
        height: 92px;
        border-radius: 20px;
    }

    .hero-stats {
        grid-template-columns: 1fr;
    }

    .status-pill,
    .verified-pill,
    .hero-chip {
        white-space: normal;
    }
}
CSS);

/* =========================================================
   Image popup JS.
   Clicking the profile photo opens a SweetAlert preview.
   ========================================================= */
$this->registerJs(<<<JS
(function () {
    function initImagePopups() {
        var images = document.querySelectorAll('[data-image-popup="1"]');

        images.forEach(function (image) {
            image.addEventListener('click', function () {
                var src = image.getAttribute('src');
                var title = image.getAttribute('data-popup-title') || image.getAttribute('alt') || 'Image preview';

                if (!src || typeof Swal === 'undefined') {
                    return;
                }

                Swal.fire({
                    title: title,
                    imageUrl: src,
                    imageAlt: title,
                    confirmButtonText: '<i class="bi bi-x-circle"></i> Close',
                    customClass: {
                        popup: 'swal-image-popup',
                        title: 'swal-image-title',
                        confirmButton: 'swal-image-confirm'
                    }
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initImagePopups);
    } else {
        initImagePopups();
    }
})();
JS, View::POS_END);
?>

<main class="dash-content view-profile-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span class="title-icon">
                        <i class="bi bi-person-badge"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    View the Aircraft Operator profile, company identity and contact details.
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

        <!-- Profile hero summary -->
        <div class="profile-hero-card">
            <div>
                <?php if ($profilePhotoUrl): ?>
                    <?= Html::img($profilePhotoUrl, [
                        'class' => 'profile-avatar',
                        'alt' => 'AO profile photo',
                        'data-image-popup' => '1',
                        'data-popup-title' => 'Profile photo',
                    ]) ?>
                <?php else: ?>
                    <div class="profile-avatar-placeholder">
                        <?= Html::encode($getInitials($fullName)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="hero-kicker">
                    <i class="bi bi-airplane"></i>
                    Aircraft Operator
                </div>

                <h2 class="hero-title">
                    <?= Html::encode($displayValue($fullName, 'AO Profile')) ?>
                </h2>

                <div class="hero-subtitle">
                    <?= Html::encode($companyName) ?>
                    <?php if (!empty($email) && $email !== 'Not provided'): ?>
                        &nbsp;•&nbsp; <?= Html::encode($email) ?>
                    <?php endif; ?>
                </div>

                <div class="hero-links">
                    <span class="hero-chip">
                        <i class="bi bi-person"></i>
                        <?= Html::encode($username) ?>
                    </span>

                    <?php if (!empty($contactNumber) && $contactNumber !== 'Not provided'): ?>
                        <span class="hero-chip">
                            <i class="bi bi-telephone"></i>
                            <?= Html::encode($contactNumber) ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($cityName) && $cityName !== 'Not provided'): ?>
                        <span class="hero-chip">
                            <i class="bi bi-geo-alt"></i>
                            <?= Html::encode($cityName) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="hero-stats">
                <div class="stat-card">
                    <div class="stat-value">#<?= Html::encode((string) $aoId) ?></div>
                    <div class="stat-label">AO ID</div>
                </div>

                <div class="stat-card">
                    <div class="stat-value">
                        <span class="status-pill <?= Html::encode($statusClass) ?>">
                            <i class="bi <?= Html::encode($statusIcon) ?>"></i>
                            <?= Html::encode($statusText) ?>
                        </span>
                    </div>
                    <div class="stat-label">Status</div>
                </div>

                <div class="stat-card">
                    <div class="stat-value">
                        <span class="verified-pill <?= Html::encode($emailVerifiedClass) ?>">
                            <i class="bi <?= Html::encode($emailVerifiedIcon) ?>"></i>
                            <?= Html::encode($emailVerifiedText) ?>
                        </span>
                    </div>
                    <div class="stat-label">Email</div>
                </div>
            </div>
        </div>

        <div class="view-layout">

            <!-- Main profile details -->
            <div class="view-main">

                <!-- Profile information -->
                <div class="info-card">
                    <h2 class="section-title">
                        <i class="bi bi-person-badge"></i>
                        Profile Information
                    </h2>

                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-hash"></i></div>
                            <div>
                                <span class="info-label">AO ID</span>
                                <span class="info-value">#<?= Html::encode((string) $aoId) ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-person"></i></div>
                            <div>
                                <span class="info-label">Username</span>
                                <span class="info-value"><?= Html::encode($username) ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-person-lines-fill"></i></div>
                            <div>
                                <span class="info-label">First Name</span>
                                <span class="info-value"><?= Html::encode($firstName) ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-person-vcard"></i></div>
                            <div>
                                <span class="info-label">Last Name</span>
                                <span class="info-value"><?= Html::encode($lastName) ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-envelope"></i></div>
                            <div>
                                <span class="info-label">Email Address</span>
                                <span class="info-value">
                                    <?php if (!empty($email) && $email !== 'Not provided'): ?>
                                        <?= Html::a(Html::encode($email), 'mailto:' . $email, ['class' => 'info-link']) ?>
                                    <?php else: ?>
                                        <?= Html::encode($email) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-telephone"></i></div>
                            <div>
                                <span class="info-label">Contact Number</span>
                                <span class="info-value"><?= Html::encode($contactNumber) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Company information -->
                <div class="info-card">
                    <h2 class="section-title">
                        <i class="bi bi-building"></i>
                        Company Information
                    </h2>

                    <div class="info-grid">
                        <div class="info-item full-width">
                            <div class="info-icon"><i class="bi bi-building-check"></i></div>
                            <div>
                                <span class="info-label">Company Name</span>
                                <span class="info-value"><?= Html::encode($companyName) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location information -->
                <div class="info-card">
                    <h2 class="section-title">
                        <i class="bi bi-geo-alt"></i>
                        Location Information
                    </h2>

                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-globe2"></i></div>
                            <div>
                                <span class="info-label">Country</span>
                                <span class="info-value"><?= Html::encode($countryName) ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-buildings"></i></div>
                            <div>
                                <span class="info-label">City</span>
                                <span class="info-value"><?= Html::encode($cityName) ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-mailbox"></i></div>
                            <div>
                                <span class="info-label">Post / Zip Code</span>
                                <span class="info-value"><?= Html::encode($zipCode) ?></span>
                            </div>
                        </div>

                        <div class="info-item full-width">
                            <div class="info-icon"><i class="bi bi-house-door"></i></div>
                            <div>
                                <span class="info-label">Address</span>
                                <span class="info-value"><?= Html::encode($address) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right helper / media column -->
            <aside class="helper-card">
                <h2 class="section-title">
                    <i class="bi bi-card-image"></i>
                    Profile Media
                </h2>

                <div class="profile-media-wrap">
                    <?php if ($profilePhotoUrl): ?>
                        <?= Html::img($profilePhotoUrl, [
                            'class' => 'profile-media-photo',
                            'alt' => 'AO profile photo',
                            'data-image-popup' => '1',
                            'data-popup-title' => 'Profile photo',
                        ]) ?>
                    <?php else: ?>
                        <div class="empty-photo">
                            <i class="bi bi-image"></i>
                            No profile photo uploaded
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-status-box">
                    <div class="status-box-row">
                        <span class="info-label">Profile Status</span>
                        <span class="status-pill <?= Html::encode($statusClass) ?>">
                            <i class="bi <?= Html::encode($statusIcon) ?>"></i>
                            <?= Html::encode($statusText) ?>
                        </span>
                    </div>

                    <div class="status-box-row">
                        <span class="info-label">Email Verification</span>
                        <span class="verified-pill <?= Html::encode($emailVerifiedClass) ?>">
                            <i class="bi <?= Html::encode($emailVerifiedIcon) ?>"></i>
                            <?= Html::encode($emailVerifiedText) ?>
                        </span>
                    </div>
                </div>

                <div class="helper-list">
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-airplane-engines"></i>
                            Aircraft Operator
                        </div>
                        <p class="helper-text">
                            This read-only view groups the AO identity, company and contact information in one clean profile page.
                        </p>
                    </div>

                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-shield-check"></i>
                            Account Overview
                        </div>
                        <p class="helper-text">
                            Status and email verification are highlighted to make account validation easier to review.
                        </p>
                    </div>

                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-zoom-in"></i>
                            Image Preview
                        </div>
                        <p class="helper-text">
                            Click the profile photo to open it in a larger popup preview.
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>

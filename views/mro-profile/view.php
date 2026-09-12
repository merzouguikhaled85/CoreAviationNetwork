<?php

use yii\helpers\Html;
use yii\web\View;

/** @var yii\web\View $this */
/** @var app\models\MroProfile $mroProfile */
/** @var int $projectRealisedCount */
/** @var float|int|string $averageRating */

/* =========================================================
   Page title and breadcrumbs.
   This view keeps the same displayed data from the original
   MRO profile view and only improves the visual presentation.
   ========================================================= */
$this->title = 'MRO Profile: ' . ($mroProfile->username ?? 'MRO');
$this->params['breadcrumbs'][] = ['label' => 'MRO Profiles', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/* =========================================================
   Safe values and reusable helpers.
   ========================================================= */
$companyName = $mroProfile->company_name ?: ($mroProfile->username ?? 'MRO Profile');
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

$buildExternalUrl = static function ($url) {
    $url = trim((string) $url);

    if ($url === '') {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $url)) {
        return $url;
    }

    return 'https://' . ltrim($url, '/');
};

$getInitials = static function ($name) {
    $name = trim((string) $name);

    if ($name === '') {
        return 'MRO';
    }

    $parts = preg_split('/\s+/', $name);
    $first = isset($parts[0]) ? mb_substr($parts[0], 0, 1) : '';
    $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '';

    $initials = mb_strtoupper($first . $second);
    return $initials !== '' ? $initials : 'MRO';
};

$profilePhotoUrl = $buildFileUrl($mroProfile->profile_photo ?? null);
$companyPhotoUrl = $buildFileUrl($mroProfile->company_photo ?? null);
$websiteUrl = $buildExternalUrl($mroProfile->website ?? null);
$videoUrl = $buildExternalUrl($mroProfile->youtube_video ?? null);

$averageRatingValue = is_numeric($averageRating ?? null) ? (float) $averageRating : 0;
$averageRatingText = number_format($averageRatingValue, 1);
$projectCountText = Html::encode((string) ($projectRealisedCount ?? 0));

$referencesRaw = $mroProfile->main_references ?? [];

if (is_array($referencesRaw)) {
    $references = $referencesRaw;
} else {
    $references = explode(',', (string) $referencesRaw);
}

$references = array_values(array_filter(array_map('trim', $references), static function ($reference) {
    return $reference !== '';
}));

/* =========================================================
   Assets.
   ========================================================= */
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
   Same visual direction as the update profile/password pages:
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

.profile-avatar:hover,
.company-photo:hover {
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
}

.hero-subtitle {
    margin-top: 6px;
    color: #64748b;
    font-size: 14px;
    font-weight: 750;
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
    grid-template-columns: repeat(2, minmax(115px, 1fr));
    gap: 10px;
}

.stat-card {
    min-width: 120px;
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 16px;
    padding: 14px;
    text-align: center;
}

.stat-value {
    color: #0f172a;
    font-size: 24px;
    font-weight: 950;
    line-height: 1;
}

.stat-label {
    margin-top: 6px;
    color: #64748b;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
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

.company-photo-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 16px;
}

.company-photo {
    width: 100%;
    max-width: 260px;
    max-height: 175px;
    object-fit: contain;
    border-radius: 14px;
    cursor: zoom-in;
    background: #ffffff;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12);
    transition: transform .2s ease, box-shadow .2s ease;
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

.reference-list {
    display: grid;
    gap: 10px;
}

.reference-item {
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 12px 13px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.reference-name {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 9px;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
}

.reference-name span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.reference-name i {
    color: #f59e0b;
    font-size: 18px;
}

.reference-action {
    min-width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    color: #334155 !important;
    border: 1px solid #cbd5e1;
    text-decoration: none;
}

.reference-action:hover {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #c2410c !important;
    text-decoration: none;
}

.empty-state {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 14px;
    padding: 16px;
    color: #64748b;
    font-size: 13px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 9px;
}

.rating-box {
    background: linear-gradient(135deg, #fff7ed, #ffffff);
    border: 1px solid #fed7aa;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 14px;
}

.rating-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.rating-number {
    font-size: 32px;
    line-height: 1;
    font-weight: 950;
    color: #c2410c;
}

.stars {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    color: #f59e0b;
    font-size: 19px;
    line-height: 1;
}

.stars .empty-star {
    color: #cbd5e1;
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
        grid-template-columns: repeat(2, minmax(0, 1fr));
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

    .profile-avatar,
    .profile-avatar-placeholder {
        width: 92px;
        height: 92px;
        border-radius: 20px;
    }

    .hero-stats {
        grid-template-columns: 1fr;
    }

    .reference-item {
        align-items: flex-start;
    }
}
CSS);

/* =========================================================
   Image popup JS.
   Clicking profile or company photo opens a SweetAlert preview.
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

/* =========================================================
   Stars renderer.
   Kept as a local helper and protected against redeclaration.
   ========================================================= */
if (!function_exists('displayMroProfileStars')) {
    function displayMroProfileStars($rating)
    {
        $rating = is_numeric($rating) ? (float) $rating : 0;
        $fullStars = (int) floor(max(0, min(5, $rating)));
        $stars = '';

        for ($i = 1; $i <= 5; $i++) {
            $class = $i <= $fullStars ? 'full-star' : 'empty-star';
            $stars .= '<span class="' . $class . '">&#9733;</span>';
        }

        return '<div class="stars" aria-label="Rating ' . Html::encode((string) $rating) . ' out of 5">' . $stars . '</div>';
    }
}
?>

<main class="dash-content view-profile-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span class="title-icon">
                        <i class="bi bi-buildings"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    View the Maintenance, Repair &amp; Overhaul provider profile, references and performance indicators.
                </div>
            </div>

            <div class="header-actions">
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Requests',
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
                        'alt' => 'MRO profile photo',
                        'data-image-popup' => '1',
                        'data-popup-title' => 'Profile photo',
                    ]) ?>
                <?php else: ?>
                    <div class="profile-avatar-placeholder">
                        <?= Html::encode($getInitials($companyName)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="hero-kicker">
                    <i class="bi bi-airplane-engines"></i>
                    MRO Provider
                </div>

                <h2 class="hero-title">
                    <?= Html::encode($displayValue($companyName, 'MRO Profile')) ?>
                </h2>

                <div class="hero-subtitle">
                    <?= Html::encode($displayValue($mroProfile->username ?? null)) ?>
                    <?php if (!empty($mroProfile->email)): ?>
                        &nbsp;•&nbsp; <?= Html::encode($mroProfile->email) ?>
                    <?php endif; ?>
                </div>

                <div class="hero-links">
                    <?php if ($websiteUrl): ?>
                        <?= Html::a(
                            '<i class="bi bi-globe2"></i> Website',
                            $websiteUrl,
                            ['class' => 'hero-chip', 'target' => '_blank', 'rel' => 'noopener noreferrer']
                        ) ?>
                    <?php endif; ?>

                    <?php if ($videoUrl): ?>
                        <?= Html::a(
                            '<i class="bi bi-play-circle"></i> Video / Social link',
                            $videoUrl,
                            ['class' => 'hero-chip', 'target' => '_blank', 'rel' => 'noopener noreferrer']
                        ) ?>
                    <?php endif; ?>

                    <?php if (!empty($mroProfile->contact_number)): ?>
                        <span class="hero-chip">
                            <i class="bi bi-telephone"></i>
                            <?= Html::encode($mroProfile->contact_number) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="hero-stats">
                <div class="stat-card">
                    <div class="stat-value"><?= $projectCountText ?></div>
                    <div class="stat-label">Completed projects</div>
                </div>

                <div class="stat-card">
                    <div class="stat-value"><?= Html::encode($averageRatingText) ?></div>
                    <div class="stat-label">Average rating</div>
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
                            <div class="info-icon"><i class="bi bi-person"></i></div>
                            <div>
                                <span class="info-label">Username</span>
                                <span class="info-value"><?= Html::encode($displayValue($mroProfile->username ?? null)) ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-envelope"></i></div>
                            <div>
                                <span class="info-label">Email Address</span>
                                <span class="info-value"><?= Html::encode($displayValue($mroProfile->email ?? null)) ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-person-lines-fill"></i></div>
                            <div>
                                <span class="info-label">Contact Name</span>
                                <span class="info-value"><?= Html::encode($displayValue($mroProfile->first_name ?? null)) ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-person-vcard"></i></div>
                            <div>
                                <span class="info-label">Contact Lastname</span>
                                <span class="info-value"><?= Html::encode($displayValue($mroProfile->last_name ?? null)) ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-telephone"></i></div>
                            <div>
                                <span class="info-label">Contact Number</span>
                                <span class="info-value"><?= Html::encode($displayValue($mroProfile->contact_number ?? null)) ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-mailbox"></i></div>
                            <div>
                                <span class="info-label">Post / Zip Code</span>
                                <span class="info-value"><?= Html::encode($displayValue($mroProfile->zip_code ?? null)) ?></span>
                            </div>
                        </div>

                        <div class="info-item full-width">
                            <div class="info-icon"><i class="bi bi-geo-alt"></i></div>
                            <div>
                                <span class="info-label">Address</span>
                                <span class="info-value"><?= Html::encode($displayValue($mroProfile->address ?? null)) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Company information -->
                <div class="info-card">
                    <h2 class="section-title">
                        <i class="bi bi-buildings"></i>
                        Company Information
                    </h2>

                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-building"></i></div>
                            <div>
                                <span class="info-label">Company Name</span>
                                <span class="info-value"><?= Html::encode($displayValue($mroProfile->company_name ?? null)) ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-globe2"></i></div>
                            <div>
                                <span class="info-label">Website</span>
                                <span class="info-value">
                                    <?php if ($websiteUrl): ?>
                                        <?= Html::a(Html::encode($mroProfile->website), $websiteUrl, [
                                            'class' => 'info-link',
                                            'target' => '_blank',
                                            'rel' => 'noopener noreferrer',
                                        ]) ?>
                                    <?php else: ?>
                                        <?= Html::encode($displayValue(null)) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-item full-width">
                            <div class="info-icon"><i class="bi bi-play-circle"></i></div>
                            <div>
                                <span class="info-label">Social Media / Video Link</span>
                                <span class="info-value">
                                    <?php if ($videoUrl): ?>
                                        <?= Html::a(Html::encode($mroProfile->youtube_video), $videoUrl, [
                                            'class' => 'info-link',
                                            'target' => '_blank',
                                            'rel' => 'noopener noreferrer',
                                        ]) ?>
                                    <?php else: ?>
                                        <?= Html::encode($displayValue(null)) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- References -->
                <div class="info-card">
                    <h2 class="section-title">
                        <i class="bi bi-folder2-open"></i>
                        Main References
                    </h2>

                    <?php if (!empty($references)): ?>
                        <div class="reference-list">
                            <?php foreach ($references as $reference): ?>
                                <?php $referenceUrl = $buildFileUrl($reference); ?>
                                <div class="reference-item">
                                    <div class="reference-name">
                                        <i class="bi bi-file-earmark-text"></i>
                                        <span title="<?= Html::encode(basename($reference)) ?>">
                                            <?= Html::encode(basename($reference)) ?>
                                        </span>
                                    </div>

                                    <?php if ($referenceUrl): ?>
                                        <?= Html::a(
                                            '<i class="bi bi-download"></i>',
                                            $referenceUrl,
                                            [
                                                'class' => 'reference-action',
                                                'download' => true,
                                                'title' => 'Download reference',
                                            ]
                                        ) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-info-circle"></i>
                            No references uploaded.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right helper / media column -->
            <aside class="helper-card">
                <h2 class="section-title">
                    <i class="bi bi-card-image"></i>
                    Company Media
                </h2>

                <div class="company-photo-wrap">
                    <?php if ($companyPhotoUrl): ?>
                        <?= Html::img($companyPhotoUrl, [
                            'class' => 'company-photo',
                            'alt' => 'Company photo',
                            'data-image-popup' => '1',
                            'data-popup-title' => 'Company photo',
                        ]) ?>
                    <?php else: ?>
                        <div class="empty-photo">
                            <i class="bi bi-image"></i>
                            No company photo uploaded
                        </div>
                    <?php endif; ?>
                </div>

                <div class="rating-box">
                    <div class="rating-head">
                        <div>
                            <span class="info-label">Average Rating</span>
                            <div class="rating-number"><?= Html::encode($averageRatingText) ?></div>
                        </div>

                        <?= displayMroProfileStars($averageRatingValue) ?>
                    </div>
                </div>

                <div class="helper-list">
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-check2-circle"></i>
                            Maintenance Operations
                        </div>
                        <p class="helper-text">
                            This MRO has completed <strong><?= $projectCountText ?></strong> project(s) on the platform.
                        </p>
                    </div>

                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-shield-check"></i>
                            Profile Overview
                        </div>
                        <p class="helper-text">
                            Contact details, company media and references are grouped in one read-only profile view.
                        </p>
                    </div>

                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-zoom-in"></i>
                            Image Preview
                        </div>
                        <p class="helper-text">
                            Click the profile or company photo to open it in a larger popup preview.
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>

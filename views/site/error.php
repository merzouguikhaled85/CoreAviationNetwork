<?php

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */
/** @var Exception $exception */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $name;

// Extract error code and clean title
$errorCode = '';
if (preg_match('/\(#(\d+)\)/', $name, $matches)) {
    $errorCode = $matches[1];
    $cleanName = trim(str_replace($matches[0], '', $name));
} else {
    $cleanName = $name;
}

if (empty($errorCode) && isset($exception) && method_exists($exception, 'getStatusCode')) {
    $errorCode = (string) $exception->getStatusCode();
}

// Error content by HTTP code
$errorMap = [
    '400' => [
        'icon' => 'ti-alert-circle',
        'footerTitle' => 'Bad request',
        'footerSub' => 'The server could not process the submitted request.',
        'pill' => 'Bad Request',
        'title' => 'Bad Request',
        'msg' => 'The submitted request is malformed or contains invalid parameters. Please check your data and try again.',
    ],
    '401' => [
        'icon' => 'ti-shield-x',
        'footerTitle' => 'Session expired',
        'footerSub' => 'Your authentication token is invalid or has expired.',
        'pill' => 'Authentication required',
        'title' => 'Unauthenticated',
        'msg' => 'Your session has expired or your credentials are invalid. Please log in again to continue.',
    ],
    '403' => [
        'icon' => 'ti-lock',
        'footerTitle' => 'Access denied',
        'footerSub' => 'You do not have the required permissions for this resource.',
        'pill' => 'Restricted access',
        'title' => 'Unauthorized Access',
        'msg' => 'You do not have the necessary permissions to view this page. Please contact your administrator.',
    ],
    '404' => [
        'icon' => 'ti-map-search',
        'footerTitle' => 'Route not found',
        'footerSub' => 'The requested destination does not exist on this network.',
        'pill' => 'System message',
        'title' => 'Page Not Found',
        'msg' => 'The page you are looking for does not exist, has been moved, or is temporarily unavailable.',
    ],
    '500' => [
        'icon' => 'ti-server-off',
        'footerTitle' => 'Server error',
        'footerSub' => 'An internal anomaly occurred. Our teams have been notified.',
        'pill' => 'Internal error',
        'title' => 'Internal Server Error',
        'msg' => 'An unexpected error occurred on the server. Our technical team has been automatically notified.',
    ],
    '503' => [
        'icon' => 'ti-cloud-off',
        'footerTitle' => 'Service unavailable',
        'footerSub' => 'The service is under maintenance or temporarily offline.',
        'pill' => 'Maintenance',
        'title' => 'Service Temporarily Unavailable',
        'msg' => 'The service is currently undergoing maintenance. Please try again later.',
    ],
];

$e = $errorMap[$errorCode] ?? [
    'icon' => 'ti-alert-hexagon',
    'footerTitle' => 'Unexpected error',
    'footerSub' => 'An unforeseen situation has occurred.',
    'pill' => 'Application error',
    'title' => $cleanName,
    'msg' => $message,
];

/* ERROR PAGE NAVIGATION: authenticated users return to their workspace; guests return home. */
$homeRoute = Yii::$app->user->isGuest ? ['/site/index'] : ['/dashboard/home'];
$homeLabel = Yii::$app->user->isGuest ? 'Back to home' : 'Go to dashboard';

$this->registerCss(<<<CSS
/* Import fonts */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* Import Tabler icons */
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --dark: #0f172a;
    --muted: #64748b;
    --border: rgba(148, 163, 184, .25);
    --card: rgba(255, 255, 255, .92);
    --bg: #eef4ff;
}

/* Main page */
.can-error-page {
    min-height: calc(100vh - 76px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(24px, 5vh, 54px) 18px;
    font-family: 'Inter', sans-serif;
    background:
        radial-gradient(circle at top left, rgba(37, 99, 235, .22), transparent 35%),
        radial-gradient(circle at bottom right, rgba(14, 165, 233, .18), transparent 35%),
        linear-gradient(135deg, #eef4ff 0%, #f8fafc 100%);
    overflow: hidden;
    position: relative;
}

/* Background grid */
.can-error-page::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(37, 99, 235, .055) 1px, transparent 1px),
        linear-gradient(90deg, rgba(37, 99, 235, .055) 1px, transparent 1px);
    background-size: 42px 42px;
}

/* Floating background shapes */
.error-shape {
    position: absolute;
    border-radius: 999px;
    filter: blur(55px);
    opacity: .55;
}

.error-shape.one {
    width: 260px;
    height: 260px;
    background: #60a5fa;
    top: 5%;
    left: 7%;
}

.error-shape.two {
    width: 300px;
    height: 300px;
    background: #38bdf8;
    right: 5%;
    bottom: 5%;
}

/* Main card */
.error-card {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 900px;
    display: grid;
    grid-template-columns: 300px 1fr;
    overflow: hidden;
    border-radius: 26px;
    background: var(--card);
    border: 1px solid rgba(255, 255, 255, .75);
    box-shadow: 0 28px 70px rgba(15, 23, 42, .16);
    backdrop-filter: blur(18px);
    animation: cardIn .55s ease both;
}

@keyframes cardIn {
    from {
        opacity: 0;
        transform: translateY(25px) scale(.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Left panel */
.error-left {
    position: relative;
    padding: 30px 26px;
    background:
        radial-gradient(circle at 85% 10%, rgba(14, 165, 233, .35), transparent 31%),
        linear-gradient(155deg, #07172d 0%, #123b77 58%, #1556c0 100%);
    color: #fff;
    overflow: hidden;
}

.error-left::before,
.error-left::after {
    content: "";
    position: absolute;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, .13);
}

.error-left::before {
    width: 350px;
    height: 350px;
    top: -150px;
    right: -160px;
}

.error-left::after {
    width: 210px;
    height: 210px;
    bottom: -90px;
    left: -90px;
}

/* Brand chip */
.brand-chip {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 8px 13px 8px 9px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .4px;
    text-transform: uppercase;
    background: rgba(255, 255, 255, .13);
    border: 1px solid rgba(255, 255, 255, .20);
}

.brand-chip img {
    width: 31px;
    height: 31px;
    object-fit: contain;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, .2));
}

/* Icon area */
.icon-zone {
    position: relative;
    z-index: 1;
    min-height: 245px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-bubble {
    width: 116px;
    height: 116px;
    border-radius: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, .13);
    border: 1px solid rgba(255, 255, 255, .22);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .22);
    animation: floatIcon 4s ease-in-out infinite;
    position: relative;
}

.icon-bubble::after {
    content: "";
    position: absolute;
    width: 184px;
    height: 78px;
    left: -34px;
    top: 18px;
    border-top: 1px dashed rgba(255, 255, 255, .34);
    border-radius: 50%;
    transform: rotate(-18deg);
}

.icon-bubble .ti {
    font-size: 54px;
    color: #fff;
}

@keyframes floatIcon {
    0%, 100% {
        transform: translateY(0) rotate(-2deg);
    }
    50% {
        transform: translateY(-13px) rotate(3deg);
    }
}

/* Bottom status box */
.status-box {
    position: relative;
    z-index: 1;
    display: flex;
    gap: 13px;
    align-items: flex-start;
    padding: 16px;
    border-radius: 18px;
    background: rgba(255, 255, 255, .12);
    border: 1px solid rgba(255, 255, 255, .18);
}

.status-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(251, 191, 36, .18);
    color: #fbbf24;
}

.status-icon .ti {
    font-size: 22px;
}

.status-box strong {
    display: block;
    font-size: 14px;
    margin-bottom: 4px;
}

.status-box span {
    display: block;
    font-size: 12.5px;
    line-height: 1.5;
    color: rgba(255, 255, 255, .68);
}

/* Right panel */
.error-right {
    padding: 42px 44px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* Small pill */
.error-pill {
    width: fit-content;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 15px;
    margin-bottom: 18px;
    border-radius: 999px;
    background: #eff6ff;
    color: var(--primary);
    border: 1px solid rgba(37, 99, 235, .18);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .45px;
}

.error-pill span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
}

/* Error code */
.error-code {
    margin-bottom: 8px;
    font-size: 78px;
    line-height: .95;
    font-weight: 800;
    letter-spacing: -6px;
    color: var(--dark);
}

/* Title and message */
.error-title {
    margin: 0 0 14px;
    color: var(--dark);
    font-size: 29px;
    font-weight: 800;
    letter-spacing: -.8px;
}

.error-message {
    max-width: 480px;
    margin: 0 0 24px;
    color: var(--muted);
    font-size: 15px;
    line-height: 1.7;
}

/* Buttons */
.error-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 26px;
}

.error-btn {
    height: 48px;
    padding: 0 22px;
    border-radius: 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none !important;
    transition: all .22s ease;
}

.error-btn-primary {
    color: #fff !important;
    background: linear-gradient(135deg, var(--primary), #0ea5e9);
    box-shadow: 0 14px 28px rgba(37, 99, 235, .28);
}

.error-btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 18px 36px rgba(37, 99, 235, .35);
}

.error-btn:focus-visible {
    outline: 3px solid rgba(14, 165, 233, .35);
    outline-offset: 3px;
}

.error-btn-secondary {
    color: #334155 !important;
    background: #f8fafc;
    border: 1px solid var(--border);
}

.error-btn-secondary:hover {
    transform: translateY(-3px);
    background: #f1f5f9;
}

/* Metadata */
.error-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    padding-top: 20px;
    border-top: 1px solid rgba(148, 163, 184, .22);
}

.error-meta span {
    padding: 7px 12px;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid rgba(148, 163, 184, .22);
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
}

/* Responsive design */
@media (max-width: 820px) {
    .error-card {
        grid-template-columns: 1fr;
        max-width: 560px;
    }

    .error-left {
        padding: 28px 24px;
    }

    .icon-zone {
        min-height: 165px;
    }

    .error-right {
        padding: 34px 26px;
    }

    .error-code {
        font-size: 68px;
        letter-spacing: -4px;
    }

    .error-title {
        font-size: 25px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .error-card,
    .icon-bubble {
        animation: none;
    }

    .error-btn {
        transition: none;
    }
}

@media (max-width: 480px) {
    .can-error-page {
        padding: 22px 12px;
    }

    .error-card {
        border-radius: 24px;
    }

    .error-code {
        font-size: 56px;
    }

    .error-title {
        font-size: 22px;
    }

    .error-actions {
        flex-direction: column;
    }

    .error-btn {
        width: 100%;
    }
}
CSS);
?>

<main class="can-error-page" aria-labelledby="error-page-title">

    <!-- Decorative background shapes -->
    <div class="error-shape one"></div>
    <div class="error-shape two"></div>

    <div class="error-card">

        <!-- Left visual panel -->
        <div class="error-left">

            <!-- Brand -->
            <div class="brand-chip">
                <img src="<?= Url::to('@web/logo/can-logo-main.png') ?>" alt="">
                Core Aviation Network
            </div>

            <!-- Main icon -->
            <div class="icon-zone">
                <div class="icon-bubble">
                    <i class="ti <?= Html::encode($e['icon']) ?>" aria-hidden="true"></i>
                </div>
            </div>

            <!-- Error status information -->
            <div class="status-box">
                <div class="status-icon">
                    <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                </div>
                <div>
                    <strong><?= Html::encode($e['footerTitle']) ?></strong>
                    <span><?= Html::encode($e['footerSub']) ?></span>
                </div>
            </div>

        </div>

        <!-- Right content panel -->
        <div class="error-right">

            <!-- Error category -->
            <div class="error-pill">
                <span></span>
                <?= Html::encode($e['pill']) ?>
            </div>

            <!-- Error code -->
            <?php if (!empty($errorCode)): ?>
                <div class="error-code"><?= Html::encode($errorCode) ?></div>
            <?php endif; ?>

            <!-- Error title -->
            <h1 class="error-title" id="error-page-title"><?= Html::encode($e['title']) ?></h1>

            <!-- Error message -->
            <p class="error-message">
                <?= nl2br(Html::encode($e['msg'])) ?>
            </p>

            <!-- Action buttons -->
            <div class="error-actions">
                <a href="<?= Url::to($homeRoute) ?>" class="error-btn error-btn-primary">
                    <i class="ti <?= Yii::$app->user->isGuest ? 'ti-home' : 'ti-layout-dashboard' ?>" aria-hidden="true"></i>
                    <?= Html::encode($homeLabel) ?>
                </a>

                <a href="javascript:history.back()" class="error-btn error-btn-secondary">
                    <i class="ti ti-arrow-left" aria-hidden="true"></i>
                    Go back
                </a>
            </div>

            <!-- Technical metadata -->
            <div class="error-meta">
                <span>Reference: <?= Html::encode($errorCode ?: 'Application error') ?></span>
                <span>Core Aviation Network</span>
                <span><?= date('Y-m-d H:i') ?></span>
            </div>

        </div>

    </div>
</main>

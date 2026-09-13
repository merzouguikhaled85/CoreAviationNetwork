<?php

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */
/** @var Throwable $exception */

use yii\helpers\Html;
use yii\helpers\Url;

$statusCode = null;
if (isset($exception) && method_exists($exception, 'getStatusCode')) {
    $statusCode = (int) $exception->getStatusCode();
}

if ($statusCode === null && preg_match('/\(#(\d{3})\)/', (string) $name, $matches)) {
    $statusCode = (int) $matches[1];
}

// Non-HTTP exceptions must never expose their internal name or message in production.
$statusCode = $statusCode ?: 500;

$errors = [
    400 => ['Bad request', 'Request not understood', 'The request could not be processed. Check the information provided and try again.', 'request'],
    401 => ['Authentication required', 'Please sign in', 'Your session may have expired. Sign in again to continue securely.', 'access'],
    403 => ['Access restricted', 'Permission denied', 'You do not have permission to access this resource. Contact your administrator if you believe this is a mistake.', 'access'],
    404 => ['We took a wrong turn', 'Page not found', 'The page you are looking for may have moved, changed its address, or no longer exists.', 'not-found'],
    405 => ['Action not available', 'Method not allowed', 'This action cannot be completed in the way it was requested. Return to the previous page and try again.', 'request'],
    408 => ['Request timeout', 'The request took too long', 'The connection timed out before the operation completed. Please try again.', 'network'],
    409 => ['Request conflict', 'A conflict occurred', 'The requested change conflicts with the current state of this resource. Refresh the page and try again.', 'request'],
    410 => ['Resource unavailable', 'This page is no longer available', 'The requested resource has been permanently removed from the platform.', 'not-found'],
    413 => ['Upload too large', 'File size limit exceeded', 'The submitted file is larger than the platform allows. Reduce its size and try again.', 'request'],
    415 => ['Unsupported format', 'File type not accepted', 'The submitted content format is not supported by the platform.', 'request'],
    422 => ['Validation error', 'We could not process the request', 'Some submitted information is invalid. Review the form and try again.', 'request'],
    429 => ['Traffic limit reached', 'Too many requests', 'Too many attempts were received in a short time. Wait a moment before trying again.', 'network'],
    500 => ['Unexpected turbulence', 'Internal server error', 'An unexpected problem occurred. Please try again or contact platform support if the problem continues.', 'server'],
    501 => ['Feature unavailable', 'Not implemented', 'This operation is not available on the platform yet.', 'server'],
    502 => ['Connection interrupted', 'Gateway temporarily unavailable', 'The platform did not receive a valid response from an upstream service. Please try again shortly.', 'server'],
    503 => ['Scheduled ground stop', 'Service temporarily unavailable', 'The platform is undergoing maintenance or is temporarily overloaded. Please try again shortly.', 'server'],
    504 => ['Connection timeout', 'Gateway timeout', 'A connected service took too long to respond. Please try again in a few moments.', 'network'],
];

$error = $errors[$statusCode] ?? [
    'Unexpected turbulence',
    'Something went wrong',
    'We could not complete your request. Please try again or contact platform support.',
    'server',
];

[$eyebrow, $title, $description, $tone] = $error;
$this->title = $statusCode . ' - ' . $title;

$isGuest = Yii::$app->user->isGuest;
$homeRoute = $isGuest ? ['/site/index'] : ['/dashboard/home'];
$homeLabel = $isGuest ? 'Back to home' : 'Go to dashboard';
$supportEmail = Yii::$app->params['publicSupportEmail'] ?? 'support@coreaviationnetwork.com';
$reference = 'HTTP-' . $statusCode;

$this->registerCss(<<<CSS
* { box-sizing: border-box; }

html,
body.can-error-layout {
    margin: 0;
    min-height: 100%;
}

body.can-error-layout {
    color: #07172f;
    background: #f8fbff;
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.can-error-page {
    min-height: 100vh;
    display: grid;
    grid-template-columns: minmax(470px, 44%) 1fr;
    overflow: hidden;
}

.can-error-content {
    position: relative;
    z-index: 2;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    padding: clamp(32px, 5vw, 88px);
    background:
        radial-gradient(circle at 12% 92%, rgba(14, 165, 233, .09), transparent 28%),
        #fff;
}

.can-error-brand {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    width: fit-content;
    color: #07172f;
    text-decoration: none;
    font-weight: 800;
    font-size: 18px;
    letter-spacing: -.02em;
}

.can-error-brand img {
    width: 128px;
    height: 96px;
    object-fit: contain;
    margin: -15px -18px -15px -20px;
}

.can-error-copy {
    width: 100%;
    max-width: 600px;
    margin: auto;
    padding: 72px 0 36px;
    text-align: center;
}

.can-error-code {
    margin: 0;
    color: #ef4f5f;
    font-size: clamp(86px, 10vw, 146px);
    font-weight: 900;
    line-height: .82;
    letter-spacing: -.075em;
}

.can-error-eyebrow {
    margin: 32px 0 12px;
    color: #1676d2;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .18em;
    text-transform: uppercase;
}

.can-error-title {
    margin: 0;
    color: #07172f;
    font-size: clamp(32px, 3.2vw, 50px);
    line-height: 1.08;
    letter-spacing: -.045em;
}

.can-error-description {
    max-width: 530px;
    margin: 20px auto 0;
    color: #64748b;
    font-size: 16px;
    line-height: 1.75;
}

.can-error-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 12px;
    margin-top: 32px;
}

.can-error-button {
    min-height: 52px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 0 22px;
    border: 1px solid #dbe5f1;
    border-radius: 13px;
    color: #17345f;
    background: #fff;
    text-decoration: none;
    font-size: 14px;
    font-weight: 800;
    box-shadow: 0 8px 24px rgba(15, 47, 87, .06);
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

.can-error-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(15, 47, 87, .12);
}

.can-error-button:focus-visible {
    outline: 3px solid rgba(14, 165, 233, .32);
    outline-offset: 3px;
}

.can-error-button--primary {
    border-color: transparent;
    color: #fff;
    background: linear-gradient(135deg, #1558c8, #08a9df);
    box-shadow: 0 13px 28px rgba(21, 88, 200, .24);
}

.can-error-button svg {
    width: 17px;
    height: 17px;
    stroke: currentColor;
}

.can-error-help {
    margin: 30px 0 0;
    color: #8190a5;
    font-size: 13px;
}

.can-error-help a {
    color: #1558c8;
    font-weight: 800;
}

.can-error-reference {
    margin-top: auto;
    color: #94a3b8;
    font-size: 11px;
    letter-spacing: .06em;
    text-align: center;
    text-transform: uppercase;
}

.can-error-visual {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background:
        linear-gradient(145deg, rgba(7, 23, 47, .08), rgba(21, 88, 200, .18)),
        #dbeafe;
}

.can-error-visual > img {
    width: 100%;
    height: 100%;
    position: absolute;
    inset: 0;
    object-fit: cover;
    object-position: center;
}

.can-error-visual::after {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(90deg, rgba(255,255,255,.10), transparent 32%);
}

@media (max-width: 920px) {
    .can-error-page { grid-template-columns: 1fr; }
    .can-error-content { min-height: 62vh; padding: 28px 24px 42px; }
    .can-error-copy { padding: 64px 0 28px; }
    .can-error-visual { min-height: 38vh; }
}

@media (max-width: 520px) {
    .can-error-brand { font-size: 16px; }
    .can-error-brand img { width: 108px; height: 82px; margin: -12px -15px -12px -17px; }
    .can-error-copy { padding-top: 54px; }
    .can-error-actions { flex-direction: column; }
    .can-error-button { width: 100%; }
}

@media (prefers-reduced-motion: reduce) {
    .can-error-button { transition: none; }
}
CSS);
?>

<main class="can-error-page can-error-page--<?= Html::encode($tone) ?>" aria-labelledby="can-error-title">
    <section class="can-error-content">
        <a class="can-error-brand" href="<?= Url::to(['/site/index']) ?>" aria-label="Core Aviation Network home">
            <img src="<?= Url::to('@web/logo/can-logo-main.png') ?>" alt="">
            <span>Core Aviation Network</span>
        </a>

        <div class="can-error-copy">
            <p class="can-error-code" aria-label="Error <?= Html::encode((string) $statusCode) ?>">
                <?= Html::encode((string) $statusCode) ?>
            </p>
            <p class="can-error-eyebrow"><?= Html::encode($eyebrow) ?></p>
            <h1 class="can-error-title" id="can-error-title"><?= Html::encode($title) ?></h1>
            <p class="can-error-description"><?= Html::encode($description) ?></p>

            <div class="can-error-actions">
                <a class="can-error-button can-error-button--primary" href="<?= Url::to($homeRoute) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5M9.5 20v-6h5v6"/></svg>
                    <?= Html::encode($homeLabel) ?>
                </a>
                <a class="can-error-button" href="mailto:<?= Html::encode($supportEmail) ?>?subject=Platform%20error%20<?= Html::encode((string) $statusCode) ?>%20-%20<?= Html::encode($reference) ?>">
                    Contact support
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5"/></svg>
                </a>
            </div>

            <p class="can-error-help">
                Need help? Email us at
                <a href="mailto:<?= Html::encode($supportEmail) ?>"><?= Html::encode($supportEmail) ?></a>
            </p>
        </div>

        <div class="can-error-reference">
            Error <?= Html::encode((string) $statusCode) ?> · Reference <?= Html::encode($reference) ?>
        </div>
    </section>

    <aside class="can-error-visual" aria-label="Core Aviation Network aircraft maintenance">
        <img src="<?= Url::to('@web/img/home-carousel/mro-team.jpg') ?>" alt="Aircraft maintenance team at work">
    </aside>
</main>

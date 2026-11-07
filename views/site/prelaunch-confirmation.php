<?php
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var string $state */
/** @var string $token */
$states = [
    'success' => ['success', 'Your email is verified!', 'Thank you for joining Core Aviation Network. We’ll notify you when the platform launches.'],
    'already-confirmed' => ['success', 'Your email is already verified.', 'You’re on the list! We’ll notify you when Core Aviation Network launches.'],
    'expired' => ['warning', 'This link has expired', 'Confirmation links are valid for 24 hours. Request a new link to complete your early access registration.'],
    'invalid' => ['warning', 'This link is invalid', 'We couldn’t verify this confirmation link. Please use the most recent confirmation email you received.'],
    'resent' => ['email', 'Check your inbox', 'We’ve sent you a new confirmation email. Open the latest link to confirm your registration. Please check your spam folder too.'],
    'delivery-failed' => ['warning', 'We couldn’t send the email', 'Please wait at least 60 seconds before trying again. Your early access registration is still saved.'],
    'cooldown' => ['warning', 'Please wait a moment', 'Please allow at least 60 seconds between email requests, then try again.'],
    'rate-limited' => ['warning', 'Please try again later', 'You’ve requested several emails recently. Please wait 10 minutes before requesting another confirmation link.'],
];
[$tone, $title, $message] = $states[$state] ?? $states['invalid'];
$this->title = $title . ' | Core Aviation Network';
$canResend = in_array($state, ['expired', 'delivery-failed', 'cooldown', 'rate-limited'], true) && $token !== '';
?>
<main class="confirmation-page">
    <a class="confirmation-brand" href="<?= Html::encode(\yii\helpers\Url::to(['/site/index'])) ?>" aria-label="Core Aviation Network homepage">
        <?= Html::img('@web/logo/can-logo-main.png', ['alt' => 'Core Aviation Network', 'class' => 'confirmation-logo']) ?>
        <span>CORE AVIATION NETWORK</span>
    </a>
    <section class="confirmation-card is-<?= Html::encode($tone) ?>" aria-labelledby="confirmation-title">
        <div class="confirmation-icon" aria-hidden="true">
            <?php if ($tone === 'success'): ?>
                <svg viewBox="0 0 32 32" fill="none"><path d="m8 16 5.5 5.5L25 10" /></svg>
            <?php elseif ($tone === 'email'): ?>
                <svg viewBox="0 0 32 32" fill="none"><rect x="5" y="8" width="22" height="16" rx="3"/><path d="m6 10 10 8 10-8"/></svg>
            <?php else: ?>
                <svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="11"/><path d="M16 10v7m0 5v.1"/></svg>
            <?php endif; ?>
        </div>
        <p class="confirmation-kicker">EARLY ACCESS</p>
        <h1 id="confirmation-title"><?= Html::encode($title) ?></h1>
        <p class="confirmation-message"><?= Html::encode($message) ?></p>
        <div class="confirmation-actions">
            <?php if ($canResend): ?>
                <?= Html::beginForm(['/site/resend-prelaunch'], 'post') ?>
                    <?= Html::hiddenInput('token', $token) ?>
                    <?= Html::submitButton('Send me a new confirmation link', ['class' => 'confirmation-button']) ?>
                <?= Html::endForm() ?>
            <?php endif; ?>
            <?= Html::a('Back to homepage <span aria-hidden="true">→</span>', ['/site/index'], ['class' => $canResend ? 'confirmation-home-link' : 'confirmation-button']) ?>
        </div>
        <?php if (in_array($state, ['success', 'already-confirmed'], true)): ?>
            <div class="confirmation-note">You’re all set. No further action is needed.</div>
        <?php endif; ?>
    </section>
    <p class="confirmation-footer">Connecting aviation professionals worldwide.</p>
</main>

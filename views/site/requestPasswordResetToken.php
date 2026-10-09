<?php
/** @var yii\web\View $this */
/** @var app\models\PasswordResetRequestForm $model */
/** @var bool $captchaRequired */
/** @var bool $captchaError */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;
use yii\web\View;

$this->title = 'Request password reset';
$captchaRequired = $captchaRequired ?? false;
$captchaError = $captchaError ?? false;
$turnstileSiteKey = trim((string) (Yii::$app->params['turnstileSiteKey'] ?? ''));
$turnstileHostname = trim((string) (Yii::$app->params['turnstileExpectedHostname'] ?? ''));
$turnstileConfigured = $turnstileSiteKey !== '' && $turnstileHostname !== '';
if ($captchaRequired && $turnstileConfigured) {
    $this->registerJsFile('https://challenges.cloudflare.com/turnstile/v0/api.js', [
        'async' => true,
        'defer' => true,
    ]);
}
$flashMap = [
    'success' => ['class' => 'alert-success', 'icon' => 'ri-check-line'],
    'message' => ['class' => 'alert-success', 'icon' => 'ri-check-line'],
    'error' => ['class' => 'alert-danger', 'icon' => 'ri-error-warning-line'],
    'warning' => ['class' => 'alert-warning', 'icon' => 'ri-alert-line'],
    'info' => ['class' => 'alert-info', 'icon' => 'ri-information-line'],
];
?>

<section class="hero-login password-recovery-page">
    <div class="auth-wrap">
        <div class="auth-card" role="region" aria-label="Request password reset">

            <div class="brand">
                <img
                    src="<?= Yii::getAlias('@web/logo/can-logo-main.png') ?>"
                    alt="CAN — Core Aviation Network"
                    class="img-fluid"
                />

                <p class="auth-eyebrow">Account recovery</p>
                <h1 class="title">Reset your password</h1>
                <p class="auth-subtitle">Enter your email and we’ll send you a secure reset link.</p>
            </div>

            <?php foreach ($flashMap as $key => $cfg): ?>
                <?php if (Yii::$app->session->hasFlash($key)): ?>
                    <div class="alert <?= $cfg['class'] ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
                        <i class="<?= $cfg['icon'] ?>" aria-hidden="true"></i>
                        <span><?= Html::encode(Yii::$app->session->getFlash($key)) ?></span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php $form = ActiveForm::begin([
                'id' => 'request-password-reset-form',
                'options' => ['novalidate' => true],
                'fieldConfig' => [
                    'errorOptions' => ['class' => 'invalid-feedback d-block small'],
                ],
            ]); ?>

            <div class="mb-3">
                <?= $form->field($model, 'email')
                    ->label('<i class="ri-mail-line"></i> Email', ['class' => 'form-label'])
                    ->input('email', [
                        'class' => 'form-control',
                        'autocomplete' => 'email',
                        'id' => 'rp-email',
                        'required' => true,
                    ]); ?>
            </div>

            <?php if ($captchaRequired): ?>
                <div class="login-challenge"<?= $captchaError ? ' data-recovery-captcha-error' : '' ?>>
                    <?php if ($turnstileConfigured): ?>
                        <div
                            class="cf-turnstile"
                            data-sitekey="<?= Html::encode($turnstileSiteKey) ?>"
                            data-action="password_recovery"
                            data-theme="light"
                            data-language="en"
                            data-callback="onPasswordRecoveryChallenge"
                        ></div>
                    <?php else: ?>
                        <p class="login-challenge-unavailable" role="alert">
                            Visitor verification is temporarily unavailable. Please contact support.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="form-group mb-3">
                <?= Html::submitButton(
                    '<i class="ri-mail-send-line btn-icon" aria-hidden="true"></i>'
                        . '<span class="btn-spinner" aria-hidden="true"></span>'
                        . '<span class="btn-text">Send reset link</span>',
                    [
                        'class' => 'btn btn-primary w-100',
                        'id' => 'submitBtn',
                        'disabled' => true,
                        'data-loading-text' => 'Sending…',
                    ]
                ) ?>
            </div>

            <nav class="auth-recovery-links recovery-navigation mt-2" aria-label="Account recovery navigation">
                <a class="link" href="<?= Url::to(['site/login']) ?>">
                    <i class="ri-arrow-left-line" aria-hidden="true"></i>
                    Back to sign in
                </a>

                <a class="link" href="<?= Url::to(['site/request-username-reset']) ?>">
                    <i class="ri-user-search-line" aria-hidden="true"></i>
                    Forgot username?
                </a>
            </nav>

            <footer class="login-footer mt-3">
                © <?= date('Y') ?>
                <strong><?= Html::encode(Yii::$app->name) ?></strong> — All rights reserved
            </footer>

            <?php ActiveForm::end(); ?>

        </div>
    </div>
</section>

<?php
$captchaRequiredJs = $captchaRequired ? 'true' : 'false';
$turnstileConfiguredJs = $turnstileConfigured ? 'true' : 'false';
$js = <<<JS
(function(){
    const form = document.getElementById('request-password-reset-form');
    const email = document.getElementById('rp-email');
    const submitBtn = document.getElementById('submitBtn');
    const challengeRequired = {$captchaRequiredJs};
    const challengeConfigured = {$turnstileConfiguredJs};
    let challengeComplete = !challengeRequired;

    if (!form || !email || !submitBtn) return;

    function isValidEmail(value){
        return /^\\S+@\\S+\\.\\S+$/.test(value.trim());
    }

    function checkForm(){
        submitBtn.disabled = !isValidEmail(email.value)
            || (challengeRequired && (!challengeConfigured || !challengeComplete));
    }

    window.onPasswordRecoveryChallenge = function(){
        challengeComplete = true;
        const challengeError = document.querySelector('[data-recovery-captcha-error]');
        if (challengeError) challengeError.removeAttribute('data-recovery-captcha-error');
        email.classList.remove('is-invalid');
        email.removeAttribute('aria-invalid');
        checkForm();
    };

    checkForm();

    email.addEventListener('input', checkForm);
    email.addEventListener('blur', checkForm);

    const startSubmitting = function(){
        submitBtn.disabled = true;
        submitBtn.classList.add('is-loading');
        submitBtn.setAttribute('aria-busy', 'true');
        const text = submitBtn.querySelector('.btn-text');
        if (text) text.textContent = submitBtn.dataset.loadingText;
    };

    if (window.jQuery && window.jQuery.fn.yiiActiveForm) {
        window.jQuery(form).on('beforeSubmit.passwordRecovery', function(){
            startSubmitting();
            return true;
        });
    } else {
        form.addEventListener('submit', startSubmitting);
    }
})();
JS;

$this->registerJs($js, View::POS_END);
?>

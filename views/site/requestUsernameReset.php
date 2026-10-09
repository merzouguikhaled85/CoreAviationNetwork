<?php

/** @var yii\web\View $this */
/** @var app\models\UsernameRequestForm $model */
/** @var bool $captchaRequired */
/** @var bool $captchaError */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->title = 'Request username';
$this->params['breadcrumbs'][] = $this->title;
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

?>
<?php
$flashMap = [
    'success'       => ['class' => 'alert-success', 'icon' => 'ri-check-line'],
    'message'       => ['class' => 'alert-success', 'icon' => 'ri-check-line'],
    'error'         => ['class' => 'alert-danger',  'icon' => 'ri-error-warning-line'],
    'usernameError' => ['class' => 'alert-danger',  'icon' => 'ri-error-warning-line'],
    'passwordError' => ['class' => 'alert-danger',  'icon' => 'ri-error-warning-line'],
    'warning'       => ['class' => 'alert-warning', 'icon' => 'ri-alert-line'],
    'info'          => ['class' => 'alert-info',    'icon' => 'ri-information-line'],
];
?>
<section class="hero-login username-recovery-page">
  <div class="auth-wrap">
    <div class="auth-card" role="region" aria-label="Request username">
      <div class="brand">
        <img
          src="<?= Yii::getAlias('@web/logo/can-logo-main.png') ?>"
          alt="CAN — Core Aviation Network"
          class="img-fluid"
        />
        <p class="auth-eyebrow">Account recovery</p>
        <h1 class="title">Find your username</h1>
        <p class="auth-subtitle">Enter your email and account type to receive your username.</p>
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
          'id' => 'request-username-form',
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
                'required' => true,
                'id' => 'ru-email',
            ]) ?>
      </div>

      <div class="mb-3">
        <?= $form->field($model, 'usertype')
            ->label('<i class="ri-user-settings-line"></i> User type', ['class' => 'form-label'])
            ->dropDownList([ 'mro' => 'MRO', 'ao' => 'AO', ],
                [
                    'class'   => 'form-select',
                    'required'=> true,
                    'id'      => 'ru-type',
                    'prompt'  => 'Select user type',
                ]
            ) ?>
      </div>

      <?php if ($captchaRequired): ?>
        <div class="login-challenge"<?= $captchaError ? ' data-recovery-captcha-error' : '' ?>>
          <?php if ($turnstileConfigured): ?>
            <div
              class="cf-turnstile"
              data-sitekey="<?= Html::encode($turnstileSiteKey) ?>"
              data-action="username_recovery"
              data-theme="light"
              data-language="en"
              data-callback="onUsernameRecoveryChallenge"
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
                . '<span class="btn-text">Send my username</span>',
            [
                'class' => 'btn btn-primary w-100',
                'id'    => 'submitBtn',
                'disabled' => true,
                'data-loading-text' => 'Sending…',
            ]
        ) ?>
      </div>

      <nav class="auth-recovery-links recovery-navigation mt-2" aria-label="Account recovery navigation">
        <a class="link" href="<?= Url::to(['site/login']) ?>">
          <i class="ri-arrow-left-line" aria-hidden="true"></i> Back to sign in
        </a>
        <a class="link" href="<?= Url::to(['site/request-password-reset']) ?>">
          <i class="ri-key-2-line" aria-hidden="true"></i> Forgot password?
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

  const form     = document.getElementById('request-username-form');
  if (!form) return;

  const email     = document.getElementById('ru-email');
  const userType  = document.getElementById('ru-type');
  const submitBtn = document.getElementById('submitBtn');
  const challengeRequired = {$captchaRequiredJs};
  const challengeConfigured = {$turnstileConfiguredJs};
  let challengeComplete = !challengeRequired;

  function isValidEmail(value){
    return /^\\S+@\\S+\\.\\S+\$/.test(value.trim());
  }

  function checkForm(){
    const emailValid = isValidEmail(email.value);
    const typeValid  = userType.value.trim() !== '';
    submitBtn.disabled = !(emailValid && typeValid)
      || (challengeRequired && (!challengeConfigured || !challengeComplete));
  }

  window.onUsernameRecoveryChallenge = function(){
    challengeComplete = true;
    const challengeError = document.querySelector('[data-recovery-captcha-error]');
    if (challengeError) challengeError.removeAttribute('data-recovery-captcha-error');
    email.classList.remove('is-invalid');
    email.removeAttribute('aria-invalid');
    checkForm();
  };

  // Vérification initiale
  checkForm();

  // Events
  email.addEventListener('input', checkForm);
  email.addEventListener('blur', checkForm);
  userType.addEventListener('change', checkForm);

  const startSubmitting = function(){
    submitBtn.disabled = true;
    submitBtn.classList.add('is-loading');
    submitBtn.setAttribute('aria-busy', 'true');
    const text = submitBtn.querySelector('.btn-text');
    if (text) text.textContent = submitBtn.dataset.loadingText;
  };

  if (window.jQuery && window.jQuery.fn.yiiActiveForm) {
    window.jQuery(form).on('beforeSubmit.usernameRecovery', function(){
      startSubmitting();
      return true;
    });
  } else {
    form.addEventListener('submit', startSubmitting);
  }

})();
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>

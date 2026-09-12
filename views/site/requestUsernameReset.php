<?php

/** @var yii\web\View $this */
/** @var app\models\UsernameRequestForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->title = 'Request username';
$this->params['breadcrumbs'][] = $this->title;

// Icônes
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css',
    ['rel' => 'stylesheet', 'position' => \yii\web\View::POS_HEAD]
);

$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css',
    ['rel' => 'stylesheet', 'position' => \yii\web\View::POS_HEAD]
);

// CSS login
//$this->registerCssFile('@web/css/login.css');
?>
<!-- ================== FLASH MESSAGES ================== -->
<?php
// Mapping type Flash → classe Bootstrap + icône
$flashMap = [
    'success'       => ['class' => 'alert-success', 'icon' => 'ri-check-line'],
    'message'       => ['class' => 'alert-success', 'icon' => 'ri-check-line'],
    'error'         => ['class' => 'alert-danger',  'icon' => 'ri-error-warning-line'],
    'usernameError' => ['class' => 'alert-danger',  'icon' => 'ri-error-warning-line'],
    'passwordError' => ['class' => 'alert-danger',  'icon' => 'ri-error-warning-line'],
    'warning'       => ['class' => 'alert-warning', 'icon' => 'ri-alert-line'],
    'info'          => ['class' => 'alert-info',    'icon' => 'ri-information-line'],
];

foreach ($flashMap as $key => $cfg):
    if (Yii::$app->session->hasFlash($key)):
?>
    <div class="alert <?= $cfg['class'] ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="<?= $cfg['icon'] ?>"></i>
        <span><?= Yii::$app->session->getFlash($key) ?></span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php
    endif;
endforeach;
?>
<!-- ================== END FLASH MESSAGES ================== -->
<section class="hero-login">
  <div class="auth-wrap">
    <div class="auth-card" role="region" aria-label="Request username">
      <div class="brand">
        <img
          src="<?= Yii::getAlias('@web/logo/can-logo-main.png') ?>"
          alt="CAN — Core Aviation Network"
          class="img-fluid"
          style="max-height:120px"
        />
        <h1 class="title">
          <i class="ri-lock-line"></i>
          <?= Html::encode(Yii::$app->name) ?>
        </h1>
        <p class="mt-2 mb-0 small">
          Enter your email and user type, we’ll send you your login username.
        </p>
      </div>

      <?php $form = ActiveForm::begin([
          'id' => 'request-username-form',
          'options' => ['novalidate' => true],
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
        <?= $form->field($model, 'usertype') // ⚠ vérifier que la propriété s’appelle bien userType dans le modèle
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


      <div class="form-group mb-3">
        <?= Html::submitButton(
            '<i class="ri-mail-check-line"></i> <span class="btn-text">Send</span>',
            [
                'class' => 'btn btn-primary w-100',
                'id'    => 'submitBtn',
                'disabled' => true, // Désactivé au départ
            ]
        ) ?>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-2">
        <a class="link" href="<?= Url::to(['site/login']) ?>">⬅ Back to sign in</a>
        <a class="link" href="<?= Url::to(['site/request-password-reset']) ?>">
          Forgot password?
        </a>
      </div>

      <footer class="login-footer mt-3">
        © <?= date('Y') ?>
        <strong><?= Html::encode(Yii::$app->name) ?></strong> — All rights reserved
      </footer>

      <?php ActiveForm::end(); ?>
    </div>
  </div>
</section>

<?php
$js = <<<JS
(function(){

  const form     = document.getElementById('request-username-form');
  if (!form) return;

  const email     = document.getElementById('ru-email');
  const userType  = document.getElementById('ru-type');
  const submitBtn = document.getElementById('submitBtn');

  function isValidEmail(value){
    return /^\\S+@\\S+\\.\\S+\$/.test(value.trim());
  }

  function checkForm(){
    const emailValid = isValidEmail(email.value);
    const typeValid  = userType.value.trim() !== '';
    submitBtn.disabled = !(emailValid && typeValid);
  }

  // Vérification initiale
  checkForm();

  // Events
  email.addEventListener('input', checkForm);
  email.addEventListener('blur', checkForm);
  userType.addEventListener('change', checkForm);

})();
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>

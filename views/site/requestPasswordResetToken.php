<?php
/** @var yii\web\View $this */
/** @var app\models\PasswordResetRequestForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;
use yii\web\View;

$this->title = 'Request password reset';

// RemixIcon
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css',
    ['rel' => 'stylesheet', 'position' => View::POS_HEAD]
);

// Optional Lucide icons
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css',
    ['rel' => 'stylesheet', 'position' => View::POS_HEAD]
);

// If your login CSS is not loaded globally, uncomment this line.
// $this->registerCssFile('@web/css/login.css');
?>

<section class="hero-login">
    <div class="auth-wrap">
        <div class="auth-card" role="region" aria-label="Request password reset">

            <div class="brand">
                <img
                    src="<?= Yii::getAlias('@web/logo/can-logo-main.png') ?>"
                    alt="CAN — Core Aviation Network"
                    class="img-fluid"
                    style="max-height:120px"
                />

                <h1 class="title">
                    <i class="ri-lock-2-line"></i>
                    <?= Html::encode($this->title) ?>
                </h1>

                <p class="mt-2 mb-0 small">
                    Please enter your email address. We’ll send you a link to reset your password.
                </p>
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'request-password-reset-form',
                'options' => ['novalidate' => true],
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

            <div class="form-group mb-3">
                <?= Html::submitButton(
                    '<i class="ri-send-plane-line"></i> <span class="btn-text">Send reset link</span>',
                    [
                        'class' => 'btn btn-primary w-100',
                        'id' => 'submitBtn',
                        'disabled' => true,
                    ]
                ) ?>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2">
                <a class="link" href="<?= Url::to(['site/login']) ?>">
                    <i class="ri-arrow-left-line"></i>
                    Back to sign in
                </a>

                <a class="link" href="<?= Url::to(['site/request-username-reset']) ?>">
                    Need your username?
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
    const form = document.getElementById('request-password-reset-form');
    const email = document.getElementById('rp-email');
    const submitBtn = document.getElementById('submitBtn');

    if (!form || !email || !submitBtn) return;

    function isValidEmail(value){
        return /^\\S+@\\S+\\.\\S+$/.test(value.trim());
    }

    function checkForm(){
        submitBtn.disabled = !isValidEmail(email.value);
    }

    checkForm();

    email.addEventListener('input', checkForm);
    email.addEventListener('blur', checkForm);
})();
JS;

$this->registerJs($js, View::POS_END);
?>
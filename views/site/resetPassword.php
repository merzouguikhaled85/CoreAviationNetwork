<?php
/** @var yii\web\View $this */
/** @var app\models\ResetPasswordForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;
use yii\web\View;

$this->title = 'Reset Password';

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

/**
 * Extra CSS for password visibility buttons.
 */
$this->registerCss(<<<CSS
.password-field-wrap {
    position: relative;
}

.password-field-wrap .form-control {
    padding-right: 48px;
}

.password-toggle-btn {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: #64748b;
    font-size: 20px;
    line-height: 1;
    padding: 0;
    cursor: pointer;
    z-index: 5;
}

.password-toggle-btn:hover {
    color: #0d6efd;
}

.password-toggle-btn:focus {
    outline: none;
    color: #0d6efd;
}
CSS);
?>

<section class="hero-login">
    <div class="auth-wrap">
        <div class="auth-card" role="region" aria-label="Reset password">

            <div class="brand">
                <img
                    src="<?= Yii::getAlias('@web/logo/can-logo-main.png') ?>"
                    alt="CAN — Core Aviation Network"
                    class="img-fluid"
                    style="max-height:120px"
                />

                <h1 class="title">
                    <i class="ri-lock-password-line"></i>
                    <?= Html::encode($this->title) ?>
                </h1>

                <p class="mt-2 mb-0 small">
                    Please enter and confirm your new password.
                </p>
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'reset-password-form',
                'options' => ['novalidate' => true],
            ]); ?>

            <div class="mb-3">
                <?= $form->field($model, 'password', [
                    'template' => "{label}\n<div class=\"password-field-wrap\">{input}
                        <button type=\"button\" class=\"password-toggle-btn\" data-target=\"rp-password\" aria-label=\"Show password\">
                            <i class=\"ri-eye-line\"></i>
                        </button>
                    </div>\n{error}",
                ])
                    ->label('<i class="ri-lock-2-line"></i> New password', ['class' => 'form-label'])
                    ->passwordInput([
                        'class' => 'form-control',
                        'autocomplete' => 'new-password',
                        'id' => 'rp-password',
                        'required' => true,
                    ]); ?>
            </div>

            <div class="mb-3">
                <?= $form->field($model, 'confirm_password', [
                    'template' => "{label}\n<div class=\"password-field-wrap\">{input}
                        <button type=\"button\" class=\"password-toggle-btn\" data-target=\"rp-confirm-password\" aria-label=\"Show confirm password\">
                            <i class=\"ri-eye-line\"></i>
                        </button>
                    </div>\n{error}",
                ])
                    ->label('<i class="ri-lock-unlock-line"></i> Confirm password', ['class' => 'form-label'])
                    ->passwordInput([
                        'class' => 'form-control',
                        'autocomplete' => 'new-password',
                        'id' => 'rp-confirm-password',
                        'required' => true,
                    ]); ?>
            </div>

            <div id="passwordMatchMessage" class="small mb-3 d-none"></div>

            <div class="form-group mb-3">
                <?= Html::submitButton(
                    '<i class="ri-save-3-line"></i> <span class="btn-text">Save new password</span>',
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

                <a class="link" href="<?= Url::to(['site/request-password-reset']) ?>">
                    <i class="ri-mail-send-line"></i>
                    Request new link
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
    const form = document.getElementById('reset-password-form');
    const password = document.getElementById('rp-password');
    const confirmPassword = document.getElementById('rp-confirm-password');
    const submitBtn = document.getElementById('submitBtn');
    const message = document.getElementById('passwordMatchMessage');

    if (!form || !password || !confirmPassword || !submitBtn || !message) return;

    function checkForm(){
        const passValue = password.value.trim();
        const confirmValue = confirmPassword.value.trim();

        const hasPassword = passValue.length > 0;
        const hasConfirm = confirmValue.length > 0;
        const passwordsMatch = passValue === confirmValue;

        if (!hasPassword && !hasConfirm) {
            message.classList.add('d-none');
            submitBtn.disabled = true;
            return;
        }

        message.classList.remove('d-none');

        if (hasPassword && hasConfirm && passwordsMatch) {
            message.className = 'small mb-3 text-success';
            message.innerHTML = '<i class="ri-check-line"></i> Passwords match.';
            submitBtn.disabled = false;
        } else {
            message.className = 'small mb-3 text-danger';
            message.innerHTML = '<i class="ri-error-warning-line"></i> Passwords do not match.';
            submitBtn.disabled = true;
        }
    }

    function initPasswordToggle(){
        const toggleButtons = document.querySelectorAll('.password-toggle-btn');

        toggleButtons.forEach(function(button){
            button.addEventListener('click', function(){
                const targetId = button.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = button.querySelector('i');

                if (!input || !icon) return;

                const isHidden = input.getAttribute('type') === 'password';

                input.setAttribute('type', isHidden ? 'text' : 'password');

                icon.classList.toggle('ri-eye-line', !isHidden);
                icon.classList.toggle('ri-eye-off-line', isHidden);

                button.setAttribute(
                    'aria-label',
                    isHidden ? 'Hide password' : 'Show password'
                );
            });
        });
    }

    checkForm();

    password.addEventListener('input', checkForm);
    confirmPassword.addEventListener('input', checkForm);

    initPasswordToggle();
})();
JS;

$this->registerJs($js, View::POS_END);
?>
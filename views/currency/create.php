<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'Create Currency';

/* Bootstrap Icons */
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

/* SweetAlert2 */
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

$this->registerCss("
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .currency-create-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .currency-create-page .container-fluid {
        max-width: 100%;
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
        font-weight: 800;
        color: #1f2937;
    }

    .subtitle-text {
        color: #6b7280;
        margin-top: 6px;
        font-size: 14px;
    }

    .form-center-wrapper {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    .content-card {
        width: 100%;
        max-width: 760px;
        background: #ffffff;
        border-radius: 18px;
        padding: 26px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5eaf3;
        margin: 0 auto;
    }

    .form-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #eef2f7;
    }

    .form-section-title i {
        color: #2563eb;
        font-size: 18px;
    }

    .form-row-center {
        display: flex;
        justify-content: center;
    }

    .form-column {
        width: 100%;
        max-width: 620px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-label,
    .control-label {
        font-size: 13px;
        font-weight: 800;
        color: #334155;
        margin-bottom: 7px;
    }

    .form-control {
        height: 44px;
        border: 1px solid #dbeafe;
        border-radius: 11px;
        padding: 9px 13px;
        font-size: 14px;
        font-weight: 600;
        color: #334155;
        background-color: #ffffff;
        box-shadow: none;
        transition: 0.2s ease;
    }

    .form-control:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12);
    }

    .form-control::placeholder {
        color: #94a3b8;
        font-weight: 500;
    }

    .required-star {
        color: #ef4444;
        margin-left: 3px;
    }

    .field-message {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        margin-top: 6px;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.35;
    }

    .field-message i {
        color: #2563eb;
        font-size: 13px;
        margin-top: 1px;
    }

    .field-error-message {
        display: none;
        align-items: flex-start;
        gap: 6px;
        margin-top: 6px;
        color: #dc2626;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.35;
    }

    .field-error-message i {
        color: #dc2626;
        font-size: 13px;
        margin-top: 1px;
    }

    .input-error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.10) !important;
    }

    .help-block,
    .invalid-feedback {
        color: #dc2626;
        font-size: 12px;
        font-weight: 600;
        margin-top: 6px;
    }

    .form-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 10px;
        padding-top: 20px;
        border-top: 1px solid #eef2f7;
    }

    .btn-submit-currency,
    .btn-back-currency {
        height: 42px;
        border-radius: 10px;
        padding: 0 18px;
        font-size: 13px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .btn-submit-currency {
        background: #2563eb;
        color: #ffffff !important;
        border: 1px solid #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
    }

    .btn-submit-currency:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .btn-back-currency {
        background: #f8fafc;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }

    .btn-back-currency:hover {
        background: #e2e8f0;
        color: #0f172a !important;
        transform: translateY(-1px);
    }

    .alert {
        border-radius: 14px;
        border: none;
        padding: 14px 18px;
        font-weight: 600;
        box-shadow: 0 5px 16px rgba(15, 23, 42, .08);
        margin-bottom: 18px;
    }

    /* Compact SweetAlert */
    .swal2-popup.custom-submit-popup {
        width: 340px !important;
        max-width: 90vw !important;
        border-radius: 15px !important;
        padding: 14px 16px 15px !important;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.22) !important;
    }

    .swal2-popup.custom-submit-popup .swal2-icon {
        width: 48px !important;
        height: 48px !important;
        margin: 6px auto 10px !important;
    }

    .swal2-popup.custom-submit-popup .swal2-icon .swal2-icon-content {
        font-size: 28px !important;
    }

    .swal2-title.custom-submit-title {
        color: #0f172a !important;
        font-size: 18px !important;
        font-weight: 800 !important;
        padding: 0 !important;
        margin: 0 0 6px !important;
    }

    .swal2-html-container.custom-submit-message {
        color: #64748b !important;
        font-size: 12.5px !important;
        line-height: 1.4 !important;
        margin: 0 8px 12px !important;
    }

    .swal2-actions {
        margin-top: 12px !important;
        gap: 14px !important;
    }

    .swal-submit-confirm,
    .swal-submit-cancel {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        border-radius: 8px !important;
        padding: 8px 12px !important;
        font-size: 12.5px !important;
        font-weight: 800 !important;
        border: none !important;
        min-width: 98px !important;
        height: 36px !important;
        transition: 0.2s ease !important;
    }

    .swal-submit-confirm {
        background: #2563eb !important;
        color: #ffffff !important;
    }

    .swal-submit-confirm:hover {
        background: #1d4ed8 !important;
        transform: translateY(-1px);
    }

    .swal-submit-cancel {
        background: #e2e8f0 !important;
        color: #334155 !important;
    }

    .swal-submit-cancel:hover {
        background: #cbd5e1 !important;
        color: #0f172a !important;
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .currency-create-page {
            padding: 14px;
        }

        .page-header-card {
            padding: 18px;
            border-radius: 14px;
        }

        .dash-title {
            font-size: 23px;
        }

        .content-card {
            max-width: 100%;
            padding: 18px;
            border-radius: 14px;
        }

        .form-column {
            max-width: 100%;
        }

        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-submit-currency,
        .btn-back-currency {
            width: 100%;
        }

        .swal2-popup.custom-submit-popup {
            width: 315px !important;
            padding: 13px 14px 14px !important;
        }

        .swal2-actions {
            gap: 10px !important;
        }

        .swal-submit-confirm,
        .swal-submit-cancel {
            min-width: 92px !important;
            height: 35px !important;
            font-size: 12px !important;
        }
    }
");

$this->registerJs(<<<JS
$(document).ready(function () {

    // Show field error message.
    function showFieldError(fieldSelector, errorSelector, message) {
        $(fieldSelector).addClass('input-error');
        $(errorSelector).html('<i class=\"bi bi-exclamation-circle\"></i><span>' + message + '</span>').css('display', 'flex');
    }

    // Clear field error message.
    function clearFieldError(fieldSelector, errorSelector) {
        $(fieldSelector).removeClass('input-error');
        $(errorSelector).hide().html('');
    }

    // Validate currency form fields.
    function validateCurrencyForm() {
        var isValid = true;

        var name = $.trim($('#currency-name-input').val());
        var code = $.trim($('#currency-code-input').val());
        var symbol = $.trim($('#currency-symbol-input').val());

        clearFieldError('#currency-name-input', '#currency-name-error');
        clearFieldError('#currency-code-input', '#currency-code-error');
        clearFieldError('#currency-symbol-input', '#currency-symbol-error');

        if (!name) {
            showFieldError('#currency-name-input', '#currency-name-error', 'Please enter the currency name.');
            isValid = false;
        }

        if (!code) {
            showFieldError('#currency-code-input', '#currency-code-error', 'Please enter the currency code.');
            isValid = false;
        } else if (!/^[A-Z]{3}$/.test(code)) {
            showFieldError('#currency-code-input', '#currency-code-error', 'Currency code must contain exactly 3 uppercase letters, example: USD.');
            isValid = false;
        }

        if (!symbol) {
            showFieldError('#currency-symbol-input', '#currency-symbol-error', 'Please enter the currency symbol.');
            isValid = false;
        }

        return isValid;
    }

    // Automatically uppercase currency code.
    $('#currency-code-input').on('input', function () {
        this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '').slice(0, 3);

        if (this.value.length === 3) {
            clearFieldError('#currency-code-input', '#currency-code-error');
        }
    });

    // Live validation for name.
    $('#currency-name-input').on('input', function () {
        if ($.trim($(this).val())) {
            clearFieldError('#currency-name-input', '#currency-name-error');
        }
    });

    // Live validation for symbol.
    $('#currency-symbol-input').on('input', function () {
        if ($.trim($(this).val())) {
            clearFieldError('#currency-symbol-input', '#currency-symbol-error');
        }
    });

    // Confirm form submission with SweetAlert2 after validation.
    $('#currency-create-form').on('beforeSubmit', function () {
        var form = $(this);

        if (!validateCurrencyForm()) {
            return false;
        }

        if (form.data('confirmed') === true) {
            return true;
        }

        if (typeof Swal === 'undefined') {
            return true;
        }

        Swal.fire({
            width: 340,
            title: 'Confirm creation',
            html: 'Are you sure you want to create this currency?',
            icon: 'question',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            confirmButtonText: '<i class=\"bi bi-check-circle-fill\"></i> Create',
            cancelButtonText: '<i class=\"bi bi-arrow-counterclockwise\"></i> Review',
            buttonsStyling: false,
            customClass: {
                popup: 'custom-submit-popup',
                title: 'custom-submit-title',
                htmlContainer: 'custom-submit-message',
                confirmButton: 'swal-submit-confirm',
                cancelButton: 'swal-submit-cancel'
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                form.data('confirmed', true);
                form.submit();
            }
        });

        return false;
    });
});
JS, \yii\web\View::POS_READY);
?>

<main class="dash-content currency-create-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-currency-exchange"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Create a new currency by entering its name, international code, and symbol.
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-arrow-left"></i> Back to Currencies',
                ['index'],
                ['class' => 'btn btn-back-currency']
            ) ?>
        </div>

        <!-- Flash success message -->
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
            </div>
        <?php endif; ?>

        <!-- Flash error message -->
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
            </div>
        <?php endif; ?>

        <div class="form-center-wrapper">

            <!-- Create currency form card -->
            <div class="content-card">

                <div class="form-section-title">
                    <i class="bi bi-pencil-square"></i>
                    Currency Information
                </div>

                <?php $form = ActiveForm::begin([
                    'id' => 'currency-create-form',
                    'options' => [
                        'autocomplete' => 'off',
                    ],
                ]); ?>

                    <div class="form-row-center">
                        <div class="form-column">

                            <!-- Currency name field -->
                            <?= $form->field($model, 'name')->textInput([
                                'class' => 'form-control',
                                'id' => 'currency-name-input',
                                'placeholder' => 'Enter currency name, example: US Dollar',
                                'maxlength' => true,
                            ])->label(
                                $model->getAttributeLabel('name') . '<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>Enter the full currency name, for example: US Dollar, Euro, Tunisian Dinar.</span>
                            </div>

                            <div id="currency-name-error" class="field-error-message"></div>

                            <!-- Currency code field -->
                            <?= $form->field($model, 'code')->textInput([
                                'class' => 'form-control',
                                'id' => 'currency-code-input',
                                'placeholder' => 'Enter currency code, example: USD',
                                'maxlength' => 3,
                            ])->label(
                                $model->getAttributeLabel('code') . '<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>Use the international 3-letter currency code, for example: USD, EUR, TND.</span>
                            </div>

                            <div id="currency-code-error" class="field-error-message"></div>

                            <!-- Currency symbol field -->
                            <?= $form->field($model, 'symbol')->textInput([
                                'class' => 'form-control',
                                'id' => 'currency-symbol-input',
                                'placeholder' => 'Enter currency symbol, example: $, €, د.ت',
                                'maxlength' => true,
                            ])->label(
                                $model->getAttributeLabel('symbol') . '<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>Enter the currency symbol used for display, for example: $, €, £.</span>
                            </div>

                            <div id="currency-symbol-error" class="field-error-message"></div>

                        </div>
                    </div>

                    <!-- Form actions -->
                    <div class="form-actions">
                        <?= Html::submitButton(
                            '<i class="bi bi-check-circle"></i> Create Currency',
                            ['class' => 'btn btn-submit-currency']
                        ) ?>

                        <?= Html::a(
                            '<i class="bi bi-arrow-left-circle"></i> Back to Currencies',
                            ['index'],
                            ['class' => 'btn btn-back-currency']
                        ) ?>
                    </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    </div>
</main>

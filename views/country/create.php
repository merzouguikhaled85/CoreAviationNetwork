<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'Add Country';

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

    .country-create-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .country-create-page .container-fluid {
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
        font-weight: 800;
        color: #1f2937;
    }

    .subtitle-text {
        color: #6b7280;
        margin-top: 6px;
        font-size: 14px;
    }

    .content-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5eaf3;
        max-width: 760px;
    }

    .form-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid #eef2f7;
    }

    .form-section-title i {
        color: #2563eb;
        font-size: 18px;
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
        background: #ffffff;
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

    .help-block,
    .invalid-feedback {
        color: #dc2626;
        font-size: 12px;
        font-weight: 600;
        margin-top: 6px;
    }

    .has-error .form-control,
    .is-invalid {
        border-color: #ef4444 !important;
    }

    .required-star {
        color: #ef4444;
        margin-left: 3px;
    }

    .form-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 8px;
        padding-top: 18px;
        border-top: 1px solid #eef2f7;
    }

    .btn-submit-country,
    .btn-back-country {
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

    .btn-submit-country {
        background: #2563eb;
        color: #ffffff !important;
        border: 1px solid #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
    }

    .btn-submit-country:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .btn-back-country {
        background: #f8fafc;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }

    .btn-back-country:hover {
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

    /* Smaller SweetAlert popup */
    .swal2-popup.custom-submit-popup {
        width: 340px !important;
        max-width: 90vw !important;
        border-radius: 15px !important;
        padding: 14px 16px 15px !important;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.22) !important;
    }

    /* Smaller SweetAlert icon */
    .swal2-popup.custom-submit-popup .swal2-icon {
        width: 48px !important;
        height: 48px !important;
        margin: 6px auto 10px !important;
    }

    .swal2-popup.custom-submit-popup .swal2-icon .swal2-icon-content {
        font-size: 28px !important;
    }

    /* Smaller SweetAlert title */
    .swal2-title.custom-submit-title {
        color: #0f172a !important;
        font-size: 18px !important;
        font-weight: 800 !important;
        padding: 0 !important;
        margin: 0 0 6px !important;
    }

    /* Smaller SweetAlert message */
    .swal2-html-container.custom-submit-message {
        color: #64748b !important;
        font-size: 12.5px !important;
        line-height: 1.4 !important;
        margin: 0 8px 12px !important;
    }

    /* More space between SweetAlert buttons */
    .swal2-actions {
        margin-top: 12px !important;
        gap: 14px !important;
    }

    /* Smaller SweetAlert buttons */
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
        .country-create-page {
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

        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-submit-country,
        .btn-back-country {
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
// Confirm form submission with SweetAlert2 after Yii validation passes.
$('#country-create-form').on('beforeSubmit', function () {
    var form = $(this);

    if (form.data('confirmed') === true) {
        return true;
    }

    if (typeof Swal === 'undefined') {
        return true;
    }

    Swal.fire({
        width: 340,
        title: 'Confirm creation',
        html: 'Are you sure you want to add this country?',
        icon: 'question',
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonText: '<i class="bi bi-check-circle-fill"></i> Add',
        cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
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
JS, \yii\web\View::POS_READY);
?>

<main class="dash-content country-create-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-globe2"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Create a new country record by entering its name and country code.
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-arrow-left"></i> Back to Country',
                ['index'],
                ['class' => 'btn btn-back-country']
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

        <!-- Create country form card -->
        <div class="content-card">

            <div class="form-section-title">
                <i class="bi bi-pencil-square"></i>
                Country Information
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'country-create-form',
                'options' => [
                    'autocomplete' => 'off',
                ],
            ]); ?>

                <!-- Country name field -->
                <div class="row">
                    <div class="col-lg-8 col-md-10">
                        <?= $form->field($country, 'country_name')->textInput([
                            'class' => 'form-control',
                            'placeholder' => 'Enter country name',
                        ])->label(
                            $country->getAttributeLabel('country_name') . '<span class="required-star">*</span>',
                            ['encode' => false]
                        ) ?>
                    </div>
                </div>

                <!-- Country code field -->
                <div class="row">
                    <div class="col-lg-6 col-md-8">
                        <?= $form->field($country, 'country_code')->textInput([
                            'class' => 'form-control',
                            'placeholder' => 'Enter country code, example: TN',
                            'maxlength' => true,
                        ])->label(
                            $country->getAttributeLabel('country_code') . '<span class="required-star">*</span>',
                            ['encode' => false]
                        ) ?>
                    </div>
                </div>

                <!-- Form actions -->
                <div class="form-actions">
                    <?= Html::submitButton(
                        '<i class="bi bi-check-circle"></i> Add Country',
                        ['class' => 'btn btn-submit-country']
                    ) ?>

                    <?= Html::a(
                        '<i class="bi bi-arrow-left-circle"></i> Back to Country',
                        ['index'],
                        ['class' => 'btn btn-back-country']
                    ) ?>
                </div>

            <?php ActiveForm::end(); ?>

        </div>

    </div>
</main>

<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

$this->title = 'Create Certificate';

/* Bootstrap Icons */
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

/* Select2 CSS */
$this->registerCssFile('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');

/* SweetAlert2 */
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

/* Select2 JS */
$this->registerJsFile('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

$this->registerCss("
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .certificate-create-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .certificate-create-page .container-fluid {
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
        max-width: 820px;
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

    .form-control,
    .form-select {
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

    .form-control:focus,
    .form-select:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12);
    }

    .form-control::placeholder {
        color: #94a3b8;
        font-weight: 500;
    }

    /* Custom English file input */
    .custom-file-input-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        overflow: hidden;
        z-index: -1;
    }

    .custom-file-box {
        width: 100%;
        min-height: 44px;
        border: 1px solid #dbeafe;
        border-radius: 11px;
        background: #ffffff;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 7px;
        transition: 0.2s ease;
    }

    .custom-file-box:hover {
        border-color: #38bdf8;
    }

    .custom-file-button {
        height: 32px;
        border: none;
        border-radius: 8px;
        background: #eef2ff;
        color: #4338ca;
        font-size: 13px;
        font-weight: 800;
        padding: 0 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        white-space: nowrap;
        transition: 0.2s ease;
    }

    .custom-file-button:hover {
        background: #e0e7ff;
        color: #3730a3;
    }

    .custom-file-name {
        flex: 1;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .custom-file-name.has-file {
        color: #0f172a;
        font-weight: 700;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--single {
        height: 44px !important;
        border: 1px solid #dbeafe !important;
        border-radius: 11px !important;
        background: #ffffff !important;
        display: flex !important;
        align-items: center !important;
        box-shadow: none !important;
        transition: 0.2s ease;
    }

    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #38bdf8 !important;
        box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12) !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #334155 !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        padding-left: 13px !important;
        padding-right: 34px !important;
        line-height: 42px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #94a3b8 !important;
        font-weight: 500 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px !important;
        right: 8px !important;
    }

    .select2-dropdown {
        border: 1px solid #dbeafe !important;
        border-radius: 12px !important;
        overflow: hidden !important;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14) !important;
    }

    .select2-search--dropdown {
        padding: 10px !important;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #dbeafe !important;
        border-radius: 9px !important;
        padding: 8px 10px !important;
        outline: none !important;
        font-size: 13px !important;
    }

    .select2-results__option {
        padding: 9px 12px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
    }

    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background: #2563eb !important;
        color: #ffffff !important;
    }

    .help-block,
    .invalid-feedback {
        color: #dc2626;
        font-size: 12px;
        font-weight: 600;
        margin-top: 6px;
    }

    .has-error .form-control,
    .has-error .form-select {
        border-color: #ef4444 !important;
    }

    .required-star {
        color: #ef4444;
        margin-left: 3px;
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

    .btn-submit-certificate,
    .btn-back-certificate {
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

    .btn-submit-certificate {
        background: #2563eb;
        color: #ffffff !important;
        border: 1px solid #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
    }

    .btn-submit-certificate:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .btn-back-certificate {
        background: #f8fafc;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }

    .btn-back-certificate:hover {
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

    .swal-submit-cancel {
        background: #e2e8f0 !important;
        color: #334155 !important;
    }

    @media (max-width: 768px) {
        .certificate-create-page {
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

        .custom-file-box {
            flex-direction: column;
            align-items: stretch;
        }

        .custom-file-button {
            width: 100%;
        }

        .custom-file-name {
            white-space: normal;
            text-align: center;
        }

        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-submit-certificate,
        .btn-back-certificate {
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

$certificateTypeUrl = Url::to(['certificate-types/get-certificate-type-id']);

$this->registerJs(<<<JS
$(document).ready(function () {

    // Initialize searchable dropdowns.
    $('.js-select2').select2({
        width: '100%',
        allowClear: true
    });

    // Open hidden file input when custom button is clicked.
    $('#certificate-file-button').on('click', function () {
        $('#certificate-file-input').trigger('click');
    });

    // Display selected file name in English custom file input.
    $('#certificate-file-input').on('change', function () {
        var fileName = this.files && this.files.length > 0 ? this.files[0].name : 'No file selected';

        $('#certificate-file-name')
            .text(fileName)
            .toggleClass('has-file', this.files && this.files.length > 0);
    });

    // Get certificate type ID when certificate type changes.
    $('#certificate-type-dropdown').on('change', function () {
        var selectedType = $(this).val();

        if (!selectedType) {
            $('#certificate-type-id').val('');
            return;
        }

        $.ajax({
            url: '$certificateTypeUrl',
            type: 'GET',
            data: {
                type: selectedType
            },
            success: function (data) {
                if (typeof data === 'string') {
                    try {
                        data = JSON.parse(data);
                    } catch (e) {
                        data = {};
                    }
                }

                if (data && data.certificate_type_id) {
                    $('#certificate-type-id').val(data.certificate_type_id);
                } else {
                    $('#certificate-type-id').val('');
                }
            },
            error: function () {
                $('#certificate-type-id').val('');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        width: 340,
                        title: 'Error',
                        html: 'Error fetching certificate type ID.',
                        icon: 'error',
                        confirmButtonText: '<i class="bi bi-check-circle"></i> Got it',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'custom-submit-popup',
                            title: 'custom-submit-title',
                            htmlContainer: 'custom-submit-message',
                            confirmButton: 'swal-submit-confirm'
                        }
                    });
                } else {
                    alert('Error fetching certificate type ID.');
                }
            }
        });
    });

    // Confirm form submission with SweetAlert2 after Yii validation passes.
    $('#certificate-create-form').on('beforeSubmit', function () {
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
            html: 'Are you sure you want to create this certificate?',
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

<main class="dash-content certificate-create-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-file-earmark-check"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Upload a certificate, select its aviation authority type, and assign it to an MRO profile.
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-arrow-left"></i> Back to Certificates',
                ['index'],
                ['class' => 'btn btn-back-certificate']
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

            <!-- Create certificate form card -->
            <div class="content-card">

                <div class="form-section-title">
                    <i class="bi bi-pencil-square"></i>
                    Certificate Information
                </div>

                <?php $form = ActiveForm::begin([
                    'id' => 'certificate-create-form',
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'autocomplete' => 'off',
                    ],
                ]); ?>

                    <div class="form-row-center">
                        <div class="form-column">

                            <!-- Certificate file field with English custom text -->
                            <?= $form->field($certificate, 'certificate', [
                                'template' => "{label}\n
                                    <div class=\"custom-file-box\">
                                        <button type=\"button\" class=\"custom-file-button\" id=\"certificate-file-button\">
                                            <i class=\"bi bi-upload\"></i> Choose File
                                        </button>
                                        <span class=\"custom-file-name\" id=\"certificate-file-name\">No file selected</span>
                                    </div>
                                    {input}\n{error}",
                            ])->fileInput([
                                'id' => 'certificate-file-input',
                                'class' => 'custom-file-input-hidden',
                                'accept' => '.pdf,.doc,.docx',
                            ])->label(
                                'Certificate File<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <!-- Certificate type field -->
                            <?= $form->field($certificate, 'type')->dropDownList(
                                ArrayHelper::map(
                                    \app\models\CertificateTypes::find()->orderBy(['type' => SORT_ASC])->all(),
                                    'type',
                                    'type'
                                ),
                                [
                                    'prompt' => 'Select Type',
                                    'id' => 'certificate-type-dropdown',
                                    'class' => 'form-select js-select2',
                                    'data-placeholder' => 'Select Type',
                                ]
                            )->label(
                                'NAA - National Aviation Authority<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <!-- Hidden certificate type ID -->
                            <?= $form->field($certificate, 'certificate_type_id')->hiddenInput([
                                'id' => 'certificate-type-id',
                            ])->label(false) ?>

                            <!-- MRO field -->
                            <?= $form->field($certificate, 'mro_id')->dropDownList(
                                ArrayHelper::map($mros, 'mro_id', 'username'),
                                [
                                    'prompt' => 'Select MRO',
                                    'class' => 'form-select js-select2',
                                    'data-placeholder' => 'Select MRO',
                                ]
                            )->label(
                                'Select MRO<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                        </div>
                    </div>

                    <!-- Form actions -->
                    <div class="form-actions">
                        <?= Html::submitButton(
                            '<i class="bi bi-check-circle"></i> Create Certificate',
                            ['class' => 'btn btn-submit-certificate']
                        ) ?>

                        <?= Html::a(
                            '<i class="bi bi-arrow-left-circle"></i> Back to Certificates',
                            ['index'],
                            ['class' => 'btn btn-back-certificate']
                        ) ?>
                    </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    </div>
</main>

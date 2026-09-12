<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

$this->title = 'Create Aircraft';

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

/*
 * Prepare aircraft models for JavaScript.
 * This allows the model dropdown to update dynamically by manufacturer.
 */
$aircraftModelsJs = [];

foreach ($models as $model) {
    $aircraftModelsJs[] = [
        'aircraft_model_id' => $model->aircraft_model_id,
        'manufacturer' => $model->manufacturer,
        'model' => $model->model,
    ];
}

$aircraftModelsJson = Json::htmlEncode($aircraftModelsJs);

$this->registerCss("
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .aircraft-create-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .aircraft-create-page .container-fluid {
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

    /* Center form card */
    .form-center-wrapper {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    .content-card {
        width: 100%;
        max-width: 850px;
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

    /* Select2 design similar to the form fields */
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
    .has-error .form-select,
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
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 10px;
        padding-top: 20px;
        border-top: 1px solid #eef2f7;
    }

    .btn-submit-aircraft,
    .btn-back-aircraft {
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

    .btn-submit-aircraft {
        background: #2563eb;
        color: #ffffff !important;
        border: 1px solid #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
    }

    .btn-submit-aircraft:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .btn-back-aircraft {
        background: #f8fafc;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }

    .btn-back-aircraft:hover {
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
        .aircraft-create-page {
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

        .btn-submit-aircraft,
        .btn-back-aircraft {
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
// Aircraft models data from PHP.
var aircraftModels = $aircraftModelsJson;

// Initialize Select2 dropdowns.
function initSelect2() {
    $('.js-select2').select2({
        width: '100%',
        allowClear: true
    });
}

// Populate model dropdown according to selected manufacturer.
function updateModelDropdown(selectedManufacturer, selectedModel) {
    var modelDropdown = document.getElementById('model-dropdown');
    var aircraftModelIdInput = document.getElementById('aircraft-model-id');

    modelDropdown.innerHTML = '';

    var defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.text = 'Select Model';
    modelDropdown.appendChild(defaultOption);

    aircraftModelIdInput.value = '';

    aircraftModels.forEach(function (item) {
        if (item.manufacturer === selectedManufacturer) {
            var option = document.createElement('option');
            option.value = item.model;
            option.text = item.model;
            option.setAttribute('data-id', item.aircraft_model_id);

            if (selectedModel && selectedModel === item.model) {
                option.selected = true;
                aircraftModelIdInput.value = item.aircraft_model_id;
            }

            modelDropdown.appendChild(option);
        }
    });

    $('#model-dropdown').val(selectedModel || '').trigger('change.select2');
}

// Update hidden aircraft_model_id when model changes.
function updateAircraftModelId() {
    var modelDropdown = document.getElementById('model-dropdown');
    var selectedOption = modelDropdown.options[modelDropdown.selectedIndex];
    var aircraftModelIdInput = document.getElementById('aircraft-model-id');

    if (selectedOption && selectedOption.getAttribute('data-id')) {
        aircraftModelIdInput.value = selectedOption.getAttribute('data-id');
    } else {
        aircraftModelIdInput.value = '';
    }
}

$(document).ready(function () {
    initSelect2();

    var selectedManufacturer = $('#manufacturer-dropdown').val();
    var selectedModel = $('#model-dropdown').data('selected-model');

    if (selectedManufacturer) {
        updateModelDropdown(selectedManufacturer, selectedModel);
    }

    $('#manufacturer-dropdown').on('change', function () {
        updateModelDropdown($(this).val(), null);
    });

    $('#model-dropdown').on('change', function () {
        updateAircraftModelId();
    });

    // Confirm form submission with SweetAlert2 after Yii validation passes.
    $('#aircraft-create-form').on('beforeSubmit', function () {
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
            html: 'Are you sure you want to create this aircraft?',
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

<main class="dash-content aircraft-create-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-airplane"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Create a new aircraft record by selecting manufacturer, model, owner, and registration details.
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-arrow-left"></i> Back to Aircraft Models',
                ['index'],
                ['class' => 'btn btn-back-aircraft']
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

            <!-- Create aircraft form card -->
            <div class="content-card">

                <div class="form-section-title">
                    <i class="bi bi-pencil-square"></i>
                    Aircraft Information
                </div>

                <?php $form = ActiveForm::begin([
                    'id' => 'aircraft-create-form',
                    'options' => [
                        'autocomplete' => 'off',
                    ],
                ]); ?>

                    <div class="form-row-center">
                        <div class="form-column">

                            <!-- Manufacturer field -->
                            <?= $form->field($aircraftModel, 'manufacturer')->dropDownList(
                                ArrayHelper::map($manufacturers, 'manufacturer', 'manufacturer'),
                                [
                                    'prompt' => 'Select Manufacturer',
                                    'id' => 'manufacturer-dropdown',
                                    'class' => 'form-select js-select2',
                                    'data-placeholder' => 'Select Manufacturer',
                                ]
                            )->label(
                                $aircraftModel->getAttributeLabel('manufacturer') . '<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <!-- Model field -->
                            <?= $form->field($aircraftModel, 'model')->dropDownList(
                                [],
                                [
                                    'prompt' => 'Select Model',
                                    'id' => 'model-dropdown',
                                    'class' => 'form-select js-select2',
                                    'data-placeholder' => 'Select Model',
                                    'data-selected-model' => $aircraftModel->model,
                                ]
                            )->label(
                                $aircraftModel->getAttributeLabel('model') . '<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <!-- Serial number field -->
                            <?= $form->field($aircraftModel, 'serial_number')->textInput([
                                'class' => 'form-control',
                                'placeholder' => 'Enter serial number',
                            ])->label(
                                $aircraftModel->getAttributeLabel('serial_number') . '<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <!-- Registration number field -->
                            <?= $form->field($aircraftModel, 'registration_number')->textInput([
                                'class' => 'form-control',
                                'placeholder' => 'Enter registration number',
                            ])->label(
                                $aircraftModel->getAttributeLabel('registration_number') . '<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <!-- Owner field -->
                            <?= $form->field($aircraftModel, 'ao_id')->dropDownList(
                                ArrayHelper::map($owners, 'ao_id', 'username'),
                                [
                                    'prompt' => 'Select Owner',
                                    'class' => 'form-select js-select2',
                                    'data-placeholder' => 'Select Owner',
                                ]
                            )->label(
                                'Select Owner<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <!-- Hidden aircraft model ID -->
                            <?= $form->field($aircraftModel, 'aircraft_model_id')->hiddenInput([
                                'id' => 'aircraft-model-id',
                            ])->label(false) ?>

                        </div>
                    </div>

                    <!-- Form actions -->
                    <div class="form-actions">
                        <?= Html::submitButton(
                            '<i class="bi bi-check-circle"></i> Create Aircraft',
                            ['class' => 'btn btn-submit-aircraft']
                        ) ?>

                        <?= Html::a(
                            '<i class="bi bi-arrow-left-circle"></i> Back to Aircraft Models',
                            ['index'],
                            ['class' => 'btn btn-back-aircraft']
                        ) ?>
                    </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    </div>
</main>

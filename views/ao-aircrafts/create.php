<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\View;

/** @var yii\web\View $this */
/** @var app\models\Aircraft $aircraftModel */
/** @var array $manufacturers */
/** @var array $models */

/**
 * Page title.
 */
$this->title = 'Create Aircraft';

/**
 * Register Yii asset.
 */
\yii\web\YiiAsset::register($this);

/**
 * Register Bootstrap Icons.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['position' => View::POS_HEAD]
);

/**
 * Register Select2 CSS and JS.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    ['position' => View::POS_HEAD]
);

$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
    ['depends' => [\yii\web\JqueryAsset::class]]
);

/**
 * Register SweetAlert2 CSS and JS.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    ['position' => View::POS_HEAD]
);

$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
    ['depends' => [\yii\web\JqueryAsset::class]]
);

/**
 * Prepare aircraft models grouped by manufacturer for JavaScript.
 */
$modelsByManufacturer = [];

foreach ($models as $modelItem) {
    $modelsByManufacturer[$modelItem->manufacturer][] = [
        'id' => $modelItem->aircraft_model_id,
        'model' => $modelItem->model,
    ];
}

$modelsJson = Json::htmlEncode($modelsByManufacturer);

/**
 * Keep selected values after validation errors.
 */
$selectedManufacturer = $aircraftModel->manufacturer ?? '';
$selectedModel = $aircraftModel->model ?? '';

$selectedManufacturerJson = Json::htmlEncode($selectedManufacturer);
$selectedModelJson = Json::htmlEncode($selectedModel);

/**
 * Prepare back URL.
 */
$backUrl = Yii::$app->request->referrer ?: ['index'];

/**
 * Register page CSS.
 */
$this->registerCss(<<<CSS
html,
body {
    max-width: 100%;
    overflow-x: hidden;
}

.create-page {
    padding: 24px;
    background: #f5f7fb;
    min-height: 100vh;
    max-width: 100%;
    overflow-x: hidden;
}

.create-page .container-fluid {
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

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-page-action {
    border-radius: 9px;
    padding: 10px 18px;
    font-weight: 800;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    white-space: nowrap;
    text-decoration: none;
    transition: all .2s ease;
}

.btn-back {
    background: #ffffff;
    color: #334155 !important;
    border: 1px solid #cbd5e1;
}

.btn-back:hover {
    background: #f1f5f9;
    color: #0f172a !important;
    transform: translateY(-1px);
    text-decoration: none;
}

.form-layout {
    display: grid;
    grid-template-columns: 1.25fr 0.75fr;
    gap: 18px;
}

.form-card,
.helper-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 20px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5eaf3;
    max-width: 100%;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 18px;
    font-size: 17px;
    font-weight: 900;
    color: #0f172a;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.form-field-card {
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 14px;
    min-width: 0;
}

.form-field-card.full-width {
    grid-column: 1 / -1;
}

.form-group {
    margin-bottom: 0;
}

.form-group label,
.control-label {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #64748b;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 8px;
}

.required-mark {
    color: #dc2626;
    font-size: 14px;
    line-height: 1;
}

.form-control,
.form-select {
    width: 100%;
    min-height: 46px;
    border-radius: 12px !important;
    border: 1px solid #cbd5e1 !important;
    background-color: #ffffff !important;
    color: #0f172a !important;
    font-size: 14px;
    font-weight: 700;
    padding: 10px 13px;
    box-shadow: none !important;
    transition: all .2s ease;
}

.form-control:focus,
.form-select:focus {
    border-color: #38bdf8 !important;
    box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12) !important;
}

.help-block,
.invalid-feedback {
    margin-top: 7px;
    font-size: 12px;
    font-weight: 700;
    color: #dc2626;
}

.has-error .form-control,
.has-error .form-select {
    border-color: #fca5a5 !important;
}

/* Select2 design */
.select2-container {
    width: 100% !important;
}

.select2-container--default .select2-selection--single {
    min-height: 46px;
    border-radius: 12px !important;
    border: 1px solid #cbd5e1 !important;
    background-color: #ffffff !important;
    display: flex;
    align-items: center;
    padding: 6px 10px;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #0f172a !important;
    font-size: 14px;
    font-weight: 700;
    line-height: 32px;
    padding-left: 2px;
}

.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #94a3b8 !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 44px;
    right: 10px;
}

.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #38bdf8 !important;
    box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12) !important;
}

.select2-dropdown {
    border: 1px solid #cbd5e1 !important;
    border-radius: 12px !important;
    overflow: hidden;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
}

.select2-search--dropdown {
    padding: 10px;
}

.select2-search--dropdown .select2-search__field {
    border-radius: 10px !important;
    border: 1px solid #cbd5e1 !important;
    padding: 8px 10px;
    outline: none;
    font-size: 14px;
    font-weight: 700;
}

.select2-results__option {
    font-size: 14px;
    font-weight: 700;
    padding: 9px 12px;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background: #0ea5e9 !important;
    color: #ffffff !important;
}

.form-actions {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid #e5eaf3;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-form-action {
    min-height: 44px;
    border-radius: 12px;
    padding: 11px 18px;
    font-size: 13px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    border: 1px solid rgba(15, 23, 42, .12);
    transition: all .2s ease;
}

.btn-create {
    background: #0ea5e9;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(14, 165, 233, 0.22);
}

.btn-create:hover {
    background: #0284c7;
    color: #ffffff !important;
    transform: translateY(-1px);
    text-decoration: none;
}

.btn-cancel {
    background: #64748b;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(100, 116, 139, 0.18);
}

.btn-cancel:hover {
    background: #475569;
    color: #ffffff !important;
    transform: translateY(-1px);
    text-decoration: none;
}

.alert {
    border-radius: 14px;
    border: none;
    font-weight: 800;
    padding: 14px 16px;
    margin-bottom: 18px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.helper-list {
    display: grid;
    gap: 12px;
}

.helper-item {
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 14px;
}

.helper-label {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    margin-bottom: 5px;
}

.helper-text {
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.5;
    margin: 0;
}

/* SweetAlert2 create confirmation design */
.swal-create-popup {
    width: 380px !important;
    border-radius: 18px !important;
    padding: 22px !important;
}

.swal-create-title {
    font-size: 20px !important;
    font-weight: 900 !important;
    color: #0f172a !important;
}

.swal-create-html {
    font-size: 14px !important;
    font-weight: 700 !important;
    color: #64748b !important;
}

.swal-create-confirm {
    border-radius: 10px !important;
    padding: 10px 18px !important;
    font-size: 13px !important;
    font-weight: 900 !important;
    background: #0ea5e9 !important;
    box-shadow: 0 8px 18px rgba(14, 165, 233, 0.25) !important;
}

.swal-create-cancel {
    border-radius: 10px !important;
    padding: 10px 18px !important;
    font-size: 13px !important;
    font-weight: 900 !important;
    background: #64748b !important;
}

.swal2-actions {
    gap: 12px !important;
}

@media (max-width: 992px) {
    .create-page {
        padding: 14px;
    }

    .page-header-card {
        padding: 18px;
    }

    .dash-title {
        font-size: 23px;
    }

    .form-layout {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .create-page {
        padding: 10px;
    }

    .page-header-card,
    .form-card,
    .helper-card {
        border-radius: 14px;
        padding: 16px;
    }

    .header-actions {
        width: 100%;
    }

    .btn-page-action,
    .btn-form-action {
        width: 100%;
    }

    .form-actions {
        justify-content: stretch;
    }
}
CSS);

/**
 * Register dynamic Select2 dropdown and SweetAlert2 confirmation JavaScript.
 */
$this->registerJs(<<<JS
const aircraftModelsByManufacturer = {$modelsJson};
const selectedManufacturer = {$selectedManufacturerJson};
const selectedModel = {$selectedModelJson};

function initSelect2() {
    $('.js-select2').select2({
        width: '100%',
        placeholder: 'Select an option',
        allowClear: true
    });
}

function fillModelDropdown(manufacturerValue, keepSelectedModel = false) {
    const modelDropdown = $('#model-dropdown');
    const aircraftModelIdInput = $('#aircraft-model-id');

    modelDropdown.empty();

    const defaultOption = new Option('Select Model', '', true, false);
    modelDropdown.append(defaultOption);

    aircraftModelIdInput.val('');

    if (
        manufacturerValue &&
        Object.prototype.hasOwnProperty.call(aircraftModelsByManufacturer, manufacturerValue)
    ) {
        aircraftModelsByManufacturer[manufacturerValue].forEach(function(item) {
            const option = new Option(item.model, item.model, false, false);
            $(option).attr('data-model-id', item.id);
            modelDropdown.append(option);
        });
    }

    if (keepSelectedModel && selectedModel) {
        modelDropdown.val(selectedModel);

        const selectedOption = modelDropdown.find('option:selected');
        const modelId = selectedOption.attr('data-model-id') || '';

        aircraftModelIdInput.val(modelId);
    } else {
        modelDropdown.val('');
    }

    modelDropdown.trigger('change.select2');
}

$(document).ready(function() {
    initSelect2();

    const manufacturerDropdown = $('#manufacturer-dropdown');
    const modelDropdown = $('#model-dropdown');
    const aircraftModelIdInput = $('#aircraft-model-id');
    const createAircraftForm = $('#create-aircraft-form');

    if (selectedManufacturer) {
        manufacturerDropdown.val(selectedManufacturer).trigger('change.select2');
        fillModelDropdown(selectedManufacturer, true);
    }

    manufacturerDropdown.on('change', function() {
        const manufacturerValue = $(this).val();

        fillModelDropdown(manufacturerValue, false);

        modelDropdown.val('').trigger('change.select2');
        aircraftModelIdInput.val('');
    });

    modelDropdown.on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const modelId = selectedOption.attr('data-model-id') || '';

        aircraftModelIdInput.val(modelId);
    });

    /**
     * Confirm aircraft creation only after Yii client validation succeeds.
     */
    createAircraftForm.on('beforeSubmit', function(e) {
        if (createAircraftForm.data('creation-confirmed') === true) {
            return true;
        }

        e.preventDefault();

        Swal.fire({
            title: 'Confirm aircraft creation?',
            html: 'Please confirm that all aircraft information is correct before creating this record.',
            icon: 'question',
            showCancelButton: true,
            // FORM DIALOG ACTIONS: use direct aircraft actions with recognizable icons.
            confirmButtonText: '<i class="bi bi-airplane-engines"></i> Create aircraft',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'swal-create-popup',
                title: 'swal-create-title',
                htmlContainer: 'swal-create-html',
                confirmButton: 'swal-create-confirm',
                cancelButton: 'swal-create-cancel'
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                createAircraftForm.data('creation-confirmed', true);
                createAircraftForm.trigger('submit');
            }
        });

        return false;
    });
});
JS);
?>

<main class="dash-content create-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-airplane-engines"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Create a new aircraft record and link it to the correct manufacturer, model and NAA certificate.
                </div>
            </div>

            <div class="header-actions">
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Aircrafts',
                    $backUrl,
                    ['class' => 'btn-page-action btn-back']
                ) ?>
            </div>
        </div>

        <!-- Flash messages -->
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
            </div>
        <?php endif; ?>

        <div class="form-layout">

            <!-- Main create form -->
            <div class="form-card">
                <h2 class="section-title">
                    <i class="bi bi-pencil-square text-primary"></i>
                    Aircraft Information
                </h2>

                <?php $form = ActiveForm::begin([
                    'id' => 'create-aircraft-form',
                    'options' => ['enctype' => 'multipart/form-data'],
                ]); ?>

                <!-- Hidden aircraft model ID -->
                <?= Html::activeHiddenInput($aircraftModel, 'aircraft_model_id', [
                    'id' => 'aircraft-model-id',
                ]) ?>

                <!-- Hidden AO ID -->
                <?= Html::activeHiddenInput($aircraftModel, 'ao_id', [
                    'value' => Yii::$app->session->get('ao_id'),
                ]) ?>

                <div class="form-grid">

                    <!-- Manufacturer dropdown -->
                    <div class="form-field-card">
                        <?= $form->field($aircraftModel, 'manufacturer')->dropDownList(
                            ArrayHelper::map($manufacturers, 'manufacturer', 'manufacturer'),
                            [
                                'prompt' => 'Select Manufacturer',
                                'id' => 'manufacturer-dropdown',
                                'class' => 'form-select js-select2',
                            ]
                        )->label(
                            '<i class="bi bi-tools"></i>' .
                            $aircraftModel->getAttributeLabel('manufacturer') .
                            '<span class="required-mark">*</span>',
                            ['encode' => false]
                        ) ?>
                    </div>

                    <!-- Model dropdown -->
                    <div class="form-field-card">
                        <?= $form->field($aircraftModel, 'model')->dropDownList(
                            [],
                            [
                                'prompt' => 'Select Model',
                                'id' => 'model-dropdown',
                                'class' => 'form-select js-select2',
                            ]
                        )->label(
                            '<i class="bi bi-airplane"></i>' .
                            $aircraftModel->getAttributeLabel('model') .
                            '<span class="required-mark">*</span>',
                            ['encode' => false]
                        ) ?>
                    </div>

                    <!-- Serial number -->
                    <div class="form-field-card">
                        <?= $form->field($aircraftModel, 'serial_number')->textInput([
                            'maxlength' => true,
                            'class' => 'form-control',
                            'placeholder' => 'Enter serial number',
                        ])->label(
                            '<i class="bi bi-upc-scan"></i>' .
                            $aircraftModel->getAttributeLabel('serial_number') .
                            '<span class="required-mark">*</span>',
                            ['encode' => false]
                        ) ?>
                    </div>

                    <!-- Registration number -->
                    <div class="form-field-card">
                        <?= $form->field($aircraftModel, 'registration_number')->textInput([
                            'maxlength' => true,
                            'class' => 'form-control',
                            'placeholder' => 'Enter registration number',
                        ])->label(
                            '<i class="bi bi-card-text"></i>' .
                            $aircraftModel->getAttributeLabel('registration_number') .
                            '<span class="required-mark">*</span>',
                            ['encode' => false]
                        ) ?>
                    </div>

                    <!-- NAA certificate dropdown -->
                    <div class="form-field-card full-width">
                        <?= $form->field($aircraftModel, 'certificate_type_id')->dropDownList(
                            ArrayHelper::map(
                                \app\models\CertificateTypes::find()->orderBy(['type' => SORT_ASC])->all(),
                                'certificate_type_id',
                                'type'
                            ),
                            [
                                'prompt' => 'Select NAA',
                                'id' => 'certificate-type-dropdown',
                                'class' => 'form-select js-select2',
                            ]
                        )->label(
                            '<i class="bi bi-shield-check"></i>' .
                            'NAA Certificate' .
                            '<span class="required-mark">*</span>',
                            ['encode' => false]
                        ) ?>
                    </div>

                </div>

                <!-- Form actions -->
                <div class="form-actions">
                    <?= Html::a(
                        '<i class="bi bi-arrow-left-circle"></i> Back to Aircrafts',
                        ['index'],
                        ['class' => 'btn-form-action btn-cancel']
                    ) ?>

                    <?= Html::submitButton(
                        '<i class="bi bi-check-circle"></i> Create Aircraft',
                        ['class' => 'btn-form-action btn-create']
                    ) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>

            <!-- Form guide -->
            <div class="helper-card">
                <h2 class="section-title">
                    <i class="bi bi-info-circle text-primary"></i>
                    Form Guide
                </h2>

                <div class="helper-list">

                    <!-- Manufacturer help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-tools"></i>
                            Manufacturer
                        </div>
                        <p class="helper-text">
                            Select the aircraft manufacturer first. The model list will be updated automatically.
                        </p>
                    </div>

                    <!-- Model help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-airplane"></i>
                            Model
                        </div>
                        <p class="helper-text">
                            Choose the matching aircraft model after selecting the manufacturer.
                        </p>
                    </div>

                    <!-- Registration help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-card-text"></i>
                            Registration
                        </div>
                        <p class="helper-text">
                            Enter the official registration number exactly as used in aircraft records.
                        </p>
                    </div>

                    <!-- NAA help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-shield-check"></i>
                            NAA Certificate
                        </div>
                        <p class="helper-text">
                            Select the related National Aviation Authority certificate type.
                        </p>
                    </div>

                </div>
            </div>

        </div>

    </div>
</main>

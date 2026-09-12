<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

$this->title = 'Update Aircraft';
$this->params['breadcrumbs'][] = ['label' => 'Aircrafts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Bootstrap Icons are used for the header, field cards and buttons.
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    ['position' => $this::POS_HEAD]
);

// SweetAlert2 is used to confirm the update action after Yii client validation passes.
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    ['position' => $this::POS_HEAD]
);

$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    ['depends' => [\yii\web\JqueryAsset::class]]
);

$manufacturerItems = ArrayHelper::map($manufacturers, 'manufacturer', 'manufacturer');
$certificateItems = ArrayHelper::map(
    \app\models\CertificateTypes::find()->orderBy(['type' => SORT_ASC])->all(),
    'certificate_type_id',
    'type'
);

$modelData = [];
foreach ($models as $model) {
    $modelData[] = [
        'id' => $model->aircraft_model_id,
        'manufacturer' => $model->manufacturer,
        'model' => $model->model,
    ];
}

$currentModel = $aircraftModel->model ?? '';
$currentManufacturer = $aircraftModel->manufacturer ?? '';
$currentAircraftModelId = $aircraftModel->aircraft_model_id ?? '';
$currentAircraftId = $aircraftModel->aircraft_id ?? '';

// Preload the model dropdown on first page render.
// This prevents an empty model field if JavaScript loads after Yii's ready event.
$modelItemsForCurrentManufacturer = [];
foreach ($models as $model) {
    if ((string) $model->manufacturer === (string) $currentManufacturer) {
        $modelItemsForCurrentManufacturer[$model->model] = $model->model;
    }
}

$this->registerCss(<<<CSS
/* Main layout aligned with AO appointments / aircraft view design */
.aircraft-page {
    padding: 24px 22px 34px;
    background: #eef3fb;
    min-height: calc(100vh - 80px);
}

.aircraft-page .container-fluid {
    max-width: 1600px;
}

.page-header-card {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 22px 24px;
    margin-bottom: 20px;
    border-radius: 18px;
    border: 1px solid #dbeafe;
    background:
        radial-gradient(circle at 92% 20%, rgba(14, 165, 233, 0.12), transparent 32%),
        linear-gradient(135deg, #ffffff 0%, #f8fbff 48%, #eef6ff 100%);
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.10);
}

.page-header-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #2563eb, #0ea5e9, #22c55e, #eab308);
}

.header-title-group {
    display: flex;
    align-items: center;
    gap: 16px;
    min-width: 0;
}

.header-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    color: #ffffff;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.25);
}

.header-icon i {
    font-size: 25px;
}

.header-title {
    margin: 0;
    color: #0f172a;
    font-size: 27px;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.header-subtitle {
    margin: 7px 0 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.5;
}

.btn-soft-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 9px 16px;
    border-radius: 10px;
    border: 1px solid #bfdbfe;
    background: rgba(8, 8, 8, 0.78);
    color: #f1f2f3ff;
    font-weight: 800;
    font-size: 13px;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.18s ease;
}

.btn-soft-back:hover {
    color: #0a0a0aff;
    background: #f0f3f7ff;
    border-color: #030303ff;
    transform: translateY(-1px);
    text-decoration: none;
}

.form-card {
    overflow: hidden;
    border-radius: 18px;
    border: 1px solid #dbeafe;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 18px 38px rgba(15, 23, 42, 0.09);
}

.form-card-header {
    padding: 20px 22px;
    border-bottom: 1px solid #dbeafe;
    background: linear-gradient(135deg, #ffffff, #f8fbff);
}

.form-card-title {
    margin: 0;
    color: #0f172a;
    font-size: 19px;
    font-weight: 800;
}

.form-card-subtitle {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 13px;
}

.form-card-body {
    padding: 22px;
}

.aircraft-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.aircraft-field-card {
    position: relative;
    display: flex;
    gap: 14px;
    align-items: flex-start;
    padding: 18px 18px 16px;
    min-height: 104px;
    border-radius: 16px;
    border: 1px solid #dbeafe;
    background: linear-gradient(135deg, #ffffff, #f8fbff);
    transition: all 0.18s ease;
}

.aircraft-field-card:hover {
    border-color: #bfdbfe;
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.07);
}

.field-icon {
    width: 42px;
    height: 42px;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    color: #2563eb;
    background: #eff6ff;
}

.field-icon i {
    font-size: 20px;
}

.field-content {
    flex: 1;
    min-width: 0;
}

.aircraft-field-card .form-group {
    margin-bottom: 0;
}

.aircraft-field-card label.control-label,
.aircraft-field-card label {
    display: block;
    margin-bottom: 7px;
    color: #475569;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.aircraft-field-card .form-control,
.aircraft-field-card .form-select {
    min-height: 44px;
    border-radius: 11px;
    border: 1px solid #cbd5e1;
    background-color: #ffffff;
    color: #0f172a;
    font-weight: 700;
    font-size: 14px;
    box-shadow: none;
}

.aircraft-field-card .form-control:focus,
.aircraft-field-card .form-select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 0.18rem rgba(37, 99, 235, 0.13);
}

.aircraft-field-card .help-block,
.aircraft-field-card .invalid-feedback {
    margin-top: 6px;
    color: #dc2626;
    font-size: 12px;
    font-weight: 700;
}

.form-card-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    padding: 18px 22px;
    border-top: 1px solid #dbeafe;
    background: linear-gradient(135deg, #ffffff, #f8fbff);
}

.btn-aircraft-primary,
.btn-aircraft-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 10px 18px;
    border-radius: 11px;
    font-weight: 900;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.18s ease;
}

.btn-aircraft-primary {
    border: 1px solid #2563eb;
    color: #ffffff;
    background: #2563eb;
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.22);
}

.btn-aircraft-primary:hover {
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 16px 28px rgba(37, 99, 235, 0.28);
}

.btn-aircraft-secondary {
    border: 1px solid #bfdbfe;
    color: #f4f5f7ff;
    background: #f70b0bff;
}

.btn-aircraft-secondary:hover {
    color: #f80909ff;
    background: #ffffffff;
    border-color: #030303ff;
    transform: translateY(-1px);
    text-decoration: none;
}

.alert {
    border-radius: 14px;
    border: 0;
    padding: 13px 16px;
    margin-bottom: 18px;
    font-weight: 700;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
}

.alert-success {
    color: #166534;
    background: #dcfce7;
}

.alert-danger {
    color: #991b1b;
    background: #fee2e2;
}

.swal2-popup.aircraft-swal-popup {
    width: 360px !important;
    max-width: calc(100vw - 32px) !important;
    border-radius: 14px;
    padding: 16px 16px 14px;
}

.swal2-popup.aircraft-swal-popup .swal2-icon {
    width: 46px;
    height: 46px;
    margin: 6px auto 10px;
}

.swal2-popup.aircraft-swal-popup .swal2-title {
    color: #0f172a;
    font-size: 18px;
    line-height: 1.25;
    font-weight: 900;
    padding: 0 8px;
}

.swal2-popup.aircraft-swal-popup .swal2-html-container {
    color: #64748b;
    font-size: 13px;
    line-height: 1.45;
    font-weight: 600;
    margin: 8px 6px 0;
}

.swal2-popup.aircraft-swal-popup .swal2-actions {
    margin-top: 14px;
    gap: 8px;
}

.swal2-popup.aircraft-swal-popup .swal2-confirm,
.swal2-popup.aircraft-swal-popup .swal2-cancel {
    min-height: 34px;
    border-radius: 9px !important;
    padding: 7px 13px;
    font-size: 13px;
    font-weight: 800;
}

@media (max-width: 992px) {
    .aircraft-form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .aircraft-page {
        padding: 16px 12px 28px;
    }

    .page-header-card {
        align-items: flex-start;
        flex-direction: column;
        padding: 20px;
    }

    .header-title {
        font-size: 22px;
    }

    .header-icon {
        width: 50px;
        height: 50px;
    }

    .form-card-header,
    .form-card-body,
    .form-card-footer {
        padding-left: 16px;
        padding-right: 16px;
    }

    .form-card-footer {
        flex-direction: column-reverse;
        align-items: stretch;
    }

    .btn-aircraft-primary,
    .btn-aircraft-secondary,
    .btn-soft-back {
        width: 100%;
    }
}
CSS);

$modelDataJson = Json::htmlEncode($modelData);
$currentModelJson = Json::htmlEncode($currentModel);
$currentManufacturerJson = Json::htmlEncode($currentManufacturer);
$currentAircraftModelIdJson = Json::htmlEncode((string)$currentAircraftModelId);
$currentAircraftIdJson = Json::htmlEncode((string)$currentAircraftId);

$this->registerJs(<<<JS
const aircraftModelsData = {$modelDataJson};
const currentAircraftModel = {$currentModelJson};
const currentManufacturer = {$currentManufacturerJson};
const currentAircraftModelId = {$currentAircraftModelIdJson};
const currentAircraftIdForAlert = {$currentAircraftIdJson};

function updateModelDropdown(selectedManufacturer, selectedModel = null) {
    const modelDropdown = document.getElementById('model-dropdown');
    const aircraftModelIdInput = document.getElementById('aircraft-model-id');

    if (!modelDropdown || !aircraftModelIdInput) {
        return;
    }

    modelDropdown.innerHTML = '';

    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Select Model';
    modelDropdown.appendChild(defaultOption);

    aircraftModelsData
        .filter(item => item.manufacturer === selectedManufacturer)
        .forEach(item => {
            const option = document.createElement('option');
            option.value = item.model;
            option.textContent = item.model;
            option.setAttribute('data-id', item.id);

            if (selectedModel && selectedModel === item.model) {
                option.selected = true;
                aircraftModelIdInput.value = item.id;
            }

            modelDropdown.appendChild(option);
        });

    if (!selectedModel) {
        aircraftModelIdInput.value = '';
    }
}

function syncAircraftModelId() {
    const modelDropdown = document.getElementById('model-dropdown');
    const aircraftModelIdInput = document.getElementById('aircraft-model-id');

    if (!modelDropdown || !aircraftModelIdInput) {
        return;
    }

    const selected = modelDropdown.options[modelDropdown.selectedIndex];
    aircraftModelIdInput.value = selected ? (selected.getAttribute('data-id') || '') : '';
}

// Yii registerJs runs this code when the DOM is ready.
// Do not wrap it again in DOMContentLoaded, otherwise it may never run.
const manufacturerDropdown = document.getElementById('manufacturer-dropdown');
const modelDropdown = document.getElementById('model-dropdown');
const aircraftModelIdInput = document.getElementById('aircraft-model-id');

if (manufacturerDropdown) {
    const manufacturerValue = manufacturerDropdown.value || currentManufacturer;

    if (manufacturerValue) {
        updateModelDropdown(manufacturerValue, currentAircraftModel);
    }

    manufacturerDropdown.addEventListener('change', function () {
        updateModelDropdown(this.value, null);
    });
}

if (modelDropdown) {
    modelDropdown.addEventListener('change', syncAircraftModelId);
}

if (aircraftModelIdInput && !aircraftModelIdInput.value && currentAircraftModelId) {
    aircraftModelIdInput.value = currentAircraftModelId;
}

const updateForm = $('#aircraft-update-form');

updateForm.on('beforeSubmit', function () {
    const form = $(this);

    // Let the form submit normally after the user confirms the SweetAlert.
    if (form.data('swal-confirmed') === true) {
        return true;
    }

    const aircraftLabel = currentAircraftIdForAlert ? ('Aircraft #' + currentAircraftIdForAlert) : 'this aircraft';

    Swal.fire({
        title: 'Confirm update',
        html: 'Update <strong>' + aircraftLabel + '</strong>?',
        icon: 'question',
        width: 360,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-check2-circle"></i> Update',
        cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
        confirmButtonColor: '#30c41cff',
        cancelButtonColor: '#f00d0dff',
        customClass: {
            popup: 'aircraft-swal-popup'
        },
        reverseButtons: true,
        focusCancel: true
    }).then(function (result) {
        if (result.isConfirmed) {
            form.data('swal-confirmed', true);
            form.trigger('submit');
        }
    });

    return false;
});
JS);
?>

<main class="aircraft-page dash-content can-form-page">
    <div class="container-fluid">
        <div class="page-header-card">
            <div class="header-title-group">
                <div class="header-icon">
                    <i class="bi bi-airplane-engines"></i>
                </div>
                <div>
                    <h1 class="header-title"><?= Html::encode($this->title) ?></h1>
                    <p class="header-subtitle">
                        Update aircraft details, registration information and NAA certificate type.
                    </p>
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-arrow-left"></i> Back to Aircrafts',
                ['index'],
                ['class' => 'btn-soft-back']
            ) ?>
        </div>

        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-1"></i>
                <?= Yii::$app->session->getFlash('success') ?>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('message')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-1"></i>
                <?= Yii::$app->session->getFlash('message') ?>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?= Yii::$app->session->getFlash('error') ?>
            </div>
        <?php endif; ?>

        <?php $form = ActiveForm::begin([
            'id' => 'aircraft-update-form',
            'options' => ['enctype' => 'multipart/form-data'],
            'enableClientValidation' => true,
        ]); ?>

        <div class="form-card">
            <div class="form-card-header">
                <h2 class="form-card-title">Aircraft Information</h2>
                <p class="form-card-subtitle">Please review and update the aircraft data registered by the aircraft operator.</p>
            </div>

            <div class="form-card-body">
                <div class="aircraft-form-grid">
                    <div class="aircraft-field-card">
                        <div class="field-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="field-content">
                            <?= $form->field($aircraftModel, 'manufacturer')->dropDownList(
                                $manufacturerItems,
                                [
                                    'prompt' => 'Select Manufacturer',
                                    'id' => 'manufacturer-dropdown',
                                    'class' => 'form-select',
                                ]
                            ) ?>
                        </div>
                    </div>

                    <div class="aircraft-field-card">
                        <div class="field-icon">
                            <i class="bi bi-airplane"></i>
                        </div>
                        <div class="field-content">
                            <?= $form->field($aircraftModel, 'model')->dropDownList(
                                $modelItemsForCurrentManufacturer,
                                [
                                    'prompt' => 'Select Model',
                                    'id' => 'model-dropdown',
                                    'class' => 'form-select',
                                    'value' => $currentModel,
                                ]
                            ) ?>
                        </div>
                    </div>

                    <?= $form->field($aircraftModel, 'aircraft_model_id')
                        ->hiddenInput([
                            'id' => 'aircraft-model-id',
                            'value' => $currentAircraftModelId,
                        ])
                        ->label(false) ?>

                    <div class="aircraft-field-card">
                        <div class="field-icon">
                            <i class="bi bi-upc-scan"></i>
                        </div>
                        <div class="field-content">
                            <?= $form->field($aircraftModel, 'serial_number')->textInput([
                                'maxlength' => true,
                                'class' => 'form-control',
                                'placeholder' => 'Enter serial number',
                            ]) ?>
                        </div>
                    </div>

                    <div class="aircraft-field-card">
                        <div class="field-icon">
                            <i class="bi bi-card-heading"></i>
                        </div>
                        <div class="field-content">
                            <?= $form->field($aircraftModel, 'registration_number')->textInput([
                                'maxlength' => true,
                                'class' => 'form-control',
                                'placeholder' => 'Enter registration number',
                            ]) ?>
                        </div>
                    </div>

                    <div class="aircraft-field-card">
                        <div class="field-icon">
                            <i class="bi bi-award"></i>
                        </div>
                        <div class="field-content">
                            <?= $form->field($aircraftModel, 'certificate_type_id')->dropDownList(
                                $certificateItems,
                                [
                                    'prompt' => 'Select Certificate Type',
                                    'class' => 'form-select',
                                ]
                            ) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-card-footer">
                <?= Html::a(
                    '<i class="bi bi-x-circle"></i> Cancel',
                    ['index'],
                    ['class' => 'btn-aircraft-secondary']
                ) ?>

                <?= Html::submitButton(
                    '<i class="bi bi-check2-circle"></i> Update Aircraft',
                    ['class' => 'btn-aircraft-primary']
                ) ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</main>

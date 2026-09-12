<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\web\View;

/** @var yii\web\View $this */
/** @var app\models\MroAircraftCertificates $certificate */

/**
 * Page title.
 */
$this->title = 'Create Certificate';

/**
 * AJAX URL used to load aircraft models by selected manufacturer.
 */
$getModelsUrl = Url::to(['mro-aircraft-certificates/get-models-by-manufacturer']);
$getModelsUrlJson = Json::htmlEncode($getModelsUrl);

/**
 * Keep selected values after validation errors.
 */
$selectedManufacturer = $certificate->manufacturer ?? '';
$selectedAircraftModelId = $certificate->aircraft_model_id ?? '';

$selectedManufacturerJson = Json::htmlEncode($selectedManufacturer);
$selectedAircraftModelIdJson = Json::htmlEncode((string) $selectedAircraftModelId);

/**
 * Prepare current MRO ID safely.
 */
$mroId = Yii::$app->user->identity->mro_id
    ?? Yii::$app->session->get('mro_id')
    ?? null;

/**
 * Prepare back URL.
 */
$backUrl = Yii::$app->request->referrer ?: ['index'];

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

.field-heading {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #64748b;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 8px;
}

.field-heading i {
    color: #0ea5e9;
    font-size: 15px;
}

.required-mark {
    color: #dc2626;
    font-size: 14px;
    line-height: 1;
}

.form-group {
    margin-bottom: 0;
}

.form-group label,
.control-label {
    display: none;
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

/* Custom English file input */
.custom-file-native {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    opacity: 0 !important;
    pointer-events: none !important;
}

.custom-file-box {
    display: flex;
    align-items: center;
    gap: 14px;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 14px;
    padding: 14px;
}

.custom-file-button {
    min-height: 42px;
    border-radius: 12px;
    padding: 10px 16px;
    background: #0ea5e9;
    color: #ffffff !important;
    font-size: 13px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    margin: 0;
    white-space: nowrap;
    box-shadow: 0 8px 18px rgba(14, 165, 233, 0.22);
    transition: all .2s ease;
}

.custom-file-button:hover {
    background: #0284c7;
    transform: translateY(-1px);
}

.custom-file-info {
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.custom-file-name {
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.custom-file-hint {
    margin-top: 3px;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
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

.btn-create:disabled {
    opacity: .55;
    cursor: not-allowed;
    transform: none;
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

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
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

/* SweetAlert2 design */
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

    .custom-file-box {
        flex-direction: column;
        align-items: stretch;
    }

    .custom-file-button {
        width: 100%;
    }
}
CSS);

/**
 * Register JavaScript.
 */
$this->registerJs(<<<JS
const getModelsUrl = {$getModelsUrlJson};
const selectedManufacturer = {$selectedManufacturerJson};
const selectedAircraftModelId = {$selectedAircraftModelIdJson};

function initSelect2() {
    $('.js-select2').select2({
        width: '100%',
        placeholder: 'Select an option',
        allowClear: true
    });
}

function checkFormReady() {
    const manufacturer = $('#manufacturer-dropdown').val();
    const model = $('#model-dropdown').val();
    const file = $('#certificate-file').val();

    $('#submit-btn').prop('disabled', !(manufacturer && model && file));
}

function updateFilePreview() {
    const fileInput = document.getElementById('certificate-file');
    const fileNameLabel = document.getElementById('selected-file-name');

    if (!fileInput || !fileNameLabel) {
        return;
    }

    if (fileInput.files && fileInput.files.length > 0) {
        fileNameLabel.textContent = fileInput.files[0].name;
        fileNameLabel.title = fileInput.files[0].name;
    } else {
        fileNameLabel.textContent = 'No file selected';
        fileNameLabel.title = '';
    }
}

function loadModelsByManufacturer(manufacturer, selectedModelId = '') {
    const modelDropdown = $('#model-dropdown');

    modelDropdown.empty();
    modelDropdown.append(new Option('Loading...', '', true, false));
    modelDropdown.trigger('change.select2');

    if (!manufacturer) {
        modelDropdown.empty();
        modelDropdown.append(new Option('Select Aircraft Model', '', true, false));
        modelDropdown.val('').trigger('change.select2');
        checkFormReady();
        return;
    }

    $.ajax({
        url: getModelsUrl,
        type: 'GET',
        dataType: 'json',
        data: {
            manufacturer: manufacturer
        },
        success: function(data) {
            modelDropdown.empty();
            modelDropdown.append(new Option('Select Aircraft Model', '', true, false));

            $.each(data, function(index, item) {
                const option = new Option(item.name, item.id, false, false);
                modelDropdown.append(option);
            });

            if (selectedModelId) {
                modelDropdown.val(selectedModelId);
            } else {
                modelDropdown.val('');
            }

            modelDropdown.trigger('change.select2');
            checkFormReady();
        },
        error: function() {
            modelDropdown.empty();
            modelDropdown.append(new Option('Unable to load models', '', true, false));
            modelDropdown.val('').trigger('change.select2');
            checkFormReady();
        }
    });
}

$(document).ready(function() {
    initSelect2();

    const form = $('#create-certificate-form');

    if (selectedManufacturer) {
        $('#manufacturer-dropdown').val(selectedManufacturer).trigger('change.select2');
        loadModelsByManufacturer(selectedManufacturer, selectedAircraftModelId);
    }

    $('#manufacturer-dropdown').on('change', function() {
        loadModelsByManufacturer($(this).val(), '');
        checkFormReady();
    });

    $('#model-dropdown').on('change', function() {
        checkFormReady();
    });

    $('#certificate-file').on('change', function() {
        updateFilePreview();
        checkFormReady();
    });

    form.on('beforeSubmit', function(e) {
        if (form.data('creation-confirmed') === true) {
            return true;
        }

        e.preventDefault();

        if (
            !$('#manufacturer-dropdown').val() ||
            !$('#model-dropdown').val() ||
            !$('#certificate-file').val()
        ) {
            Swal.fire({
                title: 'Missing information',
                html: 'Please select manufacturer, aircraft model and certificate file before creating.',
                icon: 'warning',
                confirmButtonText: '<i class="bi bi-check-circle"></i> Got it',
                customClass: {
                    popup: 'swal-create-popup',
                    title: 'swal-create-title',
                    htmlContainer: 'swal-create-html',
                    confirmButton: 'swal-create-confirm'
                }
            });

            return false;
        }

        Swal.fire({
            title: 'Confirm certificate creation?',
            html: 'Please confirm that all certificate information is correct before creating this record.',
            icon: 'question',
            showCancelButton: true,
            // FORM DIALOG ACTIONS: identify certificate creation without Yes/No wording.
            confirmButtonText: '<i class="bi bi-file-earmark-check"></i> Create certificate',
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
                form.data('creation-confirmed', true);
                form.trigger('submit');
            }
        });

        return false;
    });

    updateFilePreview();
    checkFormReady();
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
                        <i class="bi bi-shield-check"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Create a new MRO aircraft certificate by selecting the manufacturer, aircraft model and certificate file.
                </div>
            </div>

            <div class="header-actions">
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Certificates',
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

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
            </div>
        <?php endif; ?>

        <div class="form-layout">

            <!-- Main create form -->
            <div class="form-card">
                <h2 class="section-title">
                    <i class="bi bi-pencil-square text-primary"></i>
                    Certificate Information
                </h2>

                <?php $form = ActiveForm::begin([
                    'id' => 'create-certificate-form',
                    'options' => [
                        'enctype' => 'multipart/form-data',
                    ],
                ]); ?>

                <!-- Hidden MRO ID -->
                <?= Html::activeHiddenInput($certificate, 'mro_id', [
                    'value' => $mroId,
                ]) ?>

                <div class="form-grid">

                    <!-- Manufacturer field -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-tools"></i>
                            Manufacturer <span class="required-mark">*</span>
                        </div>

                        <?= $form->field($certificate, 'manufacturer')
                            ->label(false)
                            ->dropDownList(
                                ArrayHelper::map(
                                    \app\models\AircraftModel::find()
                                        ->select('manufacturer')
                                        ->distinct()
                                        ->orderBy(['manufacturer' => SORT_ASC])
                                        ->all(),
                                    'manufacturer',
                                    'manufacturer'
                                ),
                                [
                                    'prompt' => 'Select Manufacturer',
                                    'id' => 'manufacturer-dropdown',
                                    'class' => 'form-select js-select2',
                                ]
                            ) ?>
                    </div>

                    <!-- Aircraft model field -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-airplane"></i>
                            Aircraft Model <span class="required-mark">*</span>
                        </div>

                        <?= $form->field($certificate, 'aircraft_model_id')
                            ->label(false)
                            ->dropDownList(
                                [],
                                [
                                    'prompt' => 'Select Aircraft Model',
                                    'id' => 'model-dropdown',
                                    'class' => 'form-select js-select2',
                                ]
                            ) ?>
                    </div>

                    <!-- Certificate file field -->
                    <div class="form-field-card full-width">
                        <div class="field-heading">
                            <i class="bi bi-cloud-arrow-up"></i>
                            Certificate File <span class="required-mark">*</span>
                        </div>

                        <!-- Hidden native file input -->
                        <?= $form->field($certificate, 'certificate')
                            ->label(false)
                            ->fileInput([
                                'id' => 'certificate-file',
                                'accept' => '.pdf,.doc,.docx',
                                'class' => 'custom-file-native',
                            ]) ?>

                        <!-- Custom English file input -->
                        <div class="custom-file-box">
                            <label for="certificate-file" class="custom-file-button">
                                <i class="bi bi-upload"></i>
                                Choose File
                            </label>

                            <div class="custom-file-info">
                                <span class="custom-file-name" id="selected-file-name">
                                    No file selected
                                </span>

                                <span class="custom-file-hint">
                                    Accepted formats: PDF, DOC, DOCX
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Form actions -->
                <div class="form-actions">
                    <?= Html::a(
                        '<i class="bi bi-x-circle"></i> Cancel',
                        ['index'],
                        ['class' => 'btn-form-action btn-cancel']
                    ) ?>

                    <?= Html::submitButton(
                        '<i class="bi bi-check-circle"></i> Create Certificate',
                        [
                            'class' => 'btn-form-action btn-create',
                            'id' => 'submit-btn',
                            'disabled' => true,
                        ]
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
                            Select the aircraft manufacturer first. The model list will be loaded automatically.
                        </p>
                    </div>

                    <!-- Aircraft model help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-airplane"></i>
                            Aircraft Model
                        </div>
                        <p class="helper-text">
                            Choose the aircraft model linked to the selected manufacturer.
                        </p>
                    </div>

                    <!-- File help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-file-earmark-text"></i>
                            Certificate File
                        </div>
                        <p class="helper-text">
                            Upload a valid certificate document in PDF, DOC or DOCX format.
                        </p>
                    </div>

                    <!-- MRO help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-building-check"></i>
                            MRO Link
                        </div>
                        <p class="helper-text">
                            The certificate will be linked automatically to the connected MRO account.
                        </p>
                    </div>

                </div>
            </div>

        </div>

    </div>
</main>

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
$this->title = 'Update Certificate';

/**
 * AJAX URL used to load aircraft models by selected manufacturer.
 */
$getModelsUrl = Url::to(['mro-aircraft-certificates/get-models-by-manufacturer']);
$getModelsUrlJson = Json::htmlEncode($getModelsUrl);

/**
 * Detect AircraftModel table columns safely.
 */
$aircraftModelSchema = \app\models\AircraftModel::getTableSchema();

$aircraftModelIdColumn = isset($aircraftModelSchema->columns['aircraft_model_id'])
    ? 'aircraft_model_id'
    : 'id';

$aircraftModelDisplayColumn = null;

foreach (['model', 'name', 'aircraft_model', 'model_name', 'type'] as $possibleColumn) {
    if (isset($aircraftModelSchema->columns[$possibleColumn])) {
        $aircraftModelDisplayColumn = $possibleColumn;
        break;
    }
}

if ($aircraftModelDisplayColumn === null) {
    $aircraftModelDisplayColumn = $aircraftModelIdColumn;
}

/**
 * Prepare current aircraft model and manufacturer.
 */
$currentAircraftModel = null;
$currentManufacturer = null;

if (!empty($certificate->aircraft_model_id)) {
    $currentAircraftModel = \app\models\AircraftModel::findOne($certificate->aircraft_model_id);

    if ($currentAircraftModel) {
        $currentManufacturer = $currentAircraftModel->manufacturer ?? null;

        if (
            !empty($currentManufacturer)
            && method_exists($certificate, 'canSetProperty')
            && $certificate->canSetProperty('manufacturer')
        ) {
            $certificate->manufacturer = $currentManufacturer;
        }
    }
}

/**
 * Prepare manufacturer list.
 */
$manufacturers = ArrayHelper::map(
    \app\models\AircraftModel::find()
        ->select('manufacturer')
        ->distinct()
        ->orderBy(['manufacturer' => SORT_ASC])
        ->all(),
    'manufacturer',
    'manufacturer'
);

/**
 * Prepare models list for the current manufacturer.
 */
$aircraftModels = [];

if (!empty($currentManufacturer)) {
    $models = \app\models\AircraftModel::find()
        ->where(['manufacturer' => $currentManufacturer])
        ->orderBy([$aircraftModelDisplayColumn => SORT_ASC])
        ->all();

    foreach ($models as $aircraftModel) {
        $aircraftModels[$aircraftModel->{$aircraftModelIdColumn}] = $aircraftModel->{$aircraftModelDisplayColumn};
    }
}

/**
 * Prepare selected values after validation errors.
 */
$selectedManufacturer = $currentManufacturer ?? '';
$selectedAircraftModelId = $certificate->aircraft_model_id ?? '';

$selectedManufacturerJson = Json::htmlEncode((string) $selectedManufacturer);
$selectedAircraftModelIdJson = Json::htmlEncode((string) $selectedAircraftModelId);

/**
 * Prepare current MRO ID safely.
 */
$mroId = Yii::$app->user->identity->mro_id
    ?? Yii::$app->session->get('mro_id')
    ?? ($certificate->mro_id ?? null);

/**
 * Prepare current certificate file safely.
 */
$currentFileValue = null;

if (!empty($certificate->certificate)) {
    $currentFileValue = $certificate->certificate;
} elseif (
    method_exists($certificate, 'hasAttribute')
    && $certificate->hasAttribute('file_path')
    && !empty($certificate->file_path)
) {
    $currentFileValue = $certificate->file_path;
}

$currentFileName = !empty($currentFileValue)
    ? basename((string) $currentFileValue)
    : 'No current file';

/**
 * Build current file URL safely.
 */
$buildFileUrl = static function ($file, $defaultFolder = 'uploads') {
    if (empty($file)) {
        return null;
    }

    $file = trim((string) $file);

    if ($file === '') {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $file)) {
        return $file;
    }

    if (strpos($file, '@web/') === 0) {
        return Yii::getAlias('@web') . '/' . ltrim(substr($file, 5), '/');
    }

    if (strpos($file, '/') !== false) {
        return Yii::$app->request->baseUrl . '/' . ltrim($file, '/');
    }

    return Yii::$app->request->baseUrl . '/' . trim($defaultFolder, '/') . '/' . ltrim($file, '/');
};

$currentFileUrl = !empty($currentFileValue)
    ? $buildFileUrl($currentFileValue, 'uploads')
    : null;

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
    color: #f59e0b;
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
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.14) !important;
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
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.14) !important;
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
    background: #f59e0b !important;
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
    background: #f59e0b;
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
    box-shadow: 0 8px 18px rgba(245, 158, 11, 0.22);
    transition: all .2s ease;
}

.custom-file-button:hover {
    background: #d97706;
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

.current-file-box {
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 13px;
}

.current-file-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 12px;
    background: #fff7ed;
    color: #c2410c;
    display: flex;
    align-items: center;
    justify-content: center;
}

.current-file-icon i {
    font-size: 20px;
}

.current-file-text {
    min-width: 0;
}

.current-file-label {
    display: block;
    color: #64748b;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.current-file-name {
    display: block;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.current-file-link {
    color: #c2410c !important;
    text-decoration: none;
}

.current-file-link:hover {
    color: #9a3412 !important;
    text-decoration: underline;
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

/* Warning update button */
.btn-update {
    background: #f59e0b;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(245, 158, 11, 0.24);
}

.btn-update:hover {
    background: #d97706;
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

/* SweetAlert2 warning design */
.swal-update-popup {
    width: 380px !important;
    border-radius: 18px !important;
    padding: 22px !important;
}

.swal-update-title {
    font-size: 20px !important;
    font-weight: 900 !important;
    color: #0f172a !important;
}

.swal-update-html {
    font-size: 14px !important;
    font-weight: 700 !important;
    color: #64748b !important;
}

.swal-update-confirm {
    border-radius: 10px !important;
    padding: 10px 18px !important;
    font-size: 13px !important;
    font-weight: 900 !important;
    background: #f59e0b !important;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(245, 158, 11, 0.25) !important;
}

.swal-update-confirm:hover {
    background: #d97706 !important;
}

.swal-update-cancel {
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
        fileNameLabel.textContent = 'No new file selected';
        fileNameLabel.title = '';
    }
}

function appendModelOption(modelDropdown, itemKey, itemValue) {
    let optionValue = '';
    let optionText = '';

    if (typeof itemValue === 'object' && itemValue !== null) {
        optionValue = itemValue.id || itemValue.aircraft_model_id || itemValue.value || itemKey;
        optionText = itemValue.name || itemValue.model || itemValue.text || itemValue.label || itemValue.value || itemKey;
    } else {
        optionValue = itemKey;
        optionText = itemValue;
    }

    const option = new Option(optionText, optionValue, false, false);
    modelDropdown.append(option);
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

            if (Array.isArray(data)) {
                data.forEach(function(item, index) {
                    appendModelOption(modelDropdown, index, item);
                });
            } else {
                $.each(data, function(key, value) {
                    appendModelOption(modelDropdown, key, value);
                });
            }

            if (selectedModelId) {
                modelDropdown.val(selectedModelId);
            } else {
                modelDropdown.val('');
            }

            modelDropdown.trigger('change.select2');
        },
        error: function() {
            modelDropdown.empty();
            modelDropdown.append(new Option('Unable to load models', '', true, false));
            modelDropdown.val('').trigger('change.select2');
        }
    });
}

$(document).ready(function() {
    initSelect2();

    const form = $('#update-certificate-form');

    if (selectedManufacturer) {
        $('#manufacturer-dropdown').val(selectedManufacturer).trigger('change.select2');

        if ($('#model-dropdown option').length <= 1) {
            loadModelsByManufacturer(selectedManufacturer, selectedAircraftModelId);
        } else {
            $('#model-dropdown').val(selectedAircraftModelId).trigger('change.select2');
        }
    }

    $('#manufacturer-dropdown').on('change', function() {
        loadModelsByManufacturer($(this).val(), '');
    });

    $('#certificate-file').on('change', function() {
        updateFilePreview();
    });

    form.on('beforeSubmit', function(e) {
        if (form.data('update-confirmed') === true) {
            return true;
        }

        e.preventDefault();

        Swal.fire({
            title: 'Confirm certificate update?',
            html: 'Please confirm that the certificate information is correct before saving changes.',
            icon: 'warning',
            showCancelButton: true,
            // FORM DIALOG ACTIONS: name the certificate operation explicitly.
            confirmButtonText: '<i class="bi bi-save"></i> Update certificate',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'swal-update-popup',
                title: 'swal-update-title',
                htmlContainer: 'swal-update-html',
                confirmButton: 'swal-update-confirm',
                cancelButton: 'swal-update-cancel'
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                form.data('update-confirmed', true);
                form.trigger('submit');
            }
        });

        return false;
    });

    updateFilePreview();
});
JS);
?>

<main class="dash-content create-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: #f59e0b;">
                        <i class="bi bi-pencil-square"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Update the MRO aircraft certificate information and replace the certificate file only if needed.
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

            <!-- Main update form -->
            <div class="form-card">
                <h2 class="section-title">
                    <i class="bi bi-pencil-square text-warning"></i>
                    Certificate Information
                </h2>

                <?php $form = ActiveForm::begin([
                    'id' => 'update-certificate-form',
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
                                $manufacturers,
                                [
                                    'prompt' => 'Select Manufacturer',
                                    'id' => 'manufacturer-dropdown',
                                    'class' => 'form-select js-select2',
                                    'value' => $selectedManufacturer,
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
                                $aircraftModels,
                                [
                                    'prompt' => 'Select Aircraft Model',
                                    'id' => 'model-dropdown',
                                    'class' => 'form-select js-select2',
                                    'value' => $selectedAircraftModelId,
                                ]
                            ) ?>
                    </div>

                    <!-- Certificate file field -->
                    <div class="form-field-card full-width">
                        <div class="field-heading">
                            <i class="bi bi-cloud-arrow-up"></i>
                            Replace Certificate File
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
                                    No new file selected
                                </span>

                                <span class="custom-file-hint">
                                    Leave empty to keep the current certificate file.
                                </span>
                            </div>
                        </div>

                        <!-- Current certificate file -->
                        <div class="current-file-box">
                            <div class="current-file-icon">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>

                            <div class="current-file-text">
                                <span class="current-file-label">
                                    Current File
                                </span>

                                <?php if (!empty($currentFileUrl)): ?>
                                    <?= Html::a(
                                        Html::encode($currentFileName),
                                        $currentFileUrl,
                                        [
                                            'class' => 'current-file-name current-file-link',
                                            'target' => '_blank',
                                            'rel' => 'noopener',
                                        ]
                                    ) ?>
                                <?php else: ?>
                                    <span class="current-file-name">
                                        No current file
                                    </span>
                                <?php endif; ?>
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
                        '<i class="bi bi-check-circle"></i> Update Certificate',
                        [
                            'class' => 'btn-form-action btn-update',
                            'id' => 'submit-btn',
                        ]
                    ) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>

            <!-- Form guide -->
            <div class="helper-card">
                <h2 class="section-title">
                    <i class="bi bi-info-circle text-warning"></i>
                    Update Guide
                </h2>

                <div class="helper-list">

                    <!-- Manufacturer help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-tools"></i>
                            Manufacturer
                        </div>
                        <p class="helper-text">
                            Change the manufacturer only if this certificate belongs to another aircraft manufacturer.
                        </p>
                    </div>

                    <!-- Aircraft model help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-airplane"></i>
                            Aircraft Model
                        </div>
                        <p class="helper-text">
                            The aircraft model list is updated automatically when the manufacturer changes.
                        </p>
                    </div>

                    <!-- File help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-file-earmark-text"></i>
                            Certificate File
                        </div>
                        <p class="helper-text">
                            Choose a new file only when you want to replace the current certificate document.
                        </p>
                    </div>

                    <!-- Save help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-check-circle"></i>
                            Confirmation
                        </div>
                        <p class="helper-text">
                            A confirmation popup will appear before saving the updated certificate information.
                        </p>
                    </div>

                </div>
            </div>

        </div>

    </div>
</main>

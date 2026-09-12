<?php

use app\models\Countries;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\JsExpression;

$this->title = 'Add Airport(s) to MRO';

set_time_limit(120);

$urlLoadCities     = Url::to(['/site/load-cities']);
$urlLoadAirports   = Url::to(['/site/load-airports']);
$urlSearchAirports = Url::to(['/mro-airports/search-airports']);

/* Bootstrap Icons */
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

/* SweetAlert2 */
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

$this->registerCss(<<<CSS
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .mro-airports-create-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .mro-airports-create-page .container-fluid {
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
        max-width: 880px;
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

    .mode-switch-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }

    .mode-switch-btn {
        width: 100%;
        border: 1px solid #dbeafe;
        background: #ffffff;
        color: #0f172a;
        border-radius: 16px;
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-align: left;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .mode-switch-btn:hover {
        border-color: #93c5fd;
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
    }

    .mode-switch-btn.active {
        border-color: #2563eb;
        background: linear-gradient(135deg, #2563eb, #0284c7);
        color: #ffffff;
        box-shadow: 0 14px 30px rgba(37, 99, 235, 0.22);
    }

    .mode-switch-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eef2ff;
        color: #2563eb;
        font-size: 20px;
    }

    .mode-switch-btn.active .mode-switch-icon {
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
    }

    .mode-switch-title {
        font-size: 14px;
        font-weight: 800;
        margin-bottom: 3px;
    }

    .mode-switch-desc {
        font-size: 12px;
        font-weight: 600;
        opacity: 0.82;
        line-height: 1.35;
    }

    .selection-panel {
        border: 1px solid #e5eaf3;
        border-radius: 16px;
        padding: 18px;
        background: #ffffff;
        margin-bottom: 18px;
    }

    .selection-panel + .selection-panel {
        margin-top: 14px;
    }

    .panel-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 15px;
    }

    .panel-title i {
        color: #2563eb;
        font-size: 17px;
    }

    .form-row-center {
        display: flex;
        justify-content: center;
    }

    .form-column {
        width: 100%;
        max-width: 700px;
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

    .help-block,
    .invalid-feedback {
        color: #dc2626;
        font-size: 12px;
        font-weight: 600;
        margin-top: 6px;
    }

    /* Kartik Select2 design */
    .mro-airports-create-page .select2-container {
        width: 100% !important;
    }

    .mro-airports-create-page .select2-container--krajee-bs5 .select2-selection,
    .mro-airports-create-page .select2-container--krajee .select2-selection,
    .mro-airports-create-page .select2-container--default .select2-selection--single,
    .mro-airports-create-page .select2-container--default .select2-selection--multiple {
        min-height: 44px !important;
        border: 1px solid #dbeafe !important;
        border-radius: 11px !important;
        background: #ffffff !important;
        box-shadow: none !important;
        transition: 0.2s ease;
    }

    .mro-airports-create-page .select2-container--krajee-bs5.select2-container--focus .select2-selection,
    .mro-airports-create-page .select2-container--krajee.select2-container--focus .select2-selection,
    .mro-airports-create-page .select2-container--default.select2-container--focus .select2-selection--single,
    .mro-airports-create-page .select2-container--default.select2-container--open .select2-selection--single,
    .mro-airports-create-page .select2-container--default.select2-container--focus .select2-selection--multiple,
    .mro-airports-create-page .select2-container--default.select2-container--open .select2-selection--multiple {
        border-color: #38bdf8 !important;
        box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12) !important;
    }

    .mro-airports-create-page .select2-selection__rendered {
        color: #334155 !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        line-height: 42px !important;
    }

    .mro-airports-create-page .select2-selection--multiple .select2-selection__rendered {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 4px !important;
        padding: 5px 8px !important;
        line-height: 1.35 !important;
        max-height: 118px !important;
        overflow-y: auto !important;
    }

    .mro-airports-create-page .select2-selection--multiple .select2-selection__choice {
        white-space: normal !important;
        border-radius: 8px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        margin: 2px !important;
        max-width: 100% !important;
    }

    .mro-airports-create-page .select2-selection.input-error,
    .mro-airports-create-page .input-error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.10) !important;
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

    .btn-submit-airport,
    .btn-back-airport {
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

    .btn-submit-airport {
        background: #2563eb;
        color: #ffffff !important;
        border: 1px solid #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
    }

    .btn-submit-airport:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .btn-back-airport {
        background: #f8fafc;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }

    .btn-back-airport:hover {
        background: #e2e8f0;
        color: #0f172a !important;
        transform: translateY(-1px);
    }

    .alert {
        border-radius: 14px;
        border: none;
        padding: 14px 18px;
        font-weight: 600;
        box-shadow: 0 5px 16px rgba(15, 23, 42, 0.08);
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

    .swal-submit-cancel {
        background: #e2e8f0 !important;
        color: #334155 !important;
    }

    @media (max-width: 768px) {
        .mro-airports-create-page {
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

        .mode-switch-grid {
            grid-template-columns: 1fr;
        }

        .selection-panel {
            padding: 15px;
        }

        .form-column {
            max-width: 100%;
        }

        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-submit-airport,
        .btn-back-airport {
            width: 100%;
        }

        .swal2-popup.custom-submit-popup {
            width: 315px !important;
            padding: 13px 14px 14px !important;
        }
    }
CSS);

$this->registerJs(<<<JS
$(document).ready(function () {

    var currentMode = 'icao';

    // Enable or disable a Select2 field without changing the business logic.
    function enableSelect2Field(selector, enabled) {
        $(selector).prop('disabled', !enabled).trigger('change.select2');
    }

    // Show a Select2 validation error.
    function showSelect2Error(fieldSelector, errorSelector, message) {
        $(fieldSelector).next('.select2-container').find('.select2-selection').addClass('input-error');
        $(errorSelector).html('<i class="bi bi-exclamation-circle"></i><span>' + message + '</span>').css('display', 'flex');
    }

    // Clear a Select2 validation error.
    function clearSelect2Error(fieldSelector, errorSelector) {
        $(fieldSelector).next('.select2-container').find('.select2-selection').removeClass('input-error');
        $(errorSelector).hide().html('');
    }

    // Clear all validation errors.
    function clearAllErrors() {
        clearSelect2Error('#airport-icao-dropdown', '#airport-icao-error');
        clearSelect2Error('#country-dropdown', '#country-error');
        clearSelect2Error('#city-dropdown', '#city-error');
        clearSelect2Error('#airport-dropdown', '#airport-error');
    }

    // Activate ICAO search mode.
    function activateIcaoMode() {
        currentMode = 'icao';

        $('#logic-select').hide();
        $('#logic-icao').show();

        $('#btn-logic-icao').addClass('active');
        $('#btn-logic-select').removeClass('active');

        enableSelect2Field('#country-dropdown', false);
        enableSelect2Field('#city-dropdown', false);
        enableSelect2Field('#airport-dropdown', false);
        enableSelect2Field('#airport-icao-dropdown', true);

        clearAllErrors();
    }

    // Activate Country / City selection mode.
    function activateSelectMode() {
        currentMode = 'select';

        $('#logic-select').show();
        $('#logic-icao').hide();

        $('#btn-logic-select').addClass('active');
        $('#btn-logic-icao').removeClass('active');

        enableSelect2Field('#country-dropdown', true);
        enableSelect2Field('#city-dropdown', true);
        enableSelect2Field('#airport-dropdown', true);
        enableSelect2Field('#airport-icao-dropdown', false);

        clearAllErrors();
    }

    // Validate form according to selected mode.
    function validateAirportForm() {
        var isValid = true;
        clearAllErrors();

        if (currentMode === 'icao') {
            var selectedIcaoAirports = $('#airport-icao-dropdown').val();

            if (!selectedIcaoAirports || selectedIcaoAirports.length === 0) {
                showSelect2Error('#airport-icao-dropdown', '#airport-icao-error', 'Please select at least one airport by ICAO search.');
                isValid = false;
            }
        }

        if (currentMode === 'select') {
            var selectedCountries = $('#country-dropdown').val();
            var selectedCities = $('#city-dropdown').val();
            var selectedAirports = $('#airport-dropdown').val();

            if (!selectedCountries || selectedCountries.length === 0) {
                showSelect2Error('#country-dropdown', '#country-error', 'Please select at least one country.');
                isValid = false;
            }

            if (!selectedCities || selectedCities.length === 0) {
                showSelect2Error('#city-dropdown', '#city-error', 'Please select at least one city to load airports.');
                isValid = false;
            }

            if (!selectedAirports || selectedAirports.length === 0) {
                showSelect2Error('#airport-dropdown', '#airport-error', 'Please select at least one airport.');
                isValid = false;
            }
        }

        return isValid;
    }

    // Mode switch actions.
    $('#btn-logic-icao').on('click', function () {
        activateIcaoMode();
    });

    $('#btn-logic-select').on('click', function () {
        activateSelectMode();
    });

    // Country -> Cities.
    $('#country-dropdown').on('change', function () {
        var selectedCountries = $(this).val();
        clearSelect2Error('#country-dropdown', '#country-error');

        if (selectedCountries && selectedCountries.length > 0) {
            $.ajax({
                url: '$urlLoadCities',
                type: 'POST',
                dataType: 'json',
                data: { country_ids: selectedCountries },
                success: function (data) {
                    var selectedCities = data.cities || {};
                    var sortedCities = Object.keys(selectedCities)
                        .map(function (key) {
                            return { id: key, name: selectedCities[key] };
                        })
                        .sort(function (a, b) {
                            return a.name.localeCompare(b.name);
                        });

                    var cityOptions = '';
                    sortedCities.forEach(function (city) {
                        cityOptions += '<option value="' + city.id + '">' + city.name + '</option>';
                    });

                    $('#city-dropdown').html(cityOptions).trigger('change');
                },
                error: function () {
                    Swal.fire({
                        width: 340,
                        title: 'Error',
                        html: 'Unable to load cities. Please try again.',
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
                }
            });
        } else {
            $('#city-dropdown').empty().trigger('change');
            $('#airport-dropdown').empty().trigger('change');
        }
    });

    // Cities -> Airports.
    $('#city-dropdown').on('change', function () {
        var selectedCities = $(this).val();
        clearSelect2Error('#city-dropdown', '#city-error');

        if (selectedCities && selectedCities.length > 0) {
            $.ajax({
                url: '$urlLoadAirports',
                type: 'POST',
                dataType: 'json',
                data: { city_ids: selectedCities },
                success: function (data) {
                    var selectedAirports = data.airports || {};
                    var sortedAirports = Object.keys(selectedAirports)
                        .map(function (key) {
                            return { id: key, name: selectedAirports[key] };
                        })
                        .sort(function (a, b) {
                            return a.name.localeCompare(b.name);
                        });

                    var airportOptions = '';
                    sortedAirports.forEach(function (airport) {
                        airportOptions += '<option value="' + airport.id + '">' + airport.name + '</option>';
                    });

                    $('#airport-dropdown').html(airportOptions).trigger('change');
                },
                error: function () {
                    Swal.fire({
                        width: 340,
                        title: 'Error',
                        html: 'Unable to load airports. Please try again.',
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
                }
            });
        } else {
            $('#airport-dropdown').empty().trigger('change');
        }
    });

    // Clear airport error when airports are selected.
    $('#airport-dropdown').on('change', function () {
        if ($(this).val() && $(this).val().length > 0) {
            clearSelect2Error('#airport-dropdown', '#airport-error');
        }
    });

    // Clear ICAO error when airports are selected.
    $('#airport-icao-dropdown').on('change', function () {
        if ($(this).val() && $(this).val().length > 0) {
            clearSelect2Error('#airport-icao-dropdown', '#airport-icao-error');
        }
    });

    // Confirm form submission with SweetAlert2 after validation.
    $('#mro-airports-create-form').on('beforeSubmit', function () {
        var form = $(this);

        if (!validateAirportForm()) {
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
            html: 'Are you sure you want to add the selected airport(s) to this MRO?',
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

    // Default mode.
    activateIcaoMode();
});
JS, \yii\web\View::POS_READY);
?>

<main class="dash-content mro-airports-create-page can-form-page">
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
                    Add one or more airports to the MRO network using ICAO search or country/city selection.
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-arrow-left"></i> Back to MRO Airports',
                ['index'],
                ['class' => 'btn btn-back-airport']
            ) ?>
        </div>

        <!-- Flash success message -->
        <?php if (Yii::$app->session->hasFlash('message')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('message')) ?>
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

            <!-- Create MRO airport form card -->
            <div class="content-card">

                <div class="form-section-title">
                    <i class="bi bi-pencil-square"></i>
                    Airport Assignment
                </div>

                <?php $form = ActiveForm::begin([
                    'id' => 'mro-airports-create-form',
                    'options' => [
                        'autocomplete' => 'off',
                    ],
                ]); ?>

                    <!-- Mode selector -->
                    <div class="mode-switch-grid">
                        <button type="button" class="mode-switch-btn active" id="btn-logic-icao">
                            <span class="mode-switch-icon">
                                <i class="bi bi-search"></i>
                            </span>
                            <span>
                                <span class="mode-switch-title d-block">Search by ICAO</span>
                                <span class="mode-switch-desc d-block">Fast search by typing at least 3 letters.</span>
                            </span>
                        </button>

                        <button type="button" class="mode-switch-btn" id="btn-logic-select">
                            <span class="mode-switch-icon">
                                <i class="bi bi-geo-alt"></i>
                            </span>
                            <span>
                                <span class="mode-switch-title d-block">Select by Country / City</span>
                                <span class="mode-switch-desc d-block">Filter airports using country and city.</span>
                            </span>
                        </button>
                    </div>

                    <div class="form-row-center">
                        <div class="form-column">

                            <!-- ICAO Search mode -->
                            <div id="logic-icao">
                                <div class="selection-panel">
                                    <div class="panel-title">
                                        <i class="bi bi-radar"></i>
                                        Search airport by ICAO
                                    </div>

                                    <?= $form->field($model, 'airport_id_icao')
                                        ->widget(Select2::classname(), [
                                            'options' => [
                                                'placeholder' => 'Type ICAO - minimum 3 letters',
                                                'multiple' => true,
                                                'id' => 'airport-icao-dropdown',
                                            ],
                                            'pluginOptions' => [
                                                'allowClear' => true,
                                                'minimumInputLength' => 3,
                                                'ajax' => [
                                                    'url' => $urlSearchAirports,
                                                    'dataType' => 'json',
                                                    'delay' => 250,
                                                    'data' => new JsExpression('function(params) {
                                                        return { q: params.term };
                                                    }'),
                                                    'processResults' => new JsExpression('function(data) {
                                                        return { results: data.results };
                                                    }'),
                                                ],
                                            ],
                                        ])
                                        ->label(
                                            'ICAO Airport<span class="required-star">*</span>',
                                            ['encode' => false]
                                        ); ?>

                                    <div class="field-message">
                                        <i class="bi bi-info-circle"></i>
                                        <span>Type at least 3 letters, then select one or more airports from the results.</span>
                                    </div>

                                    <div id="airport-icao-error" class="field-error-message"></div>
                                </div>
                            </div>

                            <!-- Country / City selection mode -->
                            <div id="logic-select" style="display:none;">

                                <!-- Countries field -->
                                <div class="selection-panel">
                                    <div class="panel-title">
                                        <i class="bi bi-flag"></i>
                                        Country selection
                                    </div>

                                    <?= $form->field($model, 'country_id')
                                        ->widget(Select2::classname(), [
                                            'data' => ArrayHelper::map(
                                                Countries::find()->orderBy('country_name')->all(),
                                                'country_id',
                                                'country_name'
                                            ),
                                            'options' => [
                                                'placeholder' => 'Select Country',
                                                'multiple' => true,
                                                'id' => 'country-dropdown',
                                            ],
                                            'pluginOptions' => [
                                                'allowClear' => true,
                                                'scrollAfterSelect' => true,
                                            ],
                                        ])
                                        ->label(
                                            'Countries<span class="required-star">*</span>',
                                            ['encode' => false]
                                        ); ?>

                                    <div class="field-message">
                                        <i class="bi bi-info-circle"></i>
                                        <span>Select one or more countries to load the related cities.</span>
                                    </div>

                                    <div id="country-error" class="field-error-message"></div>
                                </div>

                                <!-- Cities field -->
                                <div class="selection-panel">
                                    <div class="panel-title">
                                        <i class="bi bi-buildings"></i>
                                        City selection
                                    </div>

                                    <?= $form->field($model, 'city_id')
                                        ->widget(Select2::classname(), [
                                            'data' => [],
                                            'options' => [
                                                'placeholder' => 'Select City',
                                                'multiple' => true,
                                                'id' => 'city-dropdown',
                                            ],
                                            'pluginOptions' => [
                                                'allowClear' => false,
                                                'scrollAfterSelect' => true,
                                            ],
                                        ])
                                        ->label(
                                            'Cities<span class="required-star">*</span>',
                                            ['encode' => false]
                                        ); ?>

                                    <div class="field-message">
                                        <i class="bi bi-info-circle"></i>
                                        <span>Select one or more cities to load the available airports.</span>
                                    </div>

                                    <div id="city-error" class="field-error-message"></div>
                                </div>

                                <!-- Airports field -->
                                <div class="selection-panel">
                                    <div class="panel-title">
                                        <i class="bi bi-airplane"></i>
                                        Airport selection
                                    </div>

                                    <?= $form->field($model, 'airport_id')
                                        ->widget(Select2::classname(), [
                                            'data' => [],
                                            'options' => [
                                                'placeholder' => 'Select Airport',
                                                'multiple' => true,
                                                'id' => 'airport-dropdown',
                                            ],
                                            'pluginOptions' => [
                                                'allowClear' => true,
                                                'scrollAfterSelect' => true,
                                            ],
                                        ])
                                        ->label(
                                            'Airports<span class="required-star">*</span>',
                                            ['encode' => false]
                                        ); ?>

                                    <div class="field-message">
                                        <i class="bi bi-info-circle"></i>
                                        <span>Select one or more airports to attach to this MRO.</span>
                                    </div>

                                    <div id="airport-error" class="field-error-message"></div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Form actions -->
                    <div class="form-actions">
                        <?= Html::submitButton(
                            '<i class="bi bi-check-circle"></i> Add Airport(s)',
                            ['class' => 'btn btn-submit-airport']
                        ) ?>

                        <?= Html::a(
                            '<i class="bi bi-arrow-left-circle"></i> Cancel',
                            Yii::$app->request->referrer ?: ['index'],
                            ['class' => 'btn btn-back-airport']
                        ) ?>
                    </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    </div>
</main>

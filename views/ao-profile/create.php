<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Json;

$this->title = 'Create AO Profile';

/* Bootstrap Icons */
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

/* SweetAlert2 */
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

/* Prepare aircraft models for JavaScript */
$aircraftModelsJs = [];
foreach ($models as $modelItem) {
    $aircraftModelsJs[] = [
        'aircraft_model_id' => $modelItem->aircraft_model_id,
        'manufacturer' => $modelItem->manufacturer,
        'model' => $modelItem->model,
    ];
}

$aircraftModelsJson = Json::htmlEncode($aircraftModelsJs);
$loadCitiesUrl = Url::to(['/ao-profile/load-cities']);
$loadCitiesUrlJs = Json::htmlEncode($loadCitiesUrl);

$this->registerCss(<<<CSS
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .ao-create-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .ao-create-page .container-fluid {
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
        max-width: 950px;
        background: #ffffff;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5eaf3;
        margin: 0 auto;
    }

    .ao-stepper {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-bottom: 24px;
        position: relative;
    }

    .ao-stepper::before {
        content: "";
        position: absolute;
        top: 28px;
        left: 7%;
        right: 7%;
        height: 2px;
        background: #e5eaf3;
        z-index: 1;
    }

    .step-item {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        text-align: center;
        cursor: pointer;
        user-select: none;
    }

    .step-icon {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 23px;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.12);
        border: 4px solid #ffffff;
        transition: 0.2s ease;
    }

    .step-item[data-color="blue"] .step-icon { background: #2563eb; }
    .step-item[data-color="sky"] .step-icon { background: #0ea5e9; }
    .step-item[data-color="green"] .step-icon { background: #22c55e; }
    .step-item[data-color="orange"] .step-icon { background: #f59e0b; }

    .step-title {
        font-size: 12px;
        font-weight: 900;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .step-item.active .step-icon {
        transform: translateY(-2px) scale(1.04);
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.22);
    }

    .step-item.active .step-title {
        color: #0f172a;
    }

    .step-item.completed .step-icon {
        background: #16a34a !important;
    }

    .step-item.has-error .step-icon {
        background: #ef4444 !important;
        animation: stepPulse 0.6s ease;
    }

    @keyframes stepPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.08); }
        100% { transform: scale(1); }
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

    .form-row-center {
        display: flex;
        justify-content: center;
    }

    .form-column {
        width: 100%;
        max-width: 680px;
    }

    .step-panel {
        display: none;
    }

    .step-panel.active {
        display: block;
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

    .required-star {
        color: #ef4444;
        margin-left: 3px;
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
    .field-error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.10) !important;
    }

    .error-message,
    .client-error {
        color: #dc2626;
        font-size: 12px;
        font-weight: 700;
        margin-top: 6px;
        display: none;
    }

    .client-error.show,
    .error-message.show {
        display: block;
    }


    /* Input helper messages */
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
        flex: 0 0 auto;
    }

    .field-message.warning i {
        color: #f59e0b;
    }

    .field-message.success i {
        color: #16a34a;
    }

    /* English custom file input */
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

    .step-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 18px;
        padding-top: 20px;
        border-top: 1px solid #eef2f7;
    }

    .step-actions-right {
        margin-left: auto;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-step,
    .btn-submit-ao,
    .btn-back-ao {
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

    .btn-next,
    .btn-submit-ao {
        background: #2563eb;
        color: #ffffff !important;
        border: 1px solid #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
    }

    .btn-next:hover,
    .btn-submit-ao:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .btn-prev,
    .btn-back-ao {
        background: #f8fafc;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }

    .btn-prev:hover,
    .btn-back-ao:hover {
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
        .ao-create-page {
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

        .ao-stepper {
            grid-template-columns: repeat(2, 1fr);
        }

        .ao-stepper::before {
            display: none;
        }

        .step-icon {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            font-size: 20px;
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

        .step-actions,
        .step-actions-right {
            flex-direction: column;
            align-items: stretch;
            width: 100%;
        }

        .btn-step,
        .btn-submit-ao,
        .btn-back-ao {
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
CSS);

$this->registerJs(<<<JS
$(document).ready(function () {
    var currentStep = 1;
    var totalSteps = 4;
    var aircraftModels = $aircraftModelsJson;
    var loadCitiesUrl = $loadCitiesUrlJs;

    function bindCustomFileInput(inputId, buttonId, fileNameId) {
        $('#' + buttonId).on('click', function () {
            $('#' + inputId).trigger('click');
        });

        $('#' + inputId).on('change', function () {
            var fileName = this.files && this.files.length > 0 ? this.files[0].name : 'No file selected';

            $('#' + fileNameId)
                .text(fileName)
                .toggleClass('has-file', this.files && this.files.length > 0);
        });
    }

    bindCustomFileInput('profile-photo-input', 'profile-photo-button', 'profile-photo-name');

    function fieldValue(id) {
        var el = $('#' + id);
        return el.length ? String(el.val() || '').trim() : '';
    }

    function setFieldError(id, message) {
        var el = $('#' + id);
        var errorEl = $('#' + id + '-client-error');

        el.addClass('field-error');

        if (errorEl.length) {
            errorEl.text(message).addClass('show');
        }
    }

    function clearFieldError(id) {
        var el = $('#' + id);
        var errorEl = $('#' + id + '-client-error');

        el.removeClass('field-error');

        if (errorEl.length) {
            errorEl.text('').removeClass('show');
        }
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }


    var fieldMessages = {
        username: 'Please enter the AO username.',
        password: 'Please enter a password.',
        confirm_password: 'Please confirm the password.',
        email: 'Please enter a valid email address.',
        first_name: 'Please enter the first name.',
        last_name: 'Please enter the last name.',
        contact_number: 'Please enter the contact number.',
        'country-select': 'Please select a country.',
        'city-select': 'Please select a city.',
        address: 'Please enter the address.',
        zip_code: 'Please enter the zip code.',
        'manufacturer-dropdown': 'Please select the aircraft manufacturer.',
        'model-dropdown': 'Please select the aircraft model.',
        serial_number: 'Please enter the aircraft serial number.',
        registration_number: 'Please enter the aircraft registration number.',
        'certificate-type-dropdown': 'Please select the aircraft certificate type.'
    };

    function validationMessage(id) {
        return fieldMessages[id] || 'This field is required.';
    }

    function validateStep(step, showErrors) {
        var valid = true;
        var requiredByStep = {
            1: ['username', 'password', 'confirm_password', 'email'],
            2: ['first_name', 'last_name', 'contact_number'],
            3: ['country-select', 'city-select', 'address', 'zip_code'],
            4: ['manufacturer-dropdown', 'model-dropdown', 'serial_number', 'registration_number', 'certificate-type-dropdown']
        };

        $('.step-item[data-step="' + step + '"]').removeClass('has-error');

        (requiredByStep[step] || []).forEach(function (id) {
            clearFieldError(id);

            if (!fieldValue(id)) {
                valid = false;

                if (showErrors) {
                    setFieldError(id, validationMessage(id));
                }
            }
        });

        if (step === 1) {
            clearFieldError('confirm_password');
            clearFieldError('email');

            if (fieldValue('password') && fieldValue('confirm_password') && fieldValue('password') !== fieldValue('confirm_password')) {
                valid = false;

                if (showErrors) {
                    setFieldError('confirm_password', 'Passwords do not match.');
                    $('#confirm-password-error').text('Passwords do not match.').addClass('show');
                }
            }

            if (fieldValue('email') && !isValidEmail(fieldValue('email'))) {
                valid = false;

                if (showErrors) {
                    setFieldError('email', 'Invalid email format.');
                    $('#email-error').text('Invalid email format.').addClass('show');
                }
            }
        }

        if (!valid && showErrors) {
            $('.step-item[data-step="' + step + '"]').addClass('has-error');
        }

        return valid;
    }

    function showStep(step) {
        currentStep = step;

        $('.step-panel').removeClass('active');
        $('.step-panel[data-step="' + step + '"]').addClass('active');

        $('.step-item').removeClass('active completed has-error');

        $('.step-item').each(function () {
            var itemStep = parseInt($(this).data('step'), 10);

            if (itemStep < step) {
                $(this).addClass('completed');
                $(this).find('.step-icon i').attr('class', 'bi bi-check-lg');
            } else if (itemStep === step) {
                $(this).addClass('active');
                restoreStepIcon($(this));
            } else {
                restoreStepIcon($(this));
            }
        });
    }

    function restoreStepIcon(stepItem) {
        var icon = stepItem.data('icon');
        stepItem.find('.step-icon i').attr('class', icon);
    }

    $('.btn-next').on('click', function () {
        if (!validateStep(currentStep, true)) {
            return;
        }

        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        }
    });

    $('.btn-prev').on('click', function () {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    $('.step-item').on('click', function () {
        var requestedStep = parseInt($(this).data('step'), 10);

        if (requestedStep <= currentStep) {
            showStep(requestedStep);
            return;
        }

        for (var i = currentStep; i < requestedStep; i++) {
            if (!validateStep(i, true)) {
                showStep(i);
                return;
            }
        }

        showStep(requestedStep);
    });

    // Remove inline errors while typing/changing.
    $(document).on('input change', '.form-control, .form-select', function () {
        clearFieldError(this.id);

        if (this.id === 'confirm_password') {
            $('#confirm-password-error').text('').removeClass('show');
        }

        if (this.id === 'email') {
            $('#email-error').text('').removeClass('show');
        }
    });

    // Country -> City kept compatible with your old working endpoint.
    $('#country-select').on('change', function () {
        var countryId = $(this).val();
        var citySelect = $('#city-select');

        citySelect.empty();
        citySelect.append('<option value="">Select City</option>');

        if (!countryId) {
            return;
        }

        fetch(loadCitiesUrl + '?countryId=' + encodeURIComponent(countryId))
            .then(function (response) { return response.json(); })
            .then(function (data) {
                citySelect.empty();
                citySelect.append('<option value="">Select City</option>');

                if (Array.isArray(data)) {
                    data.forEach(function (city) {
                        citySelect.append('<option value="' + city.id + '">' + city.text + '</option>');
                    });
                } else if (data && data.cities && Array.isArray(data.cities)) {
                    data.cities.forEach(function (city) {
                        citySelect.append('<option value="' + city.id + '">' + city.text + '</option>');
                    });
                }
            })
            .catch(function (error) {
                console.error('Error loading cities:', error);
                citySelect.empty();
                citySelect.append('<option value="">Error loading cities</option>');
            });
    });

    // Manufacturer -> Model with hidden aircraft_model_id update.
    function updateModelDropdown(selectedManufacturer, selectedModel) {
        var modelDropdown = $('#model-dropdown');
        var aircraftModelIdInput = $('#aircraft-model-id');

        modelDropdown.empty();
        modelDropdown.append('<option value="">Select Model</option>');
        aircraftModelIdInput.val('');

        aircraftModels.forEach(function (item) {
            if (item.manufacturer === selectedManufacturer) {
                var option = $('<option></option>')
                    .val(item.model)
                    .text(item.model)
                    .attr('data-id', item.aircraft_model_id);

                if (selectedModel && selectedModel === item.model) {
                    option.prop('selected', true);
                    aircraftModelIdInput.val(item.aircraft_model_id);
                }

                modelDropdown.append(option);
            }
        });
    }

    $('#manufacturer-dropdown').on('change', function () {
        updateModelDropdown($(this).val(), null);
    });

    $('#model-dropdown').on('change', function () {
        var selectedOption = $(this).find('option:selected');
        $('#aircraft-model-id').val(selectedOption.data('id') || '');
    });

    if ($('#manufacturer-dropdown').val()) {
        updateModelDropdown($('#manufacturer-dropdown').val(), $('#model-dropdown').data('selected-model'));
    }

    $('#ao-create-form').on('beforeSubmit', function () {
        var form = $(this);
        var firstInvalidStep = null;

        for (var i = 1; i <= totalSteps; i++) {
            if (!validateStep(i, true)) {
                firstInvalidStep = i;
                break;
            }
        }

        if (firstInvalidStep !== null) {
            showStep(firstInvalidStep);
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
            html: 'Are you sure you want to create this AO profile?',
            icon: 'question',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            confirmButtonText: '<i class="bi bi-check-circle-fill"></i> Create',
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

    showStep(1);
});
JS, \yii\web\View::POS_READY);
?>

<main class="dash-content ao-create-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-building"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Create a new aircraft owner profile with account, location, and aircraft information.
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-arrow-left"></i> Back to AO Profile',
                ['index'],
                ['class' => 'btn btn-back-ao']
            ) ?>
        </div>

        <!-- Flash messages -->
        <?php if (Yii::$app->session->hasFlash('message')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('message')) ?>
            </div>
        <?php endif; ?>

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

        <div class="form-center-wrapper">
            <div class="content-card">

                <!-- Colored stepper -->
                <div class="ao-stepper">
                    <div class="step-item active" data-step="1" data-color="blue" data-icon="bi bi-person-circle">
                        <span class="step-icon"><i class="bi bi-person-circle"></i></span>
                        <span class="step-title">Account</span>
                    </div>

                    <div class="step-item" data-step="2" data-color="sky" data-icon="bi bi-card-text">
                        <span class="step-icon"><i class="bi bi-card-text"></i></span>
                        <span class="step-title">Profile</span>
                    </div>

                    <div class="step-item" data-step="3" data-color="green" data-icon="bi bi-geo-alt">
                        <span class="step-icon"><i class="bi bi-geo-alt"></i></span>
                        <span class="step-title">Location</span>
                    </div>

                    <div class="step-item" data-step="4" data-color="orange" data-icon="bi bi-airplane-engines">
                        <span class="step-icon"><i class="bi bi-airplane-engines"></i></span>
                        <span class="step-title">Aircraft</span>
                    </div>
                </div>

                <?php $form = ActiveForm::begin([
                    'id' => 'ao-create-form',
                    'enableClientValidation' => true,
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'autocomplete' => 'off',
                    ],
                ]); ?>

                    <div class="form-row-center">
                        <div class="form-column">

                            <!-- Step 1: Account -->
                            <div class="step-panel active" data-step="1">
                                <div class="form-section-title">
                                    <i class="bi bi-person-circle"></i>
                                    Account Information
                                </div>

                                <?= $form->field($model, 'username')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'username',
                                    'placeholder' => 'Enter username',
                                ])->label('Username<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-info-circle"></i><span>Use a clear username for the AO account. It will be used for login and identification.</span></div>
                                <div class="client-error" id="username-client-error"></div>

                                <?= $form->field($model, 'password')->passwordInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'password',
                                    'placeholder' => 'Enter password',
                                ])->label('Password<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-shield-lock"></i><span>Use a secure password. It should be easy for the user to remember but difficult to guess.</span></div>
                                <div class="client-error" id="password-client-error"></div>

                                <?= $form->field($model, 'confirm_password')->passwordInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'confirm_password',
                                    'placeholder' => 'Confirm password',
                                ])->label('Confirm Password<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div id="confirm-password-error" class="error-message"></div>
                                <div class="field-message"><i class="bi bi-check2-circle"></i><span>Re-enter the same password to confirm it before continuing.</span></div>
                                <div class="client-error" id="confirm_password-client-error"></div>

                                <?= $form->field($model, 'email')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'email',
                                    'placeholder' => 'Enter email address',
                                ])->label('Email<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div id="email-error" class="error-message"></div>
                                <div class="field-message"><i class="bi bi-envelope-check"></i><span>Enter a valid email address. It can be used for notifications and password recovery.</span></div>
                                <div class="client-error" id="email-client-error"></div>

                                <div class="step-actions">
                                    <div></div>
                                    <div class="step-actions-right">
                                        <button type="button" class="btn btn-step btn-next">
                                            Next <i class="bi bi-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Profile -->
                            <div class="step-panel" data-step="2">
                                <div class="form-section-title">
                                    <i class="bi bi-card-text"></i>
                                    Profile Information
                                </div>

                                <?= $form->field($model, 'first_name')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'first_name',
                                    'placeholder' => 'Enter first name',
                                ])->label('First Name<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-person"></i><span>Enter the AO representative first name.</span></div>
                                <div class="client-error" id="first_name-client-error"></div>

                                <?= $form->field($model, 'last_name')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'last_name',
                                    'placeholder' => 'Enter last name',
                                ])->label('Last Name<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-person"></i><span>Enter the AO representative last name.</span></div>
                                <div class="client-error" id="last_name-client-error"></div>

                                <?= $form->field($model, 'contact_number')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'contact_number',
                                    'placeholder' => 'Enter contact number',
                                ])->label('Contact Number<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-telephone"></i><span>Enter a reachable phone number, including country code if needed.</span></div>
                                <div class="client-error" id="contact_number-client-error"></div>

                                <?= $form->field($model, 'company_name')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'company_name',
                                    'placeholder' => 'Enter company name',
                                ])->label('Company Name', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-building"></i><span>Optional: enter the company name associated with this AO profile.</span></div>

                                <?= $form->field($model, 'profile_photo', [
                                    'template' => "{label}\n
                                        <div class=\"custom-file-box\">
                                            <button type=\"button\" class=\"custom-file-button\" id=\"profile-photo-button\">
                                                <i class=\"bi bi-upload\"></i> Choose File
                                            </button>
                                            <span class=\"custom-file-name\" id=\"profile-photo-name\">No file selected</span>
                                        </div>
                                        {input}\n{error}",
                                ])->fileInput([
                                    'id' => 'profile-photo-input',
                                    'class' => 'custom-file-input-hidden',
                                    'accept' => 'image/*',
                                ])->label('Profile Photo', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-image"></i><span>Optional: upload a profile image. Accepted image files depend on your model validation rules.</span></div>

                                <div class="step-actions">
                                    <button type="button" class="btn btn-step btn-prev">
                                        <i class="bi bi-arrow-left"></i> Previous
                                    </button>
                                    <div class="step-actions-right">
                                        <button type="button" class="btn btn-step btn-next">
                                            Next <i class="bi bi-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Location -->
                            <div class="step-panel" data-step="3">
                                <div class="form-section-title">
                                    <i class="bi bi-geo-alt"></i>
                                    Location Information
                                </div>

                                <?= $form->field($model, 'country_id')->dropDownList(
                                    ArrayHelper::map($countries, 'country_id', 'country_name'),
                                    [
                                        'prompt' => 'Select Country',
                                        'id' => 'country-select',
                                        'class' => 'form-select',
                                    ]
                                )->label('Country<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-globe2"></i><span>Select the country first. The city list will be updated automatically.</span></div>
                                <div class="client-error" id="country-select-client-error"></div>

                                <?= $form->field($model, 'city_id')->dropDownList(
                                    [],
                                    [
                                        'prompt' => 'Select City',
                                        'id' => 'city-select',
                                        'class' => 'form-select',
                                    ]
                                )->label('City<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-buildings"></i><span>Select the city related to the AO profile location.</span></div>
                                <div class="client-error" id="city-select-client-error"></div>

                                <div id="selected-city-value" class="selected-city-value" style="display:none;"></div>

                                <?= $form->field($model, 'address')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'address',
                                    'placeholder' => 'Enter address',
                                ])->label('Address<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-geo"></i><span>Enter the full address used for AO profile records.</span></div>
                                <div class="client-error" id="address-client-error"></div>

                                <?= $form->field($model, 'zip_code')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'zip_code',
                                    'placeholder' => 'Enter zip code',
                                ])->label('Zip Code<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-mailbox"></i><span>Enter the postal or ZIP code for this address.</span></div>
                                <div class="client-error" id="zip_code-client-error"></div>

                                <div class="step-actions">
                                    <button type="button" class="btn btn-step btn-prev">
                                        <i class="bi bi-arrow-left"></i> Previous
                                    </button>
                                    <div class="step-actions-right">
                                        <button type="button" class="btn btn-step btn-next">
                                            Next <i class="bi bi-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Aircraft -->
                            <div class="step-panel" data-step="4">
                                <div class="form-section-title">
                                    <i class="bi bi-airplane-engines"></i>
                                    Aircraft Information
                                </div>

                                <?= $form->field($airplaneModel, 'manufacturer')->dropDownList(
                                    ArrayHelper::map($manufacturers, 'manufacturer', 'manufacturer'),
                                    [
                                        'prompt' => 'Select Manufacturer',
                                        'class' => 'form-select',
                                        'id' => 'manufacturer-dropdown',
                                    ]
                                )->label('Manufacturer<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-airplane-engines"></i><span>Select the aircraft manufacturer. The model list will be updated automatically.</span></div>
                                <div class="client-error" id="manufacturer-dropdown-client-error"></div>

                                <?= $form->field($airplaneModel, 'aircraft_model_id')->hiddenInput([
                                    'id' => 'aircraft-model-id',
                                ])->label(false) ?>

                                <?= $form->field($airplaneModel, 'model')->dropDownList(
                                    [],
                                    [
                                        'prompt' => 'Select Model',
                                        'class' => 'form-select',
                                        'id' => 'model-dropdown',
                                        'data-selected-model' => $airplaneModel->model ?? '',
                                    ]
                                )->label('Model<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-airplane"></i><span>Select the aircraft model after choosing the manufacturer.</span></div>
                                <div class="client-error" id="model-dropdown-client-error"></div>

                                <?= $form->field($airplaneModel, 'serial_number')->textInput([
                                    'class' => 'form-control',
                                    'id' => 'serial_number',
                                    'placeholder' => 'Enter serial number',
                                ])->label('Serial Number<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-upc-scan"></i><span>Enter the manufacturer serial number of the aircraft.</span></div>
                                <div class="client-error" id="serial_number-client-error"></div>

                                <?= $form->field($airplaneModel, 'registration_number')->textInput([
                                    'class' => 'form-control',
                                    'id' => 'registration_number',
                                    'placeholder' => 'Enter registration number',
                                ])->label('Registration Number<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-card-checklist"></i><span>Enter the official aircraft registration number.</span></div>
                                <div class="client-error" id="registration_number-client-error"></div>

                                <?= $form->field($airplaneModel, 'certificate_type_id')->dropDownList(
                                    ArrayHelper::map(
                                        \app\models\CertificateTypes::find()->orderBy(['type' => SORT_ASC])->all(),
                                        'certificate_type_id',
                                        'type'
                                    ),
                                    [
                                        'prompt' => 'Select Certificate Type',
                                        'id' => 'certificate-type-dropdown',
                                        'class' => 'form-select',
                                    ]
                                )->label('Certificate Type<span class="required-star">*</span>', ['encode' => false]) ?>
                                <div class="field-message"><i class="bi bi-file-earmark-check"></i><span>Select the certificate type linked to this aircraft.</span></div>
                                <div class="client-error" id="certificate-type-dropdown-client-error"></div>

                                <div class="step-actions">
                                    <button type="button" class="btn btn-step btn-prev">
                                        <i class="bi bi-arrow-left"></i> Previous
                                    </button>
                                    <div class="step-actions-right">
                                        <?= Html::submitButton(
                                            '<i class="bi bi-check-circle"></i> Create AO Profile',
                                            ['class' => 'btn btn-submit-ao']
                                        ) ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    </div>
</main>

<?php

use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\select2\Select2;

$this->title = 'Create MRO Profile';

/* Bootstrap Icons */
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

/* SweetAlert2 */
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

/* Working routes */
$loadCitiesUrl = Url::to(['/mro-profile/load-cities']);
$modelsByManufacturerUrl = Url::to(['/mro-profile/get-models-by-manufacturer']);
$certificateTypeUrl = Url::to(['certificate-types/get-certificate-type-id']);

$this->registerCss("
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .mro-create-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .mro-create-page .container-fluid {
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
        max-width: 920px;
        background: #ffffff;
        border-radius: 18px;
        padding: 26px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5eaf3;
        margin: 0 auto;
    }

    .form-section {
        margin-bottom: 26px;
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
    .has-error .form-select {
        border-color: #ef4444 !important;
    }

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

    .select2-container .select2-selection--single {
        min-height: 44px !important;
        border: 1px solid #dbeafe !important;
        border-radius: 11px !important;
        background: #ffffff !important;
        box-shadow: none !important;
    }

    .select2-container .select2-selection__rendered {
        line-height: 42px !important;
        color: #334155 !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        padding-left: 13px !important;
    }

    .select2-container .select2-selection__arrow {
        height: 42px !important;
        right: 8px !important;
    }

    .form-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 12px;
        padding-top: 22px;
        border-top: 1px solid #eef2f7;
    }

    .btn-submit-mro,
    .btn-back-mro {
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

    .btn-submit-mro {
        background: #2563eb;
        color: #ffffff !important;
        border: 1px solid #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
    }

    .btn-submit-mro:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .btn-back-mro {
        background: #f8fafc;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }

    .btn-back-mro:hover {
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

    .error-message {
        color: #dc2626;
        font-size: 12px;
        font-weight: 600;
        margin-top: 6px;
    }

    /* Manual step validation style */
    .step-field-invalid {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12) !important;
    }

    .mro-step-item.locked {
        opacity: 0.55;
        cursor: not-allowed;
    }

    .mro-step-item.locked:hover {
        transform: none;
        box-shadow: none;
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


    /* Colored stepper navigation */
    .mro-stepper {
        margin-bottom: 24px;
        padding: 16px;
        border: 1px solid #e5eaf3;
        border-radius: 16px;
        background: linear-gradient(135deg, #ffffff, #f8fbff);
    }

    .mro-stepper-track {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 10px;
        position: relative;
    }

    .mro-step-item {
        position: relative;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        border-radius: 14px;
        padding: 12px 8px;
        min-height: 84px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 7px;
        cursor: pointer;
        transition: 0.2s ease;
        user-select: none;
    }

    .mro-step-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
    }

    .mro-step-icon {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
        transition: 0.2s ease;
    }

    .mro-step-label {
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        text-align: center;
        line-height: 1.2;
    }

    .mro-step-item[data-color='blue'] .mro-step-icon { background: #2563eb; }
    .mro-step-item[data-color='cyan'] .mro-step-icon { background: #0891b2; }
    .mro-step-item[data-color='green'] .mro-step-icon { background: #16a34a; }
    .mro-step-item[data-color='orange'] .mro-step-icon { background: #f97316; }
    .mro-step-item[data-color='purple'] .mro-step-icon { background: #7c3aed; }
    .mro-step-item[data-color='slate'] .mro-step-icon { background: #475569; }

    .mro-step-item.active {
        border-color: #2563eb;
        background: #eff6ff;
        box-shadow: 0 12px 26px rgba(37, 99, 235, 0.16);
    }

    .mro-step-item.active .mro-step-label { color: #1d4ed8; }

    .mro-step-item.completed {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .mro-step-item.completed .mro-step-icon { background: #16a34a !important; }
    .mro-step-item.completed .mro-step-label { color: #166534; }

    .mro-step-panel {
        display: none;
        min-height: 410px;
        animation: stepFadeIn 0.18s ease;
    }

    .mro-step-panel.active { display: block; }

    @keyframes stepFadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .stepper-actions { justify-content: space-between; }

    .stepper-actions-left,
    .stepper-actions-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-step-prev,
    .btn-step-next {
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
        border: 1px solid #cbd5e1;
    }

    .btn-step-prev {
        background: #ffffff;
        color: #334155 !important;
    }

    .btn-step-prev:hover {
        background: #f1f5f9;
        color: #0f172a !important;
        transform: translateY(-1px);
    }

    .btn-step-next {
        background: #0ea5e9;
        color: #ffffff !important;
        border-color: #0ea5e9;
        box-shadow: 0 8px 18px rgba(14, 165, 233, 0.18);
    }

    .btn-step-next:hover {
        background: #0284c7;
        border-color: #0284c7;
        color: #ffffff !important;
        transform: translateY(-1px);
    }


    @media (max-width: 768px) {
        .mro-create-page {
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

        .btn-submit-mro,
        .btn-back-mro {
            width: 100%;
        }


        .mro-stepper-track {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .mro-step-item {
            min-height: 74px;
            padding: 10px 8px;
        }

        .mro-step-icon {
            width: 32px;
            height: 32px;
            font-size: 15px;
        }

        .mro-step-label {
            font-size: 10px;
        }

        .mro-step-panel {
            min-height: unset;
        }

        .stepper-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .stepper-actions-left,
        .stepper-actions-right {
            width: 100%;
            justify-content: center;
        }

        .btn-step-prev,
        .btn-step-next {
            width: 100%;
        }
    }
");

$this->registerJs(<<<JS
$(document).ready(function () {


    // Colored stepper navigation with validation.
    var currentStep = 0;
    var totalSteps = $('.mro-step-panel').length;
    var maxReachedStep = 0;

    function showSmallAlert(title, message, icon) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                width: 340,
                title: title,
                html: message,
                icon: icon || 'warning',
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
            alert(message.replace(/<[^>]*>/g, ''));
        }
    }

    function clearStepValidationErrors() {
        $('.step-field-invalid').removeClass('step-field-invalid');
        $('#confirm-password-error').hide().text('');
        $('#email-error').hide().text('');
    }

    function setFieldInvalid(selector, message, errorSelector) {
        var field = $(selector);

        field.addClass('step-field-invalid');

        if (errorSelector) {
            $(errorSelector).text(message).show();
        }
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function validateAccountStep(showAlert) {
        clearStepValidationErrors();

        var username = $.trim($('#mroprofile-username').val() || '');
        var password = $.trim($('#password').val() || '');
        var confirmPassword = $.trim($('#confirm_password').val() || '');
        var email = $.trim($('#email').val() || '');
        var firstInvalid = null;
        var message = '';

        if (username === '') {
            setFieldInvalid('#mroprofile-username', 'Username is required.');
            firstInvalid = firstInvalid || '#mroprofile-username';
            message = message || 'Please enter the username before continuing.';
        }

        if (password === '') {
            setFieldInvalid('#password', 'Password is required.');
            firstInvalid = firstInvalid || '#password';
            message = message || 'Please enter the password before continuing.';
        }

        if (confirmPassword === '') {
            setFieldInvalid('#confirm_password', 'Please confirm the password.', '#confirm-password-error');
            firstInvalid = firstInvalid || '#confirm_password';
            message = message || 'Please confirm the password before continuing.';
        }

        if (password !== '' && confirmPassword !== '' && password !== confirmPassword) {
            setFieldInvalid('#confirm_password', 'Confirm Password must match Password.', '#confirm-password-error');
            firstInvalid = firstInvalid || '#confirm_password';
            message = 'Confirm Password must match Password before you can continue.';
        }

        if (email === '') {
            setFieldInvalid('#email', 'Email is required.', '#email-error');
            firstInvalid = firstInvalid || '#email';
            message = message || 'Please enter the email before continuing.';
        } else if (!isValidEmail(email)) {
            setFieldInvalid('#email', 'Please enter a valid email address.', '#email-error');
            firstInvalid = firstInvalid || '#email';
            message = message || 'Please enter a valid email address before continuing.';
        }

        if (firstInvalid !== null) {
            $(firstInvalid).focus();

            if (showAlert) {
                showSmallAlert('Validation required', message, 'warning');
            }

            return false;
        }

        return true;
    }

    function validateCurrentStep(showAlert) {
        if (currentStep === 0) {
            return validateAccountStep(showAlert);
        }

        return true;
    }

    function refreshLockedSteps() {
        $('.mro-step-item').each(function () {
            var itemStep = parseInt($(this).data('step'), 10);
            $(this).toggleClass('locked', itemStep > maxReachedStep);
        });
    }

    function showStep(stepIndex) {
        if (stepIndex < 0 || stepIndex >= totalSteps) {
            return;
        }

        currentStep = stepIndex;

        $('.mro-step-panel').removeClass('active');
        $('.mro-step-panel[data-step="' + currentStep + '"]').addClass('active');

        $('.mro-step-item').removeClass('active completed');

        $('.mro-step-item').each(function () {
            var itemStep = parseInt($(this).data('step'), 10);

            if (itemStep < currentStep) {
                $(this).addClass('completed');
                $(this).find('.mro-step-icon').html('<i class="bi bi-check-lg"></i>');
            } else {
                $(this).find('.mro-step-icon').html($(this).data('icon'));
            }

            if (itemStep === currentStep) {
                $(this).addClass('active');
            }
        });

        refreshLockedSteps();

        $('#step-prev').toggle(currentStep > 0);
        $('#step-next').toggle(currentStep < totalSteps - 1);
        $('#step-submit').toggle(currentStep === totalSteps - 1);
    }

    $('.mro-step-item').on('click', function () {
        var targetStep = parseInt($(this).data('step'), 10);

        if (targetStep <= currentStep) {
            showStep(targetStep);
            return;
        }

        if (targetStep > maxReachedStep) {
            if (validateCurrentStep(true)) {
                maxReachedStep = Math.max(maxReachedStep, currentStep + 1);
                showStep(currentStep + 1);
            }
            return;
        }

        if (validateCurrentStep(true)) {
            showStep(targetStep);
        }
    });

    $('#step-prev').on('click', function () {
        showStep(currentStep - 1);
    });

    $('#step-next').on('click', function () {
        if (!validateCurrentStep(true)) {
            return;
        }

        maxReachedStep = Math.max(maxReachedStep, currentStep + 1);
        showStep(currentStep + 1);
    });

    $('#password, #confirm_password').on('input', function () {
        var password = $.trim($('#password').val() || '');
        var confirmPassword = $.trim($('#confirm_password').val() || '');

        $('#password, #confirm_password').removeClass('step-field-invalid');
        $('#confirm-password-error').hide().text('');

        if (confirmPassword !== '' && password !== confirmPassword) {
            setFieldInvalid('#confirm_password', 'Confirm Password must match Password.', '#confirm-password-error');
        }
    });

    $('#email').on('input', function () {
        $(this).removeClass('step-field-invalid');
        $('#email-error').hide().text('');
    });

    $('#mroprofile-username').on('input', function () {
        $(this).removeClass('step-field-invalid');
    });

    showStep(0);

    // Custom English file input.
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
    bindCustomFileInput('company-photo-input', 'company-photo-button', 'company-photo-name');
    bindCustomFileInput('naa-file-input', 'naa-file-button', 'naa-file-name');
    bindCustomFileInput('insurance-file-input', 'insurance-file-button', 'insurance-file-name');
    bindCustomFileInput('aircraft-certificate-file-input', 'aircraft-certificate-file-button', 'aircraft-certificate-file-name');

    // Country -> City kept with the old working logic.
    $('#country-select').on('change', function () {
        var countryId = $(this).val();
        var citySelect = $('#city-select');

        citySelect.html('<option value="">Loading...</option>');

        if (countryId) {
            $.ajax({
                url: '$loadCitiesUrl',
                type: 'GET',
                dataType: 'json',
                data: {
                    countryId: countryId
                },
                success: function (data) {
                    citySelect.empty();
                    citySelect.append('<option value="">Select City</option>');

                    if (data && data.cities) {
                        $.each(data.cities, function (index, city) {
                            citySelect.append('<option value="' + city.id + '">' + city.text + '</option>');
                        });
                    }
                },
                error: function () {
                    citySelect.html('<option value="">Error loading cities</option>');
                }
            });
        } else {
            citySelect.html('<option value="">Select City</option>');
        }
    });

    // Certificate type -> certificate_type_id.
    $('#certificate-type-dropdown').on('change', function () {
        var selectedType = $(this).val();

        if (!selectedType) {
            $('#certificates-certificate_type_id').val('');
            return;
        }

        $.ajax({
            url: '$certificateTypeUrl',
            type: 'GET',
            dataType: 'json',
            data: {
                type: selectedType
            },
            success: function (data) {
                if (data && data.certificate_type_id) {
                    $('#certificates-certificate_type_id').val(data.certificate_type_id);
                } else {
                    $('#certificates-certificate_type_id').val('');
                }
            },
            error: function () {
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
            }
        });
    });

    // Manufacturer -> Aircraft Type corrected.
    $('#manufacturer-dropdown').on('change', function () {
        var selectedManufacturer = $(this).val();
        var modelDropdown = $('#model-dropdown');

        modelDropdown.empty();
        modelDropdown.append('<option value="">Loading...</option>');

        if (!selectedManufacturer) {
            modelDropdown.empty();
            modelDropdown.append('<option value="">Select Aircraft Type</option>');
            return;
        }

        $.ajax({
            url: '$modelsByManufacturerUrl',
            type: 'GET',
            dataType: 'json',
            data: {
                manufacturer: selectedManufacturer
            },
            success: function (data) {
                modelDropdown.empty();
                modelDropdown.append('<option value="">Select Aircraft Type</option>');

                if (data && data.length > 0) {
                    $.each(data, function (index, model) {
                        modelDropdown.append(
                            '<option value="' + model.id + '">' + model.name + '</option>'
                        );
                    });
                } else {
                    modelDropdown.append('<option value="">No aircraft type found</option>');
                }
            },
            error: function (xhr) {
                console.log(xhr.responseText);

                modelDropdown.empty();
                modelDropdown.append('<option value="">Error loading aircraft types</option>');

                Swal.fire({
                    width: 340,
                    title: 'Error',
                    html: 'Error fetching aircraft types. Please add actionGetModelsByManufacturer in MroProfileController.',
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
    });

    // Confirm form submission after Yii validation passes.
    $('#mro-create-form').on('beforeSubmit', function () {
        var form = $(this);

        // Do not submit if the first step account/password validation is not valid.
        if (!validateAccountStep(true)) {
            showStep(0);
            return false;
        }

        if (form.data('confirmed') === true) {
            return true;
        }

        Swal.fire({
            width: 340,
            title: 'Confirm creation',
            html: 'Are you sure you want to create this MRO profile?',
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
});
JS, \yii\web\View::POS_READY);
?>

<main class="dash-content mro-create-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-tools"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Create a new MRO profile with account, company, certificates, aircraft capability, and airport coverage.
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-arrow-left"></i> Back to MRO Profile',
                ['index'],
                ['class' => 'btn btn-back-mro']
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

        <?php if (Yii::$app->session->hasFlash('usernameError')): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('usernameError')) ?>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('passwordError')): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('passwordError')) ?>
            </div>
        <?php endif; ?>

        <div class="form-center-wrapper">
            <div class="content-card">

                <?php $form = ActiveForm::begin([
                    'id' => 'mro-create-form',
                    'enableClientValidation' => true,
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'autocomplete' => 'off',
                    ],
                ]); ?>

                    <div class="form-row-center">
                        <div class="form-column">



                            <!-- Colored stepper navigation -->
                            <div class="mro-stepper">
                                <div class="mro-stepper-track">
                                    <div class="mro-step-item active" data-step="0" data-color="blue" data-icon='<i class="bi bi-person-circle"></i>'>
                                        <span class="mro-step-icon"><i class="bi bi-person-circle"></i></span>
                                        <span class="mro-step-label">Account</span>
                                    </div>
                                    <div class="mro-step-item" data-step="1" data-color="cyan" data-icon='<i class="bi bi-person-vcard"></i>'>
                                        <span class="mro-step-icon"><i class="bi bi-person-vcard"></i></span>
                                        <span class="mro-step-label">Profile</span>
                                    </div>
                                    <div class="mro-step-item" data-step="2" data-color="green" data-icon='<i class="bi bi-geo-alt"></i>'>
                                        <span class="mro-step-icon"><i class="bi bi-geo-alt"></i></span>
                                        <span class="mro-step-label">Location</span>
                                    </div>
                                    <div class="mro-step-item" data-step="3" data-color="orange" data-icon='<i class="bi bi-file-earmark-check"></i>'>
                                        <span class="mro-step-icon"><i class="bi bi-file-earmark-check"></i></span>
                                        <span class="mro-step-label">Documents</span>
                                    </div>
                                    <div class="mro-step-item" data-step="4" data-color="purple" data-icon='<i class="bi bi-airplane-engines"></i>'>
                                        <span class="mro-step-icon"><i class="bi bi-airplane-engines"></i></span>
                                        <span class="mro-step-label">Aircraft</span>
                                    </div>
                                    <div class="mro-step-item" data-step="5" data-color="slate" data-icon='<i class="bi bi-geo"></i>'>
                                        <span class="mro-step-icon"><i class="bi bi-geo"></i></span>
                                        <span class="mro-step-label">Airport</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mro-step-panel active" data-step="0">
                            <!-- Account section -->
                            <div class="form-section">
                                <div class="form-section-title">
                                    <i class="bi bi-person-circle"></i>
                                    Account Information
                                </div>

                                <?= $form->field($model, 'username')->textInput([
                                    'maxlength' => false,
                                    'class' => 'form-control',
                                    'placeholder' => 'Enter username',
                                ])->label('Username<span class="required-star">*</span>', ['encode' => false]) ?>

                                <?= $form->field($model, 'password')->passwordInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'password',
                                    'placeholder' => 'Enter password',
                                ])->label('Password<span class="required-star">*</span>', ['encode' => false]) ?>

                                <?= $form->field($model, 'confirm_password')->passwordInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'confirm_password',
                                    'placeholder' => 'Confirm password',
                                ])->label('Confirm Password<span class="required-star">*</span>', ['encode' => false]) ?>

                                <div id="confirm-password-error" class="error-message" style="display:none;"></div>

                                <?= $form->field($model, 'email')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'email',
                                    'placeholder' => 'Enter email address',
                                ])->label('Email<span class="required-star">*</span>', ['encode' => false]) ?>

                                <div class="error-message" id="email-error" style="display:none;"></div>
                            </div>

                            </div>

                            <div class="mro-step-panel" data-step="1">

                            <!-- Personal section -->
                            <div class="form-section">
                                <div class="form-section-title">
                                    <i class="bi bi-card-text"></i>
                                    Personal Information
                                </div>

                                <?= $form->field($model, 'first_name')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'first_name',
                                    'placeholder' => 'Enter first name',
                                ])->label('First Name<span class="required-star">*</span>', ['encode' => false]) ?>

                                <?= $form->field($model, 'last_name')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'last_name',
                                    'placeholder' => 'Enter last name',
                                ])->label('Last Name<span class="required-star">*</span>', ['encode' => false]) ?>

                                <?= $form->field($model, 'contact_number')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'contact_number',
                                    'placeholder' => 'Enter contact number',
                                ])->label('Contact Number<span class="required-star">*</span>', ['encode' => false]) ?>

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
                            </div>

                            <!-- Company section -->
                            <div class="form-section">
                                <div class="form-section-title">
                                    <i class="bi bi-building"></i>
                                    Company Information
                                </div>

                                <?= $form->field($model, 'company_name')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'company_name',
                                    'placeholder' => 'Enter company name',
                                ])->label('Company Name<span class="required-star">*</span>', ['encode' => false]) ?>

                                <?= $form->field($model, 'company_photo', [
                                    'template' => "{label}\n
                                        <div class=\"custom-file-box\">
                                            <button type=\"button\" class=\"custom-file-button\" id=\"company-photo-button\">
                                                <i class=\"bi bi-upload\"></i> Choose File
                                            </button>
                                            <span class=\"custom-file-name\" id=\"company-photo-name\">No file selected</span>
                                        </div>
                                        {input}\n{error}",
                                ])->fileInput([
                                    'id' => 'company-photo-input',
                                    'class' => 'custom-file-input-hidden',
                                    'accept' => 'image/*',
                                ])->label('Company Photo', ['encode' => false]) ?>
                            </div>

                            </div>

                            <div class="mro-step-panel" data-step="2">

                            <!-- Location section -->
                            <div class="form-section">
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

                                <?= $form->field($model, 'city_id')->dropDownList(
                                    [],
                                    [
                                        'prompt' => 'Select City',
                                        'id' => 'city-select',
                                        'class' => 'form-select',
                                    ]
                                )->label('City<span class="required-star">*</span>', ['encode' => false]) ?>

                                <div id="selected-city-value" class="selected-city-value"></div>

                                <?= $form->field($model, 'address')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'address',
                                    'placeholder' => 'Enter address',
                                ])->label('Address<span class="required-star">*</span>', ['encode' => false]) ?>

                                <?= $form->field($model, 'zip_code')->textInput([
                                    'maxlength' => true,
                                    'class' => 'form-control',
                                    'id' => 'zip_code',
                                    'placeholder' => 'Enter zip code',
                                ])->label('Zip Code<span class="required-star">*</span>', ['encode' => false]) ?>
                            </div>

                            </div>

                            <div class="mro-step-panel" data-step="3">

                            <!-- NAA section -->
                            <div class="form-section">
                                <div class="form-section-title">
                                    <i class="bi bi-file-earmark-check"></i>
                                    NAA Certificate
                                </div>

                                <?= $form->field($certificate, 'certificate', [
                                    'template' => "{label}\n
                                        <div class=\"custom-file-box\">
                                            <button type=\"button\" class=\"custom-file-button\" id=\"naa-file-button\">
                                                <i class=\"bi bi-upload\"></i> Choose File
                                            </button>
                                            <span class=\"custom-file-name\" id=\"naa-file-name\">No file selected</span>
                                        </div>
                                        {input}\n{error}",
                                ])->fileInput([
                                    'id' => 'naa-file-input',
                                    'class' => 'custom-file-input-hidden',
                                    'accept' => '.pdf',
                                ])->label(
                                    $certificate->getAttributeLabel('certificate') . '<span class="required-star">*</span>',
                                    ['encode' => false]
                                ) ?>

                                <?= $form->field($certificate, 'type')->dropDownList(
                                    ArrayHelper::map(\app\models\CertificateTypes::find()->all(), 'type', 'type'),
                                    [
                                        'prompt' => 'Select Type',
                                        'id' => 'certificate-type-dropdown',
                                        'class' => 'form-select',
                                    ]
                                )->label('NAA<span class="required-star">*</span>', ['encode' => false]) ?>

                                <?= $form->field($certificate, 'certificate_type_id')->hiddenInput()->label(false) ?>
                            </div>

                            <!-- Insurance section -->
                            <div class="form-section">
                                <div class="form-section-title">
                                    <i class="bi bi-shield-check"></i>
                                    Insurance Document
                                </div>

                                <?= $form->field($model, 'insurance_document', [
                                    'template' => "{label}\n
                                        <div class=\"custom-file-box\">
                                            <button type=\"button\" class=\"custom-file-button\" id=\"insurance-file-button\">
                                                <i class=\"bi bi-upload\"></i> Choose File
                                            </button>
                                            <span class=\"custom-file-name\" id=\"insurance-file-name\">No file selected</span>
                                        </div>
                                        {input}\n{error}",
                                ])->fileInput([
                                    'id' => 'insurance-file-input',
                                    'class' => 'custom-file-input-hidden',
                                    'accept' => '.pdf',
                                ])->label(
                                    $model->getAttributeLabel('insurance_document') . '<span class="required-star">*</span>',
                                    ['encode' => false]
                                ) ?>
                            </div>

                            </div>

                            <div class="mro-step-panel" data-step="4">

                            <!-- Aircraft certificate section -->
                            <div class="form-section">
                                <div class="form-section-title">
                                    <i class="bi bi-airplane-engines"></i>
                                    Aircraft Certificate
                                </div>

                                <?= $form->field($mroaircraftcertificate, 'manufacturer')->dropDownList(
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
                                        'class' => 'form-select',
                                    ]
                                )->label('Manufacturer<span class="required-star">*</span>', ['encode' => false]) ?>

                                <?= $form->field($mroaircraftcertificate, 'aircraft_model_id')->dropDownList(
                                    [],
                                    [
                                        'prompt' => 'Select Aircraft Type',
                                        'id' => 'model-dropdown',
                                        'class' => 'form-select',
                                    ]
                                )->label('Aircraft Type<span class="required-star">*</span>', ['encode' => false]) ?>

                                <?= $form->field($mroaircraftcertificate, 'certificate', [
                                    'template' => "{label}\n
                                        <div class=\"custom-file-box\">
                                            <button type=\"button\" class=\"custom-file-button\" id=\"aircraft-certificate-file-button\">
                                                <i class=\"bi bi-upload\"></i> Choose File
                                            </button>
                                            <span class=\"custom-file-name\" id=\"aircraft-certificate-file-name\">No file selected</span>
                                        </div>
                                        {input}\n{error}",
                                ])->fileInput([
                                    'id' => 'aircraft-certificate-file-input',
                                    'class' => 'custom-file-input-hidden',
                                    'accept' => '.pdf',
                                ])->label(
                                    $mroaircraftcertificate->getAttributeLabel('certificate') . '<span class="required-star">*</span>',
                                    ['encode' => false]
                                ) ?>
                            </div>

                            </div>

                            <div class="mro-step-panel" data-step="5">

                            <!-- Airport section -->
                            <div class="form-section">
                                <div class="form-section-title">
                                    <i class="bi bi-geo"></i>
                                    Airport Coverage
                                </div>

                                <?= $form->field($mroairport, 'airport_id')->widget(Select2::classname(), [
                                    'options' => [
                                        'placeholder' => 'Select Airport',
                                    ],
                                    'pluginOptions' => [
                                        'allowClear' => true,
                                        'minimumInputLength' => 2,
                                        'ajax' => [
                                            'url' => Url::to(['site/airport-search']),
                                            'dataType' => 'json',
                                            'data' => new \yii\web\JsExpression('function(params) { return {q: params.term}; }'),
                                            'processResults' => new \yii\web\JsExpression('function(data) { return {results: data.results}; }'),
                                        ],
                                    ],
                                ])->label(
                                    $mroairport->getAttributeLabel('airport_name') . '<span class="required-star">*</span>',
                                    ['encode' => false]
                                ) ?>
                            </div>

                            </div>

                        </div>
                    </div>

                    <!-- Form actions -->
                    <div class="form-actions stepper-actions">
                        <div class="stepper-actions-left">
                            <?= Html::button(
                                '<i class="bi bi-arrow-left-circle"></i> Previous',
                                [
                                    'type' => 'button',
                                    'id' => 'step-prev',
                                    'class' => 'btn btn-step-prev',
                                ]
                            ) ?>
                        </div>

                        <div class="stepper-actions-right">
                            <?= Html::button(
                                'Next <i class="bi bi-arrow-right-circle"></i>',
                                [
                                    'type' => 'button',
                                    'id' => 'step-next',
                                    'class' => 'btn btn-step-next',
                                ]
                            ) ?>

                            <?= Html::submitButton(
                                '<i class="bi bi-check-circle"></i> Create MRO Profile',
                                [
                                    'class' => 'btn btn-submit-mro',
                                    'id' => 'step-submit',
                                    'style' => 'display:none;',
                                ]
                            ) ?>

                            <?= Html::a(
                                '<i class="bi bi-arrow-left-circle"></i> Back to MRO Profile',
                                ['index'],
                                ['class' => 'btn btn-back-mro']
                            ) ?>
                        </div>
                    </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    </div>
</main>

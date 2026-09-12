<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\jui\DatePicker;

$this->title = 'Create Advert';

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

    .advert-create-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .advert-create-page .container-fluid {
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
        max-width: 960px;
        background: #ffffff;
        border-radius: 18px;
        padding: 28px 32px;
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
        max-width: 100%;
    }

    /* Two-column grid rows */
    .field-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        align-items: start;
    }

    /* Media preview */
    .media-preview-wrap {
        display: none;
        margin-top: 12px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #dbeafe;
        background: #f8fafc;
        max-height: 240px;
        text-align: center;
    }

    .media-preview-wrap img,
    .media-preview-wrap video {
        max-width: 100%;
        max-height: 238px;
        object-fit: contain;
        display: block;
        margin: 0 auto;
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

    .input-error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.10) !important;
    }

    .help-block,
    .invalid-feedback {
        color: #dc2626;
        font-size: 12px;
        font-weight: 600;
        margin-top: 6px;
    }

    /* Select2 design */
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

    /* Switch design */
    .switch-card {
        border: 1px solid #dbeafe;
        border-radius: 14px;
        padding: 13px 14px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
    }

    .switch-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .switch-title {
        color: #0f172a;
        font-size: 13px;
        font-weight: 800;
    }

    .switch-subtitle {
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }

    .switch-card .form-check {
        margin: 0;
        min-height: auto;
    }

    .switch-card input[type='checkbox'] {
        width: 42px;
        height: 22px;
        cursor: pointer;
    }

    .conditional-box {
        display: none;
        margin-bottom: 18px;
    }

    /* jQuery UI DatePicker input */
    .ui-datepicker {
        border-radius: 14px !important;
        border: 1px solid #dbeafe !important;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14) !important;
        padding: 8px !important;
        font-family: inherit !important;
    }

    .ui-datepicker-header {
        border: none !important;
        background: #eef4ff !important;
        border-radius: 10px !important;
    }

    .ui-datepicker-calendar .ui-state-default {
        border: none !important;
        background: #ffffff !important;
        color: #334155 !important;
        border-radius: 8px !important;
        text-align: center !important;
    }

    .ui-datepicker-calendar .ui-state-active {
        background: #2563eb !important;
        color: #ffffff !important;
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

    .btn-submit-advert,
    .btn-back-advert {
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

    .btn-submit-advert {
        background: #2563eb;
        color: #ffffff !important;
        border: 1px solid #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
    }

    .btn-submit-advert:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .btn-back-advert {
        background: #f8fafc;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }

    .btn-back-advert:hover {
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
        .advert-create-page {
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

        /* Stack 2-col rows on mobile */
        .field-row-2 {
            grid-template-columns: 1fr;
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

        .switch-card {
            flex-direction: column;
            align-items: flex-start;
        }

        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-submit-advert,
        .btn-back-advert {
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
$(document).ready(function () {

    // Initialize Select2 dropdowns.
    $('.js-select2').select2({
        width: '100%',
        allowClear: true
    });

    // Open hidden file input.
    $('#advert-file-button').on('click', function () {
        $('#advert-file-input').trigger('click');
    });

    // Display selected file name and preview.
    $('#advert-file-input').on('change', function () {
        var file = this.files && this.files.length > 0 ? this.files[0] : null;
        var fileName = file ? file.name : 'No file selected';

        $('#advert-file-name')
            .text(fileName)
            .toggleClass('has-file', !!file);

        var previewEl = $('#advert-media-preview');
        previewEl.empty().hide();

        if (file) {
            clearFieldError('#advert-file-input', '#content-error');
            var url = URL.createObjectURL(file);

            if (file.type.startsWith('image/')) {
                previewEl.html('<img src="' + url + '" alt="Preview" />').show();
            } else if (file.type.startsWith('video/')) {
                previewEl.html('<video src="' + url + '" controls></video>').show();
            }
        }
    });

    // Change file accept depending on advert type.
    $('#advert-type-select').on('change', function () {
        var type = $(this).val();

        if (type === 'photo') {
            $('#advert-file-input').attr('accept', 'image/*');
        } else if (type === 'video') {
            $('#advert-file-input').attr('accept', 'video/*');
        } else {
            $('#advert-file-input').attr('accept', 'image/*,video/*');
        }

        clearSelect2Error('#advert-type-select', '#advert-type-error');
    });

    // Toggle file / URL inputs.
    function toggleInputs() {
        if ($('#use-url-checkbox').is(':checked')) {
            $('#file-input').hide();
            $('#url-input').show();
            $('#advert-file-input').val('');
            $('#advert-file-name').text('No file selected').removeClass('has-file');
            clearFieldError('#advert-file-input', '#content-error');
        } else {
            $('#file-input').show();
            $('#url-input').hide();
            $('#url-input-field').val('');
            clearFieldError('#url-input-field', '#url-error');
        }
    }

    toggleInputs();
    $('#use-url-checkbox').on('change', toggleInputs);

    // Show field error.
    function showFieldError(fieldSelector, errorSelector, message) {
        $(fieldSelector).addClass('input-error');
        $(errorSelector).html('<i class=\"bi bi-exclamation-circle\"></i><span>' + message + '</span>').css('display', 'flex');
    }

    // Clear field error.
    function clearFieldError(fieldSelector, errorSelector) {
        $(fieldSelector).removeClass('input-error');
        $(errorSelector).hide().html('');
    }

    // Show Select2 error.
    function showSelect2Error(fieldSelector, errorSelector, message) {
        $(fieldSelector).next('.select2-container').find('.select2-selection--single').addClass('input-error');
        $(errorSelector).html('<i class=\"bi bi-exclamation-circle\"></i><span>' + message + '</span>').css('display', 'flex');
    }

    // Clear Select2 error.
    function clearSelect2Error(fieldSelector, errorSelector) {
        $(fieldSelector).next('.select2-container').find('.select2-selection--single').removeClass('input-error');
        $(errorSelector).hide().html('');
    }

    // Validate URL.
    function isValidUrl(value) {
        try {
            new URL(value);
            return true;
        } catch (e) {
            return false;
        }
    }

    // Validate YYYY-MM-DD date.
    function isValidDate(value) {
        return /^\\d{4}-\\d{2}-\\d{2}$/.test(value);
    }

    // Validate advert form.
    function validateAdvertForm() {
        var isValid = true;

        var adminId = $('#admin-select').val();
        var advertType = $('#advert-type-select').val();
        var useUrl = $('#use-url-checkbox').is(':checked');
        var urlValue = $.trim($('#url-input-field').val());
        var startDate = $.trim($('#start-date-input').val());
        var endDate = $.trim($('#end-date-input').val());
        var status = $('#status-select').val();

        clearSelect2Error('#admin-select', '#admin-error');
        clearSelect2Error('#advert-type-select', '#advert-type-error');
        clearSelect2Error('#status-select', '#status-error');
        clearFieldError('#advert-file-input', '#content-error');
        clearFieldError('#url-input-field', '#url-error');
        clearFieldError('#start-date-input', '#start-date-error');
        clearFieldError('#end-date-input', '#end-date-error');

        if (!adminId) {
            showSelect2Error('#admin-select', '#admin-error', 'Please select the admin username.');
            isValid = false;
        }

        if (!advertType) {
            showSelect2Error('#advert-type-select', '#advert-type-error', 'Please select the advert type.');
            isValid = false;
        }

        if (useUrl) {
            if (!urlValue) {
                showFieldError('#url-input-field', '#url-error', 'Please enter the advert URL.');
                isValid = false;
            } else if (!isValidUrl(urlValue)) {
                showFieldError('#url-input-field', '#url-error', 'Please enter a valid URL, example: https://example.com.');
                isValid = false;
            }
        } else {
            var fileInput = document.getElementById('advert-file-input');

            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                $('#content-error').html('<i class=\"bi bi-exclamation-circle\"></i><span>Please choose an advert file.</span>').css('display', 'flex');
                $('.custom-file-box').addClass('input-error');
                isValid = false;
            } else {
                $('.custom-file-box').removeClass('input-error');
            }
        }

        if (!startDate) {
            showFieldError('#start-date-input', '#start-date-error', 'Please select the start date.');
            isValid = false;
        } else if (!isValidDate(startDate)) {
            showFieldError('#start-date-input', '#start-date-error', 'Start date must use YYYY-MM-DD format.');
            isValid = false;
        }

        if (!endDate) {
            showFieldError('#end-date-input', '#end-date-error', 'Please select the end date.');
            isValid = false;
        } else if (!isValidDate(endDate)) {
            showFieldError('#end-date-input', '#end-date-error', 'End date must use YYYY-MM-DD format.');
            isValid = false;
        }

        if (startDate && endDate && isValidDate(startDate) && isValidDate(endDate)) {
            if (new Date(endDate) < new Date(startDate)) {
                showFieldError('#end-date-input', '#end-date-error', 'End date must be greater than or equal to start date.');
                isValid = false;
            }
        }

        if (!status) {
            showSelect2Error('#status-select', '#status-error', 'Please select the advert status.');
            isValid = false;
        }

        return isValid;
    }

    // Live validation.
    $('#admin-select').on('change', function () {
        if ($(this).val()) {
            clearSelect2Error('#admin-select', '#admin-error');
        }
    });

    $('#status-select').on('change', function () {
        if ($(this).val()) {
            clearSelect2Error('#status-select', '#status-error');
        }
    });

    $('#url-input-field').on('input', function () {
        if ($.trim($(this).val())) {
            clearFieldError('#url-input-field', '#url-error');
        }
    });

    $('#start-date-input').on('change input', function () {
        if ($.trim($(this).val())) {
            clearFieldError('#start-date-input', '#start-date-error');
        }
    });

    $('#end-date-input').on('change input', function () {
        if ($.trim($(this).val())) {
            clearFieldError('#end-date-input', '#end-date-error');
        }
    });

    // Confirm form submission with SweetAlert2 after validation.
    $('#advert-create-form').on('beforeSubmit', function () {
        var form = $(this);

        if (!validateAdvertForm()) {
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
            html: 'Are you sure you want to create this advert?',
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

<main class="dash-content advert-create-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-megaphone"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Create a new advert by selecting its content source, schedule, type, and status.
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-arrow-left"></i> Back to Adverts',
                ['index'],
                ['class' => 'btn btn-back-advert']
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

            <!-- Create advert form card -->
            <div class="content-card">

                <div class="form-section-title">
                    <i class="bi bi-pencil-square"></i>
                    Advert Information
                </div>

                <?php $form = ActiveForm::begin([
                    'id' => 'advert-create-form',
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'autocomplete' => 'off',
                    ],
                ]); ?>

                    <div class="form-row-center">
                        <div class="form-column">

                            <!-- Admin field -->
                            <?php
                            // Auto-select the current logged-in admin if their ID exists in the list.
                            $currentUserId = Yii::$app->user->id;
                            if (!$advert->admin_id && array_key_exists($currentUserId, $adminList)) {
                                $advert->admin_id = $currentUserId;
                            }
                            ?>
                            <?= $form->field($advert, 'admin_id')->dropDownList(
                                $adminList,
                                [
                                    'prompt' => 'Select Admin',
                                    'id' => 'admin-select',
                                    'class' => 'form-select js-select2',
                                    'data-placeholder' => 'Select Admin',
                                ]
                            )->label(
                                'Admin Username<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>Select the admin account responsible for this advert.</span>
                            </div>

                            <div id="admin-error" class="field-error-message"></div>

                            <!-- Advert Type + Status (side by side) -->
                            <div class="field-row-2">
                                <div>
                                    <?= $form->field($advert, 'advert_type')->dropDownList(
                                        [
                                            'photo' => 'Photo',
                                            'video' => 'Video',
                                        ],
                                        [
                                            'prompt' => 'Select Advert Type',
                                            'id' => 'advert-type-select',
                                            'class' => 'form-select js-select2',
                                            'data-placeholder' => 'Select Advert Type',
                                        ]
                                    )->label(
                                        $advert->getAttributeLabel('advert_type') . '<span class="required-star">*</span>',
                                        ['encode' => false]
                                    ) ?>
                                    <div class="field-message">
                                        <i class="bi bi-info-circle"></i>
                                        <span>Photo or video content.</span>
                                    </div>
                                    <div id="advert-type-error" class="field-error-message"></div>
                                </div>

                                <div>
                                    <?= $form->field($advert, 'status')->dropDownList(
                                        [
                                            'active'   => 'Active',
                                            'inactive' => 'Inactive',
                                        ],
                                        [
                                            'prompt'           => 'Select Status',
                                            'id'               => 'status-select',
                                            'class'            => 'form-select js-select2',
                                            'data-placeholder' => 'Select Status',
                                        ]
                                    )->label(
                                        $advert->getAttributeLabel('status') . '<span class="required-star">*</span>',
                                        ['encode' => false]
                                    ) ?>
                                    <div class="field-message">
                                        <i class="bi bi-info-circle"></i>
                                        <span>Set the advert as active or inactive.</span>
                                    </div>
                                    <div id="status-error" class="field-error-message"></div>
                                </div>
                            </div><!-- /.field-row-2 Advert Type + Status -->


                            <div class="switch-card">
                                <div class="switch-info">
                                    <div class="switch-title">
                                        <i class="bi bi-link-45deg"></i>
                                        Use external URL
                                    </div>
                                    <div class="switch-subtitle">
                                        Enable this option if the advert content is hosted online.
                                    </div>
                                </div>

                                <?= $form->field($advert, 'use_url', [
                                    'template' => "{input}",
                                    'options' => ['class' => 'form-check form-switch'],
                                ])->checkbox([
                                    'id' => 'use-url-checkbox',
                                    'class' => 'form-check-input',
                                ], false) ?>
                            </div>

                            <!-- File input -->
                            <div id="file-input" class="conditional-box">
                                <?= $form->field($advert, 'content', [
                                    'template' =>
                                        "{label}" .
                                        '<div class="custom-file-box">' .
                                        '<button type="button" class="custom-file-button" id="advert-file-button">' .
                                        '<i class="bi bi-upload"></i> Choose File</button>' .
                                        '<span class="custom-file-name" id="advert-file-name">No file selected</span>' .
                                        '</div>' .
                                        "{input}{error}",
                                ])->fileInput([
                                    'id' => 'advert-file-input',
                                    'class' => 'custom-file-input-hidden',
                                    'accept' => 'image/*,video/*',
                                ])->label(
                                    $advert->getAttributeLabel('content') . '<span class="required-star">*</span>',
                                    ['encode' => false]
                                ) ?>

                                <!-- Media preview -->
                                <div id="advert-media-preview" class="media-preview-wrap"></div>

                                <div class="field-message">
                                    <i class="bi bi-info-circle"></i>
                                    <span>Choose a photo or video file according to the selected advert type.</span>
                                </div>

                                <div id="content-error" class="field-error-message"></div>
                            </div>

                            <!-- URL input -->
                            <div id="url-input" class="conditional-box">
                                <?= $form->field($advert, 'url')->textInput([
                                    'id' => 'url-input-field',
                                    'class' => 'form-control',
                                    'placeholder' => 'Enter URL, example: https://example.com/advert',
                                ])->label(
                                    $advert->getAttributeLabel('url') . '<span class="required-star">*</span>',
                                    ['encode' => false]
                                ) ?>

                                <div class="field-message">
                                    <i class="bi bi-info-circle"></i>
                                    <span>Enter a valid external URL starting with http:// or https://.</span>
                                </div>

                                <div id="url-error" class="field-error-message"></div>
                            </div>

                            <!-- Start date + End date (side by side) -->
                            <div class="field-row-2">
                                <div>
                                    <?= $form->field($advert, 'start_date')->widget(DatePicker::className(), [
                                        'dateFormat' => 'yyyy-MM-dd',
                                        'clientOptions' => [
                                            'autoclose' => true,
                                            'todayHighlight' => true,
                                        ],
                                        'options' => [
                                            'id' => 'start-date-input',
                                            'class' => 'form-control',
                                            'placeholder' => 'YYYY-MM-DD',
                                            'autocomplete' => 'off',
                                        ],
                                    ])->label(
                                        $advert->getAttributeLabel('start_date') . '<span class="required-star">*</span>',
                                        ['encode' => false]
                                    ) ?>
                                    <div class="field-message">
                                        <i class="bi bi-info-circle"></i>
                                        <span>Date the advert starts appearing.</span>
                                    </div>
                                    <div id="start-date-error" class="field-error-message"></div>
                                </div>

                                <div>
                                    <?= $form->field($advert, 'end_date')->widget(DatePicker::className(), [
                                        'dateFormat' => 'yyyy-MM-dd',
                                        'clientOptions' => [
                                            'autoclose' => true,
                                            'todayHighlight' => true,
                                        ],
                                        'options' => [
                                            'id' => 'end-date-input',
                                            'class' => 'form-control',
                                            'placeholder' => 'YYYY-MM-DD',
                                            'autocomplete' => 'off',
                                        ],
                                    ])->label(
                                        $advert->getAttributeLabel('end_date') . '<span class="required-star">*</span>',
                                        ['encode' => false]
                                    ) ?>
                                    <div class="field-message">
                                        <i class="bi bi-info-circle"></i>
                                        <span>Date the advert stops appearing.</span>
                                    </div>
                                    <div id="end-date-error" class="field-error-message"></div>
                                </div>
                            </div>


                        </div>
                    </div>

                    <!-- Form actions -->
                    <div class="form-actions">
                        <?= Html::submitButton(
                            '<i class="bi bi-check-circle"></i> Create Advert',
                            ['class' => 'btn btn-submit-advert']
                        ) ?>

                        <?= Html::a(
                            '<i class="bi bi-arrow-left-circle"></i> Back to Adverts',
                            ['index'],
                            ['class' => 'btn btn-back-advert']
                        ) ?>
                    </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    </div>
</main>

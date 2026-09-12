<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

$this->title = 'Open a dispute';

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

$fetchDetailsUrl = Url::to(['ao-mro-dispute/fetch-details']);
$uploadsBaseUrl = Url::to('@web/uploads/', false);

$this->registerCss(<<<CSS
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .dispute-create-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .dispute-create-page .container-fluid {
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
        max-width: 980px;
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

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px 18px;
    }

    .form-grid-full {
        grid-column: 1 / -1;
    }

    .form-group {
        margin-bottom: 0;
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

    textarea.form-control {
        height: auto;
        min-height: 130px;
        resize: vertical;
        line-height: 1.55;
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

    .form-control[readonly] {
        background: #f8fafc;
        color: #475569;
        cursor: not-allowed;
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

    /* Selected request detail panel */
    .request-details-panel {
        display: none;
        grid-column: 1 / -1;
        border: 1px solid #dbeafe;
        border-radius: 16px;
        background: #f8fafc;
        padding: 16px;
    }

    .request-details-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    .request-details-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 800;
    }

    .request-details-title i {
        color: #2563eb;
        font-size: 16px;
    }

    .request-details-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 800;
    }

    .request-details-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .detail-card {
        background: #ffffff;
        border: 1px solid #e5eaf3;
        border-radius: 14px;
        padding: 12px;
        min-height: 78px;
    }

    .detail-label {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        font-size: 11.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
        margin-bottom: 7px;
    }

    .detail-label i {
        color: #2563eb;
        font-size: 13px;
    }

    .detail-value {
        color: #0f172a;
        font-size: 13px;
        font-weight: 800;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .detail-muted {
        color: #94a3b8;
        font-weight: 700;
    }

    .po-actions {
        margin-top: 12px;
        display: none;
    }

    .btn-download-po {
        height: 38px;
        border-radius: 10px;
        padding: 0 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #c2410c !important;
        font-size: 12.5px;
        font-weight: 800;
        text-decoration: none;
        transition: .2s ease;
    }

    .btn-download-po:hover {
        background: #ffedd5;
        color: #9a3412 !important;
        transform: translateY(-1px);
    }

    .form-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 22px;
        padding-top: 20px;
        border-top: 1px solid #eef2f7;
    }

    .btn-submit-dispute,
    .btn-back-dispute {
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

    .btn-submit-dispute {
        background: #2563eb;
        color: #ffffff !important;
        border: 1px solid #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
    }

    .btn-submit-dispute:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .btn-back-dispute {
        background: #f8fafc;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }

    .btn-back-dispute:hover {
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

    @media (max-width: 992px) {
        .request-details-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .dispute-create-page {
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

        .form-grid {
            grid-template-columns: 1fr;
        }

        .request-details-grid {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-submit-dispute,
        .btn-back-dispute,
        .btn-download-po {
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

    // Initialize Select2 dropdown.
    $('.js-select2').select2({
        width: '100%',
        allowClear: true
    });

    // Show field error message.
    function showFieldError(fieldSelector, errorSelector, message) {
        $(fieldSelector).addClass('input-error');
        $(errorSelector).html('<i class="bi bi-exclamation-circle"></i><span>' + message + '</span>').css('display', 'flex');
    }

    // Clear field error message.
    function clearFieldError(fieldSelector, errorSelector) {
        $(fieldSelector).removeClass('input-error');
        $(errorSelector).hide().html('');
    }

    // Show Select2 error.
    function showSelect2Error(fieldSelector, errorSelector, message) {
        $(fieldSelector).next('.select2-container').find('.select2-selection--single').addClass('input-error');
        $(errorSelector).html('<i class="bi bi-exclamation-circle"></i><span>' + message + '</span>').css('display', 'flex');
    }

    // Clear Select2 error.
    function clearSelect2Error(fieldSelector, errorSelector) {
        $(fieldSelector).next('.select2-container').find('.select2-selection--single').removeClass('input-error');
        $(errorSelector).hide().html('');
    }

    // Display selected request details in the detail panel.
    function updateRequestDetails(data, requestId) {
        var safeValue = function (value) {
            return value !== undefined && value !== null && value !== '' ? value : 'N/A';
        };

        $('#detail-request-id').text(safeValue(requestId));
        $('#detail-ao').text(safeValue(data.ao_username));
        $('#detail-mro').text(safeValue(data.mro_username));
        $('#detail-po').text(safeValue(data.po));
        var manufacturer = data.manufacturer || data.aircraft_manufacturer || data.aircraft || data.aircraft_name;
        var aircraftType = data.aircraft_type || data.aircraft_model || data.model || data.model_name;
        var aircraftRegistration = data.registration_number || data.aircraft_registration || data.registration;

        $('#detail-request-status').text(safeValue(data.request_status || data.status || 'Selected'));
        $('#detail-manufacturer').text(safeValue(manufacturer));
        $('#detail-aircraft-type').text(safeValue(aircraftType));
        $('#detail-registration').text(safeValue(aircraftRegistration));
        $('#detail-airport').text(safeValue(data.airport || data.airport_name || data.maintenance_location || data.location));
        $('#detail-date').text(safeValue(data.created_at || data.request_date || data.date || data.etd || data.eta));

        $('#request-details-panel').slideDown(180);
    }

    // Reset selected request details.
    function resetRequestDetails() {
        $('#dispute-ao_id').val('');
        $('#dispute-mro_id').val('');
        $('#dispute-ao_username').val('');
        $('#dispute-mro_username').val('');
        $('#dispute-po').val('');
        $('#download-po-link').attr('href', '#');
        $('#po-actions').hide();
        $('#request-details-panel').slideUp(150);

        $('#detail-request-id').text('N/A');
        $('#detail-ao').text('N/A');
        $('#detail-mro').text('N/A');
        $('#detail-po').text('N/A');
        $('#detail-request-status').text('N/A');
        $('#detail-manufacturer').text('N/A');
        $('#detail-aircraft-type').text('N/A');
        $('#detail-registration').text('N/A');
        $('#detail-airport').text('N/A');
        $('#detail-date').text('N/A');
    }

    // Load request details when request changes.
    $('#request-id-dropdown').on('change', function () {
        var requestId = $(this).val();
        clearSelect2Error('#request-id-dropdown', '#request-error');

        if (!requestId) {
            resetRequestDetails();
            return;
        }

        resetRequestDetails();
        $('#request-loading-message').css('display', 'flex');

        $.ajax({
            url: '$fetchDetailsUrl',
            type: 'GET',
            dataType: 'json',
            data: {
                requestId: requestId
            },
            success: function (data) {
                $('#request-loading-message').hide();

                if (data && data.success) {
                    $('#dispute-ao_id').val(data.ao_id || '');
                    $('#dispute-mro_id').val(data.mro_id || '');
                    $('#dispute-ao_username').val(data.ao_username || '');
                    $('#dispute-mro_username').val(data.mro_username || '');
                    $('#dispute-po').val(data.po || '');

                    updateRequestDetails(data, requestId);

                    if (data.po) {
                        $('#download-po-link').attr('href', '$uploadsBaseUrl' + data.po);
                        $('#po-actions').show();
                    } else {
                        $('#download-po-link').attr('href', '#');
                        $('#po-actions').hide();
                    }
                } else {
                    showSelect2Error('#request-id-dropdown', '#request-error', 'Unable to load the selected request details.');
                }
            },
            error: function (xhr) {
                $('#request-loading-message').hide();
                console.log(xhr.responseText);

                showSelect2Error('#request-id-dropdown', '#request-error', 'Error loading request details. Please check the fetch-details action.');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        width: 340,
                        title: 'Error',
                        html: 'Error loading selected request details.',
                        icon: 'error',
                        confirmButtonText: '<i class="bi bi-check-circle"></i> Got it',
                        buttonsStyling: false,
                        customClass: {
                            // SHARED FORM CONFIRMATION: data-loading error handling remains unchanged.
                            popup: 'custom-submit-popup can-form-swal',
                            title: 'custom-submit-title',
                            htmlContainer: 'custom-submit-message',
                            confirmButton: 'swal-submit-confirm'
                        }
                    });
                }
            }
        });
    });

    // Validate dispute form fields.
    function validateDisputeForm() {
        var isValid = true;
        var requestId = $('#request-id-dropdown').val();
        var description = $.trim($('#dispute-description').val());
        var aoId = $.trim($('#dispute-ao_id').val());
        var mroId = $.trim($('#dispute-mro_id').val());

        clearSelect2Error('#request-id-dropdown', '#request-error');
        clearFieldError('#dispute-description', '#description-error');

        if (!requestId) {
            showSelect2Error('#request-id-dropdown', '#request-error', 'Please select a request before opening a dispute.');
            isValid = false;
        }

        if (requestId && (!aoId || !mroId)) {
            showSelect2Error('#request-id-dropdown', '#request-error', 'Please wait until request details are loaded correctly.');
            isValid = false;
        }

        if (!description) {
            showFieldError('#dispute-description', '#description-error', 'Please describe the dispute clearly.');
            isValid = false;
        } else if (description.length < 20) {
            showFieldError('#dispute-description', '#description-error', 'Description must contain at least 20 characters.');
            isValid = false;
        }

        return isValid;
    }

    // Live validation for description.
    $('#dispute-description').on('input', function () {
        if ($.trim($(this).val()).length >= 20) {
            clearFieldError('#dispute-description', '#description-error');
        }
    });

    // Confirm form submission with SweetAlert2 after validation.
    $('#dispute-create-form').on('beforeSubmit', function () {
        var form = $(this);

        if (!validateDisputeForm()) {
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
            title: 'Confirm dispute',
            html: 'Are you sure you want to open this dispute for the selected request?',
            icon: 'question',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            confirmButtonText: '<i class="bi bi-check-circle-fill"></i> Create',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
            buttonsStyling: false,
            customClass: {
                // SHARED FORM CONFIRMATION: dispute creation remains explicitly confirmed.
                popup: 'custom-submit-popup can-form-swal',
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

<!-- SHARED FORM SYSTEM: presentation only; dispute creation rules remain unchanged. -->
<main class="dash-content dispute-create-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-flag"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Select a request, review its AO/MRO details, then describe the dispute clearly.
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-arrow-left"></i> Back to disputes',
                ['ao-mro-dispute/index'],
                ['class' => 'btn btn-back-dispute']
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

            <!-- Create dispute form card -->
            <div class="content-card">

                <div class="form-section-title">
                    <i class="bi bi-pencil-square"></i>
                    Dispute Information
                </div>

                <?php $form = ActiveForm::begin([
                    'id' => 'dispute-create-form',
                    'options' => [
                        'autocomplete' => 'off',
                    ],
                ]); ?>

                    <div class="form-grid">

                        <!-- Request field -->
                        <div class="form-grid-full">
                            <?= $form->field($dispute, 'request_id')->dropDownList(
                                $requests,
                                [
                                    'prompt' => 'Select Request',
                                    'id' => 'request-id-dropdown',
                                    'class' => 'form-select js-select2',
                                    'data-placeholder' => 'Select Request',
                                ]
                            )->label(
                                'Request<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>Select the request related to this dispute. Details will be loaded automatically.</span>
                            </div>

                            <div id="request-loading-message" class="field-message" style="display:none;">
                                <i class="bi bi-arrow-repeat"></i>
                                <span>Loading selected request details...</span>
                            </div>

                            <div id="request-error" class="field-error-message"></div>
                        </div>

                        <!-- Selected request details panel -->
                        <div id="request-details-panel" class="request-details-panel">
                            <div class="request-details-header">
                                <div class="request-details-title">
                                    <i class="bi bi-card-checklist"></i>
                                    Selected Request Details
                                </div>
                                <div class="request-details-badge">
                                    <i class="bi bi-check-circle"></i>
                                    Details loaded
                                </div>
                            </div>

                            <div class="request-details-grid">
                                <div class="detail-card">
                                    <div class="detail-label"><i class="bi bi-hash"></i> Request</div>
                                    <div id="detail-request-id" class="detail-value detail-muted">N/A</div>
                                </div>

                                <div class="detail-card">
                                    <div class="detail-label"><i class="bi bi-person-badge"></i> AO</div>
                                    <div id="detail-ao" class="detail-value detail-muted">N/A</div>
                                </div>

                                <div class="detail-card">
                                    <div class="detail-label"><i class="bi bi-tools"></i> MRO</div>
                                    <div id="detail-mro" class="detail-value detail-muted">N/A</div>
                                </div>

                                <div class="detail-card">
                                    <div class="detail-label"><i class="bi bi-file-earmark-text"></i> PO</div>
                                    <div id="detail-po" class="detail-value detail-muted">N/A</div>
                                </div>

                                <div class="detail-card">
                                    <div class="detail-label"><i class="bi bi-activity"></i> Status</div>
                                    <div id="detail-request-status" class="detail-value detail-muted">N/A</div>
                                </div>

                                <div class="detail-card">
                                    <div class="detail-label"><i class="bi bi-airplane"></i> Manufacturer</div>
                                    <div id="detail-manufacturer" class="detail-value detail-muted">N/A</div>
                                </div>

                                <div class="detail-card">
                                    <div class="detail-label"><i class="bi bi-airplane-engines"></i> Aircraft Type</div>
                                    <div id="detail-aircraft-type" class="detail-value detail-muted">N/A</div>
                                </div>

                                <div class="detail-card">
                                    <div class="detail-label"><i class="bi bi-postcard"></i> Registration</div>
                                    <div id="detail-registration" class="detail-value detail-muted">N/A</div>
                                </div>

                                <div class="detail-card">
                                    <div class="detail-label"><i class="bi bi-geo-alt"></i> Airport</div>
                                    <div id="detail-airport" class="detail-value detail-muted">N/A</div>
                                </div>

                                <div class="detail-card">
                                    <div class="detail-label"><i class="bi bi-calendar3"></i> Date</div>
                                    <div id="detail-date" class="detail-value detail-muted">N/A</div>
                                </div>
                            </div>

                            <div id="po-actions" class="po-actions">
                                <?= Html::a(
                                    '<i class="bi bi-file-earmark-arrow-down"></i> Download PO',
                                    '#',
                                    [
                                        'class' => 'btn-download-po',
                                        'target' => '_blank',
                                        'id' => 'download-po-link',
                                    ]
                                ) ?>
                            </div>
                        </div>

                        <!-- PO field -->
                        <div>
                            <?= $form->field($dispute, 'po')->textInput([
                                'maxlength' => true,
                                'readonly' => true,
                                'class' => 'form-control',
                                'id' => 'dispute-po',
                                'placeholder' => 'Loaded automatically',
                            ])->label('PO') ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>The PO reference is filled automatically from the selected request.</span>
                            </div>
                        </div>

                        <!-- AO username field -->
                        <div>
                            <?= $form->field($dispute, 'ao_username')->textInput([
                                'readonly' => true,
                                'class' => 'form-control',
                                'id' => 'dispute-ao_username',
                                'placeholder' => 'Loaded automatically',
                            ])->label('AO Username') ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>AO username is loaded automatically from the selected request.</span>
                            </div>
                        </div>

                        <!-- MRO username field -->
                        <div>
                            <?= $form->field($dispute, 'mro_username')->textInput([
                                'readonly' => true,
                                'class' => 'form-control',
                                'id' => 'dispute-mro_username',
                                'placeholder' => 'Loaded automatically',
                            ])->label('MRO Username') ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>MRO username is loaded automatically from the selected request.</span>
                            </div>
                        </div>

                        <!-- Hidden AO and MRO IDs -->
                        <?= $form->field($dispute, 'ao_id')->hiddenInput([
                            'id' => 'dispute-ao_id',
                        ])->label(false) ?>

                        <?= $form->field($dispute, 'mro_id')->hiddenInput([
                            'id' => 'dispute-mro_id',
                        ])->label(false) ?>

                        <!-- Description field -->
                        <div class="form-grid-full">
                            <?= $form->field($dispute, 'description')->textarea([
                                'rows' => 6,
                                'class' => 'form-control',
                                'id' => 'dispute-description',
                                'placeholder' => 'Describe the issue, expected outcome, impact, references...',
                            ])->label(
                                'Description<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>Explain the dispute clearly. Include context, evidence, expected resolution, and any useful reference.</span>
                            </div>

                            <div id="description-error" class="field-error-message"></div>
                        </div>

                        <!-- Hidden status and creator fields -->
                        <?= $form->field($dispute, 'status')->hiddenInput([
                            'value' => 'open',
                        ])->label(false) ?>

                        <?= $form->field($dispute, 'created_by')->hiddenInput([
                            'value' => Yii::$app->session->get('user_type'),
                        ])->label(false) ?>

                    </div>

                    <!-- Form actions -->
                    <div class="form-actions">
                        <?= Html::submitButton(
                            '<i class="bi bi-send"></i> Open Dispute',
                            ['class' => 'btn btn-submit-dispute']
                        ) ?>

                        <?= Html::a(
                            '<i class="bi bi-arrow-left-circle"></i> Back to disputes',
                            ['ao-mro-dispute/index'],
                            ['class' => 'btn btn-back-dispute']
                        ) ?>
                    </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    </div>
</main>

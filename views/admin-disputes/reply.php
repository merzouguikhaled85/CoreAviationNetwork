<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

$this->title = 'Reply to Dispute';

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

$fetchDetailsUrl = Url::to(['admin-disputes/fetch-details']);

$aoUsername = $dispute->ao->username ?? 'N/A';
$mroUsername = $dispute->mro->username ?? 'N/A';
$poFile = $dispute->po ?? null;

$this->registerCss("
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .dispute-reply-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .dispute-reply-page .container-fluid {
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

    .details-panel {
        background: #f8fbff;
        border: 1px solid #dbeafe;
        border-radius: 16px;
        padding: 18px;
        margin-bottom: 22px;
    }

    .details-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .details-panel-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }

    .details-panel-title i {
        color: #2563eb;
    }

    .details-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 7px 12px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 800;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .detail-item {
        min-height: 78px;
        background: #ffffff;
        border: 1px solid #e0e7ff;
        border-radius: 13px;
        padding: 13px 14px;
    }

    .detail-label {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #52627a;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 9px;
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

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 7px 11px;
        font-size: 12px;
        font-weight: 800;
    }

    .status-open {
        background: #fff7ed;
        color: #c2410c;
    }

    .status-resolved {
        background: #dcfce7;
        color: #166534;
    }

    .download-po-wrapper {
        margin-top: 14px;
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
        color: #c2410c !important;
        border: 1px solid #fed7aa;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .btn-download-po:hover {
        background: #ffedd5;
        color: #9a3412 !important;
        transform: translateY(-1px);
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
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
        min-height: 44px;
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
        min-height: 130px;
        resize: vertical;
    }

    .form-control[readonly] {
        background: #f8fafc;
        color: #475569;
        cursor: not-allowed;
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

    .select2-results__option {
        padding: 9px 12px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
    }

    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background: #2563eb !important;
        color: #ffffff !important;
    }

    .form-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 24px;
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

    @media (max-width: 992px) {
        .details-grid,
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .dispute-reply-page {
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

        .details-panel {
            padding: 14px;
        }

        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-submit-dispute,
        .btn-back-dispute {
            width: 100%;
        }
    }
");

$this->registerJs(<<<JS
$(document).ready(function () {

    // Initialize Select2.
    $('.js-select2').select2({
        width: '100%',
        minimumResultsForSearch: Infinity
    });

    // Show field error.
    function showFieldError(fieldSelector, errorSelector, message) {
        $(fieldSelector).addClass('input-error');
        $(errorSelector).html('<i class="bi bi-exclamation-circle"></i><span>' + message + '</span>').css('display', 'flex');
    }

    // Clear field error.
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

    // Validate reply form.
    function validateReplyForm() {
        var isValid = true;
        var response = $.trim($('#admin-response-input').val());
        var status = $('#status-select').val();

        clearFieldError('#admin-response-input', '#admin-response-error');
        clearSelect2Error('#status-select', '#status-error');

        if (!response) {
            showFieldError('#admin-response-input', '#admin-response-error', 'Please enter the admin response.');
            isValid = false;
        }

        if (!status) {
            showSelect2Error('#status-select', '#status-error', 'Please select the dispute status.');
            isValid = false;
        }

        return isValid;
    }

    // Live validation.
    $('#admin-response-input').on('input', function () {
        if ($.trim($(this).val())) {
            clearFieldError('#admin-response-input', '#admin-response-error');
        }
    });

    $('#status-select').on('change', function () {
        if ($(this).val()) {
            clearSelect2Error('#status-select', '#status-error');
        }
    });

    // Confirm submission.
    $('#dispute-reply-form').on('beforeSubmit', function () {
        var form = $(this);

        if (!validateReplyForm()) {
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
            title: 'Confirm response',
            html: 'Are you sure you want to submit this dispute response?',
            icon: 'question',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            confirmButtonText: '<i class="bi bi-check-circle-fill"></i> Submit',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review reply',
            buttonsStyling: false,
            customClass: {
                // SHARED FORM CONFIRMATION: admin response behaviour remains unchanged.
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

    // Keep old AJAX behavior if dispute_id changes.
    $('#dispute-id-input').on('change', function () {
        var disputeId = $(this).val();

        if (disputeId) {
            $.ajax({
                url: '$fetchDetailsUrl',
                type: 'GET',
                data: {
                    disputeId: disputeId
                },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        $('#admin-response-input').val(data.admin_response);
                        $('#status-select').val(data.status).trigger('change');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching dispute details:', error);
                }
            });
        }
    });
});
JS, \yii\web\View::POS_READY);
?>

<!-- SHARED FORM SYSTEM: presentation only; dispute response rules remain unchanged. -->
<main class="dash-content dispute-reply-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-chat-left-text"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Review the dispute details and submit an official admin response.
                </div>
            </div>

            <?= Html::a(
                '<i class="bi bi-arrow-left"></i> Back to Disputes',
                ['index'],
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
            <div class="content-card">

                <!-- Dispute information title -->
                <div class="form-section-title">
                    <i class="bi bi-info-square"></i>
                    Dispute Information
                </div>

                <?php $form = ActiveForm::begin([
                    'id' => 'dispute-reply-form',
                    'options' => [
                        'autocomplete' => 'off',
                    ],
                ]); ?>

                    <?= $form->field($dispute, 'dispute_id')->hiddenInput([
                        'id' => 'dispute-id-input',
                    ])->label(false) ?>

                    <?= $form->field($dispute, 'ao_id')->hiddenInput()->label(false) ?>
                    <?= $form->field($dispute, 'mro_id')->hiddenInput()->label(false) ?>

                    <!-- Selected dispute details panel -->
                    <div class="details-panel">
                        <div class="details-panel-header">
                            <div class="details-panel-title">
                                <i class="bi bi-card-checklist"></i>
                                Selected Dispute Details
                            </div>

                            <div class="details-badge">
                                <i class="bi bi-check-circle"></i>
                                Details loaded
                            </div>
                        </div>

                        <div class="details-grid">
                            <div class="detail-item">
                                <div class="detail-label">
                                    <i class="bi bi-hash"></i>
                                    Request
                                </div>
                                <div class="detail-value">
                                    <?= Html::encode($dispute->request_id ?: 'N/A') ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">
                                    <i class="bi bi-person-badge"></i>
                                    AO Username
                                </div>
                                <div class="detail-value">
                                    <?= Html::encode($aoUsername) ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">
                                    <i class="bi bi-tools"></i>
                                    MRO Username
                                </div>
                                <div class="detail-value">
                                    <?= Html::encode($mroUsername) ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">
                                    <i class="bi bi-file-earmark-text"></i>
                                    PO
                                </div>
                                <div class="detail-value">
                                    <?= Html::encode($poFile ?: 'N/A') ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">
                                    <i class="bi bi-clock-history"></i>
                                    Timestamp
                                </div>
                                <div class="detail-value">
                                    <?= Html::encode($dispute->timestamp ?: 'N/A') ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">
                                    <i class="bi bi-person-check"></i>
                                    Created By
                                </div>
                                <div class="detail-value">
                                    <?= Html::encode($dispute->created_by ?: 'N/A') ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">
                                    <i class="bi bi-activity"></i>
                                    Current Status
                                </div>
                                <div class="detail-value">
                                    <?php
                                    $currentStatus = $dispute->status ?: 'open';
                                    $statusClass = $currentStatus === 'resolved' ? 'status-resolved' : 'status-open';
                                    ?>
                                    <span class="status-pill <?= Html::encode($statusClass) ?>">
                                        <i class="bi bi-circle-fill"></i>
                                        <?= Html::encode(ucfirst($currentStatus)) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($poFile)): ?>
                            <div class="download-po-wrapper">
                                <?= Html::a(
                                    '<i class="bi bi-file-earmark-arrow-down"></i> Download PO',
                                    Yii::getAlias('@web/uploads/') . $poFile,
                                    [
                                        'class' => 'btn-download-po',
                                        'target' => '_blank',
                                        'rel' => 'noopener',
                                    ]
                                ) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Readonly dispute fields and response fields -->
                    <div class="form-grid">

                        <!-- Request ID -->
                        <div>
                            <?= $form->field($dispute, 'request_id')->textInput([
                                'id' => 'request-id-input',
                                'class' => 'form-control',
                                'readonly' => true,
                            ])->label('Request ID') ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>The request linked to this dispute.</span>
                            </div>
                        </div>

                        <!-- Timestamp -->
                        <div>
                            <?= $form->field($dispute, 'timestamp')->textInput([
                                'id' => 'timestamp-input',
                                'class' => 'form-control',
                                'readonly' => true,
                            ])->label('Timestamp') ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>The date and time when this dispute was created.</span>
                            </div>
                        </div>

                        <!-- AO Username -->
                        <div>
                            <?= $form->field($dispute, 'ao_username')->textInput([
                                'id' => 'ao-username-input',
                                'class' => 'form-control',
                                'value' => $aoUsername,
                                'readonly' => true,
                            ])->label('AO Username') ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>The aircraft operator related to this dispute.</span>
                            </div>
                        </div>

                        <!-- MRO Username -->
                        <div>
                            <?= $form->field($dispute, 'mro_username')->textInput([
                                'id' => 'mro-username-input',
                                'class' => 'form-control',
                                'value' => $mroUsername,
                                'readonly' => true,
                            ])->label('MRO Username') ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>The MRO provider related to this dispute.</span>
                            </div>
                        </div>

                        <!-- Created by -->
                        <div>
                            <?= $form->field($dispute, 'created_by')->textInput([
                                'id' => 'created-by-input',
                                'class' => 'form-control',
                                'readonly' => true,
                            ])->label('Created By') ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>The user who opened the dispute.</span>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <?= $form->field($dispute, 'status')->dropDownList(
                                [
                                    'resolved' => 'Resolved',
                                    'open' => 'Not Resolved',
                                ],
                                [
                                    'id' => 'status-select',
                                    'class' => 'form-select js-select2',
                                    'prompt' => 'Select Status',
                                ]
                            )->label(
                                'Status<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>Select whether the dispute is resolved or still open.</span>
                            </div>

                            <div id="status-error" class="field-error-message"></div>
                        </div>

                        <!-- Dispute description -->
                        <div class="form-grid-full">
                            <?= $form->field($dispute, 'description')->textarea([
                                'id' => 'description-input',
                                'class' => 'form-control',
                                'rows' => 6,
                                'readonly' => true,
                            ])->label('Dispute Description') ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>The original dispute description submitted by the user.</span>
                            </div>
                        </div>

                        <!-- Admin response -->
                        <div class="form-grid-full">
                            <?= $form->field($dispute, 'admin_response')->textarea([
                                'id' => 'admin-response-input',
                                'class' => 'form-control',
                                'rows' => 6,
                                'placeholder' => 'Write the official admin response here...',
                            ])->label(
                                'Admin Response<span class="required-star">*</span>',
                                ['encode' => false]
                            ) ?>

                            <div class="field-message">
                                <i class="bi bi-info-circle"></i>
                                <span>Write a clear response explaining the admin decision or next action.</span>
                            </div>

                            <div id="admin-response-error" class="field-error-message"></div>
                        </div>

                    </div>

                    <!-- Form actions -->
                    <div class="form-actions">
                        <?= Html::submitButton(
                            '<i class="bi bi-send-check"></i> Submit Response',
                            ['class' => 'btn btn-submit-dispute']
                        ) ?>

                        <?= Html::a(
                            '<i class="bi bi-arrow-left-circle"></i> Back to Disputes',
                            ['index'],
                            ['class' => 'btn btn-back-dispute']
                        ) ?>
                    </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    </div>
</main>

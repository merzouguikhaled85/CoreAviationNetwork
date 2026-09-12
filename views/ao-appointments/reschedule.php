<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\datetime\DateTimePicker;

$this->title = 'Reschedule Appointment';
$this->params['breadcrumbs'][] = ['label' => 'My Appointments', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/* Bootstrap Icons */
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

/* SweetAlert2 */
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', [
    'position' => \yii\web\View::POS_HEAD,
]);

/* Custom page design */
$this->registerCss("
    /* Prevent horizontal scroll */
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .reschedule-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .reschedule-page .container-fluid {
        max-width: 100%;
        padding-left: 0;
        padding-right: 0;
    }

    /* Page header */
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
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dash-title i {
        color: #0ea5e9;
    }

    .subtitle-text {
        color: #6b7280;
        margin-top: 6px;
        font-size: 14px;
        font-weight: 500;
    }

    /* Main card */
    .content-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5eaf3;
        max-width: 760px;
    }

    .form-title {
        font-size: 20px;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-description {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 22px;
    }

    /* Form fields */
    .appointment-reschedule-form .form-group {
        margin-bottom: 18px;
    }

    .appointment-reschedule-form label,
    .appointment-reschedule-form .control-label {
        font-weight: 800;
        color: #334155;
        font-size: 13px;
        margin-bottom: 8px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .appointment-reschedule-form .form-control {
        height: 46px;
        border-radius: 12px;
        border: 1px solid #dbe3ef;
        color: #334155;
        font-size: 14px;
        font-weight: 600;
        box-shadow: none;
        transition: all 0.2s ease;
        background-color: #ffffff;
    }

    .appointment-reschedule-form .form-control:focus {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12);
    }

    /* Kartik DateTimePicker addon style */
    .appointment-reschedule-form .input-group-addon,
    .appointment-reschedule-form .input-group-text {
        border-radius: 12px;
        border: 1px solid #dbe3ef;
        background: #f8fafc;
        color: #0ea5e9;
        font-weight: 700;
    }

    .appointment-reschedule-form .input-group .form-control {
        border-radius: 12px 0 0 12px;
    }

    .appointment-reschedule-form .input-group-addon:last-child,
    .appointment-reschedule-form .input-group-text:last-child {
        border-radius: 0 12px 12px 0;
    }

    /* Validation messages */
    .appointment-reschedule-form .help-block,
    .appointment-reschedule-form .invalid-feedback {
        color: #dc2626;
        font-size: 12px;
        font-weight: 600;
        margin-top: 6px;
    }

    .appointment-reschedule-form .has-error .form-control,
    .appointment-reschedule-form .is-invalid {
        border-color: #dc2626;
        box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.08);
    }

    .appointment-reschedule-form .has-success .form-control {
        border-color: #16a34a;
    }

    /* Action buttons */
    .form-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid #eef2f7;
    }

    .btn-modern {
        border-radius: 999px;
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none !important;
        transition: all 0.2s ease;
        border: none;
        min-height: 42px;
    }

    .btn-modern:hover {
        transform: translateY(-1px);
        text-decoration: none !important;
    }

    .btn-reschedule {
        background: #0ea5e9;
        color: #ffffff !important;
        box-shadow: 0 8px 18px rgba(14, 165, 233, 0.25);
    }

    .btn-reschedule:hover {
        background: #0284c7;
        color: #ffffff !important;
        box-shadow: 0 10px 22px rgba(14, 165, 233, 0.32);
    }

    .btn-back {
        background: #e2e8f0;
        color: #334155 !important;
    }

    .btn-back:hover {
        background: #cbd5e1;
        color: #1e293b !important;
    }

    /* Info box */
    .info-box {
        background: #eff6ff;
        border: 1px solid #dbeafe;
        color: #1e40af;
        border-radius: 14px;
        padding: 14px 16px;
        margin-bottom: 22px;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .info-box i {
        font-size: 18px;
        margin-top: 1px;
    }

    /* SweetAlert2 custom design */
    .swal2-popup.custom-reschedule-popup {
        width: 390px !important;
        max-width: 92vw !important;
        border-radius: 18px !important;
        padding: 18px 20px 18px !important;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.24) !important;
    }

    .swal2-popup.custom-reschedule-popup .swal2-icon {
        width: 52px !important;
        height: 52px !important;
        margin: 8px auto 12px !important;
    }

    .swal2-popup.custom-reschedule-popup .swal2-icon .swal2-icon-content {
        font-size: 32px !important;
    }

    .swal2-title.custom-reschedule-title {
        color: #0f172a !important;
        font-size: 20px !important;
        font-weight: 800 !important;
        padding: 0 !important;
        margin: 0 0 8px !important;
    }

    .swal2-html-container.custom-reschedule-message {
        color: #64748b !important;
        font-size: 13px !important;
        line-height: 1.45 !important;
        margin: 0 8px 14px !important;
    }

    .swal-reschedule-confirm,
    .swal-reschedule-cancel {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 7px !important;
        border-radius: 999px !important;
        padding: 9px 14px !important;
        font-size: 13px !important;
        font-weight: 800 !important;
        border: none !important;
        min-width: 125px !important;
        height: 39px !important;
        transition: 0.2s ease !important;
    }

    .swal-reschedule-confirm {
        background: #0ea5e9 !important;
        color: #ffffff !important;
        box-shadow: 0 8px 18px rgba(14, 165, 233, 0.25) !important;
    }

    .swal-reschedule-confirm:hover {
        background: #0284c7 !important;
        transform: translateY(-1px);
    }

    .swal-reschedule-cancel {
        background: #e2e8f0 !important;
        color: #334155 !important;
    }

    .swal-reschedule-cancel:hover {
        background: #cbd5e1 !important;
        transform: translateY(-1px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .reschedule-page {
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
            padding: 18px;
            border-radius: 14px;
            max-width: 100%;
        }

        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-modern {
            width: 100%;
            justify-content: center;
        }
    }
");

/* Confirmation before submitting the reschedule form */
$this->registerJs(<<<JS
(function () {
    var form = document.getElementById('reschedule-appointment-form');
    var isConfirmed = false;

    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        if (isConfirmed) {
            return true;
        }

        event.preventDefault();

        // Let Yii client validation run first when available
        if (typeof jQuery !== 'undefined' && jQuery(form).data('yiiActiveForm')) {
            jQuery(form).yiiActiveForm('validate');

            var hasErrors = jQuery(form).find('.has-error').length > 0;

            if (hasErrors) {
                return false;
            }
        }

        var selectedDateInput = form.querySelector('[name$=\"[reschedule_appointment_date]\"]');
        var selectedDate = selectedDateInput && selectedDateInput.value
            ? selectedDateInput.value
            : 'the selected date and time';

        if (typeof Swal === 'undefined') {
            if (confirm('Are you sure you want to reschedule this appointment?')) {
                isConfirmed = true;
                form.submit();
            }

            return false;
        }

        Swal.fire({
            width: 390,
            title: 'Confirm reschedule?',
            html:
                '<div style=\"text-align:center;\">' +
                    '<div style=\"font-weight:700;color:#0F172A;margin-bottom:6px;font-size:13px;\">Please confirm the new appointment date.</div>' +
                    '<div style=\"font-size:13px;\">New date: <strong>' + selectedDate + '</strong></div>' +
                '</div>',
            icon: 'question',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
            allowEscapeKey: true,
            // FORM DIALOG ACTIONS: use appointment-specific choices with icons.
            confirmButtonText: '<i class=\"bi bi-calendar2-check-fill\"></i> Reschedule',
            cancelButtonText: '<i class=\"bi bi-arrow-counterclockwise\"></i> Review date',
            buttonsStyling: false,
            customClass: {
                popup: 'custom-reschedule-popup',
                title: 'custom-reschedule-title',
                htmlContainer: 'custom-reschedule-message',
                confirmButton: 'swal-reschedule-confirm',
                cancelButton: 'swal-reschedule-cancel'
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                isConfirmed = true;
                form.submit();
            }
        });

        return false;
    });
})();
JS, \yii\web\View::POS_READY);
?>

<main class="dash-content reschedule-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <i class="bi bi-calendar-plus"></i>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Choose a new date and time for this appointment.
                </div>
            </div>
        </div>

        <!-- Main form card -->
        <div class="content-card">

            <div class="form-title">
                <i class="bi bi-arrow-repeat text-primary"></i>
                Reschedule Appointment
            </div>

            <div class="form-description">
                Please select the new appointment date and time, then confirm the rescheduling request.
            </div>

            <!-- Information box -->
            <div class="info-box">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    Make sure the selected date and time are correct before submitting the form.
                </div>
            </div>

            <div class="appointment-reschedule-form">

                <?php $form = ActiveForm::begin([
                    'id' => 'reschedule-appointment-form',
                    'options' => [
                        'class' => 'reschedule-form',
                    ],
                    'fieldConfig' => [
                        'template' => "{label}\n{input}\n{error}",
                        'labelOptions' => [
                            'class' => 'control-label',
                        ],
                        'errorOptions' => [
                            'class' => 'help-block',
                        ],
                    ],
                ]); ?>

                <!-- Reschedule appointment date field -->
                <?= $form->field($appointment, 'reschedule_appointment_date')->widget(DateTimePicker::class, [
                    'options' => [
                        'value' => $appointment->reschedule_appointment_date
                            ? date('d M Y H:i', strtotime($appointment->reschedule_appointment_date))
                            : '',
                        'placeholder' => 'Select new appointment date and time',
                        'class' => 'form-control',
                    ],
                    'pluginOptions' => [
                        'format' => 'dd M yyyy HH:ii',
                        'autoclose' => true,
                        'todayHighlight' => true,
                        'todayBtn' => true,
                        'minuteStep' => 5,
                    ],
                ])->label('<i class="bi bi-calendar-event"></i> New Appointment Date') ?>

                <!-- Form actions -->
                <div class="form-actions">

                    <?= Html::submitButton('<i class="bi bi-check-circle"></i> Reschedule', [
                        'class' => 'btn-modern btn-reschedule',
                    ]) ?>

                    <?= Html::a('<i class="bi bi-arrow-left-circle"></i> Back to Appointments', ['index'], [
                        'class' => 'btn-modern btn-back',
                    ]) ?>

                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    </div>
</main>

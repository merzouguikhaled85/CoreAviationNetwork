<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\datetime\DateTimePicker;
use yii\web\View;

$this->title = 'Schedule Maintenance Appointment';

/*
 * Remix Icons
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css',
    ['position' => View::POS_HEAD]
);

/*
 * Bootstrap Icons
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    ['position' => View::POS_HEAD]
);

/*
 * SweetAlert2
 */
$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    ['position' => View::POS_END]
);

$this->registerCss(<<<CSS
:root {
    --mro-navy: #071C3A;
    --mro-blue: #1D4ED8;
    --mro-sky: #0284C7;
    --mro-cyan: #06B6D4;
    --mro-green: #16A34A;
    --mro-amber: #F59E0B;
    --mro-red: #DC2626;
    --mro-border: #D8E3EF;
    --mro-border-strong: #B7C6D8;
    --mro-bg: #F5F8FC;
    --mro-panel: #FFFFFF;
    --mro-text: #0F172A;
    --mro-muted: #64748B;
    --mro-soft-blue: #EAF3FF;
    --mro-soft-green: #ECFDF5;
    --mro-shadow: 0 24px 70px rgba(15, 23, 42, 0.13);
}

/* Main wrapper */
.maintenance-appointment-wrapper {
    min-height: calc(100vh - 100px);
    padding: 28px 24px 44px;
    background:
        radial-gradient(circle at 8% 18%, rgba(29, 78, 216, 0.12), transparent 31%),
        radial-gradient(circle at 92% 12%, rgba(6, 182, 212, 0.12), transparent 28%),
        radial-gradient(circle at 82% 88%, rgba(22, 163, 74, 0.10), transparent 28%),
        linear-gradient(135deg, #F8FAFC 0%, #EEF5FF 52%, #F6FBFF 100%);
}

/* Main card */
.maintenance-appointment-card {
    width: 100%;
    max-width: 980px;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(216, 227, 239, 0.95);
    border-radius: 28px;
    box-shadow: var(--mro-shadow);
    overflow: hidden;
    position: relative;
}

.maintenance-appointment-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 6px;
    background: linear-gradient(90deg, var(--mro-navy), var(--mro-blue), var(--mro-cyan), var(--mro-green));
    background-size: 220% 220%;
    animation: maintenanceAppointmentGradient 8s ease infinite;
    z-index: 3;
}

@keyframes maintenanceAppointmentGradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Header */
.maintenance-appointment-header {
    position: relative;
    padding: 30px 34px 24px;
    background:
        radial-gradient(circle at 92% 2%, rgba(6, 182, 212, 0.14), transparent 33%),
        linear-gradient(135deg, #FFFFFF 0%, #F6FAFF 60%, #EFF6FF 100%);
    border-bottom: 1px solid rgba(226, 232, 240, 0.92);
}

.maintenance-appointment-header::after {
    content: "";
    position: absolute;
    right: -76px;
    top: -86px;
    width: 245px;
    height: 245px;
    border-radius: 999px;
    border: 34px solid rgba(29, 78, 216, 0.055);
    pointer-events: none;
}

.maintenance-title-row {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 22px;
}

.maintenance-title-left {
    display: flex;
    align-items: flex-start;
    gap: 18px;
    min-width: 0;
}

.maintenance-title-icon {
    width: 64px;
    height: 64px;
    border-radius: 21px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--mro-blue), var(--mro-navy));
    color: #FFFFFF;
    box-shadow: 0 18px 38px rgba(29, 78, 216, 0.28);
    position: relative;
    overflow: hidden;
    flex: 0 0 auto;
}

.maintenance-title-icon::before {
    content: "";
    position: absolute;
    inset: -40%;
    background: linear-gradient(45deg, transparent 34%, rgba(255, 255, 255, 0.28) 50%, transparent 66%);
    animation: maintenanceAppointmentShine 3.4s infinite linear;
}

@keyframes maintenanceAppointmentShine {
    0% { transform: translateX(-62%); }
    100% { transform: translateX(62%); }
}

.maintenance-title-icon i {
    position: relative;
    z-index: 1;
    font-size: 30px;
    line-height: 1;
}

.maintenance-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 8px;
    padding: 6px 10px;
    border-radius: 999px;
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    color: #1D4ED8;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.maintenance-appointment-title {
    margin: 0 0 8px;
    color: var(--mro-text);
    font-size: 30px;
    font-weight: 900;
    letter-spacing: -0.045em;
    line-height: 1.12;
}

.maintenance-appointment-subtitle {
    margin: 0;
    max-width: 650px;
    color: var(--mro-muted);
    font-size: 14px;
    line-height: 1.62;
}

.maintenance-header-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    border-radius: 999px;
    background: #ECFDF5;
    border: 1px solid #BBF7D0;
    color: #166534;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
    box-shadow: 0 10px 22px rgba(22, 163, 74, 0.08);
}

.maintenance-header-badge i {
    font-size: 15px;
}

.maintenance-metrics-row {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 22px;
}

.maintenance-metric-card {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 13px 14px;
    border-radius: 17px;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(203, 213, 225, 0.78);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.045);
}

.maintenance-metric-icon {
    width: 38px;
    height: 38px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #EEF6FF;
    color: var(--mro-blue);
    flex: 0 0 auto;
    font-size: 18px;
}

.maintenance-metric-label {
    margin: 0 0 2px;
    color: var(--mro-muted);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.maintenance-metric-value {
    margin: 0;
    color: var(--mro-text);
    font-size: 13px;
    font-weight: 900;
    line-height: 1.25;
}

/* Body */
.maintenance-appointment-body {
    padding: 28px 34px 34px;
}

.maintenance-info-panel {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 17px;
    margin-bottom: 22px;
    border-radius: 18px;
    background: linear-gradient(135deg, #EFF6FF, #F8FBFF);
    border: 1px solid #BFDBFE;
    color: #1E3A8A;
    box-shadow: 0 12px 26px rgba(29, 78, 216, 0.055);
}

.maintenance-info-icon {
    width: 40px;
    height: 40px;
    border-radius: 15px;
    background: #DBEAFE;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--mro-blue);
    font-size: 19px;
    flex: 0 0 auto;
}

.maintenance-info-title {
    margin: 0 0 4px;
    color: #1E3A8A;
    font-size: 14px;
    font-weight: 900;
}

.maintenance-info-text {
    margin: 0;
    color: #475569;
    font-size: 13px;
    line-height: 1.6;
}

/* Form box */
.maintenance-form-box {
    position: relative;
    padding: 24px;
    border-radius: 22px;
    border: 1px solid var(--mro-border);
    background:
        linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(249, 251, 255, 0.98));
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.95),
        0 16px 34px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

.maintenance-form-box::before {
    content: "";
    position: absolute;
    left: 0;
    top: 22px;
    bottom: 22px;
    width: 4px;
    border-radius: 0 999px 999px 0;
    background: linear-gradient(180deg, var(--mro-blue), var(--mro-cyan), var(--mro-green));
}

.maintenance-field-shell {
    position: relative;
    padding: 18px;
    border-radius: 18px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.045);
}

.maintenance-field-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}

.maintenance-field-icon {
    width: 40px;
    height: 40px;
    border-radius: 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--mro-soft-blue);
    color: var(--mro-blue);
    font-size: 19px;
    flex: 0 0 auto;
}

.maintenance-field-title {
    margin: 0 0 3px;
    color: var(--mro-text);
    font-size: 15px;
    font-weight: 900;
}

.maintenance-field-helper {
    margin: 0;
    color: var(--mro-muted);
    font-size: 12.5px;
    line-height: 1.55;
}

/* Labels */
.maintenance-form-box label,
.maintenance-form-box .control-label {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 9px;
    color: var(--mro-text);
    font-size: 13px;
    font-weight: 900;
}

.maintenance-form-box label::before,
.maintenance-form-box .control-label::before {
    content: "\\F1F6";
    font-family: "bootstrap-icons";
    color: var(--mro-blue);
    font-weight: normal;
}

/* Input */
.maintenance-form-box .form-control {
    min-height: 50px;
    border-radius: 15px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    color: var(--mro-text);
    font-size: 15px;
    font-weight: 650;
    box-shadow: 0 9px 20px rgba(15, 23, 42, 0.045);
    transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}

.maintenance-form-box .form-control:hover {
    border-color: #94A3B8;
}

.maintenance-form-box .form-control:focus {
    border-color: var(--mro-cyan);
    box-shadow:
        0 0 0 0.18rem rgba(6, 182, 212, 0.18),
        0 12px 24px rgba(15, 23, 42, 0.05);
}

/* DateTimePicker group */
.maintenance-form-box .input-group {
    width: 100%;
}

.maintenance-form-box .input-group-addon,
.maintenance-form-box .input-group-text,
.maintenance-form-box .input-group .btn {
    border-radius: 13px;
    border-color: #CBD5E1;
    background: #F8FAFC;
    color: var(--mro-blue);
}

.maintenance-support-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-top: 14px;
}

.maintenance-support-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 11px;
    border-radius: 14px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    color: #475569;
    font-size: 12px;
    font-weight: 800;
}

.maintenance-support-item i {
    color: var(--mro-blue);
    font-size: 15px;
}

/* Actions */
.maintenance-actions {
    display: grid;
    grid-template-columns: 0.42fr 0.58fr;
    gap: 14px;
    margin-top: 20px;
}

.btn-maintenance-back,
.btn-maintenance-submit {
    min-height: 54px;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    text-decoration: none;
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, border-color 0.18s ease, color 0.18s ease;
}

.btn-maintenance-back {
    border: 1px solid #CBD5E1;
    background: #0F172A;
    color: #FFFFFF;
    box-shadow: 0 13px 26px rgba(15, 23, 42, 0.18);
}

.btn-maintenance-back i {
    font-size: 18px;
    color: #E2E8F0;
}

.btn-maintenance-back:hover,
.btn-maintenance-back:focus {
    color: #0F172A;
    background: #FFFFFF;
    border-color: #94A3B8;
    text-decoration: none;
    transform: translateY(-1px);
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.12);
}

.btn-maintenance-back:hover i,
.btn-maintenance-back:focus i {
    color: var(--mro-blue);
}

.btn-maintenance-submit {
    width: 100%;
    border: 0;
    background: linear-gradient(135deg, var(--mro-blue), var(--mro-sky));
    color: #FFFFFF;
    box-shadow: 0 18px 34px rgba(29, 78, 216, 0.30);
}

.btn-maintenance-submit i {
    font-size: 18px;
}

.btn-maintenance-submit:hover,
.btn-maintenance-submit:focus {
    color: #FFFFFF;
    background: linear-gradient(135deg, #1E40AF, #0369A1);
    transform: translateY(-1px);
    box-shadow: 0 22px 44px rgba(29, 78, 216, 0.36);
}

.btn-maintenance-submit:disabled {
    opacity: 0.78;
    cursor: not-allowed;
    transform: none;
}

/* Yii error display */
.maintenance-form-box .help-block,
.maintenance-form-box .invalid-feedback,
.maintenance-form-box .field-appointment-appointment_date .help-block {
    margin-top: 8px;
    color: var(--mro-red) !important;
    font-size: 13px;
    font-weight: 800;
    line-height: 1.4;
}

.maintenance-form-box .has-error .help-block,
.maintenance-form-box .field-appointment-appointment_date.has-error .help-block {
    padding: 8px 10px;
    border-radius: 11px;
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: var(--mro-red) !important;
}

.maintenance-form-box .has-error .form-control,
.maintenance-form-box .field-appointment-appointment_date.has-error .form-control,
.maintenance-form-box .is-invalid {
    border-color: var(--mro-red) !important;
    box-shadow: 0 0 0 0.15rem rgba(220, 38, 38, 0.16) !important;
}

.maintenance-form-box .has-error label,
.maintenance-form-box .has-error .control-label,
.maintenance-form-box .field-appointment-appointment_date.has-error label {
    color: var(--mro-red) !important;
}

.maintenance-date-past {
    border-color: var(--mro-red) !important;
    box-shadow: 0 0 0 0.15rem rgba(220, 38, 38, 0.16) !important;
}

/* Spinner overlay */
.maintenance-loading-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    background:
        radial-gradient(circle at 20% 20%, rgba(6, 182, 212, 0.16) 0%, transparent 45%),
        radial-gradient(circle at 80% 80%, rgba(22, 163, 74, 0.14) 0%, transparent 45%),
        rgba(15, 23, 42, 0.42);
    backdrop-filter: blur(7px);
}

.maintenance-loading-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 22px;
    border-radius: 19px;
    background: #FFFFFF;
    border: 1px solid rgba(226, 232, 240, 0.94);
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.35);
}

.maintenance-loading-card img {
    width: 42px;
    height: 42px;
}

.maintenance-loading-title {
    color: var(--mro-text);
    font-size: 14px;
    font-weight: 900;
}

.maintenance-loading-text {
    color: var(--mro-muted);
    font-size: 12px;
}

/* SweetAlert2 professional style */
.swal2-container {
    backdrop-filter: blur(2px);
}

.swal-maintenance-popup {
    width: 430px !important;
    padding: 28px 28px 22px !important;
    border-radius: 20px !important;
    background: #FFFFFF !important;
    box-shadow: 0 32px 86px rgba(15, 23, 42, 0.32) !important;
}

.swal-maintenance-title {
    margin: 0 !important;
    padding: 0 !important;
    color: #111827 !important;
    font-size: 22px !important;
    font-weight: 900 !important;
    letter-spacing: -0.025em !important;
}

.swal-maintenance-text {
    margin: 16px 0 0 !important;
    padding: 0 !important;
    color: #64748B !important;
    font-size: 14px !important;
    line-height: 1.58 !important;
    font-weight: 550 !important;
}

.swal-maintenance-actions {
    width: 100% !important;
    margin: 26px 0 0 !important;
    display: flex !important;
    justify-content: center !important;
    gap: 10px !important;
}

.swal-maintenance-confirm,
.swal-maintenance-cancel {
    min-width: 126px !important;
    min-height: 42px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 7px !important;
    border-radius: 11px !important;
    border: 0 !important;
    padding: 10px 16px !important;
    font-size: 14px !important;
    font-weight: 900 !important;
    line-height: 1 !important;
    box-shadow: none !important;
    outline: none !important;
    transition: all 0.16s ease !important;
}

.swal-maintenance-confirm {
    background: var(--mro-blue) !important;
    color: #FFFFFF !important;
    box-shadow: 0 13px 24px rgba(29, 78, 216, 0.28) !important;
}

.swal-maintenance-confirm:hover {
    background: #1E40AF !important;
    color: #FFFFFF !important;
    transform: translateY(-1px) !important;
}

.swal-maintenance-cancel {
    background: #E8EEF5 !important;
    color: #334155 !important;
}

.swal-maintenance-cancel:hover {
    background: #DDE6EF !important;
    color: #0F172A !important;
    transform: translateY(-1px) !important;
}

.swal-maintenance-confirm i,
.swal-maintenance-cancel i {
    font-size: 14px !important;
}

/* Responsive */
@media (max-width: 860px) {
    .maintenance-title-row {
        flex-direction: column;
    }

    .maintenance-header-badge {
        align-self: flex-start;
    }

    .maintenance-metrics-row {
        grid-template-columns: 1fr;
    }

    .maintenance-support-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .maintenance-appointment-wrapper {
        padding: 14px;
    }

    .maintenance-appointment-header {
        padding: 22px 18px 18px;
    }

    .maintenance-title-left {
        gap: 14px;
    }

    .maintenance-title-icon {
        width: 54px;
        height: 54px;
        border-radius: 17px;
    }

    .maintenance-appointment-title {
        font-size: 24px;
    }

    .maintenance-appointment-body {
        padding: 20px 16px;
    }

    .maintenance-form-box {
        padding: 17px;
    }

    .maintenance-field-shell {
        padding: 15px;
    }

    .maintenance-info-panel {
        padding: 14px;
    }

    .maintenance-actions {
        grid-template-columns: 1fr;
    }

    .btn-maintenance-back,
    .btn-maintenance-submit {
        width: 100%;
    }

    .swal-maintenance-popup {
        width: calc(100% - 32px) !important;
    }
}
CSS);
?>

<!-- SHARED FORM SYSTEM: presentation only; appointment scheduling rules remain unchanged. -->
<div class="maintenance-appointment-wrapper can-form-page">
    <div class="maintenance-appointment-card">

        <div class="maintenance-appointment-header">
            <div class="maintenance-title-row">
                <div class="maintenance-title-left">
                    <div class="maintenance-title-icon">
                        <i class="bi bi-airplane-engines"></i>
                    </div>

                    <div>
                        <div class="maintenance-eyebrow">
                            <i class="bi bi-tools"></i>
                            Aircraft Maintenance Planning
                        </div>

                        <h1 class="maintenance-appointment-title">
                            <?= Html::encode($this->title) ?>
                        </h1>

                        <p class="maintenance-appointment-subtitle">
                            Assign a controlled MRO appointment slot for the maintenance request and keep the aircraft workflow aligned with operational availability.
                        </p>
                    </div>
                </div>

                <div class="maintenance-header-badge">
                    <i class="bi bi-shield-check"></i>
                    MRO Coordination
                </div>
            </div>

            <div class="maintenance-metrics-row">
                <div class="maintenance-metric-card">
                    <div class="maintenance-metric-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <p class="maintenance-metric-label">Time format</p>
                        <p class="maintenance-metric-value">24-hour scheduling</p>
                    </div>
                </div>

                <div class="maintenance-metric-card">
                    <div class="maintenance-metric-icon">
                        <i class="bi bi-calendar2-check"></i>
                    </div>
                    <div>
                        <p class="maintenance-metric-label">Validation</p>
                        <p class="maintenance-metric-value">Future slot required</p>
                    </div>
                </div>

                <div class="maintenance-metric-card">
                    <div class="maintenance-metric-icon">
                        <i class="bi bi-clipboard2-pulse"></i>
                    </div>
                    <div>
                        <p class="maintenance-metric-label">Workflow</p>
                        <p class="maintenance-metric-value">Maintenance readiness</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="maintenance-appointment-body">

            <div class="maintenance-info-panel">
                <div class="maintenance-info-icon">
                    <i class="bi bi-info-circle"></i>
                </div>

                <div>
                    <p class="maintenance-info-title">Maintenance appointment scheduling</p>
                    <p class="maintenance-info-text">
                        Select a valid future date and time for the MRO intervention. Use the 24-hour format, for example 08:30 or 23:30, before confirming the appointment request.
                    </p>
                </div>
            </div>

            <div class="maintenance-form-box">

                <?php $form = ActiveForm::begin([
                    'id' => 'appointment-form',
                    'options' => [
                        'autocomplete' => 'off',
                    ],
                ]); ?>

                <div id="loading-spinner" class="maintenance-loading-overlay">
                    <div class="maintenance-loading-card">
                        <img src="<?= Yii::getAlias('@web/img/spinner.gif') ?>" alt="Loading..." />
                        <div>
                            <div class="maintenance-loading-title">Processing request</div>
                            <div class="maintenance-loading-text">Scheduling maintenance appointment...</div>
                        </div>
                    </div>
                </div>

                <div class="maintenance-field-shell">
                    <div class="maintenance-field-header">
                        <div class="maintenance-field-icon">
                            <i class="bi bi-calendar2-week"></i>
                        </div>
                        <div>
                            <p class="maintenance-field-title">Maintenance slot details</p>
                            <p class="maintenance-field-helper">
                                Choose the date and exact time when the aircraft maintenance team can receive or process this application.
                            </p>
                        </div>
                    </div>

                    <?= $form->field($appointment, 'appointment_date')->widget(DateTimePicker::class, [
                        'options' => [
                            'name' => 'Appointment[appointment_date]',
                            'id' => 'appointment-date-input',
                            'value' => $appointment->appointment_date
                                ? date('d M Y H:i', strtotime($appointment->appointment_date))
                                : '',
                            'placeholder' => 'Select maintenance date and time...',
                            'class' => 'form-control',
                            'autocomplete' => 'off',
                        ],
                        'pluginOptions' => [
                            /*
                             * Important for Kartik DateTimePicker:
                             * Use hh:ii with showMeridian=false to display 24h time correctly.
                             */
                            'format' => 'dd M yyyy hh:ii',
                            'autoclose' => true,
                            'todayHighlight' => true,
                            'showMeridian' => false,
                            'minuteStep' => 5,
                            'pickerPosition' => 'bottom-left',
                        ],
                    ])->label('Maintenance appointment date & time <span class="text-danger">*</span>') ?>

                    <div class="maintenance-support-row">
                        <div class="maintenance-support-item">
                            <i class="bi bi-check2-circle"></i>
                            Future date only
                        </div>
                        <div class="maintenance-support-item">
                            <i class="bi bi-clock"></i>
                            5-minute interval
                        </div>
                        <div class="maintenance-support-item">
                            <i class="bi bi-shield-lock"></i>
                            Confirmation required
                        </div>
                    </div>
                </div>

                <?= Html::hiddenInput('ao_requests_applications_id', $application->id) ?>

                <div class="maintenance-actions">
                    <?= Html::a(
                        '<i class="bi bi-arrow-left-circle"></i> Back to MRO Applications',
                        ['mro-applications/index'],
                        [
                            'class' => 'btn-maintenance-back',
                            'data-pjax' => '0',
                        ]
                    ) ?>

                    <?= Html::submitButton(
                        '<i class="bi bi-calendar2-plus"></i> Schedule Maintenance Appointment',
                        [
                            'class' => 'btn btn-maintenance-submit',
                            'id' => 'submit-button'
                        ]
                    ) ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    </div>
</div>

<?php
$this->registerJs(<<<JS
(function () {
    var form = document.getElementById('appointment-form');
    var submitButton = document.getElementById('submit-button');
    var loadingIndicator = document.getElementById('loading-spinner');
    var dateInput = document.getElementById('appointment-date-input');

    if (!form || !submitButton || !loadingIndicator || !dateInput) {
        return;
    }

    function showLoader() {
        submitButton.disabled = true;
        loadingIndicator.style.display = 'flex';
    }

    function hideLoader() {
        submitButton.disabled = false;
        loadingIndicator.style.display = 'none';
    }

    function parseAppointmentDate(value) {
        if (!value) {
            return null;
        }

        value = value.trim().replace(/\s+/g, ' ');

        var months = {
            jan: 0, january: 0, janvier: 0,
            feb: 1, february: 1, fevrier: 1, fevrier_alt: 1,
            mar: 2, march: 2, mars: 2,
            apr: 3, april: 3, avr: 3, avril: 3,
            may: 4, mai: 4,
            jun: 5, june: 5, juin: 5,
            jul: 6, july: 6, juillet: 6,
            aug: 7, august: 7, aout: 7,
            sep: 8, sept: 8, september: 8, septembre: 8,
            oct: 9, october: 9, octobre: 9,
            nov: 10, november: 10, novembre: 10,
            dec: 11, december: 11, decembre: 11
        };

        months['fevrier'] = 1;
        months['f\u00e9vrier'] = 1;
        months['aout'] = 7;
        months['ao\u00fbt'] = 7;
        months['decembre'] = 11;
        months['d\u00e9cembre'] = 11;

        /*
         * Expected format:
         * 09 Jun 2026 23:30
         */
        var match = value.match(/^(\d{1,2})\s+([A-Za-z\u00C0-\u017F]+)\s+(\d{4})\s+(\d{1,2}):(\d{2})$/);

        if (match) {
            var day = parseInt(match[1], 10);
            var monthName = match[2].toLowerCase();
            var year = parseInt(match[3], 10);
            var hour = parseInt(match[4], 10);
            var minute = parseInt(match[5], 10);

            if (months[monthName] === undefined) {
                return null;
            }

            /*
             * 24-hour validation.
             * Valid hours: 00 to 23.
             * Valid minutes: 00 to 59.
             */
            if (hour < 0 || hour > 23 || minute < 0 || minute > 59) {
                return null;
            }

            return new Date(year, months[monthName], day, hour, minute, 0, 0);
        }

        var fallbackDate = new Date(value);

        if (isNaN(fallbackDate.getTime())) {
            return null;
        }

        return fallbackDate;
    }

    function isPastAppointmentDate(selectedDate) {
        var now = new Date();

        selectedDate.setSeconds(0, 0);
        now.setSeconds(0, 0);

        return selectedDate.getTime() < now.getTime();
    }

    function showPastDateAlert() {
        dateInput.classList.add('maintenance-date-past');

        Swal.fire({
            title: 'Invalid maintenance appointment',
            html: 'The selected maintenance appointment is in the past.<br>Please choose a future date and time.',
            icon: 'warning',
            confirmButtonText: '<i class="bi bi-calendar2-week"></i> Choose another slot',
            buttonsStyling: false,
            customClass: {
                // SHARED FORM CONFIRMATION: appointment validation behaviour remains unchanged.
                popup: 'swal-maintenance-popup can-form-swal',
                title: 'swal-maintenance-title',
                htmlContainer: 'swal-maintenance-text',
                actions: 'swal-maintenance-actions',
                confirmButton: 'swal-maintenance-confirm'
            }
        }).then(function () {
            dateInput.focus();
        });
    }

    function showInvalidDateAlert() {
        dateInput.classList.add('maintenance-date-past');

        Swal.fire({
            title: 'Maintenance slot required',
            html: 'Please select a valid maintenance appointment date and time in 24-hour format before continuing.',
            icon: 'info',
            confirmButtonText: '<i class="bi bi-calendar2-plus"></i> Select slot',
            buttonsStyling: false,
            customClass: {
                // SHARED FORM CONFIRMATION: appointment validation behaviour remains unchanged.
                popup: 'swal-maintenance-popup can-form-swal',
                title: 'swal-maintenance-title',
                htmlContainer: 'swal-maintenance-text',
                actions: 'swal-maintenance-actions',
                confirmButton: 'swal-maintenance-confirm'
            }
        }).then(function () {
            dateInput.focus();
        });
    }

    function validateAppointmentDateWithAlert() {
        var selectedDate = parseAppointmentDate(dateInput.value);

        if (!selectedDate) {
            showInvalidDateAlert();
            return false;
        }

        if (isPastAppointmentDate(selectedDate)) {
            showPastDateAlert();
            return false;
        }

        dateInput.classList.remove('maintenance-date-past');
        return true;
    }

    dateInput.addEventListener('change', function () {
        if (!dateInput.value) {
            dateInput.classList.remove('maintenance-date-past');
            return;
        }

        var selectedDate = parseAppointmentDate(dateInput.value);

        if (selectedDate && isPastAppointmentDate(selectedDate)) {
            showPastDateAlert();
        } else {
            dateInput.classList.remove('maintenance-date-past');
        }
    });

    /*
     * Yii ActiveForm event.
     * The form is submitted only after SweetAlert confirmation.
     */
    $('#appointment-form').on('beforeSubmit', function () {
        var yiiForm = $(this);

        if (yiiForm.data('confirmed') === true) {
            showLoader();
            return true;
        }

        if (!validateAppointmentDateWithAlert()) {
            hideLoader();
            return false;
        }

        var selectedDateText = dateInput.value;

        Swal.fire({
            title: 'Schedule maintenance appointment?',
            html: 'You are about to schedule this aircraft maintenance appointment for:<br><strong>' + selectedDateText + '</strong>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-shield-check"></i> Confirm slot',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review slot',
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            customClass: {
                // SHARED FORM CONFIRMATION: scheduling still occurs only after explicit confirmation.
                popup: 'swal-maintenance-popup can-form-swal',
                title: 'swal-maintenance-title',
                htmlContainer: 'swal-maintenance-text',
                actions: 'swal-maintenance-actions',
                confirmButton: 'swal-maintenance-confirm',
                cancelButton: 'swal-maintenance-cancel'
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                yiiForm.data('confirmed', true);
                showLoader();
                yiiForm.trigger('submit');
            }
        });

        return false;
    });

    /*
     * Security fallback: hide loader if Yii client validation fails.
     */
    submitButton.addEventListener('click', function () {
        window.setTimeout(function () {
            if (form.querySelector('.has-error')) {
                hideLoader();
            }
        }, 350);
    });
})();
JS, View::POS_READY);
?>

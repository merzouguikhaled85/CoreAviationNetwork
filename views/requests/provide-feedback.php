<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\web\View;
use app\components\UrlIdHelper;

$this->title = 'Provide Feedback';
$encodedRequestId = UrlIdHelper::encode($request->request_id);
$this->params['breadcrumbs'][] = [
    'label' => 'View Reports',
    'url' => ['view-reports', 'id' => $encodedRequestId]
];
$this->params['breadcrumbs'][] = $this->title;

/* FEEDBACK REVIEW 2026: display-only operational request values. */
$aircraft = $request->aircraft;
$operator = $request->aO;
$operatorName = $operator
    ? ($operator->company_name ?: trim(($operator->first_name ?: '') . ' ' . ($operator->last_name ?: '')) ?: $operator->username)
    : 'N/A';
$aircraftName = $aircraft
    ? trim(($aircraft->manufacturer ?: '') . ' ' . ($aircraft->model ?: ''))
    : 'Aircraft deleted';
$aircraftName = $aircraftName !== '' ? $aircraftName : 'N/A';
$formatOperationalDate = static function ($value) {
    if (empty($value)) {
        return 'N/A';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y H:i', $timestamp) : $value;
};

$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    ['position' => View::POS_HEAD]
);
$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    ['position' => View::POS_END]
);
?>

<style>
/* ================================
   Page layout
================================ */
.feedback-page {
    padding: 20px 0 40px;
}

.feedback-header {
    margin-bottom: 25px;
}

.feedback-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 30px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 8px;
}

.feedback-title i {
    color: #2563eb;
    font-size: 27px;
}

.feedback-subtitle {
    color: #6b7280;
    font-size: 15px;
    margin: 0;
}

/* ================================
   Progress bar design
================================ */
.progress-bar-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 25px 0 35px;
    padding: 18px;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5e7eb;
}

.progress-bar-step {
    position: relative;
    padding: 10px 16px;
    border-radius: 999px;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    transition: all 0.3s ease;
}

.progress-bar-step.step-active {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.28);
}

/* ================================
   Card container
================================ */
.feedback-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.feedback-card-header {
    padding: 24px 28px;
    background: linear-gradient(135deg, #f8fafc, #eef2ff);
    border-bottom: 1px solid #e5e7eb;
}

.feedback-card-title {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 6px;
}

.feedback-card-title i {
    color: #2563eb;
    font-size: 20px;
}

.feedback-card-description {
    color: #6b7280;
    font-size: 14px;
    margin: 0;
}

.feedback-card-body {
    padding: 28px;
}

/* ================================
   Rating rows
================================ */
.rating-list {
    display: flex;
    flex-direction: column;
    gap: 18px;
    margin-bottom: 25px;
}

.rating-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    padding: 22px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    transition: all 0.25s ease;
}

.rating-item:hover {
    background: #ffffff;
    border-color: #bfdbfe;
    box-shadow: 0 10px 25px rgba(37, 99, 235, 0.08);
    transform: translateY(-2px);
}

.rating-info {
    flex: 1;
}

.rating-title {
    font-size: 17px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 6px;
}

.rating-title .required {
    color: #dc2626;
}

.rating-description {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}

.rating-control {
    min-width: 245px;
    text-align: right;
}

/* ================================
   Stars design
================================ */
.stars {
    display: inline-flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 4px;
}

.stars input[type="radio"] {
    display: none;
}

.stars label {
    font-size: 42px;
    line-height: 1;
    color: #d1d5db;
    cursor: pointer;
    transition: transform 0.2s ease, color 0.2s ease;
    margin: 0;
}

.stars label::before {
    content: '\2605';
}

.stars label:hover,
.stars label:hover ~ label {
    color: #fbbf24;
    transform: scale(1.08);
}

.stars input[type="radio"]:checked ~ label {
    color: #f59e0b;
}

.rating-error {
    display: none;
    margin-top: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #dc2626;
    text-align: right;
}

.rating-item.has-error {
    border-color: #fecaca;
    background: #fff1f2;
}

.rating-item.has-error .rating-error {
    display: block;
}

/* ================================
   Textarea design
================================ */
.feedback-text-wrapper {
    margin-top: 8px;
}

.feedback-text-wrapper .control-label {
    font-weight: 700;
    color: #111827;
    margin-bottom: 8px;
}

.feedback-text-wrapper textarea {
    resize: vertical;
    min-height: 150px;
    border-radius: 14px !important;
    border: 1px solid #d1d5db !important;
    padding: 14px 16px !important;
    font-size: 14px;
    box-shadow: none !important;
    transition: all 0.25s ease;
}

.feedback-text-wrapper textarea:focus {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12) !important;
}

/* ================================
   Alert message
================================ */
.feedback-validation-alert {
    display: none;
    margin-bottom: 20px;
    padding: 14px 16px;
    border-radius: 14px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    font-weight: 600;
    font-size: 14px;
}

/* ================================
   Submit button
================================ */
.feedback-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 25px;
}

.btn-submit-feedback {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-width: 180px;
    padding: 12px 24px;
    border: none;
    border-radius: 9px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff !important;
    font-size: 15px;
    font-weight: 700;
    box-shadow: 0 12px 25px rgba(37, 99, 235, 0.28);
    transition: all 0.25s ease;
}

.btn-submit-feedback:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 32px rgba(37, 99, 235, 0.36);
    background: linear-gradient(135deg, #1d4ed8, #1e40af);
}

.btn-submit-feedback:active {
    transform: translateY(0);
}

.btn-submit-feedback.is-loading {
    pointer-events: none;
    opacity: 0.85;
}

.btn-submit-feedback .spinner {
    display: none;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.45);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.btn-submit-feedback.is-loading .spinner {
    display: inline-block;
}

.btn-submit-feedback.is-loading .submit-icon {
    display: none;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* FEEDBACK REVIEW 2026: professional workflow stepper aligned with the MRO screens. */
.feedback-stepper-card {
    --step-circle: 40px;
    --step-width: 128px;
    position: relative;
    padding: 18px 18px 20px;
    margin: 18px 0;
    overflow: hidden;
    border: 1px solid #dbe5f0;
    border-radius: 16px;
    background: rgba(255, 255, 255, .97);
    box-shadow: 0 12px 30px rgba(15, 23, 42, .07);
}
.feedback-stepper-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}
.feedback-stepper-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    color: #0f172a;
    font-size: 14px;
    font-weight: 900;
}
.feedback-stepper-title i { color: #2563eb; }
.feedback-stepper-note {
    padding: 6px 10px;
    border-radius: 999px;
    color: #1d4ed8;
    background: #eff6ff;
    font-size: 11px;
    font-weight: 900;
    white-space: nowrap;
}
.feedback-stepper-scroll {
    overflow-x: auto;
    overflow-y: hidden;
    padding: 8px 2px;
    scrollbar-width: thin;
}
.feedback-stepper {
    display: grid;
    grid-template-columns: repeat(8, minmax(var(--step-width), 1fr));
    min-width: calc(var(--step-width) * 8);
}
.feedback-step {
    position: relative;
    min-width: var(--step-width);
    text-align: center;
}
.feedback-step:not(:first-child)::before {
    content: "";
    position: absolute;
    z-index: 1;
    top: calc(var(--step-circle) / 2);
    left: calc(-50% + 32px);
    width: calc(100% - 64px);
    height: 4px;
    border-radius: 999px;
    background: #e2e8f0;
    transform: translateY(-50%);
}
.feedback-step.is-done::before,
.feedback-step.is-active::before {
    background: linear-gradient(90deg, #22c55e, #2563eb);
}
.feedback-step-circle {
    position: relative;
    z-index: 2;
    width: var(--step-circle);
    height: var(--step-circle);
    margin: 0 auto 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #e2e8f0;
    border-radius: 50%;
    color: #94a3b8;
    background: #f8fafc;
    box-shadow: 0 6px 14px rgba(15, 23, 42, .08);
}
.feedback-step-label {
    display: block;
    max-width: 112px;
    margin: 0 auto;
    color: #64748b;
    font-size: 10px;
    font-weight: 800;
    line-height: 1.25;
}
.feedback-step.is-done .feedback-step-circle {
    color: #fff;
    border-color: #22c55e;
    background: #22c55e;
}
.feedback-step.is-done .feedback-step-label { color: #15803d; }
.feedback-step.is-active .feedback-step-circle {
    color: #fff;
    border-color: #2563eb;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    box-shadow: 0 10px 20px rgba(37, 99, 235, .26), 0 0 0 6px rgba(37, 99, 235, .1);
}
.feedback-step.is-active .feedback-step-label { color: #1d4ed8; font-weight: 900; }

/* FEEDBACK REVIEW 2026: concise, non-duplicated request context. */
.request-context-card {
    padding: 18px;
    margin-bottom: 18px;
    border: 1px solid #dbe5f0;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .07);
}
.request-context-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 13px;
}
.request-context-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    color: #0f172a;
    font-size: 15px;
    font-weight: 900;
}
.request-context-title i { color: #2563eb; }
.request-context-id {
    padding: 6px 10px;
    border-radius: 999px;
    color: #1d4ed8;
    background: #eff6ff;
    font-size: 11px;
    font-weight: 900;
}
.request-context-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}
.request-context-item {
    min-width: 0;
    min-height: 70px;
    padding: 11px 12px;
    border: 1px solid #dde7f1;
    border-radius: 10px;
    background: #f8fafc;
}
.request-context-item.wide { grid-column: span 2; }
.request-context-label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
    color: #718096;
    font-size: 9px;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.request-context-value {
    color: #0f172a;
    font-size: 12px;
    font-weight: 800;
    line-height: 1.4;
    overflow-wrap: anywhere;
}
.request-context-dates {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-top: 10px;
}
.request-context-text {
    width: 100%;
    min-height: 82px;
    margin-top: 10px;
    padding: 11px 12px;
    resize: vertical;
    border: 1px dashed #b8cbe1;
    border-radius: 10px;
    color: #334155;
    background: #fff;
    font: inherit;
    font-size: 12px;
    line-height: 1.5;
}

/* FEEDBACK REVIEW 2026: compact confirmation dialog. */
.swal2-popup.feedback-confirm-popup {
    width: 390px !important;
    max-width: 92vw !important;
    padding: 18px 20px !important;
    border-radius: 12px !important;
}
.swal2-popup.feedback-confirm-popup .swal2-icon {
    width: 54px !important;
    height: 54px !important;
    margin: 8px auto 12px !important;
}
.swal2-popup.feedback-confirm-popup .swal2-icon-content { font-size: 32px !important; }
.swal2-title.feedback-confirm-title {
    padding: 0 !important;
    color: #0f172a !important;
    font-size: 20px !important;
    font-weight: 900 !important;
}
.swal2-html-container.feedback-confirm-message {
    color: #475569 !important;
    font-size: 13px !important;
    line-height: 1.45 !important;
}
.feedback-swal-confirm,
.feedback-swal-cancel {
    min-width: 120px !important;
    min-height: 39px !important;
    border: 0 !important;
    border-radius: 9px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    font-size: 12px !important;
    font-weight: 900 !important;
}
.swal2-popup.feedback-confirm-popup .swal2-actions {
    gap: 16px !important;
    margin-top: 16px !important;
}
.feedback-swal-confirm i,
.feedback-swal-cancel i {
    flex: 0 0 auto;
    font-size: 14px;
    line-height: 1;
}
.feedback-swal-confirm { color: #fff !important; background: #2563eb !important; }
.feedback-swal-cancel { color: #334155 !important; background: #e2e8f0 !important; }

/* ================================
   Responsive design
================================ */
@media (max-width: 992px) {
    .rating-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .rating-control {
        width: 100%;
        min-width: auto;
        text-align: left;
    }

    .rating-error {
        text-align: left;
    }

    .request-context-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .feedback-title {
        font-size: 24px;
    }

    .feedback-card-body {
        padding: 20px;
    }

    .feedback-card-header {
        padding: 20px;
    }

    .rating-item {
        padding: 18px;
    }

    .stars label {
        font-size: 34px;
    }

    .progress-bar-container {
        padding: 14px;
        gap: 8px;
    }

    .progress-bar-step {
        font-size: 12px;
        padding: 8px 12px;
    }

    .feedback-actions {
        justify-content: stretch;
    }

    .btn-submit-feedback {
        width: 100%;
    }

    .feedback-stepper-head,
    .request-context-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .request-context-grid,
    .request-context-dates {
        grid-template-columns: 1fr;
    }

    .request-context-item.wide {
        grid-column: auto;
    }
}
</style>

<!-- SHARED FORM SYSTEM: presentation only; feedback scoring rules remain unchanged. -->
<div class="feedback-page can-form-page">

    <div class="feedback-header">
        <!-- FEEDBACK REVIEW 2026: visual marker for the evaluation page title. -->
        <h1 class="feedback-title"><i class="bi bi-star-half"></i><?= Html::encode($this->title) ?></h1>
        <p class="feedback-subtitle">
            Please rate the MRO service quality and share your feedback.
        </p>
    </div>

    <!-- FEEDBACK REVIEW 2026: AO Feedback is active; Request Closed remains pending until submission. -->
    <?php
    $currentStep = 7;
    $feedbackSteps = [
        1 => ['label' => 'Create a Request', 'icon' => 'bi-person-fill'],
        2 => ['label' => 'MRO Quote', 'icon' => 'bi-file-earmark-text-fill'],
        3 => ['label' => 'PO Loaded', 'icon' => 'bi-upload'],
        4 => ['label' => 'PO Accepted By MRO', 'icon' => 'bi-check-circle-fill'],
        5 => ['label' => 'Work Started', 'icon' => 'bi-play-circle-fill'],
        6 => ['label' => 'MRO Report', 'icon' => 'bi-file-earmark-bar-graph'],
        7 => ['label' => 'AO Feedback', 'icon' => 'bi-star-fill'],
        8 => ['label' => 'Request Closed', 'icon' => 'bi-check2-circle'],
    ];
    ?>
    <section class="feedback-stepper-card">
        <div class="feedback-stepper-head">
            <h2 class="feedback-stepper-title"><i class="bi bi-signpost-split"></i> Request progress</h2>
            <span class="feedback-stepper-note"><i class="bi bi-star-fill"></i> Step 7 of 8</span>
        </div>
        <div class="feedback-stepper-scroll" aria-label="Request progress">
            <div class="feedback-stepper">
                <?php foreach ($feedbackSteps as $number => $step): ?>
                    <?php
                    if ($number < $currentStep) {
                        $stepClass = 'is-done';
                        $stepIcon = 'bi-check-lg';
                    } elseif ($number === $currentStep) {
                        $stepClass = 'is-active';
                        $stepIcon = $step['icon'];
                    } else {
                        $stepClass = 'is-pending';
                        $stepIcon = $step['icon'];
                    }
                    ?>
                    <div class="feedback-step <?= Html::encode($stepClass) ?>">
                        <span class="feedback-step-circle"><i class="bi <?= Html::encode($stepIcon) ?>"></i></span>
                        <span class="feedback-step-label"><?= Html::encode($step['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FEEDBACK REVIEW 2026: operational request details shown once before evaluation. -->
    <section class="request-context-card">
        <div class="request-context-head">
            <h2 class="request-context-title"><i class="bi bi-airplane-engines"></i> Current Request Details</h2>
            <span class="request-context-id"><i class="bi bi-hash"></i> Request <?= Html::encode($request->request_id) ?></span>
        </div>
        <div class="request-context-grid">
            <div class="request-context-item wide">
                <div class="request-context-label"><i class="bi bi-building"></i> Aircraft Operator / CAMO</div>
                <div class="request-context-value"><?= Html::encode($operatorName) ?></div>
            </div>
            <div class="request-context-item wide">
                <div class="request-context-label"><i class="bi bi-airplane"></i> Aircraft</div>
                <div class="request-context-value"><?= Html::encode($aircraftName) ?></div>
            </div>
            <div class="request-context-item">
                <div class="request-context-label"><i class="bi bi-card-text"></i> Registration</div>
                <div class="request-context-value"><?= Html::encode($request->aircraft_registration ?: 'N/A') ?></div>
            </div>
            <div class="request-context-item">
                <div class="request-context-label"><i class="bi bi-upc-scan"></i> Serial Number</div>
                <div class="request-context-value"><?= Html::encode($request->serial_number ?: 'N/A') ?></div>
            </div>
            <div class="request-context-item wide">
                <div class="request-context-label"><i class="bi bi-geo-alt"></i> Maintenance Location</div>
                <div class="request-context-value"><?= Html::encode($request->location ?: 'N/A') ?></div>
            </div>
        </div>
        <div class="request-context-dates">
            <div class="request-context-item">
                <div class="request-context-label"><i class="bi bi-calendar-event"></i> ETA</div>
                <div class="request-context-value"><?= Html::encode($formatOperationalDate($request->eta)) ?></div>
            </div>
            <div class="request-context-item">
                <div class="request-context-label"><i class="bi bi-calendar-check"></i> ETD</div>
                <div class="request-context-value"><?= Html::encode($formatOperationalDate($request->etd)) ?></div>
            </div>
        </div>
        <?= Html::textarea('request_details_display', $request->request_details ?: 'No request information available.', [
            'class' => 'request-context-text',
            'readonly' => true,
            'aria-label' => 'Request information',
        ]) ?>
    </section>

    <div class="feedback-card">
        <div class="feedback-card-header">
            <!-- FEEDBACK REVIEW 2026: visual marker for the MRO evaluation section. -->
            <h2 class="feedback-card-title"><i class="bi bi-clipboard2-check-fill"></i> MRO Performance Evaluation</h2>
            <p class="feedback-card-description">
                Select a rating for each required category before submitting your feedback.
            </p>
        </div>

        <div class="feedback-card-body">

            <?php $form = ActiveForm::begin([
                'id' => 'feedback-form',
                'options' => [
                    'novalidate' => true
                ],
            ]); ?>

            <!-- Hidden input for Capability & Certifications rating -->
            <?= $form->field($feedback, 'kept_to_agreed_schedule_rating')
                ->hiddenInput(['id' => 'kept_to_agreed_schedule_rating'])
                ->label(false) ?>

            <!-- Hidden input for Turnaround Time & Reliability rating -->
            <?= $form->field($feedback, 'kept_to_agreed_cost_rating')
                ->hiddenInput(['id' => 'kept_to_agreed_cost_rating'])
                ->label(false) ?>

            <!-- Hidden input for Pricing & Contract Terms rating -->
            <?= $form->field($feedback, 'overall_communication_rating')
                ->hiddenInput(['id' => 'overall_communication_rating'])
                ->label(false) ?>

            <!-- Custom validation alert -->
            <div class="feedback-validation-alert" id="feedback-validation-alert">
                Please select ratings for all required fields before submitting.
            </div>

            <div class="rating-list">

                <!-- Rating item 1 -->
                <div class="rating-item" data-rating-item="kept_to_agreed_schedule_rating">
                    <div class="rating-info">
                        <h4 class="rating-title">
                            Capability & Certifications <span class="required">*</span>
                        </h4>
                        <p class="rating-description">
                            Type approvals, scope of services, and experience with your fleet.
                        </p>
                    </div>

                    <div class="rating-control">
                        <div class="stars" id="kept-to-agreed-schedule-rating-input">
                            <input type="radio" id="schedule-star5" name="schedule-star" value="5">
                            <label for="schedule-star5" title="5 stars"></label>

                            <input type="radio" id="schedule-star4" name="schedule-star" value="4">
                            <label for="schedule-star4" title="4 stars"></label>

                            <input type="radio" id="schedule-star3" name="schedule-star" value="3">
                            <label for="schedule-star3" title="3 stars"></label>

                            <input type="radio" id="schedule-star2" name="schedule-star" value="2">
                            <label for="schedule-star2" title="2 stars"></label>

                            <input type="radio" id="schedule-star1" name="schedule-star" value="1">
                            <label for="schedule-star1" title="1 star"></label>
                        </div>

                        <div class="rating-error">
                            This rating is required.
                        </div>
                    </div>
                </div>

                <!-- Rating item 2 -->
                <div class="rating-item" data-rating-item="kept_to_agreed_cost_rating">
                    <div class="rating-info">
                        <h4 class="rating-title">
                            Turnaround Time & Reliability <span class="required">*</span>
                        </h4>
                        <p class="rating-description">
                            Service efficiency, AOG support, location and logistics.
                        </p>
                    </div>

                    <div class="rating-control">
                        <div class="stars" id="kept-to-agreed-cost-rating-input">
                            <input type="radio" id="cost-star5" name="cost-star" value="5">
                            <label for="cost-star5" title="5 stars"></label>

                            <input type="radio" id="cost-star4" name="cost-star" value="4">
                            <label for="cost-star4" title="4 stars"></label>

                            <input type="radio" id="cost-star3" name="cost-star" value="3">
                            <label for="cost-star3" title="3 stars"></label>

                            <input type="radio" id="cost-star2" name="cost-star" value="2">
                            <label for="cost-star2" title="2 stars"></label>

                            <input type="radio" id="cost-star1" name="cost-star" value="1">
                            <label for="cost-star1" title="1 star"></label>
                        </div>

                        <div class="rating-error">
                            This rating is required.
                        </div>
                    </div>
                </div>

                <!-- Rating item 3 -->
                <div class="rating-item" data-rating-item="overall_communication_rating">
                    <div class="rating-info">
                        <h4 class="rating-title">
                            Pricing & Contract Terms <span class="required">*</span>
                        </h4>
                        <p class="rating-description">
                            Cost transparency, warranty and support, and regulatory compliance.
                        </p>
                    </div>

                    <div class="rating-control">
                        <div class="stars" id="overall-communication-rating-input">
                            <input type="radio" id="communication-star5" name="communication-star" value="5">
                            <label for="communication-star5" title="5 stars"></label>

                            <input type="radio" id="communication-star4" name="communication-star" value="4">
                            <label for="communication-star4" title="4 stars"></label>

                            <input type="radio" id="communication-star3" name="communication-star" value="3">
                            <label for="communication-star3" title="3 stars"></label>

                            <input type="radio" id="communication-star2" name="communication-star" value="2">
                            <label for="communication-star2" title="2 stars"></label>

                            <input type="radio" id="communication-star1" name="communication-star" value="1">
                            <label for="communication-star1" title="1 star"></label>
                        </div>

                        <div class="rating-error">
                            This rating is required.
                        </div>
                    </div>
                </div>

            </div>

            <div class="feedback-text-wrapper">
                <?= $form->field($feedback, 'feedback_text')->textarea([
                    'rows' => 6,
                    'placeholder' => 'Write your feedback here...',
                ])->label('Feedback (Optional)') ?>
            </div>

            <div class="feedback-actions">
                <?= Html::submitButton(
                    '<span class="spinner"></span><i class="bi bi-send-fill submit-icon"></i><span class="btn-text">Submit Feedback</span>',
                    [
                        'class' => 'btn btn-submit-feedback',
                        'id' => 'submit-feedback-btn',
                    ]
                ) ?>
            </div>

            <?php ActiveForm::end(); ?>

        </div>
    </div>
</div>

<?php
$js = <<<JS
$(document).ready(function() {

    // Update hidden input value and remove error state
    function updateRating(hiddenInputId, ratingItemName, value) {
        $('#' + hiddenInputId).val(value);

        var ratingItem = $('[data-rating-item="' + ratingItemName + '"]');
        ratingItem.removeClass('has-error');

        checkAllRatings();
    }

    // Check if all required ratings are selected
    function checkAllRatings() {
        var scheduleRating = $('#kept_to_agreed_schedule_rating').val();
        var costRating = $('#kept_to_agreed_cost_rating').val();
        var communicationRating = $('#overall_communication_rating').val();

        if (scheduleRating && costRating && communicationRating) {
            $('#feedback-validation-alert').slideUp(150);
            return true;
        }

        return false;
    }

    // Validate all required rating fields
    function validateRatings() {
        var isValid = true;

        var requiredRatings = [
            'kept_to_agreed_schedule_rating',
            'kept_to_agreed_cost_rating',
            'overall_communication_rating'
        ];

        requiredRatings.forEach(function(fieldId) {
            var fieldValue = $('#' + fieldId).val();
            var ratingItem = $('[data-rating-item="' + fieldId + '"]');

            if (!fieldValue) {
                ratingItem.addClass('has-error');
                isValid = false;
            } else {
                ratingItem.removeClass('has-error');
            }
        });

        if (!isValid) {
            $('#feedback-validation-alert').slideDown(180);

            // Scroll to validation message
            $('html, body').animate({
                scrollTop: $('#feedback-validation-alert').offset().top - 120
            }, 350);
        } else {
            $('#feedback-validation-alert').slideUp(150);
        }

        return isValid;
    }

    // Rating 1 change event
    $('#kept-to-agreed-schedule-rating-input input[type="radio"]').on('change', function() {
        updateRating(
            'kept_to_agreed_schedule_rating',
            'kept_to_agreed_schedule_rating',
            $(this).val()
        );
    });

    // Rating 2 change event
    $('#kept-to-agreed-cost-rating-input input[type="radio"]').on('change', function() {
        updateRating(
            'kept_to_agreed_cost_rating',
            'kept_to_agreed_cost_rating',
            $(this).val()
        );
    });

    // Rating 3 change event
    $('#overall-communication-rating-input input[type="radio"]').on('change', function() {
        updateRating(
            'overall_communication_rating',
            'overall_communication_rating',
            $(this).val()
        );
    });

    // FEEDBACK REVIEW 2026: confirmation is always displayed before validation and final submission.
    $('#feedback-form').on('submit', function(e) {
        var form = this;
        e.preventDefault();

        function validateAndSubmit() {
            if (!validateRatings()) {
                return;
            }

            $('#submit-feedback-btn').addClass('is-loading');
            $('#submit-feedback-btn .btn-text').text('Submitting...');
            $('#submit-feedback-btn').prop('disabled', true);

            // Native submit avoids reopening the confirmation after the user has approved it.
            form.submit();
        }

        if (typeof Swal === 'undefined') {
            if (window.confirm('Submit this feedback?')) {
                validateAndSubmit();
            }
            return false;
        }

        Swal.fire({
            title: 'Submit feedback?',
            html: '<strong>Please confirm that the ratings and comments are final.</strong>',
            icon: 'question',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
            confirmButtonText: '<i class="bi bi-send-fill"></i> Submit Feedback',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
            buttonsStyling: false,
            customClass: {
                // SHARED FORM CONFIRMATION: feedback values are still submitted only after confirmation.
                popup: 'feedback-confirm-popup can-form-swal',
                title: 'feedback-confirm-title',
                htmlContainer: 'feedback-confirm-message',
                confirmButton: 'feedback-swal-confirm',
                cancelButton: 'feedback-swal-cancel'
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                validateAndSubmit();
            }
        });

        return false;
    });

});
JS;

$this->registerJs($js);
?>

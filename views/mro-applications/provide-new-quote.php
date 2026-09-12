<?php

use yii\helpers\Html;
use app\components\UrlIdHelper;
use yii\widgets\ActiveForm;

$this->title = 'Provide New Quote';
$this->params['breadcrumbs'][] = ['label' => 'View Reports', 'url' => ['view-reports', 'id' => UrlIdHelper::encode($report->mro_request_apply_id)]];
$this->params['breadcrumbs'][] = $this->title;

// Bootstrap Icons
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]
);

$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css',
    ['position' => \yii\web\View::POS_HEAD]
);

$this->registerCss(<<<CSS
:root {
  --deep-blue: #0D3261;
  --sky: #00B2FF;
  --gold: #EED811;
  --soft-border: #D8E4EE;
  --soft-bg: #F4F7FC;
  --text-main: #0F172A;
  --text-muted: #6B7280;
  --success: #28a745;
  --active: #007bff;
}

.quote-page-wrapper {
  min-height: calc(100vh - 100px);
  padding: 16px 24px 32px;
  margin: 0;
  background:
    radial-gradient(circle at 20% 50%, rgba(13, 50, 97, 0.06) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(0, 178, 255, 0.06) 0%, transparent 50%),
    linear-gradient(145deg, #EFF5FF 0%, #FFFFFF 45%, #F8FBFF 100%);
  overflow-x: hidden;
}

.quote-page-card {
  width: 100%;
  max-width: 100%;
  background: #FFFFFF;
  border-radius: 22px;
  border: 1px solid var(--soft-border);
  box-shadow:
      0 16px 40px rgba(15, 23, 42, 0.14),
      0 0 0 1px rgba(255,255,255,0.9);
  overflow: hidden;
  position: relative;
  z-index: 1;
}

.quote-page-card::before {
  content: "";
  position: absolute;
  inset: 0 0 auto 0;
  height: 5px;
  background: linear-gradient(135deg, var(--deep-blue), var(--sky), var(--gold));
  background-size: 220% 220%;
  animation: quoteGradient 8s ease infinite;
}

@keyframes quoteGradient {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.quote-header {
  padding: 22px 28px 14px;
  background: linear-gradient(135deg, #FFFFFF, #F4F8FF);
  border-bottom: 1px solid rgba(226,232,240,0.7);
}

.quote-title-row {
  display: flex;
  align-items: center;
  gap: 18px;
  flex-wrap: wrap;
}

.quote-title-icon {
  width: 58px;
  height: 58px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #E7F4FF;
  color: var(--deep-blue);
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.28);
  position: relative;
  overflow: hidden;
}

.quote-title-icon::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.32) 50%, transparent 70%);
  animation: quoteShine 3s infinite linear;
}

@keyframes quoteShine {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.quote-title-icon i {
  font-size: 27px;
  line-height: 1;
  position: relative;
  z-index: 1;
}

.quote-title {
  font-size: 24px;
  font-weight: 700;
  margin: 2px 0 4px;
  letter-spacing: -0.02em;
  color: var(--text-main);
}

.quote-subtitle {
  font-size: 14px;
  color: var(--text-muted);
  max-width: 900px;
}

.quote-body {
  padding: 20px 24px 26px;
}

/* Progress workflow */
.workflow-wrapper {
  border-radius: 18px;
  border: 1px solid var(--soft-border);
  background: #F9FBFF;
  padding: 18px 18px 12px;
  margin-bottom: 22px;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.progress-bar-container {
  display: flex;
  align-items: flex-start;
  gap: 0;
  min-width: 1050px;
  margin-bottom: 0;
  white-space: nowrap;
}

.progress-bar-step {
  flex: 1;
  text-align: center;
  position: relative;
  color: #94A3B8;
  font-size: 12px;
  font-weight: 700;
  padding: 34px 10px 0;
  line-height: 1.3;
}

.progress-bar-step::before {
  content: "";
  position: absolute;
  top: 13px;
  left: calc(-50% + 16px);
  width: calc(100% - 32px);
  height: 3px;
  background: #DDE7F3;
  z-index: 0;
}

.progress-bar-step:first-child::before {
  display: none;
}

.progress-bar-step::after {
  content: "";
  position: absolute;
  top: 4px;
  left: 50%;
  transform: translateX(-50%);
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 3px solid #CBD5E1;
  background: #FFFFFF;
  z-index: 1;
  box-shadow: 0 4px 10px rgba(15,23,42,0.12);
}

.progress-bar-step.step-realized {
  color: var(--success);
}

.progress-bar-step.step-realized::before {
  background: linear-gradient(135deg, var(--success), #74D99F);
}

.progress-bar-step.step-realized::after {
  border-color: var(--success);
  background: var(--success);
}

.progress-bar-step.step-active {
  color: var(--active);
}

.progress-bar-step.step-active::before {
  background: linear-gradient(135deg, var(--success), var(--active));
}

.progress-bar-step.step-active::after {
  border-color: var(--active);
  background: #FFFFFF;
  box-shadow: 0 0 0 6px rgba(0,123,255,0.12), 0 8px 18px rgba(0,123,255,0.25);
}

/* Form */
.repair-report-form {
  border-radius: 18px;
  border: 1px solid var(--soft-border);
  background: #FFFFFF;
  box-shadow: 0 10px 28px rgba(15,23,42,0.08);
  padding: 22px;
}

.repair-report-form .form-group,
.repair-report-form .mb-3 {
  margin-bottom: 18px;
}

.repair-report-form label,
.repair-report-form .control-label {
  color: var(--text-main);
  font-weight: 700;
  font-size: 13px;
  margin-bottom: 8px;
}

.repair-report-form textarea.form-control,
.repair-report-form input.form-control {
  border-radius: 12px;
  border: 1px solid var(--soft-border);
  color: var(--text-main);
  font-size: 14px;
  box-shadow: none;
  transition: all .15s ease;
}

.repair-report-form textarea.form-control:focus,
.repair-report-form input.form-control:focus {
  border-color: var(--sky);
  box-shadow: 0 0 0 4px rgba(0,178,255,0.12);
}

.repair-report-form textarea.form-control {
  min-height: 170px;
  resize: vertical;
}

.quote-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 8px;
}

.btn-submit-quote {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .5rem;
  padding: .55rem 1.05rem;
  border-radius: 12px;
  font-weight: 700;
  font-size: .9rem;
  border: none;
  background: linear-gradient(180deg, #0EA5E9, #0284C7);
  color: #FFFFFF;
  box-shadow: 0 8px 20px rgba(2,132,199,.35), inset 0 1px 0 rgba(255,255,255,.15);
  transition: all .15s ease;
}

.btn-submit-quote:hover,
.btn-submit-quote:focus {
  background: linear-gradient(180deg, #38BDF8, #0369A1);
  color: #FFFFFF;
  transform: translateY(-1px);
}

@media (max-width: 992px) {
  .quote-page-wrapper { padding: 12px 12px 24px; }
  .quote-header { padding: 18px 16px 12px; }
  .quote-body { padding: 16px 12px 20px; }
  .quote-title { font-size: 22px; }
}

@media (max-width: 768px) {
  .quote-page-wrapper {
    margin: 0 !important;
    padding: 14px 12px 22px !important;
  }

  .quote-title-row {
    align-items: flex-start;
  }

  .quote-title-icon {
    width: 50px;
    height: 50px;
  }

  .workflow-wrapper {
    padding: 16px 12px 10px;
  }

  .repair-report-form {
    padding: 16px;
  }

  .quote-actions {
    justify-content: stretch;
  }

  .btn-submit-quote {
    width: 100%;
  }
}
CSS);
?>

<div class="quote-page-wrapper">
  <div class="quote-page-card">

    <div class="quote-header">
      <div class="quote-title-row">
        <div class="quote-title-icon">
          <i class="bi bi-file-earmark-text"></i>
        </div>
        <div>
          <div class="quote-title"><?= Html::encode($this->title) ?></div>
          <div class="quote-subtitle">
            Submit a revised MRO quotation and attach the related quote document if required.
          </div>
        </div>
      </div>
    </div>

    <div class="quote-body">

      <!-- Multi-step progress bar -->
      <div class="workflow-wrapper">
        <div class="progress-bar-container">
          <span class="progress-bar-step step-realized">Create a Request</span>
          <span class="progress-bar-step step-realized">MRO Quote</span>
          <span class="progress-bar-step step-realized">PO Loaded</span>
          <span class="progress-bar-step step-realized">PO Accepted By MRO</span>
          <span class="progress-bar-step step-realized">Work Started</span>
          <span class="progress-bar-step step-realized">MRO Report</span>
          <span class="progress-bar-step step-realized">Brief Description Submitted</span>
          <span class="progress-bar-step step-active">New Quote by MRO</span>
          <span class="progress-bar-step">Request Closed</span>
        </div>
      </div>

      <div class="repair-report-form">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

        <?= $form->field($report, 'mro_quote')
          ->textarea([
            'rows' => 6,
            'placeholder' => 'Write the new MRO quote here...'
          ])
          ->label('MRO Quote <span class="text-danger">*</span>') ?>

        <?= $form->field($report, 'mro_attachment')
          ->fileInput(['accept' => 'image/png, image/jpeg, application/pdf'])
          ->label('Attach Quote') ?>

        <div class="quote-actions">
          <?= Html::submitButton('<i class="bi bi-send-check"></i> Submit', [
            'class' => 'btn-submit-quote'
          ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
      </div>

    </div>
  </div>
</div>

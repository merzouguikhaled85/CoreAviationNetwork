<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\widgets\ActiveForm;
use yii\bootstrap5\Modal;

$this->title = 'Aircraft Operator Signup';
$this->params['breadcrumbs'][] = $this->title;
$this->params['bodyClass'] = 'ao-layout';
$this->params['bodyDataTheme'] = 'light';

// Professional icon set used by this view.
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css',
    ['rel' => 'stylesheet', 'position' => \yii\web\View::POS_HEAD]
);

$selectedCities = [];

if (!empty($model->country_id)) {
    $selectedCities = ArrayHelper::map(
        \app\models\Cities::find()
            ->where(['country_id' => $model->country_id])
            ->orderBy(['city_name' => SORT_ASC])
            ->all(),
        'city_id',
        'city_name'
    );
}

$aircraftModelsData = [];

foreach ($models as $aircraftModelItem) {
    $aircraftModelsData[] = [
        'aircraft_model_id' => $aircraftModelItem->aircraft_model_id,
        'manufacturer' => $aircraftModelItem->manufacturer,
        'model' => $aircraftModelItem->model,
    ];
}

$aircraftModelsJson = Json::htmlEncode($aircraftModelsData);
$selectedModelJson = Json::htmlEncode($airplaneModel->model ?? '');
$existingCityJson = Json::htmlEncode((string)($model->city_id ?? ''));
$citiesUrl = Json::htmlEncode(Url::to(['/site/get-cities']));

?>

<section class="ao-hero">
  <div class="ao-hero-inner">

    <div class="ao-card">

      <!-- HEADER / AVIATION CONTEXT -->
      <div class="ao-header">
        <div class="ao-header-left">
          <div class="ao-header-logo">
            <img src="<?= Yii::getAlias('@web/logo/can-logo-main.png') ?>" alt="Core Aviation Network">
          </div>

          <div class="ao-header-text">
            <span class="ao-kicker">Aircraft operator onboarding</span>
            <h1><?= Html::encode($this->title) ?></h1>
            <p>
              Join Core Aviation Network and connect your aircraft operations with trusted aviation partners.
            </p>
          </div>
        </div>

        <div class="ao-header-badge d-none d-md-flex">
          <i class="ri-shield-check-line"></i>
          <span>Secure validation</span>
        </div>
      </div>

      <!-- STEPPER -->
      <div class="ao-steps" aria-label="Aircraft operator signup progress">
        <div class="ao-step ao-step-1 active">
          <span class="ao-step-index">1</span>
          <span class="ao-step-label">Account & secure access</span>
        </div>

        <div class="ao-step-line"></div>

        <div class="ao-step ao-step-2">
          <span class="ao-step-index">2</span>
          <span class="ao-step-label">Company & base details</span>
        </div>

        <div class="ao-step-line"></div>

        <div class="ao-step ao-step-3">
          <span class="ao-step-index">3</span>
          <span class="ao-step-label">Aircraft & certification</span>
        </div>
      </div>

      <div class="ao-body-row ao-layout" data-theme="light">

        <!-- FORM COLUMN -->
        <div class="ao-body-main">

          <?php $form = ActiveForm::begin([
              'id' => 'ao-profile-form',
              'options' => [
                  'class' => 'row g-3',
                  'enctype' => 'multipart/form-data'
              ]
          ]); ?>

          <input type="text" name="fake_username" autocomplete="username" style="display:none">
          <input type="password" name="fake_password" autocomplete="new-password" style="display:none">
          <input type="text" name="fake_country" autocomplete="country" style="display:none">

          <!-- STEP 1 -->
          <div id="step-1" class="step active">

            <div class="step-title-block">
              <h3>Account Information</h3>
              <p>Create your secure operator account.</p>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'username')
                  ->textInput([
                      'maxlength' => true,
                      'class' => 'form-control form-control-lg',
                      'id' => 'username'
                  ])
                  ->label('Username <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'password')
                  ->passwordInput([
                      'maxlength' => true,
                      'class' => 'form-control form-control-lg',
                      'id' => 'password'
                  ])
                  ->label('Password <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'confirm_password')
                  ->passwordInput([
                      'maxlength' => true,
                      'class' => 'form-control form-control-lg',
                      'id' => 'confirm_password'
                  ])
                  ->label('Confirm Password <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'email')
                  ->textInput([
                      'maxlength' => true,
                      'class' => 'form-control form-control-lg',
                      'id' => 'email'
                  ])
                  ->label('Email <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10 ao-nav-buttons">
              <button type="button" class="btn btn-primary" id="next-button">
                <i class="ri-arrow-right-circle-line btn-icon"></i>
                Next: Company details
                <i class="ri-arrow-right-line btn-icon btn-icon-right"></i>
              </button>
            </div>

          </div>

          <!-- STEP 2 -->
          <div id="step-2" class="step" style="display:none;">

            <div class="step-title-block">
              <h3>Company Information</h3>
              <p>Tell us about your company and operating base.</p>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'company_name')
                  ->textInput([
                      'maxlength' => true,
                      'class' => 'form-control form-control-lg',
                      'id' => 'company_name'
                  ])
                  ->label('Company Name') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'profile_photo')
                  ->fileInput([
                      'class' => 'form-control form-control-lg custom-file-real',
                      'accept' => 'image/*',
                      'id' => 'profile_photo',
                      'style' => 'display:none;'
                  ])
                  ->label('Profile Photo') ?>

              <div class="custom-file-ui" data-input="profile_photo">
                <button type="button" class="btn btn-outline-secondary custom-file-btn">
                  <i class="ri-upload-cloud-2-line btn-icon"></i>
                  Choose file
                </button>
                <span class="custom-file-name">No file chosen</span>
              </div>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'first_name')
                  ->textInput([
                      'maxlength' => true,
                      'class' => 'form-control form-control-lg',
                      'id' => 'first_name'
                  ])
                  ->label('First Name <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'last_name')
                  ->textInput([
                      'maxlength' => true,
                      'class' => 'form-control form-control-lg',
                      'id' => 'last_name'
                  ])
                  ->label('Last Name <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'contact_number')
                  ->textInput([
                      'maxlength' => true,
                      'class' => 'form-control form-control-lg',
                      'id' => 'contact_number'
                  ])
                  ->label('Contact Number <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'country_id')->dropDownList(
                  ArrayHelper::map($countries, 'country_id', 'country_name'),
                  [
                      'prompt' => 'Select Country',
                      'id' => 'country-select',
                      'class' => 'form-select form-select-lg',
                      'autocomplete' => 'off',
                      'data-lpignore' => 'true',
                      'data-form-type' => 'other',
                  ]
              )->label('Country <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'city_id')->dropDownList(
                  $selectedCities,
                  [
                      'prompt' => 'Select City',
                      'id' => 'city-select',
                      'class' => 'form-select form-select-lg',
                  ]
              )->label('City <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'address')
                  ->textInput([
                      'maxlength' => true,
                      'class' => 'form-control form-control-lg',
                      'id' => 'address'
                  ])
                  ->label('Address <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'zip_code')
                  ->textInput([
                      'maxlength' => true,
                      'class' => 'form-control form-control-lg',
                      'id' => 'zip_code'
                  ])
                  ->label('Zip Code <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10 ao-nav-buttons d-flex justify-content-between">
              <button type="button" class="btn btn-outline-secondary" id="back-button-step-2">
                <i class="ri-arrow-left-line btn-icon"></i>
                Back
              </button>

              <button type="button" class="btn btn-primary" id="next-to-step-3-button">
                <i class="ri-plane-line btn-icon"></i>
                Next: Aircraft
                <i class="ri-arrow-right-line btn-icon btn-icon-right"></i>
              </button>
            </div>

          </div>

          <!-- STEP 3 -->
          <div id="step-3" class="step" style="display:none;">

            <div class="step-title-block">
              <h3>Aircraft Information</h3>
              <p>Add your aircraft details and certificate information.</p>
            </div>

            <div class="ao-section-card">
              <div class="ao-section-header">
                <div class="ao-section-icon"><i class="ri-plane-line"></i></div>
                <div>
                  <h4>Aircraft identity</h4>
                  <p>Select the aircraft manufacturer, model and registration details.</p>
                </div>
              </div>

              <div class="col-md-10">
              <?= $form->field($airplaneModel, 'manufacturer')->dropDownList(
                  ArrayHelper::map($manufacturers, 'manufacturer', 'manufacturer'),
                  [
                      'prompt' => 'Select Manufacturer',
                      'class' => 'form-select form-select-lg',
                      'id' => 'manufacturer'
                  ]
              )->label('Manufacturer <span class="text-danger">*</span>') ?>
            </div>

            <?= $form->field($airplaneModel, 'aircraft_model_id')
                ->hiddenInput(['id' => 'aircraft-model-id'])
                ->label(false) ?>

            <div class="col-md-10">
              <?= $form->field($airplaneModel, 'model')->dropDownList(
                  [],
                  [
                      'prompt' => 'Select Model',
                      'class' => 'form-select form-select-lg',
                      'id' => 'model-dropdown'
                  ]
              )->label('Model <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($airplaneModel, 'serial_number')
                  ->textInput([
                      'maxlength' => true,
                      'class' => 'form-control form-control-lg',
                      'id' => 'serial_number'
                  ])
                  ->label('Serial Number <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($airplaneModel, 'registration_number')
                  ->textInput([
                      'maxlength' => true,
                      'class' => 'form-control form-control-lg',
                      'id' => 'registration_number'
                  ])
                  ->label('Registration Number <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($airplaneModel, 'certificate_type_id')->dropDownList(
                  ArrayHelper::map(
                      \app\models\CertificateTypes::find()
                          ->orderBy(['type' => SORT_ASC])
                          ->all(),
                      'certificate_type_id',
                      'type'
                  ),
                  [
                      'prompt' => 'Select Certificate Type',
                      'id' => 'certificate-type-dropdown',
                      'class' => 'form-select form-select-lg',
                  ]
              )->label('Certificate Type <span class="text-danger">*</span>') ?>
            </div>

            </div>

            <div class="ao-section-card">
              <div class="ao-section-header">
                <div class="ao-section-icon"><i class="ri-file-shield-2-line"></i></div>
                <div>
                  <h4>Terms & validation</h4>
                  <p>Confirm your agreement before submitting the operator application.</p>
                </div>
              </div>

              <div class="col-md-10 mt-2">
              <div class="form-check">
                <?= Html::checkbox('terms', false, [
                    'class' => 'form-check-input',
                    'id' => 'terms-check'
                ]) ?>

                <?= Html::label(
                    'I agree to the terms and conditions <span class="text-danger">*</span>',
                    'terms-check',
                    ['class' => 'form-check-label']
                ) ?>

                <?= Html::button('<i class="ri-book-open-line btn-icon"></i> Show Terms', [
                    'class' => 'btn btn-sm btn-outline-primary ms-2',
                    'id' => 'show-terms-btn'
                ]) ?>
              </div>

              <div class="terms-content mt-2" style="display:none; max-height:260px; overflow:auto; border-radius:8px; border:1px solid #e5e7eb; padding:10px 12px;">
                <?= $terms ?>
              </div>
            </div>

            </div>

            <div class="col-md-10 ao-nav-buttons d-flex justify-content-between mt-3">
              <button type="button" class="btn btn-outline-secondary" id="back-button-step-3">
                <i class="ri-arrow-left-line btn-icon"></i>
                Back
              </button>

              <?= Html::submitButton('<i class="ri-checkbox-circle-line btn-icon"></i> Submit Application', [
                  'class' => 'btn btn-primary',
                  'id' => 'submit-btn',
                  'disabled' => true
              ]) ?>
            </div>

          </div>

          <?php ActiveForm::end(); ?>

        </div>

        <!-- SIDE COLUMN -->
        <div class="ao-body-side d-none d-lg-block">

          <div class="ao-side-card">
            <div class="ao-side-title">
              <i class="ri-global-line"></i>
              <h5>Why join CAN Operator Network?</h5>
            </div>
            <ul class="mb-0 ps-3">
              <li>Access trusted aviation and maintenance partners</li>
              <li>Centralize aircraft and operator information</li>
              <li>Improve communication with service providers</li>
            </ul>
          </div>

          <div class="ao-side-card">
            <div class="ao-side-title">
              <i class="ri-lightbulb-flash-line"></i>
              <h5>Tips for faster validation</h5>
            </div>
            <ul class="mb-0 ps-3">
              <li>Use a valid corporate or professional email</li>
              <li>Enter accurate aircraft registration details</li>
              <li>Select the correct manufacturer, model and certificate type</li>
            </ul>
          </div>

        </div>

      </div>

    </div>

  </div>
</section>

<style>
/* ===================== GLOBAL DESIGN ===================== */
.ao-hero {
  position: relative;
  padding: 34px 0 42px;
  min-height: calc(100vh - 90px);
  background:
    radial-gradient(circle at 12% 12%, rgba(37, 99, 235, 0.10), transparent 28%),
    radial-gradient(circle at 90% 4%, rgba(14, 165, 233, 0.10), transparent 26%),
    linear-gradient(135deg, #f8fafc 0%, #eef4ff 50%, #ffffff 100%);
}

.ao-hero-inner {
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 16px;
}

.ao-card {
  position: relative;
  overflow: hidden;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 22px 55px rgba(15, 23, 42, 0.13);
  border: 1px solid rgba(226, 232, 240, 0.95);
  padding: 30px;
}

.ao-card::before {
  content: "";
  position: absolute;
  inset: 0 0 auto 0;
  height: 5px;
  background: linear-gradient(90deg, #0b2d5b, #2563eb, #38bdf8);
}

/* ===================== HEADER ===================== */
.ao-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 26px;
}

.ao-header-left {
  display: flex;
  align-items: center;
  gap: 18px;
  min-width: 0;
}

.ao-header-logo {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 86px;
  height: 86px;
  border-radius: 22px;
  background: linear-gradient(145deg, #ffffff, #f1f5f9);
  border: 1px solid #e2e8f0;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
  flex: 0 0 auto;
}

.ao-header-logo img {
  max-height: 62px;
  max-width: 150px;
  object-fit: contain;
}

.ao-kicker {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  margin-bottom: 8px;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.ao-header-text h1 {
  font-weight: 850;
  letter-spacing: -0.03em;
  color: #0f172a;
  margin-bottom: 6px;
}

.ao-header-text p {
  max-width: 680px;
  color: #64748b;
  margin-bottom: 0;
  font-size: 15px;
  line-height: 1.6;
}

.ao-header-badge {
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #0b2d5b;
  font-size: 13px;
  font-weight: 800;
  white-space: nowrap;
}

.ao-header-badge i {
  font-size: 18px;
  color: #2563eb;
}

.ao-body-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 330px;
  gap: 30px;
  align-items: flex-start;
}

.ao-body-main {
  min-width: 0;
}

.ao-body-side {
  width: 100%;
  position: sticky;
  top: 90px;
}

/* ===================== STEPPER ===================== */
.ao-steps {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 30px;
}

.ao-step {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 11px 14px;
  border-radius: 999px;
  transition: all 0.25s ease;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  color: #64748b;
  font-weight: 750;
  flex: 1;
  min-width: 0;
}

.ao-step-label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ao-step.active {
  background: #eff6ff;
  border-color: #2563eb;
  color: #0b2d5b;
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.13);
}

.ao-step-index {
  display: inline-flex;
  width: 28px;
  height: 28px;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #ffffff;
  color: #0b2d5b;
  border: 1px solid #dbeafe;
  font-weight: 850;
  font-size: 13px;
  flex: 0 0 auto;
}

.ao-step.active .ao-step-index {
  color: #ffffff;
  background: #2563eb;
  border-color: #2563eb;
}

.ao-step-line {
  height: 2px;
  width: 28px;
  background: linear-gradient(90deg, #cbd5e1, #e2e8f0);
  border-radius: 999px;
  flex: 0 0 28px;
}

/* ===================== STEP TITLE ===================== */
.step-title-block {
  margin-bottom: 16px;
  padding: 18px 20px;
  border-radius: 18px;
  background: linear-gradient(135deg, #f8fafc, #ffffff);
  border: 1px solid #e2e8f0;
}

.step-title-block h3 {
  color: #0f172a;
  font-weight: 850;
  margin-bottom: 4px;
  letter-spacing: -0.02em;
}

.step-title-block p {
  color: #64748b;
  margin-bottom: 0;
}

/* ===================== SECTION CARDS ===================== */
.ao-section-card {
  width: min(100%, 760px);
  padding: 18px;
  margin-bottom: 16px;
  border-radius: 18px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
}

.ao-section-card .col-md-10 {
  width: 100%;
}

.ao-section-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 14px;
}

.ao-section-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 14px;
  color: #2563eb;
  background: #eff6ff;
  border: 1px solid #dbeafe;
  flex: 0 0 auto;
}

.ao-section-icon i {
  font-size: 21px;
}

.ao-section-header h4 {
  margin: 0 0 3px;
  color: #0f172a;
  font-size: 16px;
  font-weight: 850;
}

.ao-section-header p {
  margin: 0;
  color: #64748b;
  font-size: 13px;
  line-height: 1.5;
}

/* ===================== INPUTS ===================== */
.form-control,
.form-select {
  min-height: 48px;
  border-radius: 12px !important;
  border: 1px solid #cbd5e1;
  background-color: #ffffff;
  color: #0f172a;
  transition: all 0.2s ease;
}

.form-control:hover,
.form-select:hover {
  border-color: #94a3b8;
}

.form-control:focus,
.form-select:focus {
  border-color: #2563eb !important;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12) !important;
}

.form-control.is-invalid,
.form-select.is-invalid,
.has-error .form-control,
.has-error .form-select {
  border-color: #ef4444 !important;
  background-color: #fff7f7 !important;
}

.form-control.is-invalid:focus,
.form-select.is-invalid:focus,
.has-error .form-control:focus,
.has-error .form-select:focus {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12) !important;
}

/* ===================== LABELS ===================== */
.form-label,
.control-label {
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 7px;
}

.text-danger {
  color: #ef4444 !important;
}

/* ===================== ERROR MESSAGES ===================== */
.custom-error-message,
.invalid-feedback,
.help-block,
.help-block-error {
  color: #dc2626 !important;
  font-size: 13px !important;
  margin-top: 6px !important;
  line-height: 1.4 !important;
  font-weight: 600 !important;
  display: block !important;
}

/* ===================== CUSTOM FILE INPUT ===================== */
.custom-file-ui {
  display: flex;
  align-items: center;
  width: 100%;
  min-height: 50px;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  background: #ffffff;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s ease;
}

.custom-file-ui:hover {
  border-color: #94a3b8;
  background: #f8fafc;
}

.custom-file-ui .custom-file-btn {
  height: 50px;
  border-radius: 0;
  border-top: 0;
  border-left: 0;
  border-bottom: 0;
  border-right: 1px solid #cbd5e1;
  padding-left: 18px;
  padding-right: 18px;
  white-space: nowrap;
  background: #f1f5f9;
  color: #0f172a;
  font-weight: 700;
  box-shadow: none;
}

.custom-file-ui .custom-file-name {
  padding: 0 16px;
  color: #64748b;
  font-size: 15px;
  word-break: break-word;
}

.custom-file-ui.is-invalid {
  border-color: #ef4444 !important;
  background: #fff7f7;
}

/* ===================== BUTTONS ===================== */
.btn {
  border-radius: 12px;
  font-weight: 750;
  padding: 10px 18px;
  transition: all 0.2s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  border-color: #2563eb;
  box-shadow: 0 10px 22px rgba(37, 99, 235, 0.24);
}

.btn-primary:hover {
  background: linear-gradient(135deg, #1d4ed8, #1e40af);
  border-color: #1d4ed8;
  transform: translateY(-1px);
}

.btn-primary:disabled {
  opacity: 0.55;
  transform: none;
  box-shadow: none;
}

.btn-outline-secondary,
.btn-outline-primary {
  border-color: #cbd5e1;
  color: #334155;
  background: #ffffff;
}

.btn-outline-secondary:hover,
.btn-outline-primary:hover {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #94a3b8;
}

.btn .btn-icon,
.custom-file-btn .btn-icon {
  display: inline-flex;
  align-items: center;
  margin-right: 6px;
  font-size: 17px;
  line-height: 1;
  vertical-align: -2px;
}

.btn .btn-icon-right {
  margin-left: 6px;
  margin-right: 0;
}

.ao-nav-buttons {
  margin-top: 8px;
}

/* ===================== SIDE CARDS ===================== */
.ao-side-card {
  border-radius: 18px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.07);
  padding: 18px;
  margin-bottom: 16px;
}

.ao-side-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.ao-side-title i {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 12px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 18px;
  flex: 0 0 auto;
}

.ao-side-card h5 {
  color: #0f172a;
  font-weight: 850;
  margin: 0;
  font-size: 15px;
}

.ao-side-card li {
  color: #475569;
  margin-bottom: 7px;
  line-height: 1.55;
}

/* ===================== TERMS ===================== */
.form-check {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.form-check-input {
  width: 18px;
  height: 18px;
  margin-top: 0;
}

.form-check-label {
  color: #334155;
  font-weight: 650;
}

.terms-content {
  background: #f8fafc;
  border-color: #e2e8f0 !important;
  line-height: 1.6;
  color: #475569;
}

/* ===================== RESPONSIVE ===================== */
@media (max-width: 991px) {
  .ao-card {
    padding: 22px;
  }

  .ao-body-row {
    grid-template-columns: 1fr;
  }

  .ao-body-side {
    position: static;
  }

  .ao-steps {
    flex-direction: column;
    align-items: stretch;
  }

  .ao-step-line {
    display: none;
  }

  .ao-step {
    width: 100%;
  }

  .col-md-10,
  .ao-section-card {
    width: 100%;
  }

  .ao-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .ao-header-left {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 575px) {
  .ao-hero {
    padding: 18px 0 28px;
  }

  .ao-card {
    padding: 18px;
    border-radius: 20px;
  }

  .ao-header-logo {
    width: 76px;
    height: 76px;
  }

  .ao-header-text h1 {
    font-size: 24px;
  }

  .step-title-block,
  .ao-section-card,
  .ao-side-card {
    padding: 16px;
  }

  .custom-file-ui {
    align-items: stretch;
    flex-direction: column;
  }

  .custom-file-ui .custom-file-btn {
    width: 100%;
    border-right: 0;
    border-bottom: 1px solid #cbd5e1;
  }

  .custom-file-ui .custom-file-name {
    padding: 12px 14px;
  }

  .ao-nav-buttons.d-flex {
    gap: 10px;
    flex-direction: column;
  }

  .ao-nav-buttons .btn {
    width: 100%;
  }
}
</style>

<?php

$js = <<<JS
// ===================== DATA FROM PHP =====================
var aircraftModels = {$aircraftModelsJson};
var selectedModelOnLoad = {$selectedModelJson};
var existingCityId = {$existingCityJson};
var citiesUrl = {$citiesUrl};

// ===================== VALIDATION HELPERS =====================
function getErrorContainer(field) {
  var id = field.attr('id');

  if (id && \$('.custom-file-ui[data-input="' + id + '"]').length) {
    return \$('.custom-file-ui[data-input="' + id + '"]').closest('.col-md-10');
  }

  var container = field.closest('.form-group');

  if (!container.length) {
    container = field.closest('.mb-3');
  }

  if (!container.length) {
    container = field.closest('.col-md-10');
  }

  if (!container.length) {
    container = field.parent();
  }

  return container;
}

function showFieldError(selector, message) {
  var field = \$(selector);

  if (!field.length) {
    return;
  }

  field.addClass('is-invalid');

  var container = getErrorContainer(field);
  var id = field.attr('id');

  container.find('.custom-error-message').remove();

  if (id && \$('.custom-file-ui[data-input="' + id + '"]').length) {
    \$('.custom-file-ui[data-input="' + id + '"]').after(
      '<div class="custom-error-message">' + message + '</div>'
    );
  } else {
    container.append(
      '<div class="custom-error-message">' + message + '</div>'
    );
  }
}

function clearFieldError(selector) {
  var field = \$(selector);

  if (!field.length) {
    return;
  }

  field.removeClass('is-invalid');

  var container = getErrorContainer(field);
  container.find('.custom-error-message').remove();

  var id = field.attr('id');

  if (id) {
    \$('.custom-file-ui[data-input="' + id + '"]').removeClass('is-invalid');
  }
}

function clearStepErrors(stepSelector) {
  \$(stepSelector).find('.is-invalid').removeClass('is-invalid');
  \$(stepSelector).find('.custom-file-ui').removeClass('is-invalid');
  \$(stepSelector).find('.custom-error-message').remove();
}

// Fonction conservée vide pour éviter les alertes rouges dupliquées.
function addFlashMessage(type, message) {
  return;
}

// ===================== STEPPER =====================
function setActiveStep(step) {
  document.querySelector('.ao-step-1').classList.toggle('active', step === 1);
  document.querySelector('.ao-step-2').classList.toggle('active', step === 2);
  document.querySelector('.ao-step-3').classList.toggle('active', step === 3);
}

// ===================== CUSTOM FILE INPUT =====================
function validateFileSize(inputId) {
  var fileInput = document.getElementById(inputId);

  if (!fileInput) {
    return;
  }

  fileInput.addEventListener('change', function(e) {
    var file = e.target.files[0];

    clearFieldError('#' + inputId);

    if (file && file.size > 5242880) {
      showFieldError('#' + inputId, 'The file size exceeds the 5 MB limit.');
      \$('.custom-file-ui[data-input="' + inputId + '"]').addClass('is-invalid');
      e.target.value = '';
      \$('.custom-file-ui[data-input="' + inputId + '"] .custom-file-name').text('No file chosen');
    }
  });
}

validateFileSize('profile_photo');

\$('.custom-file-ui').on('click', function(e) {
  e.preventDefault();

  var inputId = \$(this).data('input');
  \$('#' + inputId).trigger('click');
});

\$('.custom-file-real').on('change', function() {
  var input = \$(this);
  var inputId = input.attr('id');
  var fileName = 'No file chosen';

  if (this.files && this.files.length > 0) {
    fileName = this.files[0].name;
  }

  \$('.custom-file-ui[data-input="' + inputId + '"]')
    .removeClass('is-invalid')
    .find('.custom-file-name')
    .text(fileName);

  clearFieldError('#' + inputId);
});

// ===================== STEP 1 VALIDATION =====================
\$('#next-button').on('click', function() {
  var username = \$('#username').val().trim();
  var password = \$('#password').val().trim();
  var confirmPassword = \$('#confirm_password').val().trim();
  var email = \$('#email').val().trim();

  var ok = true;

  clearStepErrors('#step-1');

  if (!username) {
    ok = false;
    showFieldError('#username', 'Username is required.');
  } else if (username.length < 3) {
    ok = false;
    showFieldError('#username', 'Username must contain at least 3 characters.');
  }

  if (!password) {
    ok = false;
    showFieldError('#password', 'Password is required.');
  } else if (password.length < 6) {
    ok = false;
    showFieldError('#password', 'Password must contain at least 6 characters.');
  }

  if (!confirmPassword) {
    ok = false;
    showFieldError('#confirm_password', 'Confirm password is required.');
  } else if (confirmPassword !== password) {
    ok = false;
    showFieldError('#confirm_password', 'Passwords do not match.');
  }

  if (!email) {
    ok = false;
    showFieldError('#email', 'Email is required.');
  } else {
    var emailRegex = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;

    if (!emailRegex.test(email)) {
      ok = false;
      showFieldError('#email', 'Please enter a valid email address.');
    }
  }

  if (!ok) {
    return;
  }

  \$('#step-1').hide();
  \$('#step-2').show();
  setActiveStep(2);
});

// ===================== STEP 2 NAVIGATION =====================
\$('#back-button-step-2').on('click', function() {
  \$('#step-2').hide();
  \$('#step-1').show();
  setActiveStep(1);
});

\$('#next-to-step-3-button').on('click', function() {
  var firstName = \$('#first_name').val().trim();
  var lastName = \$('#last_name').val().trim();
  var contactNumber = \$('#contact_number').val().trim();
  var country = \$('#country-select').val();
  var city = \$('#city-select').val();
  var address = \$('#address').val().trim();
  var zipCode = \$('#zip_code').val().trim();

  var ok = true;

  clearStepErrors('#step-2');

  if (!firstName) {
    ok = false;
    showFieldError('#first_name', 'First name is required.');
  }

  if (!lastName) {
    ok = false;
    showFieldError('#last_name', 'Last name is required.');
  }

  if (!contactNumber) {
    ok = false;
    showFieldError('#contact_number', 'Contact number is required.');
  }

  if (!country) {
    ok = false;
    showFieldError('#country-select', 'Country is required.');
  }

  if (!city) {
    ok = false;
    showFieldError('#city-select', 'City is required.');
  }

  if (!address) {
    ok = false;
    showFieldError('#address', 'Address is required.');
  }

  if (!zipCode) {
    ok = false;
    showFieldError('#zip_code', 'Zip code is required.');
  }

  if (!ok) {
    return;
  }

  \$('#step-2').hide();
  \$('#step-3').show();
  setActiveStep(3);
});

// ===================== STEP 3 NAVIGATION =====================
\$('#back-button-step-3').on('click', function() {
  \$('#step-3').hide();
  \$('#step-2').show();
  setActiveStep(2);
});

// ===================== AIRCRAFT MODEL DROPDOWN =====================
window.updateModelDropdown = function(selectedManufacturer, selectedModel) {
  var modelDropdown = document.getElementById('model-dropdown');
  var aircraftModelIdInput = document.getElementById('aircraft-model-id');

  if (!modelDropdown || !aircraftModelIdInput) {
    return;
  }

  modelDropdown.innerHTML = '';

  var defaultOption = document.createElement('option');
  defaultOption.value = '';
  defaultOption.text = 'Select Model';
  modelDropdown.appendChild(defaultOption);

  aircraftModels.forEach(function(item) {
    if (item.manufacturer === selectedManufacturer) {
      var option = document.createElement('option');
      option.value = item.model;
      option.text = item.model;

      if (selectedModel && selectedModel === item.model) {
        option.selected = true;
        aircraftModelIdInput.value = item.aircraft_model_id;
      }

      modelDropdown.appendChild(option);
    }
  });

  modelDropdown.onchange = function() {
    aircraftModelIdInput.value = '';

    aircraftModels.forEach(function(item) {
      if (
        item.manufacturer === selectedManufacturer &&
        item.model === modelDropdown.value
      ) {
        aircraftModelIdInput.value = item.aircraft_model_id;
      }
    });

    clearFieldError('#model-dropdown');
  };
};

\$('#manufacturer').on('change', function() {
  updateModelDropdown(this.value, null);
  clearFieldError('#manufacturer');
  clearFieldError('#model-dropdown');
});

if (\$('#manufacturer').val()) {
  updateModelDropdown(\$('#manufacturer').val(), selectedModelOnLoad);
}

// ===================== COUNTRY -> CITY =====================
function loadCities(countryId, selectedCityId) {
  var citySelect = document.getElementById('city-select');

  if (!citySelect) {
    return;
  }

  citySelect.innerHTML = '<option value="">Select City</option>';

  if (!countryId) {
    return;
  }

  fetch(citiesUrl + '?countryId=' + encodeURIComponent(countryId))
    .then(function(response) {
      return response.json();
    })
    .then(function(cities) {
      cities.forEach(function(city) {
        var option = document.createElement('option');
        option.value = city.city_id;
        option.textContent = city.city_name;

        if (selectedCityId && String(selectedCityId) === String(city.city_id)) {
          option.selected = true;
        }

        citySelect.appendChild(option);
      });
    })
    .catch(function(error) {
      console.error('Error loading cities:', error);
    });
}

var countrySelect = document.getElementById('country-select');
var citySelect = document.getElementById('city-select');

if (countrySelect && citySelect) {
  countrySelect.addEventListener('change', function() {
    loadCities(this.value, '');
    clearFieldError('#country-select');
    clearFieldError('#city-select');
  });

  if (countrySelect.value) {
    loadCities(countrySelect.value, existingCityId);
  }
}

// ===================== TERMS =====================
\$('#show-terms-btn').on('click', function() {
  var termsContent = \$('.terms-content');

  termsContent.toggle();

  if (termsContent.is(':visible')) {
    \$(this).html('<i class="ri-book-read-line btn-icon"></i> Hide Terms');
  } else {
    \$(this).html('<i class="ri-book-open-line btn-icon"></i> Show Terms');
  }
});

function updateSubmitButtonState() {
  var termsChecked = \$('#terms-check').is(':checked');
  \$('#submit-btn').prop('disabled', !termsChecked);
}

\$('#terms-check').on('change', function() {
  updateSubmitButtonState();

  if (this.checked) {
    clearFieldError('#terms-check');
  }
});

updateSubmitButtonState();

// ===================== SUBMIT VALIDATION =====================
\$('#ao-profile-form').on('submit', function(e) {
  var manufacturer = \$('#manufacturer').val();
  var aircraftModel = \$('#model-dropdown').val();
  var serialNumber = \$('#serial_number').val().trim();
  var registrationNumber = \$('#registration_number').val().trim();
  var certificateType = \$('#certificate-type-dropdown').val();
  var termsChecked = \$('#terms-check').is(':checked');

  var ok = true;

  clearStepErrors('#step-3');

  if (!manufacturer) {
    ok = false;
    showFieldError('#manufacturer', 'Manufacturer is required.');
  }

  if (!aircraftModel) {
    ok = false;
    showFieldError('#model-dropdown', 'Model is required.');
  }

  if (!serialNumber) {
    ok = false;
    showFieldError('#serial_number', 'Serial number is required.');
  }

  if (!registrationNumber) {
    ok = false;
    showFieldError('#registration_number', 'Registration number is required.');
  }

  if (!certificateType) {
    ok = false;
    showFieldError('#certificate-type-dropdown', 'Certificate type is required.');
  }

  if (!termsChecked) {
    ok = false;
    showFieldError('#terms-check', 'You must agree to the terms and conditions.');
  }

  if (!ok) {
    e.preventDefault();
    return false;
  }
});

// ===================== CLEAR ERRORS ON CHANGE =====================
\$(document).on('input change', 'input, select, textarea', function() {
  var id = \$(this).attr('id');

  if (id) {
    clearFieldError('#' + id);
  }
});
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>

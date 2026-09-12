<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Modal;
use kartik\select2\Select2;

$this->title = 'MRO Signup';
$this->params['breadcrumbs'][] = $this->title;
$this->params['bodyClass'] = 'mro-layout';
$this->params['bodyDataTheme'] = 'light';

/*
 * ICÔNES LOCALES : la police Remix Icon est servie par l'application afin que
 * le formulaire reste complet même si un CDN est lent ou indisponible. Ce choix
 * réduit aussi une requête réseau externe sans modifier les icônes déjà utilisées.
 */
$this->registerCssFile(
    Yii::getAlias('@web/css/remixicon.css'),
    ['rel' => 'stylesheet', 'position' => \yii\web\View::POS_HEAD]
);

?>

<section class="mro-hero">
  <div class="mro-hero-inner">

    <div class="mro-card">

      <!-- HEADER / AVIATION CONTEXT -->
      <div class="mro-header">
        <div class="mro-header-left">
          <div class="mro-header-logo">
            <img src="<?= Yii::getAlias('@web/logo/can-logo-main.png') ?>" alt="Core Aviation Network">
          </div>

          <div class="mro-header-text">
            <span class="mro-kicker">MRO partner onboarding</span>
            <h1>MRO Signup</h1>
            <p>
              Join Core Aviation Network and publish your maintenance capabilities to operators worldwide.
            </p>
          </div>
        </div>

        <div class="mro-header-badge d-none d-md-flex">
          <i class="ri-shield-check-line"></i>
          <span>Secure validation</span>
        </div>
      </div>

      <!--
        STEPPER ACCESSIBLE : chaque étape expose son numéro logique au script.
        Le JavaScript ajoutera l'état « terminé » et aria-current sans changer
        l'ordre ni les règles de validation du parcours d'inscription.
      -->
      <div class="mro-steps" aria-label="MRO signup progress">
        <div class="mro-step mro-step-1 active" data-step="1" aria-current="step">
          <span class="mro-step-index" aria-hidden="true">
            <span class="mro-step-number">1</span>
            <i class="ri-check-line mro-step-check"></i>
          </span>
          <span class="mro-step-label">Account & secure access</span>
        </div>

        <div class="mro-step-line"></div>

        <div class="mro-step mro-step-2" data-step="2">
          <span class="mro-step-index" aria-hidden="true">
            <span class="mro-step-number">2</span>
            <i class="ri-check-line mro-step-check"></i>
          </span>
          <span class="mro-step-label">Contact & base details</span>
        </div>

        <div class="mro-step-line"></div>

        <div class="mro-step mro-step-3" data-step="3">
          <span class="mro-step-index" aria-hidden="true">
            <span class="mro-step-number">3</span>
            <i class="ri-check-line mro-step-check"></i>
          </span>
          <span class="mro-step-label">Certificates & coverage</span>
        </div>
      </div>



      <div class="mro-body-row mro-layout" data-theme="light">

        <!-- COLONNE FORMULAIRE -->
        <div class="mro-body-main">

          <?php $form = ActiveForm::begin([
              'options' => [
                  'class' => 'row g-3',
                  'enctype' => 'multipart/form-data'
              ]
          ]); ?>

          <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

          <!-- STEP 1 : ACCOUNT -->
          <div id="step-1">

            <div class="col-md-10">
              <?= $form->field($model, 'username')
                  ->textInput([
                      'class' => 'form-control form-control-lg',
                      'maxlength' => true,
                      'id' => 'username'
                  ])
                  ->label('Username <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'password')
                  ->passwordInput([
                      'class' => 'form-control form-control-lg',
                      'id' => 'password'
                  ])
                  ->label('Password <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'confirm_password')
                  ->passwordInput([
                      'class' => 'form-control form-control-lg',
                      'id' => 'confirm_password'
                  ])
                  ->label('Confirm password <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'email')
                  ->textInput([
                      'class' => 'form-control form-control-lg',
                      'id' => 'email'
                  ])
                  ->label('Email <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10 mro-nav-buttons">
              <button type="button" class="btn btn-primary" id="next-step-1">
                <i class="ri-arrow-right-circle-line btn-icon"></i>
                Next: Contact & base
                <i class="ri-arrow-right-line btn-icon btn-icon-right"></i>
              </button>
            </div>

          </div>

          <!-- STEP 2 : CONTACT & BASE -->
          <div id="step-2" style="display:none;">

            <div class="col-md-10">
              <?= $form->field($model, 'first_name')
                  ->textInput([
                      'class' => 'form-control form-control-lg',
                      'id' => 'first_name'
                  ])
                  ->label('First name <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'last_name')
                  ->textInput([
                      'class' => 'form-control form-control-lg',
                      'id' => 'last_name'
                  ])
                  ->label('Last name <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'contact_number')
                  ->textInput([
                      'class' => 'form-control form-control-lg',
                      'id' => 'contact_number'
                  ])
                  ->label('Direct contact number <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'profile_photo')
                  ->fileInput([
                      'class' => 'form-control form-control-lg custom-file-real',
                      'accept' => 'image/*',
                      'id' => 'profile_photo',
                      'style' => 'display:none;'
                  ])
                  ->label('Coordinator profile photo') ?>

              <div class="custom-file-ui" data-input="profile_photo">
                <button type="button" class="btn btn-outline-secondary custom-file-btn">
                  <i class="ri-upload-cloud-2-line btn-icon"></i>
                  Choose file
                </button>
                <span class="custom-file-name">No file chosen</span>
              </div>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'company_name')
                  ->textInput([
                      'class' => 'form-control form-control-lg',
                      'id' => 'company_name'
                  ])
                  ->label('MRO company name <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'company_photo')
                  ->fileInput([
                      'class' => 'form-control form-control-lg custom-file-real',
                      'accept' => 'image/*',
                      'id' => 'company_photo',
                      'style' => 'display:none;'
                  ])
                  ->label('Company / hangar photo') ?>

              <div class="custom-file-ui" data-input="company_photo">
                <button type="button" class="btn btn-outline-secondary custom-file-btn">
                  <i class="ri-upload-cloud-2-line btn-icon"></i>
                  Choose file
                </button>
                <span class="custom-file-name">No file chosen</span>
              </div>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'country_id')
                  ->dropDownList(
                      ArrayHelper::map($countries, 'country_id', 'country_name'),
                      [
                          'prompt' => 'Select country',
                          'id'     => 'country-select',
                          'class'  => 'form-select form-select-lg'
                      ]
                  )->label('Base country <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'city_id')
                  ->dropDownList(
                      [],
                      [
                          'prompt' => 'Select city',
                          'id'     => 'city-select',
                          'class'  => 'form-select form-select-lg'
                      ]
                  )->label('Base city <span class="text-danger">*</span>') ?>

              <div id="selected-city-value" class="selected-city-value"></div>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'address')
                  ->textInput([
                      'class' => 'form-control form-control-lg',
                      'id' => 'address'
                  ])
                  ->label('Hangar / facility address <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10">
              <?= $form->field($model, 'zip_code')
                  ->textInput([
                      'class' => 'form-control form-control-lg',
                      'id' => 'zip_code'
                  ])
                  ->label('ZIP / postal code <span class="text-danger">*</span>') ?>
            </div>

            <div class="col-md-10 mro-nav-buttons d-flex justify-content-between">
              <button type="button" class="btn btn-outline-secondary" id="prev-step-2">
                <i class="ri-arrow-left-line btn-icon"></i>
                Back
              </button>

              <button type="button" class="btn btn-primary" id="next-step-2">
                <i class="ri-file-shield-2-line btn-icon"></i>
                Next: Certificates
                <i class="ri-arrow-right-line btn-icon btn-icon-right"></i>
              </button>
            </div>

          </div>

          <!-- STEP 3 : CERTIFICATES & COVERAGE -->
          <div id="step-3" style="display:none;">

            <div class="col-md-10">
              <div class="mro-section-card">
                <div class="mro-section-title">
                  <span class="mro-section-icon"><i class="ri-shield-check-line"></i></span>
                  <div>
                    <h4>NAA approval</h4>
                    <p>Upload your official maintenance approval certificate and select the authority type.</p>
                  </div>
                </div>

                <?= $form->field($certificate, 'certificate')
                    ->fileInput([
                        'class' => 'form-control form-control-lg custom-file-real',
                        'accept' => '.pdf',
                        'id' => 'naa',
                        'style' => 'display:none;'
                    ])
                    ->label('NAA certificate <span class="text-danger">* (Max 5 MB, PDF)</span>') ?>

                <div class="custom-file-ui" data-input="naa">
                  <button type="button" class="btn btn-outline-secondary custom-file-btn">
                    <i class="ri-upload-cloud-2-line btn-icon"></i>
                    Choose file
                  </button>
                  <span class="custom-file-name">No file chosen</span>
                </div>

                <div class="mt-3">
                  <?= $form->field($certificate, 'type')
                      ->dropDownList(
                          ArrayHelper::map(
                              \app\models\CertificateTypes::find()
                                  ->orderBy(['type' => SORT_ASC])
                                  ->all(),
                              'type',
                              'type'
                          ),
                          [
                              'prompt' => 'Select authority type',
                              'id' => 'certificate-type-dropdown',
                              'class' => 'form-select form-select-lg'
                          ]
                      )->label('NAA authority <span class="text-danger">*</span>') ?>
                </div>

                <?= $form->field($certificate, 'certificate_type_id')->hiddenInput()->label(false) ?>
              </div>
            </div>

            <div class="col-md-10">
              <div class="mro-section-card">
                <div class="mro-section-title">
                  <span class="mro-section-icon"><i class="ri-file-list-3-line"></i></span>
                  <div>
                    <h4>Liability insurance</h4>
                    <p>Add your current insurance document for validation.</p>
                  </div>
                </div>

                <?= $form->field($model, 'insurance_document')
                    ->fileInput([
                        'class' => 'form-control form-control-lg custom-file-real',
                        'accept' => '.pdf',
                        'id' => 'insurance',
                        'style' => 'display:none;'
                    ])
                    ->label('Insurance document <span class="text-danger">* (Max 5 MB, PDF)</span>') ?>

                <div class="custom-file-ui" data-input="insurance">
                  <button type="button" class="btn btn-outline-secondary custom-file-btn">
                    <i class="ri-upload-cloud-2-line btn-icon"></i>
                    Choose file
                  </button>
                  <span class="custom-file-name">No file chosen</span>
                </div>
              </div>
            </div>

            <div class="col-md-10">
              <div class="mro-section-card">
                <div class="mro-section-title">
                  <span class="mro-section-icon"><i class="ri-plane-line"></i></span>
                  <div>
                    <h4>Aircraft capability</h4>
                    <p>Select the manufacturer, model, and upload the related capability certificate.</p>
                  </div>
                </div>

                <?= $form->field($mroaircraftcertificate, 'manufacturer')
                    ->dropDownList(
                        ArrayHelper::map(
                            \app\models\AircraftModel::find()
                                ->select('manufacturer')
                                ->distinct()
                                ->all(),
                            'manufacturer',
                            'manufacturer'
                        ),
                        [
                            'prompt' => 'Select manufacturer',
                            'id'     => 'manufacturer-dropdown',
                            'class'  => 'form-select form-select-lg'
                        ]
                    )->label('Manufacturer <span class="text-danger">*</span>') ?>

                <?= $form->field($mroaircraftcertificate, 'aircraft_model_id')
                    ->dropDownList(
                        [],
                        [
                            'prompt' => 'Select aircraft model',
                            'id'     => 'model-dropdown',
                            'class'  => 'form-select form-select-lg'
                        ]
                    )->label('Aircraft model <span class="text-danger">*</span>') ?>

                <?= $form->field($mroaircraftcertificate, 'certificate')
                    ->fileInput([
                        'class' => 'form-control form-control-lg custom-file-real',
                        'accept' => '.pdf',
                        'id' => 'mroaircraftcertificate-certificate',
                        'style' => 'display:none;'
                    ])
                    ->label('Aircraft capability certificate <span class="text-danger">* (Max 5 MB, PDF)</span>') ?>

                <div class="custom-file-ui" data-input="mroaircraftcertificate-certificate">
                  <button type="button" class="btn btn-outline-secondary custom-file-btn">
                    <i class="ri-upload-cloud-2-line btn-icon"></i>
                    Choose file
                  </button>
                  <span class="custom-file-name">No file chosen</span>
                </div>
              </div>
            </div>

            <div class="col-md-10">
              <div class="mro-section-card">
                <div class="mro-section-title">
                  <span class="mro-section-icon"><i class="ri-map-pin-2-line"></i></span>
                  <div>
                    <h4>Primary airport</h4>
                    <p>Choose the main airport or ICAO base for your maintenance operations.</p>
                  </div>
                </div>

                <?= $form->field($mroairport, 'airport_id')->widget(Select2::classname(), [
                    'options' => [
                        'placeholder' => 'Select airport',
                        'id' => 'mroairport-airport_id'
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                        'minimumInputLength' => 2,
                        'ajax' => [
                            'url' => Url::to(['site/airport-search']),
                            'dataType' => 'json',
                            'data' => new \yii\web\JsExpression('function(params){ return {q:params.term}; }'),
                            'processResults' => new \yii\web\JsExpression('function(data){ return {results:data.results}; }'),
                        ],
                    ],
                ])->label('Airport / ICAO base <span class="text-danger">*</span>') ?>
              </div>
            </div>

            <!-- Terms -->
            <div class="col-md-10 mt-2">
              <div class="mro-terms-card">
                <div class="form-check">
                  <?= Html::checkbox('terms', false, [
                      'class' => 'form-check-input',
                      'id' => 'terms-check'
                  ]) ?>

                  <?= Html::label(
                      'I agree to the Core Aviation Network partnership terms <span class="text-danger">*</span>',
                      'terms-check',
                      ['class' => 'form-check-label']
                  ) ?>

                  <?= Html::button('<i class="ri-book-open-line btn-icon"></i> Show terms', [
                      'class' => 'btn btn-sm btn-outline-primary ms-2',
                      'id' => 'show-terms-btn',
                      /*
                       * ACCESSIBILITÉ DES CONDITIONS : le bouton annonce le bloc
                       * qu'il contrôle et son état fermé dès le premier rendu.
                       */
                      'aria-controls' => 'mro-terms-content',
                      'aria-expanded' => 'false'
                  ]) ?>
                </div>

                <div id="mro-terms-content" class="terms-content mt-2" style="display:none; max-height:260px; overflow:auto; border-radius:8px; border:1px solid #e5e7eb; padding:10px 12px;">
                  <?= $terms ?>
                </div>
              </div>
            </div>

            <div class="col-md-10 mro-nav-buttons d-flex justify-content-between mt-3">
              <button type="button" class="btn btn-outline-secondary" id="prev-step-3">
                <i class="ri-arrow-left-line btn-icon"></i>
                Back
              </button>

              <?= Html::submitButton('<i class="ri-check-line btn-icon"></i> Submit MRO application', [
                  'class' => 'btn btn-primary',
                  'id'    => 'submit-btn',
                  'disabled' => true
              ]) ?>
            </div>

          </div>

          <?php ActiveForm::end(); ?>

        </div>

        <!--
          AIDE CONTEXTUELLE : un seul panneau est visible à la fois et son contenu
          correspond à l'étape affichée. Cela réduit la quantité de texte à lire
          tout en gardant les conseils utiles à proximité du formulaire.
        -->
        <div class="mro-body-side d-none d-lg-block">

          <div class="mro-side-card mro-side-step is-visible" data-side-step="1">
            <h5><i class="ri-shield-user-line"></i> Secure account</h5>
            <ul class="mb-0 ps-3">
              <li>Use a professional email address</li>
              <li>Create a unique account name</li>
              <li>Keep your access details confidential</li>
            </ul>
          </div>

          <div class="mro-side-card mro-side-step" data-side-step="2" hidden>
            <h5><i class="ri-building-4-line"></i> MRO identity</h5>
            <ul class="mb-0 ps-3">
              <li>Enter the official company name</li>
              <li>Use the operational facility address</li>
              <li>Check the direct contact number</li>
            </ul>
          </div>

          <div class="mro-side-card mro-side-step" data-side-step="3" hidden>
            <h5><i class="ri-file-shield-2-line"></i> Faster validation</h5>
            <ul class="mb-0 ps-3">
              <li>Upload readable, current PDF documents</li>
              <li>Select the exact aircraft capability</li>
              <li>Confirm the primary maintenance airport</li>
            </ul>
          </div>

          <div class="mro-side-card mro-side-trust">
            <h5><i class="ri-global-line"></i> CAN MRO Network</h5>
            <p class="mb-0">One profile for capabilities, requests and controlled documents.</p>
          </div>

        </div>

      </div>

    </div>

  </div>
</section>

<style>
/* ===================== PAGE WRAPPER ===================== */
.mro-hero {
  min-height: calc(100vh - 90px);
  padding: 34px 18px 46px;
  background:
    radial-gradient(circle at top left, rgba(37, 99, 235, 0.10), transparent 34%),
    radial-gradient(circle at top right, rgba(14, 165, 233, 0.10), transparent 30%),
    linear-gradient(135deg, #f8fafc 0%, #eef4fb 100%);
}

.mro-hero-inner {
  width: min(1180px, 100%);
  margin: 0 auto;
}

/* ===================== GLOBAL FORM DESIGN ===================== */
.mro-card {
  padding: 30px;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 22px 60px rgba(15, 23, 42, 0.13);
  border: 1px solid rgba(226, 232, 240, 0.95);
  backdrop-filter: blur(10px);
}

.mro-body-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 330px;
  gap: 30px;
  align-items: flex-start;
}

.mro-body-main {
  min-width: 0;
}

.mro-body-main .row {
  margin-left: 0;
  margin-right: 0;
}

.mro-body-main .col-md-10 {
  max-width: 760px;
}

/* ===================== HEADER ===================== */
.mro-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 26px;
  padding-bottom: 22px;
  border-bottom: 1px solid #e2e8f0;
}

.mro-header-left {
  display: flex;
  align-items: center;
  gap: 18px;
  min-width: 0;
}

.mro-header-logo {
  width: 74px;
  height: 74px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
  flex: 0 0 auto;
}

.mro-header-logo img {
  max-width: 58px;
  max-height: 58px;
  object-fit: contain;
}

.mro-kicker {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 5px;
  color: #2563eb;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.mro-header-text h1 {
  margin: 0 0 5px;
  font-size: clamp(28px, 4vw, 38px);
  font-weight: 850;
  letter-spacing: -0.04em;
  color: #0f172a;
}

.mro-header-text p {
  max-width: 660px;
  margin: 0;
  color: #64748b;
  font-size: 15px;
  line-height: 1.55;
}

.mro-header-badge {
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 999px;
  background: #eff6ff;
  color: #0b2d5b;
  border: 1px solid #bfdbfe;
  font-weight: 700;
  font-size: 13px;
  white-space: nowrap;
}

.mro-header-badge i {
  font-size: 18px;
}

/* ===================== STEPPER ===================== */
.mro-steps {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 30px;
}

.mro-step {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 9px;
  min-height: 44px;
  padding: 8px 14px 8px 8px;
  border-radius: 999px;
  transition: all 0.25s ease;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  color: #64748b;
  font-size: 14px;
  font-weight: 700;
  white-space: nowrap;
}

.mro-step.active {
  background: linear-gradient(135deg, #eff6ff, #ffffff);
  border-color: #2563eb;
  color: #0b2d5b;
  box-shadow: 0 8px 22px rgba(37, 99, 235, 0.14);
}

/*
 * ÉTAPES TERMINÉES : la coche verte distingue immédiatement ce qui est validé
 * de l'étape en cours. Les classes sont uniquement visuelles ; le passage à
 * l'étape suivante reste commandé par les validations JavaScript existantes.
 */
.mro-step.is-complete {
  color: #166534;
  background: #f0fdf4;
  border-color: #86efac;
}

.mro-step.is-complete .mro-step-index {
  color: #ffffff;
  background: #16a34a;
  border-color: #16a34a;
}

.mro-step-check {
  display: none;
  font-size: 17px;
}

.mro-step.is-complete .mro-step-number {
  display: none;
}

.mro-step.is-complete .mro-step-check {
  display: inline-flex;
}

.mro-step-index {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  color: #0b2d5b;
  font-weight: 800;
  border: 1px solid #dbeafe;
}

.mro-step.active .mro-step-index {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
}

.mro-step-line {
  height: 2px;
  flex: 1;
  min-width: 22px;
  background: linear-gradient(90deg, #dbeafe, #cbd5e1);
}

/* ===================== SECTION CARDS ===================== */
.mro-section-card,
.mro-terms-card {
  padding: 18px;
  border-radius: 18px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.055);
}

.mro-section-title {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 14px;
}

.mro-section-title h4 {
  margin: 0 0 4px;
  color: #0f172a;
  font-size: 18px;
  font-weight: 800;
  letter-spacing: -0.01em;
}

.mro-section-title p {
  margin: 0;
  color: #64748b;
  font-size: 13px;
  line-height: 1.45;
}

.mro-section-icon {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
  flex: 0 0 auto;
}

.mro-section-icon i {
  font-size: 22px;
}

/* ===================== INPUTS ===================== */
/*
 * PORTÉE DES CHAMPS : les règles visuelles sont limitées à la carte MRO. Elles
 * ne peuvent donc plus modifier par accident le menu public, une fenêtre modale
 * ou un autre composant Yii2 présent sur la même page.
 */
.mro-card .form-control,
.mro-card .form-select {
  min-height: 48px;
  border-radius: 12px !important;
  border: 1px solid #cbd5e1;
  background-color: #ffffff;
  color: #0f172a;
  transition: all 0.2s ease;
}

.mro-card .form-control:hover,
.mro-card .form-select:hover {
  border-color: #94a3b8;
}

.mro-card .form-control:focus,
.mro-card .form-select:focus {
  border-color: #2563eb !important;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12) !important;
}

.mro-card .form-control.is-invalid,
.mro-card .form-select.is-invalid {
  border-color: #ef4444 !important;
  background-color: #fff7f7;
}

.mro-card .form-control.is-invalid:focus,
.mro-card .form-select.is-invalid:focus {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12) !important;
}

/* ===================== LABELS ===================== */
.mro-card .form-label,
.mro-card .control-label {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 7px;
}

.mro-card .text-danger {
  color: #ef4444 !important;
}

/* ===================== ERROR MESSAGES ===================== */
.custom-error-message {
  color: #dc2626;
  font-size: 13px;
  margin-top: 6px;
  line-height: 1.4;
  font-weight: 600;
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

/*
 * ZONE DE DÉPÔT : l'utilisateur peut cliquer, utiliser le clavier ou déposer un
 * fichier. L'état bleu confirme la cible du dépôt et le bouton rouge permet de
 * corriger une sélection sans devoir rouvrir le sélecteur système.
 */
.custom-file-ui.is-dragover {
  border-color: #2563eb;
  background: #eff6ff;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.custom-file-ui.has-file {
  border-color: #86efac;
  background: #f0fdf4;
}

.custom-file-remove {
  flex: 0 0 auto;
  margin-left: auto;
  margin-right: 8px;
  padding: 7px 10px !important;
  color: #b91c1c !important;
  background: #fff1f2 !important;
  border: 1px solid #fecdd3 !important;
  box-shadow: none !important;
}

.custom-file-remove:hover {
  color: #ffffff !important;
  background: #dc2626 !important;
  border-color: #dc2626 !important;
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

/* ===================== SELECT2 ===================== */
.mro-card .select2-container .select2-selection--single {
  min-height: 48px !important;
  border-radius: 12px !important;
  border: 1px solid #cbd5e1 !important;
  display: flex !important;
  align-items: center !important;
}

.mro-card .select2-container--krajee-bs5 .select2-selection--single .select2-selection__rendered {
  padding-left: 12px !important;
  color: #0f172a !important;
}

.mro-card .select2-container.is-invalid .select2-selection,
.mro-card .select2-container--krajee-bs5.is-invalid .select2-selection {
  border-color: #ef4444 !important;
  background-color: #fff7f7 !important;
}

/* ===================== BUTTONS ===================== */
.mro-card .btn {
  border-radius: 12px;
  font-weight: 650;
  padding: 10px 18px;
  transition: all 0.2s ease;
}

.mro-card .btn-primary {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  border-color: #2563eb;
  box-shadow: 0 10px 22px rgba(37, 99, 235, 0.24);
}

.mro-card .btn-primary:hover {
  background: linear-gradient(135deg, #1d4ed8, #1e40af);
  border-color: #1d4ed8;
  transform: translateY(-1px);
}

.mro-card .btn-primary:disabled {
  opacity: 0.65;
  transform: none;
  box-shadow: none;
}

.mro-card .btn-outline-secondary {
  border-color: #cbd5e1;
  color: #334155;
  background: #ffffff;
}

.mro-card .btn-outline-secondary:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.mro-card .btn-outline-primary {
  border-color: #bfdbfe;
  color: #2563eb;
  background: #eff6ff;
}

.mro-card .btn-outline-primary:hover {
  background: #dbeafe;
  color: #1d4ed8;
}

.mro-card .btn .btn-icon,
.custom-file-btn .btn-icon {
  display: inline-flex;
  align-items: center;
  margin-right: 6px;
  font-size: 17px;
  line-height: 1;
  vertical-align: -2px;
}

.mro-card .btn .btn-icon-right {
  margin-left: 6px;
  margin-right: 0;
}

.mro-nav-buttons {
  margin-top: 6px;
}

/* ===================== SIDE CARDS ===================== */
.mro-body-side {
  position: sticky;
  top: 22px;
}

.mro-side-card {
  padding: 18px;
  margin-bottom: 16px;
  border-radius: 18px;
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  border: 1px solid #e2e8f0;
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.065);
}

.mro-side-card h5 {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  color: #0f172a;
  font-size: 16px;
  font-weight: 700;
}

.mro-side-card h5 i {
  color: #2563eb;
  font-size: 19px;
}

.mro-side-card li {
  color: #475569;
  margin-bottom: 8px;
  line-height: 1.45;
}

/*
 * TRANSITION DE L'AIDE : le panneau pertinent apparaît avec un déplacement très
 * court, suffisamment discret pour guider sans détourner l'attention des champs.
 * L'attribut hidden reste la source de vérité pour l'accessibilité.
 */
.mro-side-step.is-visible {
  animation: mro-side-in 180ms ease-out;
}

.mro-side-trust {
  color: #475569;
  background: linear-gradient(145deg, #eff6ff, #ffffff);
  border-color: #bfdbfe;
}

.mro-side-trust p {
  font-size: 13px;
  line-height: 1.55;
}

@keyframes mro-side-in {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}

/*
 * CONFORT VISUEL : lorsque le système demande moins d'animations, la transition
 * de l'aide et les déplacements au survol sont neutralisés sans changer la mise
 * en page ni la visibilité des informations.
 */
@media (prefers-reduced-motion: reduce) {
  .mro-card *,
  .mro-card *::before,
  .mro-card *::after {
    scroll-behavior: auto !important;
    transition-duration: 0.01ms !important;
    animation-duration: 0.01ms !important;
  }
}

/* ===================== TERMS BOX ===================== */
.mro-terms-card .form-check {
  margin-bottom: 0;
}

.terms-content {
  background: #f8fafc;
  border-color: #e2e8f0 !important;
  color: #334155;
}

/* ===================== RESPONSIVE ===================== */
@media (max-width: 991px) {
  .mro-hero {
    padding: 20px 12px 32px;
  }

  .mro-card {
    padding: 20px;
    border-radius: 20px;
  }

  .mro-body-row {
    grid-template-columns: 1fr;
  }

  .mro-header {
    align-items: flex-start;
  }

  .mro-steps {
    flex-direction: column;
    align-items: stretch;
  }

  .mro-step {
    width: 100%;
  }

  .mro-step-line {
    width: 2px;
    height: 14px;
    min-width: 2px;
    margin-left: 21px;
  }

  .col-md-10,
  .mro-body-main .col-md-10 {
    width: 100%;
    max-width: 100%;
  }
}

@media (max-width: 575px) {
  .mro-header-left {
    align-items: flex-start;
    gap: 12px;
  }

  .mro-header-logo {
    width: 56px;
    height: 56px;
    border-radius: 16px;
  }

  .mro-header-logo img {
    max-width: 44px;
    max-height: 44px;
  }

  .mro-nav-buttons {
    flex-direction: column;
    gap: 10px;
  }

  .mro-nav-buttons .btn {
    width: 100%;
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
}
</style>

<?php
// URLs for AJAX requests
// Json::htmlEncode makes the URLs safe to inject inside JavaScript.
$certTypeUrl = Json::htmlEncode(Url::to(['/certificate-types/get-certificate-type-id']));
$modelUrl    = Json::htmlEncode(Url::to(['/site/get-models-by-manufacturer']));
$citiesUrl   = Json::htmlEncode(Url::to(['/site/get-cities']));

$js = <<<JS
// ===================== VALIDATION HELPERS =====================
function getErrorContainer(field) {
  var id = field.attr('id');

  if (id && $('.custom-file-ui[data-input="' + id + '"]').length) {
    return $('.custom-file-ui[data-input="' + id + '"]').closest('.col-md-10');
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
  field.attr('aria-invalid', 'true');

  if (field.hasClass('select2-hidden-accessible')) {
    field.next('.select2-container').addClass('is-invalid');
  }

  var container = getErrorContainer(field);
  var id = field.attr('id');

  container.find('.custom-error-message').remove();

  if (id && \$('.custom-file-ui[data-input="' + id + '"]').length) {
    \$('.custom-file-ui[data-input="' + id + '"]').after(
      '<div class="custom-error-message" role="alert" aria-live="polite">' + message + '</div>'
    );
  } else {
    container.append(
      '<div class="custom-error-message" role="alert" aria-live="polite">' + message + '</div>'
    );
  }
}

function clearFieldError(selector) {
  var field = \$(selector);

  if (!field.length) {
    return;
  }

  field.removeClass('is-invalid');
  field.removeAttr('aria-invalid');

  if (field.hasClass('select2-hidden-accessible')) {
    field.next('.select2-container').removeClass('is-invalid');
  }

  var container = getErrorContainer(field);
  container.find('.custom-error-message').remove();

  var id = field.attr('id');

  if (id) {
    \$('.custom-file-ui[data-input="' + id + '"]').removeClass('is-invalid');
  }
}

function clearStepErrors(stepSelector) {
  \$(stepSelector).find('.is-invalid').removeClass('is-invalid');
  \$(stepSelector).find('.select2-container').removeClass('is-invalid');
  \$(stepSelector).find('.custom-file-ui').removeClass('is-invalid');
  \$(stepSelector).find('.custom-error-message').remove();
}

/*
 * VALIDATION DES FICHIERS : la même règle de 5 Mio est appliquée au sélecteur
 * classique et au glisser-déposer. Le retour booléen permet au dépôt de refuser
 * immédiatement un fichier trop lourd sans modifier les règles du modèle Yii2.
 */
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
      \$('.custom-file-ui[data-input="' + inputId + '"]').removeClass('has-file');
    }
  });
}

validateFileSize('profile_photo');
validateFileSize('company_photo');
validateFileSize('naa');
validateFileSize('insurance');
validateFileSize('mroaircraftcertificate-certificate');

/*
 * PIÈCES JOINTES ACCESSIBLES : chaque zone devient utilisable à la souris, au
 * clavier et par glisser-déposer. Le bouton Remove est généré ici pour conserver
 * un HTML identique sur les cinq champs et remet réellement l'input à zéro.
 */
\$('.custom-file-ui').each(function() {
  var zone = \$(this);
  var inputId = zone.data('input');

  zone.attr({
    role: 'button',
    tabindex: '0',
    'aria-controls': inputId,
    'aria-label': 'Choose or drop a file'
  });

  zone.append(
    '<button type="button" class="btn btn-sm custom-file-remove" hidden>'
      + '<i class="ri-delete-bin-6-line" aria-hidden="true"></i>'
      + '<span class="visually-hidden">Remove selected file</span>'
    + '</button>'
  );
});

\$('.custom-file-ui').on('click', function(e) {
  e.preventDefault();

  if (\$(e.target).closest('.custom-file-remove').length) {
    return;
  }

  var inputId = \$(this).data('input');
  \$('#' + inputId).trigger('click');
});

\$('.custom-file-ui').on('keydown', function(e) {
  /*
   * Le bouton de suppression possède son propre comportement clavier. Ignorer
   * ici ses événements évite qu'Entrée supprime le fichier puis ouvre aussitôt
   * la boîte de sélection du champ parent.
   */
  if (\$(e.target).closest('.custom-file-remove').length) {
    return;
  }

  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault();
    \$('#' + \$(this).data('input')).trigger('click');
  }
});

\$('.custom-file-ui').on('dragenter dragover', function(e) {
  e.preventDefault();
  e.stopPropagation();
  \$(this).addClass('is-dragover');
});

\$('.custom-file-ui').on('dragleave drop', function(e) {
  e.preventDefault();
  e.stopPropagation();
  \$(this).removeClass('is-dragover');
});

\$('.custom-file-ui').on('drop', function(e) {
  var inputId = \$(this).data('input');
  var input = document.getElementById(inputId);
  var files = e.originalEvent.dataTransfer.files;

  if (!input || !files || files.length === 0) {
    return;
  }

  var transfer = new DataTransfer();
  transfer.items.add(files[0]);
  input.files = transfer.files;
  \$(input).trigger('change');
});

\$(document).on('click', '.custom-file-remove', function(e) {
  e.preventDefault();
  e.stopPropagation();

  var zone = \$(this).closest('.custom-file-ui');
  var inputId = zone.data('input');
  var input = document.getElementById(inputId);

  if (input) {
    input.value = '';
  }

  zone.removeClass('has-file is-invalid');
  zone.find('.custom-file-name').text('No file chosen');
  \$(this).prop('hidden', true);
  clearFieldError('#' + inputId);
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
    .toggleClass('has-file', this.files && this.files.length > 0)
    .find('.custom-file-name')
    .text(fileName);

  \$('.custom-file-ui[data-input="' + inputId + '"] .custom-file-remove')
    .prop('hidden', !(this.files && this.files.length > 0));

  clearFieldError('#' + inputId);
});

/*
 * PROGRESSION ET AIDE CONTEXTUELLE : les étapes antérieures reçoivent la coche
 * verte, seule l'étape courante expose aria-current et le panneau latéral suit
 * le contexte affiché. Les étapes futures restent visuellement neutres.
 */
function setActiveStep(step) {
  document.querySelectorAll('.mro-step[data-step]').forEach(function(item) {
    var itemStep = Number(item.getAttribute('data-step'));
    var isActive = itemStep === step;

    item.classList.toggle('active', isActive);
    item.classList.toggle('is-complete', itemStep < step);

    if (isActive) {
      item.setAttribute('aria-current', 'step');
    } else {
      item.removeAttribute('aria-current');
    }
  });

  document.querySelectorAll('[data-side-step]').forEach(function(panel) {
    var isVisible = Number(panel.getAttribute('data-side-step')) === step;
    panel.hidden = !isVisible;
    panel.classList.toggle('is-visible', isVisible);
  });
}

// ===================== STEP 1 -> STEP 2 =====================
\$('#next-step-1').on('click', function() {
  var username        = \$('#username').val().trim();
  var password        = \$('#password').val().trim();
  var confirmPassword = \$('#confirm_password').val().trim();
  var email           = \$('#email').val().trim();

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
  \$('#first_name').trigger('focus');
});

// ===================== STEP 2 BACK =====================
\$('#prev-step-2').on('click', function() {
  \$('#step-2').hide();
  \$('#step-1').show();
  setActiveStep(1);
  \$('#username').trigger('focus');
});

// ===================== STEP 2 -> STEP 3 =====================
\$('#next-step-2').on('click', function() {
  var firstname   = \$('#first_name').val().trim();
  var lastname    = \$('#last_name').val().trim();
  var contact     = \$('#contact_number').val().trim();
  var companyname = \$('#company_name').val().trim();
  var countryId   = \$('#country-select').val();
  var cityId      = \$('#city-select').val();
  var address     = \$('#address').val().trim();
  var zip         = \$('#zip_code').val().trim();

  var ok = true;

  clearStepErrors('#step-2');

  if (!firstname) {
    ok = false;
    showFieldError('#first_name', 'First name is required.');
  }

  if (!lastname) {
    ok = false;
    showFieldError('#last_name', 'Last name is required.');
  }

  if (!contact) {
    ok = false;
    showFieldError('#contact_number', 'Direct contact number is required.');
  }

  if (!companyname) {
    ok = false;
    showFieldError('#company_name', 'MRO company name is required.');
  }

  if (!countryId) {
    ok = false;
    showFieldError('#country-select', 'Base country is required.');
  }

  if (!cityId) {
    ok = false;
    showFieldError('#city-select', 'Base city is required.');
  }

  if (!address) {
    ok = false;
    showFieldError('#address', 'Hangar / facility address is required.');
  }

  if (!zip) {
    ok = false;
    showFieldError('#zip_code', 'ZIP / postal code is required.');
  }

  if (!ok) {
    return;
  }

  \$('#step-2').hide();
  \$('#step-3').show();
  setActiveStep(3);
  \$('.custom-file-ui[data-input="naa"]').trigger('focus');
});

// ===================== STEP 3 BACK =====================
\$('#prev-step-3').on('click', function() {
  \$('#step-3').hide();
  \$('#step-2').show();
  setActiveStep(2);
  \$('#first_name').trigger('focus');
});

/*
 * CONDITIONS : le libellé du bouton indique clairement si le texte va être
 * ouvert ou refermé. aria-expanded synchronise le même état pour les lecteurs
 * d'écran, sans modifier l'obligation d'accepter les conditions avant envoi.
 */
\$('#show-terms-btn').on('click', function() {
  var content = \$('.terms-content');
  var willOpen = !content.is(':visible');

  content.toggle(willOpen);
  \$(this)
    .attr('aria-expanded', willOpen ? 'true' : 'false')
    .html(
      '<i class="ri-book-open-line btn-icon"></i> '
      + (willOpen ? 'Hide terms' : 'Show terms')
    );
});

\$('#terms-check').on('change', function() {
  \$('#submit-btn').prop('disabled', !this.checked);

  if (this.checked) {
    clearFieldError('#terms-check');
  }
});

// ===================== SUBMIT VALIDATION STEP 3 =====================
\$('#submit-btn').on('click', function(e) {
  var naa                 = \$('#naa').val();
  var insurance           = \$('#insurance').val();
  var certificateType     = \$('#certificate-type-dropdown').val();
  var manufacturer        = \$('#manufacturer-dropdown').val();
  var model               = \$('#model-dropdown').val();
  var aircraftCertificate = \$('#mroaircraftcertificate-certificate').val();
  var airport             = \$('#mroairport-airport_id').val();
  var terms               = \$('#terms-check').is(':checked');

  var ok = true;

  clearStepErrors('#step-3');

  if (!naa) {
    ok = false;
    showFieldError('#naa', 'NAA certificate is required.');
    \$('.custom-file-ui[data-input="naa"]').addClass('is-invalid');
  }

  if (!insurance) {
    ok = false;
    showFieldError('#insurance', 'Insurance document is required.');
    \$('.custom-file-ui[data-input="insurance"]').addClass('is-invalid');
  }

  if (!certificateType) {
    ok = false;
    showFieldError('#certificate-type-dropdown', 'NAA authority is required.');
  }

  if (!manufacturer) {
    ok = false;
    showFieldError('#manufacturer-dropdown', 'Manufacturer is required.');
  }

  if (!model) {
    ok = false;
    showFieldError('#model-dropdown', 'Aircraft model is required.');
  }

  if (!aircraftCertificate) {
    ok = false;
    showFieldError('#mroaircraftcertificate-certificate', 'Aircraft capability certificate is required.');
    \$('.custom-file-ui[data-input="mroaircraftcertificate-certificate"]').addClass('is-invalid');
  }

  if (!airport) {
    ok = false;
    showFieldError('#mroairport-airport_id', 'Airport / ICAO base is required.');
  }

  if (!terms) {
    ok = false;
    showFieldError('#terms-check', 'You must agree to the partnership terms.');
  }

  if (!ok) {
    e.preventDefault();
    return false;
  }
});

// ===================== CLEAR ERROR WHEN USER CHANGES FIELD =====================
\$(document).on('input change', 'input, select, textarea', function() {
  var id = \$(this).attr('id');

  if (id) {
    clearFieldError('#' + id);
  }
});

\$(document).on('select2:select select2:clear change', '#mroairport-airport_id', function() {
  clearFieldError('#mroairport-airport_id');
});

// ===================== CERTIFICATE TYPE -> ID =====================
\$('#certificate-type-dropdown').on('change', function() {
  var selectedType = \$(this).val();

  if (!selectedType) {
    return;
  }

  \$.ajax({
    url: {$certTypeUrl},
    type: 'GET',
    dataType: 'json',
    data: {
      type: selectedType
    },
    success: function(data) {
      if (data && data.certificate_type_id) {
        \$('#certificates-certificate_type_id').val(data.certificate_type_id);
      }
    },
    error: function(xhr, status, error) {
      console.error('Certificate type AJAX status:', status);
      console.error('Certificate type AJAX error:', error);
      console.error('Certificate type server response:', xhr.responseText);
      alert('Error fetching certificate type ID.');
    }
  });
});

// ===================== MANUFACTURER -> MODELS =====================
\$('#manufacturer-dropdown').on('change', function() {
  var manufacturer = \$(this).val();
  var modelDropdown = \$('#model-dropdown');

  // Reset the aircraft model dropdown each time the manufacturer changes.
  modelDropdown.empty();
  modelDropdown.append('<option value="">Select aircraft model</option>');

  if (!manufacturer) {
    return;
  }

  \$.ajax({
    url: {$modelUrl},
    type: 'GET',
    dataType: 'json',
    data: {
      manufacturer: manufacturer
    },
    success: function(data) {
      modelDropdown.empty();
      modelDropdown.append('<option value="">Select aircraft model</option>');

      if (!data || data.length === 0) {
        modelDropdown.append('<option value="">No models found</option>');
        return;
      }

      \$.each(data, function(index, item) {
        modelDropdown.append(
          \$('<option>', {
            value: item.id,
            text: item.name
          })
        );
      });
    },
    error: function(xhr, status, error) {
      console.error('Models AJAX status:', status);
      console.error('Models AJAX error:', error);
      console.error('Models server response:', xhr.responseText);

      modelDropdown.empty();
      modelDropdown.append('<option value="">Error loading models</option>');

      alert('Error fetching models. Please check console for details.');
    }
  });
});

// ===================== COUNTRY -> CITIES =====================
(function() {
  var countrySelect = document.getElementById('country-select');
  var citySelect    = document.getElementById('city-select');

  if (!countrySelect || !citySelect) {
    return;
  }

  countrySelect.addEventListener('change', function() {
    var countryId = this.value;

    citySelect.innerHTML = '<option value="">Select city</option>';

    if (!countryId) {
      return;
    }

    fetch({$citiesUrl} + '?countryId=' + encodeURIComponent(countryId), {
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(function(response) {
        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }

        return response.json();
      })
      .then(function(cities) {
        cities.forEach(function(city) {
          var opt = document.createElement('option');
          opt.value = city.city_id;
          opt.textContent = city.city_name;
          citySelect.appendChild(opt);
        });
      })
      .catch(function(err) {
        console.error('Error loading cities:', err);
      });
  });
})();
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>

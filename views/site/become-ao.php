<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\bootstrap5\ActiveForm;

/**
 * INSCRIPTION OPÉRATEUR (AO) — écran d'onboarding autonome (2026-11-22)
 *
 * Même présentation que l'inscription MRO (become-mro.php) : panneau de marque à
 * gauche, formulaire sur une colonne à droite, 4 étapes :
 * 1. Compte · 2. Opérateur & contact · 3. Avion · 4. Vérification & envoi.
 * Le panneau « Your aircraft » résume l'avion saisi : c'est sur son type, son
 * certificat et le lieu de maintenance que les MRO seront sélectionnés.
 * Champs et noms POST inchangés (AoProfile[...], Aircrafts[...], terms).
 *
 * @var \yii\web\View $this
 * @var \app\models\AoProfile $model
 * @var \app\models\Aircrafts $airplaneModel
 * @var \app\models\AircraftModel[] $manufacturers
 * @var \app\models\AircraftModel[] $models
 * @var \app\models\Countries[] $countries
 * @var string $terms
 * @var string[] $formErrors   Erreurs serveur (formulaire réaffiché avec les saisies)
 * @var int|null $errorStep    Étape à rouvrir après une erreur serveur
 * @var array $keptFiles       Fichiers conservés côté serveur : slot => [name, size]
 */

$this->title = 'Aircraft Operator Signup';
$this->params['breadcrumbs'][] = $this->title;

$formErrors = $formErrors ?? [];
$errorStep = $errorStep ?? null;
$keptFiles = $keptFiles ?? [];

$this->registerCssFile(
    Yii::getAlias('@web/css/remixicon.css'),
    ['rel' => 'stylesheet', 'position' => \yii\web\View::POS_HEAD]
);
$this->registerCssFile(
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
    ['position' => \yii\web\View::POS_HEAD]
);

// Villes du pays déjà choisi (réaffichage après erreur).
$cityOptions = $model->country_id
    ? ArrayHelper::map(
        \app\models\Cities::find()->where(['country_id' => (int) $model->country_id])->orderBy(['city_name' => SORT_ASC])->all(),
        'city_id',
        'city_name'
    )
    : [];

// Modèles d'avion : filtrés côté navigateur selon le constructeur choisi.
$aircraftModelsData = [];
foreach ($models as $aircraftModelItem) {
    $aircraftModelsData[] = [
        'aircraft_model_id' => $aircraftModelItem->aircraft_model_id,
        'manufacturer' => $aircraftModelItem->manufacturer,
        'model' => $aircraftModelItem->model,
    ];
}
$modelOptions = [];
if ($airplaneModel->manufacturer) {
    foreach ($aircraftModelsData as $item) {
        if ($item['manufacturer'] === $airplaneModel->manufacturer) {
            $modelOptions[$item['model']] = $item['model'];
        }
    }
}

$termsAccepted = !empty(Yii::$app->request->post('terms'));

$keptFile = static function (string $slot, string $inputId) use ($keptFiles): string {
    $meta = $keptFiles[$slot] ?? null;
    $html = Html::hiddenInput('kept_files[' . $slot . ']', $meta ? '1' : '0', ['id' => 'kept-' . $inputId]);
    if ($meta) {
        $size = (int) $meta['size'];
        $label = $meta['name'] . ' · ' . ($size >= 1048576 ? round($size / 1048576, 1) . ' MB' : max(1, (int) round($size / 1024)) . ' KB') . ' · kept from your previous submission';
        $html .= Html::tag('span', '', ['class' => 'd-none', 'data-kept-label' => $inputId, 'data-label' => $label]);
    }
    return $html;
};

$steps = [
    1 => ['Account', 'Email and password'],
    2 => ['Operator & contact', 'Company and contact person'],
    3 => ['Aircraft', 'Type, registration, certificate'],
    4 => ['Review & submit', 'Check and send'],
];
?>

<div class="ob">

  <!-- PANNEAU DE GAUCHE : marque, étapes, fiche de l'avion (desktop) -->
  <aside class="ob-aside" aria-label="Application progress">
    <div class="ob-aside-inner">
      <a class="ob-brand" href="<?= Html::encode(Yii::$app->request->baseUrl . '/') ?>">
        <img src="<?= Yii::getAlias('@web/logo/can-logo-main.png') ?>" alt="" width="52" height="35">
        <span>
          <span class="ob-brand-name">Core Aviation Network</span>
          <span class="ob-brand-sub">Aircraft operator application</span>
        </span>
      </a>

      <nav class="mro-stepper ob-steps" aria-label="Application steps">
        <?php foreach ($steps as $number => [$label, $hint]): ?>
          <button type="button" class="mro-stepper-item<?= $number === 1 ? ' active' : '' ?>" data-step="<?= $number ?>" <?= $number === 1 ? 'aria-current="step"' : 'disabled' ?>>
            <span class="mro-stepper-dot" aria-hidden="true">
              <span class="mro-stepper-number"><?= $number ?></span>
              <i class="ri-check-line mro-stepper-check"></i>
            </span>
            <span class="mro-stepper-text">
              <span class="mro-stepper-label"><?= Html::encode($label) ?></span>
              <span class="mro-stepper-hint"><?= Html::encode($hint) ?></span>
            </span>
          </button>
        <?php endforeach; ?>
      </nav>

      <!-- Fiche de l'avion : base de la sélection des MRO -->
      <section class="ob-elig" aria-labelledby="ob-elig-title">
        <div class="ob-elig-head">
          <h2 id="ob-elig-title">Your aircraft</h2>
          <span class="ob-elig-count"><span id="mro-ready-count">0</span> of 4</span>
        </div>
        <div class="mro-progress" aria-hidden="true"><span id="mro-progress-bar"></span></div>
        <ul class="mro-criteria">
          <li data-criterion="type"><span class="mro-criterion-icon" aria-hidden="true"><i class="ri-check-line"></i></span><span><strong>Aircraft type</strong><small data-detail>Step 3</small></span></li>
          <li data-criterion="registration"><span class="mro-criterion-icon" aria-hidden="true"><i class="ri-check-line"></i></span><span><strong>Registration</strong><small data-detail>Step 3</small></span></li>
          <li data-criterion="serial"><span class="mro-criterion-icon" aria-hidden="true"><i class="ri-check-line"></i></span><span><strong>Serial number</strong><small data-detail>Step 3</small></span></li>
          <li data-criterion="certificate"><span class="mro-criterion-icon" aria-hidden="true"><i class="ri-check-line"></i></span><span><strong>Certificate type</strong><small data-detail>Step 3</small></span></li>
        </ul>
        <p class="ob-elig-note">MROs approved for this aircraft receive your maintenance requests. You can add more aircraft later from your fleet.</p>
      </section>

      <div class="ob-aside-foot">
        Already have an account? <?= Html::a('Sign in', ['/site/login']) ?>
      </div>
    </div>
  </aside>

  <!-- ZONE PRINCIPALE -->
  <main class="ob-main mro-signup">
    <header class="ob-topbar">
      <a class="ob-topbar-brand" href="<?= Html::encode(Yii::$app->request->baseUrl . '/') ?>">
        <img src="<?= Yii::getAlias('@web/logo/can-logo-main.png') ?>" alt="Core Aviation Network" width="40" height="27">
      </a>
      <div class="ob-topbar-progress">
        <span class="ob-topbar-step">Step <span id="ob-step-now">1</span> of 4</span>
        <span class="ob-topbar-bar" aria-hidden="true"><span id="ob-step-bar"></span></span>
      </div>
    </header>

    <div class="ob-content">

      <?php if ($formErrors !== []): ?>
        <!-- ERREURS SERVEUR : saisies et photo valide conservées -->
        <div class="mro-alert" role="alert" id="mro-alert">
          <i class="ri-error-warning-line" aria-hidden="true"></i>
          <div class="mro-alert-body">
            <strong>We couldn't submit your application yet</strong>
            <ul>
              <?php foreach (array_unique($formErrors) as $error): ?>
                <li><?= Html::encode($error) ?></li>
              <?php endforeach; ?>
            </ul>
            <span class="mro-alert-note">Your entries are kept — you only need to fix the items above.</span>
          </div>
          <button type="button" class="mro-alert-close" id="mro-alert-close" aria-label="Close this message">
            <i class="ri-close-line" aria-hidden="true"></i>
          </button>
        </div>
      <?php endif; ?>

      <?php $form = ActiveForm::begin([
          'id' => 'ao-profile-form',
          'options' => ['class' => 'mro-form', 'enctype' => 'multipart/form-data', 'novalidate' => true],
          'fieldConfig' => ['options' => ['class' => 'mro-field']],
      ]); ?>

      <!-- ÉTAPE 1 : COMPTE -->
      <div id="step-1" class="mro-step-panel">
        <header class="ob-head">
          <span class="ob-head-icon" aria-hidden="true"><i class="ri-user-add-line"></i></span>
          <div class="ob-head-text">
            <p class="ob-eyebrow">Step 1 of 4</p>
            <h1 class="ob-title">Create your operator account</h1>
            <p class="ob-lead">You will use it to raise maintenance requests, compare MRO quotations and follow the work on your aircraft.</p>
          </div>
        </header>

        <section class="ob-section">
          <?= $form->field($model, 'email')
              ->textInput(['class' => 'form-control', 'id' => 'email', 'type' => 'email', 'maxlength' => true, 'autocomplete' => 'email', 'placeholder' => 'ops@your-company.com'])
              ->label('<i class="ri-mail-line ob-label-icon" aria-hidden="true"></i>Work email <span class="ob-req" aria-hidden="true">*</span>')
              ->hint('Quotations, purchase order updates and AOG alerts are sent here.') ?>

          <?= $form->field($model, 'username')
              ->textInput(['class' => 'form-control', 'maxlength' => true, 'id' => 'username', 'autocomplete' => 'username'])
              ->label('<i class="ri-user-line ob-label-icon" aria-hidden="true"></i>Username <span class="ob-req" aria-hidden="true">*</span>')
              ->hint('Used to sign in. At least 3 characters.') ?>

          <div class="mro-grid-2">
            <?= $form->field($model, 'password', [
                'template' => "{label}\n<div class=\"mro-password\">{input}<button type=\"button\" class=\"mro-password-toggle\" data-target=\"password\" aria-label=\"Show password\" aria-pressed=\"false\"><i class=\"ri-eye-line\" aria-hidden=\"true\"></i></button></div>\n{hint}\n{error}",
            ])->passwordInput(['class' => 'form-control', 'id' => 'password', 'maxlength' => true, 'autocomplete' => 'new-password'])
              ->label('<i class="ri-lock-password-line ob-label-icon" aria-hidden="true"></i>Password <span class="ob-req" aria-hidden="true">*</span>') ?>

            <?= $form->field($model, 'confirm_password', [
                'template' => "{label}\n<div class=\"mro-password\">{input}<button type=\"button\" class=\"mro-password-toggle\" data-target=\"confirm_password\" aria-label=\"Show password\" aria-pressed=\"false\"><i class=\"ri-eye-line\" aria-hidden=\"true\"></i></button></div>\n{hint}\n{error}",
            ])->passwordInput(['class' => 'form-control', 'id' => 'confirm_password', 'maxlength' => true, 'autocomplete' => 'new-password'])
              ->label('<i class="ri-shield-keyhole-line ob-label-icon" aria-hidden="true"></i>Confirm password <span class="ob-req" aria-hidden="true">*</span>') ?>
          </div>

          <ul class="mro-rules" aria-live="polite">
            <li data-rule="length"><i class="ri-checkbox-blank-circle-line" aria-hidden="true"></i> 6+ characters</li>
            <li data-rule="mix"><i class="ri-checkbox-blank-circle-line" aria-hidden="true"></i> Letters and numbers</li>
            <li data-rule="match"><i class="ri-checkbox-blank-circle-line" aria-hidden="true"></i> Passwords match</li>
          </ul>
        </section>

        <div class="mro-actions">
          <span class="mro-actions-note"><i class="ri-lock-line" aria-hidden="true"></i> Your details are only shared with the MROs you work with.</span>
          <button type="button" class="ob-btn ob-btn-primary mro-btn-next" id="next-step-1">
            Continue <i class="ri-arrow-right-line" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <!-- ÉTAPE 2 : OPÉRATEUR & CONTACT -->
      <div id="step-2" class="mro-step-panel" hidden>
        <header class="ob-head">
          <span class="ob-head-icon" aria-hidden="true"><i class="ri-building-2-line"></i></span>
          <div class="ob-head-text">
            <p class="ob-eyebrow">Step 2 of 4</p>
            <h1 class="ob-title">Operator and contact</h1>
            <p class="ob-lead">MROs see this on your requests and purchase orders.</p>
          </div>
        </header>

        <section class="ob-section">
          <h2 class="ob-section-title">Operator</h2>

          <?= $form->field($model, 'company_name')
              ->textInput(['class' => 'form-control', 'id' => 'company_name', 'maxlength' => true, 'autocomplete' => 'organization', 'placeholder' => 'Airline, operator or owner company'])
              ->label('<i class="ri-building-2-line ob-label-icon" aria-hidden="true"></i>Company name <span class="ob-optional">Optional</span>')
              ->hint('Leave empty if you operate the aircraft as a private owner.') ?>

          <div class="mro-grid-2">
            <?= $form->field($model, 'country_id')
                ->dropDownList(
                    ArrayHelper::map($countries, 'country_id', 'country_name'),
                    ['prompt' => 'Select country', 'id' => 'country-select', 'class' => 'form-select', 'autocomplete' => 'off']
                )->label('<i class="ri-earth-line ob-label-icon" aria-hidden="true"></i>Country <span class="ob-req" aria-hidden="true">*</span>') ?>

            <?= $form->field($model, 'city_id')
                ->dropDownList($cityOptions, ['prompt' => $cityOptions ? 'Select city' : 'Select a country first', 'id' => 'city-select', 'class' => 'form-select'])
                ->label('<i class="ri-map-pin-line ob-label-icon" aria-hidden="true"></i>City <span class="ob-req" aria-hidden="true">*</span>') ?>
          </div>

          <div class="mro-grid-2 ob-grid-address">
            <?= $form->field($model, 'address')
                ->textInput(['class' => 'form-control', 'id' => 'address', 'maxlength' => true, 'autocomplete' => 'street-address'])
                ->label('<i class="ri-road-map-line ob-label-icon" aria-hidden="true"></i>Address <span class="ob-req" aria-hidden="true">*</span>') ?>

            <?= $form->field($model, 'zip_code')
                ->textInput(['class' => 'form-control', 'id' => 'zip_code', 'maxlength' => true, 'autocomplete' => 'postal-code'])
                ->label('<i class="ri-hashtag ob-label-icon" aria-hidden="true"></i>Postal code <span class="ob-req" aria-hidden="true">*</span>') ?>
          </div>
        </section>

        <section class="ob-section">
          <h2 class="ob-section-title">Contact person</h2>
          <p class="ob-section-desc">The person MROs can reach about quotations and AOG work.</p>

          <div class="mro-grid-2">
            <?= $form->field($model, 'first_name')
                ->textInput(['class' => 'form-control', 'id' => 'first_name', 'maxlength' => true, 'autocomplete' => 'given-name'])
                ->label('<i class="ri-user-3-line ob-label-icon" aria-hidden="true"></i>First name <span class="ob-req" aria-hidden="true">*</span>') ?>

            <?= $form->field($model, 'last_name')
                ->textInput(['class' => 'form-control', 'id' => 'last_name', 'maxlength' => true, 'autocomplete' => 'family-name'])
                ->label('<i class="ri-user-3-line ob-label-icon" aria-hidden="true"></i>Last name <span class="ob-req" aria-hidden="true">*</span>') ?>
          </div>

          <div class="mro-grid-2">
            <?= $form->field($model, 'contact_number')
                ->textInput(['class' => 'form-control', 'id' => 'contact_number', 'type' => 'tel', 'maxlength' => true, 'autocomplete' => 'tel', 'placeholder' => '+216 …'])
                ->label('<i class="ri-phone-line ob-label-icon" aria-hidden="true"></i>Direct phone <span class="ob-req" aria-hidden="true">*</span>')
                ->hint('International format, reachable for AOG coordination.') ?>

            <div class="mro-file-field">
              <span class="ob-label"><i class="ri-account-circle-line ob-label-icon" aria-hidden="true"></i>Profile photo <span class="ob-optional">Optional</span></span>
              <?= $form->field($model, 'profile_photo', ['template' => "{input}\n{error}"])
                  ->fileInput(['class' => 'custom-file-real', 'accept' => 'image/*', 'id' => 'profile_photo', 'hidden' => true]) ?>
              <?= $keptFile('profile_photo', 'profile_photo') ?>
              <div class="custom-file-ui custom-file-ui-compact" data-input="profile_photo">
                <span class="custom-file-icon" aria-hidden="true"><i class="ri-user-3-line"></i></span>
                <span class="custom-file-text">
                  <strong>Upload photo</strong>
                  <span class="custom-file-name" data-empty="JPG or PNG, up to 5 MB">JPG or PNG, up to 5 MB</span>
                </span>
              </div>
            </div>
          </div>
        </section>

        <div class="mro-actions">
          <button type="button" class="ob-btn ob-btn-ghost" id="prev-step-2"><i class="ri-arrow-left-line" aria-hidden="true"></i> Back</button>
          <button type="button" class="ob-btn ob-btn-primary mro-btn-next" id="next-step-2">
            Continue <i class="ri-arrow-right-line" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <!-- ÉTAPE 3 : AVION -->
      <div id="step-3" class="mro-step-panel" hidden>
        <header class="ob-head">
          <span class="ob-head-icon" aria-hidden="true"><i class="ri-plane-line"></i></span>
          <div class="ob-head-text">
            <p class="ob-eyebrow">Step 3 of 4</p>
            <h1 class="ob-title">Your aircraft</h1>
            <p class="ob-lead">Add the aircraft you want maintained. MROs approved for this aircraft type receive your requests. You can add more aircraft later from your fleet.</p>
          </div>
        </header>

        <section class="ob-section">
          <h2 class="ob-section-title">Aircraft type</h2>

          <div class="mro-grid-2">
            <?= $form->field($airplaneModel, 'manufacturer')
                ->dropDownList(
                    ArrayHelper::map($manufacturers, 'manufacturer', 'manufacturer'),
                    ['prompt' => 'Select manufacturer', 'class' => 'form-select', 'id' => 'manufacturer']
                )->label('<i class="ri-building-4-line ob-label-icon" aria-hidden="true"></i>Manufacturer <span class="ob-req" aria-hidden="true">*</span>') ?>

            <?= $form->field($airplaneModel, 'model')
                ->dropDownList($modelOptions, ['prompt' => $modelOptions ? 'Select model' : 'Select a manufacturer first', 'class' => 'form-select', 'id' => 'model-dropdown'])
                ->label('<i class="ri-plane-line ob-label-icon" aria-hidden="true"></i>Model <span class="ob-req" aria-hidden="true">*</span>') ?>
          </div>

          <?= $form->field($airplaneModel, 'aircraft_model_id', ['options' => ['class' => 'd-none']])->hiddenInput(['id' => 'aircraft-model-id'])->label(false) ?>
        </section>

        <section class="ob-section">
          <h2 class="ob-section-title">Identification and certification</h2>

          <div class="mro-grid-2">
            <?= $form->field($airplaneModel, 'registration_number')
                ->textInput(['class' => 'form-control', 'id' => 'registration_number', 'maxlength' => true, 'placeholder' => 'e.g. TS-IOK', 'autocapitalize' => 'characters'])
                ->label('<i class="ri-hashtag ob-label-icon" aria-hidden="true"></i>Registration <span class="ob-req" aria-hidden="true">*</span>') ?>

            <?= $form->field($airplaneModel, 'serial_number')
                ->textInput(['class' => 'form-control', 'id' => 'serial_number', 'maxlength' => true, 'placeholder' => 'Manufacturer serial number (MSN)'])
                ->label('<i class="ri-barcode-line ob-label-icon" aria-hidden="true"></i>Serial number <span class="ob-req" aria-hidden="true">*</span>') ?>
          </div>

          <?= $form->field($airplaneModel, 'certificate_type_id')
              ->dropDownList(
                  ArrayHelper::map(\app\models\CertificateTypes::find()->orderBy(['type' => SORT_ASC])->all(), 'certificate_type_id', 'type'),
                  ['prompt' => 'Select certificate type', 'id' => 'certificate-type-dropdown', 'class' => 'form-select']
              )->label('<i class="ri-government-line ob-label-icon" aria-hidden="true"></i>Certificate type <span class="ob-req" aria-hidden="true">*</span>') ?>
        </section>

        <div class="mro-actions">
          <button type="button" class="ob-btn ob-btn-ghost" id="prev-step-3"><i class="ri-arrow-left-line" aria-hidden="true"></i> Back</button>
          <button type="button" class="ob-btn ob-btn-primary mro-btn-next" id="next-step-3">
            Continue <i class="ri-arrow-right-line" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <!-- ÉTAPE 4 : VÉRIFICATION & ENVOI -->
      <div id="step-4" class="mro-step-panel" hidden>
        <header class="ob-head">
          <span class="ob-head-icon" aria-hidden="true"><i class="ri-file-check-line"></i></span>
          <div class="ob-head-text">
            <p class="ob-eyebrow">Step 4 of 4</p>
            <h1 class="ob-title">Review and submit</h1>
            <p class="ob-lead">Check your application before sending it. You can change any section.</p>
          </div>
        </header>

        <section class="ob-section">
          <h2 class="ob-section-title">Your application</h2>
          <dl class="mro-review" id="mro-review">
            <?php foreach ([1 => 'Account', 2 => 'Operator & contact', 3 => 'Aircraft'] as $reviewStep => $reviewLabel): ?>
              <div class="mro-review-row">
                <dt class="mro-review-label"><?= Html::encode($reviewLabel) ?></dt>
                <dd class="mro-review-value" data-review="<?= $reviewStep ?>">—</dd>
                <dd class="mro-review-action"><button type="button" class="mro-review-edit" data-goto="<?= $reviewStep ?>">Edit</button></dd>
              </div>
            <?php endforeach; ?>
          </dl>

          <div class="mro-terms">
            <div class="form-check">
              <?= Html::checkbox('terms', $termsAccepted, ['class' => 'form-check-input', 'id' => 'terms-check']) ?>
              <?= Html::label(
                  'I agree to the Core Aviation Network terms and conditions.',
                  'terms-check',
                  ['class' => 'form-check-label']
              ) ?>
            </div>
            <?= Html::button('Read the terms', [
                'class' => 'ob-link',
                'id' => 'show-terms-btn',
                'aria-controls' => 'mro-terms-content',
                'aria-expanded' => 'false',
            ]) ?>
            <div id="mro-terms-content" class="terms-content" hidden>
              <?= $terms ?>
            </div>
          </div>
        </section>

        <div class="mro-actions">
          <button type="button" class="ob-btn ob-btn-ghost" id="prev-step-4"><i class="ri-arrow-left-line" aria-hidden="true"></i> Back</button>
          <?= Html::submitButton('<i class="ri-send-plane-line" aria-hidden="true"></i> Submit application', [
              'class' => 'ob-btn ob-btn-primary mro-btn-next',
              'id' => 'submit-btn',
              'disabled' => !$termsAccepted,
          ]) ?>
        </div>
      </div>

      <?php ActiveForm::end(); ?>

      <p class="ob-fineprint">After you submit: verify your email, then raise your first maintenance request — MROs approved for your aircraft reply with quotations.</p>
    </div>
  </main>
</div>

<style>
/*
 * INSCRIPTION AO — mêmes styles que become-mro.php (écran d'onboarding autonome) (layout « auth », sans header ni footer).
 * Panneau de marque à gauche, formulaire sur une colonne à droite.
 * Une seule couleur d'accent, bordures nettes, pas d'effets décoratifs.
 */
.ob {
  --ob-navy: #0B1A2E;
  --ob-navy-2: #12243D;
  --ob-text: #101828;
  --ob-text-2: #475467;
  --ob-text-3: #667085;
  --ob-border: #D0D5DD;
  --ob-divider: #EAECF0;
  --ob-surface: #F9FAFB;
  --ob-primary: #1D4ED8;
  --ob-primary-hover: #1E40AF;
  --ob-ring: 0 0 0 4px rgba(29, 78, 216, .14);
  --ob-success: #067647;
  --ob-success-bg: #ECFDF3;
  --ob-success-border: #ABEFC6;
  --ob-warning: #B54708;
  --ob-warning-bg: #FFFAEB;
  --ob-warning-border: #FEDF89;
  --ob-danger: #B42318;
  --ob-danger-bg: #FEF3F2;
  --ob-danger-border: #FECDCA;
  display: flex;
  min-height: 100vh;
  background: #FFFFFF;
  color: var(--ob-text);
  font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  font-size: 15px;
  line-height: 1.5;
  -webkit-font-smoothing: antialiased;
}
.ob *, .ob *::before, .ob *::after { box-sizing: border-box; }
.ob a { color: var(--ob-primary); }
.ob a:hover { color: var(--ob-primary-hover); }
.ob :focus-visible { outline: 2px solid var(--ob-primary); outline-offset: 2px; }

/* ---------- Panneau de gauche ---------- */
.ob-aside {
  flex: 0 0 360px;
  background: var(--ob-navy);
  color: #CBD5E1;
}
.ob-aside-inner {
  position: sticky;
  top: 0;
  display: flex;
  flex-direction: column;
  gap: 36px;
  min-height: 100vh;
  padding: 36px 32px 28px;
}
.ob-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.ob-brand img { width: 52px; height: auto; }
.ob-brand-name { display: block; color: #FFFFFF; font-size: 15px; font-weight: 600; letter-spacing: -.01em; }
.ob-brand-sub { display: block; color: #8DA2BF; font-size: 13px; }

.ob-steps { display: flex; flex-direction: column; }
.ob .mro-stepper-item {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 14px;
  width: 100%;
  padding: 0 0 26px;
  border: 0;
  background: none;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
}
.ob .mro-stepper-item:last-child { padding-bottom: 0; }
.ob .mro-stepper-item:not(:last-child)::after {
  content: "";
  position: absolute;
  left: 13px;
  top: 32px;
  bottom: 4px;
  width: 2px;
  border-radius: 2px;
  background: rgba(255, 255, 255, .12);
}
.ob .mro-stepper-item.is-complete:not(:last-child)::after { background: #3B82F6; }
.ob .mro-stepper-item:disabled { cursor: default; }
.ob .mro-stepper-dot {
  flex: 0 0 28px;
  height: 28px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  border: 2px solid rgba(255, 255, 255, .22);
  color: #8DA2BF;
  font-size: 13px;
  font-weight: 600;
}
.ob .mro-stepper-item.active .mro-stepper-dot { border-color: #FFFFFF; color: #FFFFFF; box-shadow: 0 0 0 4px rgba(255, 255, 255, .1); }
.ob .mro-stepper-item.is-complete .mro-stepper-dot { border-color: #3B82F6; background: #3B82F6; color: #FFFFFF; }
.ob .mro-stepper-check { display: none; font-size: 15px; }
.ob .mro-stepper-item.is-complete .mro-stepper-check { display: inline; }
.ob .mro-stepper-item.is-complete .mro-stepper-number { display: none; }
.ob .mro-stepper-text { display: flex; flex-direction: column; padding-top: 3px; }
.ob .mro-stepper-label { color: #8DA2BF; font-size: 14px; font-weight: 600; }
.ob .mro-stepper-hint { color: #6B7F9A; font-size: 13px; }
.ob .mro-stepper-item.active .mro-stepper-label,
.ob .mro-stepper-item.is-complete .mro-stepper-label { color: #FFFFFF; }
.ob .mro-stepper-item.active .mro-stepper-hint { color: #A9B8CC; }
.ob .mro-stepper-item:not(:disabled):not(.active):hover .mro-stepper-label { text-decoration: underline; text-underline-offset: 3px; }

.ob-elig {
  padding: 20px;
  border: 1px solid rgba(255, 255, 255, .1);
  border-radius: 12px;
  background: var(--ob-navy-2);
}
.ob-elig-head { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 10px; }
.ob-elig-head h2 { margin: 0; color: #FFFFFF; font-family: inherit; font-size: 14px; font-weight: 600; }
.ob-elig-count { color: #A9B8CC; font-size: 13px; font-variant-numeric: tabular-nums; }
.ob .mro-progress { height: 4px; border-radius: 4px; background: rgba(255, 255, 255, .1); overflow: hidden; margin-bottom: 6px; }
.ob .mro-progress span { display: block; width: 0; height: 100%; border-radius: 4px; background: #34D399; transition: width .25s ease; }
.ob .mro-criteria { margin: 0; padding: 0; list-style: none; }
.ob .mro-criteria li { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-top: 1px solid rgba(255, 255, 255, .06); }
.ob .mro-criteria li:first-child { border-top: 0; }
.ob .mro-criteria strong { display: block; color: #E2E8F0; font-size: 13px; font-weight: 500; }
.ob .mro-criteria small { display: block; color: #8DA2BF; font-size: 12px; overflow-wrap: anywhere; }
.ob .mro-criterion-icon {
  flex: 0 0 20px;
  height: 20px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  border: 1.5px dashed rgba(255, 255, 255, .25);
  color: transparent;
  font-size: 13px;
}
.ob .mro-criteria li.is-ready .mro-criterion-icon { border: 0; background: #34D399; color: var(--ob-navy); }
.ob-elig-note { margin: 10px 0 0; color: #8DA2BF; font-size: 12px; }
.ob-aside-foot { margin-top: auto; color: #8DA2BF; font-size: 13px; }
.ob-aside-foot a { color: #FFFFFF; font-weight: 600; text-decoration: none; }
.ob-aside-foot a:hover { color: #FFFFFF; text-decoration: underline; }

/* ---------- Zone principale ---------- */
.ob-main { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; }
.ob-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 20px 40px;
}
.ob-topbar-brand { display: none; }
.ob-topbar-brand img { width: 40px; height: auto; }
.ob-topbar-progress { display: flex; align-items: center; gap: 12px; }
.ob-topbar-step { color: var(--ob-text-3); font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums; }
/* Sur ordinateur, le compteur est déjà dans l'en-tête de l'étape : la barre du haut n'affiche que la progression. */
@media (min-width: 992px) {
  .ob-topbar-step { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
}
.ob-topbar-bar { width: 120px; height: 4px; border-radius: 4px; background: var(--ob-divider); overflow: hidden; }
.ob-topbar-bar span { display: block; width: 25%; height: 100%; background: var(--ob-primary); transition: width .25s ease; }
.ob-help { display: inline-flex; align-items: center; gap: 6px; min-height: 40px; padding: 0 12px; border: 1px solid var(--ob-border); border-radius: 8px; color: var(--ob-text-2) !important; font-size: 14px; font-weight: 500; text-decoration: none; }
.ob-help:hover { background: var(--ob-surface); }

.ob-content { width: 100%; max-width: 680px; margin: 0 auto; padding: 24px 40px 56px; }

/* En-tête d'étape : pictogramme, nom de l'étape, titre et texte d'introduction. */
.ob-head {
  display: flex;
  align-items: flex-start;
  gap: 18px;
  margin-bottom: 28px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--ob-divider);
}
.ob-head-icon {
  flex: 0 0 48px;
  height: 48px;
  border: 1px solid #D1E0FF;
  border-radius: 12px;
  background: #EFF4FF;
  color: var(--ob-primary);
  display: grid;
  place-items: center;
  font-size: 22px;
}
.ob-head-text { min-width: 0; }
.ob-eyebrow { margin: 2px 0 6px; color: var(--ob-primary); font-size: 12px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
.ob-title { margin: 0 0 8px; color: var(--ob-text); font-family: inherit; font-size: 28px; font-weight: 700; line-height: 1.2; letter-spacing: -.025em; }
.ob-lead { margin: 0; color: var(--ob-text-2); font-size: 15px; line-height: 1.6; max-width: 540px; }

.ob-section { padding: 24px 0; border-top: 1px solid var(--ob-divider); }
.ob-section:first-of-type { border-top: 0; padding-top: 0; }
.ob-section-title { margin: 0 0 4px; color: var(--ob-text); font-family: inherit; font-size: 16px; font-weight: 600; }
.ob-section-desc { margin: 0 0 18px; color: var(--ob-text-3); font-size: 14px; }
.ob-section-title + .mro-field, .ob-section-title + .mro-grid-2 { margin-top: 16px; }

.ob .mro-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 16px; }
.ob .ob-grid-address { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); }
.ob .mro-field { margin-bottom: 18px; }
.ob .mro-field > label, .ob .form-label, .ob-label {
  display: block;
  margin-bottom: 6px;
  color: #344054;
  font-size: 14px;
  font-weight: 500;
}
.ob-req { color: var(--ob-danger); }
/* Icône devant chaque libellé : repère visuel discret, ignoré par les lecteurs d'écran. */
.ob-label-icon { margin-right: 6px; color: var(--ob-text-3); font-size: 16px; font-weight: 400; vertical-align: -2px; }
.ob-optional { margin-left: 4px; color: var(--ob-text-3); font-size: 13px; font-weight: 400; }
.ob .form-control, .ob .form-select {
  height: 44px;
  padding: 10px 14px;
  border: 1px solid var(--ob-border);
  border-radius: 8px;
  background-color: #FFFFFF;
  color: var(--ob-text);
  font-size: 15px;
  box-shadow: 0 1px 2px rgba(16, 24, 40, .05);
  transition: border-color .15s ease, box-shadow .15s ease;
}
.ob .form-select { padding-right: 38px; }
.ob .form-control::placeholder { color: var(--ob-text-3); }
.ob .form-control:focus, .ob .form-select:focus { border-color: var(--ob-primary); box-shadow: var(--ob-ring); outline: none; }
.ob .form-text, .ob .hint-block { margin-top: 6px; color: var(--ob-text-3); font-size: 13px; }
.ob .invalid-feedback, .ob .custom-error-message { display: block; margin-top: 6px; color: var(--ob-danger); font-size: 13px; }
.ob .form-control.is-invalid, .ob .form-select.is-invalid { border-color: #FDA29B; background-image: none; padding-right: 14px; }
.ob .form-control.is-invalid:focus, .ob .form-select.is-invalid:focus { box-shadow: 0 0 0 4px rgba(240, 68, 56, .14); }
.ob .form-select.is-invalid { padding-right: 38px; }
/* Pas de coche verte de validation Bootstrap : champs uniformes, seules les erreurs sont signalées. */
.ob .form-control.is-valid { border-color: var(--ob-border); background-image: none; padding-right: 14px; }
.ob .form-select.is-valid { border-color: var(--ob-border); --bs-form-select-bg-icon: none; padding-right: 38px; }
.ob .form-control.is-valid:focus, .ob .form-select.is-valid:focus { border-color: var(--ob-primary); box-shadow: var(--ob-ring); }

/* Select2 aligné sur les champs */
.ob .select2-container--krajee-bs5 .select2-selection--single,
.ob .select2-container .select2-selection--single {
  height: 44px;
  border: 1px solid var(--ob-border);
  border-radius: 8px;
  box-shadow: 0 1px 2px rgba(16, 24, 40, .05);
  display: flex;
  align-items: center;
}
.ob .select2-container--focus .select2-selection--single,
.ob .select2-container--open .select2-selection--single { border-color: var(--ob-primary); box-shadow: var(--ob-ring); }
.ob .select2-container.is-invalid .select2-selection--single { border-color: #FDA29B; }
.ob .select2-container .select2-selection--single .select2-selection__rendered { flex: 1; min-width: 0; padding-left: 14px; padding-right: 56px; color: var(--ob-text); font-size: 15px; line-height: 42px; }
.ob .select2-container .select2-selection--single .select2-selection__placeholder { color: var(--ob-text-3); }
.ob .select2-container .select2-selection--single { position: relative; }
.ob .select2-container .select2-selection__clear { position: absolute; top: 50%; right: 34px; margin: 0; transform: translateY(-50%); color: var(--ob-text-3); font-size: 18px; line-height: 1; }

.ob .mro-password { display: flex; position: relative; }
.ob .mro-password .form-control { padding-right: 48px; }
.ob .mro-password-toggle {
  position: absolute;
  right: 4px;
  top: 4px;
  width: 36px;
  height: 36px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: var(--ob-text-3);
  font-size: 18px;
}
.ob .mro-password-toggle:hover { background: var(--ob-surface); color: var(--ob-text); }

.ob .mro-rules { display: flex; flex-wrap: wrap; gap: 6px 20px; margin: -4px 0 0; padding: 0; list-style: none; color: var(--ob-text-3); font-size: 13px; }
.ob .mro-rules li { display: inline-flex; align-items: center; gap: 6px; }
.ob .mro-rules li.is-ok { color: var(--ob-success); }

/* Documents */
.ob .mro-doc-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
.ob .mro-chip {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 2px 10px;
  border: 1px solid var(--ob-warning-border);
  border-radius: 999px;
  background: var(--ob-warning-bg);
  color: var(--ob-warning);
  font-size: 12px;
  font-weight: 500;
}
.ob .mro-chip::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.ob .mro-chip.is-ready { border-color: var(--ob-success-border); background: var(--ob-success-bg); color: var(--ob-success); }

.ob .mro-file-field { margin-bottom: 18px; }
.ob .mro-file-field .mro-field { margin-bottom: 0; }
.ob .custom-file-ui {
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 72px;
  padding: 14px 16px;
  border: 1px dashed var(--ob-border);
  border-radius: 10px;
  background: #FFFFFF;
  cursor: pointer;
  transition: border-color .15s ease, background-color .15s ease;
}
.ob .custom-file-ui:hover, .ob .custom-file-ui.is-dragover { border-color: var(--ob-primary); background: #F5F8FF; }
.ob .custom-file-ui:focus-visible { border-style: solid; }
.ob .custom-file-ui.has-file { border: 1px solid var(--ob-divider); background: var(--ob-surface); }
.ob .custom-file-ui.is-invalid { border-color: #FDA29B; background: var(--ob-danger-bg); }
.ob .custom-file-icon {
  flex: 0 0 40px;
  height: 40px;
  border: 1px solid var(--ob-divider);
  border-radius: 8px;
  background: #FFFFFF;
  display: grid;
  place-items: center;
  color: var(--ob-text-2);
  font-size: 20px;
}
.ob .custom-file-ui.has-file .custom-file-icon { color: var(--ob-success); }
.ob .custom-file-text { flex: 1; min-width: 0; display: flex; flex-direction: column; color: var(--ob-text-3); font-size: 13px; }
.ob .custom-file-text strong { color: var(--ob-text); font-size: 14px; font-weight: 500; }
.ob .custom-file-name { overflow-wrap: anywhere; }
.ob .custom-file-ui.has-file .custom-file-name { color: var(--ob-text-2); }
.ob .custom-file-btn { flex: 0 0 auto; color: var(--ob-primary); font-size: 14px; font-weight: 600; }
.ob .custom-file-ui.has-file .custom-file-btn { display: none; }
.ob .custom-file-remove {
  flex: 0 0 auto;
  min-height: 36px;
  padding: 0 10px;
  border: 1px solid var(--ob-border);
  border-radius: 8px;
  background: #FFFFFF;
  color: var(--ob-text-2);
  font-size: 13px;
  font-weight: 500;
}
.ob .custom-file-remove:hover { color: var(--ob-danger); border-color: #FDA29B; }
.ob .custom-file-ui-compact { min-height: 44px; padding: 7px 12px; }
.ob .custom-file-ui-compact .custom-file-icon { flex-basis: 30px; height: 30px; font-size: 16px; }

/* Étape 4 */
.ob .mro-match { padding: 12px 14px; border: 1px solid var(--ob-warning-border); border-radius: 8px; background: var(--ob-warning-bg); color: var(--ob-warning); font-size: 14px; }
.ob .mro-match.is-ready { border-color: var(--ob-success-border); background: var(--ob-success-bg); color: var(--ob-success); }
.ob .mro-review { margin: 12px 0 0; border: 1px solid var(--ob-divider); border-radius: 10px; }
.ob .mro-review-row { display: grid; grid-template-columns: 170px minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 14px 16px; border-top: 1px solid var(--ob-divider); }
.ob .mro-review-row:first-child { border-top: 0; }
.ob .mro-review-label { color: var(--ob-text-3); font-size: 13px; font-weight: 500; }
.ob .mro-review-value { margin: 0; color: var(--ob-text); font-size: 14px; overflow-wrap: anywhere; }
.ob .mro-review-action { margin: 0; }
.ob .mro-review-edit { min-height: 36px; padding: 0 8px; border: 0; background: none; color: var(--ob-primary); font-size: 14px; font-weight: 600; }
.ob .mro-review-edit:hover { text-decoration: underline; }

.ob .mro-terms { margin-top: 20px; }
.ob .mro-terms .form-check { display: flex; gap: 10px; margin: 0; padding: 0; }
.ob .mro-terms .form-check-input { position: static; flex: 0 0 18px; width: 18px; height: 18px; margin: 2px 0 0; float: none; border-color: var(--ob-border); border-radius: 5px; }
.ob .mro-terms .form-check-input:checked { background-color: var(--ob-primary); border-color: var(--ob-primary); }
.ob .mro-terms .form-check-label { color: var(--ob-text-2); font-size: 14px; }
.ob-link { margin: 6px 0 0 28px; padding: 0; border: 0; background: none; color: var(--ob-primary); font-size: 14px; font-weight: 600; }
.ob-link:hover { text-decoration: underline; }
.ob .terms-content { margin-top: 12px; max-height: 260px; overflow: auto; padding: 14px 16px; border: 1px solid var(--ob-divider); border-radius: 8px; background: var(--ob-surface); color: var(--ob-text-2); font-size: 13px; }

/* Actions */
.ob .mro-actions {
  position: sticky;
  bottom: 0;
  z-index: 5;
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 8px;
  padding: 16px 0;
  border-top: 1px solid var(--ob-divider);
  background: rgba(255, 255, 255, .96);
}
.ob .mro-actions-note { display: inline-flex; align-items: center; gap: 6px; color: var(--ob-text-3); font-size: 13px; }
.ob-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 44px;
  padding: 0 18px;
  border: 1px solid transparent;
  border-radius: 8px;
  font: inherit;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color .15s ease, border-color .15s ease;
}
.ob-btn-primary { margin-left: auto; background: var(--ob-primary); color: #FFFFFF; box-shadow: 0 1px 2px rgba(16, 24, 40, .05); }
.ob-btn-primary:hover { background: var(--ob-primary-hover); }
.ob-btn-primary:disabled { background: #98A2B3; cursor: not-allowed; }
.ob-btn-ghost { border-color: var(--ob-border); background: #FFFFFF; color: #344054; }
.ob-btn-ghost:hover { background: var(--ob-surface); }

.ob-fineprint { margin: 20px 0 0; color: var(--ob-text-3); font-size: 13px; }

/* Erreurs serveur */
.ob .mro-alert {
  display: flex;
  gap: 12px;
  margin-bottom: 28px;
  padding: 16px;
  border: 1px solid var(--ob-danger-border);
  border-radius: 10px;
  background: var(--ob-danger-bg);
  color: var(--ob-danger);
  font-size: 14px;
}
.ob .mro-alert > i { font-size: 20px; line-height: 1.2; }
.ob .mro-alert strong { color: #912018; font-weight: 600; }
.ob .mro-alert ul { margin: 6px 0; padding-left: 18px; }
.ob .mro-alert-note { color: var(--ob-text-2); font-size: 13px; }
.ob .mro-alert-body { flex: 1; min-width: 0; }
.ob .mro-alert-close {
  flex: 0 0 36px;
  height: 36px;
  margin: -8px -8px 0 0;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: var(--ob-danger);
  font-size: 20px;
  display: grid;
  place-items: center;
  cursor: pointer;
}
.ob .mro-alert-close:hover { background: rgba(180, 35, 24, .08); }

.ob .mro-step-panel[hidden] { display: none; }

/* ---------- Responsive ---------- */
@media (max-width: 1100px) {
  .ob-aside { flex-basis: 300px; }
  .ob-aside-inner { padding: 28px 24px; }
}
@media (max-width: 991.98px) {
  .ob { display: block; }
  .ob-aside { display: none; }
  .ob-topbar { position: sticky; top: 0; z-index: 10; padding: 12px 20px; border-bottom: 1px solid var(--ob-divider); background: #FFFFFF; }
  .ob-topbar-brand { display: block; }
  .ob-help span { display: none; }
  .ob-content { padding: 24px 20px 40px; }
}
@media (max-width: 575.98px) {
  .ob .mro-grid-2, .ob .ob-grid-address { grid-template-columns: minmax(0, 1fr); }
  .ob-title { font-size: 23px; }
  .ob-head { gap: 14px; }
  .ob-head-icon { flex-basis: 40px; height: 40px; font-size: 19px; border-radius: 10px; }
  .ob-topbar-bar { width: 72px; }
  .ob .mro-review-row { grid-template-columns: minmax(0, 1fr) auto; }
  .ob .mro-review-value { grid-column: 1 / 2; }
  .ob .mro-actions-note { display: none; }
  .ob .custom-file-ui { flex-wrap: wrap; }
}
@media (prefers-reduced-motion: reduce) {
  .ob *, .ob *::before, .ob *::after { transition: none !important; }
}
</style>

<?php
// Données et URLs injectées dans le JavaScript (encodées sans risque).
$aircraftModelsJson = Json::htmlEncode($aircraftModelsData);
$citiesUrl = Json::htmlEncode(Url::to(['/site/get-cities']));
$startStep = Json::htmlEncode($errorStep !== null ? (int) $errorStep : 1);

$js = <<<JS
// ===================== AIDES DE VALIDATION =====================
function getErrorContainer(field) {
  var id = field.attr('id');

  if (id && \$('.custom-file-ui[data-input="' + id + '"]').length) {
    return \$('.custom-file-ui[data-input="' + id + '"]').closest('.mro-file-field');
  }

  var container = field.closest('.mro-field');
  return container.length ? container : field.parent();
}

function showFieldError(selector, message) {
  var field = \$(selector);

  if (!field.length) {
    return;
  }

  field.addClass('is-invalid').attr('aria-invalid', 'true');

  if (field.hasClass('select2-hidden-accessible')) {
    field.next('.select2-container').addClass('is-invalid');
  }

  var container = getErrorContainer(field);
  var id = field.attr('id');
  var error = \$('<div class="custom-error-message" role="alert"></div>').text(message);

  container.find('.custom-error-message').remove();

  if (id && \$('.custom-file-ui[data-input="' + id + '"]').length) {
    \$('.custom-file-ui[data-input="' + id + '"]').addClass('is-invalid').after(error);
  } else {
    container.append(error);
  }
}

function clearFieldError(selector) {
  var field = \$(selector);

  if (!field.length) {
    return;
  }

  field.removeClass('is-invalid').removeAttr('aria-invalid');

  if (field.hasClass('select2-hidden-accessible')) {
    field.next('.select2-container').removeClass('is-invalid');
  }

  getErrorContainer(field).find('.custom-error-message').remove();

  var id = field.attr('id');
  if (id) {
    \$('.custom-file-ui[data-input="' + id + '"]').removeClass('is-invalid');
  }
}

function clearStepErrors(stepSelector) {
  \$(stepSelector).find('.is-invalid').removeClass('is-invalid');
  \$(stepSelector).find('.custom-error-message').remove();
}

function focusFirstError(stepSelector) {
  var first = \$(stepSelector).find('.is-invalid').first();
  if (first.length) {
    if (first.hasClass('select2-hidden-accessible')) {
      first.select2('open');
    } else {
      first.trigger('focus');
    }
  }
}

// ===================== PIÈCES JOINTES =====================
/*
 * Zones de dépôt utilisables à la souris, au clavier et par glisser-déposer.
 * Règle de 5 Mio identique pour le sélecteur et le dépôt.
 */
var MAX_FILE_SIZE = 5242880;
var DOCUMENT_INPUTS = []; // l'inscription AO n'a pas de document PDF obligatoire

function resetFileZone(inputId) {
  \$('#kept-' + inputId).val('0');
  var zone = \$('.custom-file-ui[data-input="' + inputId + '"]');
  var name = zone.find('.custom-file-name');
  zone.removeClass('has-file');
  name.text(name.data('empty'));
  zone.find('.custom-file-remove').prop('hidden', true);
}

\$('.custom-file-ui').each(function() {
  var zone = \$(this);
  zone.attr({ role: 'button', tabindex: '0', 'aria-controls': zone.data('input') });
  zone.attr('aria-label', 'Choose or drop a file: ' + zone.find('strong').first().text().replace('*', '').trim());
  zone.append(
    '<button type="button" class="btn btn-sm custom-file-remove" hidden>'
      + '<i class="ri-delete-bin-6-line" aria-hidden="true"></i> Remove'
    + '</button>'
  );
});

\$('.custom-file-ui').on('click', function(e) {
  if (\$(e.target).closest('.custom-file-remove').length) {
    return;
  }
  e.preventDefault();
  \$('#' + \$(this).data('input')).trigger('click');
});

\$('.custom-file-ui').on('keydown', function(e) {
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
  var input = document.getElementById(\$(this).data('input'));
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

  var inputId = \$(this).closest('.custom-file-ui').data('input');
  var input = document.getElementById(inputId);

  if (input) {
    input.value = '';
  }

  resetFileZone(inputId);
  clearFieldError('#' + inputId);
  updateEligibility();
});

\$('.custom-file-real').on('change', function() {
  var inputId = this.id;
  var file = this.files && this.files.length ? this.files[0] : null;

  clearFieldError('#' + inputId);

  if (file && file.size > MAX_FILE_SIZE) {
    this.value = '';
    resetFileZone(inputId);
    showFieldError('#' + inputId, 'The file size exceeds the 5 MB limit.');
    updateEligibility();
    return;
  }

  if (file && DOCUMENT_INPUTS.indexOf(inputId) !== -1 && !/\\.pdf$/i.test(file.name)) {
    this.value = '';
    resetFileZone(inputId);
    showFieldError('#' + inputId, 'Please choose a PDF file.');
    updateEligibility();
    return;
  }

  if (!file) {
    resetFileZone(inputId);
  } else {
    // Un nouveau fichier remplace celui conservé par le serveur.
    \$('#kept-' + inputId).val('0');
    var zone = \$('.custom-file-ui[data-input="' + inputId + '"]');
    var sizeKb = Math.max(1, Math.round(file.size / 1024));
    zone.addClass('has-file');
    zone.find('.custom-file-name').text(file.name + ' · ' + (sizeKb >= 1024 ? (sizeKb / 1024).toFixed(1) + ' MB' : sizeKb + ' KB'));
    zone.find('.custom-file-remove').prop('hidden', false);
  }

  updateEligibility();
});

// Fichiers conservés par le serveur après une erreur : affichés comme déjà joints.
\$('[data-kept-label]').each(function() {
  var inputId = \$(this).data('kept-label');
  var zone = \$('.custom-file-ui[data-input="' + inputId + '"]');
  zone.addClass('has-file');
  zone.find('.custom-file-name').text(\$(this).data('label'));
  zone.find('.custom-file-remove').prop('hidden', false);
});

// ===================== MOT DE PASSE =====================
\$('.mro-password-toggle').on('click', function() {
  var input = document.getElementById(\$(this).data('target'));
  var show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  \$(this)
    .attr({ 'aria-pressed': show ? 'true' : 'false', 'aria-label': show ? 'Hide password' : 'Show password' })
    .find('i').attr('class', show ? 'ri-eye-off-line' : 'ri-eye-line');
});

function updatePasswordRules() {
  var password = \$('#password').val();
  var confirm = \$('#confirm_password').val();
  \$('.mro-rules [data-rule="length"]').toggleClass('is-ok', password.length >= 6);
  \$('.mro-rules [data-rule="mix"]').toggleClass('is-ok', /[A-Za-z]/.test(password) && /[0-9]/.test(password));
  \$('.mro-rules [data-rule="match"]').toggleClass('is-ok', password !== '' && password === confirm);
  \$('.mro-rules li').each(function() {
    \$(this).find('i').attr('class', \$(this).hasClass('is-ok') ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line');
  });
}
\$('#password, #confirm_password').on('input', updatePasswordRules);

// ===================== NAVIGATION ENTRE ÉTAPES =====================
var currentStep = 1;
var furthestStep = 1;

function setActiveStep(step) {
  var stepNow = document.getElementById('ob-step-now');
  var stepBar = document.getElementById('ob-step-bar');
  if (stepNow) {
    stepNow.textContent = step;
  }
  if (stepBar) {
    stepBar.style.width = (step * 25) + '%';
  }

  document.querySelectorAll('.mro-stepper-item[data-step]').forEach(function(item) {
    var itemStep = Number(item.getAttribute('data-step'));
    var isActive = itemStep === step;

    item.classList.toggle('active', isActive);
    item.classList.toggle('is-complete', itemStep < step || (itemStep <= furthestStep && itemStep !== step && itemStep < furthestStep));
    item.disabled = itemStep > furthestStep;

    if (isActive) {
      item.setAttribute('aria-current', 'step');
    } else {
      item.removeAttribute('aria-current');
    }
  });
}

function goToStep(step, options) {
  step = Math.max(1, Math.min(4, step));
  currentStep = step;
  furthestStep = Math.max(furthestStep, step);

  for (var i = 1; i <= 4; i++) {
    document.getElementById('step-' + i).hidden = i !== step;
  }

  setActiveStep(step);

  if (step === 4) {
    fillReview();
  }

  if (!options || options.scroll !== false) {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  var firstField = \$('#step-' + step).find('input:not([type=hidden]):not([type=file]), select, .custom-file-ui').filter(':visible').first();
  if (firstField.length && (!options || options.focus !== false)) {
    firstField.trigger('focus');
  }
}

\$('.mro-stepper-item').on('click', function() {
  var step = Number(\$(this).data('step'));
  if (step <= furthestStep) {
    goToStep(step);
  }
});

\$('.mro-review-edit').on('click', function() {
  goToStep(Number(\$(this).data('goto')));
});

// ===================== VALIDATION PAR ÉTAPE =====================
function validateStep1() {
  var username = \$('#username').val().trim();
  var password = \$('#password').val().trim();
  var confirmPassword = \$('#confirm_password').val().trim();
  var email = \$('#email').val().trim();
  var ok = true;

  clearStepErrors('#step-1');

  if (!email) {
    ok = false;
    showFieldError('#email', 'Email is required.');
  } else if (!/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(email)) {
    ok = false;
    showFieldError('#email', 'Please enter a valid email address.');
  }

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

  return ok;
}

function validateRequired(stepSelector, rules) {
  var ok = true;
  clearStepErrors(stepSelector);
  rules.forEach(function(rule) {
    if (!String(\$(rule[0]).val() || '').trim()) {
      ok = false;
      showFieldError(rule[0], rule[1]);
    }
  });
  return ok;
}

function validateStep2() {
  return validateRequired('#step-2', [
    ['#country-select', 'Country is required.'],
    ['#city-select', 'City is required.'],
    ['#address', 'Address is required.'],
    ['#zip_code', 'Postal code is required.'],
    ['#first_name', 'First name is required.'],
    ['#last_name', 'Last name is required.'],
    ['#contact_number', 'Direct phone is required.']
  ]);
}

function validateStep3() {
  return validateRequired('#step-3', [
    ['#manufacturer', 'Manufacturer is required.'],
    ['#model-dropdown', 'Model is required.'],
    ['#registration_number', 'Registration is required.'],
    ['#serial_number', 'Serial number is required.'],
    ['#certificate-type-dropdown', 'Certificate type is required.']
  ]);
}

function validateStep4() {
  clearStepErrors('#step-4');
  if (!\$('#terms-check').is(':checked')) {
    showFieldError('#terms-check', 'You must agree to the terms and conditions.');
    return false;
  }
  return true;
}

var VALIDATORS = { 1: validateStep1, 2: validateStep2, 3: validateStep3, 4: validateStep4 };

function continueFrom(step) {
  if (!VALIDATORS[step]()) {
    focusFirstError('#step-' + step);
    return;
  }
  goToStep(step + 1);
}

\$('#next-step-1').on('click', function() { continueFrom(1); });
\$('#next-step-2').on('click', function() { continueFrom(2); });
\$('#next-step-3').on('click', function() { continueFrom(3); });
\$('#prev-step-2').on('click', function() { goToStep(1); });
\$('#prev-step-3').on('click', function() { goToStep(2); });
\$('#prev-step-4').on('click', function() { goToStep(3); });

// ENVOI : toutes les étapes sont revérifiées ; la première en erreur est rouverte.
\$('#submit-btn').on('click', function(e) {
  for (var step = 1; step <= 4; step++) {
    if (!VALIDATORS[step]()) {
      e.preventDefault();
      goToStep(step, { focus: false });
      focusFirstError('#step-' + step);
      return false;
    }
  }
});

// ===================== FICHE AVION ET RÉCAPITULATIF =====================
function selectedText(selector) {
  var option = \$(selector).find('option:selected');
  return option.val() ? option.text().trim() : '';
}

function hasFile(id) {
  var input = document.getElementById(id);
  return !!(input && input.files && input.files.length) || \$('#kept-' + id).val() === '1';
}

function aircraftTypeLabel() {
  var manufacturer = selectedText('#manufacturer');
  var model = selectedText('#model-dropdown');
  if (model && manufacturer && model.toLowerCase().indexOf(manufacturer.toLowerCase()) === 0) {
    return model;
  }
  return [manufacturer, model].filter(Boolean).join(' ');
}

// Nom gardé « updateEligibility » : appelé aussi par la gestion des pièces jointes.
function updateEligibility() {
  var registration = \$('#registration_number').val().trim().toUpperCase();
  var serial = \$('#serial_number').val().trim();
  var certificate = selectedText('#certificate-type-dropdown');
  var criteria = {
    type: { ok: !!\$('#model-dropdown').val(), detail: aircraftTypeLabel() || 'Step 3' },
    registration: { ok: !!registration, detail: registration || 'Step 3' },
    serial: { ok: !!serial, detail: serial || 'Step 3' },
    certificate: { ok: !!certificate, detail: certificate || 'Step 3' }
  };
  var ready = 0;

  Object.keys(criteria).forEach(function(key) {
    var item = \$('.mro-criteria [data-criterion="' + key + '"]');
    item.toggleClass('is-ready', criteria[key].ok);
    item.find('[data-detail]').text(criteria[key].detail);
    if (criteria[key].ok) {
      ready++;
    }
  });

  \$('#mro-ready-count').text(ready);
  \$('#mro-progress-bar').css('width', (ready * 25) + '%');
}

function fillReview() {
  var account = [\$('#email').val().trim(), \$('#username').val().trim()].filter(Boolean).join(' · ');
  var operator = [
    \$('#company_name').val().trim(),
    [selectedText('#city-select'), selectedText('#country-select')].filter(Boolean).join(', '),
    [\$('#first_name').val().trim(), \$('#last_name').val().trim()].filter(Boolean).join(' '),
    \$('#contact_number').val().trim()
  ].filter(Boolean).join(' · ');
  var aircraft = [
    aircraftTypeLabel(),
    \$('#registration_number').val().trim().toUpperCase(),
    \$('#serial_number').val().trim() ? 'MSN ' + \$('#serial_number').val().trim() : '',
    selectedText('#certificate-type-dropdown')
  ].filter(Boolean).join(' · ');

  \$('[data-review="1"]').text(account || '—');
  \$('[data-review="2"]').text(operator || '—');
  \$('[data-review="3"]').text(aircraft || '—');
  updateEligibility();
}

\$('#registration_number, #serial_number').on('input', updateEligibility);
\$('#certificate-type-dropdown, #model-dropdown').on('change', updateEligibility);

// Efface l'erreur d'un champ dès que l'utilisateur le corrige.
\$(document).on('input change', '.mro-signup input, .mro-signup select', function() {
  if (this.id && !\$(this).hasClass('custom-file-real')) {
    clearFieldError('#' + this.id);
  }
});

// ===================== CONSTRUCTEUR -> MODÈLES (liste fournie par le serveur) =====================
var aircraftModels = {$aircraftModelsJson};

function syncAircraftModelId() {
  var manufacturer = \$('#manufacturer').val();
  var model = \$('#model-dropdown').val();
  var match = aircraftModels.filter(function(item) {
    return item.manufacturer === manufacturer && item.model === model;
  })[0];
  \$('#aircraft-model-id').val(match ? match.aircraft_model_id : '');
}

\$('#manufacturer').on('change', function() {
  var manufacturer = this.value;
  var modelDropdown = document.getElementById('model-dropdown');

  modelDropdown.innerHTML = '';
  var prompt = document.createElement('option');
  prompt.value = '';
  prompt.textContent = manufacturer ? 'Select model' : 'Select a manufacturer first';
  modelDropdown.appendChild(prompt);

  aircraftModels.forEach(function(item) {
    if (item.manufacturer === manufacturer) {
      var option = document.createElement('option');
      option.value = item.model;
      option.textContent = item.model;
      modelDropdown.appendChild(option);
    }
  });

  syncAircraftModelId();
  updateEligibility();
});

\$('#model-dropdown').on('change', syncAircraftModelId);


// ===================== ALERTE SERVEUR : FERMETURE =====================
\$('#mro-alert-close').on('click', function() {
  \$('#mro-alert').remove();
});

// ===================== CONDITIONS =====================
\$('#show-terms-btn').on('click', function() {
  var content = document.getElementById('mro-terms-content');
  var willOpen = content.hidden;

  content.hidden = !willOpen;
  \$(this)
    .attr('aria-expanded', willOpen ? 'true' : 'false')
    .text(willOpen ? 'Hide the terms' : 'Read the terms');
});

\$('#terms-check').on('change', function() {
  \$('#submit-btn').prop('disabled', !this.checked);
  if (this.checked) {
    clearFieldError('#terms-check');
  }
});

// ===================== PAYS -> VILLES =====================
(function() {
  var countrySelect = document.getElementById('country-select');
  var citySelect = document.getElementById('city-select');

  if (!countrySelect || !citySelect) {
    return;
  }

  countrySelect.addEventListener('change', function() {
    var countryId = this.value;

    citySelect.innerHTML = '<option value="">' + (countryId ? 'Loading cities…' : 'Select country first') + '</option>';

    if (!countryId) {
      return;
    }

    fetch({$citiesUrl} + '?countryId=' + encodeURIComponent(countryId), { headers: { 'Accept': 'application/json' } })
      .then(function(response) {
        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }
        return response.json();
      })
      .then(function(cities) {
        citySelect.innerHTML = '<option value="">Select city</option>';
        cities.forEach(function(city) {
          var opt = document.createElement('option');
          opt.value = city.city_id;
          opt.textContent = city.city_name;
          citySelect.appendChild(opt);
        });
      })
      .catch(function() {
        citySelect.innerHTML = '<option value="">Select city</option>';
        showFieldError('#city-select', 'Cities could not be loaded. Please try again.');
      });
  });
})();


// ===================== DÉMARRAGE =====================
// Après une erreur serveur, la page s'ouvre sur la première étape à corriger.
(function() {
  var start = {$startStep};
  furthestStep = start;
  updatePasswordRules();
  syncAircraftModelId();
  updateEligibility();
  goToStep(start, { scroll: start !== 1, focus: false });
})();
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>

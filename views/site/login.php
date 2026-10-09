<?php
/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\LoginForm $model */
/** @var bool $captchaRequired */
/** @var bool $captchaError */

use app\components\PrelaunchMode;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;
$prelaunchMode = PrelaunchMode::isEnabled();
$captchaRequired = $captchaRequired ?? false;
$captchaError = $captchaError ?? false;
$loginTurnstileSiteKey = trim((string) (Yii::$app->params['turnstileSiteKey'] ?? ''));
$loginTurnstileHostname = trim((string) (Yii::$app->params['turnstileExpectedHostname'] ?? ''));
$loginTurnstileConfigured = $loginTurnstileSiteKey !== '' && $loginTurnstileHostname !== '';
if ($captchaRequired && $loginTurnstileConfigured) {
    $this->registerJsFile('https://challenges.cloudflare.com/turnstile/v0/api.js', [
        'async' => true,
        'defer' => true,
    ]);
}
?>

<!--
  SECTION PRINCIPALE LOGIN
  .hero-login => gère le full-screen et le background
-->
<section class="hero-login">

  <!-- FOND VIDEO DESKTOP : l'image WebP reste le poster visible pendant le
       chargement et sert automatiquement de fallback. L'URL MP4 est conservee
       dans data-src afin que le navigateur mobile ne telecharge pas la video. -->
  <video
      id="loginBackgroundVideo"
      class="login-background-video"
      poster="<?= Url::to('@web/img/core-aviation-network-hero.webp') ?>"
      autoplay
      muted
      loop
      playsinline
      preload="none"
      aria-hidden="true"
      tabindex="-1">
    <source
        data-src="<?= Url::to('@web/img/can-drone.mp4') ?>"
        type="video/mp4">
  </video>

  <!-- Wrapper pour centrer la carte -->
  <div class="auth-wrap">

    <!-- Carte d'authentification -->
    <div class="auth-card shadow" role="region" aria-label="Sign in">

      <!-- ================== BRAND (logo + titre) ================== -->
      <div class="brand text-center mb-3">
        <!-- Logo CAN -->
        <a href="<?= Url::to(['/site/index']) ?>" class="d-inline-block">
    <img
        src="<?= Yii::getAlias('@web/logo/can-logo-main.png') ?>"
        alt="Logo <?= Html::encode(Yii::$app->name) ?>"
        class="img-fluid mb-2"
        style="max-height:120px"
        title="<?= Html::encode(Yii::$app->name) ?>"
        data-bs-toggle="tooltip"
        data-bs-placement="bottom"
        data-bs-delay='{"show":500,"hide":100}'
        data-bs-html="true"
    >
</a>


        <p class="auth-eyebrow">Secure workspace</p>
        <h1 class="title">Welcome back</h1>
        <p class="auth-subtitle">
          Sign in to <?= Html::encode(Yii::$app->name) ?>
        </p>
      </div>

      <!-- ================== MESSAGES FLASH ================== -->
      <?php
      /**
       * On boucle sur plusieurs clés possibles de flash :
       * - message / success -> vert
       * - warning -> orange pour un lien arrivé à expiration
       * - info -> bleu lorsque l'adresse est déjà vérifiée
       * - error / usernameError / passwordError -> rouge
       */
      foreach ([
           'message'       => 'alert-success',
           'success'       => 'alert-success',
           'warning'       => 'alert-warning',
           'info'          => 'alert-info',
           'error'         => 'alert-danger',
          'usernameError' => 'alert-danger',
          'passwordError' => 'alert-danger',
      ] as $flashKey => $class): ?>
        <?php if (Yii::$app->session->hasFlash($flashKey)): ?>
          <div class="alert <?= $class ?> mb-3">
            <?= Yii::$app->session->getFlash($flashKey) ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>

      <!-- ================== FORMULAIRE LOGIN ================== -->
      <?php $form = ActiveForm::begin([
          'id' => 'login-form',
          // VALIDATION PROGRESSIVE : les messages ne sont affiches qu'au moment
          // ou l'utilisateur tente de se connecter. Cela evite de presenter le
          // champ Username en erreur pendant la simple saisie ou au chargement.
          'validateOnBlur' => false,
          'validateOnChange' => false,
          'validateOnType' => false,
          'validateOnSubmit' => true,
          'options' => [
              // La validation HTML native est desactivee pour conserver un rendu
              // homogene : Yii affiche les erreurs serveur et client au meme endroit.
              'novalidate' => true,
          ],
          'fieldConfig' => [
              // classes des messages d'erreur
              // Les details sont regroupes dans l'alerte accessible au-dessus
              // du formulaire; les champs conservent leur etat visuel invalide.
              'errorOptions' => ['class' => 'invalid-feedback visually-hidden'],
          ],
      ]); ?>

      <?php if ($model->hasErrors()): ?>
        <div class="auth-form-alert" role="alert" aria-live="assertive" tabindex="-1"<?= $captchaError ? ' data-login-captcha-error' : '' ?>>
          <i class="ri-error-warning-line" aria-hidden="true"></i>
          <div>
            <strong>We couldn't sign you in</strong>
            <?= $form->errorSummary($model, [
                'header' => '',
                'class' => 'auth-error-summary',
            ]) ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- ===== CHAMP USERNAME ===== -->
      <div class="mb-3">
        <?php
        // Champ username avec label personnalisé
        echo $form->field($model, 'username')
            ->textInput([
                'autofocus'    => true,
                'id'           => 'username',
                'class'        => 'form-control',
                'required'     => true,
                'autocomplete' => 'username',
            ])
            ->label('<i class="ri-user-line"></i> Username', [
                'class' => 'form-label',
            ]);
        ?>
      </div>

      <!-- ===== CHAMP PASSWORD + EYE TOGGLE ===== -->
      <div class="mb-2">
        <!-- Le libelle reste volontairement seul : placer "Forgot username?"
             ici donnait l'impression que ce lien concernait le mot de passe. -->
        <label class="form-label" for="password">
          <span><i class="ri-key-line"></i> Password</span>
        </label>

        <!-- CONTENEUR DU MOT DE PASSE : l'icone est integree au meme sous-bloc
             que l'input. Le message d'erreur reste en dehors de ce sous-bloc et
             ne peut donc plus deplacer verticalement le bouton afficher/masquer. -->
        <div class="password">
          <?php
          // Le template ActiveField place explicitement le bouton dans
          // .password-input-wrap, puis affiche l'erreur sous ce conteneur.
          echo $form->field($model, 'password', [
                  'template' => '<div class="password-input-wrap">{input}'
                      . '<button type="button" class="toggle btn btn-link" id="togglePwd"'
                      . ' aria-label="Show or hide password" aria-pressed="false">'
                      . '<i class="ri-eye-line" aria-hidden="true"></i>'
                      . '</button></div>{error}',
              ])->passwordInput([
                  'id'           => 'password',
                  'class'        => 'form-control',
                  'autocomplete' => 'current-password',
                  'required'     => true,
              ])->label(false);
          ?>
        </div>
        <p class="caps-lock-warning" id="capsLockWarning" role="status" aria-live="polite" hidden>
          <i class="ri-arrow-up-circle-line" aria-hidden="true"></i>
          Caps Lock is on
        </p>
      </div>

      <!-- ===== OPTION DE SESSION ===== -->
      <div class="login-options mb-2">

        <!-- Checkbox "Remember me" -->
        <div class="form-check mb-0">
          <?php
          echo $form->field($model, 'rememberMe', [
                  'template' => "{input}\n{error}",
                  'options' => ['class' => 'remember-me-field'],
              ])->checkbox([
                  'id'    => 'rememberMe',
                  'class' => 'form-check-input',
              ])->label('Keep me signed in', ['class' => 'form-check-label']);
          ?>
          <small class="remember-me-hint">Only use this option on a private device.</small>
        </div>

      </div>

      <!-- RECUPERATION DU COMPTE : les deux parcours sont regroupes dans une
           zone unique afin que leur fonction soit immediate et sans ambiguite. -->
      <nav class="auth-recovery-links mb-3" aria-label="Account recovery">
        <a href="<?= Url::to(['site/request-username-reset']) ?>" class="link">
          <i class="ri-user-search-line" aria-hidden="true"></i>
          Forgot username?
        </a>
        <a href="<?= Url::to(['site/request-password-reset']) ?>" class="link">
          <i class="ri-key-2-line" aria-hidden="true"></i>
          Forgot password?
        </a>
      </nav>

      <?php if ($captchaRequired): ?>
        <div class="login-challenge" aria-label="Visitor verification">
          <?php if ($loginTurnstileConfigured): ?>
            <div
              class="cf-turnstile"
              data-sitekey="<?= Html::encode($loginTurnstileSiteKey) ?>"
              data-action="login"
              data-theme="light"
              data-language="en"
              data-callback="onLoginTurnstileSuccess"
              data-expired-callback="onLoginTurnstileExpired"
            ></div>
          <?php else: ?>
            <p class="login-challenge-unavailable" role="alert">
              Visitor verification is temporarily unavailable. Please contact support.
            </p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- ===== BOUTON SUBMIT ===== -->
      <div class="form-group mb-2">
        <?=
        Html::submitButton(
            // texte + icône
            '<i class="ri-login-circle-line btn-icon" aria-hidden="true"></i>'
                . '<span class="btn-spinner" aria-hidden="true"></span>'
                . '<span class="btn-text">Sign in</span>',
            [
                'class' => 'btn btn-primary w-100',
                'id'    => 'submitBtn',
                'data-loading-text' => 'Signing in…',
                'disabled' => $captchaRequired && !$loginTurnstileConfigured,
            ]
        );
        ?>
      </div>

      <?php ActiveForm::end(); ?>

      <!-- NAVIGATION PUBLIQUE : ces liens offrent une sortie claire vers
           l'accueil et les inscriptions sans concurrencer le bouton Sign In. -->
      <div class="auth-public-links" aria-label="Public navigation">
        <a href="<?= Url::to(['/site/index']) ?>" class="auth-home-link">
          <i class="ri-arrow-left-line" aria-hidden="true"></i>
          Back to home
        </a>
        <div class="auth-signup-links">
          <?php if ($prelaunchMode): ?>
            <a href="<?= Url::to(['/site/index', '#' => 'early-access']) ?>">Request early access</a>
          <?php else: ?>
            <a href="<?= Url::to(['/site/become-mro']) ?>">Request MRO access</a>
            <span aria-hidden="true">·</span>
            <a href="<?= Url::to(['/site/become-ao']) ?>">Request operator access</a>
          <?php endif; ?>
        </div>
        <button type="button" class="auth-support-link" id="loginSupportLink">
          <i class="ri-customer-service-2-line" aria-hidden="true"></i>
          Need help? Contact support
        </button>
      </div>

      <!-- ================== FOOTER COPYRIGHT ================== -->
      <footer class="login-footer mt-3 text-center small">
        © <?= date('Y') ?>
        <strong><?= Html::encode(Yii::$app->name) ?></strong>
        — All rights reserved
      </footer>

    </div><!-- /.auth-card -->

  </div><!-- /.auth-wrap -->

</section><!-- /.hero-login -->

<?php
// ============================================================
// JS pour afficher/masquer le mot de passe
// - change le type du champ (password <-> text)
// - change l'icône (oeil ouvert / fermé)
// - met à jour aria-pressed pour l'accessibilité
// ============================================================
$js = <<<JS
(function() {
  const btn = document.getElementById('togglePwd');
  const input = document.getElementById('password');
  if (!btn || !input) return;

  btn.addEventListener('click', function () {
    const isText = input.type === 'text';
    // Si actuellement text => on repasse en password, sinon l'inverse
    input.type = isText ? 'password' : 'text';

    // Mise à jour de l'état aria (accessibilité)
    this.setAttribute('aria-pressed', (!isText).toString());

    // Changement de l'icône
    const icon = this.querySelector('i');
    if (icon) {
      icon.classList.toggle('ri-eye-line', isText);
      icon.classList.toggle('ri-eye-off-line', !isText);
    }
  });
})();

// Caps Lock feedback, accessible error focus and duplicate-submit protection.
(function enhanceLoginForm() {
  const form = document.getElementById('login-form');
  const password = document.getElementById('password');
  const capsWarning = document.getElementById('capsLockWarning');
  const submitButton = document.getElementById('submitBtn');
  const formAlert = document.querySelector('.auth-form-alert');
  const supportLink = document.getElementById('loginSupportLink');

  window.onLoginTurnstileSuccess = function () {
    const captchaAlert = document.querySelector('[data-login-captcha-error]');
    if (captchaAlert) captchaAlert.remove();
    if (password) {
      password.classList.remove('is-invalid');
      password.removeAttribute('aria-invalid');
    }
  };

  window.onLoginTurnstileExpired = function () {
    const challenge = document.querySelector('.login-challenge');
    if (challenge) challenge.classList.add('is-expired');
  };

  if (formAlert) {
    formAlert.focus();
  }

  if (password && capsWarning) {
    const updateCapsLock = function (event) {
      capsWarning.hidden = !event.getModifierState || !event.getModifierState('CapsLock');
    };
    password.addEventListener('keydown', updateCapsLock);
    password.addEventListener('keyup', updateCapsLock);
    password.addEventListener('blur', function () {
      capsWarning.hidden = true;
    });
  }

  if (form && submitButton) {
    // Un bouton submit natif garantit que la touche Entree suit exactement
    // le meme parcours de validation et de chargement qu'un clic.
    const startSubmitting = function () {
      if (submitButton.disabled) return;
      submitButton.disabled = true;
      submitButton.classList.add('is-loading');
      submitButton.setAttribute('aria-busy', 'true');
      const text = submitButton.querySelector('.btn-text');
      if (text) text.textContent = submitButton.dataset.loadingText;
    };

    // Yii declenche beforeSubmit uniquement lorsque la validation client est
    // terminee. Le fallback natif couvre le cas ou ActiveForm est indisponible.
    if (window.jQuery && window.jQuery.fn.yiiActiveForm) {
      window.jQuery(form).on('beforeSubmit.loginLoading', function () {
        startSubmitting();
        return true;
      });
    } else {
      form.addEventListener('submit', startSubmitting);
    }

    window.addEventListener('pageshow', function () {
      submitButton.disabled = false;
      submitButton.classList.remove('is-loading');
      submitButton.removeAttribute('aria-busy');
      const text = submitButton.querySelector('.btn-text');
      if (text) text.textContent = 'Sign in';
    });
  }

  if (supportLink) {
    supportLink.addEventListener('click', function () {
      const supportLauncher = document.querySelector('#canSupportWidget [data-support-open]');
      if (supportLauncher) supportLauncher.click();
    });
  }
})();

// ============================================================
// CHARGEMENT RESPONSABLE DU FOND VIDEO
// - charge le MP4 uniquement sur un ecran desktop ;
// - respecte la preference systeme de reduction des mouvements ;
// - conserve le poster WebP si la lecture automatique est refusee ;
// - libere la source lors d'un passage vers une largeur mobile.
// ============================================================
(function initLoginBackgroundVideo() {
  const video = document.getElementById('loginBackgroundVideo');
  const source = video ? video.querySelector('source[data-src]') : null;
  if (!video || !source) return;

  const desktopQuery = window.matchMedia('(min-width: 992px)');
  const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

  function synchronizeBackgroundVideo() {
    const canAnimate = desktopQuery.matches && !reducedMotionQuery.matches;

    if (canAnimate) {
      if (!source.src) {
        source.src = source.dataset.src;
        video.load();
      }

      const playback = video.play();
      if (playback && typeof playback.catch === 'function') {
        // Un refus d'autoplay n'est pas bloquant : le poster reste visible.
        playback.catch(function () {});
      }
      return;
    }

    video.pause();
    if (source.src) {
      source.removeAttribute('src');
      video.load();
    }
  }

  synchronizeBackgroundVideo();
  desktopQuery.addEventListener('change', synchronizeBackgroundVideo);
  reducedMotionQuery.addEventListener('change', synchronizeBackgroundVideo);
})();
JS;

$this->registerJs($js);
?>

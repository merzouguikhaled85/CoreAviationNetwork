<?php
/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;
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


        <!-- Titre principal (nom de l'application) -->
        <h1 class="title">
          <!-- Icône cadenas (Remix Icon) -->
          <i class="ri-lock-line"></i>
          <?= Html::encode(Yii::$app->name) ?>
        </h1>
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
              'errorOptions' => ['class' => 'invalid-feedback d-block small'],
          ],
      ]); ?>

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
      </div>

      <!-- ===== OPTION DE SESSION ===== -->
      <div class="login-options mb-2">

        <!-- Checkbox "Remember me" -->
        <div class="form-check mb-0">
          <?php
          echo $form->field($model, 'rememberMe', [
                  'template' => "{input}\n{error}",
              ])->checkbox([
                  'id'    => 'rememberMe',
                  'class' => 'form-check-input',
              ])->label('Remember me', ['class' => 'form-check-label']);
          ?>
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

      <!-- ===== BOUTON SUBMIT ===== -->
      <div class="form-group mb-2">
        <?=
        Html::submitButton(
            // texte + icône
            '<i class="ri-login-circle-line"></i> <span class="btn-text">Sign In</span>',
            [
                'class' => 'btn btn-primary w-100',
                'id'    => 'submitBtn',
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
          Back to Home
        </a>
        <div class="auth-signup-links">
          <a href="<?= Url::to(['/site/become-mro']) ?>">MRO Signup</a>
          <span aria-hidden="true">·</span>
          <a href="<?= Url::to(['/site/become-ao']) ?>">Operator Signup</a>
        </div>
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

<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Html;

$isHome = (
    Yii::$app->controller->id === 'site'
    && (Yii::$app->controller->action->id === 'index'
        || Yii::$app->controller->action->id === 'landing')
);

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css',
    ['rel' => 'stylesheet', 'position' => \yii\web\View::POS_HEAD],
    'can-remixicon'
);

$this->registerCssFile('@web/css/can.css', ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]);
$this->registerJsFile('@web/js/can.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => \yii\web\View::POS_END]);

$extraBodyClass = $this->params['bodyClass'] ?? '';
$bodyDataTheme  = $this->params['bodyDataTheme'] ?? null;

/*
 * DÉTECTION DU MESSAGE FLASH SUR LES PAGES PUBLIQUES
 * -------------------------------------------------
 * Cette table centralise aussi les anciens messages « message », « usernameError »
 * et « passwordError ». Le widget du layout devient ainsi l'unique responsable de
 * leur rendu et évite qu'une même notification apparaisse dans la vue et le layout.
 */
$landingFlashTypes = [
    'message' => 'alert-success',
    'success' => 'alert-success',
    'error' => 'alert-danger',
    'danger' => 'alert-danger',
    'warning' => 'alert-warning',
    'info' => 'alert-info',
    'usernameError' => 'alert-danger',
    'passwordError' => 'alert-danger',
];
$hasLandingFlash = false;
foreach (array_keys($landingFlashTypes) as $landingFlashType) {
    if (Yii::$app->session->hasFlash($landingFlashType)) {
        $hasLandingFlash = true;
        break;
    }
}

$bodyClass = trim(
    'd-flex flex-column h-100 '
    . ($isHome ? 'page-home ' : '')
    . ($hasLandingFlash ? 'has-landing-flash ' : '')
    . $extraBodyClass
);

/*
 * PREPARATION DU SUPPORT GLOBAL :
 * le partiel est rendu avant head() afin que Yii publie son CSS dans le document.
 * Son HTML sera insere en fin de body, au-dessus du carrousel sans changer celui-ci.
 */
$supportWidget = $this->render('_support-widget');
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <meta charset="utf-8">
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>

<body class="<?= Html::encode($bodyClass) ?>"
      <?= $bodyDataTheme ? 'data-theme="' . Html::encode($bodyDataTheme) . '"' : '' ?>>

<?php $this->beginBody() ?>

<?= $this->render('can-navbar') ?>

<main id="main" class="flex-shrink-0" role="main">

<?php
$this->registerCss("
  /*
   * NOTIFICATION FLASH PUBLIQUE
   * ---------------------------
   * Le message est placé sous la navigation afin que son fond et ses effets visuels
   * ne recouvrent plus l'en-tête. Le conteneur reste transparent et limité à la
   * largeur de la notification : aucune bande colorée ne peut couvrir la page.
   */
  #flash-message-overlay {
    position: fixed;
    top: 92px;
    right: 24px;
    width: 350px;
    max-width: calc(100vw - 48px);
    pointer-events: none;
    z-index: 2100;
    display: flex;
    flex-direction: column;
    gap: 8px;
    height: auto;
    min-height: 0;
    padding: 0;
    background: transparent !important;
    box-shadow: none !important;
    isolation: isolate;
  }

  /*
   * Lorsque la page d'accueil transparente affiche une notification, l'en-tête
   * reçoit temporairement un fond sombre opaque. Cela conserve le contraste du logo
   * et des liens, sans modifier le rendu normal de la page lorsqu'il n'y a aucun flash.
   */
  body.page-home.has-landing-flash .header {
    background: rgba(7, 13, 25, 0.96) !important;
    border-bottom-color: rgba(148, 163, 184, 0.18) !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
  }

  /*
   * Carte compacte et entièrement opaque : la suppression du backdrop-filter évite
   * que la couleur du héros soit diffusée dans la barre de navigation située derrière.
   */
  #flash-message-overlay .alert {
    pointer-events: auto;
    width: 100%;
    margin-bottom: 0;
    border: 1px solid #dbe4ee;
    border-radius: 10px;
    background: #ffffff;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
    padding: 12px 14px;
    color: #1e293b;
    font-size: 14px;
    line-height: 1.4;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    animation: toast-slide-in 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  }
  
  #flash-message-overlay .alert-success {
    border-color: #a7f3d0;
    border-left: 4px solid #10b981;
    background: #f0fdf4;
  }
  
  #flash-message-overlay .alert-danger {
    border-color: #fecaca;
    border-left: 4px solid #ef4444;
    background: #fef2f2;
  }
  
  #flash-message-overlay .alert-warning {
    border-color: #fde68a;
    border-left: 4px solid #f59e0b;
    background: #fffbeb;
  }
  
  #flash-message-overlay .alert-info {
    border-color: #bfdbfe;
    border-left: 4px solid #3b82f6;
    background: #eff6ff;
  }

  #flash-message-overlay .alert-dismissible .btn-close {
    position: static !important;
    margin: 0 0 0 12px !important;
    padding: 6px !important;
    font-size: 12px;
    opacity: 0.6;
    transition: opacity 0.2s ease;
    align-self: center !important;
    display: inline-flex !important;
    border-radius: 6px;
  }
  
  #flash-message-overlay .alert-dismissible .btn-close:hover {
    opacity: 1;
    background-color: rgba(15, 23, 42, 0.08);
  }

  /* Sur mobile, la notification conserve des marges régulières sous le menu. */
  @media (max-width: 575.98px) {
    #flash-message-overlay {
      top: 76px;
      right: 12px;
      left: 12px;
      width: auto;
      max-width: none;
    }
  }

  @keyframes toast-slide-in {
    from {
      transform: translateX(120%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
");

?>
  <!--
      Zone indépendante réservée aux retours d'inscription et d'authentification.
      Elle ne possède volontairement aucun fond global pour préserver la navigation.
  -->
  <div id="flash-message-overlay" aria-live="polite" aria-atomic="true">
      <?= Alert::widget(['alertTypes' => $landingFlashTypes]) ?>
  </div>

  <?= $content ?>

</main>

<?= $this->render('can-footer') ?>

<!-- CAN Global Spinner (global) -->

<?= $this->render('can-spinner') ?>

<!-- Bulle Support disponible sur l'accueil et les pages publiques. -->
<?= $supportWidget ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

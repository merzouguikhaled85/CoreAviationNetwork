<?php
/** @var yii\web\View $this */

use yii\helpers\Url;
use yii\helpers\Html;

$isGuest = Yii::$app->user->isGuest;

// Route actuelle
$controller = Yii::$app->controller->id;
$action = Yii::$app->controller->action->id;
$currentRoute = $controller . '/' . $action;

// Est-ce la home ?
$isHome = ($currentRoute === 'site/index' || $currentRoute === 'site/landing');

// Helper active
$navActive = function (string $route) use ($currentRoute): string {
    return $currentRoute === $route ? ' active' : '';
};

// Nom user connecté
$currentUserLabel = null;
if (!$isGuest && Yii::$app->user->identity !== null) {
    $identity = Yii::$app->user->identity;
    $currentUserLabel = $identity->username ?? $identity->email ?? ('User #' . $identity->id);
}
?>


<header class="header nav-aviation" role="banner">
  <div class="header-inner">

    <!-- Logo + texte -->
    <div class="logo-area">
      <a class="brand-lockup" aria-label="Core Aviation Network - Home" href="<?= Url::to(['/']) ?>">
        <img src="<?= Url::to('@web/logo/can-logo-main.png') ?>" 
             alt="Core Aviation Network Logo" 
             class="logo-mark" >
      </a>
      <div>
        <div class="logo-text-main">Core Aviation Network</div>
        <div class="logo-text-sub">Aircraft Services Network</div>
      </div>
    </div>

    <!-- Menu desktop -->
    <nav class="nav" role="navigation" aria-label="Primary">

      <a href="<?= Url::to(['/site/index']) ?>"
         class="<?= $navActive('site/index') ?>">
        <i class="ri-home-5-line"></i> Home
      </a>
<?php if ($isGuest): ?>
      <a href="<?= Url::to(['/site/become-mro']) ?>"
         class="<?= $navActive('site/become-mro') ?>">
        <i class="ri-building-2-line"></i> MRO Signup
      </a>

      <a href="<?= Url::to(['/site/become-ao']) ?>"
         class="<?= $navActive('site/become-ao') ?>">
        <i class="ri-plane-line"></i> Operator Signup
      </a>
<?php endif; ?>
      <a href="<?= Url::to(['/site/about']) ?>"
         class="<?= $navActive('site/about') ?>">
        <i class="ri-price-tag-3-line"></i> About Us
      </a>

      <a href="<?= Url::to(['/site/advertising']) ?>"
         class="<?= $navActive('site/advertising') ?>">
        <i class="ri-megaphone-line"></i> Advertising
      </a>

      <!-- Dashboard visible seulement si connecté -->
      <?php if (!$isGuest): ?>
        <a href="<?= Url::to(['/dashboard/home']) ?>"
           class="<?= $navActive('dashboard/home') ?>">
          <i class="ri-layout-grid-line"></i> Dashboard
        </a>
      <?php endif; ?>

    </nav>

    <!-- Actions header -->
    <div class="header-actions">

      <!-- Dark/Light Switch -->
      <!-- <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
        <i class="theme-toggle-icon sun ri-sun-line"></i>
        <i class="theme-toggle-icon moon ri-moon-line"></i>
        <span class="theme-toggle-knob" aria-hidden="true"></span>
      </button> -->

      <!-- Login / User + Dropdown -->
      <?php if ($isGuest): ?>
        <a class="btn-login <?= $navActive('site/login') ?>"
           href="<?= Url::to(['/site/login']) ?>">
          <i class="ri-login-box-line"></i> Login
        </a>
      <?php else: ?>
        <!-- Menu user avec dropdown -->
        <div class="user-menu">
          <button type="button" class="user-pill user-menu-toggle">
            <i class="ri-user-3-line"></i>
            <span><?= Html::encode($currentUserLabel) ?></span>
            <i class="ri-arrow-down-s-line user-menu-caret"></i>
          </button>

          <div class="user-menu-dropdown">
            <!-- Si tu veux, tu peux rajouter Dashboard ici aussi -->
            <!--
            <a href="<?= Url::to(['/dashboard/home']) ?>">
              <i class="ri-layout-grid-line"></i> Dashboard
            </a>
            -->
            <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'can-navbar-logout-form']) ?>
              <?= Html::submitButton(
                  '<i class="ri-logout-box-line"></i> Logout',
                  ['class' => 'can-navbar-logout-button']
              ) ?>
            <?= Html::endForm() ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Menu mobile toggle -->
      <button id="mobileMenuBtn" class="mobile-menu-toggle" aria-label="Toggle menu">
        <span></span><span></span><span></span>
      </button>

    </div>

  </div>

  <!-- Menu Mobile -->
  <nav id="mobileNav" class="mobile-nav" aria-label="Mobile Navigation">
    
    <a href="<?= Url::to(['/']) ?>"
       class="<?= $navActive('site/index') ?>">
      <i class="ri-home-5-line"></i> Home
    </a>
 <?php if ($isGuest): ?>
    <a href="<?= Url::to(['/site/become-mro']) ?>"
       class="<?= $navActive('site/become-mro') ?>">
      <i class="ri-building-2-line"></i> MRO Signup
    </a>

    <a href="<?= Url::to(['/site/become-ao']) ?>"
       class="<?= $navActive('site/become-ao') ?>">
      <i class="ri-plane-line"></i> Operator Signup
    </a>
    <?php endif; ?>

    <a href="<?= Url::to(['/site/about']) ?>"
       class="<?= $navActive('site/about') ?>">
      <i class="ri-price-tag-3-line"></i> About Us
    </a>

    <a href="<?= Url::to(['/site/advertising']) ?>"
       class="<?= $navActive('site/advertising') ?>">
      <i class="ri-megaphone-line"></i> Advertising
    </a>
    

    <!-- Dashboard visible seulement si connecté -->
    <?php if (!$isGuest): ?>
      <a href="<?= Url::to(['/dashboard/home']) ?>"
         class="<?= $navActive('dashboard/home') ?>">
        <i class="ri-layout-grid-line"></i> Dashboard
      </a>
    <?php endif; ?>

    <!-- Login / Logout -->
    <?php if ($isGuest): ?>
      <a href="<?= Url::to(['/site/login']) ?>"
         class="<?= $navActive('site/login') ?>">
        <i class="ri-login-box-line"></i> Login
      </a>
    <?php else: ?>
      <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'can-navbar-logout-form']) ?>
        <?= Html::submitButton(
            '<i class="ri-logout-box-line"></i> Logout',
            ['class' => 'can-navbar-logout-button']
        ) ?>
      <?= Html::endForm() ?>

    <?php endif; ?>

  </nav>
</header>

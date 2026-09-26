<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $token */
/** @var bool $alreadyUnsubscribed */

$this->title = 'Early Access Preferences | Core Aviation Network';
$this->registerCssFile(
    '@web/css/prelaunch.css?v=20260926-1',
    ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]
);
?>
<section class="prelaunch-preference-page">
    <div class="prelaunch-preference-card">
        <p class="prelaunch-preference-kicker">Core Aviation Network</p>
        <h1>Early access preferences</h1>
        <?php if ($alreadyUnsubscribed): ?>
            <p>This address has already been removed from the launch notification list.</p>
            <?= Html::a('Return to the Home Page', ['/site/index'], ['class' => 'prelaunch-preference-link']) ?>
        <?php else: ?>
            <p>Confirm that you no longer want to receive launch-related updates from Core Aviation Network.</p>
            <?= Html::beginForm(['/site/unsubscribe-prelaunch', 'token' => $token], 'post') ?>
                <?= Html::submitButton('Remove my address', ['class' => 'prelaunch-preference-button']) ?>
                <?= Html::a('Keep my registration', ['/site/index', '#' => 'early-access'], ['class' => 'prelaunch-preference-link']) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
</section>

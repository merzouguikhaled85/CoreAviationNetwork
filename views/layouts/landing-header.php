<?php

use yii\helpers\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\bootstrap5\Dropdown;
use yii\helpers\Url;


/* @var $this yii\web\View */

$this->title = 'GlobalMROs';

?>
<style>
    .gradient-text {
    background: linear-gradient(to right, #BF953F, #FCF6BA, #B38728, #FBF5B7, #AA771C);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 18px;
    font-weight: 700;
}


</style>
<?= \yii\helpers\Html::csrfMetaTags() ?>

<?php NavBar::begin([
    'brandLabel' => '<img src="' . Url::to('@web/img/logoGmro.png') . '" alt="GlobalMROs Logo" style="height: 40px;">',
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar navbar-expand-lg navbar-dark bg-dark',
        'style' => 'background: linear-gradient(90deg, rgba(19,63,98,1) 0%, rgba(75,111,135,1) 100%);',

    ],
]); ?>

<?= Nav::widget([
    'options' => ['class' => 'navbar-nav mr-auto'],
    'items' => [
        ['label' => 'Operator Signup', 'url' => ['/site/become-ao'], 'linkOptions' => ['class' => 'nav-link gradient-text']],

        ['label' => 'MRO Signup', 'url' => ['/site/become-mro'], 'linkOptions' => ['class' => 'nav-link gradient-text' ]],
    ],
]); ?>
<div class="navbar-nav mx-auto text-center" style="position: absolute; left: 50%; transform: translateX(-50%);">
<a class="text-white gradient-text" href="<?= Yii::$app->homeUrl ?>" style="font-size: 2.35rem; font-weight: bold; line-height: 1.2; background: linear-gradient(to right, #BF953F, #FCF6BA, #B38728, #FBF5B7, #AA771C); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
    Global MROs
</a>

</div>

<?= Nav::widget([
    'options' => ['class' => 'navbar-nav'],
    'items' => [
        Yii::$app->user->isGuest ? (
            ['label' => 'Login', 'url' => ['/site/login'], 'linkOptions' => ['class' => 'nav-link gradient-text']]
        ) : (
            ['label' => 'Dashboard', 'url' => ['/dashboard/home'], 'linkOptions' => ['class' => 'nav-link gradient-text']]
        ),
        ['label' => 'Help', 'url' => ['/#help'], 'linkOptions' => ['class' => 'nav-link gradient-text']],
        ['label' => 'About Us', 'url' => ['/#about'], 'linkOptions' => ['class' => 'nav-link gradient-text']],
       
    ],
]); ?>

<?php NavBar::end(); ?>

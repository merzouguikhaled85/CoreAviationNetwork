<?php
/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this); // charge CSS / JS global (dont Bootstrap)

/*
 * PREPARATION DU SUPPORT GLOBAL :
 * le widget est prepare avant head() pour charger correctement ses styles, puis
 * son HTML est affiche apres le formulaire de connexion sans modifier celui-ci.
 */
$supportWidget = $this->render('_support-widget');
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>

    <!--
        Remix Icon est enregistre par le widget Support avant head(). Cette source
        unique evite de telecharger deux fois la meme police sur la page de login.
    -->

    <?php $this->head() ?>
</head>

<body class="login-layout" data-theme="dark">
<?php $this->beginBody() ?>

    <!-- ======= PAGE LOGIN (your view content) ======= -->
    <?= $content ?>
<?= $this->render('can-spinner') ?>

<!-- Bulle Support accessible meme lorsqu'un utilisateur ne peut pas se connecter. -->
<?= $supportWidget ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

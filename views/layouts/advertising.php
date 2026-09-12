<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $content */

/*
 * PREPARATION DU SUPPORT GLOBAL :
 * le partiel est rendu avant head() afin que ses ressources soient disponibles
 * aussi dans ce layout volontairement minimal.
 */
$supportWidget = $this->render('_support-widget');
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<?= $content ?>

<!-- Bulle Support globale de la page publicitaire. -->
<?= $supportWidget ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

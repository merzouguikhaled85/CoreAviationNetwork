<?php
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var string $content */
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
$this->registerMetaTag(['name' => 'robots', 'content' => 'noindex, nofollow']);
$this->registerMetaTag(['name' => 'referrer', 'content' => 'no-referrer']);
$this->registerCsrfMetaTags();
$this->registerCssFile('@web/css/prelaunch-confirmation.css?v=20261107-1');
$this->renderFile('@app/views/layouts/_favicon.php');
?>
<?php $this->beginPage() ?>
<!doctype html>
<html lang="en">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="confirmation-body">
<?php $this->beginBody() ?>
<?= $content ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

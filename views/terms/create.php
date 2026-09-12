<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Terms $model */

$this->title = 'Create Terms';
$this->params['breadcrumbs'][] = ['label' => 'Terms', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="terms-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

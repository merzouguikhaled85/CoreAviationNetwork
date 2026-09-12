<?php

use yii\helpers\Html;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

$this->title = 'Recommend MRO';

?>

<main class="dash-content">
  <div class="container-fluid">
    <h1 class="dash-title"><?= Html::encode($this->title) ?></h1>
    <?php
      if (Yii::$app->session->hasFlash('success')) {
        echo '<div class="alert alert-success">' . Yii::$app->session->getFlash('success') . '</div>';
      }

      if (Yii::$app->session->hasFlash('error')) {
        echo '<div class="alert alert-danger">' . Yii::$app->session->getFlash('error') . '</div>';
      }
    ?>

    <h2>Select MRO Profile</h2>
    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
      <div class="col-lg-6">
        <?= $form->field($model, 'mro_id')->widget(Select2::classname(), [
          'data' => ArrayHelper::map($requests, 'mro_id', 'username'),
          'options' => ['placeholder' => 'Select MRO Profile', 'id' => 'mro-dropdown'],
          'pluginOptions' => ['allowClear' => true],
        ]); ?>
      </div>
    </div>

    <div class="row">
      <div class="form-group col-lg-6">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
      </div>
    </div>

    <?php ActiveForm::end(); ?>
  </div>
</main>

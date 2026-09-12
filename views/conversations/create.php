<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Create New Conversation';
?>

<h1><?= Html::encode($this->title) ?></h1>

<div class="conversation-form can-form-page">
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'subject')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'initial_message')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Start Conversation', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

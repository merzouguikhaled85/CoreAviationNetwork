<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="mro-profile-form">
    <?php $form = ActiveForm::begin(); ?>
    
    <?= $form->field($mroProfile, 'website') ?>
    
    <?= $form->field($mroProfile, 'youtube_video') ?>
    
    <?= $form->field($mroProfile, 'company_photo') ?>
    
    <?= $form->field($mroProfile, 'mainReferencesFiles[]')->fileInput(['multiple' => true]) ?>
    
    <?= $form->field($mroProfile, 'username') ?>
    
    <?= $form->field($mroProfile, 'password')->passwordInput() ?>
    
    <?= $form->field($mroProfile, 'email') ?>
    
    
    <?= $form->field($mroProfile, 'status')->dropDownList(['active' => 'Active', 'banned' => 'Banned', 'hidden' => 'Hidden']) ?>
    
    <?= $form->field($mroProfile, 'first_name') ?>
    
    <?= $form->field($mroProfile, 'last_name') ?>
    
    <?= $form->field($mroProfile, 'contact_number') ?>
    
    <?= $form->field($mroProfile, 'company_name') ?>
    
    <?= $form->field($mroProfile, 'profile_photo') ?>
    
    <?= $form->field($mroProfile, 'email_verified')->checkbox() ?>
    
    <div class="form-group">
        <?= Html::submitButton($mroProfile->isNewRecord ? 'Create' : 'Update', ['class' => 'btn btn-primary']) ?>
    </div>
    
    <?php ActiveForm::end(); ?>
</div>

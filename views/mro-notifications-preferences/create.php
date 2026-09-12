<?php 
use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'Create Notification Preference';
?>

<main class="dash-content can-form-page">
    <div class="container-fluid">
        <h1 class="dash-title">Create Notification Preference</h1>
        <?php
        // Check if there is a success flash message set
        if (Yii::$app->session->hasFlash('success')) {
            echo '<div class="alert alert-success">' . Yii::$app->session->getFlash('success') . '</div>';
        }

        // Check if there is an error flash message set
        if (Yii::$app->session->hasFlash('error')) {
            echo '<div class="alert alert-danger">' . Yii::$app->session->getFlash('error') . '</div>';
        }
        ?>
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_by_email')->checkbox() ?>
            </div>
        </div>
        
        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_by_platform')->checkbox() ?>
            </div>
        </div>
        
        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_by_both')->checkbox() ?>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_for_non_certified_aircraft')->checkbox() ?>
            </div>
        </div>
        
        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_for_certified_aircraft')->checkbox() ?>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_for_appointment_acceptance')->checkbox() ?>
            </div>
        </div>
        
        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_for_feedback')->checkbox() ?>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'mro_id')->hiddenInput(['value' => Yii::$app->user->identity->mro_id])->label(false) ?>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-lg-8">
                <div class="row">
                    <div class="col-lg-6">
                        <?= Html::submitButton('Create Notification Preference', ['class' => 'btn btn-primary']) ?>
                    </div>
                    <div class="col-lg-2">
                        <?= Html::a('Back to Notification Preferences', ['index'], ['class' => 'btn btn-primary']) ?>
                    </div>
                </div>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</main>

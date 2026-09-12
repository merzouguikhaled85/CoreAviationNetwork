<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'Create Notification Preference';
?>

<main class="dash-content can-form-page">
    <div class="container-fluid">
        <h1 class="dash-title">Create Notification Preference</h1>

        <?php
        // Check for success or error flash messages
        if (Yii::$app->session->hasFlash('success')) {
            echo '<div class="alert alert-success">' . Yii::$app->session->getFlash('success') . '</div>';
        }
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
                <?= $form->field($model, 'notify_mro_replies')->checkbox() ?>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_appointment_requests')->checkbox() ?>
            </div>
        </div>
        
        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_po_acceptance')->checkbox() ?>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_maintenance_proposals')->checkbox() ?>
            </div>
        </div>
        
        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_mro_recommendations')->dropDownList(['email' => 'Email', 'message' => 'Message', 'both' => 'Both']) ?>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_work_start')->checkbox() ?>
            </div>
        </div>
        
        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'notify_report_submissions')->checkbox() ?>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($model, 'ao_id')->hiddenInput(['value' => Yii::$app->user->identity->ao_id])->label(false) ?>
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

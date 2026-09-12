<?php

use yii\helpers\Html;

$this->title = 'View Notification Preference: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Notification Preferences', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<main class="dash-content">
    <div class="container-fluid">
        <h1 class="dash-title"><?= Html::encode($this->title) ?></h1>

        <div class="row">
            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>ID:</strong>
                    <?= Html::encode($model->id) ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>MRO ID:</strong>
                    <?= Html::encode($model->mro_id) ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify By Email:</strong>
                    <?= Html::encode($model->notify_by_email ? 'Yes' : 'No') ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify By Platform:</strong>
                    <?= Html::encode($model->notify_by_platform ? 'Yes' : 'No') ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify By Both:</strong>
                    <?= Html::encode($model->notify_by_both ? 'Yes' : 'No') ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify For Certified Aircraft:</strong>
                    <?= Html::encode($model->notify_for_certified_aircraft ? 'Yes' : 'No') ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify For Non-Certified Aircraft:</strong>
                    <?= Html::encode($model->notify_for_non_certified_aircraft ? 'Yes' : 'No') ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify For Appointment Acceptance:</strong>
                    <?= Html::encode($model->notify_for_appointment_acceptance ? 'Yes' : 'No') ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify For Feedback:</strong>
                    <?= Html::encode($model->notify_for_feedback ? 'Yes' : 'No') ?>
                </li>
            </ul>
            <div class="row mt-4">
                <?= Html::a('Back to Notification Preferences', ['index'],  ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>
</main>

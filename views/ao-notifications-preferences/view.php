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
                    <strong>AO ID:</strong>
                    <?= Html::encode($model->ao_id) ?>
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
                    <strong>Notify MRO Replies:</strong>
                    <?= Html::encode($model->notify_mro_replies ? 'Yes' : 'No') ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify Appointment Requests:</strong>
                    <?= Html::encode($model->notify_appointment_requests ? 'Yes' : 'No') ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify PO Acceptance:</strong>
                    <?= Html::encode($model->notify_po_acceptance ? 'Yes' : 'No') ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify Maintenance Proposals:</strong>
                    <?= Html::encode($model->notify_maintenance_proposals ? 'Yes' : 'No') ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify MRO Recommendations:</strong>
                    <?= Html::encode($model->notify_mro_recommendations) ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify Work Start:</strong>
                    <?= Html::encode($model->notify_work_start ? 'Yes' : 'No') ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <strong>Notify Report Submissions:</strong>
                    <?= Html::encode($model->notify_report_submissions ? 'Yes' : 'No') ?>
                </li>
            </ul>
            <div class="row mt-4">
                <?= Html::a('Back to Notification Preferences', ['index'], ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>
</main>

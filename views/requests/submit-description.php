<?php

use yii\helpers\Html;
use app\components\UrlIdHelper;
use yii\widgets\ActiveForm;

$this->title = 'Submit Description';
$this->params['breadcrumbs'][] = ['label' => 'View Reports', 'url' => ['view-reports', 'id' => UrlIdHelper::encode($report->getRequest()->request_id)]];
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>
<style>
    .progress-bar-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    overflow-x: auto; /* Enable horizontal scrolling */
    white-space: nowrap; /* Prevent wrapping */
}

.progress-bar-step {
    flex: 1;
    text-align: center;
    position: relative;
    color: #ccc; /* Color for steps not realized */
    margin-right: 20px; /* Adjust the spacing between steps */

}

.progress-bar-step.step-active {
    color: #007bff; /* Color for step in progress */
}

.progress-bar-step.step-realized {
    color: #28a745; /* Color for realized step */
}

.progress-bar-step:before {
    content: '';
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 100%;
    height: 2px;
    z-index: -1;
}

</style>
<!-- Multi-step progress bar -->
<div class="progress-bar-container">
    <span class="progress-bar-step step-realized">Create a Request</span>
    <span class="progress-bar-step step-realized">MRO Quote</span>
    <span class="progress-bar-step step-realized">PO Loaded</span>
    <span class="progress-bar-step step-realized">PO Accepted By MRO</span>
    <span class="progress-bar-step step-realized">Work Started</span>
    <span class="progress-bar-step step-realized">Report Submitted</span>
    <span class="progress-bar-step step-active">Brief Description Submitted</span>
    <span class="progress-bar-step">New Quote by MRO</span>
    <span class="progress-bar-step">Request Closed</span>
</div>
</br>
<div class="repair-report-form">
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($report, 'AO_description')->textarea(['rows' => 6])->label('Description'. '<span class="text-danger">*</span>') ?>

    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

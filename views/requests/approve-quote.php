<?php

use yii\helpers\Html;
use app\components\UrlIdHelper;
use yii\widgets\ActiveForm;

$this->title = 'Approve CRS';
$this->params['breadcrumbs'][] = ['label' => 'View Reports', 'url' => ['view-reports', 'id' => UrlIdHelper::encode($report->getRequest()->request_id)]];
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<!-- Multi-step progress bar -->
<div class="progress-bar-container">
    <span class="progress-bar-step ">Create a Request</span>
    <span class="progress-bar-step ">MRO Quote</span>
    <span class="progress-bar-step ">PO Loaded</span>
    <span class="progress-bar-step ">PO Accepted By MRO</span>
    <span class="progress-bar-step">Work Started</span>
    <span class="progress-bar-step">MRO Report</span>
    <span class="progress-bar-step step-active">AO Feedback</span>
    <span class="progress-bar-step">Request Closed</span>
</div>
</br>
<div class="approve-quote-form">
    <?php $form = ActiveForm::begin(); ?>

    <p><?= $report->mro_quote ?></p>
    <div class="row">
    <div class="col-md-12">
        <h3>Workpacks Reports</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th class="report-column">Report</th>
                    <th>CRS Attachment</th>
                    <!-- Add more columns as needed -->
                </tr>
            </thead>
            <tbody>
                    <tr>
                        <td><?= Html::encode($report->repair_report_id) ?></td>
                        <td class="report-column"><?= Html::encode($report->report) ?></td>
                        <td>
                            <?php if ($report->CRSed): ?>
                                <?= Html::a('Download',['download', 'id' => UrlIdHelper::encode($report->repair_report_id)]) ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <!-- Add more cells as needed -->
                    </tr>
            </tbody>
        </table>
    </div>
</div>

    <div class="form-group">
        <?= Html::submitButton('Accept', ['class' => 'btn btn-primary', 'name' => 'approve', 'value' => 'yes']) ?>
        <?= Html::submitButton('Request CRS Change', ['class' => 'btn btn-info', 'name' => 'approve', 'value' => 'no']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

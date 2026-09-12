<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $report app\models\RepairReport */
/* @var $mro app\models\MroProfile */

?>

<div class="crs-report-rejected">
    <p>Dear <?= Html::encode($mro->username) ?>,</p>

    <p>We regret to inform you that the CRS report associated with the repair report <?= Html::encode($report->repair_report_id) ?> has been rejected.</p>


    <p>Please review the feedback provided and make necessary adjustments to resubmit the report.</p>

    <p>Thank you for your understanding and cooperation.</p>

    <p>Best regards,</p>
    <p>Your Organization</p>
</div>

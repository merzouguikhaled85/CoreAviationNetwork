<?php

/* @var $this yii\web\View */
/* @var $application app\models\MroRequestApply */

use yii\helpers\Html;

?>

<div class="crs-report-accepted">
    <p>Dear Customer,

</br>We would like to thank you for your prompt response to the operator request. </br>
    However, on this occasion you were not selected to carry out the work. </br>
    Please do pay close attention to your notifications as the operator may still contact you.</br>

    Best regards,</br>
   Global MROs team</p>
</div>



<p>Application Details:</p>
<ul>
    <li><strong>ID:</strong> <?= $application->id ?></li>
    <li><strong>MRO ID:</strong> <?= $application->mro_id ?></li>
    <li><strong>Request ID:</strong> <?= $application->request_id ?></li>
    <!-- Include other application details as needed -->
</ul>


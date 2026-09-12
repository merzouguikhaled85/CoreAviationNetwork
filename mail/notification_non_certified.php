<?php
// notification_non_certified.php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $ao_id string */ // Assuming $ao_id is the AO ID from the session

$companyName = 'Your Company Name'; // Replace with your company name

?>

<div>
<p>Dear customer,

<br>We would like to inform you that a new request for a <?= Html::encode($manufacturer) . ' ' . Html::encode($model) ?> has been submitted, for which you do not hold the appropriate cover. 
<br>
Puse this information for future development and expansion.
<br>
Thank you for your continued support and cooperation.
<br>
<br>
Kind regards,
<br>
<?= Html::encode($companyName) ?>
</p>

</div>

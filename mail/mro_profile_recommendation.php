<?php
/* @var $this yii\web\View */
/* @var $request string */
/* @var $recommendationFrom string */
/* @var $recommendedMro string */
use yii\helpers\Html;

?>

<p>Dear Aircraft Owner,</p>

<p>We have recommended the following MRO profile for your aircraft request:</p>

<ul>
    <li>Request ID: <?= Html::encode($request) ?></li>
    <li>Recommendation From: <?= Html::encode($recommendationFrom) ?></li>
    <li>Recommended MRO: <?= Html::encode($recommendedMro) ?></li>
</ul>

<p>Best Regards,<br>
Your Company Name</p>

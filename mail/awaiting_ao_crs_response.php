<?php
/* @var $this yii\web\View */
/* @var $ao app\models\AoProfile */

use yii\helpers\Html;

?>

<div class="awaiting-ao-crs-response">
    <p>Hello <?= Html::encode($ao->username) ?>,</p>

    <p>This is a notification that we are awaiting your response to the Certificate Release Sheet (CRS).</p>

    <p>Please log in to your account to review and approve the CRS as soon as possible.</p>

    <p>Thank you for your prompt attention to this matter.</p>

    <p>Best regards,</p>
    <p>Your Company Name Team</p>
</div>

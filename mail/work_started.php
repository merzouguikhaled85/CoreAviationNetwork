<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $request app\models\Requests */

$aoName = $request->aO->first_name; // Assuming 'name' is the attribute for AO's name

?>

<div class="work-started-email">
    <p>Dear <?= Html::encode($aoName) ?>,</p>

    <p>We are pleased to inform you that work has been started for the request with ID <?= Html::encode($request->request_id) ?>.</p>

    <p>Thank you for your continued partnership.</p>

    <p>Best regards,<br>
    Your Company Name</p>
</div>

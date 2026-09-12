<?php
use app\models\AoProfile;
use app\models\Requests;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $MroRequestApply app\models\MroRequestApply */

?>
<div class="mro-reply">
    <p>Dear AO,</p>
    
    <p>We have replied to your appointment request with the following details:</p>

    <p><strong>MRO Reply:</strong></p>
    <p><?= nl2br(Html::encode($MroRequestApply->Description)) ?></p>
    
    <p><strong>Quoted Price:</strong></p>
    <p><?= Html::encode($MroRequestApply->price) ?></p>

    <p>Thank you,</p>
    <p><?= Html::encode(Yii::$app->name) ?></p>
</div>

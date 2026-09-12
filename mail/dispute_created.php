<?php
use yii\helpers\Html;

/* @var $dispute app\models\Dispute */

?>

<div class="dispute-created-email">
    <p>Hello <?= Html::encode(Yii::$app->session->get('user_type') == 'ao' ? $dispute->getMro()->one()->username : $dispute->getAo()->one()->username) ?>,</p>

    <?php if (Yii::$app->session->get('user_type') == 'ao'): ?>
        <p>A new dispute has been created:</p>
    <?php else: ?>
        <p>You have received a new dispute:</p>
    <?php endif; ?>

    <ul>
        <li><strong>Dispute ID:</strong> <?= Html::encode($dispute->dispute_id) ?></li>
        <li><strong>Request ID:</strong> <?= Html::encode($dispute->request_id) ?></li>
        <li><strong>Description:</strong> <?= Html::encode($dispute->description) ?></li>
        <li><strong>Created By:</strong> <?= Html::encode($dispute->created_by) ?></li>
    </ul>

    <p>Please log in to your account to view the details and status of this dispute.</p>

    <p>Thank you.</p>
</div>

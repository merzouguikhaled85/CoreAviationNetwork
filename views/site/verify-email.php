<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $token string */

$this->title = 'Email Verification';
?>
<div class="site-verify-email">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Please click the button below to verify your email address:</p>

    <?= Html::a('Verify Email', ['site/verify-email', 'token' => $token], ['class' => 'btn btn-primary']) ?>
</div>

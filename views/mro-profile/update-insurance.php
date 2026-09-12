<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $mroProfile app\models\MroProfile */

$this->title = 'Insurance Document';
?>

<h1><?= Html::encode($this->title) ?></h1>


<div class="mro-profile-update-insurance">

<?php $form = ActiveForm::begin([
    'id' => 'update-insurance-form',
    'options' => ['enctype' => 'multipart/form-data'],
]); ?>

<p>
    Current Insurance Document: 
    <?= Html::a(Html::encode(basename($mroProfile->insurance_document)), '@web/' . $mroProfile->insurance_document, ['target' => '_blank']) ?>
</p>

<?= $form->field($mroProfile, 'insurance_document')->fileInput()->label('Upload New Insurance Document (if any)') ?>

<div class="form-group">
    <?= Html::submitButton('Update', ['class' => 'btn btn-primary']) ?>
</div>

<?php ActiveForm::end(); ?>


</div>

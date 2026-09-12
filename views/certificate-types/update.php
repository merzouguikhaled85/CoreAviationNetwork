<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

$this->title = 'Update Certificate Type';
$this->params['breadcrumbs'][] = ['label' => 'Certificate Types', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<style>
/* CSS for enabling vertical scrolling in Kartik Select2 */

.select2-container--krajee-bs5 .select2-selection--multiple .select2-selection__rendered {
    display: flex;
    flex-wrap: wrap; /* Allow choices to wrap */
    max-height: 100px!important; /* Set a maximum height for the container */
    overflow-y: auto !important; /* Enable vertical scrolling */
}

.select2-container--krajee-bs5 .select2-selection--multiple .select2-selection__choice  {
    white-space: normal !important; /* Allow text to wrap */
    display: inline-block !important; /* Ensure each choice is a block element */
    margin: 2px !important; /* Add some spacing between choices */
    max-width: 100% !important; /* Ensure choices take full width */
}

</style>
<div class="certificate-types-update can-form-page">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'type')->textInput(['maxlength' => true])->label('NAA'. '<span class="text-danger">*</span>') ?>



    <div class="form-group">
        <?= Html::submitButton('Update', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
// Register Select2 assets
$this->registerCss(".select2-container { width: 100% !important; }");


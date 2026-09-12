<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
?>

<?php $this->title ='Update Currency' ?>

<main class="dash-content can-form-page">
    <div class="container-fluid">
        <h1 class="dash-title"><?= Html::encode($this->title) ?></h1>

        <?php $form = ActiveForm::begin() ?>
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, 'name')->textInput(['class' => 'form-control']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, 'code')->textInput(['class' => 'form-control']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, 'symbol')->textInput(['class' => 'form-control']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?= Html::submitButton('Update', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Back to Currencies', ['index'], ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
        <?php ActiveForm::end() ?>
    </div>
</main>

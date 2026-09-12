<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'Update Aircraft Model';
?>

<main class="dash-content can-form-page">
    <div class="container-fluid">
        <h1 class="dash-title">Update Aircraft Model</h1>
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($aircraftModel, 'manufacturer')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="form-group col-lg-6">
                <?= $form->field($aircraftModel, 'model')->textInput(['maxlength' => true]) ?>
            </div>
        </div>

        </div>
        <div class="row">
            <div class="form-group col-lg-8">
                <div class="row">
                    <div class="col-lg-6">
                        <?= Html::submitButton('Update Aircraft Model', ['class' => 'btn btn-primary']) ?>
                    </div>
                    <div class="col-lg-2">
                        <?= Html::a('Back to aircraft models', ['index'], ['class' => 'btn btn-primary']) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php ActiveForm::end(); ?>
    </div>
</main>

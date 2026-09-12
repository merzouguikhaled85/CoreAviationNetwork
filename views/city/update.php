<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'Update City';
?>

<main class="dash-content can-form-page">
    <div class="container-fluid">
        <h1 class="dash-title">Update City</h1>
        <?php $form = ActiveForm::begin(); ?>
        
        <div class="row">
            <div class="form-group">
                <div class="col-lg-6">
                    <?= $form->field($city, 'city_name')->textInput(['maxlength' => true]); ?>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="form-group">
                <div class="col-lg-6">
                    <?= $form->field($city, 'country_id')->dropDownList(
                        ArrayHelper::map($countries, 'country_id', 'country_name'),
                        ['prompt' => 'Select Country']
                    ); ?>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="form-group">
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-6">
                            <?= Html::submitButton('Update City', ['class' => 'btn btn-primary']); ?>
                        </div>
                        <div class="col-lg-2">
                        <?= Html::a('Back to City', ['index'], ['class' => 'btn btn-primary']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php ActiveForm::end(); ?>
    </div>
</main>

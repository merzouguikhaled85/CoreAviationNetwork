<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

?>

<?php $this->title ='GlobaleMRO' ?>

<main class="dash-content can-form-page">
                <div class="container-fluid">
                    <h1 class="dash-title">Update Country</h1>
                    <!-- put your rows / columns here -->
                    <?php 
                        $form = ActiveForm::begin() ?>
                    <div class="row">
                        <div class="form-group">
                            <div class="col-lg-6">
                                <?= $form->field($country, 'country_name');  ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <div class="col-lg-6">
                                <?= $form->field($country, 'country_code');  ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <div class="col-lg-8">
                                <div class="row">
                                <div class="col-lg-6">
                                    <?= Html::submitButton('Update country',['class'=>'btn btn-primary']);  ?>
                                </div>
                                <div class="col-lg-2">
                                <?= Html::a('Back to Country', ['index'], ['class' => 'btn btn-primary']) ?>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php ActiveForm::end() ?>
                </div>
            </main>
        </div>
    </div>
 

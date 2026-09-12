<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

$this->title = 'Update Advert: ' . $advert->advert_id;
?>

<main class="dash-content can-form-page">
    <div class="container-fluid">
        <h1 class="dash-title"><?= Html::encode($this->title) ?></h1>
        
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>
        
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($advert, 'admin_id')->dropDownList($adminList, ['prompt' => 'Select Admin']) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($advert, 'advert_type')->dropDownList(['photo' => 'Photo', 'video' => 'Video'], ['prompt' => 'Select Advert Type']) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($advert, 'use_url')->checkbox(['id' => 'use-url-checkbox']) ?>
            </div>
        </div>

        <div id="file-input" style="<?= ($advert->use_url == 1) ? 'display: none;' : '' ?>">
            <div class="row">
                <div class="col-lg-6">
                    <?= $form->field($advert, 'content')->fileInput() ?>
                </div>
            </div>
        </div>

        <div id="url-input" style="<?= ($advert->use_url == 1) ? '' : 'display: none;' ?>">
            <div class="row">
                <div class="col-lg-6">
                    <?= $form->field($advert, 'url')->textInput(['placeholder' => 'Enter URL']) ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($advert, 'start_date')->widget(DatePicker::className(), [
                    'dateFormat' => 'yyyy-MM-dd',
                    'clientOptions' => [
                        'autoclose' => true,
                        'todayHighlight' => true
                    ],
                    'options' => ['class' => 'form-control']
                ]); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($advert, 'end_date')->widget(DatePicker::className(), [
                    'dateFormat' => 'yyyy-MM-dd',
                    'clientOptions' => [
                        'autoclose' => true,
                        'todayHighlight' => true
                    ],
                    'options' => ['class' => 'form-control']
                ]); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($advert, 'status')->dropDownList(['active' => 'Active', 'inactive' => 'Inactive'], ['prompt' => 'Select Status']) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <?= Html::submitButton('Update Advert', ['class' => 'btn btn-primary']) ?>
            </div>
        </div>

        <?php ActiveForm::end() ?>
    </div>
</main>

<?php
$script = <<< JS
$(document).ready(function(){
    // Function to show/hide file input or URL input based on checkbox status
    function toggleInputs() {
        if($('#use-url-checkbox').is(":checked")) {
            $('#file-input').hide();
            $('#url-input').show();
        } else {
            $('#file-input').show();
            $('#url-input').hide();
        }
    }
    
    // Call the function when the page loads
    toggleInputs();

    // Call the function whenever the checkbox status changes
    $('#use-url-checkbox').change(toggleInputs);
});
JS;
$this->registerJs($script);
?>

<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'Update Aircraft';
?>

<main class="dash-content can-form-page">
    <div class="container-fluid">
        <h1 class="dash-title"><?= Html::encode($this->title) ?></h1>
        <?php $form = ActiveForm::begin() ?>

        <div class="row">
            <div class="form-group">
                <div class="col-lg-6">
                <?= $form->field($aircraftModel, 'manufacturer')->dropDownList(
    \yii\helpers\ArrayHelper::map($manufacturers, 'manufacturer', 'manufacturer'),
    [
        'prompt' => 'Select Manufacturer',
        'id' => 'manufacturer-dropdown', // Corrected ID
        'class' => 'form-select form-select-lg',
        'onchange' => 'updateModelDropdown(this.value)', // Call JavaScript function on change

    ]
) ?>                </div>
            </div>
        </div>
       

        <div class="row">
            <div class="form-group">
                <div class="col-lg-6">
                <?= $form->field($aircraftModel, 'model')->dropDownList(
    [], // Preload empty options, to be populated dynamically
    [
        'prompt' => 'Select Model',
        'id' => 'model-dropdown', // Corrected ID
        'class' => 'form-select form-select-lg'
    ])->label('Model') ?>                </div>
            </div>
        </div>


        <div class="row">
            <div class="form-group">
                <div class="col-lg-6">
                    <?= $form->field($aircraftModel, 'serial_number') ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <div class="col-lg-6">
                    <?= $form->field($aircraftModel, 'registration_number') ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <div class="col-lg-6">
                    <?= $form->field($aircraftModel, 'ao_id')->dropDownList(
                        \yii\helpers\ArrayHelper::map($owners, 'ao_id', 'username'),
                        ['prompt' => 'Select Owner']
                    ) ?>
        <?= $form->field($aircraftModel, 'aircraft_model_id')->hiddenInput(['id' => 'aircraft-model-id'])->label(false) ?>


                </div>
            </div>
        </div>

        <!-- Rest of the form fields -->
        <div class="row">
            <div class="form-group">
                <div class="col-lg-8">
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
        </div>
       

        <?php ActiveForm::end() ?>
    </div>
</main>
<script>
function updateModelDropdown(selectedManufacturer) {
    // Get the model dropdown element
    var modelDropdown = document.getElementById('model-dropdown');
    var aircraftModelIdInput = document.getElementById('aircraft-model-id');

    // Clear existing options
    modelDropdown.innerHTML = '';

    // Populate model dropdown with models corresponding to the selected manufacturer
    <?php foreach ($models as $model): ?>
        if (selectedManufacturer === '<?= $model->manufacturer ?>') {
            var option = document.createElement('option');
            option.value = '<?= $model->model ?>'; // Use appropriate attribute as the option value
            option.text = '<?= $model->model ?>'; // Use appropriate attribute as the option text
            modelDropdown.appendChild(option);
            aircraftModelIdInput.value = '<?= $model->aircraft_model_id ?>';
        }
    <?php endforeach; ?>
}
</script>


<?php
// Register JavaScript to handle dynamic dropdown update
$script = <<<JS
$(document).ready(function(){
    $('#manufacturer-select').change(function(){
        var selectedManufacturer = $(this).val(); // Get the selected manufacturer
        $('#model-select option').each(function(){
            if ($(this).data('manufacturer') === selectedManufacturer || $(this).val() === '') {
                $(this).show(); // Show options matching the selected manufacturer or the default prompt
            } else {
                $(this).hide(); // Hide options not matching the selected manufacturer
            }
        });
    });
});
JS;

// Register the JavaScript code block
$this->registerJs($script);

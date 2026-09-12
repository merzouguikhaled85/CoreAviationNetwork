<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'Update Certificate';
?>

<main class="dash-content can-form-page">
    <div class="container-fluid">
        <h1 class="dash-title">Update Certificate</h1>
        <?php
        // Check if there is a success flash message set
        if (Yii::$app->session->hasFlash('success')) {
            echo '<div class="alert alert-success">' . Yii::$app->session->getFlash('success') . '</div>';
        }

        // Check if there is an error flash message set
        if (Yii::$app->session->hasFlash('error')) {
            echo '<div class="alert alert-danger">' . Yii::$app->session->getFlash('error') . '</div>';
        }
        ?>
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); // Enable file uploads ?>

        <div class="row">
            <div class="form-group">
                <div class="col-lg-6">
                <?= $form->field($certificate, 'certificate')->fileInput(['maxlength' => true, 'accept' => '.pdf, .doc, .docx'])->label($certificate->getAttributeLabel('certificate') . '<span class="text-danger">*</span>') ?>
                </div>
            </div>
        </div>
        <div class="row">
    <div class="form-group">
        <div class="col-lg-6">
            <?= $form->field($certificate, 'type')->dropDownList(
                ArrayHelper::map(\app\models\CertificateTypes::find()->orderBy(['type' => SORT_ASC])->all(), 'type', 'type'), // Assuming 'type' is the column in CertificateTypes you want to display
                [
                    'prompt' => 'Select Type',
                    'id' => 'certificate-type-dropdown',
                ]
            )->label($certificate->getAttributeLabel('NAA') . '<span class="text-danger">*</span>') ?>
        </div>
    </div>
</div>



        <?= $form->field($certificate, 'certificate_type_id')->hiddenInput()->label(false) ?>



        <div class="row">
            <div class="form-group">
                <div class="col-lg-6">
                <?= $form->field($certificate, 'mro_id')->dropDownList(
                    ArrayHelper::map($mros, 'mro_id', 'username'),
                    ['prompt' => 'Select MRO']
                )->label( 'Select MRO' . '<span class="text-danger">*</span>') ?>
                </div>
            </div>
        </div>

      

        <div class="row">
            <div class="form-group col-lg-8">
                <div class="row">
                    <div class="col-lg-6">
                        <?= Html::submitButton('Update Certificate', ['class' => 'btn btn-primary']) ?>
                    </div>
                    <div class="col-lg-2">
                        <?= Html::a('Back to certificates', ['index'], ['class' => 'btn btn-primary']) ?>
                    </div>
                </div>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
        <?php
$this->registerJs("
    $('#certificate-type-dropdown').change(function() {
        var selectedType = $(this).val();
        $.ajax({
            url: '" . \yii\helpers\Url::to(['certificate-types/get-certificate-type-id']) . "',
            type: 'GET',
            data: {type: selectedType},
            success: function(data) {
                $('#certificates-certificate_type_id').val(data.certificate_type_id); // Set 'certificate_type_id' field value
            },
            error: function() {
                alert('Error fetching certificate type ID.');
            }
        });
    });
");
?>

    

    </div>
</main>

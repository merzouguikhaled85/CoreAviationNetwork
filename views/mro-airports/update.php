<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;

$this->title = 'Update MRO Airports';
?>

<main class="dash-content can-form-page">
    <div class="container-fluid">
        <h1 class="dash-title"><?= Html::encode($this->title) ?></h1>
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
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, 'airport_id')->widget(Select2::classname(), [
                    'data' => ArrayHelper::map(\app\models\Airports::find()->all(), 'airport_id', 'airport_name'),
                    'options' => ['placeholder' => 'Select Airport'],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                ]); ?>
            </div>
        </div>


        <div class="row">
            <div class="form-group col-lg-6">
                <?= Html::submitButton('Update', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Cancel', Yii::$app->request->referrer, [ 'class' => 'btn btn-secondary']) ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</main>

<?php
use yii\helpers\Html;
?>
<?= Html::dropDownList('city_id', null, \yii\helpers\ArrayHelper::map($cities, 'id', 'name'), ['class' => 'form-control']) ?>

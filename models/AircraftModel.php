<?php

namespace app\models;

use Yii;

class AircraftModel extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'aircraft_model';
    }

    public function rules()
    {
        return [
            [['manufacturer', 'model'], 'required'],
            [['manufacturer', 'model'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'aircraft_model_id' => 'Aircraft Model ID',
            'manufacturer' => 'Manufacturer',
            'model' => 'Model',
        ];
    }
}

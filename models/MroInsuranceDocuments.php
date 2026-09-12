<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "mro_insurance_documents".
 */
class MroInsuranceDocuments extends ActiveRecord
{
    public static function tableName()
    {
        return 'mro_insurance_documents';
    }

    public function rules()
    {
        return [
            [['mro_id', 'file_path'], 'required'],
            [['mro_id', 'file_size'], 'integer'],
            [['created_at'], 'safe'],

            [['file_path', 'file_name'], 'string', 'max' => 255],
            [['file_type'], 'string', 'max' => 50],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mro_id' => 'MRO ID',
            'file_path' => 'File Path',
            'file_name' => 'File Name',
            'file_type' => 'File Type',
            'file_size' => 'File Size',
            'created_at' => 'Created At',
        ];
    }

    public function getMroProfile()
    {
        return $this->hasOne(MroProfile::class, ['mro_id' => 'mro_id']);
    }
}
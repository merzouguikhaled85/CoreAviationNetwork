<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "certificate_types".
 *
 * @property int $certificate_type_id
 * @property string $type
 * @property string|null $aircraft_model_ids
 */
class CertificateTypes extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'certificate_types';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type'], 'required'],
            [['type'], 'string', 'max' => 255],
            [['type'], 'unique'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'certificate_type_id' => 'Certificate Type ID',
            'type' => 'Type',
            'aircraft_model_ids' => 'Aircraft Model IDs',
        ];
    }


    /**
     * Gets the related certificates.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCertificates()
    {
        return $this->hasMany(Certificates::class, ['certificate_type_id' => 'certificate_type_id']);
    }

    /**
     * Gets the related aircraft models.
     *
     * @return \yii\db\ActiveQuery
     */

}

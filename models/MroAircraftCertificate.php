<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "mro_aircraft_certificate".
 *
 * @property int $aircraft_certificate_id
 * @property int $mro_id
 * @property int $aircraft_model_id
 * @property string $certificate
 *
 * @property MroProfile $mro
 * @property AircraftModel $aircraftModel
 */
class MroAircraftCertificate extends \yii\db\ActiveRecord
{
    public $manufacturer;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mro_aircraft_certificate';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mro_id', 'aircraft_model_id'], 'integer'],
            [['certificate'], 'string', 'max' => 255],
            [['mro_id'], 'exist', 'skipOnError' => true, 'targetClass' => MroProfile::className(), 'targetAttribute' => ['mro_id' => 'mro_id']],
            [['aircraft_model_id'], 'exist', 'skipOnError' => true, 'targetClass' => AircraftModel::className(), 'targetAttribute' => ['aircraft_model_id' => 'aircraft_model_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'aircraft_certificate_id' => 'Aircraft Certificate ID',
            'mro_id' => 'MRO ID',
            'aircraft_model_id' => 'Aircraft Model ID',
            'certificate' => 'Certificate',
        ];
    }

    /**
     * Gets the MRO profile associated with this certificate.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMro()
    {
        return $this->hasOne(MroProfile::className(), ['mro_id' => 'mro_id']);
    }

    /**
     * Gets the aircraft model associated with this certificate.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAircraftModel()
    {
        return $this->hasOne(AircraftModel::className(), ['aircraft_model_id' => 'aircraft_model_id']);
    }
}

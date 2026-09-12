<?php

namespace app\models;

use Yii;

class Aircrafts extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aircrafts';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['manufacturer', 'model', 'serial_number', 'registration_number', 'certificate_type_id'], 'required'], // certificate_type_id is required
            [['ao_id', 'aircraft_model_id', 'certificate_type_id'], 'integer'], // certificate_type_id as integer
            [['manufacturer', 'model', 'serial_number', 'registration_number'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'aircraft_id' => 'Aircraft ID',
            'manufacturer' => 'Manufacturer',
            'model' => 'Model',
            'serial_number' => 'Serial Number',
            'registration_number' => 'Registration Number',
            'ao_id' => 'AO ID',
            'aircraft_model_id' => 'Aircraft Model ID',
            'certificate_type_id' => 'Certificate Type', // Add label for certificate_type_id
        ];
    }

    /**
     * Define a relationship with the AoProfile model to retrieve owner information.
     */
    public function getOwner()
    {
        return $this->hasOne(AoProfile::class, ['ao_id' => 'ao_id']);
    }

    /**
     * Define a relationship with the AircraftModel model to retrieve aircraft model information.
     */
    public function getAircraftModel()
    {
        return $this->hasOne(AircraftModel::class, ['aircraft_model_id' => 'aircraft_model_id']);
    }

    /**
     * Define a relationship with the CertificateType model.
     */
    public function getCertificateType()
    {
        return $this->hasOne(CertificateTypes::class, ['certificate_type_id' => 'certificate_type_id']);
    }
    /**
 * Validate model belongs to manufacturer
 */
public function validateModelManufacturer($attribute)
{
    $exists = \app\models\AircraftModel::find()
        ->where([
            'aircraft_model_id' => $this->$attribute,
            'manufacturer' => $this->manufacturer
        ])
        ->exists();

    if (!$exists) {
        $this->addError($attribute, 'Invalid aircraft model for selected manufacturer.');
    }
}
}

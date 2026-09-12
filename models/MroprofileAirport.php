<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class MroprofileAirport extends ActiveRecord
{
    public $country_id;
    public $city_id;
public $airport_id_icao;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mroprofile_airport';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mro_id', 'airport_id'], 'required'],
            [['mro_id', 'airport_id'], 'integer'],
            [['mro_id'], 'exist', 'skipOnError' => true, 'targetClass' => MroProfile::className(), 'targetAttribute' => ['mro_id' => 'mro_id']],
            [['airport_id'], 'exist', 'skipOnError' => true, 'targetClass' => Airports::className(), 'targetAttribute' => ['airport_id' => 'airport_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mro_id' => 'MRO ID',
            'airport_id' => 'Airport ID',
        ];
    }

    /**
     * Gets query for [[Mro]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMro()
    {
        return $this->hasOne(MroProfile::className(), ['mro_id' => 'mro_id']);
    }

    /**
     * Gets query for [[Airport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAirport()
    {
        return $this->hasOne(Airports::className(), ['airport_id' => 'airport_id']);
    }

    /**
     * Gets the country associated with the airport.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Countries::className(), ['country_id' => 'country_id'])->via('airport');
    }

    /**
     * Gets the city associated with the airport.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(Cities::className(), ['city_id' => 'city_id'])->via('airport');
    }
}

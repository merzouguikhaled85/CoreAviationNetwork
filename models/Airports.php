<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "airports".
 *
 * @property int $airport_id
 * @property string $airport_name
 * @property int|null $city_id
 * @property string|null $city_name
 * @property string|null $country_name
 * @property string|null $icao
 * @property int|null $country_id
 *
 * @property Cities $city
 * @property Countries $country
 */
class Airports extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'airports';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['airport_name'], 'required'],
            [['city_id', 'country_id'], 'integer'],
            [['airport_name', 'icao'], 'string', 'max' => 255],
            [['city_name', 'country_name'], 'string', 'max' => 255],
            [['city_id'], 'exist', 'skipOnError' => true, 'targetClass' => Cities::className(), 'targetAttribute' => ['city_id' => 'city_id']],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Countries::className(), 'targetAttribute' => ['country_id' => 'country_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'airport_id' => 'Airport ID',
            'airport_name' => 'Airport Name',
            'city_id' => 'City ID',
            'city_name' => 'City Name',
            'country_name' => 'Country Name',
            'icao' => 'ICAO Code',
            'country_id' => 'Country ID',
        ];
    }

    /**
     * Gets query for [[City]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(Cities::className(), ['city_id' => 'city_id']);
    }

    /**
     * Gets query for [[Country]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Countries::className(), ['country_id' => 'country_id']);
    }
}

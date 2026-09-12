<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "mro_request_apply".
 *
 * @property int $id
 * @property int $mro_id
 * @property int $request_id
 * @property string $Description
 * @property float $price
 * @property string $currency
* @property string|null $attachment

 */
class MroRequestApply extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mro_request_apply';
    }

    /**
     * SYNCHRONISATION DES DEVIS MRO : observe la creation, le remplacement et
     * l'annulation d'une proposition. request_id est deja porte par ce modele,
     * ce qui permet de prevenir exactement les listes de la demande concernee.
     */
    public function behaviors()
    {
        return [
            'requestChange' => [
                'class' => \app\components\RequestChangeBehavior::class,
                'requestIdAttribute' => 'request_id',
                'eventPrefix' => 'application',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mro_id', 'request_id', 'Description', 'price', 'currency'], 'required'],
            [['mro_id', 'request_id'], 'integer'],
            [['Description'], 'string'],
            [['price'], 'number'],
            [['currency'], 'string', 'max' => 3],
            [['attachment'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, pdf', 'maxSize' => 1024 * 1024 * 10], // Example validation for file uploads

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
            'request_id' => 'Request ID',
            'Description' => 'Description',
            'price' => 'Price',
            'currency' => 'Currency',
            'attachment' => 'Attachment',

        ];
    }



    /**
     * Gets query for [[Mro]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMro()
    {
        return $this->hasOne(MroProfile::className(), ['mro_id' => 'mro_id'])->one();
    }

    /**
     * Gets query for [[Request]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequest()
    {
        return $this->hasOne(Requests::className(), ['request_id' => 'request_id']);
    }
}

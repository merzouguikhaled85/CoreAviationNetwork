<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ao_requests_applications".
 *
 * @property int $id
 * @property int $request_id
 * @property int $application_id
 * @property string|null $po
 *
 * @property Requests $request
 * @property MroRequestApply $application
 */
class AoRequestsApplications extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ao_requests_applications';
    }

    /**
     * SYNCHRONISATION DES PURCHASE ORDERS : toute creation ou modification du
     * fichier PO peut changer le statut affiche et les boutons disponibles pour
     * l'AO et le MRO. Le comportement publie uniquement l'identifiant concerne.
     */
    public function behaviors()
    {
        return [
            'requestChange' => [
                'class' => \app\components\RequestChangeBehavior::class,
                'requestIdAttribute' => 'request_id',
                'eventPrefix' => 'po',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['request_id', 'application_id'], 'required'],
            [['request_id', 'application_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],

            [['request_id'], 'exist', 'skipOnError' => true, 'targetClass' => Requests::className(), 'targetAttribute' => ['request_id' => 'request_id']],
            [['application_id'], 'exist', 'skipOnError' => true, 'targetClass' => MroRequestApply::className(), 'targetAttribute' => ['application_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'request_id' => 'Request ID',
            'application_id' => 'Application ID',
            'po' => 'PO',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
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

    /**
     * Gets query for [[Application]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getApplication()
    {
        return $this->hasOne(MroRequestApply::className(), ['id' => 'application_id']);
    }
}

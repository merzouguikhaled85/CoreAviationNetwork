<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Chat extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat';
    }

    /**
     * SYNCHRONISATION DES CANAUX DE DISCUSSION : la creation ou suppression d'un
     * chat rend visible ou retire une conversation dans la liste. Le comportement
     * reutilise le request_id deja enregistre sur le canal.
     */
    public function behaviors()
    {
        return [
            'requestChange' => [
                'class' => \app\components\RequestChangeBehavior::class,
                'requestIdAttribute' => 'request_id',
                'eventPrefix' => 'chat',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mro_id', 'ao_id'], 'required'],
            [['mro_id', 'ao_id', 'request_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'chat_id' => 'Chat ID',
            'mro_id' => 'MRO ID',
            'ao_id' => 'AO ID',
            'request_id' => 'Request ID',
        ];
    }
}

<?php

namespace app\models;
use app\models\MroProfile;
use app\models\AoProfile;
use Yii;

class Conversations extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conversations';
    }

    /**
     * SYNCHRONISATION DES MESSAGES : un nouveau message peut modifier la date,
     * l'aperçu et l'ordre de la liste des conversations. Aucun texte du message
     * n'est copie dans le journal technique afin de proteger sa confidentialite.
     */
    public function behaviors()
    {
        return [
            'requestChange' => [
                'class' => \app\components\RequestChangeBehavior::class,
                'requestIdAttribute' => 'request_id',
                'eventPrefix' => 'conversation',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sender_id', 'sender_type', 'receiver_id', 'receiver_type'], 'required'],
            [['sender_id', 'receiver_id', 'request_id'], 'integer'],
            [['sender_type', 'receiver_type'], 'string', 'max' => 255],
            [['message'], 'string'],
            [['message'], 'safe'],
            [['timestamp'], 'safe'],
        ];
    }

    /**
     * Define a relation with MroProfile.
     */
    public function getSender()
    {
        if ($this->sender_type === 'mro') {
            return $this->hasOne(MroProfile::class, ['mro_id' => 'sender_id']);
        } elseif ($this->sender_type === 'ao') {
            return $this->hasOne(AoProfile::class, ['ao_id' => 'sender_id']);
        } else {
            // Handle the case where sender_type is neither 'mro' nor 'ao'
            // You can return a default value or handle the situation according to your application logic
            return null;
        }
    }

    /**
     * Define a relation with AoProfile.
     */
    public function getReceiver()
    {
        if ($this->receiver_type === 'mro') {
            return $this->hasOne(MroProfile::class, ['mro_id' => 'receiver_id']);
        } elseif ($this->receiver_type === 'ao') {
            return $this->hasOne(AoProfile::class, ['ao_id' => 'receiver_id']);
        } else {
            // Handle the case where receiver_type is neither 'mro' nor 'ao'
            // You can return a default value or handle the situation according to your application logic
            return null;
        }
    }

    /**
     * Define a relation with Requests.
     */
    public function getRequest()
    {
        return $this->hasOne(Requests::class, ['request_id' => 'request_id']);
    }
}

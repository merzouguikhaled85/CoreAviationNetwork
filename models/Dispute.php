<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "disputes".
 *
 * @property int $dispute_id
 * @property int|null $request_id
 * @property string $description
 * @property string $status
 * @property string $timestamp
 * @property int $ao_id
 * @property int $mro_id
 * @property string $po
 * @property string $created_by
 * @property string|null $admin_response
 *
 * @property AoProfile $ao
 * @property MroProfile $mro
 * @property AoRequestsApplications $aoRequest
 * @property Requests $request
 */
class Dispute extends \yii\db\ActiveRecord
{
    public $ao_username;
    public $mro_username;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'disputes';
    }

    /**
     * SYNCHRONISATION DES LITIGES : creation, reponse administrative, resolution
     * et suppression peuvent modifier les listes AO, MRO et administrateur. Les
     * descriptions et reponses ne sont jamais dupliquees dans le journal.
     */
    public function behaviors()
    {
        return [
            'requestChange' => [
                'class' => \app\components\RequestChangeBehavior::class,
                'requestIdAttribute' => 'request_id',
                'eventPrefix' => 'dispute',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description', 'ao_id', 'mro_id', 'po', 'created_by'], 'required'],
            [['request_id', 'ao_id', 'mro_id'], 'integer'],
            [['description', 'admin_response'], 'string'],
            [['status'], 'string'],
            [['timestamp'], 'safe'],
            [['po'], 'string', 'max' => 255],
            [['status'], 'in', 'range' => ['open', 'resolved']],
            [['ao_id'], 'exist', 'skipOnError' => true, 'targetClass' => AoProfile::class, 'targetAttribute' => ['ao_id' => 'ao_id']],
            [['mro_id'], 'exist', 'skipOnError' => true, 'targetClass' => MroProfile::class, 'targetAttribute' => ['mro_id' => 'mro_id']],
            [['created_by'], 'in', 'range' => ['ao', 'mro']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'dispute_id' => 'Dispute ID',
            'request_id' => 'Request ID',
            'description' => 'Description',
            'status' => 'Status',
            'timestamp' => 'Timestamp',
            'ao_id' => 'AO ID',
            'mro_id' => 'MRO ID',
            'po' => 'PO',
            'created_by' => 'Created By',
            'admin_response' => 'Admin Response',
        ];
    }

    /**
     * Gets query for [[Ao]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAo()
    {
        return $this->hasOne(AoProfile::class, ['ao_id' => 'ao_id']);
    }

    /**
     * Gets query for [[Mro]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMro()
    {
        return $this->hasOne(MroProfile::class, ['mro_id' => 'mro_id']);
    }

    /**
     * Gets query for [[AoRequest]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAoRequest()
    {
        return $this->hasOne(AoRequestsApplications::class, ['request_id' => 'request_id']);
    }

    /**
     * Gets query for [[Request]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequest()
    {
        return $this->hasOne(Requests::class, ['request_id' => 'request_id']);
    }
}

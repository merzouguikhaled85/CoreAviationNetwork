<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "feedback".
 *
 * @property int $feedback_id
 * @property int|null $request_id
 * @property int|null $ao_id
 * @property int|null $mro_id
 * @property int|null $rating
 * @property int|null $kept_to_agreed_schedule_rating
 * @property int|null $kept_to_agreed_cost_rating
 * @property int|null $overall_communication_rating
 * @property string|null $feedback_text
 * @property string $timestamp
 */
class Feedback extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feedback';
    }

    /**
     * SYNCHRONISATION DES EVALUATIONS : le depot ou la correction d'un feedback
     * change les actions accessibles sur une demande fermee. Les notes et le
     * commentaire restent dans feedback et ne sont pas exposes dans l'evenement.
     */
    public function behaviors()
    {
        return [
            'requestChange' => [
                'class' => \app\components\RequestChangeBehavior::class,
                'requestIdAttribute' => 'request_id',
                'eventPrefix' => 'feedback',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['request_id', 'ao_id', 'mro_id','rating', 'kept_to_agreed_schedule_rating', 'kept_to_agreed_cost_rating', 'overall_communication_rating'], 'integer'],
            [['feedback_text'], 'string'],
            [['timestamp'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'feedback_id' => 'Feedback ID',
            'request_id' => 'Request ID',
            'ao_id' => 'AO ID',
            'mro_id' => 'MRO ID',
            'rating' => 'Rating',
            'kept_to_agreed_schedule_rating' => 'Kept to agreed schedule rating',
            'kept_to_agreed_cost_rating' => 'Kept to agreed cost rating',
            'overall_communication_rating' => 'Overall communication rating',
            'feedback_text' => 'Feedback Text',
            'timestamp' => 'Timestamp',
        ];
    }
}

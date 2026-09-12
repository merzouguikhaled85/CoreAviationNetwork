<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Appointment extends ActiveRecord
{
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_WAITING_RESPONSE = 'waiting response';
    const STATUS_CANCELED = 'canceled';
    const STATUS_RESCHEDULE = 'reschedule'; // Added new status

    public static function tableName()
    {
        return 'appointment';
    }

    /**
     * SYNCHRONISATION DES RENDEZ-VOUS : confirmation, annulation et changement
     * de date sont publies apres sauvegarde. Les calendriers AO et MRO peuvent
     * ainsi se mettre a jour a partir du meme evenement lie a request_id.
     */
    public function behaviors()
    {
        return [
            'requestChange' => [
                'class' => \app\components\RequestChangeBehavior::class,
                'requestIdAttribute' => 'request_id',
                'eventPrefix' => 'appointment',
            ],
        ];
    }

    public function rules()
    {
        return [
            [['appointment_date', 'mro_id', 'ao_id', 'request_id', 'ao_requests_applications_id', 'status'], 'required'],
            [['appointment_date', 'reschedule_appointment_date'], 'safe'], // Include 'reschedule_appointment_date' in safe rule
            [['mro_id', 'ao_id', 'request_id', 'ao_requests_applications_id'], 'integer'],
            [['status'], 'in', 'range' => [self::STATUS_CONFIRMED, self::STATUS_WAITING_RESPONSE, self::STATUS_CANCELED, self::STATUS_RESCHEDULE]], // Update 'status' validation to include new status

        ];
    }

    public function attributeLabels()
    {
        return [
            'appointment_date' => 'Appointment Date',
            'mro_id' => 'MRO ID',
            'ao_id' => 'AO ID',
            'request_id' => 'Request ID',
            'ao_requests_applications_id' => 'AO Requests Applications ID',
            'status' => 'Status',
            'reschedule_appointment_date' => 'Reschedule Appointment Date', // Add label for new column

        ];
    }
}

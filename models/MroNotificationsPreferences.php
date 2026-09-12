<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class MroNotificationsPreferences extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mro_notifications_preferences';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mro_id'], 'required'],
            [['mro_id'], 'integer'],
            [
                [
                    'notify_by_email', 
                    'notify_by_platform', 
                    'notify_by_both', 
                    'notify_for_non_certified_aircraft', 
                    'notify_for_certified_aircraft', 
                    'notify_for_appointment_acceptance', 
                    'notify_for_feedback'
                ], 
                'boolean'
            ],
            [['mro_id'], 'exist', 'skipOnError' => true, 'targetClass' => MroProfile::className(), 'targetAttribute' => ['mro_id' => 'mro_id']],
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
            'notify_by_email' => 'Notify by Email',
            'notify_by_platform' => 'Notify by Platform',
            'notify_by_both' => 'Notify by Both',
            'notify_for_non_certified_aircraft' => 'Notify for Non-Certified Aircraft',
            'notify_for_certified_aircraft' => 'Notify for Certified Aircraft',
            'notify_for_appointment_acceptance' => 'Notify for Appointment Acceptance',
            'notify_for_feedback' => 'Notify for Feedback',
        ];
    }

    /**
     * Gets query for [[MroProfile]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMroProfile()
    {
        return $this->hasOne(MroProfile::className(), ['mro_id' => 'mro_id']);
    }
}

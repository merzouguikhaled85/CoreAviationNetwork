<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "ao_notifications_preferences".
 *
 * @property int $id
 * @property int $ao_id
 * @property int $notify_by_email
 * @property int $notify_by_platform
 * @property int $notify_mro_replies
 * @property int $notify_appointment_requests
 * @property int $notify_po_acceptance
 * @property int $notify_maintenance_proposals
 * @property string $notify_mro_recommendations
 * @property int $notify_work_start
 * @property int $notify_report_submissions
 *
 * @property AoProfile $aoProfile
 */
class AoNotificationsPreferences extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ao_notifications_preferences';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ao_id', 'notify_by_email', 'notify_by_platform', 'notify_mro_replies', 'notify_appointment_requests', 'notify_po_acceptance', 'notify_maintenance_proposals', 'notify_work_start', 'notify_report_submissions'], 'required'],
            [['ao_id', 'notify_by_email', 'notify_by_platform', 'notify_mro_replies', 'notify_appointment_requests', 'notify_po_acceptance', 'notify_maintenance_proposals', 'notify_work_start', 'notify_report_submissions'], 'integer'],
            [['notify_mro_recommendations'], 'string', 'max' => 10],
            [['ao_id'], 'exist', 'skipOnError' => true, 'targetClass' => AoProfile::className(), 'targetAttribute' => ['ao_id' => 'ao_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ao_id' => 'AO ID',
            'notify_by_email' => 'Notify by Email',
            'notify_by_platform' => 'Notify by Platform',
            'notify_mro_replies' => 'Notify MRO Replies',
            'notify_appointment_requests' => 'Notify Appointment Requests',
            'notify_po_acceptance' => 'Notify PO Acceptance',
            'notify_maintenance_proposals' => 'Notify Maintenance Proposals',
            'notify_mro_recommendations' => 'Notify MRO Recommendations',
            'notify_work_start' => 'Notify Work Start',
            'notify_report_submissions' => 'Notify Report Submissions',
        ];
    }

    /**
     * Gets query for [[AoProfile]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAoProfile()
    {
        return $this->hasOne(AoProfile::className(), ['ao_id' => 'ao_id']);
    }
}

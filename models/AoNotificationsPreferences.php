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

    /**
     * Valeurs par défaut : toutes les notifications activées (2026-11-22).
     * Utilisées à l'inscription (AoProfile::afterSave) et à la première ouverture
     * de la page des préférences.
     */
    public const DEFAULTS = [
        'notify_by_email' => 1,
        'notify_by_platform' => 1,
        'notify_mro_replies' => 1,
        'notify_appointment_requests' => 1,
        'notify_po_acceptance' => 1,
        'notify_maintenance_proposals' => 1,
        'notify_mro_recommendations' => '1',
        'notify_work_start' => 1,
        'notify_report_submissions' => 1,
    ];

    /**
     * Retourne les préférences de l'AO, en les créant avec les valeurs par défaut
     * si elles n'existent pas encore. Ne modifie jamais des préférences existantes.
     */
    public static function ensureDefaultsFor(int $aoId): ?self
    {
        if ($aoId <= 0) {
            return null;
        }

        $preferences = static::findOne(['ao_id' => $aoId]);
        if ($preferences !== null) {
            return $preferences;
        }

        $preferences = new static();
        $preferences->ao_id = $aoId;
        $preferences->setAttributes(self::DEFAULTS, false);

        if (!$preferences->save()) {
            Yii::error('Préférences AO par défaut non créées : ' . json_encode($preferences->errors), __METHOD__);
            return null;
        }

        return $preferences;
    }
}

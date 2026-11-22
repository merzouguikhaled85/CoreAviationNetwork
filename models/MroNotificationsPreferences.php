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

    /**
     * Valeurs par défaut : toutes les notifications activées (2026-11-22).
     * Utilisées à l'inscription (MroProfile::afterSave) et à la première ouverture
     * de la page des préférences.
     */
    public const DEFAULTS = [
        'notify_by_email' => 1,
        'notify_by_platform' => 1,
        'notify_by_both' => 1,
        'notify_for_certified_aircraft' => 1,
        'notify_for_non_certified_aircraft' => 1,
        'notify_for_appointment_acceptance' => 1,
        'notify_for_feedback' => 1,
    ];

    /**
     * Retourne les préférences du MRO, en les créant avec les valeurs par défaut
     * si elles n'existent pas encore. Ne modifie jamais des préférences existantes.
     */
    public static function ensureDefaultsFor(int $mroId): ?self
    {
        if ($mroId <= 0) {
            return null;
        }

        $preferences = static::findOne(['mro_id' => $mroId]);
        if ($preferences !== null) {
            return $preferences;
        }

        $preferences = new static();
        $preferences->mro_id = $mroId;
        $preferences->setAttributes(self::DEFAULTS, false);

        if (!$preferences->save()) {
            Yii::error('Préférences MRO par défaut non créées : ' . json_encode($preferences->errors), __METHOD__);
            return null;
        }

        return $preferences;
    }
}

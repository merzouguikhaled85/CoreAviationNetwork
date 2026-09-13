<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * Trace ajoutee sans modification ulterieure. L'absence volontaire de relations
 * AR empeche une suppression ou un renommage metier d'alterer l'historique.
 */
class AuditLog extends ActiveRecord
{
    public static function tableName()
    {
        return 'audit_log';
    }

    public function rules()
    {
        return [
            [['user_id', 'company_id'], 'integer'],
            [['old_values', 'new_values', 'request_url'], 'string'],
            [['created_at'], 'safe'],
            [['action'], 'required'],
            [['username', 'company_name', 'model', 'record_id'], 'string', 'max' => 255],
            [['user_role'], 'string', 'max' => 32],
            [['action'], 'string', 'max' => 64],
            [['ip_address'], 'string', 'max' => 45],
            [['country_code'], 'string', 'max' => 2],
            [['country_name', 'city'], 'string', 'max' => 100],
            [['user_agent'], 'string', 'max' => 1000],
            [['request_method'], 'string', 'max' => 10],
            [['request_id'], 'string', 'max' => 32],
        ];
    }

    public function beforeSave($insert)
    {
        return $insert && parent::beforeSave($insert);
    }

    public function beforeDelete()
    {
        return false;
    }

    public function getOldValuesArray(): array
    {
        return $this->decodeValues($this->old_values);
    }

    public function getNewValuesArray(): array
    {
        return $this->decodeValues($this->new_values);
    }

    private function decodeValues($value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }

        try {
            $decoded = Json::decode($value);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $exception) {
            return [];
        }
    }
}

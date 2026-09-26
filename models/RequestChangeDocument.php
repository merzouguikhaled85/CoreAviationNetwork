<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Archive immuable d'une version de PO ou de devis liée à un avenant.
 */
class RequestChangeDocument extends ActiveRecord
{
    public const TYPE_PO = 'po';
    public const TYPE_QUOTE = 'quote';

    public const REVIEW_PENDING = 'pending';
    public const REVIEW_ACCEPTED = 'accepted';
    public const REVIEW_REJECTED = 'rejected';

    public static function tableName()
    {
        return '{{%request_change_document}}';
    }

    public function rules()
    {
        return [
            [['request_change_id', 'version', 'uploaded_by_id'], 'integer'],
            [['request_change_id', 'document_type', 'version', 'uploaded_by_type', 'uploaded_by_id', 'review_status'], 'required'],
            [['description', 'rejection_reason'], 'string'],
            [['price'], 'number', 'min' => 0],
            [['file_path'], 'string', 'max' => 255],
            [['currency'], 'string', 'max' => 3],
            [['document_type'], 'in', 'range' => [self::TYPE_PO, self::TYPE_QUOTE]],
            [['uploaded_by_type'], 'in', 'range' => ['ao', 'mro']],
            [['review_status'], 'in', 'range' => [self::REVIEW_PENDING, self::REVIEW_ACCEPTED, self::REVIEW_REJECTED]],
            [['created_at', 'reviewed_at'], 'safe'],
        ];
    }

    public function getRequestChange()
    {
        return $this->hasOne(RequestChange::class, ['id' => 'request_change_id']);
    }
}

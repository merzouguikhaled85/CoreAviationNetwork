<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Représente un avenant sans modifier le cycle de vie opérationnel de la Request.
 *
 * @property int $id
 * @property int $request_id
 * @property int $mro_request_apply_id
 * @property int $ao_id
 * @property int $mro_id
 * @property int $version
 * @property string $status
 * @property string $original_request_status
 * @property string $reason
 * @property string|null $added_tasks
 * @property string|null $removed_tasks
 * @property string|null $mro_rejection_reason
 * @property string|null $revised_po
 * @property string|null $po_rejection_reason
 * @property string|null $quote_description
 * @property float|null $quote_price
 * @property string|null $quote_currency
 * @property string|null $revised_quote
 * @property string|null $quote_rejection_reason
 */
class RequestChange extends ActiveRecord
{
    public const STATUS_PENDING_MRO = 'pending_mro';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_AWAITING_UPDATED_PO = 'awaiting_updated_po';
    public const STATUS_PENDING_PO_REVIEW = 'pending_po_review';
    public const STATUS_PO_REJECTED = 'po_rejected';
    public const STATUS_AWAITING_UPDATED_QUOTE = 'awaiting_updated_quote';
    public const STATUS_PENDING_QUOTE_REVIEW = 'pending_quote_review';
    public const STATUS_QUOTE_REJECTED = 'quote_rejected';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /** @var mixed fichier reçu depuis le formulaire AO */
    public $revisedPoUpload;

    /** @var mixed fichier reçu depuis le formulaire MRO */
    public $revisedQuoteUpload;

    public static function tableName()
    {
        return '{{%request_change}}';
    }

    /**
     * SYNCHRONISATION DES LISTES : chaque décision sur l'avenant réveille les
     * écrans AO/MRO déjà ouverts, sans toucher au statut de la Request principale.
     */
    public function behaviors()
    {
        return [
            'requestChange' => [
                'class' => \app\components\RequestChangeBehavior::class,
                'requestIdAttribute' => 'request_id',
                'eventPrefix' => 'amendment',
            ],
        ];
    }

    public function rules()
    {
        return [
            [['request_id', 'mro_request_apply_id', 'ao_id', 'mro_id', 'version'], 'integer'],
            [['request_id', 'mro_request_apply_id', 'ao_id', 'mro_id', 'version', 'status', 'original_request_status', 'reason'], 'required'],
            [['revisedPoUpload'], 'required', 'on' => 'uploadPo'],
            [['quote_description', 'quote_price', 'quote_currency'], 'required', 'on' => 'submitQuote'],
            [['reason', 'added_tasks', 'removed_tasks', 'mro_rejection_reason', 'po_rejection_reason', 'quote_description', 'quote_rejection_reason'], 'string'],
            [['quote_price'], 'number', 'min' => 0],
            [['status', 'original_request_status'], 'string', 'max' => 40],
            [['quote_currency'], 'string', 'length' => 3],
            [['quote_currency'], 'filter', 'filter' => static function ($value) {
                return $value === null ? null : strtoupper(trim((string) $value));
            }],
            [['revised_po', 'revised_quote'], 'string', 'max' => 255],
            [['requested_at', 'mro_reviewed_at', 'po_uploaded_at', 'po_reviewed_at', 'quote_submitted_at', 'quote_reviewed_at', 'completed_at', 'updated_at'], 'safe'],
            [['status'], 'in', 'range' => array_keys(self::statusLabels())],
            [['revisedPoUpload', 'revisedQuoteUpload'], 'file', 'skipOnEmpty' => true, 'extensions' => ['pdf', 'png', 'jpg', 'jpeg'], 'maxSize' => 10 * 1024 * 1024],
            ['added_tasks', 'validateScopeChange'],
        ];
    }

    /**
     * Un avenant doit décrire au moins un ajout ou un retrait. Le contrôle est
     * porté par le modèle afin de rester vrai quel que soit le formulaire utilisé.
     */
    public function validateScopeChange($attribute)
    {
        if (trim((string) $this->added_tasks) === '' && trim((string) $this->removed_tasks) === '') {
            $this->addError($attribute, 'Enter at least one task to add or remove.');
        }
    }

    public function attributeLabels()
    {
        return [
            'reason' => 'Change reason',
            'added_tasks' => 'Tasks to add',
            'removed_tasks' => 'Tasks to remove',
            'revisedPoUpload' => 'Revised PO',
            'quote_description' => 'Revised quote description',
            'quote_price' => 'Revised quote amount',
            'quote_currency' => 'Currency',
            'revisedQuoteUpload' => 'Revised quote document',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING_MRO => 'Awaiting MRO decision',
            self::STATUS_REJECTED => 'Change rejected by MRO',
            self::STATUS_AWAITING_UPDATED_PO => 'Awaiting revised PO',
            self::STATUS_PENDING_PO_REVIEW => 'Revised PO awaiting MRO approval',
            self::STATUS_PO_REJECTED => 'Revised PO rejected by MRO',
            self::STATUS_AWAITING_UPDATED_QUOTE => 'Awaiting revised quote',
            self::STATUS_PENDING_QUOTE_REVIEW => 'Revised quote awaiting AO approval',
            self::STATUS_QUOTE_REJECTED => 'Revised quote rejected by AO',
            self::STATUS_COMPLETED => 'Change order accepted',
            self::STATUS_CANCELLED => 'Change order cancelled',
        ];
    }

    public static function activeStatuses(): array
    {
        return [
            self::STATUS_PENDING_MRO,
            self::STATUS_AWAITING_UPDATED_PO,
            self::STATUS_PENDING_PO_REVIEW,
            self::STATUS_PO_REJECTED,
            self::STATUS_AWAITING_UPDATED_QUOTE,
            self::STATUS_PENDING_QUOTE_REVIEW,
            self::STATUS_QUOTE_REJECTED,
        ];
    }

    public function getStatusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function getRequest()
    {
        return $this->hasOne(Requests::class, ['request_id' => 'request_id']);
    }

    public function getApplication()
    {
        return $this->hasOne(MroRequestApply::class, ['id' => 'mro_request_apply_id']);
    }

    /** Toutes les versions de PO et de devis, de la plus récente à la plus ancienne. */
    public function getDocuments()
    {
        return $this->hasMany(RequestChangeDocument::class, ['request_change_id' => 'id'])
            ->orderBy(['document_type' => SORT_ASC, 'version' => SORT_DESC]);
    }
}

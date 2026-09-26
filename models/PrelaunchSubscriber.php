<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Early-access registration with double opt-in confirmation.
 */
class PrelaunchSubscriber extends ActiveRecord
{
    public const SCENARIO_PUBLIC_SIGNUP = 'public-signup';

    public const TYPE_MRO = 'MRO';
    public const TYPE_AIRCRAFT_OPERATOR = 'AIRCRAFT_OPERATOR';

    public const STATUS_PENDING_CONFIRMATION = 'PENDING_CONFIRMATION';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_UNSUBSCRIBED = 'UNSUBSCRIBED';

    public const EMAIL_PENDING = 'PENDING';
    public const EMAIL_SENT = 'SENT';
    public const EMAIL_FAILED = 'FAILED';

    public const CONSENT_TEXT_VERSION = 'launch-updates-v1';

    /** @var string Honeypot that must remain empty. */
    public $website;

    public static function tableName()
    {
        return '{{%prelaunch_subscriber}}';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_PUBLIC_SIGNUP] = [
            'first_name',
            'last_name',
            'company_name',
            'business_email',
            'company_website',
            'company_type',
            'consent',
            'website',
        ];

        return $scenarios;
    }

    public function rules()
    {
        return [
            [['first_name', 'last_name', 'company_name', 'business_email', 'company_type'], 'required'],
            [['first_name', 'last_name'], 'trim'],
            [['first_name', 'last_name'], 'string', 'min' => 2, 'max' => 100],
            [['company_name'], 'trim'],
            [['company_name'], 'string', 'min' => 2, 'max' => 200],
            [['business_email'], 'trim'],
            [['business_email'], 'email'],
            [['business_email'], 'string', 'max' => 190],
            [
                ['business_email'],
                'unique',
                'targetAttribute' => 'business_email',
                'except' => self::SCENARIO_PUBLIC_SIGNUP,
            ],
            [['company_website'], 'trim'],
            [['company_website'], 'string', 'max' => 255],
            [
                ['company_website'],
                'url',
                'validSchemes' => ['http', 'https'],
                'skipOnEmpty' => true,
            ],
            [['company_type'], 'in', 'range' => array_keys(self::companyTypeOptions())],
            [['consent'], 'boolean', 'trueValue' => 1, 'falseValue' => 0],
            [
                ['consent'],
                'compare',
                'compareValue' => 1,
                'operator' => '==',
                'type' => 'number',
                'message' => 'You must agree to receive launch updates.',
            ],
            [['website'], 'string', 'max' => 255],
            [['website'], 'validateHoneypot'],
        ];
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $this->business_email = self::normalizeEmail($this->business_email);
        $website = trim((string) $this->company_website);
        if ($website !== '' && !preg_match('~^https?://~i', $website)) {
            $website = 'https://' . $website;
        }
        $this->company_website = $website === '' ? null : $website;

        return true;
    }

    public function validateHoneypot($attribute)
    {
        if (trim((string) $this->$attribute) !== '') {
            $this->addError($attribute, 'The registration could not be submitted.');
        }
    }

    public static function normalizeEmail($email)
    {
        return mb_strtolower(trim((string) $email), 'UTF-8');
    }

    public static function companyTypeOptions()
    {
        return [
            self::TYPE_MRO => 'MRO',
            self::TYPE_AIRCRAFT_OPERATOR => 'Aircraft Operator',
        ];
    }

    public static function subscriptionStatusOptions()
    {
        return [
            self::STATUS_PENDING_CONFIRMATION => 'Pending confirmation',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_UNSUBSCRIBED => 'Unsubscribed',
        ];
    }

    public static function confirmationEmailStatusOptions()
    {
        return [
            self::EMAIL_PENDING => 'Pending',
            self::EMAIL_SENT => 'Sent',
            self::EMAIL_FAILED => 'Failed',
        ];
    }

    public function attributeLabels()
    {
        return [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'company_name' => 'Company Name',
            'business_email' => 'Business Email',
            'company_website' => 'Company Website',
            'company_type' => 'Company Type',
            'consent' => 'I agree to receive updates about the launch of Core Aviation Network.',
        ];
    }
}

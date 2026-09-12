<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Certificates;
use app\models\MroNotificationsPreferences;

class MroProfile extends ActiveRecord
{
    public $terms;
    public $confirm_password;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        // Set default status to 'active'
        $this->status = 'active';
    }

    public static function tableName()
    {
        return 'mro_profiles';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['main_references'], 'file',
                'maxFiles' => 10,
                'maxSize' => 5 * 1024 * 1024, // 5MB
                'mimeTypes' => ['image/*', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                'wrongMimeType' => 'Only images or documents are allowed.',
                'skipOnEmpty' => true, // Allow empty during updates
            ],
            
            [['username', 'email', 'country_id', 'city_id','zip_code','first_name','address', 'last_name', 'contact_number', 'company_name'], 'required'], // Make others required only during creation
            ['zip_code', 'string', 'max' => 20],
            [['main_references'], 'required', 'on' => 'create'], // Required only on creation
            [['website', 'youtube_video', 'company_photo', 'verification_token', 'address'], 'safe'], // Add 'address' to the safe rule
            [['username', 'password', 'confirm_password', 'email', 'status', 'first_name','address', 'last_name', 'contact_number', 'company_name', 'profile_photo'], 'string', 'max' => 255],
            [['email'], 'email'],
            ['username', 'isUsernameUnique'],
            ['email', 'isEmailUnique'],
            [['country_id', 'city_id'], 'integer'],
            [['status'], 'default', 'value' => 'active'],
            [['email_verified'], 'default', 'value' => 0],
            [['status'], 'in', 'range' => ['active', 'banned', 'hidden']],
            [['email_verified'], 'boolean'],
            [['password_reset_token'], 'safe'],
            [['password', 'confirm_password'], 'required', 'on' => 'create'], // Required only on creation
            ['confirm_password', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.', 'on' => 'create'],
            ['status', 'in', 'range' => ['active', 'banned', 'hidden'], 'when' => function($model) {
                return Yii::$app->session->get('user_type') === 'admin';
            }],
        ];
    }
    
    

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'mro_id' => 'Mro ID',
            'website' => 'Website',
            'youtube_video' => 'Youtube Video',
            'company_photo' => 'Company Photo',
            'main_references' => 'Main References',
            'username' => 'Username',
            'password' => 'Password',
            'confirm_password' => 'Confirm Password',
            'email' => 'Email',
            'status' => 'Status',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'contact_number' => 'Contact Number',
            'company_name' => 'Company Name',
            'profile_photo' => 'Profile Photo',
            'email_verified' => 'Email Verified',
            'country_id' => 'Country',
            'city_id' => 'City',
            'verification_token' => 'Verification Token', // Add verification_token label
            'password_reset_token' => 'Password Reset Token',
            'address' => 'Address', // Add label for 'address'
            'zip_code' => 'Zip Code',


        ];
    }
public function scenarios()
{
    $scenarios = parent::scenarios();

    $scenarios['update'] = [
        'username',
        'email',
        'first_name',
        'last_name',
        'contact_number',
        'company_name',
        'profile_photo',
        'country_id',
        'city_id',
        'website',
        'youtube_video',
        'address',
        'zip_code',
        'company_photo',
        'main_references',
    ];

    return $scenarios;
}
/*
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Only hash the password if a new password is provided
            if (!empty($this->password)) {
                $this->password = Yii::$app->security->generatePasswordHash($this->password);
            } else {
                // Unset password to avoid overwriting it if no new password is provided
                unset($this->password);
            }
            return true;
        }
        return false;
    }
    */
    public function isUsernameUnique($attribute, $params)
    {
        $query = self::find()->where(['username' => $this->username]);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'mro_id', $this->mro_id]);
        }
        $aoExists = AoProfile::find()->where(['username' => $this->username])->exists();
        if ($query->exists() || $aoExists) {
            $this->addError($attribute, 'Username is already taken');
        }
    }

    // Custom validation method to check email uniqueness

    public function isEmailUnique($attribute, $params)
{
    // Only validate if the email has changed
    if ($this->isNewRecord || $this->getOldAttribute('email') !== $this->email) {
        $query = self::find()->where(['email' => $this->email]);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'mro_id', $this->mro_id]);
        }
        $mroExists = AoProfile::find()->where(['email' => $this->email])->exists();
        if ($query->exists() || $mroExists) {
            $this->addError($attribute, 'Email is already taken');
        }
    }
}

    public static function findByUsername($username)
    {
        return static::find()->where(['username' => $username])->one();
    }

    public static function findByPasswordResetToken($token)
    {
        return static::findOne(['password_reset_token' => $token]);
    }

public function generatePasswordResetToken()
{
    $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();

    if (!$this->save(false)) {
        Yii::error([
            'model' => static::class,
            'errors' => $this->errors,
            'email' => $this->email,
        ]);
        return false;
    }

    return true;
}

    public function resetPassword($newPassword)
    {
        $this->password = $newPassword;
        $this->password_reset_token = null; // Clear the token after successful reset
        return $this->save(false); // Save without validation to prevent validation errors
    }

    public function getCertificates()
    {
        return $this->hasMany(Certificates::className(), ['mro_id' => 'mro_id']);
    }

    public function getMroNotificationsPreferences()
    {
        return $this->hasOne(MroNotificationsPreferences::className(), ['mro_id' => 'mro_id']);
    }
    public function getAircraftCertificates()
    {
        return $this->hasMany(MroAircraftCertificate::className(), ['mro_id' => 'mro_id']);
    }
    public function setPassword($password)
{
    $this->password_hash = Yii::$app->security->generatePasswordHash($password);
}

public function validatePassword($password)
{
    return Yii::$app->security->validatePassword($password, $this->password_hash);
}
public function getInsuranceDocuments()
{
    return $this->hasMany(MroInsuranceDocuments::class, ['mro_id' => 'mro_id']);
}
}

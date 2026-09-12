<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class AoProfile extends ActiveRecord
{
    public $terms;
    public $confirm_password;

    public static function tableName()
    {
        return 'ao_profiles';
    }

    public function init()
    {
        parent::init();
        // Set default status to 'active'
        $this->status = 'active';
    }

    public function rules()
    {
        return [
            [['username', 'email', 'country_id', 'city_id', 'address','zip_code', 'first_name', 'last_name', 'contact_number'], 'required'],
            ['zip_code', 'string', 'max' => 20],
            [['password', 'confirm_password'], 'required', 'on' => 'create'], // Required only on creation
            ['confirm_password', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.', 'on' => 'create'], // Only during creation
            ['username', 'isUsernameUnique'],
            ['email', 'isEmailUnique'],
            [['email'], 'email'],
            [['status'], 'in', 'range' => ['active', 'banned', 'hidden']],
            [['status'], 'in', 'range' => ['active', 'banned', 'hidden'], 'when' => function ($model) {
                return Yii::$app->session->get('user_type') === 'admin';
            }],
            [['email_verified'], 'boolean'],
            [['username', 'email', 'first_name', 'last_name', 'contact_number', 'company_name', 'profile_photo', 'address'], 'string', 'max' => 255],
            [['profile_photo'], 'image', 'extensions' => ['png', 'jpg', 'jpeg', 'gif'], 'maxSize' => 5 * 1024 * 1024], // Maximum 5MB
            [['country_id', 'city_id'], 'integer'],
            [['verification_token'], 'safe'], // Keep verification_token as safe
            [['password_reset_token'], 'safe'], // Keep password_reset_token as safe
        ];
    }
    
    
    public function attributeLabels()
    {
        return [
            'ao_id' => 'AO ID',
            'username' => 'Username',
            'password' => 'Password',
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
            'address' => 'Address',
            'verification_token' => 'Verification Token', // Add verification_token label
            'password_reset_token' => 'Password Reset Token',
            'zip_code' => 'Zip Code',

        ];
    }

    // Method to find user by password reset token
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

    // Custom validation method to check username uniqueness
    public function isUsernameUnique($attribute, $params)
    {
        $query = self::find()->where(['username' => $this->username]);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'ao_id', $this->ao_id]);
        }
        $mroExists = MroProfile::find()->where(['username' => $this->username])->exists();
        if ($query->exists() || $mroExists) {
            $this->addError($attribute, 'Username is already taken');
        }
    }

    // Custom validation method to check email uniqueness
// Custom validation method to check email uniqueness
public function isEmailUnique($attribute, $params)
{
    // Only validate if the email has changed
    if ($this->isNewRecord || $this->getOldAttribute('email') !== $this->email) {
        $query = self::find()->where(['email' => $this->email]);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'ao_id', $this->ao_id]);
        }
        $mroExists = MroProfile::find()->where(['email' => $this->email])->exists();
        if ($query->exists() || $mroExists) {
            $this->addError($attribute, 'Email is already taken');
        }
    }
}


    public static function findByUsername($username)
    {
        return static::find()->where(['username' => $username])->one();
    }

    public function resetPassword($newPassword)
    {
        $this->password = $newPassword;
        $this->password_reset_token = null; // Clear the token after successful reset
        return $this->save(false); // Save without validation to prevent validation errors
    }
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }
    
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function getCountry()
    {
        return $this->hasOne(Countries::class, ['country_id' => 'country_id']);
    }

    public function getCity()
    {
        return $this->hasOne(Cities::class, ['city_id' => 'city_id']);
    }
}

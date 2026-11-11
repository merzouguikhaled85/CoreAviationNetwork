<?php

namespace app\models;

use Yii;
use yii\base\Model;

/** Form bound by the controller to the authenticated administrator only. */
class AdminPasswordForm extends Model
{
    public $currentPassword;
    public $newPassword;
    public $confirmPassword;

    private AdminProfile $admin;

    public function __construct(AdminProfile $admin, array $config = [])
    {
        $this->admin = $admin;
        parent::__construct($config);
    }

    public function rules()
    {
        return [
            [['currentPassword', 'newPassword', 'confirmPassword'], 'required'],
            [['currentPassword', 'newPassword', 'confirmPassword'], 'string', 'max' => 4096],
            ['currentPassword', 'validateCurrentPassword'],
            ['newPassword', 'string', 'min' => 8],
            ['newPassword', 'match', 'pattern' => '/^(?=.*[A-Za-z])(?=.*\d).+$/s',
                'message' => 'Use at least one letter and one number.'],
            ['newPassword', 'validatePasswordLength'],
            ['newPassword', 'validateDifferentPassword'],
            ['confirmPassword', 'compare', 'compareAttribute' => 'newPassword',
                'message' => 'Confirmation does not match the new password.',
                'when' => static function ($model) { return !$model->hasErrors('newPassword'); }],
        ];
    }

    public function attributeLabels()
    {
        return [
            'currentPassword' => 'Current password',
            'newPassword' => 'New password',
            'confirmPassword' => 'Confirm new password',
        ];
    }

    public function validateCurrentPassword($attribute)
    {
        if (!$this->hasErrors($attribute)
            && !Yii::$app->security->validatePassword($this->$attribute, $this->admin->password)) {
            $this->addError($attribute, 'The current password is incorrect.');
        }
    }

    public function validatePasswordLength($attribute)
    {
        // Bcrypt only uses the first 72 bytes, including multibyte characters.
        if (!$this->hasErrors($attribute) && strlen($this->$attribute) > 72) {
            $this->addError($attribute, 'The new password must not exceed 72 bytes.');
        }
    }

    public function validateDifferentPassword($attribute)
    {
        if (!$this->hasErrors('currentPassword') && $this->$attribute === $this->currentPassword) {
            $this->addError($attribute, 'Choose a password different from your current password.');
        }
    }

    public function updatePassword(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->admin->password = $this->admin->hashPassword($this->newPassword);
        // beforeSave() increments auth_version, invalidating the other sessions.
        if (!$this->admin->save(false, ['password', 'auth_version'])) {
            $this->addError('newPassword', 'Your password could not be saved. Please try again.');
            return false;
        }

        return true;
    }
}

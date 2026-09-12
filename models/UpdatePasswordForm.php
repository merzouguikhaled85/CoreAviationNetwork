<?php

namespace app\models;

use Yii;
use yii\base\Model;

class UpdatePasswordForm extends Model
{
    public $oldPassword;
    public $newPassword;
    public $confirmPassword;

    /** @var User|null */
    private $_user;

    public function __construct($config = [])
    {
        $this->_user = Yii::$app->user->identity;
        parent::__construct($config);
    }

    public function rules()
    {
        return [
            [['oldPassword', 'newPassword', 'confirmPassword'], 'required'],

            // Vérifier l'ancien mot de passe
            ['oldPassword', 'validateOldPassword'],

            // Complexité du nouveau mot de passe
            ['newPassword', 'string', 'min' => 8],
            [
                'newPassword',
                'match',
                'pattern' => '/^(?=.*[A-Za-z])(?=.*\d).+$/',
                'message' => 'Password must contain at least one letter and one number.'
            ],

            // Confirmation = newPassword
            [
                'confirmPassword',
                'compare',
                'compareAttribute' => 'newPassword',
                'message' => 'Confirmation does not match the new password.'
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'oldPassword'     => 'Current Password',
            'newPassword'     => 'New Password',
            'confirmPassword' => 'Confirm Password',
        ];
    }

    public function validateOldPassword($attribute)
    {
        if ($this->hasErrors()) {
            return;
        }

        if (!$this->_user || !$this->_user->validatePassword($this->oldPassword)) {
            $this->addError($attribute, 'Invalid current password.');
        }
    }

    /**
     * Applique réellement le changement de mot de passe.
     */
    public function updatePassword(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        /** @var User $user */
        $user = $this->_user;
        $user->setPassword($this->newPassword);
        $user->generateAuthKey(); // si tu utilises auth_key / rememberMe

        return $user->save(false);
    }
}

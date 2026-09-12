<?php 
namespace app\models;

use Yii;
use yii\base\Model;

class ResetPasswordForm extends Model
{
    public $password;
    public $confirm_password;

    public function rules()
    {
        return [
            [['password', 'confirm_password'], 'required'],
            [['password', 'confirm_password'], 'string', 'min' => 6],
            [['confirm_password'], 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],
        ];
    }

    public function resetPassword($user)
    {
        $user->password = $this->password;
        $user->password_reset_token = null; // Clear the token after successful reset

        return $user->save(false); // Save without validation to prevent validation errors
    }
}

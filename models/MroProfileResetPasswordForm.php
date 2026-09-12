<?php
namespace app\models;

use Yii;
use yii\base\Model;
use yii\web\BadRequestHttpException;

class MroProfileResetPasswordForm extends Model
{
    public $old_password;
    public $new_password;
    public $confirm_password;

    public function rules()
    {
        return [
            [['old_password', 'new_password', 'confirm_password'], 'required'],
            [['new_password', 'confirm_password'], 'string', 'min' => 6],
            ['confirm_password', 'compare', 'compareAttribute' => 'new_password', 'message' => "Passwords don't match."],
        ];
    }

    public function attributeLabels()
    {
        return [
            'old_password' => 'Old Password',
            'new_password' => 'New Password',
            'confirm_password' => 'Confirm Password',
        ];
    }
}

<?php
namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\VarDumper;

class LoginForm extends Model
{
    public $username;
    public $password;
    /* Une session persistante exige toujours un choix explicite de l'utilisateur. */
    public $rememberMe = false;

    private $_user = false;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect username or password.');
            }
        }
    }

    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            if ($user) {
                return Yii::$app->user->login($user, $this->rememberMe ? 3600 * 24 * 14 : 0);
            }
        }
        return false;
    }

    protected function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }
        return $this->_user;
    }
}

<?php
namespace app\models;

use Yii;
use yii\base\Model;
use app\models\AoProfile;
use app\models\MroProfile;
use yii\helpers\VarDumper;
use app\components\PasswordResetTokenService;

class PasswordResetRequestForm extends Model
{
    public $email;

    public function rules()
    {
        return [
            [['email'], 'required'],
            [['email'], 'email'],
        ];
    }
public function sendEmail()
{
    $user = AoProfile::findOne([
        'email' => $this->email,
        'status' => 'active',
    ]);

    if (!$user) {
        $user = MroProfile::findOne([
            'email' => $this->email,
            'status' => 'active',
        ]);
    }

    if (!$user) {
        return false;
    }

    $token = PasswordResetTokenService::issue();
    $user->password_reset_token = $token['storedToken'];

    if (!$user->save(false)) {
        return false;
    }

    return \app\components\PasswordResetEmail::compose($user, $token['publicToken'])->send();
}


}

<?php 

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class AdminProfile extends ActiveRecord
{
    public static function tableName()
    {
        return 'admin_profiles';
    }

    public function rules()
    {
        return [
            [['username', 'password', 'email'], 'required'],
            [['username', 'password', 'email', 'first_name', 'last_name', 'profile_photo'], 'string', 'max' => 255],
            [['contact_number'], 'string', 'max' => 20],
        ];
    }

    public function attributeLabels()
    {
        return [
            'admin_id' => 'Admin ID',
            'username' => 'Username',
            'password' => 'Password',
            'email' => 'Email',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'contact_number' => 'Contact Number',
            'profile_photo' => 'Profile Photo',
        ];
    }
    public function hashPassword($password)
    {
        return Yii::$app->security->generatePasswordHash($password);
    }
    public static function findByUsername($username)
{
    return static::find()->where(['username' => $username])->one();
}

}

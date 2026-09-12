<?php

namespace app\models;

use Yii;
use yii\base\BaseObject;
use yii\helpers\VarDumper;
use yii\web\IdentityInterface;

class User extends BaseObject implements IdentityInterface
{
    /**
     * Finds user by ID
     *
     * 

     * @param int|string $id
     * @return IdentityInterface|null
     */
    public $ao_id; // Define admin_id attribute
    public $status; // Define admin_id attribute
    public $company_name; // Define admin_id attribute
    public $email_verified; // Define admin_id attribute
    public $country_id; // Define admin_id attribute
    public $city_id; // Define admin_id attribute
    public $address; // Define admin_id attribute
    public $mro_id; // Define admin_id attribute
    public $website; // Define admin_id attribute

    public $youtube_video; // Define admin_id attribute
    public $company_photo; // Define admin_id attribute
    public $main_references; // Define admin_id attribute

    public $insurance_document;
     public $admin_id; // Define admin_id attribute
     public $username; // Define admin_id attribute
     public $password;
     public $email;
     public $first_name;
     public $last_name;

     public $contact_number;

     public $profile_photo;

     public $authKey; // Add this line
     public $verification_token;
     public $password_reset_token;

     public $zip_code;

     public static function findIdentity($id)
     {
         $admin = AdminProfile::findOne(['admin_id' => $id]);
         if ($admin !== null) {
             return new static($admin->toArray());
         }
 
         $mro = MroProfile::findOne(['mro_id' => $id]);
         if ($mro !== null) {
             return new static($mro->toArray());
         }
 
         $ao = AoProfile::findOne(['ao_id' => $id]);
         if ($ao !== null) {
             return new static($ao->toArray());
         }
 
         return null;
     }
 
     public static function findIdentityByAccessToken($token, $type = null)
     {
         return null; // Implement if needed
     }
 
     public function getUserType()
     {
         if ($this->admin_id !== null) {
            Yii::$app->session->set('user_type','admin');
            Yii::$app->session->set('admin_id',$this->admin_id);

             return 'admin';
         } elseif ($this->mro_id !== null) {
            Yii::$app->session->set('user_type','mro');
            Yii::$app->session->set('mro_id',$this->mro_id);

             return 'mro';
         } elseif ($this->ao_id != null) {
            Yii::$app->session->set('user_type','ao');
            Yii::$app->session->set('ao_id',$this->ao_id);

             return 'ao';
         } else {
             return 'Unknown';
         }
     }
 
     public static function findByUsername($username)
     {
         $admin = AdminProfile::findOne(['username' => $username]);
         if ($admin !== null) {
             return new static($admin->toArray());
         }
 
         $mro = MroProfile::findOne(['username' => $username]);
         if ($mro !== null) {
             return new static($mro->toArray());
         }
 
         $ao = AoProfile::findOne(['username' => $username]);
         if ($ao !== null) {
             return new static($ao->toArray());
         }
 
         return null;
     }
 
     public function getId()
     {

         return $this->admin_id ?? $this->mro_id ?? $this->ao_id;
     }
 
     public function getAuthKey()
     {
         return $this->authKey;
     }
 
     public function validateAuthKey($authKey)
     {
         return $this->authKey === $authKey;
     }
 
     public function validatePassword($password)
     {
         return Yii::$app->security->validatePassword($password, $this->password);
     }
 }

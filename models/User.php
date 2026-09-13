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
         /*
          * L'identifiant persistant contient le rôle afin que admin:12, mro:12
          * et ao:12 ne puissent jamais restaurer le mauvais compte.
          */
         if (preg_match('/^(admin|mro|ao):([1-9][0-9]*)$/', (string) $id, $matches)) {
             return static::findIdentityByTypeAndId($matches[1], (int) $matches[2]);
         }

         /*
          * Compatibilité de transition : une ancienne session numérique n'est
          * restaurée que si son rôle est encore présent dans cette même session.
          */
         if (filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false) {
             $userType = Yii::$app->session->get('user_type');
             if (in_array($userType, ['admin', 'mro', 'ao'], true)) {
                 return static::findIdentityByTypeAndId($userType, (int) $id);
             }
         }

         return null;
     }

     private static function findIdentityByTypeAndId($userType, $id)
     {
         $profile = null;
         if ($userType === 'admin') {
             $profile = AdminProfile::findOne(['admin_id' => $id]);
         } elseif ($userType === 'mro') {
             $profile = MroProfile::findOne(['mro_id' => $id]);
         } elseif ($userType === 'ao') {
             $profile = AoProfile::findOne(['ao_id' => $id]);
         }

         return $profile === null ? null : new static($profile->toArray());
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
         if ($this->admin_id !== null) {
             return 'admin:' . (int) $this->admin_id;
         }
         if ($this->mro_id !== null) {
             return 'mro:' . (int) $this->mro_id;
         }
         if ($this->ao_id !== null) {
             return 'ao:' . (int) $this->ao_id;
         }

         return null;
     }
 
     public function getAuthKey()
     {
         /*
          * La clé dérive du rôle, de l'identifiant et du hash du mot de passe.
          * Elle change automatiquement après une modification du mot de passe.
          */
         if ($this->getId() === null || empty($this->password)) {
             return null;
         }

         return hash_hmac(
             'sha256',
             $this->getId() . '|' . $this->password,
             Yii::$app->request->cookieValidationKey
         );
     }
 
     public function validateAuthKey($authKey)
     {
         $expected = $this->getAuthKey();
         return is_string($authKey) && is_string($expected) && hash_equals($expected, $authKey);
     }
 
     public function validatePassword($password)
     {
         return Yii::$app->security->validatePassword($password, $this->password);
     }
 }

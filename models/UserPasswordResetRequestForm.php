<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\base\InvalidConfigException;

class UserPasswordResetRequestForm extends Model
{
    public $email;
    public $usertype;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email', 'usertype'], 'required'],
            [['email'], 'email'],
            [['usertype'], 'in', 'range' => ['mro', 'ao']],
        ];
    }

    /**
     * Sends an email with a link, for resetting the password.
     *
     * @return bool whether the email was sent
     * @throws InvalidConfigException
     */
  

public function sendEmail()
{
    // Find active user depending on selected user type
    if ($this->usertype === 'mro') {
        $user = MroProfile::findOne([
            'status' => 'active',
            'email' => $this->email,
        ]);
    } else {
        $user = AoProfile::findOne([
            'status' => 'active',
            'email' => $this->email,
        ]);
    }

    if (!$user) {
        Yii::$app->session->setFlash('error', 'No active user found with this email address.');
        return false;
    }

    // Generate a new secure password reset token
    $user->generatePasswordResetToken();

    // Save only the token without running full model validation
    if (!$user->save(false)) {
        Yii::$app->session->setFlash('error', 'Unable to generate reset token.');
        return false;
    }

    // Create absolute reset link
    $resetLink = Yii::$app->urlManager->createAbsoluteUrl([
        'site/reset-password',
        'token' => $user->password_reset_token,
        'type' => $this->usertype,
    ]);

    // Email sender information
    $senderEmail = Yii::$app->params['senderEmail'] ?? 'donotreply@coreaviationnetwork.com';
    $senderName = Yii::$app->params['senderName'] ?? 'Core Aviation Network';
    $appName = Yii::$app->name ?: 'Core Aviation Network';

    // Send HTML email using the view template 'password_reset'
    return Yii::$app->mailer->compose('password_reset', [
        'user' => $user,
        'resetLink' => $resetLink,
        'userType' => $this->usertype,
    ])
    ->setTo($this->email)
    ->setFrom([$senderEmail => $senderName])
    ->setSubject('Password reset - ' . $appName)
    ->send();
}
}

<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\base\InvalidConfigException;

class UsernameResetRequestForm extends Model
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
     * Sends an email with the username reminder.
     *
     * @return bool whether the email was sent
     * @throws InvalidConfigException
     */
    public function sendEmail()
    {
        if ($this->usertype == 'mro') {
            $user = MroProfile::findOne(['status' => 'active', 'email' => $this->email]);
        } else {
            $user = AoProfile::findOne(['status' => 'active', 'email' => $this->email]);
        }

        if (!$user) {
            return false;
        }

        // Résolution du nom affiché
        if (!empty($user->first_name) || !empty($user->last_name)) {
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        } elseif (!empty($user->name)) {
            $fullName = $user->name;
        } elseif (!empty($user->username)) {
            $fullName = $user->username;
        } elseif (!empty($user->email)) {
            $fullName = $user->email;
        } else {
            $fullName = 'User';
        }

        $appName      = htmlspecialchars(Yii::$app->name, ENT_QUOTES, 'UTF-8');
        $safeName     = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $safeUsername = htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8');

        $htmlBody = "
        <div style='margin:0; padding:0; background:#f4f6f8;'>
          <div style='max-width:640px; margin:0 auto; padding:24px; font-family:Arial, Helvetica, sans-serif; color:#1f2937;'>
            <div style='background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;'>

              <!-- En-tête -->
              <div style='padding:18px 22px; background:#0b1a2e; color:#ffffff;'>
                <div style='font-size:16px; font-weight:700; letter-spacing:.2px;'>
                  {$appName}
                </div>
                <div style='font-size:13px; opacity:.9; margin-top:4px;'>
                  Username reminder
                </div>
              </div>

              <!-- Corps du mail -->
              <div style='padding:22px;'>
                <h2 style='margin:0 0 12px; font-size:20px; line-height:1.3; color:#111827;'>
                  Your username
                </h2>

                <p style='margin:0 0 12px; font-size:14px; line-height:1.7;'>
                  Hello <strong>{$safeName}</strong>,
                </p>

                <p style='margin:0 0 16px; font-size:14px; line-height:1.7;'>
                  You recently requested a reminder of your username for your <strong>{$appName}</strong> account. Here it is:
                </p>

                <!-- Bloc username -->
                <div style='margin:18px 0; padding:16px 20px; background:#f0f4ff; border:1px solid #c7d7f9; border-radius:10px; text-align:center;'>
                  <p style='margin:0 0 6px; font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:.8px;'>
                    Your username
                  </p>
                  <p style='margin:0; font-size:22px; font-weight:700; color:#0b5ed7; letter-spacing:.5px;'>
                    {$safeUsername}
                  </p>
                </div>

                <p style='margin:0 0 16px; font-size:14px; line-height:1.7;'>
                  You can use this username to log in to your account.
                </p>

                <div style='padding:12px 14px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px;'>
                  <p style='margin:0; font-size:13px; line-height:1.7; color:#374151;'>
                    If you didn't request this reminder, you can safely ignore this email — no changes have been made to your account.
                  </p>
                </div>

                <p style='margin:18px 0 0; font-size:14px; line-height:1.7;'>
                  Best regards,<br>
                  <strong>{$appName} Team</strong>
                </p>
              </div>

              <!-- Pied de page -->
              <div style='padding:14px 22px; background:#ffffff; border-top:1px solid #e5e7eb;'>
                <p style='margin:0; font-size:12px; line-height:1.6; color:#6b7280;'>
                  This is an automated message. Please do not reply.
                </p>
              </div>

            </div>
          </div>
        </div>
        ";

        return Yii::$app->mailer->compose()
            ->setTo($this->email)
            ->setFrom([
                Yii::$app->params['supportEmail'] ?? 'no-reply@coreaviationnetwork.com' => Yii::$app->name,
            ])
            ->setSubject('Your Username Reminder - ' . Yii::$app->name)
            ->setHtmlBody($htmlBody)
            ->send();
    }
}
<?php

namespace app\components;

use Yii;
use yii\mail\MessageInterface;

/** Shared password reset email for self-service and administrative requests. */
final class PasswordResetEmail
{
    public static function compose($user, string $publicToken): MessageInterface
    {
        $resetLink = Yii::$app->urlManager->createAbsoluteUrl([
            'site/reset-password',
            'token' => $publicToken,
        ]);

        if (!empty($user->first_name) || !empty($user->last_name)) {
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        } elseif (!empty($user->name)) {
            $fullName = $user->name;
        } elseif (!empty($user->username)) {
            $fullName = $user->username;
        } else {
            $fullName = $user->email ?: 'User';
        }

        $html = Yii::$app->mailer->render('password-reset-html', [
            'appName' => htmlspecialchars(Yii::$app->name, ENT_QUOTES, 'UTF-8'),
            'safeName' => htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'),
            'safeLink' => htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8'),
        ]);

        return Yii::$app->mailer->compose()
            ->setTo($user->email)
            ->setFrom([
                Yii::$app->params['supportEmail'] ?? 'no-reply@coreaviationnetwork.com' => Yii::$app->name,
            ])
            ->setSubject('Password Reset Request - ' . Yii::$app->name)
            ->setHtmlBody($html)
            ->setTextBody("Hello {$fullName},\n\nFollow the link to reset your password:\n{$resetLink}\n\nThis link will expire after 60 minutes. If you did not request a password reset, you can safely ignore this email.\n\n" . Yii::$app->name . ' Team');
    }
}

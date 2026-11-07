<?php

namespace app\components;

use app\models\PrelaunchSubscriber;
use Yii;

class PrelaunchConfirmationMailer
{
    public static function send(
        PrelaunchSubscriber $subscriber,
        $confirmationToken,
        $unsubscribeToken
    ) {
        $confirmationUrl = Yii::$app->urlManager->createAbsoluteUrl([
            '/site/confirm-prelaunch',
            'token' => $confirmationToken,
        ]);
        $unsubscribeUrl = Yii::$app->urlManager->createAbsoluteUrl([
            '/site/unsubscribe-prelaunch',
            'token' => $unsubscribeToken,
        ]);

        try {
            $sent = (bool) Yii::$app->mailer->compose(
                [
                    'html' => 'prelaunch-confirmation-html',
                    'text' => 'prelaunch-confirmation-text',
                ],
                [
                    'subscriber' => $subscriber,
                    'confirmationUrl' => $confirmationUrl,
                    'unsubscribeUrl' => $unsubscribeUrl,
                ]
            )
                ->setTo($subscriber->business_email)
                ->setSubject('Verify your email for Core Aviation Network early access')
                ->send();

            return [
                'sent' => $sent,
                'error' => $sent
                    ? null
                    : 'SMTP transport returned no delivery confirmation. Review the protected application log.',
            ];
        } catch (\Throwable $exception) {
            Yii::warning([
                'message' => 'Prelaunch confirmation email could not be sent.',
                'subscriberId' => (int) $subscriber->id,
                'exceptionClass' => get_class($exception),
            ], __METHOD__);
            return [
                'sent' => false,
                'error' => 'Mailer exception (' . get_class($exception) . '). Review the protected application log.',
            ];
        }
    }

}

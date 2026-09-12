<?php
namespace app\components;

use Yii;
use app\models\Appointment;
use yii\base\Component;

class NotificationManager extends Component
{
    public static function sendNotifications()
    {
        $now = new \DateTime();
        $appointments = Appointment::find()
            ->where(['notification_cancelled' => 0])
            ->andWhere(['>', 'start_time', $now->format('Y-m-d H:i:s')])
            ->all();

        foreach ($appointments as $appointment) {
            $startTime = new \DateTime($appointment->start_time);
            $interval = new \DateInterval('PT' . $appointment->notification_recurrence . 'H');
            $notifyTime = clone $startTime;
            $notifyTime->sub($interval);

            if ($now >= $notifyTime && !$appointment->notification_sent) {
                // Send the notification
                Yii::$app->mailer->compose('appointment_reminder', ['appointment' => $appointment])
                    ->setTo($appointment->user->email)
                    ->setSubject('Appointment Reminder')
                    ->send();

                $appointment->notification_sent = 1;
                $appointment->save();
            }
        }
    }
}

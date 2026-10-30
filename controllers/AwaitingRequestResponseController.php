<?php

namespace app\controllers;

use app\models\AoNotificationsPreferences;
use app\models\AoProfile;
use app\models\MroNotificationsPreferences;
use app\models\MroProfile;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\User;
use app\models\RepairReport;
use yii\data\ActiveDataProvider;
use yii\helpers\VarDumper;
use yii\web\NotFoundHttpException;

class AwaitingRequestResponseController extends Controller
{

    /** Protect every action, including administrative reminders, before business queries. */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [[
                    'allow' => true,
                    'roles' => ['@'],
                    'matchCallback' => static function () {
                        $identity = Yii::$app->user->identity;
                        return $identity instanceof User && (int) $identity->admin_id > 0;
                    },
                ]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET', 'HEAD'],
                    'ban-ao' => ['POST'],
                    'unban-ao' => ['POST'],
                    'ban-mro' => ['POST'],
                    'unban-mro' => ['POST'],
                    'notify-ao' => ['POST'],
                    'notify-mro' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $threeDaysAgo = date('Y-m-d', strtotime('-3 days'));
    
        // Query for reports awaiting MRO to send new report
        $reportsAwaitingMRO = RepairReport::find()
            ->where(['<', 'updated_at', $threeDaysAgo])
            ->andWhere(['quote_approved' => 0])
            ->all();
    
        // Query for reports awaiting AO to submit approvals
        $reportsAwaitingAO = RepairReport::find()
            ->where(['<', 'updated_at', $threeDaysAgo])
            ->andWhere(['quote_approved' => null ])
            ->all();
   // VarDumper::dump($reportsAwaitingAO);die();
        // Define statuses based on conditions
        $statusAwaitingMRO = 'Awaiting MRO to send new report';
        $statusAwaitingAO = 'Awaiting AO to submit approvals';
    
        return $this->render('index', [
            'reportsAwaitingMRO' => $reportsAwaitingMRO,
            'reportsAwaitingAO' => $reportsAwaitingAO,
            'statusAwaitingMRO' => $statusAwaitingMRO,
            'statusAwaitingAO' => $statusAwaitingAO,
        ]);
    }


    public function actionBanAo($id)
    {
        $ao = AoProfile::findOne($id);
        if (!$ao) {
            throw new NotFoundHttpException('The requested AO profile does not exist.');
        }
    
        // Mise a jour sans validation metier, mais via ActiveRecord afin de conserver l'audit.
        $result = $ao->updateAttributes(['status' => 'banned']);
    
        if ($result) {
            Yii::$app->session->setFlash('success', 'AO has been banned successfully.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to ban AO. Please try again.');
        }
    
        return $this->redirect(['index']);
    }
    public function actionUnbanAo($id)
    {
        $ao = AoProfile::findOne($id);
        if (!$ao) {
            throw new NotFoundHttpException('The requested AO profile does not exist.');
        }
    
        // Mise a jour sans validation metier, mais via ActiveRecord afin de conserver l'audit.
        $result = $ao->updateAttributes(['status' => 'active']);
    
        if ($result) {
            Yii::$app->session->setFlash('success', 'AO has been unbanned successfully.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to unban AO. Please try again.');
        }
    
        return $this->redirect(['index']);
    }
    

    /**
     * Ban MRO by setting status to 'banned'.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if MRO profile is not found
     */
    public function actionBanMro($id)
    {
        $mro = MroProfile::findOne($id);
        if (!$mro) {
            throw new NotFoundHttpException('The requested MRO profile does not exist.');
        }
        $mro->status = 'banned'; // Update status to 'banned'
        $mro->confirm_password = $mro->password; // Update status to 'banned'

        if ($mro->save()) {

            Yii::$app->session->setFlash('success', 'MRO has been banned successfully.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to ban MRO. Please try again.');
        }

        return $this->redirect(['index']);
    }
    public function actionUnbanMro($id)
    {
        $mro = MroProfile::findOne($id);
        if (!$mro) {
            throw new NotFoundHttpException('The requested MRO profile does not exist.');
        }
        $mro->status = 'active'; // Update status to 'banned'
        $mro->confirm_password = $mro->password; // Update status to 'banned'

        if ($mro->save()) {

            Yii::$app->session->setFlash('success', 'MRO has been unbanned successfully.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to unban MRO. Please try again.');
        }

        return $this->redirect(['index']);
    }


    public function actionNotifyMro($id)
    {
        $mro = MroProfile::findOne($id);
        if (!$mro) {
            throw new NotFoundHttpException('Profile not found.');
        }
        $emailSent = null;
        $platformSaved = null;
        
        $notificationPreferences = MroNotificationsPreferences::findOne(['mro_id' => $mro->mro_id]);
        if ($notificationPreferences && $notificationPreferences->notify_by_email) {
            $emailSent = Yii::$app->mailer->compose('awaiting_mro_send_new_crs', ['mro' => $mro])
            ->setTo($mro->email)
            
            ->setSubject('Awaiting new CRS')
            ->send();
        }
          //platform notification
          if ($notificationPreferences && $notificationPreferences->notify_by_platform) {
            // Call the action to save the notification
                $platformSaved = Yii::$app->runAction('notification/save-notification', [
                    'recipientType' => 'mro',
                    'recipientId' => $mro->mro_id,
                    'message' => 'Awaiting for your new CRS',
                    'actions' => Yii::$app->request->baseUrl . '/mro-applications,' // Or any route you want

                    
                ]);
            }

        $this->recordReminderAudit($mro, $emailSent, $platformSaved);

        Yii::$app->session->setFlash('success', 'Notification sent to Mro successfully.');

        return $this->redirect(['index']);
    }

    public function actionNotifyAo($id)
    {
        $ao = AoProfile::findOne($id);
        if (!$ao) {
            throw new NotFoundHttpException('Profile not found.');
        }
        $emailSent = null;
        $platformSaved = null;
        
        $notificationPreferences = AoNotificationsPreferences::findOne(['ao_id' => $ao->ao_id]);
        if ($notificationPreferences && $notificationPreferences->notify_by_email) {
            $emailSent = Yii::$app->mailer->compose('awaiting_ao_crs_response', ['ao' => $ao])
            ->setTo($ao->email)
            
            ->setSubject('Awaiting CRS Response')
            ->send();
        }
          //platform notification
          if ($notificationPreferences && $notificationPreferences->notify_by_platform) {
            // Call the action to save the notification
                $platformSaved = Yii::$app->runAction('notification/save-notification', [
                    'recipientType' => 'ao',
                    'recipientId' => $ao->ao_id,
                    'message' => 'Awaiting for your response to CRS',
                     'actions' => Yii::$app->request->baseUrl . '/requests/open-requests,' // Or any route you want

                ]);
            } 

        $this->recordReminderAudit($ao, $emailSent, $platformSaved);

        Yii::$app->session->setFlash('success', 'Notification sent to Ao successfully.');

        return $this->redirect(['index']);
    }
    
    /** Status changes are already captured by AuditTrailBootstrap; reminders need an explicit event. */
    private function recordReminderAudit($profile, $emailSent, $platformSaved): void
    {
        Yii::$app->auditService->record('ADMIN_REMINDER', [
            'model' => get_class($profile),
            'record_id' => $profile->getPrimaryKey(),
            'new_values' => [
                'operation' => $this->action->id,
                'email_sent' => $emailSent,
                'platform_saved' => $platformSaved,
            ],
        ]);
    }
}

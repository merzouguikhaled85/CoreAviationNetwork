<?php

namespace app\controllers;

use app\models\AoNotificationsPreferences;
use app\models\AoProfile;
use app\models\MroNotificationsPreferences;
use app\models\MroProfile;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\models\RepairReport;
use yii\data\ActiveDataProvider;
use yii\helpers\VarDumper;
use yii\web\NotFoundHttpException;

class AwaitingRequestResponseController extends Controller
{


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
    
        // Directly update the status in the database without triggering Yii validation
        $result = Yii::$app->db->createCommand()
            ->update('ao_profiles', ['status' => 'banned'], ['ao_id' => $id])
            ->execute();
    
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
    
        // Directly update the status in the database without triggering Yii validation
        $result = Yii::$app->db->createCommand()
            ->update('ao_profiles', ['status' => 'active'], ['ao_id' => $id])
            ->execute();
    
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
        
        $notificationPreferences = MroNotificationsPreferences::findOne(['mro_id' => $mro->mro_id]);
        if ($notificationPreferences && $notificationPreferences->notify_by_email) {
            Yii::$app->mailer->compose('awaiting_mro_send_new_crs', ['mro' => $mro])
            ->setTo($mro->email)
            
            ->setSubject('Awaiting new CRS')
            ->send();
        }
          //platform notification
          if ($notificationPreferences && $notificationPreferences->notify_by_platform) {
            // Call the action to save the notification
                Yii::$app->runAction('notification/save-notification', [
                    'recipientType' => 'mro',
                    'recipientId' => $mro->mro_id,
                    'message' => 'Awaiting for your new CRS',
                    'actions' => Yii::$app->request->baseUrl . '/mro-applications,' // Or any route you want

                    
                ]);
            }

        Yii::$app->session->setFlash('success', 'Notification sent to Mro successfully.');

        return $this->redirect(['index']);
    }

    public function actionNotifyAo($id)
    {
        $ao = AoProfile::findOne($id);
        
        $notificationPreferences = AoNotificationsPreferences::findOne(['ao_id' => $ao->ao_id]);
        if ($notificationPreferences && $notificationPreferences->notify_by_email) {
            Yii::$app->mailer->compose('awaiting_ao_crs_response', ['ao' => $ao])
            ->setTo($ao->email)
            
            ->setSubject('Awaiting CRS Response')
            ->send();
        }
          //platform notification
          if ($notificationPreferences && $notificationPreferences->notify_by_platform) {
            // Call the action to save the notification
                Yii::$app->runAction('notification/save-notification', [
                    'recipientType' => 'ao',
                    'recipientId' => $ao->ao_id,
                    'message' => 'Awaiting for your response to CRS',
                     'actions' => Yii::$app->request->baseUrl . '/requests/open-requests,' // Or any route you want

                ]);
            } 

        Yii::$app->session->setFlash('success', 'Notification sent to Ao successfully.');

        return $this->redirect(['index']);
    }
    
}

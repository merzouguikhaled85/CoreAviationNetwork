<?php

namespace app\controllers;

use app\models\AoProfile;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use app\models\Dispute;
use app\models\MroProfile;
use app\components\UrlIdHelper;
use yii\helpers\VarDumper;

class AdminDisputesController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            // Allow access only to users with specific user types
                            return in_array(Yii::$app->session->get('user_type'), ['admin']);
                        }
                    ],
                ],
            ],
        ];
    }


    
    public function actionIndex()
    {
        $search = Yii::$app->request->get('search');
        
        $query = Dispute::find()
            ->leftJoin('ao_profiles', 'disputes.ao_id = ao_profiles.ao_id')
            ->leftJoin('mro_profiles', 'disputes.mro_id = mro_profiles.mro_id')
            ->leftJoin('requests', 'disputes.request_id = requests.request_id');
        
        // Apply search filter if search query is provided
        if ($search !== null) {
            $query->andFilterWhere(['like', 'disputes.description', $search])
                ->orFilterWhere(['like', 'disputes.status', $search])
                ->orFilterWhere(['like', 'disputes.created_by', $search])
                ->orFilterWhere(['like', 'disputes.admin_response', $search]);
        }
        
        $countQuery = clone $query;
        $totalCount = $countQuery->count();
        
        $pagination = new Pagination(['totalCount' => $totalCount, 'pageSize' => 20]);
        
        $disputes = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
        
        return $this->render('index', [
            'disputes' => $disputes,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Display the complete information for one dispute.
     */
    public function actionView($id)
    {
        // SIGNED DISPUTE ID: decode once at the controller boundary.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid dispute link.');
        return $this->render('view', [
            'dispute' => $this->findModel($id),
        ]);
    }

    public function actionDelete($id)
    {
        // SIGNED DISPUTE ID: decode once at the controller boundary.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid dispute link.');
        $this->findModel($id)->delete();

        Yii::$app->session->setFlash('success', 'Dispute deleted successfully.');

        return $this->redirect(['index']);
    }



    public function actionReply($id)
    {
        // SIGNED DISPUTE ID: decode once at the controller boundary.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid dispute link.');
        $dispute = $this->findModel($id);
    
        if (Yii::$app->request->isPost) {
            // Load the POST data into the dispute model
            $dispute->load(Yii::$app->request->post());
    
            // Optionally validate the model before saving
            if ($dispute->validate()) {
                // Save the updated dispute model with admin response
                if ($dispute->save()) {

                    Yii::$app->mailer->compose('ao_admin_dispute_respose', ['dispute' => $dispute])
                    ->setTo($dispute->getAo()->one()->email)
                    
                    ->setSubject('admin dispute respose')
                    ->send();
            
                      //platform notification
                        // Call the action to save the notification
 Yii::$app->runAction('notification/save-notification', [
    'recipientType' => 'ao',
    'recipientId' => $dispute->ao_id,
    'message' => 'Admin dispute response received for request #' . $dispute->request_id . '.',
    'actions' => Yii::$app->request->baseUrl . '/disputes',
]);

                        Yii::$app->mailer->compose('mro_admin_dispute_respose', ['dispute' => $dispute])
                        ->setTo($dispute->getMro()->one()->email)
                        
                        ->setSubject('admin dispute respose')
                        ->send();
                
                          //platform notification
                            // Call the action to save the notification
                            Yii::$app->runAction('notification/save-notification', [
                                'recipientType' => 'mro',
                                'recipientId' => $dispute->mro_id,
                                   'message' => 'Admin dispute response received for request #' . $dispute->request_id . '.',
                                                         'actions' => Yii::$app->request->baseUrl . '/disputes', // Or any route you want

                            ]);



                    Yii::$app->session->setFlash('success', 'Admin response submitted successfully.');
                    return $this->redirect(['index']);
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to save admin response.');
                }
            }
        }
    
        return $this->render('reply', [
            'dispute' => $dispute,
        ]);
    }
    
    

    protected function findModel($id)
    {
        if (($model = Dispute::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


        /**
     * Ban AO by setting status to 'banned'.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if AO profile is not found
     */
    public function actionBanAo($id)
    {
        // SIGNED PROFILE ID: administrative actions no longer expose the AO primary key.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid AO profile link.');
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
        // SIGNED PROFILE ID: administrative actions no longer expose the AO primary key.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid AO profile link.');
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
        // SIGNED PROFILE ID: administrative actions no longer expose the MRO primary key.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid MRO profile link.');
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
        // SIGNED PROFILE ID: administrative actions no longer expose the MRO primary key.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid MRO profile link.');
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
}

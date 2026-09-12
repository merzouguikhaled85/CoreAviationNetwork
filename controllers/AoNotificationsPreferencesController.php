<?php

namespace app\controllers;

use Yii;
use app\models\AoNotificationsPreferences;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\Pagination;
use yii\helpers\VarDumper;

class AoNotificationsPreferencesController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
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
                            return in_array(Yii::$app->session->get('user_type'), ['ao']);
                        }
                    ],
                ],
            ],
        ];
    }
  

    /**
     * Lists all AoNotificationsPreferences models.
     * @return mixed
     */
public function actionIndex()
{
    $ao_id = Yii::$app->session->get('ao_id'); // Adjust as per your session setup

    // Check if the AO ID is present in the session
    if ($ao_id === null) {
        throw new NotFoundHttpException('AO ID is not set in the session.');
    }

    // Try to find the existing notification preference record
    $model = AoNotificationsPreferences::find()->where(['ao_id' => $ao_id])->one();

    // If no existing record, create a new model instance
    if (!$model) {
        $model = new AoNotificationsPreferences();
        $model->ao_id = $ao_id; // Set the ao_id for the new model
        
        // Set default notification preferences
        $model->notify_by_email = 1; 
        $model->notify_by_platform = 1; 
        $model->notify_mro_replies = 1; 
        $model->notify_mro_recommendations = '1'; 
        $model->notify_po_acceptance = 1; 
        $model->notify_maintenance_proposals = 1; 
        $model->notify_work_start = 1; 
        $model->notify_report_submissions = 1; 
        $model->notify_appointment_requests = 1; 
        // Save the new model to the database
        if (!$model->save()) {
            VarDumper::dump($model );die();

        }

        // Redirect to the update view for the newly created model
        return $this->redirect(['update', 'id' => $model->id]); // Use the id of the new model
    }

    // Load data and save the model if it's being updated
    if ($model->load(Yii::$app->request->post()) && $model->save()) {
        Yii::$app->session->setFlash('success', 'Notification preference updated successfully.');
        return $this->redirect(['update', 'id' => $model->id]); // Use the model's id
    }

    // Render the update view with the model
    return $this->render('update', [
        'model' => $model,
    ]);
}


    /**
     * Displays a single AoNotificationsPreferences model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new AoNotificationsPreferences model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $ao_id = Yii::$app->session->get('ao_id'); // Adjust as per your session setup

        $model = new AoNotificationsPreferences();
        $model->ao_id = $ao_id;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Notification preference created successfully.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing AoNotificationsPreferences model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Notification preference updated successfully.');
            return $this->redirect(['update', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing AoNotificationsPreferences model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Notification preference deleted successfully.');

        return $this->redirect(['index']);
    }

    /**
     * Finds the AoNotificationsPreferences model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return AoNotificationsPreferences the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AoNotificationsPreferences::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

<?php

namespace app\controllers;

use Yii;
use app\models\MroNotificationsPreferences;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\Pagination;
use yii\helpers\VarDumper;

class MroNotificationsPreferencesController extends Controller
{



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
                            return in_array(Yii::$app->session->get('user_type'), ['mro']);
                        }
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all MroNotificationsPreferences models.
     * @return mixed
     */
    public function actionIndex()
    {
        $mro_id = Yii::$app->session->get('mro_id');

        // Find the record in MroNotificationsPreferences where mro_id matches the session value and id matches the provided id
        $model = MroNotificationsPreferences::find()
            ->where(['mro_id' => $mro_id])
            ->one();
        


    // If no existing record, create a new model instance
    if (!$model) {
        $model = new MroNotificationsPreferences();
        $model->mro_id = $mro_id; // Set the ao_id for the new model
        
        // Set default notification preferences
        $model->notify_by_email = 1; 
        $model->notify_by_platform = 1; 
        $model->notify_for_non_certified_aircraft = 0; 
        $model->notify_for_appointment_acceptance = '1'; 
        $model->notify_for_feedback = 1; 
       
        // Save the new model to the database
        if (!$model->save()) {
            VarDumper::dump($model );die();

        }
    }
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Notification preference updated successfully.');
            return $this->redirect(['update', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);


      /*  $mro_id = Yii::$app->session->get('mro_id');
    
        $query = MroNotificationsPreferences::find()->where(['mro_id' => $mro_id]);
        $pagination = new Pagination([
            'defaultPageSize' => 10, // Adjust the page size as needed
            'totalCount' => $query->count(),
        ]);
        $models = $query->offset($pagination->offset)
                        ->limit($pagination->limit)
                        ->all();
    
        if (empty($models)) {
            // Redirect to create action if no preferences found
            return $this->redirect(['create']);
        }
    
        return $this->render('index', [
            'preferences' => $models,
            'pagination' => $pagination,
        ]);*/
    }
    
    

    /**
     * Displays a single MroNotificationsPreferences model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new MroNotificationsPreferences model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $mro_id = Yii::$app->session->get('mro_id');

        $model = new MroNotificationsPreferences();
        $model->mro_id = $mro_id;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Notification preference created successfully.');
            return $this->redirect(['update', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing MroNotificationsPreferences model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
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
     * Deletes an existing MroNotificationsPreferences model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Notification preference deleted successfully.');

        return $this->redirect(['index']);
    }

    /**
     * Finds the MroNotificationsPreferences model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return MroNotificationsPreferences the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = MroNotificationsPreferences::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

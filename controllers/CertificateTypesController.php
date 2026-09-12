<?php

namespace app\controllers;

use app\models\AircraftModel;
use Yii;
use app\models\CertificateTypes;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;

/**
 * CertificateTypesController implements the CRUD actions for CertificateTypes model.
 */
class CertificateTypesController extends Controller
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
           
        ];
    }

    /**
     * Lists all CertificateTypes models.
     * @return mixed
     */
    public function actionIndex($search = null)
    {
        $query = CertificateTypes::find()->orderBy(['type' => SORT_ASC]);

        if ($search !== null) {
            // Get all aircraft model IDs that match the search criteria
            $matchingAircraftModels = AircraftModel::find()
                ->where(['like', 'manufacturer', $search])
                ->orWhere(['like', 'model', $search])
                ->all();
        
            $matchingAircraftModelIds = array_map(function($model) {
                return $model->aircraft_model_id;
            }, $matchingAircraftModels);
        
            // Initialize the query condition
            $condition = ['or'];
        
            // Add the type condition if search string is provided
            if (!empty($search)) {
                $condition[] = ['like', 'type', $search];
            }
        

        
            // Apply the combined condition to the main query
            $query->andWhere($condition);
      

        }
        
        
        $countQuery = clone $query;
        $totalCount = $countQuery->count();

        $pagination = new Pagination(['totalCount' => $totalCount]);
        $pagination->pageSize = 20; // Adjust the page size as needed

        $certificateTypes = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('index', [
            'certificateTypes' => $certificateTypes,
            'pagination' => $pagination,
            'search' => $search,
        ]);
    }


    /**
     * Displays a single CertificateTypes model.
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
     * Creates a new CertificateTypes model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new CertificateTypes();

        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                Yii::$app->session->setFlash('message', 'Certificate Type created successfully.');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Error creating Certificate Type.');
            }
        }


        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing CertificateTypes model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
    
        // Check if the form is submitted and load post data into the model
        if ($model->load(Yii::$app->request->post())) {
            // Convert selected aircraft model IDs to comma-separated string
            
            // Validate and save the model
            if ($model->save()) {
                Yii::$app->session->setFlash('message', 'Certificate Type Updated successfully.');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Error Updating Certificate Type.');
            }
        }
    
     
    
        return $this->render('update', [
            'model' => $model,

        ]);
    }

    /**
     * Deletes an existing CertificateTypes model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('message', 'Certificate Type Deleted successfully.');

        return $this->redirect(['index']);
    }

    /**
     * Finds the CertificateTypes model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CertificateTypes the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CertificateTypes::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
    public function actionGetCertificateTypeId($type)
{
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    
    $certificateType = \app\models\CertificateTypes::find()
        ->select('certificate_type_id')
        ->where(['type' => $type])
        ->one();

    if ($certificateType !== null) {
        return ['certificate_type_id' => $certificateType->certificate_type_id];
    } else {
        return ['certificate_type_id' => null];
    }
}

}

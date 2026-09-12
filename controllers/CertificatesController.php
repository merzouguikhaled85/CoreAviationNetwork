<?php

namespace app\controllers;

use app\models\AircraftModel;
use app\models\Aircrafts;
use Yii;
use app\models\Certificates;
use app\models\CertificateTypes;
use app\models\MroProfile;

use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\web\UploadedFile;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;

/**
 * CertificateController implements the CRUD actions for Certificate model.
 */
class CertificatesController extends Controller
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
                            return in_array(Yii::$app->session->get('user_type'), ['admin']);
                        }
                    ],
                ],
            ],
        ];
    }



    /**
     * Lists all Certificate models.
     * @param string|null $search
     * @return mixed
     */
    public function actionIndex($search = null)
    {
        $query = Certificates::find()->with('mro')->orderBy(['type' => SORT_ASC]);

        // Apply search filter if search query is provided
        if ($search !== null) {
            $query->andFilterWhere(['like', 'type', $search])
                  ->orFilterWhere(['like', 'certificate', $search]);
        }

        $countQuery = clone $query;
        $totalCount = $countQuery->count();

        $pagination = new Pagination(['totalCount' => $totalCount]);
        $pagination->pageSize = 20; // Adjust the page size as needed

        $certificates = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('index', [
            'certificates' => $certificates,
            'pagination' => $pagination,
            'search' => $search,
        ]);
    }

    /**
     * Displays a single Certificate model.
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
     * Creates a new Certificate model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $certificate = new Certificates();
        $mros = MroProfile::find()->orderBy(['username' => SORT_ASC])->all(); // Assuming Mro is the model for MROs, adjust as per your actual model name

        if ($certificate->load(Yii::$app->request->post())) {
            // Get the instance of the uploaded file
            $certificateFile = UploadedFile::getInstance($certificate, 'certificate');
            $certificate->type = Yii::$app->request->post('Certificates')['type'];
            $certificate->certificate_type_id = Yii::$app->request->post('Certificates')['certificate_type_id'];

            // Set the file path for the uploaded certificate file
            if ($certificateFile) {
                $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $certificateFile->extension;
                $certificateFile->saveAs($uploadPath);
                $certificate->certificate = $uploadPath;
              
            }
    
            // Check if the model is valid before saving
            if ( $certificate->save()) {
                Yii::$app->getSession()->setFlash('message', 'Certificate Added Successfully!');
                return $this->redirect(['index']);
            } else {

                Yii::$app->getSession()->setFlash('error', 'Failed to add certificate: ' . json_encode($certificate));
            }

        }
    
        return $this->render('create', [
            'certificate' => $certificate,
            'mros' => $mros, // Pass the $mros variable to the view
        ]);
    }
    

    /**
     * Updates an existing Certificate model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $certificate = Certificates::findOne($id);
        if ($certificate === null) {
            throw new NotFoundHttpException('The requested certificate does not exist.');
        }
    
        $mros = MroProfile::find()->orderBy(['username' => SORT_ASC])->all(); // Assuming Mro is the model for MROs, adjust as per your actual model name

        if ($certificate->load(Yii::$app->request->post())) {
            // Get the instance of the uploaded file
            $certificateFile = UploadedFile::getInstance($certificate, 'certificate');
            $certificate->type = Yii::$app->request->post('Certificates')['type'];
            $certificate->certificate_type_id = Yii::$app->request->post('Certificates')['certificate_type_id'];

            // Set the file path for the uploaded certificate file
            if ($certificateFile) {
                $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $certificateFile->extension;
                $certificateFile->saveAs($uploadPath);
                $certificate->certificate = $uploadPath;
              
            }
    
            // Check if the model is valid before saving
            if ( $certificate->save()) {
                Yii::$app->getSession()->setFlash('message', 'Certificate Updated Successfully!');
                return $this->redirect(['index']);
            } else {

                Yii::$app->getSession()->setFlash('error', 'Failed to update certificate: ' . json_encode($certificate));
            }

        }
    
        return $this->render('update', [
            'certificate' => $certificate,
            'mros' => $mros,
        ]);
    }
    

    /**
     * Deletes an existing Certificate model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Certificate model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Certificate the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Certificates::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionModelsWithoutCertificates($search = null)
    {
        // Subquery to get all unique certificate_type_id values from Certificates
        $subquery = Certificates::find()
            ->select('certificate_type_id')
            ->distinct();
    
        // Query to find all unique aircraft_model_ids from CertificateTypes
        $subquery2 = Aircrafts::find()
            ->select('aircraft_model_id')
            ->where(['in', 'certificate_type_id', $subquery])
            ->asArray()
            ->all();
    
  /*      // Extract all aircraft_model_ids from the subquery result
        $existingAircraftModelIds = [];
        foreach ($subquery2 as $item) {
            $ids = $item['aircraft_model_id'];
            $existingAircraftModelIds = array_push($existingAircraftModelIds, $ids);
        }
    
        // Remove duplicates from the array of aircraft_model_ids
        $existingAircraftModelIds = array_unique($existingAircraftModelIds);
    */
        // Query to find all AircraftModels that are not in the list of existing aircraft_model_ids
        $query = AircraftModel::find()
            ->where(['not in', 'aircraft_model_id', $subquery2]);
    
        // Apply search criteria if provided
        if ($search !== null) {
            $query->andWhere(['or',
                ['like', 'manufacturer', $search],
                ['like', 'model', $search],
            ]);
        }
    
        // Fetch the models matching the criteria
        $models = $query->orderBy(['manufacturer' => SORT_ASC])->all();
    
        return $this->render('models-without-certificates', [
            'models' => $models,
            'search' => $search,  // Pass $search instead of $searchModel
        ]);
    }
    
    
    
    
}

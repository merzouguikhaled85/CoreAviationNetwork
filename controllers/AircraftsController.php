<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\Aircrafts;
use yii\data\Pagination;
use app\models\AoProfile;
use app\models\AircraftModel;
use yii\helpers\VarDumper;

class AircraftsController extends Controller
{
    public function behaviors()
    {
        return [
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
        // Define the query with eager loading of owner information
        $query = Aircrafts::find()->with('owner')->orderBy(['model' => SORT_ASC]);
    
        // Create a pagination object with a total count and a limit of 20 per page
        $pagination = new Pagination([
            'defaultPageSize' => 20,
            'totalCount' => $query->count(),
        ]);
    
        // Adjust the query using the pagination object
        $aircraftModels = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
    
        // Render the view, passing the aircraftModels and pagination objects
        return $this->render('index', [
            'aircraftModels' => $aircraftModels,
            'pagination' => $pagination,
        ]);
    }
    
    public function actionView($id)
    {
        $aircraftModel = Aircrafts::findOne($id);
        
        // Check if the aircraft model exists
        if ($aircraftModel) {
            // Fetch the owner model if it exists
            $ownerModel = $aircraftModel->getOwner()->one();
            // Fetch the owner's username if the owner model exists
            $ownerUsername = $ownerModel ? $ownerModel->username : null;
        } else {
            // If the aircraft model doesn't exist, set $ownerUsername to null
            $ownerUsername = null;
        }
    
        return $this->render('view', [
            'aircraftModel' => $aircraftModel,
            'ownerUsername' => $ownerUsername,
        ]);
    }
    
    
    
    
    public function actionCreate()
    {
        $aircraftModel = new Aircrafts();
        $owners = AoProfile::find()->orderBy(['username' => SORT_ASC])->all(); // Fetch all owners
        $manufacturers = AircraftModel::find()->select('manufacturer')->distinct()->orderBy(['manufacturer' => SORT_ASC])->all(); // Fetch all distinct manufacturers from the aircraft_model table
        $models = AircraftModel::find()->orderBy(['model' => SORT_ASC])->all(); // Fetch all models from the aircraft_model table
        
        if ($aircraftModel->load(Yii::$app->request->post()) ) {
            $aircraftModel->aircraft_model_id = AircraftModel::find()
                ->select('aircraft_model_id') // Select the aircraft_model_id
                ->where(['model' => $aircraftModel->model]) // Match on model
                ->andWhere(['manufacturer' => $aircraftModel->manufacturer]) // Match on manufacturer
                ->scalar(); // Get the scalar value (aircraft_model_id)
                    $aircraftModel->save();
            Yii::$app->session->setFlash('message', 'Aircraft Model created successfully!');
            return $this->redirect(['index']);
        }
    
        return $this->render('create', [
            'aircraftModel' => $aircraftModel,
            'owners' => $owners, // Pass the owners to the view
            'manufacturers' => $manufacturers, // Pass the manufacturers to the view
            'models' => $models, // Pass the models to the view
        ]);
    }
    
    
    

    public function actionUpdate($id)
{
    $aircraftModel = Aircrafts::findOne($id);
    $owners = AoProfile::find()->all(); // Fetch all owners
    $manufacturers = AircraftModel::find()->select('manufacturer')->distinct()->all(); // Fetch all distinct manufacturers from the aircraft_model table
    $models = AircraftModel::find()->all(); // Fetch all models from the aircraft_model table
    
    if ($aircraftModel->load(Yii::$app->request->post()) && $aircraftModel->save()) {
        Yii::$app->session->setFlash('message', 'Aircraft Model updated successfully!');
        return $this->redirect(['index']);
    }

    return $this->render('update', [
        'aircraftModel' => $aircraftModel,
        'owners' => $owners, // Pass the owners to the view
        'manufacturers' => $manufacturers, // Pass the manufacturers to the view
        'models' => $models, // Pass the models to the view
    ]);
}


    public function actionDelete($id)
    {
        $aircraftModel = Aircrafts::findOne($id);
        if ($aircraftModel->delete()) {
            Yii::$app->session->setFlash('message', 'Aircraft Model deleted successfully!');
        } else {
            Yii::$app->session->setFlash('message', 'Failed to delete Aircraft Model!');
        }
        return $this->redirect(['index']);
    }
}

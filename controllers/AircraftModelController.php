<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use app\models\AircraftModel;
use yii\data\Pagination;

class AircraftModelController extends Controller
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
            // Une suppression ne doit jamais être déclenchée par une URL GET.
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['delete' => ['POST']],
            ],
        ];
    }
    public function actionIndex()
    {
        $searchModel = Yii::$app->request->get('search');
        $query = AircraftModel::find()->orderBy(['manufacturer' => SORT_ASC]); // Sort by model A to Z
    
        // Apply search filter if the search parameter is provided
        if ($searchModel) {
            $query->andFilterWhere(['like', 'manufacturer', $searchModel])
                  ->orFilterWhere(['like', 'model', $searchModel]);
        }
    
        $pagination = new Pagination([
            'defaultPageSize' => 20,
            'totalCount' => $query->count(),
        ]);
    
        $aircraftModels = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
    
        return $this->render('index', [
            'aircraftModels' => $aircraftModels,
            'pagination' => $pagination,
            'searchModel' => $searchModel, // Pass the search model to the view
        ]);
    }
    

    public function actionView($id)
    {
        $aircraftModel = AircraftModel::findOne($id);
        return $this->render('view', ['aircraftModel' => $aircraftModel]);
    }

    public function actionCreate()
    {
        $aircraftModel = new AircraftModel();
        if ($aircraftModel->load(Yii::$app->request->post()) && $aircraftModel->save()) {
            Yii::$app->session->setFlash('message', 'Aircraft Model created successfully!');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'aircraftModel' => $aircraftModel,
        ]);
    }

    public function actionUpdate($id)
    {
        $aircraftModel = AircraftModel::findOne($id);
        if ($aircraftModel->load(Yii::$app->request->post()) && $aircraftModel->save()) {
            Yii::$app->session->setFlash('message', 'Aircraft Model updated successfully!');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'aircraftModel' => $aircraftModel,
        ]);
    }

    public function actionDelete($id)
    {
        $aircraftModel = AircraftModel::findOne($id);
        if ($aircraftModel->delete()) {
            Yii::$app->session->setFlash('message', 'Aircraft Model deleted successfully!');
        } else {
            Yii::$app->session->setFlash('message', 'Failed to delete Aircraft Model!');
        }
        return $this->redirect(['index']);
    }
}

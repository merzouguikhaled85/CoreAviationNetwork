<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\Cities;
use app\models\Countries;
use yii\data\Pagination;


class CityController extends Controller
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
    public function actionCitiesAutocomplete($q = null)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $cities = Cities::find()
            ->select(['city_id', 'city_name AS text'])
            ->andFilterWhere(['like', 'city_name', $q])
            ->asArray()
            ->all();
        
        return ['results' => $cities];
    }
    public function actionIndex($search = null)
    {
        $query = Cities::find()->joinWith('country');
    
        // Apply search filter if search query is provided
        if ($search !== null) {
            $query->andFilterWhere(['like', 'cities.city_name', $search]);
        }
    
        // Add sorting by country_name and then by city_name
        $query->orderBy([
            'countries.country_name' => SORT_ASC,
            'cities.city_name' => SORT_ASC,
        ]);
    
        $countQuery = clone $query;
        $totalCount = $countQuery->count();
    
        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => 20, // Maximum number of cities per page
        ]);
    
        $cities = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
    
        return $this->render('home', [
            'cities' => $cities,
            'pagination' => $pagination,
        ]);
    }
    
    
    


    public function actionView($id)
    {
        $city = cities::findOne($id);
        if (!$city) {
            throw new \yii\web\NotFoundHttpException('The requested city does not exist.');
        }
        return $this->render('view', ['city' => $city]);
    }

    public function actionCreate()
    {
        $city = new cities();
        $countries = Countries::find()->all();
        if ($city->load(Yii::$app->request->post()) && $city->save()) {
            Yii::$app->session->setFlash('message', 'City created successfully!');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'city' => $city,
            'countries' => $countries,
        ]);
    }

    public function actionUpdate($id)
    {
        $city = cities::findOne($id);
        if (!$city) {
            throw new \yii\web\NotFoundHttpException('The requested city does not exist.');
        }
        $countries = Countries::find()->all();
        if ($city->load(Yii::$app->request->post()) && $city->save()) {
            Yii::$app->session->setFlash('message', 'City updated successfully!');
            return $this->redirect(['index']);
        }
    
        return $this->render('update', [
            'city' => $city,
            'countries' => $countries,
        ]);
    }

    public function actionDelete($id)
    {
        $city = cities::findOne($id);
        if ($city->delete()) {
            Yii::$app->session->setFlash('message', 'City deleted successfully!');
        } else {
            Yii::$app->session->setFlash('message', 'Failed to delete city!');
        }
        return $this->redirect(['index']);
    }
}

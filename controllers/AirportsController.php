<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\Airports;
use app\models\Cities;
use app\models\Countries;
use yii\data\Pagination;
use yii\web\Response;

class AirportsController extends Controller
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
    public function actionIndex($search = null)
    {
        $query = Airports::find()->joinWith(['city', 'country'])->orderBy(['airport_name' => SORT_ASC]);
        
        // Apply search filter if search query is provided
        if ($search !== null) {
            $query->andFilterWhere(['like', 'airport_name', $search])
                  ->orFilterWhere(['like', 'icao', $search])
                  ->orFilterWhere(['like', 'cities.city_name', $search])
                  ->orFilterWhere(['like', 'countries.country_name', $search]);
        }
        $query->orderBy(['airport_name' => SORT_ASC]); // Order by country_name ascending

        $countQuery = clone $query;
        $totalCount = $countQuery->count();
        
        $pagination = new Pagination(['totalCount' => $totalCount]);
        $pagination->pageSize = 20; // Adjust the page size as needed
        
        $airports = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
        
        return $this->render('index', [
            'airports' => $airports,
            'pagination' => $pagination,
        ]);
    }

    public function actionView($id)
    {
        $airport = Airports::findOne($id);
        return $this->render('view', ['airport' => $airport]);
    }


// Controller Action Adjustment
public function actionCreate()
{
    $airport = new Airports();
    $cities = Cities::find()->orderBy(['city_name' => SORT_ASC])->all();
    $countries = Countries::find()->orderBy(['country_name' => SORT_ASC])->all();

    // Check if the form is submitted and data is loaded into the $airport model
    if ($airport->load(Yii::$app->request->post())) {
        // Populate country_name and city_name based on the selected country_id and city_id
        $countryId = Yii::$app->request->post('Airports')['country_id'];
        $cityId = Yii::$app->request->post('Airports')['city_id'];
        
        $country = Countries::findOne($countryId);
        $city = Cities::findOne($cityId);

        // Set the country_name and city_name attributes of the $airport model
        $airport->country_name = $country->country_name;
        $airport->city_name = $city->city_name;
        $airport->country_id = $countryId;
        // Save the airport model
        if ($airport->save()) {
            Yii::$app->session->setFlash('message', 'Airport created successfully!');
            return $this->redirect(['index']);
        }
    }

    // Render the create view with the necessary data
    return $this->render('create', [
        'airport' => $airport,
        'cities' => $cities,
        'countries' => $countries,
    ]);
}

 
public function actionUpdate($id)
{
    $airport = Airports::findOne($id);
    $cities = Cities::find()->orderBy(['city_name' => SORT_ASC])->all();
    $countries = Countries::find()->orderBy(['country_name' => SORT_ASC])->all();

    // Check if the form is submitted and data is loaded into the $airport model
    if ($airport->load(Yii::$app->request->post())) {
        // Populate country_name and city_name based on the selected country_id and city_id
        $countryId = $airport->country_id;
        $cityId = $airport->city_id;
        
        $country = Countries::findOne($countryId);
        $city = Cities::findOne($cityId);

        // Set the country_name and city_name attributes of the $airport model
        $airport->country_name = $country->country_name;
        $airport->city_name = $city->city_name;

        // Save the airport model
        if ($airport->save()) {
            Yii::$app->session->setFlash('message', 'Airport updated successfully!');
            return $this->redirect(['index']);
        }
    }

    // Render the update view with the necessary data
    return $this->render('update', [
        'airport' => $airport,
        'cities' => $cities,
        'countries' => $countries,
    ]);
}

    

    public function actionDelete($id)
    {
        $airport = Airports::findOne($id);
        if ($airport->delete()) {
            Yii::$app->session->setFlash('message', 'Airport deleted successfully!');
        } else {
            Yii::$app->session->setFlash('message', 'Failed to delete airport!');
        }
        return $this->redirect(['index']);
    }

    // Controller action to fetch cities based on country ID
    public function actionGetCities($countryId)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Perform database query to fetch cities based on the country ID
        $cities = Cities::find()->where(['country_id' => $countryId])->all();

        // Prepare data to be sent back as JSON
        $data = [];
        foreach ($cities as $city) {
            $data[] = [
                'id' => $city->id,
                'name' => $city->name,
            ];
        }

        return ['cities' => $data];
    }
}

<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
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
            // Une suppression ne doit jamais être déclenchée par une URL GET.
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['delete' => ['POST']],
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


// Charger uniquement les données nécessaires au formulaire de création.
public function actionCreate()
{
    $airport = new Airports();
    $cityOptions = [];
    $countries = Countries::find()
        ->select(['country_id', 'country_name'])
        ->orderBy(['country_name' => SORT_ASC])
        ->asArray()
        ->all();

    if ($airport->load(Yii::$app->request->post())) {
        // Vérifier les identifiants et l'appartenance de la ville au pays choisi.
        $countryId = filter_var($airport->country_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $cityId = filter_var($airport->city_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $country = $countryId ? Countries::findOne($countryId) : null;
        $city = $cityId && $country ? Cities::find()
            ->where(['city_id' => $cityId, 'country_id' => $countryId])
            ->one() : null;

        // Les noms proviennent de la base, jamais des champs cachés du navigateur.
        $airport->country_name = $country ? $country->country_name : null;
        $airport->city_name = $city ? $city->city_name : null;
        if ($city) {
            $cityOptions[$city->city_id] = $city->city_name;
        }

        if (!$country) {
            $airport->addError('country_id', 'Please select a valid country.');
        }
        if (!$city) {
            $airport->addError('city_id', 'Please select a city belonging to the selected country.');
        }
        if ($country && $city && $airport->save()) {
            Yii::$app->session->setFlash('message', 'Airport created successfully!');
            return $this->redirect(['index']);
        }
    }

    return $this->render('create', [
        'airport' => $airport,
        'cityOptions' => $cityOptions,
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

    // Limiter les recherches pour préserver la mémoire de l'hébergement partagé.
    public function actionGetCities($countryId, $term = '', $page = 1)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $countryId = filter_var($countryId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $page = filter_var($page, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
        if (!$countryId || !$page || !is_string($term) || strlen($term) > 200) {
            throw new \yii\web\BadRequestHttpException('Invalid city search.');
        }

        $cities = Cities::find()
            ->select(['id' => 'city_id', 'name' => 'city_name'])
            ->where(['country_id' => $countryId])
            ->andFilterWhere(['like', 'city_name', trim($term)])
            ->orderBy(['city_name' => SORT_ASC, 'city_id' => SORT_ASC])
            ->offset(($page - 1) * 50)
            ->limit(51)
            ->asArray()
            ->all();

        return [
            'cities' => array_slice($cities, 0, 50),
            'countryId' => $countryId,
            'pagination' => ['more' => count($cities) > 50],
        ];
    }
}
